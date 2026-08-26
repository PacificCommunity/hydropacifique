<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — geographic data bulk save
Called by saveDataGeo() in gestion_geo.php.
Receives the entire formDataGeo via multipart POST.
Saves all six entity types in a single database transaction:
  1. Geographic regions   (TABLE_REGION)
  2. Towns                (TABLE_COMMUNE)
  3. Hydrological regions (TABLE_REGIONHYDRO)
  4. Rivers               (TABLE_RIVIERE)
  5. Aquifers             (TABLE_GEO_AQUIFERE)        — legacy table, no territoire field
  6. Rounds               (TABLE_TOURNEE)

Validation rules (any failure rolls back ALL writes):
- 'nom'         : required, max 255 chars, trimmed
- 'description' : max 500 chars
- Duplicates    : refused per entity (see uniqueness scope below)

Uniqueness scope per entity:
  - Region geo   : (nom_region,  id_territoire)
  - Commune      : (nom_commune, id_region)
  - Region hydro : (nom,         id_territoire)
  - Riviere      : (nom,         id_regionhydro)
  - Aquifere     : (nom)                              — legacy, no territoire
  - Tournee      : (nom,         id_territoire)

Security: all POST inputs are sanitised via post_secure().
Returns JSON:
  erreur   : bool   — true if the transaction failed
  msg_info : string — feedback message for the user
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Validation constants

define('GEO_NOM_MAX_LENGTH',  255);
define('GEO_DESC_MAX_LENGTH', 500);


// -----------------------------------------------
// Validation helpers

/**
 * Validate a 'nom' field: required, trimmed, max length.
 * On failure, push a contextual error message into $errors and return false.
 */
function geo_validate_nom($value, $context_label, &$errors)
{
    $trimmed = trim((string)$value);
    if ($trimmed === '')
    {
        $errors[] = sprintf(TEXT_GEO_SAVE_ERR_NOM_EMPTY, $context_label);
        return false;
    }
    if (mb_strlen($trimmed) > GEO_NOM_MAX_LENGTH)
    {
        $errors[] = sprintf(TEXT_GEO_SAVE_ERR_NOM_TOO_LONG, $context_label, GEO_NOM_MAX_LENGTH);
        return false;
    }
    return true;
}

/**
 * Validate a 'description' field: optional, trimmed, max length.
 */
function geo_validate_desc($value, $context_label, &$errors)
{
    if (mb_strlen(trim((string)$value)) > GEO_DESC_MAX_LENGTH)
    {
        $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DESC_TOO_LONG, $context_label, GEO_DESC_MAX_LENGTH);
        return false;
    }
    return true;
}

/**
 * Check whether a name already exists for the given uniqueness scope.
 * Returns true if duplicate found.
 *
 *   $sql_link        : MySQLi link
 *   $table           : TABLE_* constant
 *   $nom_column      : column name for the entity's name (e.g. 'nom_region', 'nom')
 *   $nom_value       : value to test (must be already sanitised)
 *   $parent_column   : optional FK column for scoped uniqueness (e.g. 'id_territoire'),
 *                      or null for global uniqueness
 *   $parent_value    : value of the parent FK (cast to int upstream)
 *   $exclude_id_col  : when updating, column name of the row's own id to exclude
 *                      (e.g. 'id', 'id_region'); null when inserting
 *   $exclude_id_val  : value of the row's own id to exclude
 */
function geo_duplicate_exists($sql_link, $table, $nom_column, $nom_value,
                              $parent_column = null, $parent_value = null,
                              $exclude_id_col = null, $exclude_id_val = null)
{
    $sql = "SELECT 1 FROM " . $table
         . " WHERE LOWER(TRIM(" . $nom_column . ")) = LOWER(TRIM('" . $nom_value . "'))";

    if ($parent_column !== null)
    {
        $sql .= " AND " . $parent_column . " = " . (int)$parent_value;
    }
    if ($exclude_id_col !== null && $exclude_id_val !== null)
    {
        $sql .= " AND " . $exclude_id_col . " <> " . (int)$exclude_id_val;
    }
    $sql .= " LIMIT 1";

    $r = tep_db_fetch_array(tep_db_query($sql_link, $sql));
    return !empty($r);
}


// -----------------------------------------------
// Initialise output variables

$msg_info_send = '';
$erreur        = false;
$errors        = []; // collected validation errors


// -----------------------------------------------
// Process the POST request

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $id_user_agent = $_POST['id_user_agent'] ?? '';
    $territoire_id = $_POST['territoire_id'] ?? '';

    // Wrap all writes in a transaction so a partial failure rolls back everything
    tep_db_query($sql_link, "START TRANSACTION");

    try
    {
        // ---- 1. Geographic regions ----

        $regiongeo_query = tep_db_query($sql_link,
            "SELECT DISTINCT id_region, nom_region FROM " . TABLE_REGION
            . " WHERE id_territoire = " . (int)$territoire_id);
        while ($regiongeo = tep_db_fetch_array($regiongeo_query))
        {
            $field = 'regiongeo_nom_' . $regiongeo['id_region'];
            if (isset($_POST[$field]))
            {
                if (!geo_validate_nom($_POST[$field], TEXT_GEO_CTX_REGION, $errors)) { continue; }

                $nom = post_secure($sql_link, trim($_POST[$field]));

                // Duplicate check (excluding self)
                if (geo_duplicate_exists($sql_link, TABLE_REGION, 'nom_region', $nom,
                                         'id_territoire', $territoire_id,
                                         'id_region', $regiongeo['id_region']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_REGION, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_REGION . " SET nom_region = '" . $nom . "'
                     WHERE id_region = " . (int)$regiongeo['id_region']);
            }
        }
        if (isset($_POST['regiongeo_nom_0']) && tep_not_null(trim($_POST['regiongeo_nom_0'])))
        {
            if (geo_validate_nom($_POST['regiongeo_nom_0'], TEXT_GEO_CTX_REGION, $errors))
            {
                $nom = post_secure($sql_link, trim($_POST['regiongeo_nom_0']));

                if (geo_duplicate_exists($sql_link, TABLE_REGION, 'nom_region', $nom,
                                         'id_territoire', $territoire_id))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_REGION, $nom);
                }
                else
                {
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_REGION . " (nom_region, id_territoire)
                         VALUES ('" . $nom . "', " . (int)$territoire_id . ")");
                }
            }
        }


        // ---- 2. Towns ----

        $commune_query = tep_db_query($sql_link,
            "SELECT DISTINCT id_commune FROM " . TABLE_COMMUNE
            . " WHERE id_territoire = " . (int)$territoire_id);
        while ($commune = tep_db_fetch_array($commune_query))
        {
            $field = 'commune_nom_' . $commune['id_commune'];
            if (isset($_POST[$field]))
            {
                if (!geo_validate_nom($_POST[$field], TEXT_GEO_CTX_COMMUNE, $errors)) { continue; }

                $nom    = post_secure($sql_link, trim($_POST[$field]));
                $region = (int) post_secure($sql_link, $_POST['select_commune_regiongeo_' . $commune['id_commune']]);

                if (geo_duplicate_exists($sql_link, TABLE_COMMUNE, 'nom_commune', $nom,
                                         'id_region', $region,
                                         'id_commune', $commune['id_commune']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_COMMUNE, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_COMMUNE
                    . " SET nom_commune = '" . $nom . "', id_region = " . $region
                    . " WHERE id_commune = " . (int)$commune['id_commune']);
            }
        }
        if (isset($_POST['commune_nom_0']) && tep_not_null(trim($_POST['commune_nom_0'])))
        {
            if (geo_validate_nom($_POST['commune_nom_0'], TEXT_GEO_CTX_COMMUNE, $errors))
            {
                $nom    = post_secure($sql_link, trim($_POST['commune_nom_0']));
                $region = (int) post_secure($sql_link, $_POST['select_commune_regiongeo_0']);

                if (geo_duplicate_exists($sql_link, TABLE_COMMUNE, 'nom_commune', $nom,
                                         'id_region', $region))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_COMMUNE, $nom);
                }
                else
                {
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_COMMUNE . " (nom_commune, id_region, id_territoire)
                         VALUES ('" . $nom . "', " . $region . ", " . (int)$territoire_id . ")");
                }
            }
        }


        // ---- 3. Hydrological regions ----

        $regionhydro_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_REGIONHYDRO
            . " WHERE id_territoire = " . (int)$territoire_id);
        while ($regionhydro = tep_db_fetch_array($regionhydro_query))
        {
            $field = 'regionhydro_nom_' . $regionhydro['id'];
            if (isset($_POST[$field]))
            {
                $nom_raw  = $_POST[$field];
                $desc_raw = $_POST['regionhydro_description_' . $regionhydro['id']] ?? '';

                if (!geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_REGIONHYDRO, $errors)) { continue; }
                if (!geo_validate_desc($desc_raw, TEXT_GEO_CTX_REGIONHYDRO, $errors)) { continue; }

                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_REGIONHYDRO, 'nom', $nom,
                                         'id_territoire', $territoire_id,
                                         'id', $regionhydro['id']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_REGIONHYDRO, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_REGIONHYDRO . " SET nom = '" . $nom . "', description = '" . $desc . "'
                     WHERE id = " . (int)$regionhydro['id']);
            }
        }
        if (isset($_POST['regionhydro_nom_0']) && tep_not_null(trim($_POST['regionhydro_nom_0'])))
        {
            $nom_raw  = $_POST['regionhydro_nom_0'];
            $desc_raw = $_POST['regionhydro_description_0'] ?? '';

            if (geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_REGIONHYDRO, $errors)
             && geo_validate_desc($desc_raw, TEXT_GEO_CTX_REGIONHYDRO, $errors))
            {
                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_REGIONHYDRO, 'nom', $nom,
                                         'id_territoire', $territoire_id))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_REGIONHYDRO, $nom);
                }
                else
                {
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_REGIONHYDRO . " (nom, description, id_territoire)
                         VALUES ('" . $nom . "', '" . $desc . "', " . (int)$territoire_id . ")");
                }
            }
        }


        // ---- 4. Rivers ----

        $riviere_query = tep_db_query($sql_link,
            "SELECT DISTINCT id FROM " . TABLE_RIVIERE
            . " WHERE id_territoire = " . (int)$territoire_id);
        while ($riviere = tep_db_fetch_array($riviere_query))
        {
            $field = 'riviere_nom_' . $riviere['id'];
            if (isset($_POST[$field]))
            {
                $nom_raw  = $_POST[$field];
                $desc_raw = $_POST['riviere_description_' . $riviere['id']] ?? '';

                if (!geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_RIVIERE, $errors)) { continue; }
                if (!geo_validate_desc($desc_raw, TEXT_GEO_CTX_RIVIERE, $errors)) { continue; }

                $nom         = post_secure($sql_link, trim($nom_raw));
                $desc        = post_secure($sql_link, trim($desc_raw));
                $regionhydro = (int) post_secure($sql_link, $_POST['select_riviere_regionhydro_' . $riviere['id']]);

                if (geo_duplicate_exists($sql_link, TABLE_RIVIERE, 'nom', $nom,
                                         'id_regionhydro', $regionhydro,
                                         'id', $riviere['id']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_RIVIERE, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_RIVIERE
                    . " SET nom = '" . $nom . "', description = '" . $desc . "', id_regionhydro = " . $regionhydro
                    . " WHERE id = " . (int)$riviere['id']);
            }
        }
        if (isset($_POST['riviere_nom_0']) && tep_not_null(trim($_POST['riviere_nom_0'])))
        {
            $nom_raw  = $_POST['riviere_nom_0'];
            $desc_raw = $_POST['riviere_description_0'] ?? '';

            if (geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_RIVIERE, $errors)
             && geo_validate_desc($desc_raw, TEXT_GEO_CTX_RIVIERE, $errors))
            {
                $nom         = post_secure($sql_link, trim($nom_raw));
                $desc        = post_secure($sql_link, trim($desc_raw));
                $regionhydro = (int) post_secure($sql_link, $_POST['select_riviere_regionhydro_0']);

                if (geo_duplicate_exists($sql_link, TABLE_RIVIERE, 'nom', $nom,
                                         'id_regionhydro', $regionhydro))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_RIVIERE, $nom);
                }
                else
                {
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_RIVIERE . " (nom, description, id_regionhydro, id_territoire)
                         VALUES ('" . $nom . "', '" . $desc . "', " . $regionhydro . ", " . (int)$territoire_id . ")");
                }
            }
        }


        // ---- 5. Aquifers ----
        // Legacy table: NO id_territoire column. Uniqueness is global on nom.

        $aquifere_query = tep_db_query($sql_link,
            "SELECT DISTINCT id, nom, description FROM " . TABLE_GEO_AQUIFERE);
        while ($aquifere = tep_db_fetch_array($aquifere_query))
        {
            $field = 'aquifere_nom_' . $aquifere['id'];
            if (isset($_POST[$field]))
            {
                $nom_raw  = $_POST[$field];
                $desc_raw = $_POST['aquifere_description_' . $aquifere['id']] ?? '';

                if (!geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_AQUIFERE, $errors)) { continue; }
                if (!geo_validate_desc($desc_raw, TEXT_GEO_CTX_AQUIFERE, $errors)) { continue; }

                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_GEO_AQUIFERE, 'nom', $nom,
                                         null, null,
                                         'id', $aquifere['id']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_AQUIFERE, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_GEO_AQUIFERE . " SET nom = '" . $nom . "', description = '" . $desc . "'
                     WHERE id = " . (int)$aquifere['id']);
            }
        }
        if (isset($_POST['aquifere_nom_0']) && tep_not_null(trim($_POST['aquifere_nom_0'])))
        {
            $nom_raw  = $_POST['aquifere_nom_0'];
            $desc_raw = $_POST['aquifere_description_0'] ?? '';

            if (geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_AQUIFERE, $errors)
             && geo_validate_desc($desc_raw, TEXT_GEO_CTX_AQUIFERE, $errors))
            {
                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_GEO_AQUIFERE, 'nom', $nom))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_AQUIFERE, $nom);
                }
                else
                {
                    // FIX: legacy table has no id_territoire column
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_GEO_AQUIFERE . " (nom, description)
                         VALUES ('" . $nom . "', '" . $desc . "')");
                }
            }
        }


        // ---- 6. Rounds ----

        $tournee_query = tep_db_query($sql_link,
            "SELECT DISTINCT id, nom, description FROM " . TABLE_TOURNEE
            . " WHERE id_territoire = " . (int)$territoire_id);
        while ($tournee = tep_db_fetch_array($tournee_query))
        {
            $field = 'tournee_nom_' . $tournee['id'];
            if (isset($_POST[$field]))
            {
                $nom_raw  = $_POST[$field];
                $desc_raw = $_POST['tournee_description_' . $tournee['id']] ?? '';

                if (!geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_TOURNEE, $errors)) { continue; }
                if (!geo_validate_desc($desc_raw, TEXT_GEO_CTX_TOURNEE, $errors)) { continue; }

                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_TOURNEE, 'nom', $nom,
                                         'id_territoire', $territoire_id,
                                         'id', $tournee['id']))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_TOURNEE, $nom);
                    continue;
                }

                tep_db_query($sql_link,
                    "UPDATE " . TABLE_TOURNEE . " SET nom = '" . $nom . "', description = '" . $desc . "'
                     WHERE id = " . (int)$tournee['id']);
            }
        }
        if (isset($_POST['tournee_nom_0']) && tep_not_null(trim($_POST['tournee_nom_0'])))
        {
            $nom_raw  = $_POST['tournee_nom_0'];
            $desc_raw = $_POST['tournee_description_0'] ?? '';

            if (geo_validate_nom ($nom_raw,  TEXT_GEO_CTX_TOURNEE, $errors)
             && geo_validate_desc($desc_raw, TEXT_GEO_CTX_TOURNEE, $errors))
            {
                $nom  = post_secure($sql_link, trim($nom_raw));
                $desc = post_secure($sql_link, trim($desc_raw));

                if (geo_duplicate_exists($sql_link, TABLE_TOURNEE, 'nom', $nom,
                                         'id_territoire', $territoire_id))
                {
                    $errors[] = sprintf(TEXT_GEO_SAVE_ERR_DUPLICATE, TEXT_GEO_CTX_TOURNEE, $nom);
                }
                else
                {
                    tep_db_query($sql_link,
                        "INSERT INTO " . TABLE_TOURNEE . " (nom, description, id_territoire)
                         VALUES ('" . $nom . "', '" . $desc . "', " . (int)$territoire_id . ")");
                }
            }
        }


        // ---- Final decision: any error → rollback the whole transaction ----
        if (!empty($errors))
        {
            tep_db_query($sql_link, "ROLLBACK");

            $msg_info_send  = "<span style='font-size:14px;font-weight:bold;'>" . TEXT_GEO_SAVE_ERR_VALIDATION . "</span><br>";
            $msg_info_send .= "<ul style='margin:5px 0 0 20px;padding:0;'>";
            foreach ($errors as $e)
            {
                $msg_info_send .= "<li>" . $e . "</li>";
            }
            $msg_info_send .= "</ul>";
            $erreur = true;
        }
        else
        {
            // ---- Log the action ----
            $type_action = 13; // Platform settings action type
            $today_us    = date('Y-m-d H:i:s');

            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . (int)$id_user_agent . ", '" . $type_action . "', '" . TEXT_GEO_SAVE_ACTION_LOG . "', '" . $today_us . "')");

            // All operations succeeded — commit the transaction
            tep_db_query($sql_link, "COMMIT");

            $msg_info_send = TEXT_GEO_SAVE_OK;
        }
    }
    catch (Exception $e)
    {
        // Any exception rolls back all writes to keep the database consistent
        tep_db_query($sql_link, "ROLLBACK");

        $msg_info_send  = TEXT_GEO_SAVE_ERR_WRITE . "<br>";
        $msg_info_send .= TEXT_GEO_SAVE_ERR_DETAIL . $e->getMessage();
        $erreur         = true;
    }
}
else
{
    // Request method is not POST — should not happen in normal use
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_GEO_SAVE_ERR_REQUEST . "</span><br><br>";
    $erreur        = true;
}


// Return JSON response to the client
echo json_encode([
    'erreur'   => $erreur,
    'msg_info' => $msg_info_send,
]);
?>

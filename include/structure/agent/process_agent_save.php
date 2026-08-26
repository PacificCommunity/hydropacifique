<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save (create or update) an agent record
- post_secure(): sanitizes form input against JS/PHP injection
- Numeric/date/time fields: empty values are valid
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: text/html; charset=utf-8');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Query: Users lookup

$user_list_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
while ($user_list = tep_db_fetch_array($user_list_query))
{
    $user_list_array[$user_list['id']] = [
        'id_statut' => $user_list['id_statut'],
        'login'     => html_entity_decode($user_list['login']  ?? ''),
        'nom'       => ucfirst(strtolower(html_entity_decode($user_list['nom']    ?? ''))),
        'prenom'    => ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? ''))),
    ];
}


// -----------------------------------------------
// Initialize

$msg_info_send = '';
$msg_info      = '';
$erreur        = false;
$newAgent      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $id_user_agent = $_POST['id_user_agent'] ?? '';
    $territoire_id = $_POST['territoire_id'] ?? '';
    $id_agent      = $_POST['id_agent_fiche'] ?? '';

    $check_terrain       = isset($_POST['check_terrain'])       ? 1 : 0;
    $check_service_hydro = isset($_POST['check_service_hydro']) ? 1 : 0;

    $nom            = post_secure($sql_link, $_POST['nom']            ?? '');
    $nom_marital    = post_secure($sql_link, $_POST['nom_marital']    ?? '');
    $prenom         = post_secure($sql_link, $_POST['prenom']         ?? '');
    $raisonsociale  = post_secure($sql_link, $_POST['raisonsociale']  ?? '');
    $numinscription = post_secure($sql_link, $_POST['numinscription'] ?? '');
    $fonction       = post_secure($sql_link, $_POST['fonction']       ?? '');
    $tel            = post_secure($sql_link, $_POST['tel']            ?? '');
    $mobile         = post_secure($sql_link, $_POST['mobile']         ?? '');
    $fax            = post_secure($sql_link, $_POST['fax']            ?? '');
    $email          = post_secure($sql_link, $_POST['email']          ?? '');
    $siteweb        = post_secure($sql_link, $_POST['siteweb']        ?? '');
    $adresse        = post_secure($sql_link, $_POST['adresse']        ?? '');
    $lieudit        = post_secure($sql_link, $_POST['lieudit']        ?? ''); // Bug fix: was $_lieudit
    $bp             = post_secure($sql_link, $_POST['bp']             ?? '');
    $codepostal     = post_secure($sql_link, $_POST['codepostal']     ?? '');
    $select_commune = isset($_POST['select_commune']) ? post_secure($sql_link, $_POST['select_commune']) : '';

    // Validate: name is required
    if (!tep_not_null($nom))
    {
        $erreur    = true;
        $msg_info .= TEXT_AGENT_SAVE_ERR_NOM . "<br>";
    }

    // Validate: no duplicate name + firstname
    if ($id_agent < 1) // New agent
    {
        $verif_query = tep_db_query($sql_link,
            "SELECT EXISTS (
                SELECT 1 FROM " . TABLE_AGENT . "
                WHERE nom = '" . $nom . "' AND prenom = '" . $prenom . "' LIMIT 1
            ) AS agent_exists");
        $verif = tep_db_fetch_array($verif_query);
        if ($verif['agent_exists'] == 1)
        {
            $erreur    = true;
            $msg_info .= TEXT_AGENT_SAVE_ERR_DUPLICATE . " " . $nom . " " . $prenom . " — " . TEXT_AGENT_SAVE_ERR_DUPLICATE_SUFFIX . "<br>";
        }
    }


    // -----------------------------------------------
    // Save to database

    if (!$erreur)
    {
        $type_action      = 13; // Settings action type
        $dateheure_action = date("Y-m-d H:i:s");

        if ($id_agent < 1) // New agent
        {
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_AGENT . " (nom, prenom) VALUES ('" . $nom . "', '" . $prenom . "')");
            $id_agent = mysqli_insert_id($sql_link);

            if (HP_VERSION == 'Nomad')
            {
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_AGENT . " SET from_nomad = 1, new_nomad = 1 WHERE id = " . $id_agent);
            }

            $newAgent      = true;
            $msg_info_send = "<span style='font-size:16px;'>" . TEXT_AGENT_SAVE_CREATED . "</span><br><br>";
            $msg_info      = TEXT_AGENT_LABEL . " : " . $nom . " - " . $prenom;

            $info_action = post_secure($sql_link, TEXT_AGENT_SAVE_ACTION_CREATE . "<br>" . $msg_info);
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . $info_action . "', '" . $dateheure_action . "')");
        }

        $from_nomad = 0;
        $new_nomad  = 0;
        if (HP_VERSION == 'nomad') { $from_nomad = 1; $new_nomad = 1; }

        tep_db_query($sql_link,
            "UPDATE " . TABLE_AGENT . " SET
                from_nomad      = '" . $from_nomad           . "',
                new_nomad       = '" . $new_nomad            . "',
                nom             = '" . $nom                  . "',
                nom_marital     = '" . $nom_marital          . "',
                prenom          = '" . $prenom               . "',
                raisonsociale   = '" . $raisonsociale        . "',
                numinscription  = '" . $numinscription       . "',
                fonction        = '" . $fonction             . "',
                tel             = '" . $tel                  . "',
                mobile          = '" . $mobile               . "',
                fax             = '" . $fax                  . "',
                email           = '" . $email                . "',
                siteweb         = '" . $siteweb              . "',
                adresse         = '" . $adresse              . "',
                lieudit         = '" . $lieudit              . "',
                bp              = '" . $bp                   . "',
                codepostal      = '" . $codepostal           . "',
                id_commune      = '" . $select_commune       . "',
                terrain         = '" . $check_terrain        . "',
                niveau          = '" . $check_service_hydro  . "'
             WHERE id = " . $id_agent);

        if (HP_VERSION == 'Nomad')
        {
            tep_db_query($sql_link,
                "UPDATE " . TABLE_AGENT . " SET from_nomad = 1 WHERE id = " . $id_agent);
        }

        if (!$newAgent)
        {
            $msg_info_send = "<span style='font-size:16px;'>" . TEXT_AGENT_SAVE_UPDATED . "</span><br><br>";
            $msg_info      = TEXT_AGENT_LABEL . " : " . $nom . " - " . $prenom;

            $info_action = post_secure($sql_link, TEXT_AGENT_SAVE_ACTION_UPDATE . "<br>" . $msg_info);
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . $info_action . "', '" . $dateheure_action . "')");
        }
    }
    else
    {
        $msg_info_send = "<span style='font-size:16px;'>" . TEXT_AGENT_SAVE_ERR_GENERAL . "</span><br><br>";
    }
}
else
{
    $msg_info_send = "<span style='font-size:16px;'>" . TEXT_AGENT_SAVE_ERR_REQUEST . "</span><br><br>";
}

$msg_info_send .= $msg_info;

echo json_encode(['erreur' => $erreur, 'id_agent' => $id_agent, 'msg_info' => $msg_info_send]);
?>

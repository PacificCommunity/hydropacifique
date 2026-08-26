<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
File    : application_top.php
Purpose : Global application bootstrap, loaded on every page.
          Loads config, helpers, opens the DB, starts the session,
          resolves the active user, applies access rights, and
          determines the active LANGUAGE.

Loading order matters:
  1. config.php → defines paths, INIT_T, LANGUAGE_TERRITOIRE,
     and the shared $languages_array (single source of truth for
     supported languages).
  2. Helpers and database_tables.php.
  3. DB connection + session start + IP guard.
  4. Read the territoire row (used by every page).
  5. Resolve LANGUAGE: connected user's language wins over the
     platform's default. Then load the matching text_content_*.
  6. Authorisation check + page-level access control + housekeeping
     (export cleanup, upload folder cleanup).
----------------------------------------
*/


// ----------------------------------------
// Load shared configuration (paths, INIT_T, LANGUAGE_TERRITOIRE,
// $languages_array, DB credentials, etc.)
// ----------------------------------------
require('include/config.php');


// ----------------------------------------
// Load helper functions
// ----------------------------------------
require(DIR_WS_FUNCTION . 'database.php');
require(DIR_WS_FUNCTION . 'html_output.php');

require(DIR_WS_FUNCTION . 'general.php');
require(DIR_WS_FUNCTION . 'date.php');
require(DIR_WS_FUNCTION . 'math.php');
require(DIR_WS_FUNCTION . 'stats.php');
require(DIR_WS_FUNCTION . 'gestion_erreur.php');
require(DIR_WS_FUNCTION . 'rubrique.php');
require(DIR_WS_FUNCTION . 'pagination.php');
require(DIR_WS_FUNCTION . 'search.php');
require(DIR_WS_FUNCTION . 'pdf.php');
require(DIR_WS_FUNCTION . 'form_valid.php');
require(DIR_WS_FUNCTION . 'form_multilingue_content.php');
require(DIR_WS_FUNCTION . 'envoi_mail.php');
require(DIR_WS_FUNCTION . 'html_affichage.php');
require(DIR_WS_FUNCTION . 'barre_progression.php');

require(DIR_WS_FUNCTION . 'password.php');
require(DIR_WS_FUNCTION . 'sessions.php');
require(DIR_WS_FUNCTION . 'ip_controle.php');

require(DIR_WS_FUNCTION . 'sql_function.php');


// ----------------------------------------
// Database table name constants
// ----------------------------------------
require(DIR_WS_INCLUDE . 'database_tables.php');


// UTF-8 output
header('Content-Type: text/html; charset=utf-8');


// ----------------------------------------
// Open the database connection
// ----------------------------------------
global $sql_link;
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database!');
mysqli_query($sql_link, 'SET NAMES UTF8');


// Cleanup the session table (drop expired connections)
clean_connexion($sql_link);

// Prevent the session id from being added to URLs
ini_set('url_rewriter.tags', '');

// Start the session if it isn't already (config.php may have started it)
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}


// ----------------------------------------
// IP guard — reject blacklisted IPs and notify by email
// ----------------------------------------
if (ip_out($sql_link))
{
    $message      = "";
    $message_info = "";
    $message_info .= "!!! Access attempt from a prohibited IP !!!<br><br>";
    $message      .= $message_info . $message_content;

    mail_simple($expediteur, $adresse_reponse, $destinataire, $sujet, $copie_mail, $message);

    tep_redirect('error.html');
    tep_db_close($sql_link);
    die();
}


// ----------------------------------------
// Print mode flag — used by some pages to render a print-friendly view
// ----------------------------------------
$print = false;
if (isset($_GET['print']) && tep_not_null($_GET['print']) && $_GET['print'] == 'ok')
{
    $print = true;
}


// ----------------------------------------
// Default authorisation state — refined later for non-public pages
// ----------------------------------------
$autorisation = false;
$id_user      = 0;
$file_encours = basename($_SERVER['PHP_SELF']);


// ----------------------------------------
// Load the territoire row (single record per platform).
// Drives global UI/regional behaviour: theme, default region,
// timezone, map defaults, etc.
// ----------------------------------------
$sql_territoire = "SELECT DISTINCT t.id_territoire, t.init_territoire, t.nom_territoire, t.theme_region,
                          t.region_default, t.service_hydro, t.color_service, t.timezone_php, t.lang,
                          t.mapLong, t.mapLat, t.mapZoom, t.mapMinZoom
                   FROM " . TABLE_TERRITOIRE . " t
                   WHERE t.init_territoire = '" . INIT_T . "'";
$territoire_query = tep_db_query($sql_link, $sql_territoire);
$territoire       = tep_db_fetch_array($territoire_query);

$territoire_id          = $territoire['id_territoire'];
$territoire_init        = INIT_T; // $territoire['init_territoire']
$territoire_nom         = $territoire['nom_territoire'];
$territoire_region      = $territoire['theme_region'];
$region_default         = $territoire['region_default'];
$service_hydro          = $territoire['service_hydro'];
$color_service          = $territoire['color_service'];
$timezone_php           = $territoire['timezone_php'];
$territoire_mapLong     = $territoire['mapLong'];
$territoire_mapLat      = $territoire['mapLat'];
$territoire_mapZoom     = $territoire['mapZoom'];
$territoire_mapMinZoom  = $territoire['mapMinZoom'];


// Tipping-bucket size — should ultimately be tied to the equipment type rather than kept global
$sizeAugetFix = 0.5;


// Load translation strings for the active language
require(DIR_WS_INCLUDE . 'text_content_' . LANGUAGE . '.php');


// ----------------------------------------
// Authorisation flow — runs on every page except the login page.
// Validates the session, loads user info and access rights, enforces
// page-level permissions, and runs periodic housekeeping.
// ----------------------------------------
if ($file_encours != 'login.php')
{
    // suiviSession() guards against session hijacking by checking that
    // the session matches the original client fingerprint.
    if (suiviSession($sql_link))
    {
        if (basename($_SERVER['PHP_SELF']) != 'logout.php')
        {
            $autorisation = true;
        }


        // ----------------------------------------
        // Session-bound user info
        // ----------------------------------------
        $tab_session     = getAdminInfo($sql_link);
        $id_service_user = $tab_session['id_service'];
        $id_statut       = $tab_session['id_statut'];
        $id_user         = $tab_session['admin_id'];
        $nom_user        = htmlaccent(post_secure($sql_link, $tab_session['nom']));
        $prenom_user     = htmlaccent(post_secure($sql_link, $tab_session['prenom']));
        $info_user       = htmlaccent(post_secure($sql_link, $tab_session['info']));


        // ----------------------------------------
        // Date/time helpers — bound to the platform timezone
        // ----------------------------------------
        date_default_timezone_set($timezone_php);

        $today                   = new DateTime();
        $today_formatted         = $today->format('Y-m-d');
        $today_fr_formatted      = $today->format('d-m-Y');

        $today_time              = new DateTime();
        $today_time_formatted    = $today_time->format('Y-m-d H:i:s');
        $today_time_fr_formatted = $today_time->format('d-m-Y H:i:s');


        // ----------------------------------------
        // Load the connected user's access rights
        // ----------------------------------------
        $sql_acces   = "SELECT DISTINCT gestion_data, parametre, config FROM " . TABLE_USER_ACCES;
        $where_acces = " WHERE id=" . $id_user;
        $acces_query = tep_db_query($sql_link, $sql_acces . $where_acces);
        $acces       = tep_db_fetch_array($acces_query);

        // Permission levels for the user account:
        // - $visual_data  = 1 : minimum access — read-only viewing of data
        // - $gestion_data = 1 : access to field report entry (Field Reports / RA) and stream gaugings
        // - $gestion_data = 2 : access to time-series data correction and advanced modules
        // - $parametre    = 1 : access to general parameter management (e.g. time-series configuration)
        // - $config       = 1 : access to platform administration and user management
        $visual_data  = 1;
        $gestion_data = post_secure($sql_link, $acces['gestion_data']);
        $parametre    = post_secure($sql_link, $acces['parametre']);
        $config       = post_secure($sql_link, $acces['config']);


        // ----------------------------------------
        // Page-level authorisation
        // Each protected page declares which permission flag controls it
        // in TABLE_AUTORISATION. If the file isn't listed, or the user
        // lacks the required flag, redirect to the no-access page.
        // ----------------------------------------
        $sql_ctrl_acces   = "SELECT DISTINCT file, var FROM " . TABLE_AUTORISATION . " WHERE file='" . $file_encours . "'";
        $ctrl_acces_query = tep_db_query($sql_link, $sql_ctrl_acces);
        $ctrl_acces       = tep_db_fetch_array($ctrl_acces_query);

        if (isset($ctrl_acces) && tep_not_null($ctrl_acces['file']))
        {
            if (isset(${$ctrl_acces['var']}))
            {
                if (${$ctrl_acces['var']} == 0)
                {
                    tep_redirect('noaccess.html');
                    tep_db_close($sql_link);
                    die();
                }
            }
            else
            {
                tep_redirect('noaccess.html');
                tep_db_close($sql_link);
                die();
            }
        }
        else
        {
            tep_redirect('noaccess.html');
            tep_db_close($sql_link);
            die();
        }


        // ----------------------------------------
        // Housekeeping #1 — drop export files older than one month
        // (action type 36 = export)
        // ----------------------------------------
        $sql_cleanexport = "SELECT DISTINCT id, id_user, type_action, info, dateheure, file_export
                            FROM " . TABLE_ACTIONS . "
                            WHERE type_action = 36
                              AND dateheure < DATE_SUB(NOW(), INTERVAL 1 MONTH)";

        $cleanexport_query = tep_db_query($sql_link, $sql_cleanexport);
        while ($cleanexport_tab = tep_db_fetch_array($cleanexport_query))
        {
            $file_export = DIR_WS_DATA_EXPORT . $cleanexport_tab['file_export'];

            if (file_exists($file_export))
            {
                unlink($file_export);
            }
        }


        // ----------------------------------------
        // Housekeeping #2 — drop import files older than 48 hours
        // ----------------------------------------
        $folder_upload = DIR_WS_DATA_IMPORT . 'files/';

        $threshold = 48 * 3600; // 48h in seconds
        $now       = time();

        if (is_dir($folder_upload))
        {
            $files_upload = scandir($folder_upload);

            foreach ($files_upload as $file)
            {
                $filePath = $folder_upload . $file;

                // Skip parent/current directory entries, the index placeholder,
                // and anything that isn't a regular file
                if ($file !== '.' && $file !== '..' && $file !== 'index.html' && is_file($filePath))
                {
                    $fileAge = $now - filemtime($filePath);

                    if ($fileAge > $threshold)
                    {
                        unlink($filePath);
                    }
                }
            }
        }
    }
    else
    {
        // Session is invalid (mismatched fingerprint, expired, etc.) —
        // destroy it and force the user back to the login page.
        session_destroy();
        tep_redirect('login.php');
        die();
    }
}

?>
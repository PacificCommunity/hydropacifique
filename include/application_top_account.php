<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
File    : application_top_account.php
Purpose : Lightweight bootstrap loaded only by account-creation pages.
          Mirrors application_top.php but skips the auth flow, page
          access control, and housekeeping — those don't apply when
          a visitor has no user account yet.

Loading order:
  1. config.php → defines paths, INIT_T, LANGUAGE_TERRITOIRE, LANGUAGE,
     and the shared $languages_array.
  2. Helpers and database_tables.php.
  3. DB connection + session start + IP guard.
  4. Read the territoire row.
  5. Load the matching text_content_*.
----------------------------------------
*/

// ----------------------------------------
// Server-side error reporting (visible on the cloud server)
// ----------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);


// ----------------------------------------
// Load shared configuration (paths, INIT_T, LANGUAGE_TERRITOIRE,
// LANGUAGE, $languages_array, DB credentials, etc.)
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
// Default authorisation state — account pages are public, so no
// further access control is performed here.
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
$territoire_init        = $territoire['init_territoire'];
$territoire_nom         = htmlaccent($territoire['nom_territoire']);
$territoire_region      = htmlaccent($territoire['theme_region']);
$region_default         = htmlaccent($territoire['region_default']);
$service_hydro          = htmlaccent($territoire['service_hydro']);
$color_service          = htmlaccent($territoire['color_service']);
$timezone_php           = htmlaccent($territoire['timezone_php']);
$territoire_mapLong     = $territoire['mapLong'];
$territoire_mapLat      = $territoire['mapLat'];
$territoire_mapZoom     = $territoire['mapZoom'];
$territoire_mapMinZoom  = $territoire['mapMinZoom'];


// Load translation strings for the active language
require(DIR_WS_INCLUDE . 'text_content_' . LANGUAGE . '.php');

?>
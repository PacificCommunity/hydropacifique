<?php
/*
 * ============================================================
 * Hydro Pacifique - Shared Configuration File
 * ============================================================
 * Copyright (c) 2026 - Vai-Natura
 *
 * Common configuration loaded on every page.
 * Platform-specific settings (DB, credentials, visuals) are
 * defined in include/config_platform.php — one file per platform,
 * never shared, never committed to version control.
 * ============================================================
 */

// ============================================================
// PLATFORM CONFIG LOADER
// ============================================================
define('HP_APP', true);
require(__DIR__ . '/config_plateform.php');


// ============================================================
// AVAILABLE LANGUAGES
// ============================================================
// Single source of truth for supported languages.
// Used by:
//   - The bootstrap below to validate the platform & user language.
//   - The user form (lang dropdown).
//   - process_user_save.php and process_plateform_save.php to validate
//     the lang field on save.
// To add a new language, add a 'code => label' entry here AND create
// the matching include/text_content_<code>.php file.
// ============================================================

$languages_array = array(
    'fr' => 'fr - Français',
    'en' => 'en - English',
);


// ============================================================
// LANGUAGE BOOTSTRAP
// ============================================================
// Defines two constants used throughout the application:
//
//   LANGUAGE_TERRITOIRE - the platform's default language, read from
//                         the territoire row matching INIT_T. Stable
//                         for the whole platform.
//
//   LANGUAGE            - the active language for the current request.
//                         Resolved here so it's always defined right
//                         after config.php is loaded — including for
//                         the 133 AJAX endpoints that don't go through
//                         application_top.php.
//                         Resolution order:
//                           1. The connected user's language (read
//                              from the active session, if any).
//                           2. LANGUAGE_TERRITOIRE — platform default.
//
// One short-lived mysqli connection covers both lookups, then closes.
// The rest of the app opens its own connection later as before.
// Raw mysqli is used because helpers aren't loaded yet at this point.
// ============================================================

$_lang_link = @mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
$_lang_territoire = 'fr'; // Safe fallback if anything goes wrong
$_lang_user       = null;

if ($_lang_link)
{
    mysqli_query($_lang_link, 'SET NAMES utf8mb4');

    // ---- 1. Platform language (territoire) ----
    // INIT_T is hard-coded in config_plateform.php so it's safe to inject directly.
    $_lang_query = mysqli_query($_lang_link,
        "SELECT lang FROM geo_territoire
         WHERE init_territoire = '" . INIT_T . "'
         LIMIT 1");

    if ($_lang_query && ($_lang_row = mysqli_fetch_assoc($_lang_query)))
    {
        if (array_key_exists($_lang_row['lang'], $languages_array))
        {
            $_lang_territoire = $_lang_row['lang'];
        }
    }

    // ---- 2. Connected user's language (if a session exists) ----
    // Resume the session silently so session_id() returns a usable value.
    // session_status() avoids restarting an already-active session.
    if (session_status() === PHP_SESSION_NONE)
    {
        @session_start();
    }

    $_session_id = session_id();

    if (!empty($_session_id))
    {
        $_session_id_safe = mysqli_real_escape_string($_lang_link, $_session_id);

        // Adjust table names below if they differ in your database_tables.php
        $_lang_query = mysqli_query($_lang_link,
            "SELECT a.lang
             FROM ad_session s
             JOIN ad_user a ON s.admin_id = a.id
             WHERE s.sid = '" . $_session_id_safe . "'
               AND a.active = 1
             LIMIT 1");

        if ($_lang_query && ($_lang_row = mysqli_fetch_assoc($_lang_query)))
        {
            if (array_key_exists($_lang_row['lang'], $languages_array))
            {
                $_lang_user = $_lang_row['lang'];
            }
        }
    }

    // Close the temporary connection — the rest of the app will
    // open its own connection as before.
    mysqli_close($_lang_link);
}

define('LANGUAGE_TERRITOIRE', $_lang_territoire);
define('LANGUAGE',            $_lang_user ?? $_lang_territoire);

// Clean up temporary variables to avoid polluting the global scope
unset($_lang_link, $_lang_query, $_lang_row, $_lang_territoire, $_lang_user, $_session_id, $_session_id_safe);

// ============================================================
// GLOBAL SITE IDENTITY
// ============================================================

define('TITRE_SITE',      'Hydro Pacifique - Data Management and Processing Platform');
define('HP_NOMAD',        'HP - Nomad');
define('VERSION_HP',      'v1.7.1');
define('DATE_VERSION_HP', '26/06/2026');


// ============================================================
// SESSION
// ============================================================

// Idle session timeout, in SECONDS — every comparison against this constant is
// against a time() delta (see sessions.php: controleSessionInfo(),
// double_connexion(), clean_connexion()). It was previously documented as
// milliseconds, which made the real window ~2h46m rather than the 10s implied.
define('SESSION_TIMEOUT', 7200); // 2 hours


// ============================================================
// DATABASE TABLE PREFIXES
// ============================================================

define('DB_TABLE_PREFIX',       '');
define('DB_TABLE_PREFIX_ADMIN', DB_TABLE_PREFIX . 'ad_');
define('DB_TABLE_PREFIX_CTRL',  DB_TABLE_PREFIX . 'ctrl_');


// ============================================================
// EMAIL SETTINGS
// ============================================================

define('MAIL_CONTACT',      'contact@ahmes.net');
define('MAIL_CONTACT_NAME', 'Hydro Pacifique - Contact');
define('MAIL_NOREPLY',      'noreply@ahmes.net');
define('MAIL_NOREPLY_NAME', 'Hydro Pacifique - NoReply');


// ============================================================
// DIRECTORY PATHS — PHP include files
// ============================================================

define('DIR_WS_INCLUDE',     'include/');
define('DIR_WS_STRUCTURE',   DIR_WS_INCLUDE . 'structure/');

define('DIR_WS_LOG',         DIR_WS_STRUCTURE . 'log/');
define('DIR_WS_CHRON',       DIR_WS_STRUCTURE . 'chron/');
define('DIR_WS_STATION',     DIR_WS_STRUCTURE . 'station/');
define('DIR_WS_FILTRE',      DIR_WS_STRUCTURE . 'filtre/');
define('DIR_WS_IMPORT',      DIR_WS_STRUCTURE . 'import/');
define('DIR_WS_EXPORT',      DIR_WS_STRUCTURE . 'export/');
define('DIR_WS_CSV',         DIR_WS_EXPORT    . 'csv/');
define('DIR_WS_ADMIN',       DIR_WS_STRUCTURE . 'admin/');
define('DIR_WS_INDEX',       DIR_WS_STRUCTURE . 'index/');
define('DIR_WS_GRAPH',       DIR_WS_STRUCTURE . 'graph/');
define('DIR_WS_TYPEDATA',    DIR_WS_STRUCTURE . 'typedata/');
define('DIR_WS_QUALITYDATA', DIR_WS_STRUCTURE . 'qualitydata/');
define('DIR_WS_EQJGE',       DIR_WS_STRUCTURE . 'eq_jge/');
define('DIR_WS_JAUGEAGE',    DIR_WS_STRUCTURE . 'jaugeage/');
define('DIR_WS_ETL',         DIR_WS_STRUCTURE . 'etl/');
define('DIR_WS_CALCUL',      DIR_WS_STRUCTURE . 'calcul/');
define('DIR_WS_MODCALCUL',   DIR_WS_STRUCTURE . 'mod_calcul/');
define('DIR_WS_RA',          DIR_WS_STRUCTURE . 'ra/');
define('DIR_WS_DIAG',        DIR_WS_STRUCTURE . 'diag/');
define('DIR_WS_AGENT',       DIR_WS_STRUCTURE . 'agent/');
define('DIR_WS_GEO',         DIR_WS_STRUCTURE . 'geographie/');
define('DIR_WS_FORMULAIRE',  DIR_WS_INCLUDE   . 'ctrl_form/');
define('DIR_WS_SUPPRIMER',   DIR_WS_INCLUDE   . 'suppression/');
define('DIR_WS_BOX',         DIR_WS_STRUCTURE . 'box/');
define('DIR_WS_FUNCTION',    'function/'); // ← corrigé


// ============================================================
// DIRECTORY PATHS — Data files
// ============================================================

define('DIR_WS_DATA',                 'data/');
define('DIR_WS_DATA_EXPORT',          DIR_WS_DATA . 'export/');
define('DIR_WS_DATA_IMPORT',          DIR_WS_DATA . 'uploads/');
define('DIR_WS_DATA_CORRECTIONS',     DIR_WS_DATA . 'corrections/');
define('DIR_WS_DATA_PHOTOS',          DIR_WS_DATA . 'photos_station/');
define('DIR_WS_STATION_PHOTO_ACCESS', DIR_WS_DATA_PHOTOS . 'access/');
define('DIR_WS_PDF',                  DIR_WS_DATA . 'pdf/');
define('DIR_WS_TXT',                  DIR_WS_DATA . 'txt/');


// ============================================================
// DIRECTORY PATHS — Assets
// ============================================================

define('DIR_WS_IMG',     'image/');
define('DIR_WS_IMG_ICO', DIR_WS_IMG . 'icones/');
define('DIR_WS_IMG_PDF', DIR_WS_IMG . 'pdf/');


// ============================================================
// AUTOLOADER (Composer)
// ============================================================

require_once __DIR__ . '/../vendor/autoload.php';



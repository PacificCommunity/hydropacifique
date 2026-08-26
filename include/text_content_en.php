<?php
/*
--------------------------------------------------------
Copyright (c) 2024 - Vai-Natura
--------------------------------------------------------
English translation dictionary - Vai-Natura application
Organised by functional module for ease of maintenance.

To add a new language, duplicate this file as text_content_fr.php
(or the relevant locale suffix) and update the constant values.
The active file is loaded via:
require('text_content_' . LANGUAGE . '.php');
--------------------------------------------------------
*/


// ============================================================
// GENERAL / SHARED
// ============================================================

define('LANG_BTN_LOGIN',            'Log in');
define('LANG_BTN_SAVE',             'Save');
define('LANG_BTN_VALIDATE',         'Validate');

// ============================================================
// LOGIN PAGE  (login.php)
// ============================================================

define('LANG_LOGIN_BAD_CREDENTIALS','Login information is incorrect');
define('LANG_LOGIN_BRUTE_FORCE',    '!!! Protected access - brute-force attempt detected !!!');
define('LANG_LOGIN_DOUBLE_SESSION', 'An active session was detected for this account.');
define('LANG_LOGIN_SESSION_CLOSED', 'As a security measure, the session has been closed.');
define('LANG_LOGIN_RECONNECT',      'Please log in again.');

// ============================================================
// LOGOUT PAGE  (logout.php)
// ============================================================

define('LANG_LOGOUT_ON_APP',        'You are on the application');
define('LANG_LOGOUT_SESSION_ENDED', 'Your session has ended');
define('LANG_LOGOUT_LINK_LOGIN',    'Log in');

// ============================================================
// LOGOUT OVERLAY BLOCK  (block_logout.php)
// ============================================================

define('LANG_LOGOUT_CONFIRMED',     'You have been logged out of');
define('LANG_LOGOUT_BTN_BACK',      'Back');

// ============================================================
// POPUP DIALOGS
// ============================================================
 
define('TEXT_POPUP_CLOSE',             'Close');
define('TEXT_POPUP_VALIDATE',          'Confirm');
define('TEXT_POPUP_CANCEL',            'Cancel');
define('TEXT_POPUP_DELETE_CONFIRM',    'Are you sure you want to delete the data?');
define('TEXT_POPUP_DELETE_IRREVERSIBLE', 'This permanently deletes the selected data. It cannot be recovered.');
define('TEXT_POPUP_SAVE_CONFIRM',      'Are you sure you want to validate the corrections?');
define('TEXT_POPUP_SAVE_OVERWRITE',    'If data already exists for the same series, station and period, it will be overwritten.');
define('TEXT_POPUP_DELETE_CHALLENGE_LABEL', 'Solve this to confirm:');


// ============================================================
// LOGIN OVERLAY BLOCK  (block_login.php)
// ============================================================

define('LANG_BLOCK_LOGIN_TITLE',        'Sign in');
define('LANG_BLOCK_LOGIN_FIELD_PH',     'Email or username');
define('LANG_BLOCK_LOGIN_PASS_PH',      'Password');
define('LANG_BLOCK_LOGIN_BTN_CONNECT',  'Connect');
define('LANG_BLOCK_LOGIN_BTN_CANCEL',   'Cancel');
define('LANG_BLOCK_LOGIN_FORGOT_PASS',  'Forgot password?');
define('LANG_BLOCK_LOGIN_CREATE_ACCT',  'Create an account');

// ============================================================
// CHANGE PASSWORD PAGE  (mdp.php)
// ============================================================

define('LANG_MDP_TITLE',            'Change my password');
define('LANG_MDP_OLD_PASS',         'Current password');
define('LANG_MDP_NEW_PASS',         'New password');
define('LANG_MDP_CONFIRM_PASS',     'Confirm new password');
define('LANG_MDP_STRENGTH_WEAK',    'weak');
define('LANG_MDP_STRENGTH_MEDIUM',  'medium');
define('LANG_MDP_STRENGTH_STRONG',  'strong');

// ============================================================
// CREATE ACCOUNT PAGE  (account_new.php)
// ============================================================

define('LANG_ACCT_NEW_TITLE',           'Create a new access account');
define('LANG_ACCT_NEW_LOGIN',           'Username');
define('LANG_ACCT_NEW_LOGIN_HINT',      'This field cannot contain spaces, accents, or special characters');
define('LANG_ACCT_NEW_LOGIN_PH',        'Username');
define('LANG_ACCT_NEW_EMAIL',           'Email address');
define('LANG_ACCT_NEW_EMAIL_PH',        'mail@example.com');
define('LANG_ACCT_NEW_ORGA',            'Organisation');
define('LANG_ACCT_NEW_ORGA_PH',         'Organisation');
define('LANG_ACCT_NEW_LASTNAME',        'Last name');
define('LANG_ACCT_NEW_LASTNAME_PH',     'Last name');
define('LANG_ACCT_NEW_FIRSTNAME',       'First name');
define('LANG_ACCT_NEW_FIRSTNAME_PH',    'First name');

// ============================================================
// ACCOUNT CONFIRMATION PAGE  (account_confirm.php)
// ============================================================

define('LANG_CONFIRM_TITLE',        'Account creation confirmation');
define('LANG_CONFIRM_EMAIL_SENT',   'A confirmation email has been sent to (check your spam folder):');
define('LANG_CONFIRM_ENTER_CODE',   'Please enter the confirmation code');
define('LANG_CONFIRM_CODE_PH',      'Confirmation code');
define('LANG_CONFIRM_RESEND_PRE',   'If the email has not arrived, you can ');
define('LANG_CONFIRM_RESEND',       'Resend the code');
define('LANG_CONFIRM_CODE_SHORT',   'The entered code is incorrect');

// ============================================================
// FORGOT PASSWORD PAGE  (account_mail.php)
// ============================================================

define('LANG_MAIL_TITLE',           'Password access');
define('LANG_MAIL_FIELD_LABEL',     'Enter your username or email address');
define('LANG_MAIL_FIELD_PH',        'Username or email address');

// ============================================================
// SET / RESET PASSWORD PAGE  (account_valid.php)
// ============================================================

define('LANG_VALID_TITLE',              'Set your password');
define('LANG_VALID_PASS_RULE',          'The password must contain at least 8 characters');
define('LANG_VALID_PASS_HINT',          'Uppercase, lowercase and at least one special character');
define('LANG_VALID_PASS_FIRST',         'Password');
define('LANG_VALID_PASS_FIRST_PH',      'New password');
define('LANG_VALID_PASS_CONFIRM',       'Confirm password');
define('LANG_VALID_PASS_CONFIRM_PH',    'Confirm password');

// =============================================================================
// CONNEXION - SECURITY 
// =============================================================================


// -----------------------------------------------
// Shared / generic

define('TEXT_AC_ERR_FORM',              'The form could not be submitted.');
define('TEXT_AC_ERR_REQUEST',           'Invalid request.');
define('TEXT_AC_ERR_SESSION',           'The session has expired or is invalid. Please start again.');
define('TEXT_AC_ERR_VALIDATION',        'Validation error.');
define('TEXT_AC_ERR_MAIL',              'An error occurred while sending the email. Please contact support.');

// -----------------------------------------------
// process_account_verif-input.php - account creation form validation

define('TEXT_AC_CREATE_ERR_LOGIN_EMPTY',    'The Login field is required.');
define('TEXT_AC_CREATE_ERR_LOGIN_CHARS',    'The login must only contain letters and digits (no spaces or accented characters).');
define('TEXT_AC_CREATE_ERR_LOGIN_DUP',      'Another user already uses this login. Please choose another.');
define('TEXT_AC_CREATE_ERR_EMAIL_EMPTY',    'An email address is required.');
define('TEXT_AC_CREATE_ERR_EMAIL_FORMAT',   'The email address format is invalid.');
define('TEXT_AC_CREATE_ERR_EMAIL_DUP',      'This email address is already in use. Please enter another.');
define('TEXT_AC_CREATE_ERR_ORG_EMPTY',      'The Organisation field is required.');
define('TEXT_AC_CREATE_MSG_OK',             'If this account exists, an email has been sent to you.');
define('TEXT_AC_CREATE_ERR_MAIL',           'An error occurred while sending the email. Please contact support.');

// -----------------------------------------------
// process_account_verif-logmail.php


// ---- Password reset - step 1 ----
define('TEXT_AC_RESET_ERR_FIELD_EMPTY',  'Please fill in this field.');
define('TEXT_AC_RESET_ERR_NOT_FOUND',    'No account matches this login or email address.');
define('TEXT_AC_RESET_MSG_OK',           'A confirmation code has been sent to your email address.');
define('TEXT_AC_RESET_MSG_REDIRECT',     'You will be redirected shortly.');
 
// ---- Password reset email ----
define('TEXT_MAIL_RESET_TITLE',          'Confirmation code');
define('TEXT_MAIL_RESET_SUBTITLE',       'New password');
define('TEXT_MAIL_RESET_HELLO',          'Hello');
define('TEXT_MAIL_RESET_INSTRUCTION',    'Use the following code to verify your identity.');
define('TEXT_MAIL_RESET_VALIDITY',       'This code is valid for 15 minutes.');
define('TEXT_MAIL_RESET_ENTER_CODE',     'Enter your code here:');
define('TEXT_MAIL_RESET_LINK_LABEL',     'Verify your identity');
define('TEXT_MAIL_RESET_WARN_MISUSE',    'If you did not initiate this request, your email address may have been misused.');
define('TEXT_MAIL_RESET_WARN_IGNORE',    'Ignore this email or contact support:');
define('TEXT_MAIL_RESET_AUTO_GENERATED', 'This email was generated automatically. Please do not reply.');
define('TEXT_MAIL_RESET_SUBJECT',        'New password on %s - Confirmation code');

// -----------------------------------------------
// process_account_valid.php - email verification code check

define('TEXT_AC_VALID_ERR_USER',            'The account could not be found.');
define('TEXT_AC_VALID_ERR_TOKEN',           'The session is not valid or has expired.');
define('TEXT_AC_VALID_ERR_DATE',            'The verification code has expired.');
define('TEXT_AC_VALID_ERR_CODE',            'The code entered is incorrect.');
define('TEXT_AC_VALID_MSG_OK',              'Your account has been verified.');
define('TEXT_AC_VALID_MSG_REDIRECT',        'You will be redirected to the login page in a moment...');

// -----------------------------------------------
// process_account_pass_valid.php - password creation/reset

define('TEXT_AC_PASS_ERR_MISMATCH',         'The passwords do not match.');
define('TEXT_AC_PASS_ERR_TOO_SHORT',        'The password must be at least 8 characters long.');
define('TEXT_AC_PASS_ERR_COMPLEXITY',       'The password must contain at least one uppercase letter, one lowercase letter, and one special character.');
define('TEXT_AC_PASS_ERR_NOT_COMPLIANT',    'The password does not meet the requirements.');
define('TEXT_AC_PASS_ERR_TECH',             'A technical error occurred during activation.');
define('TEXT_AC_PASS_MSG_OK',               'Your password has been saved.');
define('TEXT_AC_PASS_MSG_REDIRECT',         'You will be redirected to the login page in a moment...');





// =============================================================================
// INDEX - HOMEPAGE
// =============================================================================

define('TEXT_INDEX_LAST_FIELD', 'LAST FIELD SHEET');
define('TEXT_INDEX_LAST_IMPORT', 'LAST IMPORT');

// -----------------------------------------------
// block_index_affiche.php

define('TEXT_IX_POPUP_CLOSE',           'Close');

// -----------------------------------------------
// process_index_last_import.php - import table

define('TEXT_IX_IMP_COL_DATE',          'Date');
define('TEXT_IX_IMP_COL_USER',          'User');
define('TEXT_IX_IMP_COL_STATION',       'Station');
define('TEXT_IX_IMP_COL_CHRON',         'Time series');
define('TEXT_IX_IMP_COL_CONSULT',       'View');
define('TEXT_IX_IMP_LINK_TITLE',        'View imported data');

// -----------------------------------------------
// process_index_last_ra.php - field report table

define('TEXT_IX_RA_COL_DATE',           'Date');
define('TEXT_IX_RA_COL_TYPE',           'Type');
define('TEXT_IX_RA_COL_STATION',        'Station');
define('TEXT_IX_RA_COL_AGENTS',         'Operator(s)');
define('TEXT_IX_RA_STATUS_VALID',       'Validated');
define('TEXT_IX_RA_STATUS_PENDING',     'Pending validation');


// =============================================================================
// SYSTEM MESSAGES & POPUPS
// =============================================================================
define('TEXT_POPUP_NOCONNEXION', 'You are not connected to the Internet. \n Some features may not be available. \n Map backgrounds will not be displayed.');




// =============================================================================
// TOP BAR
// =============================================================================
define('TEXT_TOP_FIRST', 'Home');
define('TEXT_TOP_DATE_HP', 'Date');
define('TEXT_TOP_VERSION_HP', 'Version');
define('TEXT_TOP_DATE_DATA_UPDATE', 'Last Update');
define('TEXT_TOP_COUNTRY', 'Territory');
define('TEXT_TOP_LOG', 'Account');
define('TEXT_TOP_LOG_QUAL', 'Role');
define('TEXT_TOP_ADMIN', 'Administration');
define('TEXT_TOP_PASS', 'Change my password');
define('TEXT_TOP_CLOSE', 'Log out');

// =============================================================================
// NAVIGATION MENU
// =============================================================================

// -----------------------------------------------
// Data
define('TEXT_MENU_DATA', 'Data');
define('TEXT_MENU_DATA_CHRON', 'Time-Series');
define('TEXT_MENU_DATA_TRACKCONNECT', 'Tracking Corrections');
define('TEXT_MENU_DATA_ACTREPORT', 'Field Reports (FR)');
define('TEXT_MENU_DATA_IMPORT', 'Import');
define('TEXT_MENU_DATA_EXPORT', 'Export');
define('TEXT_MENU_DATA_SYNC', 'Server Sync.');

// -----------------------------------------------
// Modules
define('TEXT_MENU_MOD', 'Modules');
define('TEXT_MENU_MOD_STATION', 'Stations List');
define('TEXT_MENU_MOD_JGE', 'Stream Gaugings');
define('TEXT_MENU_MOD_ETL', 'Rating Curves Q=f(H)');
define('TEXT_MENU_MOD_DIAG', 'Well Logs');
define('TEXT_MENU_MOD_AGENTS', 'Agents');

// -----------------------------------------------
// Monitoring rounds
define('TEXT_MENU_ROUND', 'Rounds');
define('TEXT_MENU_ROUND_TRACK', 'Round Tracking');
define('TEXT_MENU_ROUND_MANAGE', 'Round Management');

// -----------------------------------------------
// Settings
define('TEXT_MENU_SET', 'Settings');
define('TEXT_MENU_SET_GEO', 'Geographical Zones');
define('TEXT_MENU_SET_TYPEC', 'Time-Series');
define('TEXT_MENU_SET_QUAL', 'Quality Codes');
define('TEXT_MENU_SET_EQJGE', 'Gauging Equipment');
define('TEXT_MENU_SET_OPTION', 'Options');
define('TEXT_MENU_SET_TRANSF', 'Param Export/Import');

// -----------------------------------------------
// Platform actions
define('TEXT_MENU_HP', 'Platform Actions');
define('TEXT_MENU_HP_TRACKIMPORT', 'Import Tracking');
define('TEXT_MENU_HP_TRACKEXPORT', 'Export Tracking');
define('TEXT_MENU_HP_ACTIONS', 'All Actions');

// -----------------------------------------------
// Resources
define('TEXT_MENU_RESSOURCE', 'Resources');
define('TEXT_MENU_RESSOURCE_FIRST', 'Home');
define('TEXT_MENU_RESSOURCE_HELP', 'Help');
define('TEXT_MENU_RESSOURCE_CONDITION', 'Terms of Use');
define('TEXT_MENU_RESSOURCE_DATA', 'Data Licence');
define('TEXT_MENU_RESSOURCE_CONTACT', 'Contact');

define('TEXT_MENU_POPUP_CGU',           "General Terms of Use (GTU)");
define('TEXT_MENU_POPUP_LICENCE',       "Data licence");


// =============================================================================
// INTERACTIVE MAP
// =============================================================================
define('TEXT_MAP_TITLE', 'Interactive Map');
define('TEXT_MAP_BACK', 'Return to %s scale'); // %s sera remplacé par TEXT_TOP_COUNTRY
define('TEXT_MAP_ZOOM', 'Zoom');
define('TEXT_MAP_LONG', 'Long.');
define('TEXT_MAP_LAT', 'Lat.');
define('TEXT_MAP_ALT', 'Elev.');
define('TEXT_MAP_FULLSCREEN', 'Fullscreen Map');
define('TEXT_MAP_WINDOWED', 'Exit Fullscreen');
define('TEXT_MAP_SAVEIMG', 'Capture map as image');
define('TEXT_MAP_DLIMG', 'Download image');
define('TEXT_MAP_LEGEND_TITLE', 'Station Legend');
define('TEXT_MAP_SHOW_CODES', 'Display station codes');


define('TEXT_MAP_LENS_DEPTH', 'Freshwater lens depth:');

// =============================================================================
// STATION FILTERS
// =============================================================================

define('TEXT_FILTER_TITLE', 'Filters');
define('TEXT_FILTER_TOGGLE', 'Collapse / show filters');
define('TEXT_FILTER_RESET', 'Reset filters'); 

// -----------------------------------------------
// Status / monitoring option values

define('TEXT_FILTER_ALL', '* All');
define('TEXT_FILTER_SEARCH', 'Search');
define('TEXT_FILTER_TYPE', 'Data Type');
define('TEXT_FILTER_BV', 'Watershed');
define('TEXT_FILTER_RIVER', 'River');
define('TEXT_FILTER_AQUIFERE', 'Aquifer');
define('TEXT_FILTER_CITY', 'Town / Village');
define('TEXT_FILTER_ROUND', 'Monitoring Round');
define('TEXT_FILTER_STATION', 'Station(s)');

define('TEXT_FILTER_GEO_AREA', 'Geographic area');

// -----------------------------------------------
// Status & monitoring options
define('TEXT_FILTER_STATUT', 'Status');
define('TEXT_FILTER_STATUTACTIVE', 'Active');
define('TEXT_FILTER_STATUTHISTORIQUE', 'Historical (Closed)');
define('TEXT_FILTER_SUIVI', 'Monitoring');
define('TEXT_FILTER_SUIVICONTINU', 'Continuous');
define('TEXT_FILTER_SUIVIPONCTUEL', 'Discrete');
define('TEXT_FILTER_ETATEQ', 'Device');
define('TEXT_FILTER_ETATFONCTIONNEMENT', 'In service');
define('TEXT_FILTER_ETATPANNE', 'Out of service');

define('TEXT_FILTER_ACTIVE',        'Active');
define('TEXT_FILTER_CLOSED',        'Historical (Closed)');
define('TEXT_FILTER_CONTINU',       'Continuous ');
define('TEXT_FILTER_PONCTUEL',      'Discrete');

// -----------------------------------------------
// Filter info labels
define('TEXT_FILTER_OWNER', 'Data Owner');
define('TEXT_FILTER_NBSTATION', 'Station count');

// =============================================================================
// STATION SUMMARY (MAP POPUP)
// =============================================================================
define('TEXT_FROM_DATA', 'By');
define('TEXT_STATION_TYPE', 'Data');
define('TEXT_STATION_NOM', 'Name');
define('TEXT_STATION_CODE', 'Code');
define('TEXT_STATION_DATE_INSTALL', 'Installation');
define('TEXT_STATION_DATE_CLOSING', 'Closing Date');
define('TEXT_STATION_DATE_LASTGO', 'Date Last Visit');
define('TEXT_STATION_DELAY_LASTGO', 'Time Since Last Visit');
define('TEXT_STATION_STATUT', 'Status');
define('TEXT_STATION_SUIVI', 'Monitoring');
define('TEXT_STATION_ETATEQ', 'Device');

// -----------------------------------------------
// Station detail links
define('TEXT_STATION_LINK_FICHE', '>> Details');
define('TEXT_STATION_LINK_DATA', '>> Data');
define('TEXT_STATION_LINK_LAST_RA', '>> Field Reports');


// =============================================================================
// ACTION BUTTONS
// =============================================================================
define('TEXT_BUTTON_RA', 'Lasted Field Report');
define('TEXT_BUTTON_IMPORT', 'Lasted Imported Data');

// =============================================================================
// FIELD REPORTS (FR) & TIME SERIES
// =============================================================================
define('TEXT_CHRON_RA', 'Field visit');
define('TEXT_CHRON_RA_HEIGHT', 'Manual stage reading');
define('TEXT_CHRON_JGE', 'Stream gaugings');

// -----------------------------------------------
// Field report list
define('TEXT_TITLE_RA_LIST', 'Field Reports');
define('TEXT_NEW_RA_PLUVIO', 'New FR - Rainfall');
define('TEXT_NEW_RA_HYDRO', 'New FR - Surf. water');
define('TEXT_NEW_RA_PIEZO', 'New FR - Groundwater');

// -----------------------------------------------
// Period & sort filters
define('TEXT_PERIOD_LABEL', 'Period');
define('TEXT_PERIOD_1_MONTH', '1 month');
define('TEXT_PERIOD_3_MONTHS', '3 months');
define('TEXT_PERIOD_6_MONTHS', '6 months');
define('TEXT_PERIOD_1_YEAR', '1 year');
define('TEXT_PERIOD_2_YEARS', '2 years');
define('TEXT_PERIOD_5_YEARS', '5 years');
define('TEXT_PERIOD_10_YEARS', '10 years');
define('TEXT_PERIOD_ALL_DATA', 'All data');

define('TEXT_SORT_BY', 'Sort by');
define('TEXT_SORT_LAST_VISIT', 'Last visit');
define('TEXT_SORT_STATION_NAME', 'Name');
define('TEXT_SORT_STATION_CODE', 'Code');
define('TEXT_SORT_DATA_TYPE', 'Data');
define('TEXT_SORT_ASCENDING', 'Ascending');
define('TEXT_SORT_DESCENDING', 'Descending');

define('TEXT_NB_LINES', 'Nb lines');
define('TEXT_NB_LINES_50', '50 lines');
define('TEXT_NB_LINES_100', '100 lines');
define('TEXT_NB_LINES_200', '200 lines');
define('TEXT_NB_LINES_300', '300 lines');
define('TEXT_NB_LINES_ALL', 'All lines');

// -----------------------------------------------
// Field report counters
define('TEXT_NB_RA_TO_VALIDATE', 'To validate: ');
define('TEXT_NB_TOTAL_RA', 'All field reports: ');

// -----------------------------------------------
// Field report table headers
define('TEXT_TABLE_HEADER_STATUS', 'Step');
define('TEXT_TABLE_HEADER_DATE', 'Date');
define('TEXT_TABLE_HEADER_DATA_TYPE', 'Data');
define('TEXT_TABLE_HEADER_STATION_CODE', 'Code');
define('TEXT_TABLE_HEADER_STATION_NAME', 'Name');
define('TEXT_TABLE_HEADER_COMMUNE', 'Town / Village');
define('TEXT_TABLE_HEADER_AGENTS', 'Operator(s)');

// -----------------------------------------------
// Loading states
define('TEXT_LOADING', 'Loading ...');
define('TEXT_PLEASE_WAIT', '- Please wait -');

// -----------------------------------------------
// Deletion confirmation dialog
define('TEXT_RA_DELETE_CONFIRMATION', 'Are you sure you want to delete this field report?');
define('TEXT_RA_STATION_INFO', 'Station');
define('TEXT_RA_DATE_INFO', 'Date');
define('TEXT_DELETE_BUTTON', 'Delete');
define('TEXT_CANCEL_BUTTON', 'Cancel');

// -----------------------------------------------
// Deletion result messages
define('TEXT_RA_DELETE_SUCCESS', 'The field report has been successfully deleted.');
define('TEXT_RA_DELETE_ERROR', 'An error occurred while deleting the field report.');

// -----------------------------------------------
// Field toggle buttons
define('TEXT_TOGGLE_HIDE_FIELDS', '[ Hide hidden fields ]');
define('TEXT_TOGGLE_SHOW_FIELDS', '[ Show hidden fields ]');

define('TEXT_SELECT2_STATION_PLACEHOLDER', 'Select a Station...');
define('TEXT_SELECT2_TYPE_PLACEHOLDER', 'Choose or enter a type...');
define('TEXT_SELECT2_NUMBER_PLACEHOLDER', 'Choose or enter a number...');
define('TEXT_SELECT2_PROBE_NUMBER_PLACEHOLDER', 'Choose or enter a probe number...');
define('TEXT_SELECT2_INSTRUMENT_PLACEHOLDER', 'Choose or enter an instrument type...');
define('TEXT_SELECT2_INSTRUMENT_NUMBER_PLACEHOLDER', 'Choose or enter an instrument number...');
define('TEXT_SELECT2_BENCHMARK_TYPE_PLACEHOLDER', 'Choose or enter an benchmark type...');


// -----------------------------------------------
// RA tab - navigation & misc
define('TEXT_STEP_0', 'Step 0');
define('TEXT_STEP_1', 'Step 1');
define('TEXT_DELETE_RA', 'Delete FR');
define('TEXT_NO_RA_FOUND', 'No field reports found.');

// -----------------------------------------------
// Field report form labels
define('TEXT_MODIFY', 'Edit');
define('TEXT_SELECT_STATION', '-- Station --');
define('TEXT_RA_VALIDATED', 'Validated');
define('TEXT_RA_NOT_VALIDATED', 'Not validated');
define('TEXT_ENTERED_ON', 'Created: ');
define('TEXT_BY', 'by: ');
define('TEXT_RA_NOT_FOUND', 'FR not found.');
define('TEXT_CANNOT_CREATE_RA', 'Cannot create FR (%s) with current filters.');
define('TEXT_CANCEL', 'Cancel');
define('TEXT_SAVE', 'Save');
define('TEXT_READING', 'Reading');
define('TEXT_READING_FILE_NAME', 'Ref. number');
define('TEXT_DATE', 'Date');
define('TEXT_TIME', 'Time');
define('TEXT_DEVICE', 'Device');
define('TEXT_TYPE', 'Type');
define('TEXT_NUMBER', 'Number');
define('TEXT_DEVICE_STATE', 'Device state');
define('TEXT_NB_TIPPINGS', 'Tipping count');
define('TEXT_NB_BYTES', 'Memory');
define('TEXT_BATTERY_NUM', 'Battery N°');
define('TEXT_BATTERY_VOLTAGE', 'Battery volt.');
define('TEXT_PREVIOUS', 'Previous');
define('TEXT_TOTALIZER', 'Totaliser');
define('TEXT_TOTAL_TYPE', 'Type');
define('TEXT_CUMUL_ARRIVAL', 'Arrival level');
define('TEXT_CUMUL_DEPARTURE', 'Departure level');
define('TEXT_TIPPING_TIME', 'Tip time');
define('TEXT_CONTROL', 'Control automatic Rain Gauge');
define('TEXT_CUMUL_TOTAL', 'Total cumul');
define('TEXT_CUMUL_RAIN', 'Rain gauge cumul');
define('TEXT_TOTAL_RAIN', 'Total rain');
define('TEXT_TIME_ADJUSTMENT', 'Time adj.');
define('TEXT_AUGET_TEST', 'Bucket test');
define('TEXT_NEW_CASSETTE', 'New cassette');
define('TEXT_CASSETTE_NUM', 'Cassette N°');
define('TEXT_INIT_TIME', 'Init. time');
define('TEXT_FIRST_TIPPING_TIME', '1st tip');
define('TEXT_RECORDING_DURATION', 'Rec. duration');
define('TEXT_DAYS', 'DD');
define('TEXT_HOURS', 'HH');
define('TEXT_MINUTES', 'MM');
define('TEXT_SECONDES', 'SS');
define('TEXT_LAST_RECORDING', 'Last recording');
define('TEXT_OBSERVATIONS', 'Comment');
define('TEXT_MARKABLE_ACTION', 'Notable action');
define('TEXT_FUTURE_ACTIONS', 'To do');
define('TEXT_PARTICIPANTS', 'Operator(s)');
define('TEXT_CLOGGING', 'Clogged');
define('TEXT_TOTAL_OIL', 'Total oil');
define('TEXT_CLEARING', 'Clearing');
define('TEXT_BATTERY_WATER', 'Battery water');
define('TEXT_DATA_TRANSFER', 'Data transfer');
define('TEXT_MEMORY_CLEARED', 'Memory cleared');
define('TEXT_IMPORTANT_ACTION', 'Key action');
define('TEXT_AGENTS_PARTICIPATED', 'Operator(s)');
define('TEXT_FIRST_RA', 'First FR');
define('TEXT_PREVIOUS_RA', 'Previous FR');
define('TEXT_NEXT_RA', 'Next FR');
define('TEXT_LAST_RA', 'Last FR');
define('TEXT_RA_VALIDATION', 'Validation');

// -----------------------------------------------
// Hydrometric station - stage & gauging fields
define('TEXT_PROBE_INFO', 'Sensor info');
define('TEXT_WATER_LEVEL', 'Water level (manually)');
define('TEXT_PROBE_HEIGHT', 'Sensor stage');
define('TEXT_SCALE_HEIGHT', 'Staff gauge');
define('TEXT_SCALE_HEIGHT_2', 'Staff gauge 2');
define('TEXT_HYDRO_CONTROL', 'Stage check');
define('TEXT_SCALE_PROBE_DIFF', 'Gauge − Sensor');
define('TEXT_PROBE_ADJUSTMENT', 'Sensor offset');
define('TEXT_PROBE_TIME_ADJUSTMENT', 'Clock adj.');
define('TEXT_DATA_PURGE', 'Data clean');
define('TEXT_GAUGING', 'Stream Gauging');

// -----------------------------------------------
// Piezometric station form labels
define('TEXT_FIELD_FORM_PIEZO_DISPLAY', 'Field form - GroundWater');
define('TEXT_MODIFY_RA', 'Edit FR');
define('TEXT_MEASUREMENT_POSITION', 'Location');
define('TEXT_X_GPS_POSITION', 'X - GPS');
define('TEXT_Y_GPS_POSITION', 'Y - GPS');
define('TEXT_COORD_SYSTEM', 'Coord. system');
define('TEXT_GPS_PRECISION', 'GPS acc.');
define('TEXT_CONDUCTIVITY_PROFILE', 'Well log');
define('TEXT_FIXED_PROBE_CHARACTERISTICS', 'Automatic Sensor');
define('TEXT_MANUAL_PROBE_CHARACTERISTICS', 'Manual Sensor (Reference point)');
define('TEXT_FIXED_PROBE_MEASUREMENT', 'Automatic Sensor Data');
define('TEXT_MANUAL_PROBE_MEASUREMENT', 'Manual Sensor Data');
define('TEXT_WATER_TABLE_DEPTH', 'Water table depth');
define('TEXT_CONDUCTIVITY', 'Conductivity');
define('TEXT_TEMPERATURE', 'Temperature');
define('TEXT_MARKER_NATURE', 'Benchmark type');
define('TEXT_TOTAL_DEPTH', 'Total depth');
define('TEXT_FIXED_PROBE_ADJUSTMENT', 'Automatic Sensor Offset');
define('TEXT_DIFF_MANUAL_FIXED', 'Manual − fixed');
define('TEXT_DEVICE_STATE_FIXED_PROBE', 'Automatic Sensor State');
define('TEXT_NB_DATA', 'Nb data');
define('TEXT_BATTERY_PERCENT', 'Memory');
define('TEXT_PUMPING_IN_PROGRESS', 'Pumping active');
define('TEXT_NEARBY_PUMPING', 'Closer pumping');
define('TEXT_RAIN_FLOOD', 'Rain and/or flood');
define('TEXT_DRY_DAY', 'Dry day');
define('TEXT_PHOTOS', 'Photos');
define('TEXT_DEPTH_PROFILE', 'Well log');
define('TEXT_BACK', 'Back');
define('TEXT_DEPTH', 'Depth');

define('TEXT_SELECT_DEVICE_TYPE', '-- Device type --');
define('TEXT_SELECT_DEVICE_NUMBER', '-- Device N° --');
define('TEXT_SELECT_PROBE_NUMBER', '-- Probe N° --');
define('TEXT_SELECT_INSTRUMENT_TYPE', '-- Instr. type --');
define('TEXT_SELECT_INSTRUMENT_NUMBER', '-- Instrument N° --');
define('TEXT_SELECT_NATURE_REPERE', '-- Benchmark type --');

define('TEXT_LAST_RA_DATE', 'Date');
define('TEXT_LAST_RA_TOTAL', 'Tot');
define('TEXT_LAST_RA_TIPPINGS', 'Tips');
define('TEXT_NO_PREVIOUS_DATA', '-');

// -----------------------------------------------
// Field report save - validation & result messages
define('TEXT_DB_CONNECTION_ERROR', 'Unable to connect to database!');
define('TEXT_STATION_NOT_EXIST', 'The station does not exist.<br>');
define('TEXT_INVALID_READING_DATE_FORMAT', 'The reading date is not in the correct format: dd-mm-YYYY.<br>');
define('TEXT_INVALID_READING_TIME_FORMAT', 'The reading time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_DEVICE_TIME_FORMAT', 'The device time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_CASSETTE_INIT_TIME_FORMAT', 'The new cassette initialization time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_CASSETTE_PROBE_HEIGHT', 'The new cassette probe height must be a number.<br>');
define('TEXT_INVALID_CASSETTE_FIRST_TIP_TIME_FORMAT', 'The first tipping time of the new cassette is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_FIRST_TOTALIZER_VALUE', 'The first Storage Gauge cumulative value must be a number.<br>');
define('TEXT_INVALID_LAST_TOTALIZER_VALUE', 'The last Storage Gauge cumulative value must be a number.<br>');
define('TEXT_INVALID_TOTALIZER_TIP_TIME_FORMAT', 'The Storage Gauge tipping time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_TOTALIZER_CUMUL_VALUE', 'The Storage Gauge cumulative value must be a number.<br>');
define('TEXT_INVALID_RAIN_CUMUL_VALUE', 'The rain cumulative value must be a number.<br>');
define('TEXT_INVALID_TOTALIZER_RAIN_DIFF_VALUE', 'The Storage Gauge - Rain Gauge difference value must be a number.<br>');
define('TEXT_INVALID_RAIN_ADJUSTMENT_TIME_FORMAT', 'The rain adjustment time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_TIPPINGS_COUNT_VALUE', 'The tippings count value must be a number.<br>');
define('TEXT_INVALID_WATER_LEVEL_TIME_FORMAT', 'The water level measurement time is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_PROBE_HEIGHT_VALUE', 'The probe height value must be a number.<br>');
define('TEXT_INVALID_SCALE_HEIGHT_VALUE', 'The scale height value must be a number.<br>');
define('TEXT_INVALID_SCALE_HEIGHT_2_VALUE', 'The scale height 2 value must be a number.<br>');
define('TEXT_INVALID_PROBE_ADJUSTMENT_VALUE', 'The probe adjustment value must be a number.<br>');
define('TEXT_INVALID_PROBE_TIME_ADJUSTMENT_FORMAT', 'The probe time adjustment is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_WATER_TABLE_DEPTH_VALUE', 'The water table depth value must be a number.<br>');
define('TEXT_INVALID_CONDUCTIVITY_VALUE', 'The conductivity value must be a number.<br>');
define('TEXT_INVALID_TEMPERATURE_VALUE', 'The temperature value must be a number.<br>');
define('TEXT_INVALID_PIEZO_ADJUSTMENT_VALUE', 'The piezometer adjustment value must be a number.<br>');
define('TEXT_INVALID_PIEZO_PROBE_ADJUSTMENT_VALUE', 'The piezometer probe adjustment value must be a number.<br>');
define('TEXT_INVALID_PIEZO_PROBE_TIME_ADJUSTMENT_FORMAT', 'The piezometer probe time adjustment is not in the correct format: hh:mm:ss or hh:mm.<br>');
define('TEXT_INVALID_MANUAL_WATER_TABLE_DEPTH_VALUE', 'The manual water table depth value must be a number.<br>');
define('TEXT_INVALID_TOTAL_WELL_DEPTH_VALUE', 'The total well depth value must be a number.<br>');
define('TEXT_INVALID_GPS_X_VALUE', 'The GPS X position value must be a number.<br>');
define('TEXT_INVALID_GPS_Y_VALUE', 'The GPS Y position value must be a number.<br>');
define('TEXT_NEW_RA_CREATED', 'The new field report has been successfully created');
define('TEXT_STATION_DATE_INFO', 'Station');
define('TEXT_NEW_RA_ACTION', 'Creation of a new field report<br>');
define('TEXT_RA_SUCCESSFULLY_SAVED', 'The field report has been successfully saved');
define('TEXT_RA_MODIFICATION_ACTION', 'Field report modification<br>');
define('TEXT_RA_SAVE_ERROR', 'Error: The field report could not be saved');
define('TEXT_SERVER_DATA_ERROR', 'An error occurred while sending data to the server.');

define('TEXT_PIEZO_PROFIL_SAVE_WARNING',
    'Changes to the profile points are not saved until you save the RA form itself. '
  . 'Click the Save button on the field-report card to persist your edits.');
define('TEXT_PIEZO_PROFIL_SAVE_REMINDER',
    'Profile points have unsaved changes — click Save on the RA to persist them.');



// --- RA PDF sheet (field report export) ---
define('TEXT_RA_PDF_TITLE', 'Field report');
define('TEXT_RA_PDF_EDITED_ON', 'Edited on');
define('TEXT_RA_PDF_ENTRY_AGENT', 'Entered by');
define('TEXT_RA_PDF_STATION', 'Station name');
define('TEXT_RA_PDF_STATION_CODE', 'Station code');
define('TEXT_RA_PDF_COMMUNE', 'Town / Village');
define('TEXT_RA_PDF_DATE', 'Reading date');
define('TEXT_RA_PDF_TIME', 'Reading time');
define('TEXT_RA_PDF_STATUS', 'Status');
define('TEXT_RA_PDF_STATUS_FIELD', 'Field');
define('TEXT_RA_PDF_STATUS_VALID', 'Validated');
define('TEXT_RA_PDF_OBSERVATIONS', 'Observations');
define('TEXT_RA_PDF_FUTURE_ACTIONS', 'To do');
define('TEXT_RA_PDF_PARTICIPANTS', 'Participants');
define('TEXT_RA_PDF_YES', 'Yes');
define('TEXT_RA_PDF_NO', 'No');
define('TEXT_RA_PDF_NO_SELECTION', 'No sheet selected.');
define('TEXT_RA_PDF_TOO_MANY', 'Too many sheets selected (50 max). Please refine your selection.');
define('TEXT_RA_PDF_SECTION_READING_DEVICE', 'Reading and device');
define('TEXT_RA_PDF_SECTION_DEVICE_STATE', 'Device state');
define('TEXT_RA_PDF_SECTION_TOTALIZER', 'Totalizer');
define('TEXT_RA_PDF_SECTION_CONTROL', 'Control');
define('TEXT_RA_PDF_SECTION_OLD_EQUIPMENT', 'Old equipment (cassette)');
define('TEXT_RA_PDF_SECTION_MAINTENANCE', 'Maintenance / operations');
define('TEXT_RA_PDF_CHK_BOUCHAGE', 'Unclogging');
define('TEXT_RA_PDF_CHK_HUILE', 'Totalizer oil');
define('TEXT_RA_PDF_CHK_DEBROUSS', 'Brush clearing');
define('TEXT_RA_PDF_CHK_EAUBAT', 'Battery water');
define('TEXT_RA_PDF_CHK_TRANSFERT', 'Data transfer');
define('TEXT_RA_PDF_CHK_DELETEMEM', 'Memory wipe');
define('TEXT_RA_PDF_SECTION_CONTEXT', 'Context');
define('TEXT_RA_PDF_BADGE_VALID', 'Validated report');
define('TEXT_RA_PDF_BADGE_NOTVALID', 'Unvalidated report');
define('TEXT_RA_EXPORT_PDF', 'PDF');
define('TEXT_RA_EXPORT_CSV', 'CSV');
define('TEXT_RA_SELECTED', 'selected');
define('TEXT_RA_EXPORT_LIST_PDF', 'List PDF');
define('TEXT_RA_LIST_PDF_TITLE', 'Field reports list');
define('TEXT_RA_LIST_COL_VALID', 'Validated');
define('TEXT_RA_LIST_COL_STATION_NUM', 'Station Number');
define('TEXT_RA_LIST_COL_STATION_NAME', 'Station Name');
define('TEXT_RA_LIST_COL_DATE', 'Reading Date');
define('TEXT_RA_LIST_COL_TIME', 'Reading Time');
define('TEXT_RA_LIST_COL_COMMENT', 'Comment');
define('TEXT_RA_LIST_COL_PLANNED', 'Planned');
define('TEXT_RA_LIST_COL_OPERATORS', 'Operator(s)');



// =============================================================================
// DATA MODULE - TIME SERIES VIEWER
// =============================================================================

// -----------------------------------------------
// Data access - validation messages
define('TEXT_INVALID_DATE_FORMAT_1', ' is not a valid date in format ');
define('TEXT_INVALID_DATE_FORMAT_2', '');
define('TEXT_END_DATE_BEFORE_START', 'The end date must not be before the start date.');
define('TEXT_SELECT_AT_LEAST_ONE_STATION', 'You must select at least one station');
define('TEXT_SELECT_AT_LEAST_ONE_CHRONIC', 'At least one time series must be selected.');


// -----------------------------------------------
// Station selection - step 1
define('TEXT_DATA_ACCESS_STEP1', 'Data Access - Step 1: Station Selection');
define('TEXT_STATIONS_TO_SELECT', 'Stations to select');
define('TEXT_STATIONS', 'station(s)');
define('TEXT_SELECTED_STATIONS', 'Selected stations');
define('TEXT_SELECT_STATIONS', 'Select Stations');
define('TEXT_REMOVE_SELECTION', 'Remove selection');
define('TEXT_VALIDATE', 'Next step >>');

// -----------------------------------------------
// Time-series selection - step 2
define('TEXT_DATA_ACCESS_STEP2', 'Data Access - Step 2: Time-series selection');
define('TEXT_CHRONICLE_DETAILS', 'Time-series details');
define('TEXT_SELECT_PERIOD', 'Select Period');
define('TEXT_ALL_PERIODS', 'All');
define('TEXT_CURRENT_YEAR', 'Current Year');
define('TEXT_6_MONTHS', '6 Months');
define('TEXT_12_MONTHS', '12 Months');
define('TEXT_2_YEARS', '2 Years');
define('TEXT_5_YEARS', '5 Years');
define('TEXT_10_YEARS', '10 Years');
define('TEXT_20_YEARS', '20 Years');
define('TEXT_START_DATE', 'Start');
define('TEXT_END_DATE', 'End');
define('TEXT_GRAPH_VISUALIZATION', 'Visualization');
define('TEXT_COMBINED_GRAPH', 'Combined Graph');
define('TEXT_EDIT', 'Plot');
define('TEXT_DATA_EXTRACTION', 'Export data');
define('TEXT_EXPORT', 'Export');
define('TEXT_DELETE_DATA', 'Delete Data');
define('TEXT_DELETE', 'Delete');
define('TEXT_LONG_LOADING_WARNING', 'If you selected many stations, loading may take more than 1 minute');
define('TEXT_DATE_ERROR', 'Date error');
define('TEXT_START_BEFORE_END', 'Start date must be before end date');
define('TEXT_INVALID_DATE_FORMAT', 'At least one of the entered dates is invalid or in wrong format (dd-mm-yyyy: valid format)');
define('TEXT_DELETE_CONFIRMATION', 'Confirm delete action - Period :');
define('TEXT_TO', 'to');


// -----------------------------------------------
// Time-series type selector
define('TEXT_SELECT_ALL', 'Select +/-');
define('TEXT_SELECT_CHRONIC_TYPE', 'Filter by series type');
define('TEXT_NONE', '-');
define('TEXT_RA', 'FR');
define('TEXT_RA_DESC', 'Field Report');
define('TEXT_JGE', 'SG');
define('TEXT_JGE_DESC', 'Stream Gauging');
define('TEXT_ETL', 'RC');
define('TEXT_ETL_DESC', 'Rating Curve');
define('TEXT_DIAC', 'CD');
define('TEXT_DIAC_DESC', 'Well logs');
define('TEXT_LAB', 'LAB');
define('TEXT_LAB_DATA', 'LAB Data (Rain)');
define('TEXT_TOT', 'TOT');
define('TEXT_TOT_DATA', 'TOT Data (Rain)');
define('TEXT_CHRONIC', 'Series');
define('TEXT_UNIT', 'Unit');
define('TEXT_DATA_COUNT', 'Nb data');
define('TEXT_CHRONICLES', ' series');
define('TEXT_SELECT_ALL_SHORT', '+/-');
define('TEXT_NO_CHRONIC_FOUND', 'No time series found for this period.');


// -----------------------------------------------
// Graph visualisation panel
define('TEXT_DATA_VISUALIZATION', 'Data Visualization');
define('TEXT_SHOW_GAPS', 'Show gaps');
define('TEXT_STATS_LINES', 'Statistical benchmarks');
define('TEXT_RETURN_PERIOD', 'Return period (Tr)');
define('TEXT_YEARS', 'years');
define('TEXT_ZOOM_CONTROL', 'Zoom Control');
define('TEXT_Y_MIN', 'Y min');
define('TEXT_Y_MAX', 'Y max');
define('TEXT_ADJUST_SCALE', 'Adjust Scale');
define('TEXT_FIXED_PERIOD', 'Fixed period');
define('TEXT_YEAR', 'Year');
define('TEXT_MONTH', 'Month');
define('TEXT_GENERATE', 'Generate');
define('TEXT_CONFIGURATION', 'Configuration');
define('TEXT_TRACES', 'Traces');
define('TEXT_ENLARGE', 'Full Screen');
define('TEXT_GAPS_TABLE', 'Data gap inventory');
define('TEXT_GAPS', 'Data gaps');
define('TEXT_NO_DATA_FOUND', 'No data found');
define('TEXT_ZOOM_MOVE_X', 'Zoom/Move X');
define('TEXT_ZOOM_MOVE_Y', 'Zoom/Move Y');
define('TEXT_ADD_DECIMAL', 'Add decimal');
define('TEXT_REMOVE_DECIMAL', 'Remove decimal');
define('TEXT_LOG_SCALE', 'Logarithmic scale (base 10)');
define('TEXT_LOG_SCALE_SHORT', 'Log');
define('TEXT_GRAPH_FR_CTRLCLICK_HINT',
    'Click on a yellow marker to open FR');
define('TEXT_EXPORT_CSV', 'Export Data CSV');
define('TEXT_EXPORT_SVG', 'Export SVG');
define('TEXT_EXPORT_PNG', 'Export PNG');
define('TEXT_NUMERIC_ERROR', 'Error: Ymin and Ymax fields must be numbers');

define('TEXT_ZOOM_BACK',       '↶');
define('TEXT_ZOOM_BACK_TITLE', 'Back to previous zoom');

define('TEXT_GRAPH_META_QUALCODE_TITLE', 'Quality code');
define('TEXT_GRAPH_META_COVERAGE_TITLE', 'Quality codes');
define('TEXT_GRAPH_META_NO_QUALCODE',    'No quality code');
define('TEXT_GRAPH_META_START', 'Start date');
define('TEXT_GRAPH_META_END',   'End date');
define('TEXT_GRAPH_META_NBPTS', 'Nb points');
define('TEXT_FILE_QUALIF',      'qualification');
define('TEXT_FILE_GAPS',   'gaps');

// -----------------------------------------------
// Statistics panel
define('TEXT_STATS_MEAN', 'Mean');
define('TEXT_STATS_PERCENTILE_99', 'Percentile (99%)');
define('TEXT_STATS_PERCENTILE_90', 'Percentile (90%)');
define('TEXT_STATS_QUARTILE_75', 'Third Quartile (75%)');
define('TEXT_STATS_MEDIAN', 'Median (50%)');
define('TEXT_STATS_QUARTILE_25', 'First Quartile (25%)');
define('TEXT_STATS_PERCENTILE_10', 'Percentile (10%)');
define('TEXT_STATS_PERCENTILE_1', 'Percentile (1%)');

define('TEXT_STATS_COMPLEMENTARY', 'General indicators');
define('TEXT_STATS_INDICATOR',     'Indicator');
define('TEXT_STATS_VALUE',         'Value');
define('TEXT_STATS_CARD_N',            'Sample size (N)');
define('TEXT_STATS_CARD_COMPLETENESS', 'Completeness');
define('TEXT_STATS_CARD_DATE_MIN',     'Date of minimum');
define('TEXT_STATS_CARD_DATE_MAX',     'Date of maximum');
define('TEXT_STATS_CARD_CV',           'Coefficient of variation');
define('TEXT_STATS_CARD_RANGE',        'Range');
define('TEXT_STATS_CARD_CUMUL_YEAR',   'Mean annual total');
define('TEXT_STATS_CARD_DRY_LE1',      'Max dry spell (≤ 1 mm)');
define('TEXT_STATS_CARD_DRY_LE5',      'Max dry spell (≤ 5 mm)');
define('TEXT_STATS_CARD_DRY_LE20',     'Max dry spell (≤ 20 mm)');
define('TEXT_STATS_CARD_DAYS_UNIT',    'd');

// -----------------------------------------------
// Graph data processing messages
define('TEXT_TOO_MUCH_DATA', 'Data volume is too large');
define('TEXT_RECORDS', 'records');
define('TEXT_SHORTER_PERIOD', 'Data visualization is possible for a shorter period');
define('TEXT_STATS_AVAILABLE', 'Statistics for this chronicle can be calculated - >');
define('TEXT_STATISTICS', 'Statistics');
define('TEXT_QUALITY_CODE', 'Quality Code');
define('TEXT_MEAN', 'Mean');
define('TEXT_PERCENTILE_99', 'Percentile (99%)');
define('TEXT_PERCENTILE_90', 'Percentile (90%)');
define('TEXT_QUARTILE_75', 'Third Quartile (75%)');
define('TEXT_MEDIAN', 'Median');
define('TEXT_QUARTILE_25', 'First Quartile (25%)');
define('TEXT_PERCENTILE_10', 'Percentile (10%)');
define('TEXT_PERCENTILE_1', 'Percentile (1%)');
define('TEXT_RETURN_PERIOD_2', '2 years');
define('TEXT_RETURN_PERIOD_5', '5 years');
define('TEXT_RETURN_PERIOD_10', '10 years');
define('TEXT_RETURN_PERIOD_20', '20 years');
define('TEXT_RETURN_PERIOD_30', '30 years');
define('TEXT_RETURN_PERIOD_40', '40 years');
define('TEXT_RETURN_PERIOD_50', '50 years');
define('TEXT_RETURN_PERIOD_100', '100 years');
define('TEXT_RA_HEIGHT', 'FR - Manual stage');
define('TEXT_HEIGHT', 'Stage (H)');
define('TEXT_FLOW', 'Discharge (Q)');
define('TEXT_OBSERVATION', 'Comment');
define('TEXT_MODIFY_CHRONIC', 'Edit or correct time series');


// -----------------------------------------------
// Combined multi-station graph

define('TEXT_GRAPH_TITLE', 'Combined Graph');
define('TEXT_AXIS_1', 'Axis 1');
define('TEXT_AXIS_2', 'Axis 2');
define('TEXT_STATION_NAME', 'Station Name');
define('TEXT_FLIP', 'Flip');
define('TEXT_REFRESH_GRAPH', 'Refresh Graph');
define('TEXT_YMIN', 'Y min');
define('TEXT_YMAX', 'Y max');
define('TEXT_FULLSCREEN', 'Full Screen');
define('TEXT_TOOLS',            'Tools');
define('TEXT_DATA_QUALIF',      'Data qualification');
define('TEXT_EXPORT_GRAPH_CSV', 'Export chart');
define('TEXT_LOADING_WAIT', 'If the data volume is large, please wait 1 to 2 minutes');

define('TEXT_GRAPH_HOVER_DATE',  'Date');
define('TEXT_GRAPH_HOVER_VALUE', 'Value');


// -----------------------------------------------
// Graph - loading & overflow messages
define('TEXT_GRAPH_TOO_MANY_ROWS',        'The volume of data to display is too large');
define('TEXT_GRAPH_TOO_MANY_ROWS_SUB',    'for a good user experience.');
define('TEXT_GRAPH_RECORDS',              'records');
define('TEXT_GRAPH_SHORTER_PERIOD',       'Data visualization is possible over a shorter period');
define('TEXT_GRAPH_STATS_AVAILABLE',      'Statistics for this series can be calculated ->');
define('TEXT_GRAPH_STATS_LINK',           'STATISTICS');
define('TEXT_GRAPH_NO_DATA',              'No data found for this period');
define('TEXT_GRAPH_LOAD_PACKET',      'Load period');
define('TEXT_GRAPH_OR_LOAD_PACKET',   'Or load data by period:');

// -----------------------------------------------
// Graph - hover labels
define('TEXT_GRAPH_HOVER_HEIGHT',         'Stage (H)');
define('TEXT_GRAPH_HOVER_FLOW',           'Discharge (Q)');
define('TEXT_GRAPH_HOVER_OBS',            'Comment');
define('TEXT_GRAPH_HOVER_QUALCODE',       'Quality Code');
define('TEXT_GRAPH_HOVER_CORRECTION', 'Correction');
define('TEXT_GRAPH_HOVER_CORRECTION_OBS', 'Comment');

// -----------------------------------------------
// Graph - trace names
define('TEXT_CHRON_MEAN',                 'Mean');

// -----------------------------------------------
// Graph - action buttons
define('TEXT_BTN_EDIT_CHRON',             'Edit');
define('TEXT_BTN_EDIT_ALL_DATA',       'All data');
define('TEXT_BTN_EDIT_ALL_DATA_TITLE', 'Check to edit the full series, otherwise uses the currently displayed range');
define('TEXT_BTN_STATS',                  'Statistics');

// -----------------------------------------------
// Data gap table headers
define('TEXT_LAC_HEADER_TITLE',           'Data Gaps list');
define('TEXT_LAC_HEADER_CHRON',           'Chron.');
define('TEXT_LAC_HEADER_DATE_START',      'Start date');
define('TEXT_LAC_HEADER_DATE_END',        'End date');

// -----------------------------------------------
// Statistics popup

define('TEXT_STATS_TITLE',          'Statistics');
define('TEXT_STATS_STATION',        'Station');
define('TEXT_STATS_CHRONIQUE',      'Time series');
define('TEXT_STATS_BTN_GENERAL',    'General data');
define('TEXT_STATS_BTN_BYYEAR',     'Annual data summary');
define('TEXT_STATS_BTN_BYMONTH',    'Monthly data summary');
define('TEXT_STATS_BTN_BYDAYS',     'Daily data summary');
define('TEXT_STATS_BTN_LOWFLOW',    'Low-flow analysis');
define('TEXT_STATS_PERIOD',         'Evaluated period');
define('TEXT_STATS_PERIOD_FROM',    'from');
define('TEXT_STATS_PERIOD_TO',      'to');
define('TEXT_STATS_DURATION',       'Period extent');
define('TEXT_STATS_DATA',           'Data');
define('TEXT_STATS_DURATION_YEAR',  'year');
define('TEXT_STATS_DURATION_YEARS', 'years');
define('TEXT_STATS_DURATION_MONTH', 'month');
define('TEXT_STATS_DURATION_MONTHS','months');
define('TEXT_STATS_DURATION_DAY',   'day');
define('TEXT_STATS_DURATION_DAYS',  'days');
define('TEXT_STATS_DURATION_AND',   'and');

define('TEXT_STATS_CLOSE',  'Close');
define('TEXT_STATS_CLOSE_X', 'X');

// -----------------------------------------------
// MONTH ABBREVIATIONS

define('TEXT_MONTH_SHORT_JAN', 'Jan.');
define('TEXT_MONTH_SHORT_FEB', 'Feb.');
define('TEXT_MONTH_SHORT_MAR', 'Mar.');
define('TEXT_MONTH_SHORT_APR', 'Apr.');
define('TEXT_MONTH_SHORT_MAY', 'May');
define('TEXT_MONTH_SHORT_JUN', 'Jun.');
define('TEXT_MONTH_SHORT_JUL', 'Jul.');
define('TEXT_MONTH_SHORT_AUG', 'Aug.');
define('TEXT_MONTH_SHORT_SEP', 'Sep.');
define('TEXT_MONTH_SHORT_OCT', 'Oct.');
define('TEXT_MONTH_SHORT_NOV', 'Nov.');
define('TEXT_MONTH_SHORT_DEC', 'Dec.');


// -----------------------------------------------
// GENERAL STATISTICS

define('TEXT_STATS_MEDIAN_LABEL',         'median');
define('TEXT_STATS_MINIMUM',              'Minimum');
define('TEXT_STATS_MAXIMUM',              'Maximum');
define('TEXT_STATS_STD_DEV',              'Std. Dev.');
define('TEXT_STATS_CUMUL',                'Totals');
define('TEXT_STATS_YEAR',                 'Year');
define('TEXT_STATS_DAY',                  'Day');
define('TEXT_STATS_STATISTIC',            'Statistic');
define('TEXT_STATS_COMPUTED_VALUES',      'Computed values');

define('TEXT_STATS_PERCENTILE_5',         '5th Percentile');
define('TEXT_STATS_PERCENTILE_95',        '95th Percentile');

define('TEXT_STATS_INTERANNUAL_MEDIAN',   'Interannual Median');

define('TEXT_STATS_ANNUAL_SUMMARY',       'Annual data summary');
define('TEXT_STATS_MONTHLY_SUMMARY',      'Monthly data summary');
define('TEXT_STATS_DAILY_SUMMARY',        'Daily data summary');

define('TEXT_STATS_MONTHLY_CHART',        'Monthly summary chart');
define('TEXT_STATS_ANNUAL_CUMUL_CHART',   'Intra-annual cumulative chart');
define('TEXT_STATS_ANNUAL_SUMMARY_CHART', 'Intra-annual summary chart');

define('TEXT_STATS_GLOBAL_DATA',          'General Data');

define('TEXT_STATS_NON_EXCEEDANCE_FREQ',  'Non-exceedance frequency (%)');
define('TEXT_STATS_NON_EXCEEDANCE_PROB',  'Non-exceedance probability');

define('TEXT_STATS_OBSERVED_DATA',        'Comment data');
define('TEXT_STATS_CI_95',                'Confidence interval 95%');
define('TEXT_STATS_CI_LOW',               'CI low');
define('TEXT_STATS_CI_HIGH',              'CI high');


// -----------------------------------------------
// GUMBEL DISTRIBUTION (RETURN PERIODS)

define('TEXT_STATS_GUMBEL_LAW',     'Gumbel distribution');
define('TEXT_STATS_GUMBEL_PARAMS',  'Gumbel distribution parameters (95% CI)');
define('TEXT_STATS_GUMBEL_CHART',   'Gumbel fit - return period calculation');
define('TEXT_STATS_GUMBEL_U',       'Location (u)');
define('TEXT_STATS_GUMBEL_A',       'Scale (a)');

define('TEXT_STATS_ESTIMATE',       'Estimate');
define('TEXT_STATS_STD_ERROR',      'Std. Error');

define('TEXT_STATS_EXTREME_RETURN', 'Return periods of extreme maximum events');
define('TEXT_STATS_YEARS',          'years');

define('TEXT_STATS_RETURN_2Y',      '2 years');
define('TEXT_STATS_RETURN_5Y',      '5 years');
define('TEXT_STATS_RETURN_10Y',     '10 years');
define('TEXT_STATS_RETURN_20Y',     '20 years');
define('TEXT_STATS_RETURN_30Y',     '30 years');
define('TEXT_STATS_RETURN_40Y',     '40 years');
define('TEXT_STATS_RETURN_50Y',     '50 years');
define('TEXT_STATS_RETURN_100Y',    '100 years');


// -----------------------------------------------
// YEAR NAVIGATION (DAILY VIEW)

define('TEXT_STATS_PREV_YEAR', 'Previous Year');
define('TEXT_STATS_NEXT_YEAR', 'Next Year');


// -----------------------------------------------
// LOW FLOW METRICS
define('TEXT_STATS_METHODOLOGY',   'Methodology');
define('TEXT_LOWFLOW_HELP_TITLE',  'Low-flow metrics — methodological note');
define('TEXT_LOWFLOW_CHARTS_TITLE', 'Charts');
define('TEXT_LOWFLOW_PDF_SUMMARY', 'Summary PDF');
define('TEXT_LOWFLOW_PDF_FULL',    'Full PDF');

define('TEXT_LOWFLOW_MODULE',    'Module');
define('TEXT_LOWFLOW_MODULE_10', 'Module/10');
define('TEXT_LOWFLOW_MODULE_20', 'Module/20');

define('TEXT_LOWFLOW_QMNA_2',   'QMNA-2');
define('TEXT_LOWFLOW_QMNA_5',   'QMNA-5');
define('TEXT_LOWFLOW_DCE_2',    'DCE-2 (Q355)');
define('TEXT_LOWFLOW_DCE_2_50', '50% DCE-2 (Q355)');
define('TEXT_LOWFLOW_DCE_5',    'DCE-5 (Q355)');

define('TEXT_LOWFLOW_VCN3_2',   'VCN3-2');
define('TEXT_LOWFLOW_VCN3_5',   'VCN3-5');
define('TEXT_LOWFLOW_VCN7_2',   'VCN7-2');
define('TEXT_LOWFLOW_VCN7_5',   'VCN7-5');
define('TEXT_LOWFLOW_VCN10_2',  'VCN10-2');
define('TEXT_LOWFLOW_VCN10_5',  'VCN10-5');
define('TEXT_LOWFLOW_VCN30_2',  'VCN30-2');
define('TEXT_LOWFLOW_VCN30_5',  'VCN30-5');

define('TEXT_LOWFLOW_METRIC_VALUES',   'Metric values');
define('TEXT_LOWFLOW_PCT_MODULE',      '% of interannual module');
define('TEXT_LOWFLOW_NON_EXCEEDANCE',  'Non-exceedance freq. (% annual)');

define('TEXT_LOWFLOW_SERIES_TOO_SHORT', 'Warning: The data series is not long enough to compute return period metrics.');
define('TEXT_LOWFLOW_CDC_TITLE',        'Flow Duration Curve - Low Flow Metrics');

define('TEXT_LOWFLOW_FREQUENCY',      'Frequency');
define('TEXT_LOWFLOW_FLOW_CDC',       'Flow Duration Curve');
define('TEXT_LOWFLOW_DAILY_FLOW_AXIS', 'Mean daily flow (m³/s)');
define('TEXT_LOWFLOW_FLOW_AXIS',       'Flow (m³/s)');
define('TEXT_LOWFLOW_FLOW_LABEL',      'Flow');

define('TEXT_LOWFLOW_ANNEX_METRICS',      'Annex: Low flow metric calculation details');
define('TEXT_LOWFLOW_ANNEX_ANNUAL_MINIMA', 'Annex: Annual minimum flows (m³/s)');

define('TEXT_LOWFLOW_LOGNORMAL_PARAMS',  'Log-normal distribution parameters (95% CI)');
define('TEXT_LOWFLOW_LOGNORMAL_LAW',     'Log-normal distribution');
define('TEXT_LOWFLOW_OBSERVED_POINTS',   'Comment points');

define('TEXT_LOWFLOW_LOG_MU',    'Mean-log-mu');
define('TEXT_LOWFLOW_LOG_SIGMA', 'Std. dev. log-sigma');

define('TEXT_LOWFLOW_RESULTS_TITLE', 'Results');
define('TEXT_LOWFLOW_N_POINTS',      'Number of retained data points');

define('TEXT_LOWFLOW_BIENNIAL',     'Biennial (median)');
define('TEXT_LOWFLOW_QUINQUENNIAL', 'Quinquennial');
define('TEXT_LOWFLOW_DECENNIAL',    'Decennial');
define('TEXT_LOWFLOW_VICENNIAL',    'Vicennial');

define('TEXT_LOWFLOW_QMNA_LABEL',  'QMNA');
define('TEXT_LOWFLOW_DCE_LABEL',   'DCE (Q355)');
define('TEXT_LOWFLOW_VCN3_LABEL',  'VCN3');
define('TEXT_LOWFLOW_VCN7_LABEL',  'VCN7');
define('TEXT_LOWFLOW_VCN10_LABEL', 'VCN10');
define('TEXT_LOWFLOW_VCN30_LABEL', 'VCN30');


// -----------------------------------------------
// STATION INDEX / SUMMARY

define('TEXT_INDEX_ANNUAL_STAT_LABEL', 'annual');
define('TEXT_INDEX_BETWEEN',           'between');
define('TEXT_INDEX_AND',               'and');

define('TEXT_INDEX_DATA_UNUSABLE',     'Data unusable');
define('TEXT_INDEX_DATA_MISSING',      'Graph unavailable');

define('TEXT_INDEX_MEAN_FLOW',    'Mean Flow');
define('TEXT_INDEX_CUMUL',        'Cumulative');
define('TEXT_INDEX_MONTHLY_LABEL', 'Monthly');

// EXPORT

define('TEXT_ACTION_EXPORT', 'Data export');

// -----------------------------------------------
// Export page - UI labels
define('TEXT_EXPORT_PAGE_TITLE',        'Time-series export');
define('TEXT_EXPORT_COMPIL_LABEL',      'File generation');
define('TEXT_EXPORT_PROGRESS_LABEL',    'Processing progress');
define('TEXT_EXPORT_TEXTAREA_DATETIME', 'Date Time');
define('TEXT_EXPORT_TEXTAREA_WAITING',  'File compilation in progress - Please wait...');
define('TEXT_EXPORT_BTN_DOWNLOAD',      'Download the generated files');
define('TEXT_EXPORT_COPYRIGHT',         '&copy; 2024 Vai-Natura. All rights reserved.');

// -----------------------------------------------
// Export - JS progress messages (PHP-injected)
define('TEXT_EXPORT_JS_ALL_DONE',       'All files have been generated - Total processing time');
define('TEXT_EXPORT_JS_SEC',            'sec.');
define('TEXT_EXPORT_JS_NB_DATA',        'Nb Data');
define('TEXT_EXPORT_JS_COMPRESSING',    'Compressing files - Please wait...');
define('TEXT_EXPORT_JS_TIME',           'Time');

// -----------------------------------------------
// Archive file download
define('TEXT_COMPRESS_READY',     'Files are available for download (tar format)');
define('TEXT_COMPRESS_FILE',      'File');
define('TEXT_COMPRESS_SIZE',      'Size');
define('TEXT_COMPRESS_SIZE_UNIT', 'MB');


// -----------------------------------------------
// Export file content - CSV title lines
define('TEXT_CSV_TITLE_RA',    'Activity Report - Station');
define('TEXT_CSV_TITLE_JGE',   'Stream gaugings - Station');
define('TEXT_CSV_TITLE_REP',   'Piezometric benchmarks - Station');
define('TEXT_CSV_TITLE_CTE',   'Well characteristics - Station');

// -----------------------------------------------
// CSV column headers - shared
define('TEXT_CSV_COL_STATION_NUM',   'Station Number');
define('TEXT_CSV_COL_STATION_NAME',  'Station Name');
define('TEXT_CSV_COL_DATE',          'Date');
define('TEXT_CSV_COL_OBS',           'Comment');
define('TEXT_CSV_COL_AGENTS',        'Operator(s)');
define('TEXT_CSV_COL_FILE_NAME',     'File Name');
define('TEXT_CSV_COL_FILE_OBS',      'File Comment');
define('TEXT_CSV_COL_PRE_EVENT',     'Pre-Event');
define('TEXT_CSV_COL_EVENT',         'Notable Event');
define('TEXT_CSV_COL_FUTURE',        'Planned');
define('TEXT_CSV_COL_COORD_X',       'Coord X');
define('TEXT_CSV_COL_COORD_Y',       'Coord Y');
define('TEXT_CSV_COL_QUALITY',       'Quality Code');

// -----------------------------------------------
// CSV column headers - rain gauge field report
define('TEXT_CSV_COL_PLU_RELEVE_DATE',       'Reading Date');
define('TEXT_CSV_COL_PLU_RELEVE_HEURE',      'Reading Time');
define('TEXT_CSV_COL_PLU_APP_K7',            'Device Cassette N°');
define('TEXT_CSV_COL_PLU_APP_TYPE',          'Device Type');
define('TEXT_CSV_COL_PLU_APP_NUM',           'Device Number');
define('TEXT_CSV_COL_PLU_APP_HEURE',         'Device Time');
define('TEXT_CSV_COL_PLU_TOT_TYPE',          'Storage Gauge');
define('TEXT_CSV_COL_PLU_TOT_ARRIVE',        'Arrival totals (mm)');
define('TEXT_CSV_COL_PLU_TOT_DEPART',        'Departure totals (mm)');
define('TEXT_CSV_COL_PLU_TOT_HEURE',         'Tipping Time');
define('TEXT_CSV_COL_PLU_DUR_JJ',            'Recording Duration DD');
define('TEXT_CSV_COL_PLU_DUR_HH',            'Recording Duration HH');
define('TEXT_CSV_COL_PLU_DUR_MM',            'Recording Duration MM');
define('TEXT_CSV_COL_PLU_LAST_JJ',           'Last Recording DD');
define('TEXT_CSV_COL_PLU_LAST_HH',           'Last Recording HH');
define('TEXT_CSV_COL_PLU_LAST_MM',           'Last Recording MM');
define('TEXT_CSV_COL_PLU_DUR_SS', 'SS');
define('TEXT_CSV_COL_PLU_LAST_SS', 'SS');
define('TEXT_CSV_COL_PLU_NB_BASC',           'Tip Count');
define('TEXT_CSV_COL_PLU_NB_OCTET',          'Byte Count');
define('TEXT_CSV_COL_PLU_BAT_NUM',           'Battery Number');
define('TEXT_CSV_COL_PLU_BAT_TENSION',       'Battery Voltage');
define('TEXT_CSV_COL_PLU_K7_NUM',            'Cassette Number');
define('TEXT_CSV_COL_PLU_K7_INIT',           'Cassette Init Time');
define('TEXT_CSV_COL_PLU_K7_FIRST_BASC',     'First Tip Time');
define('TEXT_CSV_COL_PLU_CUMUL_TOT',         'Storage Gauge totals');
define('TEXT_CSV_COL_PLU_CUMUL_PLU',         'Rain Gauge totals');
define('TEXT_CSV_COL_PLU_DIFF',              'Difference: TOT - Rain (mm)');
define('TEXT_CSV_COL_PLU_CALAGE_HEURE',      'Time Calibration (hh:mm)');
define('TEXT_CSV_COL_PLU_TEST_AUGET',        'Bucket Test');
define('TEXT_CSV_COL_PLU_BOUCHAGE',          'Clogging Action');
define('TEXT_CSV_COL_PLU_DEBROUSSAILLAGE',   'Clearing Action');
define('TEXT_CSV_COL_PLU_EAU_BAT',          'Battery Water Action');
define('TEXT_CSV_COL_PLU_HUILE_TOT',         'Storage Gauge Oil Action');
define('TEXT_CSV_COL_PLU_TRANSFERT',         'Transfer Action');
define('TEXT_CSV_COL_PLU_MEM_EFFACEE',       'Memory Erased Action');
define('TEXT_CSV_COL_PLU_COMMENTAIRE',       'Comment');
define('TEXT_CSV_COL_PLU_NOM_OE2',           'OE2 Name');

// -----------------------------------------------
// CSV column headers - hydrometric field report
define('TEXT_CSV_COL_HYD_COTE_HEURE',        'Water Level Time');
define('TEXT_CSV_COL_HYD_COTE_SONDE',        'Stage - sensor reading');
define('TEXT_CSV_COL_HYD_COTE_ECHL',         'Stage - staff gauge reading');
define('TEXT_CSV_COL_HYD_COTE_ECHL2',        'Stage - secondary staff gauge');
define('TEXT_CSV_COL_HYD_NUM_SONDE',         'Probe Number');
define('TEXT_CSV_COL_HYD_NB_OCTET',          'Byte Count % Mem');
define('TEXT_CSV_COL_HYD_BAT_NUM',           'Battery Number');
define('TEXT_CSV_COL_HYD_BAT_TENSION',       'Battery Voltage');
define('TEXT_CSV_COL_HYD_K7_NUM',            'New Cassette N°');
define('TEXT_CSV_COL_HYD_K7_INIT',           'New Cassette Init Time');
define('TEXT_CSV_COL_HYD_K7_SONDE',          'New Cassette H. Probe');
define('TEXT_CSV_COL_HYD_CTRL_HECH_HSPI',    'Cross-check: staff gauge vs sensor (Hech−Hspi)');
define('TEXT_CSV_COL_HYD_CTRL_RECAL_SONDE',  'Sensor recalibration action');
define('TEXT_CSV_COL_HYD_CTRL_RECAL_DATA',   'Data offset correction applied');
define('TEXT_CSV_COL_HYD_PURGE',             'Orifice / sensor purge action');
define('TEXT_CSV_COL_HYD_JAUGEAGE',          'Gauging Action');
define('TEXT_CSV_COL_HYD_DEBROUSSAILLAGE',   'Clearing Action');
define('TEXT_CSV_COL_HYD_EAU_BAT',           'Battery Water Action');
define('TEXT_CSV_COL_HYD_TRANSFERT',         'Transfer Action');
define('TEXT_CSV_COL_HYD_MEM_EFFACEE',       'Memory Erased Action');

// -----------------------------------------------
// CSV column headers - piezometric field report
define('TEXT_CSV_COL_PIE_SONDE_FIXE_TYPE',   'Fixed Probe - Type');
define('TEXT_CSV_COL_PIE_SONDE_FIXE_NUM',    'Fixed Probe - Number');
define('TEXT_CSV_COL_PIE_SONDE_FIXE_HEURE',  'Fixed Probe - Time');
define('TEXT_CSV_COL_PIE_SONDE_MAN_TYPE',    'Manual Probe - Type');
define('TEXT_CSV_COL_PIE_SONDE_MAN_NUM',     'Manual Probe - Number');
define('TEXT_CSV_COL_PIE_MESURE_TOIT_M',     'Sensor reading - depth to water table (m)');
define('TEXT_CSV_COL_PIE_MESURE_COND',       'Probe Conductivity');
define('TEXT_CSV_COL_PIE_MESURE_TEMP',       'Probe Temperature');
define('TEXT_CSV_COL_PIE_MAN_TOIT_M',        'Manual reading - depth to water table (m)');
define('TEXT_CSV_COL_PIE_MAN_TOIT_CM',       'Manual reading - depth to water table (cm)');
define('TEXT_CSV_COL_PIE_PROF_OUV',          'Total borehole depth (m)');
define('TEXT_CSV_COL_PIE_CTRL_DIFF',         'QC check - offset (manual − sensor)');
define('TEXT_CSV_COL_PIE_CTRL_RECAL_SONDE',  'QC check - sensor recalibration');
define('TEXT_CSV_COL_PIE_CTRL_RECAL_HEURE',  'QC check - clock recalibration');
define('TEXT_CSV_COL_PIE_MEM_NB',            'Memory Record Count');
define('TEXT_CSV_COL_PIE_MEM_EFFACEE',       'Memory Erased');
define('TEXT_CSV_COL_PIE_BAT',               'Battery % Mem');
define('TEXT_CSV_COL_PIE_NATURE_REPERE',     'Reference benchmark type');
define('TEXT_CSV_COL_PIE_Z_REPERE',          'Z (mNGNC)');
define('TEXT_CSV_COL_PIE_POMPAGE_ENCOURS',   'Active Pumping');
define('TEXT_CSV_COL_PIE_POMPAGE_PROCHE',    'Nearby Pumping');
define('TEXT_CSV_COL_PIE_PLUIE_CRUE',        'Rain / Flood');
define('TEXT_CSV_COL_PIE_TEMPS_SEC',         'Dry Period');

// -----------------------------------------------
// CSV column headers - streamflow gaugings
define('TEXT_CSV_COL_JGE_START_HEURE',       'Start Time');
define('TEXT_CSV_COL_JGE_START_H_ECHL',      'Start stage - staff gauge (cm)');
define('TEXT_CSV_COL_JGE_END_HEURE',         'End Time');
define('TEXT_CSV_COL_JGE_END_H_ECHL',        'End stage - staff gauge (cm)');
define('TEXT_CSV_COL_JGE_HMOY',              'Mean stage H (cm)');
define('TEXT_CSV_COL_JGE_Q',                 'Discharge Q (m³/s)');
define('TEXT_CSV_COL_JGE_SECT',              'Wetted cross-sectional area (m²)');
define('TEXT_CSV_COL_JGE_VMOY',              'Mean flow velocity (m/s)');
define('TEXT_CSV_COL_JGE_VSURF',             'Surface velocity (m/s)');
define('TEXT_CSV_COL_JGE_RH',               'Hydraulic radius (m)');
define('TEXT_CSV_COL_JGE_PROFMOY',           'Mean water depth (m)');
define('TEXT_CSV_COL_JGE_NBVERT',            'Number of verticals');
define('TEXT_CSV_COL_JGE_MOULINET',          'Current meter (Price / OTT)');
define('TEXT_CSV_COL_JGE_HELICE',            'Propeller reference');
define('TEXT_CSV_COL_JGE_COORD_GPS_X',       'GPS Coord X');
define('TEXT_CSV_COL_JGE_COORD_GPS_Y',       'GPS Coord Y');
define('TEXT_CSV_COL_JGE_COORD_SIG_X',       'GIS Coord X');
define('TEXT_CSV_COL_JGE_COORD_SIG_Y',       'GIS Coord Y');

// -----------------------------------------------
// CSV column headers - piezometric benchmarks
define('TEXT_CSV_COL_REP_NATURE',            'Benchmark type');
define('TEXT_CSV_COL_REP_CODE',              'Benchmark Code');
define('TEXT_CSV_COL_REP_Z',                 'Z Benchmark');
define('TEXT_CSV_COL_REP_PRECISION',         'Benchmark Precision');
define('TEXT_CSV_COL_REP_DATE_START',        'Validity Start');
define('TEXT_CSV_COL_REP_DATE_END',          'Validity End');
define('TEXT_CSV_COL_REP_NATURE_GEO1',       'Survey benchmark 1 - type');
define('TEXT_CSV_COL_REP_Z_GEO1',            'Surveyor Z 1');
define('TEXT_CSV_COL_REP_NATURE_GEO2',       'Survey benchmark 2 - type');
define('TEXT_CSV_COL_REP_Z_GEO2',            'Surveyor Z 2');

// -----------------------------------------------
// CSV column headers - borehole characteristics
define('TEXT_CSV_COL_CTE_PROF',              'Depth');
define('TEXT_CSV_COL_CTE_MAT_TETE',          'Head Material');
define('TEXT_CSV_COL_CTE_DIM_EXT',           'External Dimension');
define('TEXT_CSV_COL_CTE_MAT_TUB',           'Inner Casing Material');
define('TEXT_CSV_COL_CTE_DIM_TUB',           'Inner Casing Dimension (mm)');
define('TEXT_CSV_COL_CTE_MAT_DALLE',         'Slab Material');
define('TEXT_CSV_COL_CTE_DIM_DALLE',         'Slab Dimension');
define('TEXT_CSV_COL_CTE_CAPOT',             'Cover Present');
define('TEXT_CSV_COL_CTE_DIST_CAPOT_TUBE',   'Cover/Pipe Distance');
define('TEXT_CSV_COL_CTE_DIST_TUBE_DALLE',   'Pipe/Slab Distance');
define('TEXT_CSV_COL_CTE_DIST_DALLE_SOL',    'Slab/Ground Distance');
define('TEXT_CSV_COL_CTE_ETAT',              'Condition');
define('TEXT_CSV_COL_CTE_ACTIVITE',          'Active');
define('TEXT_CSV_COL_CTE_USAGE',             'Usage');
define('TEXT_CSV_COL_CTE_EQUIPEMENT',        'Equipment');
define('TEXT_CSV_COL_CTE_SCHEMA',            'Diagram');
define('TEXT_CSV_COL_CTE_PROTECTION',        'Protection');

// -----------------------------------------------
// CSV column headers - conductivity diagraphy
define('TEXT_CSV_COL_DIAC_PROFONDEUR',       'Depth');
define('TEXT_CSV_COL_DIAC_CONDUCTIVITE',     'Conductivity');
define('TEXT_CSV_COL_DIAC_TEMPERATURE',      'Temperature');


// =============================================================================
// IMPORT MODULE
// =============================================================================

// -----------------------------------------------
// Import page - UI labels
define('TEXT_IMPORT_PAGE_TITLE',         'Data import - Step 1: File selection');
define('TEXT_IMPORT_PROCESS_LABEL',      'Processing workflow');
define('TEXT_IMPORT_INSTRUCTIONS_LINK',  'Import instructions');
define('TEXT_IMPORT_BTN_UPLOAD',         'Load files');
define('TEXT_IMPORT_BTN_IMPORT',         'Import data');

// -----------------------------------------------
// Import page - table headers
define('TEXT_IMPORT_TH_FILE',            'File');
define('TEXT_IMPORT_TH_STATION',         'Station');
define('TEXT_IMPORT_TH_CHRON',           'Time series');
define('TEXT_IMPORT_TH_UNIT',            'Unit');
define('TEXT_IMPORT_TH_SELECT',          'Select +/-');

// -----------------------------------------------
// Import - JS progress messages (PHP-injected)
define('TEXT_IMPORT_JS_FILE_LIST',       'List of selected files');
define('TEXT_IMPORT_JS_UPLOAD_START',    '-- FILE UPLOAD IN PROGRESS --');
define('TEXT_IMPORT_JS_UPLOAD_DONE',     '-- FILE UPLOAD COMPLETE --');
define('TEXT_IMPORT_JS_NO_FILE',         'No file selected');
define('TEXT_IMPORT_JS_DATA_START',      '-- START - DATA RECORDING --');
define('TEXT_IMPORT_JS_DATA_DONE',       '-- END - DATA RECORDING --');
define('TEXT_IMPORT_JS_PARSE_ERROR',     'Error parsing server response: ');
define('TEXT_IMPORT_JS_UPLOAD_ERROR',    'Upload error: ');
define('TEXT_IMPORT_JS_WAIT_UPLOAD',     'Uploading files - Please wait...');
define('TEXT_IMPORT_JS_WAIT_IMPORT',     'Saving data - Please wait...');

// -----------------------------------------------
// File loader - status messages
define('TEXT_LOAD_FILE_LABEL',          'File');
define('TEXT_LOAD_FILE_CONFORM',        ' - Valid.');
define('TEXT_LOAD_FILE_STATION_LABEL',  'Station');
define('TEXT_LOAD_FILE_CHRON_LABEL',    'Series');

// -----------------------------------------------
// File loader - error messages
define('TEXT_LOAD_ERR_NO_CHRON',        'No registered data series could be identified in the file name.');
define('TEXT_LOAD_ERR_NO_STATION',      'No station could be identified in the file name.');
define('TEXT_LOAD_ERR_BAD_EXT',         'Invalid file type. Extension not registered: ');
define('TEXT_LOAD_ERR_MOVE',            'Error moving the uploaded file.');
define('TEXT_LOAD_ERR_UPLOAD',          'Upload error: ');
define('TEXT_LOAD_ERR_NO_FILE',         'No file was received.');

// -----------------------------------------------
// File loader - table row tooltips
define('TEXT_LOAD_TIP_DATA_OK',         'Data loaded');
define('TEXT_LOAD_TIP_DATA_FAIL',       'No data could be loaded');
define('TEXT_LOAD_TIP_DATA_WAIT',       'Data processing in progress');
define('TEXT_LOAD_TIP_DETAIL',          'Import details');
define('TEXT_LOAD_TIP_GRAPH',           'View imported data');

// -----------------------------------------------
// File loader - series type labels (static fallbacks)
define('TEXT_CHRON_TYPE_RA',            'Activity Report');
define('TEXT_CHRON_TYPE_JGE',           'Stream Gauging');
define('TEXT_CHRON_TYPE_ETL',           'Rating Curve Q=f(H)');
define('TEXT_CHRON_TYPE_REP',           'Piezometric reference benchmark');


// -----------------------------------------------
// Import processing - progress log
define('TEXT_IMPORT_CHRON_FILE',           'File');
define('TEXT_IMPORT_CHRON_STATION',        'Station');
define('TEXT_IMPORT_CHRON_SERIES',         'Series');
define('TEXT_IMPORT_CHRON_DONE',           'File processing complete.');
define('TEXT_IMPORT_CHRON_DURATION',       'Processing time');
define('TEXT_IMPORT_CHRON_SEC',            'sec.');
define('TEXT_IMPORT_CHRON_NB_IMPORTED',    'Records imported');
define('TEXT_IMPORT_CHRON_NB_ERRORS',      'Errors');
define('TEXT_IMPORT_CHRON_NB_DELETED',     'Records deleted');
define('TEXT_IMPORT_CHRON_DATE_START',     'Series start');
define('TEXT_IMPORT_CHRON_DATE_END',       'Series end');
define('TEXT_IMPORT_CHRON_FAIL',           'Data could not be imported.');
define('TEXT_IMPORT_CHRON_DB_ERROR',       'Database insert error: ');

// -----------------------------------------------
// Import processing - warning/error detail lines (.txt log)
define('TEXT_IMPORT_WARN_FILE', 'File format errors (structure invalid): ');
define('TEXT_IMPORT_WARN_DATE',     'Date format errors (column 1) - unrecognised or empty: ');
define('TEXT_IMPORT_WARN_VALEUR',   'Value format errors (column 2) - invalid or empty: ');
define('TEXT_IMPORT_WARN_QUALITE',  'Quality code warnings (column 3) - not registered or empty: ');
define('TEXT_IMPORT_WARN_LINE',     'line(s) affected.');
define('TEXT_IMPORT_WARN_LINE_Q',   'line(s) concerned.');

// -----------------------------------------------
// Import processing - action log
define('TEXT_ACTION_IMPORT',        'Data import - File');
define('TEXT_ACTION_IMPORT_STATION','Station');

// -----------------------------------------------
// RA import - progress log
define('TEXT_IMPORT_RA_NB_IMPORTED',  'Field Reports imported');
define('TEXT_IMPORT_RA_SERIES_LABEL', 'FR');

// -----------------------------------------------
// RA import - action log
define('TEXT_ACTION_IMPORT_RA',       'Field Report data import - File');

// -----------------------------------------------
// TOT import - progress log
define('TEXT_IMPORT_TOT_SERIES_LABEL',  'TOT');
define('TEXT_IMPORT_TOT_INFO_LABEL',    'Information(s)');
define('TEXT_IMPORT_TOT_DATA_UPDATED',  'Data successfully updated.');
define('TEXT_IMPORT_TOT_DB_ERROR',      'Transaction execution error: ');

// -----------------------------------------------
// TOT import - validation errors
define('TEXT_IMPORT_TOT_ERR_DATE',      "Date '%s' is not in a valid format dd/mm/yyyy hh:mm:ss.");
define('TEXT_IMPORT_TOT_ERR_NO_DATE',   'At least one date is missing.');
define('TEXT_IMPORT_TOT_ERR_COL2',      'At least one value in column 2 is not numeric.');
define('TEXT_IMPORT_TOT_ERR_COL3',      'At least one value in column 3 is not numeric.');
define('TEXT_IMPORT_TOT_ERR_COL4',      'At least one value in column 4 is not numeric.');

// -----------------------------------------------
// TOT import - action log
define('TEXT_ACTION_IMPORT_TOT',        'TOT data import - File');


// -----------------------------------------------
// Streamflow gauging import - progress log
define('TEXT_IMPORT_JGE_SERIES_LABEL',  'GAUGING');
define('TEXT_IMPORT_JGE_NB_IMPORTED',   'Stream measurements imported');
define('TEXT_IMPORT_JGE_ERR_DATE',      'Line %d: Invalid date.');
define('TEXT_ACTION_IMPORT_JGE',        'GAUGING data import - File');

// -----------------------------------------------
// Rating curve (ETL) import - progress log - Rating Curve
define('TEXT_IMPORT_ETL_SERIES_LABEL',   'RC');
define('TEXT_IMPORT_ETL_DATA_UPDATED',   'Data successfully updated.');
define('TEXT_IMPORT_ETL_DB_ERROR',       'Transaction execution error: ');

// -----------------------------------------------
// Rating curve (ETL) import - validation errors
define('TEXT_IMPORT_ETL_ERR_ODD_COLS',   'The CSV file must have an even number of columns (H/Q pairs).');
define('TEXT_IMPORT_ETL_ERR_HQ_MISMATCH','Stage/discharge (H/Q) pair count mismatch for period %s – %s.');
define('TEXT_IMPORT_ETL_ERR_DATE',       "Date '%s' is not in a valid format dd/mm/yyyy hh:mm:ss.");
define('TEXT_IMPORT_ETL_ERR_HAUTEUR',    'Some height values are not numeric: index %d - value %s.');
define('TEXT_IMPORT_ETL_ERR_DEBIT',      'Some flow values are not numeric.');

// -----------------------------------------------
// Rating curve (ETL) import - action log
define('TEXT_ACTION_IMPORT_ETL',         'RC data import - File');

// -----------------------------------------------
// Piezometric benchmark import - progress log
define('TEXT_IMPORT_REP_SERIES_LABEL',  'REP');
define('TEXT_IMPORT_REP_NB_IMPORTED',   'Piezometric benchmarks imported');
define('TEXT_IMPORT_REP_ERR_DATE',      'Line %d: Invalid date.');

// -----------------------------------------------
// Piezometric benchmark import - action log
define('TEXT_ACTION_IMPORT_REP',        'Piezometric benchmark data import - file');



// -----------------------------------------------
// Station list - page title & header
define('TEXT_STATION_LIST_TITLE',           'Stations List');
define('TEXT_STATION_LIST_NEW',             'New Station');
define('TEXT_STATION_LIST_DOWNLOAD_TITLE',  'Download information for selected stations');
define('TEXT_STATION_LIST_CREATING_FILE',   'Creating file...');

// -----------------------------------------------
// Station list - sort controls
define('TEXT_STATION_SORT_BY',              'Sort by');
define('TEXT_STATION_SORT_NAME',            'Name');
define('TEXT_STATION_SORT_CODE',            'Code');
define('TEXT_STATION_SORT_TYPE',            'Data');
define('TEXT_STATION_SORT_ASC',             'Ascending');
define('TEXT_STATION_SORT_DESC',            'Descending');
define('TEXT_STATION_STATUS_CHANGE',        'Change status');

// -----------------------------------------------
// Station list - summary counters
define('TEXT_STATION_NB_TOTAL',             'Stations');
define('TEXT_STATION_NB_ACTIVE',            'Active stations');
define('TEXT_STATION_NB_SUIVI',             'Continuous data');
define('TEXT_STATION_NB_ARMEE',             'Station error');

// -----------------------------------------------
// Station list - table column headers
define('TEXT_STATION_COL_STATUS',           'Status');
define('TEXT_STATION_COL_STATUS_TITLE',     'Active / Historical (Closed)');
define('TEXT_STATION_COL_SUIVI',            'Mode');
define('TEXT_STATION_COL_SUIVI_TITLE',      'Continuous / Discrete');
define('TEXT_STATION_COL_ETAT',             'Device');
define('TEXT_STATION_COL_ETAT_TITLE',       'In service / Out of service');
define('TEXT_STATION_COL_TYPE',             'Data');
define('TEXT_STATION_COL_CODE',             'Code');
define('TEXT_STATION_COL_NOM',              'Name');
define('TEXT_STATION_COL_COMMUNE',          'Town / Village');
define('TEXT_STATION_COL_REGIONHYDRO',      'Watershed');
define('TEXT_STATION_COL_REGIONHYDRO_TITLE','Hydrological region or watershed ');
define('TEXT_STATION_COL_INSTALLATION',     'Install');
define('TEXT_STATION_COL_INSTALLATION_TITLE','Installation date');
define('TEXT_STATION_COL_VISITE',           'Last visit');
define('TEXT_STATION_COL_VISITE_TITLE',     'Date of last visit');
define('TEXT_STATION_COL_NB_RA',            'Nb FR');
define('TEXT_STATION_COL_NB_RA_TITLE',      'Nb of field reports');
define('TEXT_STATION_COL_EXPORT',           'Export');
define('TEXT_STATION_COL_EXPORT_TITLE',     'Select stations for XLS export');
define('TEXT_STATION_COL_DELETE_TITLE',     'Delete');

// --- Excel export column headers (station download) ---
define('TEXT_STATION_COL_SHEET_IDENT', 'Identification');

define('TEXT_STATION_COL_SITE', 'Site');
define('TEXT_STATION_COL_REGION', 'Region');
define('TEXT_STATION_COL_NAPPE', 'Aquifer');
define('TEXT_STATION_COL_X_RGNC', 'X_RGNC');
define('TEXT_STATION_COL_Y_RGNC', 'Y_RGNC');
define('TEXT_STATION_COL_X_WGS', 'X_WGS');
define('TEXT_STATION_COL_Y_WGS', 'Y_WGS');
define('TEXT_STATION_COL_DESCRIPTION', 'Description');
define('TEXT_STATION_COL_ZSOL', 'Ground level (Z)');
define('TEXT_STATION_COL_PRECISION', 'Accuracy');
define('TEXT_STATION_COL_AQUIFERE', 'Tapped aquifer');
define('TEXT_STATION_COL_NATURE', 'Nature');
define('TEXT_STATION_COL_MAITRE_OUVRAGE', 'Project owner');
define('TEXT_STATION_COL_DATE_REALISATION', 'Construction date');
define('TEXT_STATION_COL_SONDE', 'Probe');
define('TEXT_STATION_COL_REP_NATURE', 'Benchmark type');
define('TEXT_STATION_COL_REP_Z', 'Benchmark Z');
define('TEXT_STATION_COL_REP_PRECISION', 'Accuracy');
define('TEXT_STATION_COL_REP_CODE', 'Benchmark code');
define('TEXT_STATION_COL_REP_DATE_DEBUT', 'Start date');
define('TEXT_STATION_COL_REP_DATE_FIN', 'End date');
define('TEXT_STATION_COL_REP_NATURE_G1', 'Surveyor benchmark type 1');
define('TEXT_STATION_COL_REP_Z_G1', 'Surveyor benchmark Z 1');
define('TEXT_STATION_COL_REP_NATURE_G2', 'Surveyor benchmark type 2');
define('TEXT_STATION_COL_REP_Z_G2', 'Surveyor benchmark Z 2');
define('TEXT_STATION_COL_REP_OBS', 'Observation');
define('TEXT_STATION_COL_CAR_DATE_OBS', 'Observation date');
define('TEXT_STATION_COL_CAR_PROF', 'Depth');
define('TEXT_STATION_COL_CAR_MAT_TETE', 'Head material');
define('TEXT_STATION_COL_CAR_DIM_TETE', 'Outer head dim.');
define('TEXT_STATION_COL_CAR_MAT_TUB', 'Inner casing material');
define('TEXT_STATION_COL_CAR_DIAM_TUB', 'Inner casing diam.');
define('TEXT_STATION_COL_CAR_MAT_DALLE', 'Slab material');
define('TEXT_STATION_COL_CAR_DIM_DALLE', 'Slab dim.');
define('TEXT_STATION_COL_CAR_DIST_CAPOT', 'Cap-to-casing dist.');
define('TEXT_STATION_COL_CAR_DIST_TUB_DALLE', 'Casing-to-slab dist.');
define('TEXT_STATION_COL_CAR_DIST_DALLE_SOL', 'Slab-to-ground dist.');
define('TEXT_STATION_COL_CAR_PRESENCE_CAPOT', 'Cap present');
define('TEXT_STATION_COL_CAR_ETAT', 'State');
define('TEXT_STATION_COL_CAR_ACTIVITE', 'Active');
define('TEXT_STATION_COL_CAR_USAGE', 'Usage');
define('TEXT_STATION_COL_CAR_EQUIP', 'Operating equipment');
define('TEXT_STATION_COL_CAR_SCHEMA_TETE', 'Head diagram');
define('TEXT_STATION_COL_CAR_OBS', 'Remark');

// -----------------------------------------------
// list_stations.php — deletion confirmation popup
define('TEXT_STATION_DEL_CONFIRM_TITLE',   'Confirm deletion');
define('TEXT_STATION_DEL_CONFIRM_MSG',     'You are about to delete the following station. This action cannot be undone.');
define('TEXT_STATION_DEL_STATION_LABEL',   'Station');
define('TEXT_STATION_DEL_CHALLENGE_LABEL', 'To confirm, solve this:');
define('TEXT_STATION_DEL_BTN_CANCEL',      'Cancel');
define('TEXT_STATION_DEL_BTN_CONFIRM',     'Delete');

// -----------------------------------------------
// process_station_delete.php — deletion messages
define('TEXT_STATION_DELETE_SUCCESS',      'The station has been successfully deleted.');
define('TEXT_STATION_INFO',                'Station');
define('TEXT_STATION_DELETE_HAS_RECORDS',  'This station cannot be deleted because it contains records.');
define('TEXT_STATION_DELETE_NOT_FOUND',    'This station does not exist and cannot be deleted.');

// -----------------------------------------------
// Station list - empty result
define('TEXT_STATION_NONE_FOUND',           'No station was found.');

// -----------------------------------------------
// Station list - JS error messages (PHP-injected)
define('TEXT_STATION_JS_NO_SELECTION',      'No station selected - the file cannot be created.');
define('TEXT_STATION_JS_ERR_GENERATE',      'Error generating the file.');
define('TEXT_STATION_JS_ERR_SERVER',        'Server request error.');

// -----------------------------------------------
// Station edit - page title
define('TEXT_STATION_EDIT_TITLE_NEW',       'New Station');
define('TEXT_STATION_EDIT_TITLE_TYPE',      ' - Station: ');
define('TEXT_STATION_EDIT_SAVE',            'Save');

// -----------------------------------------------
// AVAILABLE DATA TABLE

define('TEXT_BLOCK_INFO_CHRON_TITLE',    'Time-Series description');
define('TEXT_BLOCK_HISTORY_CHRON_TITLE', 'Time-Series History');
 
define('TEXT_LOADDATA_COL_CHRON',     'Time-series');
define('TEXT_LOADDATA_COL_NBDATA',    '#data');
define('TEXT_LOADDATA_COL_DATESTART', 'Start date');
define('TEXT_LOADDATA_COL_DATEEND',   'End date');
 
define('TEXT_LOADDATA_CODEQUAL_TITLE', 'Quality Codes');
 
define('TEXT_LOADDATA_YAXIS_LABEL',   'Time-series type');

// process_loaddata.php — graph display strings
define('TEXT_LOADDATA_HOVER_DATE',     'Date');
define('TEXT_LOADDATA_HOVER_CODEQUAL', 'Quality code');
define('TEXT_LOADDATA_LABEL_START',    'Start');
define('TEXT_LOADDATA_LABEL_END',      'End');

// -----------------------------------------------
// CHRONOLOGY HISTORY (process_history_chron.php)

define('TEXT_HISTORY_IMPORT',       'Import');
define('TEXT_HISTORY_COL_DATE',     'Date');
define('TEXT_HISTORY_COL_DATA',     'Data');
define('TEXT_HISTORY_COL_OPERATION','Operation');
define('TEXT_HISTORY_COL_USER',     'User');
define('TEXT_HISTORY_COL_OBS',      'Comment');
define('TEXT_HISTORY_COL_START',    'Period start');
define('TEXT_HISTORY_COL_END',      'Period end');


// -----------------------------------------------
// CHRONOLOGY INFORMATION (process_info_chron.php)

define('TEXT_CHRON_RAW_DATA', 'Raw data');
define('TEXT_CHRON_COL_ACRONYM',  'Acronym');
define('TEXT_CHRON_COL_LABEL',    'Title');
define('TEXT_CHRON_COL_UNIT',     'Unit');
define('TEXT_CHRON_COL_DATATYPE', 'Data type');

define('TEXT_CHRON_JGE_LABEL',  'Stream Gauging');
define('TEXT_CHRON_ETL_LABEL',  'Rating Curves');
define('TEXT_CHRON_DIAG_LABEL', 'Well logs');
define('TEXT_CHRON_RA_LABEL',   'Field Report');

// -----------------------------------------------
// Station edit - tabs
define('TEXT_STATION_TAB_MONITORING',       'Metadata');
define('TEXT_STATION_TAB_FORM',             'Main info');
define('TEXT_STATION_TAB_BENCHMARK',        'Reference');
define('TEXT_STATION_TAB_CHARACTERISTICS',  'Characteristics');
define('TEXT_STATION_TAB_ACCESS',           'Access');
define('TEXT_STATION_TAB_PHOTOS',           'Photos');

// -----------------------------------------------
// Station edit - error state
define('TEXT_STATION_EDIT_ERROR_TITLE',     'Station file: Measurement station');
define('TEXT_STATION_EDIT_NOT_FOUND',       'No station was found.');
define('TEXT_STATION_EDIT_BACK_TO_LIST',    '>>  Return to station list');

// -----------------------------------------------
// Station form - section headers
define('TEXT_FORM1_MEASURE_TYPE',       'Measurement type');
define('TEXT_FORM1_STATUS',             'Status');
define('TEXT_FORM1_MONITORING',         'Monitoring');
define('TEXT_FORM1_EQUIPMENT_FAULT',    'Out of service');
define('TEXT_FORM1_CODE',               'Code / Station No.');
define('TEXT_FORM1_NAME',               'Station name');
define('TEXT_FORM1_IRH',                'IRH / Station Registration');
define('TEXT_FORM1_SHORT_NAME',         'Abbreviation');

// -----------------------------------------------
// Station form - status & monitoring dropdown options
define('TEXT_FORM1_STATUS_ACTIVE',      'Active');
define('TEXT_FORM1_STATUS_HISTORICAL',  'Historical (Closed)');
define('TEXT_FORM1_MONITORING_CONT',    'Continuous');
define('TEXT_FORM1_MONITORING_SPOT',    'Discrete');

// -----------------------------------------------
// Station form - geographic section
define('TEXT_FORM1_MUNICIPALITY',       'Town / Village');
define('TEXT_FORM1_SITE',               'Site');
define('TEXT_FORM1_WATERSHED',          'Watershed / catchment');
define('TEXT_FORM1_ORIENTATION',        'Catchment drainage orientation');
define('TEXT_FORM1_ALTITUDE',           'Elevation (m)');
define('TEXT_FORM1_RIVER',              'River');
define('TEXT_FORM1_AQUIFER',            'Aquifer');

// -----------------------------------------------
// Station form - coordinates
define('TEXT_FORM1_LONGITUDE',          'Longitude');
define('TEXT_FORM1_LATITUDE',           'Latitude');
define('TEXT_FORM1_UTM_X',              'UTM (WGS 84) - X');
define('TEXT_FORM1_UTM_Y',              'UTM (WGS 84) - Y');
define('TEXT_FORM1_IGN_X',              'IGN - X');
define('TEXT_FORM1_IGN_Y',              'IGN - Y');
define('TEXT_FORM1_LAMB_X',             'Lambert (RGNC 91) - X');
define('TEXT_FORM1_LAMB_Y',             'Lambert (RGNC 91) - Y');

// -----------------------------------------------
// Station form - description
define('TEXT_FORM1_MANAGER',            'Station manager');
define('TEXT_FORM1_DATE_INSTALL',       'Installation date');
define('TEXT_FORM1_DATE_REMOVAL',       'Uninstallation date');
define('TEXT_FORM1_DATE_PLACEHOLDER',   'dd-mm-yyyy');
define('TEXT_FORM1_DESCRIPTION',        'Description');

// -----------------------------------------------
// Station form - select2 placeholders
define('TEXT_FORM1_SELECT2_REGION',     'Select region...');
define('TEXT_FORM1_SELECT2_COMMUNE',    'Select city...');
define('TEXT_FORM1_SELECT2_REGIONHYDRO','Select watershed...');
define('TEXT_FORM1_SELECT2_RIVER','Select river...');
define('TEXT_FORM1_SELECT2_AQUIFER',    'Select aquifer...');

// -----------------------------------------------
// Station monitoring tab - section titles
define('TEXT_STATION2_METADATA_TITLE',   'Metadata');
define('TEXT_STATION2_DATA_TITLE',       'Available data');

// -----------------------------------------------
// Station monitoring tab - links panel
define('TEXT_STATION2_LINKS',            'Links:');
define('TEXT_STATION2_LINK_DATA',        '>> Data');
define('TEXT_STATION2_LINK_RA',          '>> Field Reports');
define('TEXT_STATION2_LINK_JGE',         '>> Gauging list');
define('TEXT_STATION2_LINK_ETL',         '>> Rating Curves (H→Q)');

// -----------------------------------------------
// Station monitoring tab - series info links
define('TEXT_STATION2_SERIES_DETAILS',   'Series details');
define('TEXT_STATION2_MODIF_HISTORY',    'History');

// -----------------------------------------------
// Station monitoring tab - JS empty state
define('TEXT_STATION2_JS_NO_DATA',       'No data recorded for this station.');


// -----------------------------------------------
// Station access tab - page structure
define('TEXT_ACCESS_EXPORT_BTN',        'PDF - Export Access');
define('TEXT_ACCESS_FORM_TITLE',        'Access form');
define('TEXT_ACCESS_PLAN_TITLE',        'Access map');

// -----------------------------------------------
// Station access tab - contact fields
define('TEXT_ACCESS_OWNER',             'Site owner');
define('TEXT_ACCESS_CONTACT_NAME',      'Contact name');
define('TEXT_ACCESS_CONTACT_PHONE',     'Phone');
define('TEXT_ACCESS_CONTACT_EMAIL',     'Email');
define('TEXT_ACCESS_CONTACT_ADDRESS',   'Address');
define('TEXT_ACCESS_CONTACT_PO_BOX',    'P.O. Box');
define('TEXT_ACCESS_CONTACT_POSTCODE',  'Postcode');
define('TEXT_ACCESS_CONTACT_COMMUNE',   'Town / Village');

// -----------------------------------------------
// Station access tab - access information
define('TEXT_ACCESS_INFO',              'Access information');
define('TEXT_ACCESS_PEDESTRIAN',        'Pedestrian access');
define('TEXT_ACCESS_TIME',              'Access time');
define('TEXT_ACCESS_DIFFICULTY',        'Access difficulties');
define('TEXT_ACCESS_REMARKS',           'Additional remarks');

// -----------------------------------------------
// Station access tab - map upload
define('TEXT_ACCESS_PLAN_UPLOAD_LABEL', 'Upload map (formats: .jpg .jpeg .png)');
define('TEXT_ACCESS_PLAN_UPLOAD_SIZE',  'File size must not exceed 2 MB.');
define('TEXT_ACCESS_PLAN_SAVE_BTN',     'Load map');
define('TEXT_ACCESS_PLAN_LOADING',      'Loading...');

// -----------------------------------------------
// Station access tab - select2 placeholder
define('TEXT_ACCESS_SELECT2_COMMUNE',   'Select city...');

// -----------------------------------------------
// Access map upload handler
define('TEXT_PHOTO_ACCESS_ERR_FORMAT',  'The photo file is not in a supported format. Accepted formats: .jpg, .jpeg, .png.');
define('TEXT_PHOTO_ACCESS_ERR_SIZE',    'The photo file must not exceed 2 MB.');
define('TEXT_PHOTO_ACCESS_ERR_UPLOAD',  'An error occurred while uploading the file.');
define('TEXT_PHOTO_ACCESS_ERR_NO_FILE', 'No file could be loaded.');
define('TEXT_PHOTO_ACCESS_SUCCESS',     'The photo was saved successfully.');

// -----------------------------------------------
// Access map display handler
define('TEXT_PLAN_DELETE_LINK',   'Delete map');
define('TEXT_PLAN_VIEW_TITLE',    'View image');
define('TEXT_PLAN_ADD', 'Add a plan');

// form_station_access.php — plan delete confirmation popup
define('TEXT_PLAN_DEL_CONFIRM_TITLE', 'Delete access map');
define('TEXT_PLAN_DEL_CONFIRM_MSG',   'Do you confirm the deletion of the access map? This action cannot be undone.');
define('TEXT_PLAN_DEL_BTN_CANCEL',    'Cancel');
define('TEXT_PLAN_DEL_BTN_CONFIRM',   'Delete');

// -----------------------------------------------
// Access map delete handler
define('TEXT_PLAN_DELETE_SUCCESS', 'The access map was successfully deleted.');
define('TEXT_PLAN_DELETE_FAIL',    'The access map could not be deleted.');

// Station photo gallery tab (form_station_photos)
define('TEXT_PHOTOS_UPLOAD_LABEL',    'Select a new photo (formats: .jpg .jpeg .png)');
define('TEXT_PHOTOS_UPLOAD_SIZE',     'File size must not exceed 2 MB.');
define('TEXT_PHOTOS_DESC',            'Description');
define('TEXT_PHOTOS_DATE',            'Photo date');
define('TEXT_PHOTOS_DATE_PLACEHOLDER','dd-mm-yyyy');
define('TEXT_PHOTOS_SAVE_BTN',        'Load photo');
define('TEXT_PHOTOS_LOADING',         'Loading...');

// Station photo gallery display handler (process_loadphotos)
define('TEXT_PHOTOS_COL_DATE',        'Date');
define('TEXT_PHOTOS_COL_DESC',        'Description');
define('TEXT_PHOTOS_DELETE_TITLE',    'Delete picture');
define('TEXT_PHOTOS_VIEW_TITLE',      'View picture');
define('TEXT_PHOTOS_ADD',            'Add a picture.');

define('TEXT_PHOTO_ERR_DATE', 'Date is not in the expected format: dd-mm-yyyy.');

// form_station_photos.php — delete confirmation popup + missing image
define('TEXT_PHOTOS_DEL_CONFIRM_TITLE', 'Delete photo');
define('TEXT_PHOTOS_DEL_CONFIRM_MSG',   'Do you confirm the deletion of this photo? This action cannot be undone.');
define('TEXT_PHOTOS_DEL_PHOTO_LABEL',   'Photo');
define('TEXT_PHOTOS_DEL_BTN_CANCEL',    'Cancel');
define('TEXT_PHOTOS_DEL_BTN_CONFIRM',   'Delete');
define('TEXT_PHOTOS_MISSING',           'Image not found');

// Station photo delete handler (process_delphoto)
define('TEXT_PHOTO_DELETE_SUCCESS', 'Photo file was successfully deleted.');
define('TEXT_PHOTO_DELETE_FAIL',    'The photo file could not be deleted.');


// Piezo characteristics tab (form_station_caracteristique)
define('TEXT_CARACT_NEW_OBS',           'New comment');
define('TEXT_CARACT_OBS_TITLE',         'Data comment ');
define('TEXT_CARACT_NEW_OBS_TITLE',     '(New) Data comment');

define('TEXT_CARACT_DELETE_TITLE',   'Delete');

define('TEXT_CARACT_DATE_PLACEHOLDER',   'dd-mm-yyyy');

// Borehole condition states
define('TEXT_ETAT_BON',           'Good');
define('TEXT_ETAT_MOYEN',         'Fair');
define('TEXT_ETAT_MAUVAIS',       'Poor');
define('TEXT_ETAT_ABANDONNE',     'Abandoned');
define('TEXT_ETAT_COLMATE',       'Clogged');
define('TEXT_ETAT_REBOUCHE',      'Backfilled');
define('TEXT_ETAT_NON_ACCESSIBLE','Inaccessible');
define('TEXT_ETAT_DISPARU',       'Lost');

// Piezo characteristics tab - well construction fields
define('TEXT_CARACT_DEPTH',             'Depth [m]');
define('TEXT_CARACT_HEAD_MATERIAL',     'Head material');
define('TEXT_CARACT_EXT_DIM',           'External dimension');
define('TEXT_CARACT_CASING_MATERIAL',   'Inside material');
define('TEXT_CARACT_CASING_DIM',        'Diameter [mm]');
define('TEXT_CARACT_SCHEMA',            'Diagram');
define('TEXT_CARACT_PROTECTION',        'Protection');
define('TEXT_CARACT_SLAB_MATERIAL',     'Slab material');
define('TEXT_CARACT_SLAB_DIM',          'Slab dimension');
define('TEXT_CARACT_CAP_PRESENT',       'Cap present');
define('TEXT_CARACT_DIST_CAP_TUBE',     'Dist. Cap/Casing (1)');
define('TEXT_CARACT_DIST_TUBE_SLAB',    'Dist. Casing/Slab (2)');
define('TEXT_CARACT_DIST_SLAB_GROUND',  'Dist. Slab/Ground (3)');

// Piezo characteristics tab - usage section
define('TEXT_CARACT_USAGE_TITLE',       'Usage');
define('TEXT_CARACT_STATE',             'Condition');
define('TEXT_CARACT_ACTIVE',            'In operation');
define('TEXT_CARACT_USAGE',             'Usage');
define('TEXT_CARACT_EQUIPMENT',         'Equipment');
define('TEXT_CARACT_OBSERVATIONS',      'Comment');

// Piezo characteristics delete handler (process_delcaracteristique)
define('TEXT_CARACT_DELETE_SUCCESS', 'Well characteristics record dated %s was successfully deleted.');
define('TEXT_CARACT_DELETE_FAIL',    'The characteristics record could not be deleted.');

// Piezo benchmark tab (form_station_repere) - table headers
define('TEXT_REPERE_COL_VALIDITY',    'Validity period');
define('TEXT_REPERE_DATE_PLACEHOLDER',   'dd-mm-yyyy');
define('TEXT_REPERE_COL_BENCHMARK',   'Reference');
define('TEXT_REPERE_COL_SURVEYOR',    'Survey levelling data');
define('TEXT_REPERE_COL_DATE_START',  'Start date');
define('TEXT_REPERE_COL_DATE_END',    'End date');
define('TEXT_REPERE_COL_NATURE',      'Nature');
define('TEXT_REPERE_COL_CODE',        'Code');
define('TEXT_REPERE_COL_Z',           'Z [m]');
define('TEXT_REPERE_COL_PRECISION',   'Precision');
define('TEXT_REPERE_COL_BENCHMARK1',  'Ref. 1');
define('TEXT_REPERE_COL_BENCHMARK2',  'Ref. 2');
define('TEXT_REPERE_COL_OBS',         'Comment');

// Piezo benchmark tab - actions
define('TEXT_REPERE_ADD_ROW',         'Add a reference');
define('TEXT_REPERE_DELETE_TITLE',    'Delete');

// form_station_repere.php — delete confirmation popup
define('TEXT_REPERE_DEL_CONFIRM_TITLE', 'Delete benchmark');
define('TEXT_REPERE_DEL_CONFIRM_MSG',   'Do you confirm the deletion of this benchmark? This action cannot be undone.');
define('TEXT_REPERE_DEL_BTN_CANCEL',    'Cancel');
define('TEXT_REPERE_DEL_BTN_CONFIRM',   'Delete');

// form_station_caracteristique.php — delete confirmation popup
define('TEXT_CARACT_DEL_CONFIRM_TITLE', 'Delete comment');
define('TEXT_CARACT_DEL_CONFIRM_MSG',   'Do you confirm the deletion of this comment? This action cannot be undone.');
define('TEXT_CARACT_DEL_BTN_CANCEL',    'Cancel');
define('TEXT_CARACT_DEL_BTN_CONFIRM',   'Delete');


// Piezo benchmark delete handler (process_delrepere)
define('TEXT_REPERE_DELETE_SUCCESS', 'Reference record (start date: %s) was successfully deleted.');
define('TEXT_REPERE_DELETE_FAIL',    'The reference record could not be deleted.');



// -----------------------------------------------
// Station save - Success messages
define('TEXT_STATION_SAVE_NEW_SUCCESS',         'The new Station  has been successfully created');
define('TEXT_STATION_SAVE_UPDATE_SUCCESS',      'The Station has been successfully saved');
define('TEXT_STATION_SAVE_LABEL',               'Station:');
 
// -----------------------------------------------
// Station save - Server / auth errors
define('TEXT_ERROR_SERVER_GENERIC',             'Server error. Please contact the administrator.');
define('TEXT_ERROR_USER_NOT_IDENTIFIED',        'User not identified.');
define('TEXT_ERROR_REQUEST_METHOD',             'An error occurred while sending data to the server.');
define('TEXT_ERROR_SAVE_FAILED',                'An error occurred: the Station record could not be saved');
define('TEXT_ERROR_DB_WRITE',                   'An error occurred while writing to the database.');
define('TEXT_ERROR_RETRY_OR_CONTACT',           'Please try again or contact the administrator.');
 
// -----------------------------------------------
// Station save - Field validation
define('TEXT_ERROR_CODE_STATION_REQUIRED',      'Station Code is a required field.');
define('TEXT_ERROR_NAME_STATION_REQUIRED',      'Station Name is a required field.');
define('TEXT_ERROR_CODE_STATION_DUPLICATE_1',   'The code ');
define('TEXT_ERROR_CODE_STATION_DUPLICATE_2',   ' is already assigned, a new station cannot be created with this Station Code.');
 
// -----------------------------------------------
// Station save - Date validation
define('TEXT_ERROR_DATE_INSTALLATION_FORMAT',   'The installation date format is invalid. Please check your entry: dd-mm-yyyy');
define('TEXT_ERROR_DATE_DECOMMISSION_FORMAT',   'The decommissioning date format is invalid. Please check your entry: dd-mm-yyyy');
define('TEXT_ERROR_DATE_DECOMMISSION_ORDER',    'The decommissioning date cannot be earlier than or equal to the installation date.');
 
// -----------------------------------------------
// Station save - Piezometric characteristics (Well)
define('TEXT_ERROR_WELL_DEPTH_NUMERIC',         'The Depth field, in the Well Characteristics, must be a number.');
define('TEXT_ERROR_WELL_CASING_SIZE_NUMERIC',   'The Casing Size field, in the Well Characteristics, must be a number.');
define('TEXT_ERROR_WELL_DIST_CAP_TUBE',         'The Cap/Casing Dist. (1) field, in the Well Characteristics, must be a number.');
define('TEXT_ERROR_WELL_DIST_TUBE_SLAB',        'The Casing/Slab Dist. (2) field, in the Well Characteristics, must be a number.');
define('TEXT_ERROR_WELL_DIST_SLAB_GROUND',      'The Slab/Ground Dist. (3) field, in the Well Characteristics, must be a number.');
define('TEXT_ERROR_WELL_DATE_FORMAT',           'The date format, in the Well Characteristics, is invalid. Please check your entry: dd-mm-yyyy');
 
// -----------------------------------------------
// Station save - Piezometric benchmarks (Reference marks)
define('TEXT_ERROR_REF_DATE_START_FORMAT',      'The start validity date format of a Reference mark is invalid. Please check your entry: dd-mm-yyyy');
define('TEXT_ERROR_REF_DATE_END_FORMAT',        'The end validity date format of a Reference mark is invalid. Please check your entry: dd-mm-yyyy');
define('TEXT_ERROR_REF_DATE_START_REQUIRED', 'The benchmark validity start date is required.');
define('TEXT_ERROR_REF_DATE_ORDER',             'The end validity date of a Reference mark cannot be earlier than or equal to its start validity date.');
define('TEXT_ERROR_REF_Z_NUMERIC',              'The Z - Reference field, in the Well Reference, must be a number.');
define('TEXT_ERROR_REF_Z_SURVEYOR_1',           'The Z - Surveyor Reading 1 field, in the Well Reference, must be a number.');
define('TEXT_ERROR_REF_Z_SURVEYOR_2',           'The Z - Surveyor Reading 2 field, in the Well Reference, must be a number.');
 
// -----------------------------------------------
// Station save - Action log messages
define('TEXT_ACTION_CREATE_STATION',            'New Station creation: ');
define('TEXT_ACTION_UPDATE_STATION',            'Station modification: ');


// Correction page (graph_correct_chron.php) - page header
define('TEXT_CORRECT_TITLE',              'Edit Time-series');
define('TEXT_CORRECT_STATION',            'Station: ');
define('TEXT_CORRECT_SERIES',             'Time-series: ');

define('TEXT_CORRECT_LINE_WIDTH',     'Line width');
define('TEXT_CORRECT_LINE_WIDTH_DEC', 'Thinner');
define('TEXT_CORRECT_LINE_WIDTH_INC', 'Thicker');

define('TEXT_CORRECT_ZOOM_BACK',       '↶');
define('TEXT_CORRECT_ZOOM_BACK_TITLE', 'Back to previous zoom');
define('TEXT_CORRECT_ZOOM_FORWARD', '↪');
define('TEXT_CORRECT_ZOOM_FORWARD_TITLE', 'Next zoom');

// Correction page - left panel labels
define('TEXT_CORRECT_SERIES_DETAILS',     'Time-series details');
define('TEXT_CORRECT_PERIOD_TITLE',       'Correction period');
define('TEXT_CORRECT_DATE_START',         'Start date');
define('TEXT_CORRECT_DATE_END',           'End date');
define('TEXT_CORRECT_HOUR',               'Time');
define('TEXT_CORRECT_Y_MIN',              'Y min');
define('TEXT_CORRECT_Y_MAX',              'Y max');
define('TEXT_CORRECT_APPLY_PERIOD', 'Apply period');
define('TEXT_CORRECT_ADJUST_SCALE',       'Adjust scale');
define('TEXT_CORRECT_OPTIONS_TITLE',      'Correction options');
define('TEXT_CORRECT_OPTIONS_OPEN',       'Open correction options');
define('TEXT_CORRECT_DUPLICATE',          'Duplicate time series');
define('TEXT_CORRECT_TIMESTEP',           'Time shift');
define('TEXT_CORRECT_INTERVAL',    'Interval');
define('TEXT_CORRECT_UNIT_MIN',    'min');
define('TEXT_CORRECT_UNIT_HOUR',   'hour');
define('TEXT_CORRECT_UNIT_DAY',    'day');
define('TEXT_CORRECT_UNIT_MONTH',  'month');
define('TEXT_CORRECT_UNIT_YEAR',   'year');
define('TEXT_CORRECT_UNIT_MIN_PLURAL',    'min');
define('TEXT_CORRECT_UNIT_HOUR_PLURAL',   'hours');
define('TEXT_CORRECT_UNIT_DAY_PLURAL',    'days');
define('TEXT_CORRECT_UNIT_MONTH_PLURAL',  'months');
define('TEXT_CORRECT_UNIT_YEAR_PLURAL',   'years');
define('TEXT_CORRECT_INFO_TIMESTEP', 'New chron. - Interval (%s) : %s %s');
define('TEXT_CORRECT_INTERVAL_MIN',       'Interval (min)');
define('TEXT_CORRECT_CALC_MODE',          'Method');
define('TEXT_CORRECT_CALC_MEAN',          'Mean');
define('TEXT_CORRECT_CALC_CUMUL',         'Sum');
define('TEXT_CORRECT_NEW_SERIES_BTN',     'Generate new series');
define('TEXT_CORRECT_TEMPORAL_GROUP',     'Temporal aggregation');

define('TEXT_CORRECT_MARKERS_LABEL',  'Points');
define('TEXT_CORRECT_MARKERS_TITLE',  'Show data points on the curve');
define('TEXT_CORRECT_INFO_DELETE',    'Deletion: %d point(s)');
define('TEXT_CORRECT_DEL_COUNT_1',    'point removed');
define('TEXT_CORRECT_DEL_COUNT_N',    'points removed');
define('TEXT_CORRECT_DEL_UNDO',       '↩ Undo');
define('TEXT_CORRECT_DEL_UNDO_TITLE', 'Undo last deletion (Ctrl+Z)');
define('TEXT_CORRECT_DEL_SAVE',       '✓ Save');
define('TEXT_CORRECT_DEL_SAVE_TITLE', 'Save as correction');

define('TEXT_CORRECT_SELECT_PERIOD_HINT', "Shift click + drag to select period");


// Correction info labels (shown in corrections table)
define('TEXT_CORRECT_INFO_OFFSET',    '%s second(s)');
define('TEXT_CORRECT_INFO_SMOOTHING', 'Smoothing - threshold: %s %%');
define('TEXT_CORRECT_INFO_GAP',       'Gap');

// Time-step error + summary
define('TEXT_CORRECT_ERR_NO_BUCKET',     'No complete bucket in your selection. Please select at least one full %s.');
define('TEXT_CORRECT_SUMMARY_COMPLETE',  '%d complete bucket(s) generated');
define('TEXT_CORRECT_SUMMARY_ANNOTATED', '%d bucket(s) annotated with gap warning');
define('TEXT_CORRECT_SUMMARY_GAPZONE',   '%d gap zone(s) generated');
define('TEXT_CORRECT_SUMMARY_EMPTY',     '%d empty bucket(s) skipped (no source data)');
define('TEXT_CORRECT_SUMMARY_PARTIAL',   '%d partial bucket(s) ignored');

// Success message period range
define('TEXT_CALCUL_SUCCESS_RANGE',   'from %s to %s');

// Correction page - graph panel
define('TEXT_CORRECT_IN_PROGRESS',        'Edit Time-series');
define('TEXT_CORRECT_ENLARGE',            'Full Screen');
define('TEXT_CORRECT_HQ_TITLE',           'Convert stage series to discharge using rating curve');
define('TEXT_CORRECT_HQ_BTN',             'H -> Q');
define('TEXT_CORRECT_ZOOM_X',             'Zoom / Move X');
define('TEXT_CORRECT_ZOOM_Y',             'Zoom / Move Y');
define('TEXT_CORRECT_LOADING',            'Loading...');
define('TEXT_CORRECT_NO_DATA',            'No data was found.');

// Correction page - corrections table
define('TEXT_CORRECT_TABLE_TITLE',        'Current corrections list');
define('TEXT_CORRECT_COL_TYPE',           'Type');
define('TEXT_CORRECT_COL_START',          'Start');
define('TEXT_CORRECT_COL_END',            'End');

// Correction page - save buttons
define('TEXT_CORRECT_SAVE',               'Save');
define('TEXT_CORRECT_SAVE_TITLE',         'Save to the same series');
define('TEXT_CORRECT_SAVEAS',             'Save as...');
define('TEXT_CORRECT_SAVEAS_TITLE',       'Save to a different series');
define('TEXT_CORRECT_PROCESSING',         'Data being processed');

define('TEXT_CORRECT_GAP_THRESHOLD',       'Gap threshold');
define('TEXT_CORRECT_GAP_THRESHOLD_TITLE', 'Maximum % of gaps allowed in a bucket before it is marked as a gap itself. Only applies to Mean mode (Cumul is strict: any gap = bucket is a gap).');

// Correction page - axis controls
define('TEXT_CORRECT_ADD_DECIMAL',        'Add a decimal place');
define('TEXT_CORRECT_REMOVE_DECIMAL',     'Remove a decimal place');
define('TEXT_CORRECT_LOG_SCALE',          'Log');
define('TEXT_CORRECT_LOG_SCALE_TITLE',    'Logarithmic scale (base 10)');

// Correction page - JS error/info messages (injected via LANG object)
define('TEXT_CORRECT_JS_ERR_GENERATE',    'Error generating the file.');
define('TEXT_CORRECT_JS_ERR_SERVER',      'Server request error.');
define('TEXT_CORRECT_JS_ERR_SELECT_ONE',  'You must select at least one current correction.');
define('TEXT_CORRECT_JS_ERR_DATE_ORDER',  'Start date and time must be earlier than end date and time.');
define('TEXT_CORRECT_JS_ERR_TIME_FMT',    'At least one of the entered times is invalid or in a wrong format (HH:MM or HH:MM:SS are valid formats).');
define('TEXT_CORRECT_JS_ERR_DATE_FMT',    'At least one of the entered dates is invalid or in a wrong format (dd-mm-yyyy is the valid format).');
define('TEXT_CORRECT_JS_ERR_Y_NUM',       'Error: Ymin and Ymax fields must be numbers.');

// process_chron_calcul_view.php + process_chron_calcul_graph.php
define('TEXT_CALCUL_VIEW_DOWNLOAD_TITLE',   'Download series');
define('TEXT_CALCUL_VIEW_APPLIED_TITLE',    'Correction applied');
define('TEXT_CALCUL_VIEW_DELETE_TITLE',     'Delete correction');
define('TEXT_CALCUL_VIEW_NONE',             'No corrections in progress.');
define('TEXT_CALCUL_VIEW_GAP_COL_SERIES',   'Series');
define('TEXT_CALCUL_VIEW_GAP_COL_START',    'Start date');
define('TEXT_CALCUL_VIEW_GAP_COL_END',      'End date');
define('TEXT_CALCUL_OPEN_TARGET_SERIES', 'Open this series in correction mode (new tab)');


// process_chron_calcul.php
define('TEXT_CALCUL_SUCCESS_TITLE',         'Correction successfully generated.');
define('TEXT_CALCUL_SUCCESS_TYPE',          'Type');
define('TEXT_CALCUL_SUCCESS_PERIOD',        'Period');

// process_chron_calcul_valid.php
define('TEXT_CALCUL_VALID_SUCCESS',         'Data update completed successfully.');
define('TEXT_CALCUL_VALID_ERROR_WRITE',     'The data correction encountered a problem while writing to the tables.');
define('TEXT_CALCUL_VALID_ERROR_DETAIL',    'An error occurred: ');
define('TEXT_CALCUL_VALID_NO_DATA',         'No data received.');
define('TEXT_CALCUL_VALID_BASE_OBS', 'Full series (source copy)');

// process_chron_calcul_del.php
define('TEXT_CALCUL_DEL_SUCCESS',           'Correction successfully deleted:<br>%s<br>Period: %s - %s');
define('TEXT_CALCUL_DEL_FAIL',              'The correction could not be deleted.');

// block_calcul_options.php
define('TEXT_CALCUL_OPT_TITLE',             'Calculation options for series');
define('TEXT_CALCUL_OPT_CLOSE',             'Close');
define('TEXT_CALCUL_OPT_LINEAR_TITLE',      'Linear function correction');
define('TEXT_CALCUL_OPT_LINEAR_FN',         'Ynew = aY + b');
define('TEXT_CALCUL_OPT_LINEAR_BTN',        'Generate correction');
define('TEXT_CALCUL_OPT_OFFSET_TITLE',      'Temporal shift (X-axis)');
define('TEXT_CALCUL_OPT_SECONDS',           'seconds');
define('TEXT_CALCUL_OPT_OFFSET_BTN',        'Generate correction');
define('TEXT_CALCUL_OPT_GAP_TITLE',         'Insert data gap');
define('TEXT_CALCUL_OPT_GAP_BTN',           'Generate gap');
define('TEXT_CALCUL_OPT_SMOOTH_TITLE',      'Smooth curve');
define('TEXT_CALCUL_OPT_SMOOTH_LOW',        'Low variation threshold');
define('TEXT_CALCUL_OPT_SMOOTH_THRESH',     'Threshold : ');
define('TEXT_CALCUL_OPT_SMOOTH_BTN',        'Smooth series');


// block_verif_savedata.php
define('TEXT_SAVEDATA_CONFIRM_TITLE',      'Are you sure you want to validate the corrections?');
define('TEXT_SAVEDATA_CHRON_LABEL',        'Time series to be modified:');
define('TEXT_SAVEDATA_CHRON_CURRENT',      'Current time series');
define('TEXT_SAVEDATA_CHRON_PLACEHOLDER', '-- Select the target time series --');
define('TEXT_CORRECT_JS_ERR_NO_TARGET', 'Please select the target time series for the correction.');
define('TEXT_SAVEDATA_QUAL_LABEL',         'Correction Quality Code');
define('TEXT_SAVEDATA_OBS_LABEL',          'Comment correction');
define('TEXT_SAVEDATA_OVERWRITE_WARNING',  'If data already exists for the same chronicle, station and period, it will be overwritten.');
define('TEXT_SAVEDATA_BTN_CONFIRM',        'Confirm');
define('TEXT_SAVEDATA_BTN_CANCEL',         'Cancel');
define('TEXT_SAVEDATA_CURRENT_SERIES', 'Current series');
define('TEXT_SAVEDATA_CURRENT_BADGE',  'current');



// =============================================================================
// GAUGING MODULE - STREAMFLOW GAUGINGS
// =============================================================================

// -----------------------------------------------
// Gauging list - data_jge.php

// data_jge.php
define('TEXT_JGE_LIST_TITLE',           'Stream gauging list');
define('TEXT_JGE_LIST_NEW_BTN',         'New gauging');
define('TEXT_JGE_LIST_PERIODE',         'Period');
define('TEXT_JGE_LIST_PERIODE_1M',      '1 month');
define('TEXT_JGE_LIST_PERIODE_3M',      '3 months');
define('TEXT_JGE_LIST_PERIODE_6M',      '6 months');
define('TEXT_JGE_LIST_PERIODE_1Y',      '1 year');
define('TEXT_JGE_LIST_PERIODE_2Y',      '2 years');
define('TEXT_JGE_LIST_PERIODE_5Y',      '5 years');
define('TEXT_JGE_LIST_PERIODE_10Y',     '10 years');
define('TEXT_JGE_LIST_PERIODE_ALL',     'All data');
define('TEXT_JGE_LIST_SORT_BY',         'Sort by');
define('TEXT_JGE_LIST_SORT_NAME',       'Name');
define('TEXT_JGE_LIST_SORT_CODE',       'Code');
define('TEXT_JGE_LIST_SORT_DATE',       'Date');
define('TEXT_JGE_LIST_ASC',             'Ascending');
define('TEXT_JGE_LIST_DESC',            'Descending');
define('TEXT_JGE_LIST_COUNT',           'Number of gaugings: ');
define('TEXT_JGE_LIST_TH_TYPE',         'Type');
define('TEXT_JGE_LIST_TH_CODE',         'Station code');
define('TEXT_JGE_LIST_TH_STATION',      'Station name');
define('TEXT_JGE_LIST_TH_DATE',         'Date');
define('TEXT_JGE_LIST_TH_HEURE',        'Time');
define('TEXT_JGE_LIST_TH_BRAS',         'River branch');
define('TEXT_JGE_LIST_TH_Q',            'Flow [m³/s]');
define('TEXT_JGE_LIST_TH_H',            'Height [cm]');
define('TEXT_JGE_LIST_EDIT_TITLE',      'Edit H/Q summary values');
define('TEXT_JGE_LIST_EDIT_FULL_TITLE', 'Enter detailed gauging by points');
define('TEXT_JGE_LIST_DEL_TITLE',       'Delete gauging');
define('TEXT_JGE_LIST_NOT_FOUND',       'No gauging record was found');

// -----------------------------------------------
// Gauging detail page
define('TEXT_JGE_PAGE_NEW',           'New stream gauging');
define('TEXT_JGE_PAGE_LABEL',         'Gauging: ');
define('TEXT_JGE_PAGE_SAVE',          'Save');
define('TEXT_JGE_PAGE_TITLE_ERROR',   'Gauging');
define('TEXT_JGE_PAGE_NOT_FOUND',     'No gauging record was found');
define('TEXT_JGE_SIDEBAR_Q',          'Flow rate [m³/s]');
define('TEXT_JGE_SIDEBAR_HMOY',       'Mean height [cm]');
define('TEXT_JGE_SIDEBAR_ETL_TITLE',  'Show H→Q rating curve');
define('TEXT_JGE_SIDEBAR_ETL_LINK',   '- View rating curve -');
define('TEXT_JGE_SIDEBAR_DATE',       'Date');
define('TEXT_JGE_SIDEBAR_HEURE',      'Time');
define('TEXT_JGE_SIDEBAR_STATION',    'Station');
define('TEXT_JGE_SIDEBAR_CODE_QUAL',  'Quality code');
define('TEXT_JGE_PANEL_SITUATION',    'Location');
define('TEXT_JGE_PANEL_DIST_SITE',    'Site distance [m]');
define('TEXT_JGE_PANEL_SITE',         'Site - Detail');
define('TEXT_JGE_PANEL_GPS_X',        'X coord. (GPS)');
define('TEXT_JGE_PANEL_GPS_Y',        'Y coord. (GPS)');
define('TEXT_JGE_PANEL_METHODE',      'Gauging method');
define('TEXT_JGE_PANEL_TYPE',         'Gauging type');
define('TEXT_JGE_PANEL_METHODE_SEL',  'Method');
define('TEXT_JGE_PANEL_DETAILS',      'Details');
define('TEXT_JGE_PANEL_AGENTS',       'Field agents');
define('TEXT_JGE_PANEL_OBS',          'Comment');
define('TEXT_JGE_PANEL_FICHIER',      'File link');
define('TEXT_JGE_TAB_BRAS',           'Branch');
define('TEXT_JGE_TAB_NEW_BRAS',       'New branch');
define('TEXT_JGE_ETL_ERR_DATE',       'Gauging date is not in the correct format (dd-mm-yyyy)');
define('TEXT_JGE_ETL_ERR_VALUES',     'Mean height and flow rate values must be numbers');

// -----------------------------------------------
// Gauging deletion

define('TEXT_JGE_DEL_STATION',              'Station : ');
define('TEXT_JGE_DEL_NOT_FOUND',            'This flow measurement does not exist and cannot be deleted.');
define('TEXT_JGE_DEL_INVALID',              'The gauging record identifier is invalid.');


// -----------------------------------------------
// Gauging deletion confirmation

define('TEXT_JGE_VERIFDEL_IRREVERSIBLE',    'This action is irreversible.');
define('TEXT_JGE_VERIFDEL_OK',              'Confirm');
define('TEXT_JGE_VERIFDEL_CANCEL',          'Cancel');

// -----------------------------------------------
// Gauging deletion confirmation - full record
define('TEXT_JGE_VERIFDEL_TITLE',           'Are you sure you want to delete this Gauging record?');

// -----------------------------------------------
// Gauging deletion confirmation - branch
define('TEXT_JGE_BRAS_VERIFDEL_TITLE',      'Are you sure you want to delete this Gauging branch?');
define('TEXT_JGE_BRAS_VERIFDEL_WARNING',    'Warning!');
define('TEXT_JGE_BRAS_VERIFDEL_UNSAVED',    'Any unsaved changes will be lost.');

// -----------------------------------------------
// Gauging branch deletion

define('TEXT_JGE_BRAS_DEL_SUCCESS',   'Arm successfully deleted.');
define('TEXT_JGE_BRAS_DEL_ERR_DB',        'Database connection error.');
define('TEXT_JGE_BRAS_DEL_ERR_INVALID',   'Invalid arm identifier.');
define('TEXT_JGE_BRAS_DEL_ERR_NOT_FOUND', 'Arm not found or outside your territory.');
define('TEXT_JGE_BRAS_DEL_ERR_FAILED',    'Arm deletion failed.');
define('TEXT_JGE_BRAS_DEL_LOG',           'Gauging arm deletion');


// -----------------------------------------------
// Gauging equipment - propeller detail popup
define('TEXT_JGE_HELICE_INFO_TITLE', 'Show the propeller equation details');
define('TEXT_JGE_HELICE_TITLE',             'Propeller description: ');
define('TEXT_JGE_HELICE_CLOSE',             'Close');
define('TEXT_JGE_HELICE_EQ_TITLE',          'Velocity equations:');
define('TEXT_JGE_HELICE_MULT_N_PRE', '*');
define('TEXT_JGE_HELICE_N_LTE',             '<=');
define('TEXT_JGE_HELICE_N_GT',              '<');
define('TEXT_JGE_HELICE_V_EQ',              'v =');
define('TEXT_JGE_HELICE_MULT_N',            '* n +');
define('TEXT_JGE_HELICE_FORMULA_TITLE',     'Propeller calibration formula');
define('TEXT_JGE_HELICE_FORMULA',           'v = k * n + a');
define('TEXT_JGE_HELICE_VAR_V',             ': velocity [m/s]');
define('TEXT_JGE_HELICE_VAR_K',             ': calibration constant [m]');
define('TEXT_JGE_HELICE_VAR_N',             ': rotational speed [rev/s]');
define('TEXT_JGE_HELICE_VAR_A',             ': starting constant [m/s]');

define('TEXT_JGE_BRAS_STREAMPRO', 'StreamPro');

define('TEXT_JGE_BRAS_NEED_HELICE',          'Please select a propeller before entering the points.');
define('TEXT_JGE_BRAS_MOULINET_PLACEHOLDER', 'Select a Flow tracker...');
define('TEXT_JGE_BRAS_HELICE_PLACEHOLDER',   'Select a propeller...');


// -----------------------------------------------
// Quick gauging entry popup

// -----------------------------------------------
// JGE SIMPLE — Popup labels (likely already defined, listed for reference)
 
define('TEXT_JGE_SIMPLE_TITLE',                  'Quick gauging entry');
define('TEXT_JGE_SIMPLE_STATION',                'Station');
define('TEXT_JGE_SIMPLE_DEBIT',                  'Flow rate (m3/s)');
define('TEXT_JGE_SIMPLE_HAUTEUR',                'Water level (cm)');
define('TEXT_JGE_SIMPLE_DATE',                   'Date');
define('TEXT_JGE_SIMPLE_HEURE',                  'Time');
define('TEXT_JGE_SIMPLE_OBS',                    'Comment');
define('TEXT_JGE_SIMPLE_CODE_QUAL',              'Quality code');
define('TEXT_JGE_SIMPLE_SAVE',                   'Save');
 
 
// -----------------------------------------------
// JGE SIMPLE — New constants for the AJAX refactor
 
define('TEXT_JGE_SIMPLE_CODE_QUAL_PLACEHOLDER',  'Select quality code...');
 
// Success messages — %s placeholders are filled by sprintf() with the station info
define('TEXT_JGE_SIMPLE_CREATED',                'Gauging successfully created for station %s.');
define('TEXT_JGE_SIMPLE_UPDATED',                'Gauging successfully updated for station %s.');
 
// Log entries (written to TABLE_ACTIONS.info)
define('TEXT_JGE_SIMPLE_LOG_CREATE',             'JGE creation');
define('TEXT_JGE_SIMPLE_LOG_UPDATE',             'JGE update');
 
// Error messages
define('TEXT_JGE_SIMPLE_ERR_HMOY',               'Water level is required and must be numeric.');
define('TEXT_JGE_SIMPLE_ERR_Q',                  'Flow rate is required and must be numeric.');
define('TEXT_JGE_SIMPLE_ERR_DATE',               'Date format is invalid. Expected: dd-mm-yyyy.');
define('TEXT_JGE_SIMPLE_ERR_HEURE',              'Time format is invalid. Expected: hh:mm:ss.');
define('TEXT_JGE_SIMPLE_ERR_STATION',            'A valid station must be selected.');
define('TEXT_JGE_SIMPLE_ERR_DB',                 'Unable to connect to the database.');

// -----------------------------------------------
// JGE DELETION — AJAX deletion of a gauging record
 
// Confirmation popup — math challenge hint
define('TEXT_JGE_VERIFDEL_CHALLENGE_HINT',       'To confirm the deletion, please solve this simple operation:');
 
// Success message — %1$s = date, %2$s = station code/name
define('TEXT_JGE_DEL_SUCCESS',                   'Gauging of %1$s successfully deleted - Station: %2$s.');
 
// Log entry (written to TABLE_ACTIONS.info)
define('TEXT_JGE_DEL_LOG',                       'JGE deletion');
 
// Error messages
define('TEXT_JGE_DEL_ERR_INVALID',               'Invalid gauging id.');
define('TEXT_JGE_DEL_ERR_NOT_FOUND',             'Gauging not found or not in your territory.');
define('TEXT_JGE_DEL_ERR_FAILED',                'Database delete failed.');
define('TEXT_JGE_DEL_ERR_DB',                    'Unable to connect to the database.');


// -----------------------------------------------
// Measurement point Comment popup

define('TEXT_JGE_OBS_TITLE',                'Measurement point comment');
define('TEXT_JGE_OBS_VERTICALE',            'Vertical number');
define('TEXT_JGE_OBS_DIST',                 'Distance from start [m]');
define('TEXT_JGE_OBS_PROF',                 'Measurement depth [m]');
define('TEXT_JGE_OBS_OBS',                  'Comment');
define('TEXT_JGE_OBS_VALIDATE',             'Validate');


// -----------------------------------------------
// Gauging points data entry

define('TEXT_JGE_PTS_TITLE',                'Gauging data entry by points');
define('TEXT_JGE_PTS_TT_VERTICALE', 'Vertical');
define('TEXT_JGE_PTS_CLOSE',                'Close');
define('TEXT_JGE_PTS_CALC_TITLE',           'Calculate velocities and flow');
define('TEXT_JGE_PTS_CALC_BTN',             'Validate and calculate flow');
define('TEXT_JGE_PTS_HELP',                'Input Help');
define('TEXT_JGE_PTS_INPUT_LABEL',          'Data to enter: ');
define('TEXT_JGE_PTS_OPT_TOPS',             'Number of rotations (TOPs)');
define('TEXT_JGE_PTS_OPT_TOPS_SEC',         'Rotations per second (TOPs/sec)');
define('TEXT_JGE_PTS_OPT_VITESSE',          'Velocity');
define('TEXT_JGE_PTS_COL_VERT',             'Vert. No.');
define('TEXT_JGE_PTS_COL_VERT_TITLE',       'Vertical number');
define('TEXT_JGE_PTS_COL_DIST',             'Dist. start <br> [m]');
define('TEXT_JGE_PTS_COL_DIST_TITLE',       'Distance from start');
define('TEXT_JGE_PTS_COL_PROFMAX',          'Tot depth <br> [m]');
define('TEXT_JGE_PTS_COL_PROFMAX_TITLE',    'Total depth of the vertical');
define('TEXT_JGE_PTS_COL_PROFMESURE',       'Depth <br> [m]');
define('TEXT_JGE_PTS_COL_PROFMESURE_TITLE', 'Measurement depth');
define('TEXT_JGE_PTS_COL_TOPS',             'TOPs');
define('TEXT_JGE_PTS_COL_TOPS_TITLE',       'Number of rotations');
define('TEXT_JGE_PTS_COL_TEMPS',            'Time <br> [s]');
define('TEXT_JGE_PTS_COL_TEMPS_TITLE',      'Recording time');
define('TEXT_JGE_PTS_COL_TOPS_SEC',         'TOPs/sec');
define('TEXT_JGE_PTS_COL_TOPS_SEC_TITLE',   'Rotations per second');
define('TEXT_JGE_PTS_COL_VITESSE',          'Velocity <br> [m/s]');
define('TEXT_JGE_PTS_COL_VITESSE_TITLE',    'Velocity');
define('TEXT_JGE_PTS_COL_OBS',              'Comment');

define('TEXT_JGE_PTS_CONFIRM_TITLE',      'Close the entry form');
define('TEXT_JGE_PTS_CONFIRM_CLOSE',      'Do you want to calculate the flow before closing, close without saving, or stay on the form?');
define('TEXT_JGE_PTS_CONFIRM_CANCEL',     'Cancel');
define('TEXT_JGE_PTS_CONFIRM_CLOSE_ONLY', 'Close without calculating');
define('TEXT_JGE_PTS_CONFIRM_CALC_CLOSE', 'Calculate and close');


// -----------------------------------------------
// Rating curve display popup (gauging view)

define('TEXT_JGE_ETL_BOX_TITLE',            'Rating curve');
define('TEXT_JGE_ETL_CLOSE',                'Close');
define('TEXT_JGE_ETL_LOADING',              'Loading...');


// -----------------------------------------------
// Gauging branch form - channel / river

define('TEXT_JGE_BRAS_HEURE_FIRST',         'Start Time');
define('TEXT_JGE_BRAS_ECH_FIRST',           'Start height [cm]');
define('TEXT_JGE_BRAS_HEURE_END',           'End Time');
define('TEXT_JGE_BRAS_ECH_END',             'End height [cm]');
define('TEXT_JGE_BRAS_FOND',                'Substrate');
define('TEXT_JGE_BRAS_BERGE',               'Starting bank');
define('TEXT_JGE_BRAS_RIVE_GAUCHE',         'Left Bank');
define('TEXT_JGE_BRAS_RIVE_DROITE',         'Right Bank');
define('TEXT_JGE_BRAS_OBS',                 'Comment');
define('TEXT_JGE_BRAS_DELETE',              'Delete branch');
define('TEXT_JGE_BRAS_DEPOUIL_TITLE',       'Gauging computation');
define('TEXT_UNSAVED_CHANGES',              'Unsaved changes');
define('TEXT_JGE_BRAS_PERCHE_TITLE',        'Wading rod diameter');
define('TEXT_JGE_BRAS_PERCHE_LABEL',        'Rod diam. [mm]');
define('TEXT_JGE_BRAS_MOULINET',            'Current meter');
define('TEXT_JGE_BRAS_HELICE',              'Propeller');
define('TEXT_JGE_BRAS_SAISIE_BTN',          'Enter gauging data');
define('TEXT_JGE_BRAS_Q_TITLE',             'Instantaneous discharge');
define('TEXT_JGE_BRAS_Q_LABEL',             'Flow (Q) [m3/s]');
define('TEXT_JGE_BRAS_HMOY_TITLE',          'Mean stage');
define('TEXT_JGE_BRAS_HMOY_LABEL',          'Mean height [cm]');
define('TEXT_JGE_BRAS_VMOY_TITLE',          'Mean velocity');
define('TEXT_JGE_BRAS_VMOY_LABEL',          'Mean vel. [m/s]');
define('TEXT_JGE_BRAS_VSURF_TITLE',         'Mean surf. velocity');
define('TEXT_JGE_BRAS_VSURF_LABEL',         'Mean surf. vel. [m/s]');
define('TEXT_JGE_BRAS_SURFMOUIL_TITLE',     'Wetted cross-section');
define('TEXT_JGE_BRAS_SURFMOUIL_LABEL',     'Wetted area [m2]');
define('TEXT_JGE_BRAS_PERIMOUIL_TITLE',     'Wetted perimeter');
define('TEXT_JGE_BRAS_PERIMOUIL_LABEL',     'Wetted perim. [m]');
define('TEXT_JGE_BRAS_PROFMOY_TITLE',       'Mean depth');
define('TEXT_JGE_BRAS_PROFMOY_LABEL',       'Mean depth [cm]');
define('TEXT_JGE_BRAS_DISTMAX_TITLE',       'Total channel width');
define('TEXT_JGE_BRAS_DISTMAX_LABEL',       'Total width [m]');
define('TEXT_JGE_BRAS_RH_TITLE',            'Hydraulic radius Rh');
define('TEXT_JGE_BRAS_RH_LABEL',            'Hyd. radius');
define('TEXT_JGE_BRAS_GRAPH_PLACEHOLDER',   '- Graph : Cross Section -');


// -----------------------------------------------
// Gauging save - validation & result messages

define('TEXT_JGE_SAVE_ERR_HMOY',                '- ʼMean Heightʼ value must be a number.');
define('TEXT_JGE_SAVE_ERR_Q',                   '- ʼFlow rateʼ value must be a number.');
define('TEXT_JGE_SAVE_ERR_DATE',                '- Gauging date format is incorrect: dd-mm-YYYY.');
define('TEXT_JGE_SAVE_ERR_HEURE',               '- Gauging time format is incorrect: hh:mm:ss or hh:mm.');
define('TEXT_JGE_SAVE_ERR_STATION',             '- The gauging must be linked to a station.');
define('TEXT_JGE_SAVE_ERR_DIST',                '- ʼSite distanceʼ value must be a number.');
define('TEXT_JGE_SAVE_ERR_BRAS_FIRST_REQUIRED', '- Branch %d: start time and start gauge height are required.');
define('TEXT_JGE_SAVE_ERR_BRAS_HFIRST',         '- Branch %d: start time format is incorrect: hh:mm:ss or hh:mm.');
define('TEXT_JGE_SAVE_ERR_BRAS_ECHFIRST',       '- Branch %d: start gauge value must be a number.');
define('TEXT_JGE_SAVE_ERR_BRAS_HEND',           '- Branch %d: end time format is incorrect: hh:mm:ss or hh:mm.');
define('TEXT_JGE_SAVE_ERR_BRAS_ECHEND',         '- Branch %d: end gauge value must be a number.');
define('TEXT_JGE_SAVE_ERR_BRAS_PERCHE',         '- Branch %d: rod diameter value must be a number.');
define('TEXT_JGE_SAVE_ERR_BRAS_FIELDS',         '- Time and gauge fields must all be filled in.');
define('TEXT_JGE_SAVE_ERR_PTS_TITLE',           'An error occurred: the gauging record could not be saved.');
define('TEXT_JGE_SAVE_ERR_PTS_FORMAT',          '- Some values in the ʼGauging Pointsʼ table are not in the correct format (numeric expected).');
define('TEXT_JGE_SAVE_ERR_TRANSACTION',         'The data save encountered a problem while writing to the tables.');
define('TEXT_JGE_SAVE_ERR_EXCEPTION',           'An error occurred: ');
define('TEXT_JGE_SAVE_ERR_GENERAL',             'An error occurred: the gauging record could not be saved.');
define('TEXT_JGE_SAVE_ERR_METHOD',              'An error occurred while sending data to the server.');
define('TEXT_JGE_SAVE_CREATED',                 'New gauging successfully created.');
define('TEXT_JGE_SAVE_UPDATED',                 'Gauging successfully updated.');
define('TEXT_JGE_SAVE_STATION_LABEL',           'Station: ');
define('TEXT_JGE_SAVE_ACTION_CREATE',           'New gauging created: ');
define('TEXT_JGE_SAVE_ACTION_UPDATE',           'Gauging updated: ');

// -----------------------------------------------
// Gauging by points (js_jge.js)
// -----------------------------------------------
 
// Row delete button
define('TEXT_JGE_BTN_DELETE_TITLE',   'Delete');
 
// Console warning
define('TEXT_JGE_WARN_NO_FREE_ROW',   'No more rows available to pre-fill vertical ');
 
// calc_q() return messages
define('TEXT_JGE_MSG_CALC_OK',        'The calculation was successfully performed.');
define('TEXT_JGE_MSG_CALC_OK_REMIND', 'Do not forget to save the Gauging, otherwise the data will be lost');
define('TEXT_JGE_MSG_CALC_ERR',       'Error !!!');
define('TEXT_JGE_MSG_CALC_ERR_RUN',   'The Gauging calculation could not be performed');
define('TEXT_JGE_MSG_CALC_ERR_EMPTY', 'No data has been entered');
 
// Plotly graph — trace names
define('TEXT_JGE_TRACE_POINTS_NAME',  'Gauging points');
define('TEXT_JGE_TRACE_BED_NAME',     'Bed profile');
define('TEXT_JGE_TRACE_VSURF_NAME', 'Surface velocity');
define('TEXT_JGE_TRACE_VMOY_NAME',  'Mean velocity');
define('TEXT_JGE_AXIS_VELOCITY',    'Velocity [m/s]');
define('TEXT_JGE_TT_VERTICALE',     'Vertical');
 
// Plotly graph — tooltip labels
define('TEXT_JGE_TT_DISTANCE',        'Distance');
define('TEXT_JGE_TT_DEPTH',           'Depth');
define('TEXT_JGE_TT_VELOCITY',        'Velocity');
define('TEXT_JGE_TT_OBSERVATION',     'Comment');
 
// Plotly graph — axis titles
define('TEXT_JGE_AXIS_DISTANCE',      'Distance [m]');
define('TEXT_JGE_AXIS_DEPTH',         'Depth [m]');

// -----------------------------------------------
// Gauging - rating curve graph

define('TEXT_JGE_ETL_STATION',              'Station :');
define('TEXT_JGE_ETL_PERIODE',              'Rating Curve Period:');
define('TEXT_JGE_ETL_DU',                   'from');
define('TEXT_JGE_ETL_AU',                   'to');
define('TEXT_JGE_ETL_NO_ETL',               'No Rating Curve covers this gauging date.');
define('TEXT_JGE_ETL_NO_JGE',               'No Stream Gauging data found.');
define('TEXT_JGE_ETL_NB_PTS',               'Number of gauging points:');
define('TEXT_JGE_ETL_ENCOURS',              'Current');
define('TEXT_JGE_ETL_AXIS_H',               'Height (cm)');
define('TEXT_JGE_ETL_AXIS_Q',               'Flow rate (m3/s)');




// =============================================================================
// AGENT MODULE
// =============================================================================

// -----------------------------------------------
// Agent list
define('TEXT_AGENT_LIST_TITLE',          'Agent List');
define('TEXT_AGENT_LIST_NEW_BTN',        'New Agent');
define('TEXT_AGENT_LIST_SEARCH',         'Search');
define('TEXT_AGENT_LIST_COUNT',          'Total agents: ');
define('TEXT_AGENT_LIST_COUNT_SERVICE',  '');
define('TEXT_AGENT_LIST_COUNT_TERRAIN',  'Field agents: ');
define('TEXT_AGENT_LOADING',             'Loading...');
define('TEXT_AGENT_LOADING_WAIT',        '- Please wait -');

// -----------------------------------------------
// Agent list - table headers
define('TEXT_AGENT_TH_NOM',         'Last name');
define('TEXT_AGENT_TH_PRENOM',      'First name');
define('TEXT_AGENT_TH_EMAIL',       'Email');
define('TEXT_AGENT_TH_TEL',         'Phone');
define('TEXT_AGENT_TH_INSTITUTION', 'Institution');
define('TEXT_AGENT_TH_FONCTION',    'Position');
define('TEXT_AGENT_TH_SERVICE',     ' ');
define('TEXT_AGENT_TH_TERRAIN',     'Field agent');

// -----------------------------------------------
// Agent form
define('TEXT_AGENT_NOM',               'Last name');
define('TEXT_AGENT_NOM_MARITAL',       'Married name');
define('TEXT_AGENT_PRENOM',            'First name');
define('TEXT_AGENT_SECTION_ACTIVITE',  'Activity');
define('TEXT_AGENT_INSTITUTION',       'Institution / Company');
define('TEXT_AGENT_FONCTION',          'Position');
define('TEXT_AGENT_NUMINSCRIPTION',    'Registration number');
define('TEXT_AGENT_SECTION_COORDONNEES', 'Contact details');
define('TEXT_AGENT_TEL',               'Phone');
define('TEXT_AGENT_MOBILE',            'Mobile');
define('TEXT_AGENT_FAX',               'Fax');
define('TEXT_AGENT_EMAIL',             'Email');
define('TEXT_AGENT_SITEWEB',           'Website');
define('TEXT_AGENT_SECTION_ADRESSE',   'Address');
define('TEXT_AGENT_RUE',               'Street');
define('TEXT_AGENT_LIEUDIT',           'Locality');
define('TEXT_AGENT_BP',                'P.O. Box');
define('TEXT_AGENT_CODEPOSTAL',        'Postal code');
define('TEXT_AGENT_COMMUNE',           'Town / Village');
define('TEXT_AGENT_CHECK_TERRAIN',     'Field agent');
define('TEXT_AGENT_CHECK_SERVICE',     '');
define('TEXT_AGENT_BTN_SAVE',          'Save');
define('TEXT_AGENT_BTN_CANCEL',        'Cancel');
define('TEXT_AGENT_BTN_DELETE',        'Delete');
define('TEXT_AGENT_FICHE_TITLE',       'Agent :');
define('TEXT_AGENT_FICHE_NEW',         'Create new agent record');

// -----------------------------------------------
// Agent status badges
define('TEXT_AGENT_PUCE_SERVICE',  '');
define('TEXT_AGENT_PUCE_TERRAIN',  'Field agent');
define('TEXT_AGENT_DEL_LINK_TITLE','Delete agent');
define('TEXT_AGENT_NOT_FOUND',     'No agent record was found');
define('TEXT_AGENT_LABEL',         'Agent');

// -----------------------------------------------
// Agent deletion
define('TEXT_AGENT_DEL_CONFIRM_TITLE', 'Are you sure you want to delete this agent ?');
define('TEXT_AGENT_DEL_NOM',           'Last name: ');
define('TEXT_AGENT_DEL_PRENOM',        'First name: ');
define('TEXT_AGENT_DEL_SUCCESS',    'The agent record has been successfully deleted');
define('TEXT_AGENT_DEL_ERROR',      'An error occurred while deleting the agent ');
define('TEXT_AGENT_DEL_ACTION_LOG', 'Agent deleted');

// -----------------------------------------------
// Agent save - validation & result messages
define('TEXT_AGENT_SAVE_CREATED',          'Agent successfully created');
define('TEXT_AGENT_SAVE_UPDATED',          'Agent successfully updated');
define('TEXT_AGENT_SAVE_ERR_NOM',          'Last name is a required field');
define('TEXT_AGENT_SAVE_ERR_DUPLICATE',    'An agent with the same last and first name already exists:');
define('TEXT_AGENT_SAVE_ERR_DUPLICATE_SUFFIX', 'This agent cannot be added again');
define('TEXT_AGENT_SAVE_ERR_GENERAL',      'Error: the agent could not be saved');
define('TEXT_AGENT_SAVE_ERR_REQUEST',      'An error occurred while sending data to the server');
define('TEXT_AGENT_SAVE_ACTION_CREATE',    'New agent record created');
define('TEXT_AGENT_SAVE_ACTION_UPDATE',    'Agent updated');




// =============================================================================
// SETTINGS - GEOGRAPHIC ZONES
// =============================================================================

// -----------------------------------------------
// gestion_geo.php - page title, save button, tabs

define('TEXT_GEO_PAGE_TITLE',       'Geographic data entry');
define('TEXT_GEO_BTN_SAVE',         'Save');

// Tab labels - TEXT_GEO_TAB_REGION has $theme_region appended at runtime
define('TEXT_GEO_TAB_REGION',       'Regions - ');
define('TEXT_GEO_TAB_COMMUNES',     'Town / Village');
define('TEXT_GEO_TAB_REGIONHYDRO',  'Watershed');
define('TEXT_GEO_TAB_RIVIERES',     'Rivers');
define('TEXT_GEO_TAB_AQUIFERES',    'Aquifers');
define('TEXT_GEO_TAB_TOURNEES',     'Rounds');

// -----------------------------------------------
// Shared table column headers (used by multiple process_tab_*.php files)

define('TEXT_GEO_TH_INTITULE',      'Name');
define('TEXT_GEO_TH_DESCRIPTION',   'Description');
define('TEXT_GEO_BTN_DELETE',       'Delete');
define('TEXT_GEO_NO_DATA',          'No data found');

// -----------------------------------------------
// process_tab_region.php - geographic region table
// Both constants have $theme_region appended at runtime

define('TEXT_GEO_REGION_TH',        'Name - ');
define('TEXT_GEO_REGION_ADD',       'Add - ');

// -----------------------------------------------
// process_tab_commune.php - town table

define('TEXT_GEO_COMMUNE_TH_NOM',    'Town / Village');
define('TEXT_GEO_COMMUNE_TH_REGION', 'Associated region');
define('TEXT_GEO_COMMUNE_ADD',       'Add a town');

// -----------------------------------------------
// process_tab_regionhydro.php - hydrological region table

define('TEXT_GEO_REGIONHYDRO_ADD',   'Add a hydrological region');

// -----------------------------------------------
// process_tab_riviere.php - river table

define('TEXT_GEO_RIVIERE_TH_NOM',    'River name');
define('TEXT_GEO_RIVIERE_TH_REGION', 'Associated hydrological region');
define('TEXT_GEO_RIVIERE_ADD',       'Add a river');

// -----------------------------------------------
// process_tab_aquifere.php - aquifer table (legacy, no territoire filter)

define('TEXT_GEO_AQUIFERE_ADD',      'Add an aquifer');


// -----------------------------------------------
// process_tab_tournee.php - round table

define('TEXT_GEO_TOURNEE_ADD',       'Add a monitoring round');


// -----------------------------------------------
// process_datageo_save.php - bulk save result messages

define('TEXT_GEO_SAVE_OK',          'Geographic data (Regions, Town / Village, Whatershed, Rivers, Aquifers and Rounds) have been saved successfully.');
define('TEXT_GEO_SAVE_ERR_WRITE',   'An error occurred while writing geographic data to the database.');
define('TEXT_GEO_SAVE_ERR_DETAIL',  'Error details: ');
define('TEXT_GEO_SAVE_ERR_REQUEST', 'An error occurred while sending data to the server.');
define('TEXT_GEO_SAVE_ACTION_LOG',  'Geographic data saved');



// -----------------------------------------------
// Deletion handlers

// -----------------------------------------------
// process_delaquifere.php - aquifer deletion

define('TEXT_GEO_AQUIFERE_DEL_OK',          'The aquifer has been successfully deleted.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_LINKED',  'could not be deleted.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_STATION', 'It is linked to at least one station.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_NOTFOUND','The aquifer does not exist.');


// -----------------------------------------------
// process_delregiongeo.php - geographic region deletion messages

define('TEXT_GEO_REGION_DEL_OK',           'has been successfully deleted.');
define('TEXT_GEO_REGION_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_GEO_REGION_DEL_ERR_DEPENDENCY','It is linked to at least one town or station.');
define('TEXT_GEO_REGION_DEL_ERR_NOTFOUND', 'The geographic region does not exist.');

// -----------------------------------------------
// process_delcommune.php - town deletion messages

define('TEXT_GEO_COMMUNE_DEL_OK',           'has been successfully deleted.');
define('TEXT_GEO_COMMUNE_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_GEO_COMMUNE_DEL_ERR_STATION',  'It is linked to at least one station.');
define('TEXT_GEO_COMMUNE_DEL_ERR_NOTFOUND', 'The town does not exist.');

// -----------------------------------------------
// process_delregionhydro.php - hydrological region deletion messages

define('TEXT_GEO_REGIONHYDRO_DEL_OK',           'has been successfully deleted.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_DEPENDENCY','It is linked to at least one river or station.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_NOTFOUND', 'The hydrological region does not exist.');

// -----------------------------------------------
// process_delriviere.php - river deletion messages

define('TEXT_GEO_RIVIERE_DEL_OK',           'has been successfully deleted.');
define('TEXT_GEO_RIVIERE_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_GEO_RIVIERE_DEL_ERR_STATION',  'It is linked to at least one station.');
define('TEXT_GEO_RIVIERE_DEL_ERR_NOTFOUND', 'The river does not exist.');

// -----------------------------------------------
// process_deltournee.php - round deletion messages

define('TEXT_GEO_TOURNEE_DEL_OK',           'has been successfully deleted.');
define('TEXT_GEO_TOURNEE_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_GEO_TOURNEE_DEL_ERR_STATION',  'It is linked to at least one station.');
define('TEXT_GEO_TOURNEE_DEL_ERR_NOTFOUND', 'The round does not exist.');

// GEO SAVE — Validation error messages
// %s placeholders are filled by sprintf() at runtime
 
define('TEXT_GEO_SAVE_ERR_VALIDATION',   'Save failed - please correct the errors below:');
 
// %1$s = context label (region / town / etc.)
define('TEXT_GEO_SAVE_ERR_NOM_EMPTY',    'The %s name cannot be empty.');
 
// %1$s = context label, %2$d = max length
define('TEXT_GEO_SAVE_ERR_NOM_TOO_LONG', 'The %1$s name is too long (max %2$d characters).');
 
// %1$s = context label, %2$d = max length
define('TEXT_GEO_SAVE_ERR_DESC_TOO_LONG', 'The %1$s description is too long (max %2$d characters).');
 
// %1$s = context label, %2$s = name attempted
define('TEXT_GEO_SAVE_ERR_DUPLICATE',    'A %1$s named "%2$s" already exists.');
 
 
// -----------------------------------------------
// GEO SAVE — Context labels (used as %s in the messages above)
 
define('TEXT_GEO_CTX_REGION',       'region');
define('TEXT_GEO_CTX_COMMUNE',      'town');
define('TEXT_GEO_CTX_REGIONHYDRO',  'hydrological region');
define('TEXT_GEO_CTX_RIVIERE',      'river');
define('TEXT_GEO_CTX_AQUIFERE',     'aquifer');
define('TEXT_GEO_CTX_TOURNEE',      'round');

// -----------------------------------------------
// GEO DELETION — Confirmation popup
// %s is filled by sprintf() with the entity name (bold HTML)
 
define('TEXT_GEO_VERIFDEL_TITLE',            'Confirm deletion');
define('TEXT_GEO_VERIFDEL_IRREVERSIBLE',     'This action is irreversible.');
define('TEXT_GEO_VERIFDEL_CHALLENGE_HINT',   'To confirm the deletion, please solve this simple operation:');
define('TEXT_GEO_VERIFDEL_OK',               'Confirm');
define('TEXT_GEO_VERIFDEL_CANCEL',           'Cancel');
 
// Target sentences — %s is replaced by the bold entity name
define('TEXT_GEO_VERIFDEL_TARGET_REGION',       'Delete the region %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_COMMUNE',      'Delete the town %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_REGIONHYDRO',  'Delete the hydrological region %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_RIVIERE',      'Delete the river %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_AQUIFERE',     'Delete the aquifer %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_TOURNEE',      'Delete the round %s ?');


// =============================================================================
// SETTINGS - QUALITY CODES
// =============================================================================

// -----------------------------------------------
// gestion_quality_data.php - page title, save button, tab

define('TEXT_QD_PAGE_TITLE',        'Quality code configuration');
define('TEXT_QD_BTN_SAVE',          'Save');
define('TEXT_QD_TAB_LABEL',         'Quality codes');

// URL save-confirmation message shown after page reload
define('TEXT_QD_SAVE_URL_OK',       'Quality codes have been saved successfully.');

// -----------------------------------------------
// form_qualitydata.php - table headers, new-entry row, dropdown default

define('TEXT_QD_TH_INIT',          'Code');
define('TEXT_QD_TH_NOM',           'Full name');
define('TEXT_QD_TH_INFO',          'Description');
define('TEXT_QD_TH_TYPE',          'Data type');
define('TEXT_QD_TH_COLOR',          'Color');
define('TEXT_QD_NEW_ENTRY',        'Add a quality code');
define('TEXT_QD_TYPE_ALL',         'All types');
define('TEXT_QD_BTN_DELETE',       'Delete');

// -----------------------------------------------
// process_delqualitydata.php - deletion feedback messages

define('TEXT_QD_DEL_OK',           'has been successfully deleted.');
define('TEXT_QD_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_QD_DEL_ERR_DATA',     'It is linked to at least one data record.');
define('TEXT_QD_DEL_ERR_NOTFOUND', 'The quality code does not exist.');

// -----------------------------------------------
// process_qualitydata_save.php - save feedback messages

define('TEXT_QD_SAVE_OK',          'Quality codes have been saved successfully.');
define('TEXT_QD_SAVE_ERR_WRITE',   'An error occurred while writing quality data to the database.');
define('TEXT_QD_SAVE_ERR_DETAIL',  'Error details: ');
define('TEXT_QD_SAVE_ERR_REQUEST', 'An error occurred while sending data to the server.');
define('TEXT_QD_SAVE_ERR_DUPLICATE','A quality code with the same label already exists and cannot be added again: ');
define('TEXT_QD_SAVE_ACTION_LOG',  'Quality codes saved');




// =============================================================================
// SETTINGS - TIME-SERIES TYPES & AXES
// =============================================================================

// -----------------------------------------------
// gestion_type_data.php - page title, save button, tabs

define('TEXT_TD_PAGE_TITLE', 'Time-series type and graph axis configuration');
define('TEXT_TD_BTN_SAVE',   'Save');
define('TEXT_TD_TAB_CHRON',  'Time-series');
define('TEXT_TD_TAB_AXES',   'Axes');

// -----------------------------------------------
// process_tab_axe.php - axis table headers, new-entry label

define('TEXT_TD_AXE_TH_NAME',   'Axis name');
define('TEXT_TD_AXE_TH_UNIT',   'Unit');
define('TEXT_TD_AXE_TH_ROUND',  'Rounding');
define('TEXT_TD_AXE_NEW',       'Add an axis');
define('TEXT_TD_AXE_NO_DATA',   'No data found');

// -----------------------------------------------
// process_delaxe.php - axis deletion messages

define('TEXT_TD_AXE_DEL_OK',          'has been successfully deleted.');
define('TEXT_TD_AXE_DEL_ERR_LINKED',  'could not be deleted.');
define('TEXT_TD_AXE_DEL_ERR_CHRON',   'It is linked to at least one time-series type.');
define('TEXT_TD_AXE_DEL_ERR_NOTFOUND','The axis does not exist.');

// -----------------------------------------------
// process_deltypedata.php - time-series type deletion messages

define('TEXT_TD_CHRON_DEL_OK',          'has been successfully deleted.');
define('TEXT_TD_CHRON_DEL_ERR_LINKED',  'could not be deleted.');
define('TEXT_TD_CHRON_DEL_ERR_DATA',    'It is linked to at least one data record.');
define('TEXT_TD_CHRON_DEL_ERR_NOTFOUND','The time-series type does not exist.');

// -----------------------------------------------
// form_typedata_chron.php - filter label

define('TEXT_TD_FILTER_LABEL', 'Select: ');

// -----------------------------------------------
// process_tab_typedata.php - table headers, new-entry labels, dropdown values

define('TEXT_TD_TH_ACRONYM',        'Acronym');
define('TEXT_TD_TH_NAME',           'Name');
define('TEXT_TD_TH_DATATYPE',       'Data type');
define('TEXT_TD_TH_AXIS',           'Axis');
define('TEXT_TD_TH_UNIT',           'Unit');
define('TEXT_TD_TH_ROUND',          'Rounding');
define('TEXT_TD_TH_TIMESCALE',      'Time scale');
define('TEXT_TD_TH_PROCESSING',     'Processing');
define('TEXT_TD_TH_GRAPHTYPE',      'Graph type');
define('TEXT_TD_TH_PERIOD_TRANSF',  'Period transf.');
define('TEXT_TD_TH_CHRON_TRANSF',   'Time-series transf.');
define('TEXT_TD_TH_RAWDATA', 'Raw data');

define('TEXT_TD_NEW_CHRON',         'Add a time-series type');

// Graph-type dropdown options
define('TEXT_TD_GRAPH_LINEAR',      'linear');
define('TEXT_TD_GRAPH_BAR',         'bar chart');

// Processing dropdown options
define('TEXT_TD_PROC_VALUE',        'value');
define('TEXT_TD_PROC_CUMUL',        'cumulative');

// Delete cell - shown when deletion is allowed; dash shown otherwise
define('TEXT_TD_BTN_DELETE',        'Delete');

// No-data fallback message
define('TEXT_TD_NO_DATA',           'No data found');

// -----------------------------------------------
// process_typedata_save.php - save feedback messages

// Duplicate-label errors
define('TEXT_TD_SAVE_ERR_DUP_CHRON', 'A time-series type with the same acronym already exists and cannot be added again: ');
define('TEXT_TD_SAVE_ERR_DUP_AXE',   'An axis with the same label already exists and cannot be added again: ');

// Success message (appended after all writes, even if some duplicates were blocked)
define('TEXT_TD_SAVE_OK',            'Time-series types and axes have been saved successfully.');

// Transaction error
define('TEXT_TD_SAVE_ERR_WRITE',     'An error occurred while writing time-series and axis data to the database.');
define('TEXT_TD_SAVE_ERR_DETAIL',    'Error details: ');

// Wrong request method
define('TEXT_TD_SAVE_ERR_REQUEST',   'An error occurred while sending data to the server.');

// Action log entry
define('TEXT_TD_SAVE_ACTION_LOG',    'Time-series type data saved');




// =============================================================================
// SETTINGS - GAUGING EQUIPMENT
// =============================================================================

// -----------------------------------------------
// Shared across all three equipment tabs

define('TEXT_EJ_BTN_DELETE',            'Delete');

// gestion eq jaugeage — shared delete confirmation popup
define('TEXT_EJ_DEL_CONFIRM_TITLE',  'Confirm deletion');
define('TEXT_EJ_DEL_CONFIRM_MSG',    'Do you confirm the deletion of this equipment? This action cannot be undone.');
define('TEXT_EJ_DEL_CONFIRM_OK',     'Delete');
define('TEXT_EJ_DEL_CONFIRM_CANCEL', 'Cancel');

// -----------------------------------------------
// form_eq_jge_moulinets.php - current meter tab

define('TEXT_EJ_MOUL_TH_NUM',           'Number');
define('TEXT_EJ_MOUL_TH_FABRICANT',     'Manufacturer');
define('TEXT_EJ_MOUL_TH_OBS',           'Comment');
define('TEXT_EJ_MOUL_NEW',              'Add a Flow tracker');

// -----------------------------------------------
// process_delmoulinet.php - current meter deletion messages

define('TEXT_EJ_MOUL_DEL_OK',           'has been successfully deleted.');
define('TEXT_EJ_MOUL_DEL_ERR_LINKED',   'could not be deleted.');
define('TEXT_EJ_MOUL_DEL_ERR_JGE',      'It is linked to at least one gauging record.');
define('TEXT_EJ_MOUL_DEL_ERR_NOTFOUND', 'The Flow tracker does not exist.');

// -----------------------------------------------
// form_eq_jge_helices.php - propeller tab

define('TEXT_EJ_HEL_TH_NUM',            'Number');
define('TEXT_EJ_HEL_TH_DIAM',           'Diameter');
define('TEXT_EJ_HEL_TH_PAS',            'Pitch');
define('TEXT_EJ_HEL_TH_L1',             'l1');
define('TEXT_EJ_HEL_TH_A1',             'a1');
define('TEXT_EJ_HEL_TH_B1',             'b1');
define('TEXT_EJ_HEL_TH_L2',             'l2');
define('TEXT_EJ_HEL_TH_A2',             'a2');
define('TEXT_EJ_HEL_TH_B2',             'b2');
define('TEXT_EJ_HEL_TH_A3',             'a3');
define('TEXT_EJ_HEL_TH_B3',             'b3');
define('TEXT_EJ_HEL_TH_FABRICANT',      'Manufacturer');
define('TEXT_EJ_HEL_TH_OBS',            'Comment');
define('TEXT_EJ_HEL_NEW',               'Add a propeller');

// gestion eq jaugeage — propeller equations popup
define('TEXT_EJ_HEL_TH_EQUATIONS', 'Equations');
define('TEXT_EJ_HEL_EQ_BTN',       'Equations');
define('TEXT_EJ_HEL_EQ_TITLE',     'Propeller velocity equations:');
define('TEXT_EJ_HEL_EQ_SUBTITLE',  'Velocity equations');
define('TEXT_EJ_HEL_EQ_RANGE_MID', '< n <=');
define('TEXT_EJ_HEL_EQ_OK',        'Validate');
define('TEXT_EJ_HEL_EQ_CANCEL',    'Cancel');
define('TEXT_EJ_HEL_EQ_CLOSE',     'Close');
define('TEXT_EJ_HEL_EQ_SAVE_REMINDER', 'Equations updated. Remember to click Save to store your changes.');


// -----------------------------------------------
// process_delhelice.php - propeller deletion messages

define('TEXT_EJ_HEL_DEL_OK',            'has been successfully deleted.');
define('TEXT_EJ_HEL_DEL_ERR_LINKED',    'could not be deleted.');
define('TEXT_EJ_HEL_DEL_ERR_JGE',       'It is linked to at least one gauging record.');
define('TEXT_EJ_HEL_DEL_ERR_NOTFOUND',  'The propeller does not exist.');

// -----------------------------------------------
// form_eq_jge_saumons.php - bomb (saumon) tab

define('TEXT_EJ_SAU_TH_NUM',            'No.');
define('TEXT_EJ_SAU_TH_TITRE',          'Title');
define('TEXT_EJ_SAU_TH_POIDS',          'Weight');
define('TEXT_EJ_SAU_TH_DIST_AXE',       'Axis dist.');
define('TEXT_EJ_SAU_TH_TAIR',           'Tair');
define('TEXT_EJ_SAU_TH_RDIST',          'Rdist');
define('TEXT_EJ_SAU_TH_FABRICANT',      'Manufacturer');
define('TEXT_EJ_SAU_TH_OBS',            'Comment');
define('TEXT_EJ_SAU_NEW',               'Add a bomb');

// -----------------------------------------------
// process_delsaumon.php - bomb deletion messages

define('TEXT_EJ_SAU_DEL_OK',            'has been successfully deleted.');
define('TEXT_EJ_SAU_DEL_ERR_LINKED',    'could not be deleted.');
define('TEXT_EJ_SAU_DEL_ERR_JGE',       'It is linked to at least one gauging record.');
define('TEXT_EJ_SAU_DEL_ERR_NOTFOUND',  'The bomb does not exist.');

// -----------------------------------------------
// process_dataeqjge_save.php - save feedback messages

define('TEXT_EJ_SAVE_OK',               'Gauging equipment (Flow tracker, propeller and bomb) has been saved successfully.');
define('TEXT_EJ_SAVE_ERR_WRITE',        'An error occurred while writing gauging equipment data to the database.');
define('TEXT_EJ_SAVE_ERR_DETAIL',       'Error details: ');
define('TEXT_EJ_SAVE_ERR_REQUEST',      'An error occurred while sending data to the server.');
define('TEXT_EJ_SAVE_ACTION_LOG',       'Gauging equipment saved');

// -----------------------------------------------
// gestion_eq_jaugeage.php - page title, save button, tab labels, URL save message

define('TEXT_EJ_PAGE_TITLE',    'Gauging equipment configuration');
define('TEXT_EJ_BTN_SAVE',      'Save');
define('TEXT_EJ_TAB_HELICES',   'Propeller');
define('TEXT_EJ_TAB_MOULINETS', 'Flow tracker');
define('TEXT_EJ_TAB_SAUMONS',   'Bomb');
define('TEXT_EJ_SAVE_URL_OK',   '<span style=\'font-size:16px;\'>Gauging equipment data has been saved successfully.</span>');





// =============================================================================
// SETTINGS - PARAMETER EXPORT / IMPORT
// =============================================================================

// -----------------------------------------------
// export_param.php - page title, checkboxes, button

define('TEXT_EX_PAGE_TITLE',        'Platform parameter export / import');
define('TEXT_EX_CHK_ZONEGEO',       'Geographic zones (Geographic regions / Communes / Hydro regions / Rivers)');
define('TEXT_EX_CHK_TYPECHRON',     'Time-series');
define('TEXT_EX_CHK_STNATURE',      'Measurement station types (hydrometric / rain gauge / piezometric)');
define('TEXT_EX_CHK_CODEQUAL',      'Quality codes');
define('TEXT_EX_CHK_EQJGE',         'Gauging equipment (Propeller / Current meter / Bomb)');
define('TEXT_EX_BTN_EXPORT',        'Export data');
define('TEXT_EX_WAIT_FILE',         'Creating file ...');

// -----------------------------------------------
// export_param.php - JS inline feedback messages

define('TEXT_EX_ERR_NO_PARAM',      'No parameter was selected - the file cannot be created.');
define('TEXT_EX_ERR_GENERATE',      'An error occurred while generating the file.');
define('TEXT_EX_ERR_SERVER',        'An error occurred while contacting the server.');

// -----------------------------------------------
// process_download.php - file-not-found message

define('TEXT_EX_FILE_NOT_FOUND',    'The requested file does not exist.');







// =============================================================================
// PLATFORM ACTIVITY LOGS
// =============================================================================

// -----------------------------------------------
// Shared column headers

define('TEXT_LS_COL_LOGIN',         'Login');
define('TEXT_LS_COL_NAME',          'First name / Last name');
define('TEXT_LS_COL_DATE',          'Date');
define('TEXT_LS_COL_DETAILS',       'Details');
define('TEXT_LS_COL_CONSULT',       'View');
define('TEXT_LS_COL_TYPE',          'Type');
define('TEXT_LS_COL_STATION',       'Station');

// -----------------------------------------------
// Shared filter labels

define('TEXT_LS_FILTER_USER',       'User');
define('TEXT_LS_FILTER_ACTION',     'Action');
define('TEXT_LS_FILTER_DELAY',      'Delay');
define('TEXT_LS_FILTER_STATION',    'Station');

// -----------------------------------------------
// Shared delay prefix labels (concatenated with the period name from DB)

define('TEXT_LS_DELAY_LESS',        'less than ');
define('TEXT_LS_DELAY_MORE',        'more than ');

// -----------------------------------------------
// Shared empty-result message pattern

define('TEXT_LS_NO_RESULT',         'No result found.');

// -----------------------------------------------
// list_actions.php

define('TEXT_LS_ACT_PAGE_TITLE',    'Platform activity log - HydroPacifique');
define('TEXT_LS_ACT_COL_DELAY',     'Delay (days)');
define('TEXT_LS_ACT_COL_DATE',      'Action date');
define('TEXT_LS_ACT_COL_DETAIL',    'Detail');
define('TEXT_LS_ACT_NB_ACTIONS',    'Number of actions: ');
define('TEXT_LS_ACT_NO_RESULT',     'No action was found.');

// -----------------------------------------------
// list_exports.php

define('TEXT_LS_EXP_PAGE_TITLE',    'Recent data exports - last 24 months');
define('TEXT_LS_EXP_AVAIL_INFO',    'Data files are available for download for 1 month.');
define('TEXT_LS_EXP_COL_FILE',      'File to download');
define('TEXT_LS_EXP_NO_RESULT',     'No export was found.');

// -----------------------------------------------
// list_imports.php

define('TEXT_LS_IMP_PAGE_TITLE',    'Recent data imports - last 24 months');
define('TEXT_LS_IMP_COL_FILE',      'Imported file');
define('TEXT_LS_IMP_COL_CHRON',     'Time series');
define('TEXT_LS_IMP_COL_NBDATA',    'Nb data');
define('TEXT_LS_IMP_COL_DATE_S',    'Start date');
define('TEXT_LS_IMP_COL_DATE_E',    'End date');
define('TEXT_LS_IMP_NO_RESULT',     'No import was found.');

// -----------------------------------------------
// corrections.php

define('TEXT_LS_COR_PAGE_TITLE',    'Time-series correction log');
define('TEXT_LS_COR_SORT_LABEL',    'SORT BY');
define('TEXT_LS_COR_SORT_DATE',     'Correction date');
define('TEXT_LS_COR_SORT_NAME',     'Station name');
define('TEXT_LS_COR_SORT_CODE',     'Station code');
define('TEXT_LS_COR_SORT_TYPE',     'Data type');
define('TEXT_LS_COR_ORDER_ASC',     'Ascending');
define('TEXT_LS_COR_ORDER_DESC',    'Descending');
define('TEXT_LS_COR_NB_CORR',       'Number of corrections: ');
define('TEXT_LS_COR_COL_CODE',      'Station code');
define('TEXT_LS_COR_COL_NAME',      'Station name');
define('TEXT_LS_COR_COL_TYPE',      'Data type');
define('TEXT_LS_COR_NO_RESULT',     'No correction was found.');



// =============================================================================
// ADMINISTRATION MODULE
// =============================================================================

define('TEXT_MYACC_TITLE',          'My account');
define('TEXT_MYACC_LOCKED',         'not editable');
define('TEXT_MYACC_SAVE_OK',        'Your profile has been updated.');
define('TEXT_MYACC_ERR_UNEXPECTED', 'Unexpected server error. Please try again.');
define('TEXT_MYACC_ERR_HTTP',       'Server error');
define('TEXT_MYACC_ERR_NO_SESSION', 'Your session has expired. Please sign in again.');


// -----------------------------------------------
// gestion.php - admin home page

define('TEXT_US_APP_SETTINGS',          'Application settings');
define('TEXT_US_MENU_USERS',            'Users');
define('TEXT_US_MENU_USER_RIGHTS',      'Permissions & settings');
define('TEXT_US_MENU_USER_NEW',         'Create a new user');
define('TEXT_US_MENU_CONFIG',           'Configuration');
define('TEXT_US_MENU_PLATEFORM',           'Platform');
define('TEXT_US_MENU_SERVICE',          'Services (owner data)');
define('TEXT_US_MENU_TYPE_MESURE',      'Measurement type');

// -----------------------------------------------
// list_users.php - user list page

define('TEXT_US_LIST_PAGE_TITLE',       'User management & permissions');
define('TEXT_US_LIST_COL_FROM',        'Department');
define('TEXT_US_LIST_COL_LOGIN',        'Login');
define('TEXT_US_LIST_COL_NOM',          'Last name');
define('TEXT_US_LIST_COL_PRENOM',       'First name');
define('TEXT_US_LIST_COL_EMAIL',       'Email');
define('TEXT_US_LIST_COL_INFO',       'Info');
define('TEXT_US_LIST_COL_DATE_CREATE',  'Creation date');
define('TEXT_US_LIST_COL_LAST_LOG',     'Last log date');
define('TEXT_US_LIST_COL_NB_LOG',       'Nb Log');
define('TEXT_US_LIST_COL_ACTIVE',       'Active');
define('TEXT_US_LIST_BTN_DELETE',       'Delete');

// -----------------------------------------------
// modif_user.php - user edit page

define('TEXT_US_EDIT_TITLE_NEW',        'New user');
define('TEXT_US_EDIT_TITLE_PREFIX',     'User : ');
define('TEXT_US_EDIT_TAB_INFO',         'Information');
define('TEXT_US_EDIT_TAB_RIGHTS',       'Access rights');

// -----------------------------------------------
// form_user_1.php - user info tab

define('TEXT_US_F1_LOGIN_LABEL',        'Access login');
define('TEXT_US_F1_LOGIN_HINT',         'This field must not contain spaces, accented characters, or special characters');
define('TEXT_US_F1_NOM_LABEL',          'Last name');
define('TEXT_US_F1_PRENOM_LABEL',       'First name');
define('TEXT_US_F1_MAIL_LABEL',       'Email');
define('TEXT_US_F1_INFO_LABEL',         'Additional information');

define('TEXT_US_F1_PASS_GENERATE',      'Generate a new password');
define('TEXT_US_F1_PASS_COPY',          'Copy this password.<br> For security reasons, the password is encrypted. You will not be able to access it again.');

define('TEXT_US_F1_SEND_MAIL_LABEL', 'Send Connect Email');

define('TEXT_US_F1_LANG_LABEL', 'Language');

// -----------------------------------------------
// form_user_2.php - user rights tab

define('TEXT_US_F2_RIGHTS_TITLE',       'Rights and permissions management');
define('TEXT_US_F2_RIGHT_DATA',         'Data management');
define('TEXT_US_F2_RIGHT_DATA_EXPERT',  'Data management - Expert');
define('TEXT_US_F2_RIGHT_PARAM',        'Settings');
define('TEXT_US_F2_RIGHT_CONFIG',       'Application configuration');

// -----------------------------------------------
// process_user_save.php - user save processor

define('TEXT_US_CTRL_ERR_LOGIN_EMPTY',  'The Login field is required.');
define('TEXT_US_CTRL_ERR_MAIL_EMPTY',  'The Email field is required.');
define('TEXT_US_CTRL_ERR_LOGIN_DUP',    'Another user already has this login. Please choose another.');
define('TEXT_US_CTRL_ERR_LOGIN_CHARS',  'The login must not contain spaces, accented characters, or special characters.');
define('TEXT_US_CTRL_ERR_MAIL_INVALID', 'Please enter a valid email address.');
define('TEXT_US_CTRL_MSG_CREATED',      'The user record has been created.');
define('TEXT_US_CTRL_MSG_UPDATED',      'The user record has been updated.');


// -----------------------------------------------
// process_user_sendmail.php - user send mail

define('TEXT_US_WELCOME_MAIL_TITLE',     'Your account has been created');
define('TEXT_US_WELCOME_MAIL_BODY',      'Your account is ready. Click the link below to set your password.');
define('TEXT_US_WELCOME_MAIL_BTN_LINK',  'Set my password');
define('TEXT_US_WELCOME_MAIL_SUBJECT',   'Your account on %s');
define('TEXT_US_WELCOME_MAIL_OK',        'Welcome email sent successfully.');
define('TEXT_US_WELCOME_ERR_NOT_FOUND',  'User not found or email address missing.');

// -----------------------------------------------
// ctrl_user_active.php - bulk active/inactive save

define('TEXT_US_ACTIVE_MSG_OK',         'The user list has been updated.');


// -----------------------------------------------
// suppr_user.php - user deletion

define('TEXT_US_DEL_ERR_HAS_LOGS',      'This record cannot be deleted - the user has already performed actions on the platform.');
define('TEXT_US_DEL_ERR_HAS_LOGS2',     'Access can only be deactivated.');
define('TEXT_US_DEL_OK',                'The user record for "%s" has been deleted.');
define('TEXT_US_DEL_ERR_NOT_FOUND',     'This user record does not exist and cannot be deleted.');

// ============================================================
// Plateform configuration page
// ============================================================

// ---- Page header ----
define('TEXT_PF_PAGE_TITLE',             'Plateform Configuration');
define('TEXT_PF_LABEL',                  'General');
define('TEXT_PF_SAVE',                   'Save');

// ---- Block 1 — Territoire identification ----
define('TEXT_PF_F1_INIT',                'Initials');
define('TEXT_PF_F1_INIT_HINT',           'Short territoire code (e.g.: NC, PF, WF)');
define('TEXT_PF_F1_NOM',                 'Name');

// ---- Block 2 — Regional settings ----
define('TEXT_PF_F1_THEME',               'Region theme');
define('TEXT_PF_F1_REGION_DEFAULT',      'Default region');
define('TEXT_PF_F1_SERVICE_HYDRO',       'Hydro service');

// ---- Block 3 — Locale and language ----
define('TEXT_PF_F1_TIMEZONE',            'Timezone');
define('TEXT_PF_F1_TIMEZONE_HINT',       'PHP format (e.g.: Pacific/Tahiti, Pacific/Noumea)');
define('TEXT_PF_F1_LANG',                'Language');

// ---- Block 4 — Map configuration ----
define('TEXT_PF_F1_MAP_LONG',            'Longitude');
define('TEXT_PF_F1_MAP_LAT',             'Latitude');
define('TEXT_PF_F1_MAP_ZOOM',            'Default zoom');
define('TEXT_PF_F1_MAP_MIN_ZOOM',        'Minimum zoom');

// ---- Save feedback ----
define('TEXT_PF_SAVE_OK',                'Configuration saved successfully.');
define('TEXT_PF_SAVE_ACTION_LOG',        'Plateform configuration updated');

// ---- Save errors ----
define('TEXT_PF_SAVE_ERR_THEME',         'Region theme is required.');
define('TEXT_PF_SAVE_ERR_SERVICE_HYDRO', 'Hydro service is required.');
define('TEXT_PF_SAVE_ERR_REGION',        'The selected default region does not belong to this territoire.');
define('TEXT_PF_SAVE_ERR_TIMEZONE',      'Invalid timezone');
define('TEXT_PF_SAVE_ERR_LANG',          'Unsupported language');
define('TEXT_PF_SAVE_ERR_LONG',          'Longitude must be between -180 and 180.');
define('TEXT_PF_SAVE_ERR_LAT',           'Latitude must be between -90 and 90.');
define('TEXT_PF_SAVE_ERR_ZOOM',          'Default zoom must be between 2 and 16.');
define('TEXT_PF_SAVE_ERR_MIN_ZOOM',      'Minimum zoom must be between 2 and 5.');
define('TEXT_PF_SAVE_ERR_ZOOM_ORDER',    'Minimum zoom cannot be greater than default zoom.');
define('TEXT_PF_SAVE_ERR_WRITE',         'Error while saving.');
define('TEXT_PF_SAVE_ERR_DETAIL',        'Detail: ');
define('TEXT_PF_SAVE_ERR_REQUEST',       'Invalid request method.');

// -----------------------------------------------
// gestion_service.php - service (ownder data) config page

define('TEXT_SV_PAGE_TITLE',       'Departments (Owner Data)');
define('TEXT_SV_TAB_LABEL',        'Information');
define('TEXT_SV_SAVE',        'Save');

define('TEXT_SV_COL_NAME',        'Department');
define('TEXT_SV_COL_DESC',        'Description');
define('TEXT_SV_COL_LOCAL',        'Local');
define('TEXT_SV_COL_CONTACT',        'Contact');
define('TEXT_SV_COL_CONTACT_MAIL',        'Contact Email');
define('TEXT_SV_NEW_ENTRY',        'New Department');

define('TEXT_SV_DEL_CONFIRM_TITLE','Delete a service');
define('TEXT_SV_DEL_CONFIRM_MSG','You are about to permanently delete the following service.<br>This action cannot be undone.');
define('TEXT_SV_DEL_CHALLENGE_HINT','Solve the operation below to confirm:');

define('TEXT_SV_SAVE_ERR_DUP_NAME',        'The save failed, the service name is already in use.');
define('TEXT_SV_SAVE_ERR_DUP_MAIL',        'The save failed, email address is already in use.');
define('TEXT_SV_SAVE_ERR_MAIL',        'Email address is not in a valid format.');
define('TEXT_SV_SAVE_ACTION_LOG',        'Department Settings Registration');
define('TEXT_SV_SAVE_OK',               'Departments have been successfully saved.');
define('TEXT_SV_SAVE_ERR_WRITE',        'An error occurred while writing data to the database.');
define('TEXT_SV_SAVE_ERR_DETAIL',       'Error details: ');
define('TEXT_SV_SAVE_ERR_REQUEST',      'Invalid request.');

define('TEXT_SV_DEL_OK',        'Department "%s" has been deleted.');
define('TEXT_SV_DEL_ERR_LINKED',        'The Department "%s" cannot be deleted because it is linked to at least one Station.');
define('TEXT_SV_DEL_ERR_NOT_FOUND',        'This Department does not exist and cannot be deleted.');


// -----------------------------------------------
// gestion_type.php - measurement type config page

define('TEXT_US_TYPE_PAGE_TITLE',       'Data type configuration (Rain, Flow, ...)');
define('TEXT_US_TYPE_TAB_LABEL',        'Data type');
define('TEXT_US_TYPE_SAVE',        'Save');

// -----------------------------------------------
// form_type_1.php - measurement type table

define('TEXT_US_FT_COL_NAME',           'Name');
define('TEXT_US_FT_COL_MESURE',         'Type');
define('TEXT_US_FT_COL_ORDER',          'Order');
define('TEXT_US_FT_COL_ACTIVE',         'Active');
define('TEXT_US_FT_COL_COLOR_BORDER',   'Border colour');
define('TEXT_US_FT_COL_COLOR_BG',       'Background colour');
define('TEXT_US_FT_COL_GRAPH',          'Graph type');
define('TEXT_US_FT_NEW_ENTRY',          'Add a new entry');
define('TEXT_US_FT_OPT_PONCTUEL',       'Punctual');
define('TEXT_US_FT_OPT_CUMUL',          'Cumulative');
define('TEXT_US_FT_OPT_LINES',          'lines');
define('TEXT_US_FT_OPT_BAR',            'bar');
define('TEXT_US_FT_BTN_DELETE',         'Delete');

// -----------------------------------------------
// ctrl_type.php - measurement type save processor

define('TEXT_US_TYPE_MSG_UPDATED',      'The measurement type list has been updated.');
define('TEXT_US_TYPE_MSG_CREATED',      'The new measurement type has been saved.');

define('TEXT_US_TYPE_DEL_CONFIRM_TITLE','Delete a measurement type');
define('TEXT_US_TYPE_DEL_CONFIRM_MSG','You are about to permanently delete the following measurement type.<br>This action cannot be undone.');
define('TEXT_US_TYPE_DEL_CHALLENGE_HINT','Solve the operation below to confirm:');


// -----------------------------------------------
// suppr_type.php - measurement type deletion

define('TEXT_US_TYPE_DEL_OK',           'The data type "%s" has been deleted.');
define('TEXT_US_TYPE_DEL_ERR_LINKED',   'The data type "%s" cannot be deleted because it is linked to at least one data series.');
define('TEXT_US_TYPE_DEL_ERR_NOT_FOUND', 'This data type does not exist and cannot be deleted.');








// =============================================================================
// DIAGRAPHY MODULE (PIEZOMETRIC)
// =============================================================================

// -----------------------------------------------
// data_diag_piezo.php - page title & filter panel

define('TEXT_DG_PAGE_TITLE',        'Well logs - Groundwater');
define('TEXT_DG_BTN_SHOW',          'Show graph');
define('TEXT_DG_SORT_LABEL',        'Sort by');
define('TEXT_DG_SORT_NAME',         'Name');
define('TEXT_DG_SORT_CODE',         'Code');
define('TEXT_DG_ORDER_ASC',         'Ascending');
define('TEXT_DG_ORDER_DESC',        'Descending');
define('TEXT_DG_NB_STATIONS',       'Number of stations: ');

// -----------------------------------------------
// data_diag_piezo.php - table column headers & tooltips

define('TEXT_DG_COL_STATUS',        'Status');
define('TEXT_DG_COL_STATUS_TITLE',  'Active or Historical (Closed)');
define('TEXT_DG_COL_SUIVI',         'Monitoring');
define('TEXT_DG_COL_SUIVI_TITLE',   'Continuous or Discrete');
define('TEXT_DG_COL_CODE',          'Code');
define('TEXT_DG_COL_NAME',          'Name');
define('TEXT_DG_COL_COMMUNE',       'Town / Village');
define('TEXT_DG_COL_NB_DIAG',       'No. diag.');
define('TEXT_DG_COL_NB_DIAG_TITLE', 'Number of diagraphies');
define('TEXT_DG_COL_LAST_DIAG',     'Last Diag.');
define('TEXT_DG_COL_LAST_DIAG_TITLE', 'Date of the last diag.');
define('TEXT_DG_COL_SELECT',        'Select +/-');
define('TEXT_DG_COL_SELECT_TITLE',  'Select all diag.');

// -----------------------------------------------
// data_diag_piezo.php - station status icon tooltips

define('TEXT_DG_STATUS_ACTIVE',     'Active');
define('TEXT_DG_STATUS_CLOSED',     'Historical (Closed)');
define('TEXT_DG_SUIVI_CONTINU',     'Continuous measurements');
define('TEXT_DG_SUIVI_PONCTUEL',    'Spot measurements');

// -----------------------------------------------
// data_diag_piezo.php - empty result & JS inline messages

define('TEXT_DG_NO_STATION',        'No station was found.');
define('TEXT_DG_ERR_NO_DIAG',       'No diagraphy was selected - the chart cannot be generated.');

// -----------------------------------------------
// block_diag.php - popup panel labels

define('TEXT_DG_POPUP_TITLE',       'Compared Well logs');
define('TEXT_DG_POPUP_CLOSE',       'Close');
define('TEXT_DG_LIST_TITLE',        'Well logs list');
define('TEXT_DG_BTN_REFRESH',       'Refresh chart');
define('TEXT_DG_LOADING',           'Loading...');

// -----------------------------------------------
// process_diag_graph.php - Plotly chart axis labels & hover template

define('TEXT_DG_AXIS_X',            'Conductivity');
define('TEXT_DG_AXIS_Y',            'Depth');
define('TEXT_DG_HOVER_DATE', '<b>Profile date</b>: ');
define('TEXT_DG_HOVER_COND',  '<b>Conductivity</b>: %{x:.0f} ');
define('TEXT_DG_HOVER_PROF',  '<b>Depth</b>: %{y:.2f} ');
define('TEXT_DG_HOVER_TEMP',  '<b>Temperature</b>: %{customdata[0]} ');
define('TEXT_DG_HOVER_OBS',   '<b>Comment</b>: %{customdata[1]}');

define('TEXT_DG_UNIT_COND', 'µS/cm');
define('TEXT_DG_UNIT_PROF', 'm');
define('TEXT_DG_UNIT_TEMP', '°C');

// Diag — edit mode (block_diag.php / data_diag_piezo.php)
define('TEXT_DG_NEW_RA_LINK', 'Create a new field report for this station');
define('TEXT_DG_BTN_EDIT',           'Edit');
define('TEXT_DG_BTN_CANCEL_EDIT',    'Cancel edit');
define('TEXT_DG_EDIT_EDITING',       'Editing');
define('TEXT_DG_EDIT_TARGET',        'Target well log');
define('TEXT_DG_EDIT_NO_DATA',       'No well log to edit (none checked).');
define('TEXT_DG_EDIT_CHECK_LOCK',    'Cannot uncheck the well log currently being edited. Save or cancel first.');
define('TEXT_DG_EDIT_SWITCH_BLOCKED','Unsaved changes — save or cancel before switching well log.');
define('TEXT_DG_EDIT_MIN_POINTS',    'A well log must keep at least 1 point.');
define('TEXT_DG_EDIT_CANCEL_CONFIRM','Discard your unsaved changes?');
define('TEXT_DG_EDIT_CONFIRM_TITLE', 'Confirm edit');
define('TEXT_DG_EDIT_CONFIRM_MSG',   'You are about to replace all points of this well log. This action cannot be undone.');
define('TEXT_DG_EDIT_SAVE_ERR',      'Save error.');

// Edit hints under the chart
define('TEXT_DG_EDIT_HINT_DRAG', 'Left-click on a point: drag to move');
define('TEXT_DG_EDIT_HINT_RDEL', 'Right-click on a point: remove');
define('TEXT_DG_EDIT_HINT_RADD', 'Right-click on empty area: add a point');

define('TEXT_DG_DEL_TITLE',           'Delete this well log');
define('TEXT_DG_DEL_CONFIRM_TITLE',  'Confirm delete');
define('TEXT_DG_DEL_CONFIRM_MSG',    'You are about to permanently delete all points of this well log. This action cannot be undone.');
define('TEXT_DG_DEL_BTN_CONFIRM',    'Delete');
define('TEXT_DG_DEL_ERR',            'Delete error.');






// =============================================================================
// RATING CURVE MODULE (ETL)
// =============================================================================

// -----------------------------------------------
// modif_etl.php - page-level labels

define('TEXT_ET_TIMELINE_TITLE', 'Rating curves timeline');
define('TEXT_ET_TIMELINE_HINT',  'Click a bar to select / deselect');
define('TEXT_ET_TITLE',                 'Rating Curve Q=f(H)');
define('TEXT_ET_TITLE_STATION',                 '- Hydrometric Station: ');
define('TEXT_ET_ERR_STATION',           'The station could not be identified.');
define('TEXT_ET_LIST_TITLE',            'Rating Curves');
define('TEXT_ET_BTN_REFRESH',           'Refresh chart');
define('TEXT_ET_LOADING',               'Loading...');
define('TEXT_ET_BTN_NEW',               'New');
define('TEXT_ET_BTN_NEW_TITLE',         'Create a new Rating Curve');
define('TEXT_ET_BTN_MODIF',             'Edit');
define('TEXT_ET_BTN_MODIF_TITLE',       'Edit one of the selected RCs');
define('TEXT_ET_BTN_DUPLIC',            'Duplicate');
define('TEXT_ET_BTN_DUPLIC_TITLE',      'Duplicate one of the selected RCs');
define('TEXT_ET_BTN_DEL',               'Delete');
define('TEXT_ET_BTN_DEL_TITLE',         'Delete one of the selected RCs');
define('TEXT_ET_BTN_DECIMAL_PLUS',      'Add a decimal place');
define('TEXT_ET_BTN_DECIMAL_MINUS',     'Remove a decimal place');
define('TEXT_ET_BTN_ADJUST',            'Adjust scale');
define('TEXT_ET_OPT_SWAP', 'Swap axes');
define('TEXT_ET_COORD',               'Coordinates');
define('TEXT_ET_COORD_HMIN',   'Height min');
define('TEXT_ET_COORD_HMAX',   'Height max');
define('TEXT_ET_COORD_QMIN',   'Flow min');
define('TEXT_ET_COORD_QMAX',   'Flow max');
define('TEXT_ET_COORD_UNIT_H', 'cm');
define('TEXT_ET_COORD_UNIT_Q', 'm³/s');
define('TEXT_ET_TOOLTIP_DATE', 'Date :');
define('TEXT_ET_TOOLTIP_H',    'Height :');
define('TEXT_ET_TOOLTIP_Q',    'Flow :');

// ===== ETL — Chart series labels =====
define('TEXT_ET_LABEL_ETL_REF', 'Rating Curve - ref:');
define('TEXT_ET_LABEL_JGE_REF', 'Gauging - ref:');
define('TEXT_ET_LABEL_JGE',     'Gauging');


// -----------------------------------------------
// process_etl_tab.php - ETL list table

define('TEXT_ET_TAB_COL_REF',           'Ref.');
define('TEXT_ET_TAB_COL_DATE_START',    'Start date');
define('TEXT_ET_TAB_COL_DATE_END',      'End date');
define('TEXT_ET_TAB_COL_SELECT',        'Select');
define('TEXT_ET_TAB_NO_DATA',           'No data was found.');

// -----------------------------------------------
// Shared popup labels (block_etl_*.php)

define('TEXT_ET_POPUP_ETL_CURVE',       'Rating Curve (RC)');
define('TEXT_ET_POPUP_DATE_FMT',        'Date (dd-mm-yyyy)');
define('TEXT_ET_POPUP_TIME_FMT',        'Time (hh:mm:ss)');
define('TEXT_ET_POPUP_PERIOD_START',    'Period start');
define('TEXT_ET_POPUP_PERIOD_END',      'Period end');

define('TEXT_ET_SG_OPEN_HINT', 'Shift+Click to open SG in new tab');

// -----------------------------------------------
// block_etl_delete.php

define('TEXT_ET_DEL_TITLE',             'Delete an RC');

// -----------------------------------------------
// block_etl_modif.php

define('TEXT_ET_MODIF_TITLE',           'Edit RC validity period');

// -----------------------------------------------
// block_etl_new.php

define('TEXT_ET_NEW_TITLE',             'Create a new Rating Curve');
define('TEXT_ET_NEW_CURVE_TYPE',        'Curve type');
define('TEXT_ET_NEW_H0_LABEL',          'Stage parameter (zero flow)');
define('TEXT_ET_NEW_H0_AUTO',        "Optimise H₀ automatically");
define('TEXT_ET_NEW_H0_AUTO_RESULT', "optimised to");
define('TEXT_ET_NEW_LOGLOG_AXES',    "Log-log axes");  
define('TEXT_ET_NEW_EQ_1',              'Q = 10^b * H^a');
define('TEXT_ET_NEW_EQ_2',              'Q = a * H + b');
define('TEXT_ET_NEW_EQ_3',              'Q = log(H)');
define('TEXT_ET_NEW_DENSITY',           'Point density');
define('TEXT_ET_NEW_STEP1',          '1. Analysis period');
define('TEXT_ET_NEW_STEP2',          '2. Regression model');
define('TEXT_ET_NEW_STEP3',          '3. Regression result');
define('TEXT_ET_NEW_STEP4',          '4. Validity range (H)');
define('TEXT_ET_NEW_PREVIEW_TITLE',  'Preview');
define('TEXT_ET_NEW_DISABLED_HINT',  'Available after step B');
define('TEXT_BTN_CANCEL',            'Cancel');
define('TEXT_ET_NEW_MODEL_POWER',      'Power law');
define('TEXT_ET_NEW_MODEL_POLY',       'Polynomial');
define('TEXT_ET_NEW_MODEL_LINEAR', 'Linear');
define('TEXT_ET_NEW_BORNE_INF',        'Min');
define('TEXT_ET_NEW_BORNE_SUP',        'Max');
define('TEXT_ET_NEW_INTERVAL',         'Step');
define('TEXT_ET_NEW_REGRESSION_HINT',  'Adjust period and model to see the result.');

define('TEXT_ET_NEW_ADD_REGRESSION',      'Add regression');
define('TEXT_ET_NEW_ADD_REGRESSION_HINT', 'Review the gauging points first, then add a regression');

define('TEXT_ET_NEW_SHOW_PI', 'Show 95% prediction interval');
define('TEXT_ET_NEW_PI_BAND', '95% PI');

define('TEXT_ET_NEW_JGE_EXCLUDED',          'excluded');
define('TEXT_ET_NEW_JGE_EXCLUDED_LABEL',    'Excluded gauging');
define('TEXT_ET_NEW_JGE_CLICK_HINT',        'Click to exclude from regression');
define('TEXT_ET_NEW_JGE_REINCLUDE_HINT',    'Click to re-include in regression');

define('TEXT_ET_NEW_JGE_FOUND',         'gauging points found in this period.');
define('TEXT_ET_NEW_JGE_FEW',           'gauging points — at least 2 are needed to fit a curve.');
define('TEXT_ET_NEW_JGE_NONE',          'gauging point — at least 2 are needed to fit a curve.');
define('TEXT_ET_NEW_JGE_NONE_PERIOD',   'No gauging points in this period.');
define('TEXT_ET_NEW_DATE_HINT',         'Enter two dates in dd-mm-yyyy format.');
define('TEXT_ET_NEW_EQ_LABEL',          'Equation:');
define('TEXT_ET_NEW_R2_LABEL',          'R²:');
define('TEXT_ET_NEW_MANUAL_EDIT',       'Curve adjusted manually — regression is a starting guide.');
define('TEXT_ET_NEW_REG_FAILED',        'Regression failed');
define('TEXT_ET_NEW_REG_NEED_PTS',      'At least 2 points are needed to fit a curve.');
define('TEXT_ET_NEW_PLOTLY_MISSING',    'Plotly unavailable — the chart cannot be displayed.');
define('TEXT_ET_NEW_PT_TITLE',          'Edit a point');
define('TEXT_ET_NEW_CURVE_LABEL',       'New curve');
define('TEXT_ET_NEW_CURVE_HINT',        'Click to edit · drag to move');
define('TEXT_BTN_SAVE',                 'Save');


define('TEXT_ET_NEW_STEP5',                'Period conflicts');
define('TEXT_ET_NEW_CONFLICTS_HINT',       'Conflicts will be detected on save.');
define('TEXT_ET_NEW_CONFLICTS_NONE',       'No overlap detected on this period.');
define('TEXT_ET_NEW_CONFLICT_ACTION',      'Action');
define('TEXT_ET_NEW_CONFLICT_DELETE',     'Delete (fully included in the new one)');
define('TEXT_ET_NEW_CONFLICT_TRUNC_R',    'Truncate end (will stop at the new RC start)');
define('TEXT_ET_NEW_CONFLICT_TRUNC_L',    'Truncate start (will resume after the new RC ends)');
define('TEXT_ET_NEW_CONFLICT_BLOCKING',   'Blocking conflict: new fully inside this one');
define('TEXT_ET_NEW_BLOCKING_TITLE',       'Period not allowed');
define('TEXT_ET_NEW_BLOCKING_MSG',         'The chosen period falls entirely inside an existing RC. Adjust the period before saving.');
define('TEXT_ET_NEW_SAVE_CONFIRM_TITLE',   'Confirm creation');
define('TEXT_ET_NEW_SAVE_CONFIRM_MSG',     'You are about to create a new RC. This action cannot be undone.');
define('TEXT_ET_NEW_CHALLENGE_CONFLICTS',  'Existing RC that will be changed:');
define('TEXT_ET_NEW_CHALLENGE_HINT',       'To confirm, solve this:');
define('TEXT_ET_NEW_CHALLENGE_NEW_PERIOD', 'New rating curve period:');
define('TEXT_ET_NEW_CHALLENGE_BEFORE',     'Before');
define('TEXT_ET_NEW_CHALLENGE_AFTER',      'After');
define('TEXT_ET_NEW_CHALLENGE_DELETED',    'Deleted');
define('TEXT_ET_NEW_SAVE_ERR',             'Save error.');
define('TEXT_ET_NEW_CONCURRENT_TITLE',     'Unresolved conflict');
define('TEXT_ET_NEW_CONCURRENT_MSG',       'Another RC was created or modified during your edit. Please review the RC list and try again.');

define('TEXT_ET_NEW_PERIOD_CHANGED_TITLE',
    'Period changed');
define('TEXT_ET_NEW_PERIOD_CHANGED_MSG',
    'The analysis period has changed. Do you want to recompute the regression on the new gauging set? <br><br>'
    . '<b>Yes</b>: refit the curve and drop any manual edits.<br>'
    . '<b>Cancel</b>: keep the current curve as-is.');

define('TEXT_ET_NEW_CONFIRM_TITLE',   'Confirmation required');
define('TEXT_ET_NEW_CONFIRM_OK',      'Continue anyway');
define('TEXT_ET_NEW_CONFIRM_DISCARD',
    'You have manually adjusted the curve (points or constant). '
  . 'Changing this parameter will recompute the regression and discard your edits.');




// -----------------------------------------------
// process_etl_graph.php - Plotly axis labels & hover templates

define('TEXT_ET_AXIS_H',                'Height (cm)');
define('TEXT_ET_AXIS_Q',                'Flow rate (m<sup>3</sup>/s)');
define('TEXT_ET_HOVER_DATE',            '<b>Date</b>: %{customdata}<br>');
define('TEXT_ET_HOVER_H',               '<b>Height</b>: %{x:.1f} cm<br>');
define('TEXT_ET_HOVER_Q',               '<b>Flow rate</b>: %{y:.3f} m\u00b3/s');
define('TEXT_ET_HOVER_H_ONLY',          '<b>Height</b>: %{x:.1f} cm<br><b>Flow rate</b>: %{y:.3f} m\u00b3/s');


// -----------------------------------------------
// process_etl_new.php - new ETL creation messages

define('TEXT_ET_NEW_OK',                "The new rating curve 'RC: %s %s \u2192 %s %s' has been created.");
define('TEXT_ET_NEW_EQ_PREFIX',         'Equation: ');
define('TEXT_ET_NEW_R2_PREFIX',         'Fit quality: R<sup>2</sup> = ');
define('TEXT_ET_NEW_ERR_FEW_PTS',       'At least two gauging points are required to fit a rating curve.');
define('TEXT_ET_NEW_ERR_OVERLAP',       "The chosen period is already covered by another rating curve: %s %s \u2192 %s %s");
define('TEXT_ET_NEW_ERR_TRANSACTION',   'Transaction error: ');



// -----------------------------------------------
define('TEXT_ET_BTN_EDIT',          'Edit');
define('TEXT_ET_BTN_EDIT_TITLE',    'Edit the points of this rating curve');
define('TEXT_ET_EDIT_TITLE',        'Edit RC points');
define('TEXT_ET_EDIT_LOADING',      'Loading…');
define('TEXT_ET_EDIT_LOAD_ERR',     'Could not load this RC.');
define('TEXT_ET_EDIT_HINT',         'Click a point to edit its values, or drag it to move it.');
define('TEXT_ET_EDIT_TOO_FEW_PTS',  'This rating curve has fewer than 2 points — nothing to edit.');
define('TEXT_ET_EDIT_CONFIRM_TITLE','Confirm edit');
define('TEXT_ET_EDIT_CONFIRM_MSG',  'You are about to replace all points of this rating curve. The period is not changed.');
define('TEXT_ET_EDIT_SAVE_ERR',     'Save error.');

define('TEXT_ET_EDIT_CURVE_HINT',      'Drag to move');
define('TEXT_ET_NEW_CURVE_DRAG_HINT',  'Drag to move');
define('TEXT_ET_EDIT_HINT_DRAG',       'Drag a point to move it');
define('TEXT_ET_EDIT_HINT_RCLICK',     'Right-click: add (empty area) or remove (on a point)');
define('TEXT_ET_EDIT_MIN_PTS',         'A curve must keep at least 2 points.');

// -----------------------------------------------
// process_etl_duplic.php - duplication messages

define('TEXT_ET_DUPLIC_OK',             "The new rating curve 'RC-%s: %s %s \u2192 %s %s' has been created.");
define('TEXT_ET_DUPLIC_ERR_DATA',       'An error occurred while duplicating the data.');
define('TEXT_ET_DUPLIC_ERR_OVERLAP',    "The chosen period is already covered by another rating curve: %s %s \u2192 %s %s");

// -----------------------------------------------
// process_etl_delete.php - deletion messages

define('TEXT_ET_DEL_OK',                "The rating curve RC-%s: %s %s \u2192 %s %s has been deleted.");
define('TEXT_ET_DEL_ERR_TRANSACTION',   'Transaction error: ');

define('TEXT_ET_DEL_NO_SELECTION',    'Please check at least one RC to delete.');
define('TEXT_ET_DEL_CONFIRM_TITLE',   'Confirm deletion');
define('TEXT_ET_DEL_CONFIRM_MSG',     'You are about to delete the following rating curves. This action cannot be undone.');
define('TEXT_ET_DEL_RC_TO_DELETE',    'RC will be deleted');
define('TEXT_ET_DEL_POINTS',          'points');
define('TEXT_ET_DEL_BTN_CONFIRM',     'Delete');
define('TEXT_ET_DEL_ERR',             'Delete error.');

// -----------------------------------------------
// data_etl.php - station list page

define('TEXT_ET_LIST_PAGE_TITLE',       'Rating Curves - Hydrometric stations');
define('TEXT_ET_FILTER_CURVES',         'Rating Curves');
define('TEXT_ET_FILTER_ALL_ST',         'All stations');
define('TEXT_ET_FILTER_ETL_ST',         'Stations with Rating Curves');
define('TEXT_ET_SORT_LABEL',            'SORT BY');
define('TEXT_ET_SORT_NAME',             'Station name');
define('TEXT_ET_SORT_CODE',             'Station code');
define('TEXT_ET_ORDER_ASC',             'Ascending');
define('TEXT_ET_ORDER_DESC',            'Descending');
define('TEXT_ET_NB_STATIONS',           'Number of stations: ');
define('TEXT_ET_COL_STATUS',            'Status');
define('TEXT_ET_COL_STATUS_TITLE',      'Active or Historical (Closed)');
define('TEXT_ET_COL_SUIVI',             'Monitoring');
define('TEXT_ET_COL_SUIVI_TITLE',       'Continuous measurements or Spot measurements');
define('TEXT_ET_COL_STATION',           'Station (Code - Name)');
define('TEXT_ET_COL_NB_JGE',            'Nb SG');
define('TEXT_ET_COL_NB_JGE_TITLE',      'Number of valid gaugings');
define('TEXT_ET_COL_NB_ETL',            'Nb RC');
define('TEXT_ET_COL_NB_ETL_TITLE',      'Number of rating curves');
define('TEXT_ET_COL_CURVE_TITLE',       'Edit Rating Curve (RC)');
define('TEXT_ET_COL_HQ',                'H -> Q');
define('TEXT_ET_COL_HQ_TITLE',          'Convert water levels to flow rates');
define('TEXT_ET_STATUS_ACTIVE',         'Active');
define('TEXT_ET_STATUS_CLOSED',         'Historical (Closed)');
define('TEXT_ET_SUIVI_CONTINU',         'Continuous measurements');
define('TEXT_ET_SUIVI_PONCTUEL',        'Spot measurements');
define('TEXT_ET_LINK_ETL_TITLE',        'Edit Rating Curve (RC)');
define('TEXT_ET_LINK_HQ_TITLE',         'Convert water levels to flow rates');
define('TEXT_ET_NO_STATION',            'No station was found.');


// Timeline tooltip + duration units
define('TEXT_ET_TIMELINE_TT_RC',       'RC');
define('TEXT_ET_TIMELINE_TT_START',    'Start');
define('TEXT_ET_TIMELINE_TT_END',      'End');
define('TEXT_ET_TIMELINE_TT_DUR',      'Duration');
define('TEXT_ET_TIMELINE_UNIT_DAYS',   'd');
define('TEXT_ET_TIMELINE_UNIT_MONTHS', 'mo');
define('TEXT_ET_TIMELINE_UNIT_YEAR',   'yr');
define('TEXT_ET_TIMELINE_UNIT_YEARS',  'yrs');



// =============================================================================
// STAGE–DISCHARGE CONVERSION MODULE (H→Q)
// =============================================================================

// -----------------------------------------------
// convert_hq.php - page-level errors

define('TEXT_HQ_ERR_STATION',           'The station could not be identified.');
define('TEXT_HQ_ERR_NO_ID',             'No station identifier was provided. The page URL is not recognised.');

define('TEXT_HQ_TOO_MANY_ROWS', 'Too much data to display the chart for this period.');
define('TEXT_HQ_RECORDS',        'records');
define('TEXT_HQ_SHORTER_PERIOD', 'Please select a shorter period.');
define('TEXT_HQ_OR_LOAD_PACKET', 'or load a chunk below');
define('TEXT_HQ_LOAD_PACKET',    'Load chunk');

// -----------------------------------------------
// convert_hq.php - page title

define('TEXT_HQ_PAGE_TITLE_PREFIX',     'Convert Stage to Discharge : ');
define('TEXT_HQ_PAGE_TITLE_STATION',    'Hydrometric station : ');

// -----------------------------------------------
// convert_hq.php - left panel labels

define('TEXT_HQ_CHRON_H_LABEL',         'Stage series to convert');
define('TEXT_HQ_CHRON_Q_LABEL',         'Target discharge series');
define('TEXT_HQ_SHOW_GAPS',             'Show gaps');
define('TEXT_HQ_BTN_CONVERT',           'Convert : H -> Q');
define('TEXT_HQ_BTN_CONVERT_TITLE',     'Run the convert');
define('TEXT_HQ_BTN_VALIDATE',          'Save conversion');
define('TEXT_HQ_BTN_SAVE_TITLE',        'Save data');
define('TEXT_HQ_BTN_WAIT_LABEL',        'Convert in progress');
define('TEXT_HQ_BTN_SAVE_WAIT_LABEL',   'Save data in progress');
define('TEXT_HQ_ZOOM_LABEL',            'Zoom control');
define('TEXT_HQ_DATE_MIN_LABEL',        'Start date');
define('TEXT_HQ_DATE_MAX_LABEL',        'End date');
define('TEXT_HQ_Y_MIN_H',               'Min height');
define('TEXT_HQ_Y_MAX_H',               'Max height');
define('TEXT_HQ_Y_MIN_Q',               'Min flow rate');
define('TEXT_HQ_Y_MAX_Q',               'Max flow rate');
define('TEXT_HQ_BTN_ADJUST',            'Adjust scale');
define('TEXT_HQ_LOADING',               'Loading...');
define('TEXT_HQ_NO_DATA',               'No data was found.');
define('TEXT_HQ_PERIOD_SELECT_LABEL', 'Period Select');
define('TEXT_HQ_BTN_APPLY_PERIOD',    'Apply period');

// Enriched ETL coverage timeline (Lot 4)

define('TEXT_HQ_ETL_TOOLTIP_CURVE',  'Rating curve');
define('TEXT_HQ_ETL_TOOLTIP_PERIOD', 'Period');
define('TEXT_HQ_ETL_RANGE_PREFIX',   'Stage range');
define('TEXT_HQ_ETL_TOOLTIP_HINT',   'click to open in rating curve module');
define('TEXT_HQ_ETL_NO_COVERAGE',    'no coverage');
define('TEXT_HQ_ETL_GAP_HINT',       'click to manage rating curves for this station');
define('TEXT_HQ_ETL_NO_RC', "No defined RC");

// Stepper labels (3 macro steps)
define('TEXT_HQ_STEP_1_TITLE', 'Proposed discharge');
define('TEXT_HQ_STEP_1_HINT',  'Pick source stage series and run conversion');
define('TEXT_HQ_STEP_2_TITLE', 'Review & validate');
define('TEXT_HQ_STEP_2_HINT',  'Compare the green proposal and decide');
define('TEXT_HQ_STEP_3_TITLE', 'Computed discharge');
define('TEXT_HQ_STEP_3_HINT',  'Done — discharge series is saved');
 
// Step 1 panel
define('TEXT_HQ_PERIOD_LABEL', 'Period');
define('TEXT_HQ_STEP_1_FOOT',  'No data will be modified until you validate in step 2.');
 
// Step 2 panel
define('TEXT_HQ_REVIEW_READY',    '✓ Conversion ready');
define('TEXT_HQ_REVIEW_CONVERTED','points converted');
define('TEXT_HQ_REVIEW_LOST_ABOVE','lost (stage above curve range)');
define('TEXT_HQ_REVIEW_LOST_BELOW','lost (stage below curve range)');
define('TEXT_HQ_REVIEW_LOST_NOCOV','lost (no rating curve)');
define('TEXT_HQ_STEP_2_FOOT',   'The green curve on the chart is the proposed discharge series. Compare it carefully before saving.');
define('TEXT_HQ_BTN_DISCARD',   'Discard proposal');
 
// Step 3 panel
define('TEXT_HQ_SAVE_WARNING_TITLE', 'Warning — irreversible action');
define('TEXT_HQ_SAVED_OK',      '✓ Conversion saved');
define('TEXT_HQ_SAVED_WRITTEN', 'points written to production');
define('TEXT_HQ_SAVED_REMOVED', 'previous points removed');
define('TEXT_HQ_SAVED_AT',      'Saved at');
define('TEXT_HQ_BTN_AGAIN',     '↻ Run another conversion');


// Floating console — header & buttons
define('TEXT_HQ_CONSOLE_TITLE',       'Conversion log');
define('TEXT_HQ_CONSOLE_COPY',        'Copy log to clipboard');
define('TEXT_HQ_CONSOLE_COPY_LABEL',  'Copy');
define('TEXT_HQ_CONSOLE_CLEAR',       'Clear the console');
define('TEXT_HQ_CONSOLE_CLEAR_LABEL', 'Clear');
define('TEXT_HQ_CONSOLE_MIN',         'Minimize');
define('TEXT_HQ_CONSOLE_CLOSE',       'Close');
 
// Floating console — log messages
define('TEXT_HQ_LOG_START',           'Conversion started for period');
define('TEXT_HQ_LOG_BAD_RESPONSE',    'Unexpected server response — please retry.');
define('TEXT_HQ_LOG_ETL_FOUND',       'rating curve(s) found');
define('TEXT_HQ_LOG_SEGMENTS_READY',  'interpolation segment(s) prepared');
define('TEXT_HQ_LOG_CONVERT_START',   'Converting stage values to discharge...');
define('TEXT_HQ_LOG_CONVERT_DONE',    'Conversion finished.');
define('TEXT_HQ_LOG_SUMMARY',         'Summary:');
define('TEXT_HQ_LOG_CONVERTED',       'converted points:');
define('TEXT_HQ_LOG_NO_COVERAGE',     'points lost — no rating curve covers this date:');
define('TEXT_HQ_LOG_STAGE_ABOVE',     'points lost — stage above curve range:');
define('TEXT_HQ_LOG_STAGE_BELOW',     'points lost — stage below curve range:');
define('TEXT_HQ_LOG_SOURCE_GAPS',     'source gaps preserved:');
define('TEXT_HQ_LOG_READY_VALID',     'Ready for validation. Review the green trace, then click Validate to save.');

// Validation log (Lot 5) — appended after the conversion log when the user
// clicks "Validate convert".
define('TEXT_HQ_LOG_SAVE_START',         'Saving conversion to production...');
define('TEXT_HQ_LOG_SAVE_BAD_RESPONSE',  'Unexpected server response — please retry.');
define('TEXT_HQ_LOG_SAVE_META_CREATED',  'Created new discharge series record');
define('TEXT_HQ_LOG_SAVE_REMOVED',       'previous discharge points removed on this period:');
define('TEXT_HQ_LOG_SAVE_NO_REMOVE',     'no previous discharge data on this period');
define('TEXT_HQ_LOG_SAVE_COPIED',        'discharge points written to production:');
define('TEXT_HQ_LOG_SAVE_CLEANED',       'temporary records removed:');
define('TEXT_HQ_LOG_SAVE_AT',            'Action logged at');
define('TEXT_HQ_LOG_SAVE_DONE',          'Save completed.');
define('TEXT_HQ_LOG_SAVE_SUCCESS',       'Conversion saved successfully.');

// -----------------------------------------------
// convert_hq.php - JS inline messages

define('TEXT_HQ_JS_SAVED',              'The new flow-rate data has been saved.');
define('TEXT_HQ_JS_ERR_DATE_ORDER',     'The start date must be earlier than the end date.');
define('TEXT_HQ_JS_ERR_DATE_FORMAT',    'At least one of the entered dates is invalid or in the wrong format (dd-mm-yyyy required).');

// -----------------------------------------------
// process_convert_graph_etl.php - Plotly chart title

define('TEXT_HQ_ETL_COVERAGE_TITLE',    'Rating Curve');

// -----------------------------------------------
// process_convert_valid.php - server-side result messages

define('TEXT_HQ_VALID_OK',              'The new data series has been saved.');
define('TEXT_HQ_VALID_ERR',             'An error occurred while saving the data.');
define('TEXT_HQ_LOG_INFO_PREFIX',       "Conversion of 'height' series to flow rate\n");
define('TEXT_HQ_LOG_STATION_PREFIX',    'Station : ');
define('TEXT_HQ_LOG_CHRON_PREFIX',      'Series : ');

// -----------------------------------------------
// Trace name suffix for the pending conversion series (process_convert_graph.php)

define('TEXT_HQ_TRACE_PENDING',         '- pending validation');

define('TEXT_HQ_STEP_1_LABEL', '1. Data display');
define('TEXT_HQ_STEP_2_LABEL', '2. Conversion');
define('TEXT_HQ_STEP_3_LABEL', '3. Save');


// =============================================================================
// PDF - STATION SHEET
// =============================================================================

 
define('TEXT_PDF_TITLE',          'Station sheet');
define('TEXT_PDF_EDITED_ON',      'Edited on');
define('TEXT_PDF_EDITED_BY',      'by');
 
define('TEXT_PDF_STATION_NAME',   'Station name');
define('TEXT_PDF_STATION_CODE',   'Station code');
define('TEXT_PDF_SHORT_NAME',     'Short name');
define('TEXT_PDF_NUM_IRH',        'IRH number');
 
define('TEXT_PDF_STATUS',         'Status');
define('TEXT_PDF_STATUS_ACTIVE',  'Active');
define('TEXT_PDF_STATUS_CLOSED',  'Historical (closed)');
 
define('TEXT_PDF_MONITORING',            'Monitoring');
define('TEXT_PDF_MONITORING_CONTINUOUS', 'Continuous measurements');
define('TEXT_PDF_MONITORING_SPOT',       'Spot measurements');
 
define('TEXT_PDF_EQUIPMENT',        'Equipment');
define('TEXT_PDF_EQUIPMENT_OK',     'Operational');
define('TEXT_PDF_EQUIPMENT_FAULTY', 'Out of order');
 
define('TEXT_PDF_GEO_LOCATION', 'Geographical Location');
define('TEXT_PDF_TERRITORY',    'Territory');
define('TEXT_PDF_COMMUNE',      'Commune');
define('TEXT_PDF_SITE',         'Site');
define('TEXT_PDF_HYDRO_REGION', 'Hydrological region / WS');
define('TEXT_PDF_RIVER',        'River');
define('TEXT_PDF_ALTITUDE',     'Altitude (m)');
 
define('TEXT_PDF_GEO_COORDS',  'Geographical coordinates');
define('TEXT_PDF_LONGITUDE',   'Longitude');
define('TEXT_PDF_LATITUDE',    'Latitude');
define('TEXT_PDF_UTM_X',       'UTM - X (WGS 84)');
define('TEXT_PDF_UTM_Y',       'UTM - Y (WGS 84)');
define('TEXT_PDF_LAMBERT_X',   'Lambert - X (RGNC 91)');
define('TEXT_PDF_LAMBERT_Y',   'Lambert - Y (RGNC 91)');
 
define('TEXT_PDF_INFORMATION',   'Information');
define('TEXT_PDF_INSTALL_DATE',  'Installation date');
define('TEXT_PDF_CLOSE_DATE',    'Uninstallation date');
define('TEXT_PDF_DESCRIPTION',   'Description');
 
define('TEXT_PDF_PHOTOS_TITLE', 'Station photos');
define('TEXT_PDF_PHOTO_DATE',   'Date: ');
define('TEXT_PDF_PHOTO_DESC',   'Description: ');
 
define('TEXT_PDF_RA_TITLE',  'Latest visits (Activity Reports - Field sheets)');
define('TEXT_PDF_RA_DATE',   'Date');
define('TEXT_PDF_RA_OBS',    'Comment');
define('TEXT_PDF_RA_TODO',   'To do');
define('TEXT_PDF_RA_AGENTS', 'Operator(s)');
 
define('TEXT_PDF_DATA_AVAILABLE', 'Data available at the station');
 
define('TEXT_PDF_FOOTER_PAGE',      'Page');
define('TEXT_PDF_FOOTER_OF',        'of');
define('TEXT_PDF_FOOTER_GENERATED', 'Document generated on');

// process_station_access_pdf.php
define('TEXT_PDF_ACCESS_TITLE',      'Station access sheet');
define('TEXT_PDF_ACCESS_CONTACT',    'Contact');
define('TEXT_PDF_ACCESS_OWNER',      'Owner');
define('TEXT_PDF_ACCESS_NAME',       'Contact name');
define('TEXT_PDF_ACCESS_PHONE',      'Phone');
define('TEXT_PDF_ACCESS_EMAIL',      'Email');
define('TEXT_PDF_ACCESS_ADDRESS',    'Address');
define('TEXT_PDF_ACCESS_PO_BOX',     'PO box');
define('TEXT_PDF_ACCESS_POSTCODE',   'Postcode');
define('TEXT_PDF_ACCESS_COMMUNE',    'Municipality');
define('TEXT_PDF_ACCESS_DETAILS',    'Access details');
define('TEXT_PDF_ACCESS_PEDESTRIAN', 'Pedestrian access');
define('TEXT_PDF_ACCESS_TIME',       'Access time');
define('TEXT_PDF_ACCESS_INFO',       'Access information');
define('TEXT_PDF_ACCESS_DIFFICULTY', 'Difficulties');
define('TEXT_PDF_ACCESS_REMARKS',    'Remarks');
define('TEXT_PDF_ACCESS_MAP',        'Access map');
define('TEXT_PDF_ACCESS_YES',        'Yes');
define('TEXT_PDF_ACCESS_NO',         'No');
define('TEXT_PDF_ACCESS_ERROR',      'Error while creating the PDF.');
define('TEXT_ACCESS_PDF_JS_ERR_GENERATE', 'Error while generating the PDF.');
define('TEXT_ACCESS_PDF_JS_ERR_SERVER',   'Server error, please try again.');




// ============================================================
// PAGE LAYOUT  (sync.php)
// ============================================================
 
define('TEXT_SYNC_PAGE_TITLE',          'Data synchronization: Nomad <-> Server');
define('TEXT_SYNC_BTN_TO_NOMAD',        'Load data from the server <<');
define('TEXT_SYNC_BTN_TO_SERVER',       'Push data to the server >>');
define('TEXT_SYNC_LAST_LOAD',           'Last data load: ');
define('TEXT_SYNC_NB_AGENTS',           'Nb Agents: ');
define('TEXT_SYNC_NB_RA',               "Nb Activity Reports: ");
define('TEXT_SYNC_NB_JGE',              'Nb Gaugings: ');
define('TEXT_SYNC_PROCESS_RUNNING',     'Process running');
define('TEXT_SYNC_BTN_STOP',            'Stop');
 
// ============================================================
// CLIENT-SIDE MESSAGES  (sync.php — JavaScript)
// ============================================================
 
define('TEXT_SYNC_JS_NO_CONNECTION',    "Error: no connection was detected, the update is not possible.");
define('TEXT_SYNC_JS_PROCESS_STOPPED',  'The process has been stopped');
define('TEXT_SYNC_JS_CONNECTION_OK',    'Connection detected: starting the process');
define('TEXT_SYNC_JS_LOADING_FROM',     'Loading data from the server ...');
define('TEXT_SYNC_JS_PUSHING_TO',       'Pushing data to the server ...');
define('TEXT_SYNC_JS_CONNECT_FAILED',   'The process has been stopped: connection failed.');
define('TEXT_SYNC_JS_PLEASE_WAIT',      'Please wait, loading the data may take a few minutes ...');
define('TEXT_SYNC_JS_STOP_REQUESTED',   "Stop request sent ... the process will halt at the next step.");
 
// ============================================================
// CONNECTION TEST  (process_connect.php)
// ============================================================
 
define('TEXT_SYNC_CONN_NOMAD_OK',       'The Nomad connection is established');
define('TEXT_SYNC_CONN_NOMAD_FAIL',     'Unable to connect to the offline Nomad database');
define('TEXT_SYNC_CONN_SERVER_OK',      'The connection with the platform is established');
define('TEXT_SYNC_CONN_SERVER_FAIL',    'Unable to connect to the remote database (server unreachable — check your Internet connection / proxy)');
 
// ============================================================
// SHARED PROCESS MESSAGES  (process_tonomad.php & process_toserveur.php)
// ============================================================
 
define('TEXT_SYNC_DB_CONNECT_FAIL',     'Unable to connect to the databases.');
define('TEXT_SYNC_DB_CONNECT_RETRY',    'Check your Internet connection then try again.');
define('TEXT_SYNC_SUCCESS',             'Synchronization completed successfully.');
define('TEXT_SYNC_PROCESSING_TIME',     'Processing time: ');
define('TEXT_SYNC_SECONDS_SHORT',       'sec.');
define('TEXT_SYNC_TECH_DETAIL',         'Technical details (forward to support if needed):');
 
// ---- Download direction: SERVER -> NOMAD  (process_tonomad.php) ----
 
define('TEXT_SYNC_DL_STOPPED',          'Loading stopped at your request.');
define('TEXT_SYNC_DL_STOPPED_ROLLBACK', 'The local data was restored to its initial state: everything was cleanly rolled back.');
define('TEXT_SYNC_DL_STOPPED_RETRY',    'You may restart the loading whenever you wish.');
define('TEXT_SYNC_DL_FAILED',           'Loading failed and was entirely rolled back.');
define('TEXT_SYNC_DL_FAILED_SAFE',      'Your local database was left in its previous state.');
define('TEXT_SYNC_DL_CONNECTION_LOST', 'The connection to the server was lost during the transfer (the data volume was likely too large for the server in a single run). Nothing was changed locally. Try again; if it persists, contact support to raise the server limits.');
 
// ---- Upload direction: NOMAD -> SERVER  (process_toserveur.php) ----
 
define('TEXT_SYNC_UP_NB_AGENTS',        'Number of Agents synchronized: ');
define('TEXT_SYNC_UP_NB_RA',            'Number of Activity Reports synchronized: ');
define('TEXT_SYNC_UP_NB_JGE',           'Number of Gaugings synchronized: ');
define('TEXT_SYNC_UP_STOPPED',          'Synchronization stopped at your request.');
define('TEXT_SYNC_UP_STOPPED_ROLLBACK', 'No data was sent to the server: everything was cleanly rolled back.');
define('TEXT_SYNC_UP_STOPPED_RETRY',    'You may restart the synchronization whenever you wish.');
define('TEXT_SYNC_UP_FAILED',           'Synchronization failed and was entirely rolled back.');
define('TEXT_SYNC_UP_FAILED_SAFE',      'No data was sent: your field entries are intact.');
?>
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

define('LANG_BTN_LOGIN',            'Connexion');
define('LANG_BTN_SAVE',             'Enregistrer');
define('LANG_BTN_VALIDATE',         'Valider');

// ============================================================
// LOGIN PAGE  (login.php)
// ============================================================

define('LANG_LOGIN_BAD_CREDENTIALS','Les identifiants sont incorrects');
define('LANG_LOGIN_BRUTE_FORCE',    '!!! Accès protégé - tentative de force brute détectée !!!');
define('LANG_LOGIN_DOUBLE_SESSION', 'Une session active existe déjà pour ce compte.');
define('LANG_LOGIN_SESSION_CLOSED', 'Par sécurité, la session a été fermée.');
define('LANG_LOGIN_RECONNECT',      'Veuillez vous reconnecter.');

// ============================================================
// LOGOUT PAGE  (logout.php)
// ============================================================

define('LANG_LOGOUT_ON_APP',        'Vous êtes sur l’application');
define('LANG_LOGOUT_SESSION_ENDED', 'Votre session est terminée');
define('LANG_LOGOUT_LINK_LOGIN',    'Se connecter');

// ============================================================
// LOGOUT OVERLAY BLOCK  (block_logout.php)
// ============================================================

define('LANG_LOGOUT_CONFIRMED',     'Vous avez été déconnecté de');
define('LANG_LOGOUT_BTN_BACK',      'Retour');


// ============================================================
// POPUP DIALOGS
// ============================================================
 
define('TEXT_POPUP_CLOSE',              'Fermer');
define('TEXT_POPUP_VALIDATE',           'Valider');
define('TEXT_POPUP_CANCEL',             'Annuler');
define('TEXT_POPUP_DELETE_CONFIRM',     'Êtes vous sûr de vouloir supprimer les données ?');
define('TEXT_POPUP_DELETE_IRREVERSIBLE', 'Cette action supprime définitivement les données sélectionnées. Elles ne pourront pas être récupérées.');
define('TEXT_POPUP_SAVE_CONFIRM',       'Êtes vous sûr de vouloir valider les corrections ?');
define('TEXT_POPUP_SAVE_OVERWRITE',     'Si des données existent pour la même chronique, sur la même station et la même période, elles seront effacées.');
define('TEXT_POPUP_DELETE_CHALLENGE_LABEL', 'Pour confirmer, résolvez l’opération :'); // FR


// ============================================================
// LOGIN OVERLAY BLOCK  (block_login.php)
// ============================================================

define('LANG_BLOCK_LOGIN_TITLE',        'Connexion');
define('LANG_BLOCK_LOGIN_FIELD_PH',     'Email ou identifiant');
define('LANG_BLOCK_LOGIN_PASS_PH',      'Mot de passe');
define('LANG_BLOCK_LOGIN_BTN_CONNECT',  'Connexion');
define('LANG_BLOCK_LOGIN_BTN_CANCEL',   'Annuler');
define('LANG_BLOCK_LOGIN_FORGOT_PASS',  'Mot de passe oublié ?');
define('LANG_BLOCK_LOGIN_CREATE_ACCT',  'Créer un compte');

// ============================================================
// CHANGE PASSWORD PAGE  (mdp.php)
// ============================================================

define('LANG_MDP_TITLE',            'Changer le mot de passe');
define('LANG_MDP_OLD_PASS',         'Ancien mot de passe');
define('LANG_MDP_NEW_PASS',         'Nouveau mot de passe');
define('LANG_MDP_CONFIRM_PASS',     'Confirmer le mot de passe');
define('LANG_MDP_STRENGTH_WEAK',    'faible');
define('LANG_MDP_STRENGTH_MEDIUM',  'moyen');
define('LANG_MDP_STRENGTH_STRONG',  'fort');

// ============================================================
// CREATE ACCOUNT PAGE  (account_new.php)
// ============================================================

define('LANG_ACCT_NEW_TITLE',           'Créer un compte');
define('LANG_ACCT_NEW_LOGIN',           'Identifiant');
define('LANG_ACCT_NEW_LOGIN_HINT',      'Sans espaces, accents ou caractères spéciaux');
define('LANG_ACCT_NEW_LOGIN_PH',        'Identifiant');
define('LANG_ACCT_NEW_EMAIL',           'Adresse email');
define('LANG_ACCT_NEW_EMAIL_PH',        'exemple@domaine.com');
define('LANG_ACCT_NEW_ORGA',            'Organisation');
define('LANG_ACCT_NEW_ORGA_PH',         'Organisation');
define('LANG_ACCT_NEW_LASTNAME',        'Nom');
define('LANG_ACCT_NEW_LASTNAME_PH',     'Nom');
define('LANG_ACCT_NEW_FIRSTNAME',       'Prénom');
define('LANG_ACCT_NEW_FIRSTNAME_PH',    'Prénom');

// ============================================================
// ACCOUNT CONFIRMATION PAGE  (account_confirm.php)
// ============================================================

define('LANG_CONFIRM_TITLE',        'Confirmation de compte');
define('LANG_CONFIRM_EMAIL_SENT',   'Un email de confirmation a été envoyé à (vérifiez les spams) :');
define('LANG_CONFIRM_ENTER_CODE',   'Saisir le code de confirmation');
define('LANG_CONFIRM_CODE_PH',      'Code de confirmation');
define('LANG_CONFIRM_RESEND_PRE',   'Si l’email n’est pas arrivé, vous pouvez ');
define('LANG_CONFIRM_RESEND',       'Renvoyer le code');
define('LANG_CONFIRM_CODE_SHORT',   'Code incorrect');

// ============================================================
// FORGOT PASSWORD PAGE  (account_mail.php)
// ============================================================

define('LANG_MAIL_TITLE',           'Mot de passe d’accès');
define('LANG_MAIL_FIELD_LABEL',     'Identifiant ou email');
define('LANG_MAIL_FIELD_PH',        'Identifiant ou email');

// ============================================================
// SET / RESET PASSWORD PAGE  (account_valid.php)
// ============================================================

define('LANG_VALID_TITLE',              'Définir le mot de passe');
define('LANG_VALID_PASS_RULE',          '8 caractères minimum');
define('LANG_VALID_PASS_HINT',          'Majuscule, minuscule et caractère spécial');
define('LANG_VALID_PASS_FIRST',         'Mot de passe');
define('LANG_VALID_PASS_FIRST_PH',      'Nouveau mot de passe');
define('LANG_VALID_PASS_CONFIRM',       'Confirmer le mot de passe');
define('LANG_VALID_PASS_CONFIRM_PH',    'Confirmer le mot de passe');

// =============================================================================
// CONNEXION - SECURITY
// =============================================================================

// -----------------------------------------------
// Shared / generic
define('TEXT_AC_ERR_FORM',              'Erreur lors de l’envoi du formulaire.');
define('TEXT_AC_ERR_REQUEST',           'Requête invalide.');
define('TEXT_AC_ERR_SESSION',           'Session expirée ou invalide. Veuillez recommencer.');
define('TEXT_AC_ERR_VALIDATION',        'Erreur de validation.');
define('TEXT_AC_ERR_MAIL',              'Erreur lors de l’envoi de l’email. Contactez le support.');

// -----------------------------------------------
// process_account_verif-input.php
define('TEXT_AC_CREATE_ERR_LOGIN_EMPTY',    'L’identifiant est obligatoire.');
define('TEXT_AC_CREATE_ERR_LOGIN_CHARS',    'Seuls lettres/chiffres sont autorisés (pas d’espaces/accents).');
define('TEXT_AC_CREATE_ERR_LOGIN_DUP',      'Cet identifiant est déjà utilisé.');
define('TEXT_AC_CREATE_ERR_EMAIL_EMPTY',    'L’email est obligatoire.');
define('TEXT_AC_CREATE_ERR_EMAIL_FORMAT',   'Format d’email invalide.');
define('TEXT_AC_CREATE_ERR_EMAIL_DUP',      'Cet email est déjà utilisé.');
define('TEXT_AC_CREATE_ERR_ORG_EMPTY',      'L’organisation est obligatoire.');
define('TEXT_AC_CREATE_MSG_OK',             'Si le compte existe, un email a été envoyé.');
define('TEXT_AC_CREATE_ERR_MAIL',           'Erreur lors de l’envoi de l’email. Contactez le support.');

// -----------------------------------------------
// process_account_verif-logmail.php


// ---- Password reset — step 1 ----
define('TEXT_AC_RESET_ERR_FIELD_EMPTY',  'Veuillez renseigner ce champ.');
define('TEXT_AC_RESET_ERR_NOT_FOUND',    'Aucun compte ne correspond à cet identifiant ou à cette adresse email.');
define('TEXT_AC_RESET_MSG_OK',           'Un code de confirmation vous a été envoyé par email.');
define('TEXT_AC_RESET_MSG_REDIRECT',     'Vous allez être redirigé dans quelques instants.');
 
// ---- Password reset email ----
define('TEXT_MAIL_RESET_TITLE',          'Code de confirmation');
define('TEXT_MAIL_RESET_SUBTITLE',       'Nouveau mot de passe');
define('TEXT_MAIL_RESET_HELLO',          'Bonjour');
define('TEXT_MAIL_RESET_INSTRUCTION',    'Utilisez le code suivant pour vérifier votre identité.');
define('TEXT_MAIL_RESET_VALIDITY',       'Ce code est valable 15 minutes.');
define('TEXT_MAIL_RESET_ENTER_CODE',     'Entrez votre code ici :');
define('TEXT_MAIL_RESET_LINK_LABEL',     'Vérifier mon identité');
define('TEXT_MAIL_RESET_WARN_MISUSE',    'Si vous n’êtes pas à l’origine de cette demande, votre adresse email a peut-être été utilisée à votre insu.');
define('TEXT_MAIL_RESET_WARN_IGNORE',    'Ignorez cet email ou contactez le support :');
define('TEXT_MAIL_RESET_AUTO_GENERATED', 'Cet email a été généré automatiquement. Merci de ne pas y répondre.');
define('TEXT_MAIL_RESET_SUBJECT',        'Nouveau mot de passe sur %s — Code de confirmation');

// -----------------------------------------------
// process_account_valid.php
define('TEXT_AC_VALID_ERR_USER',            'Compte introuvable.');
define('TEXT_AC_VALID_ERR_TOKEN',           'Session invalide ou expirée.');
define('TEXT_AC_VALID_ERR_DATE',            'Code de vérification expiré.');
define('TEXT_AC_VALID_ERR_CODE',            'Code incorrect.');
define('TEXT_AC_VALID_MSG_OK',              'Compte vérifié.');
define('TEXT_AC_VALID_MSG_REDIRECT',        'Redirection vers la connexion...');

// -----------------------------------------------
// process_account_pass_valid.php
define('TEXT_AC_PASS_ERR_MISMATCH',         'Les mots de passe ne correspondent pas.');
define('TEXT_AC_PASS_ERR_TOO_SHORT',        '8 caractères minimum.');
define('TEXT_AC_PASS_ERR_COMPLEXITY',       'Majuscule, minuscule et caractère spécial requis.');
define('TEXT_AC_PASS_ERR_NOT_COMPLIANT',    'Mot de passe non conforme.');
define('TEXT_AC_PASS_ERR_TECH',             'Erreur technique.');
define('TEXT_AC_PASS_MSG_OK',               'Mot de passe enregistré.');
define('TEXT_AC_PASS_MSG_REDIRECT',         'Redirection vers la connexion...');

// =============================================================================
// INDEX - HOMEPAGE
// =============================================================================
define('TEXT_INDEX_LAST_FIELD', 'Dernières Fiches Terrain');
define('TEXT_INDEX_LAST_IMPORT', 'Dernières Importations');

// -----------------------------------------------
// block_index_affiche.php
define('TEXT_IX_POPUP_CLOSE',           'Fermer');

// -----------------------------------------------
// process_index_last_import.php
define('TEXT_IX_IMP_COL_DATE',          'Date');
define('TEXT_IX_IMP_COL_USER',          'Utilisateur');
define('TEXT_IX_IMP_COL_STATION',       'Station');
define('TEXT_IX_IMP_COL_CHRON',         'Séries');
define('TEXT_IX_IMP_COL_CONSULT',       'Consulter');
define('TEXT_IX_IMP_LINK_TITLE',        'Voir les données importées');

// -----------------------------------------------
// process_index_last_ra.php
define('TEXT_IX_RA_COL_DATE',           'Date');
define('TEXT_IX_RA_COL_TYPE',           'Type');
define('TEXT_IX_RA_COL_STATION',        'Station');
define('TEXT_IX_RA_COL_AGENTS',         'Agents');
define('TEXT_IX_RA_STATUS_VALID',       'Validé');
define('TEXT_IX_RA_STATUS_PENDING',     'En attente');

// =============================================================================
// SYSTEM MESSAGES & POPUPS
// =============================================================================
define('TEXT_POPUP_NOCONNEXION', 'Pas de connexion Internet.\n Certaines fonctionnalités sont indisponibles.\n Les fonds de carte ne s’affichent pas.');

// =============================================================================
// TOP BAR
// =============================================================================
define('TEXT_TOP_FIRST', 'Accueil');
define('TEXT_TOP_DATE_HP', 'Date');
define('TEXT_TOP_VERSION_HP', 'Version');
define('TEXT_TOP_DATE_DATA_UPDATE', 'MAJ données');
define('TEXT_TOP_COUNTRY', 'Territoire');
define('TEXT_TOP_LOG', 'Compte');
define('TEXT_TOP_LOG_QUAL', 'Qualité');
define('TEXT_TOP_ADMIN', 'Administration');
define('TEXT_TOP_PASS', 'Changer mot de passe');
define('TEXT_TOP_CLOSE', 'Déconnexion');

// =============================================================================
// NAVIGATION MENU
// =============================================================================

// -----------------------------------------------
// Data
define('TEXT_MENU_DATA', 'Données');
define('TEXT_MENU_DATA_CHRON', 'Séries temporelles');
define('TEXT_MENU_DATA_TRACKCONNECT', 'Suivi des Corrections');
define('TEXT_MENU_DATA_ACTREPORT', 'Rapports d’Activité (RA)');
define('TEXT_MENU_DATA_IMPORT', 'Import');
define('TEXT_MENU_DATA_EXPORT', 'Export');
define('TEXT_MENU_DATA_SYNC', 'Sync. serveur');

// -----------------------------------------------
// Modules
define('TEXT_MENU_MOD', 'Modules');
define('TEXT_MENU_MOD_STATION', 'Stations');
define('TEXT_MENU_MOD_JGE', 'Jaugages');
define('TEXT_MENU_MOD_ETL', 'Etalonnage (Débits)');
define('TEXT_MENU_MOD_DIAG', 'Diagraphie');
define('TEXT_MENU_MOD_AGENTS', 'Agents');

// -----------------------------------------------
// Monitoring rounds
define('TEXT_MENU_ROUND', 'Tournées');
define('TEXT_MENU_ROUND_TRACK', 'Suivi');
define('TEXT_MENU_ROUND_MANAGE', 'Gestion');

// -----------------------------------------------
// Settings
define('TEXT_MENU_SET', 'Paramètres');
define('TEXT_MENU_SET_GEO', 'Zones géographiques');
define('TEXT_MENU_SET_TYPEC', 'Types de séries');
define('TEXT_MENU_SET_QUAL', 'Codes qualité');
define('TEXT_MENU_SET_EQJGE', 'Équipements');
define('TEXT_MENU_SET_OPTION', 'Options');
define('TEXT_MENU_SET_TRANSF', 'Import/Export');

// -----------------------------------------------
// Platform actions
define('TEXT_MENU_HP', 'Actions');
define('TEXT_MENU_HP_TRACKIMPORT', 'Suivi Import');
define('TEXT_MENU_HP_TRACKEXPORT', 'Suivi Export');
define('TEXT_MENU_HP_ACTIONS', 'Toutes les actions');

// -----------------------------------------------
// Resources
define('TEXT_MENU_RESSOURCE', 'Ressources');
define('TEXT_MENU_RESSOURCE_FIRST', 'Accueil');
define('TEXT_MENU_RESSOURCE_HELP', 'Aide');
define('TEXT_MENU_RESSOURCE_CONDITION', 'CGU');
define('TEXT_MENU_RESSOURCE_DATA', 'Licence');
define('TEXT_MENU_RESSOURCE_CONTACT', 'Contact');

define('TEXT_MENU_POPUP_CGU',           'Conditions Générales d’Utilisation');
define('TEXT_MENU_POPUP_LICENCE',       'Licence des données');

// =============================================================================
// INTERACTIVE MAP
// =============================================================================
define('TEXT_MAP_TITLE', 'Carte interactive');
define('TEXT_MAP_BACK', 'Retour à l’échelle %s');
define('TEXT_MAP_ZOOM', 'Zoom');
define('TEXT_MAP_LONG', 'Long.');
define('TEXT_MAP_LAT', 'Lat.');
define('TEXT_MAP_ALT', 'Alt.');
define('TEXT_MAP_FULLSCREEN', 'Plein écran');
define('TEXT_MAP_WINDOWED', 'Quitter plein écran');
define('TEXT_MAP_SAVEIMG', 'Capturer la carte');
define('TEXT_MAP_DLIMG', 'Télécharger');
define('TEXT_MAP_LEGEND_TITLE', 'Légende');
define('TEXT_MAP_SHOW_CODES', 'Afficher les codes stations');


define('TEXT_MAP_LENS_DEPTH', 'Profondeur de la lentille :');

// =============================================================================
// STATION FILTERS
// =============================================================================

define('TEXT_FILTER_TITLE', 'Filtres');
define('TEXT_FILTER_TOGGLE', 'Reduire / afficher les filtres');
define('TEXT_FILTER_RESET', 'Réinitialiser les filtres');

// -----------------------------------------------
// Status / monitoring option values
define('TEXT_FILTER_ALL', '* Tous');
define('TEXT_FILTER_SEARCH', 'Rechercher');
define('TEXT_FILTER_TYPE', 'Type');
define('TEXT_FILTER_BV', 'Bassin');
define('TEXT_FILTER_RIVER', 'Cours d’eau');
define('TEXT_FILTER_AQUIFERE', 'Aquifère');
define('TEXT_FILTER_CITY', 'Commune');
define('TEXT_FILTER_ROUND', 'Tournée');
define('TEXT_FILTER_STATION', 'Station');

define('TEXT_FILTER_GEO_AREA', 'Zone géographique'); 

// -----------------------------------------------
// Status & monitoring options
define('TEXT_FILTER_STATUT', 'Statut');
define('TEXT_FILTER_STATUTACTIVE', 'Active');
define('TEXT_FILTER_STATUTHISTORIQUE', 'Historique');
define('TEXT_FILTER_SUIVI', 'Suivi');
define('TEXT_FILTER_SUIVICONTINU', 'Continu');
define('TEXT_FILTER_SUIVIPONCTUEL', 'Ponctuel');
define('TEXT_FILTER_ETATEQ', 'État équip.');
define('TEXT_FILTER_ETATFONCTIONNEMENT', 'En service');
define('TEXT_FILTER_ETATPANNE', 'Hors service');

define('TEXT_FILTER_ACTIVE',        'Active');
define('TEXT_FILTER_CLOSED',        'Historique(Fermée)');
define('TEXT_FILTER_CONTINU',       'Continu');
define('TEXT_FILTER_PONCTUEL',      'Ponctuel');

// -----------------------------------------------
// Filter info labels
define('TEXT_FILTER_OWNER', 'Gestionnaire des données');
define('TEXT_FILTER_NBSTATION', 'Nb stations');

// =============================================================================
// STATION SUMMARY (MAP POPUP)
// =============================================================================
define('TEXT_FROM_DATA', 'Depuis données');
define('TEXT_STATION_TYPE', 'Type');
define('TEXT_STATION_NOM', 'Nom');
define('TEXT_STATION_CODE', 'Code');
define('TEXT_STATION_DATE_INSTALL', 'Installation');
define('TEXT_STATION_DATE_CLOSING', 'Fermeture');
define('TEXT_STATION_DATE_LASTGO', 'Dernière visite');
define('TEXT_STATION_DELAY_LASTGO', 'Délai dernière visite');
define('TEXT_STATION_STATUT', 'Statut');
define('TEXT_STATION_SUIVI', 'Suivi');
define('TEXT_STATION_ETATEQ', 'État équip.');

// -----------------------------------------------
// Station detail links
define('TEXT_STATION_LINK_FICHE', '>> Fiche station');
define('TEXT_STATION_LINK_DATA', '>> Données station');
define('TEXT_STATION_LINK_LAST_RA', '>> Derniers RA');

// =============================================================================
// ACTION BUTTONS
// =============================================================================
define('TEXT_BUTTON_RA', 'Fiches Terrain');
define('TEXT_BUTTON_IMPORT', 'Liste des importations');

// =============================================================================
// FIELD REPORTS (RA) & TIME SERIES
// =============================================================================
define('TEXT_CHRON_RA', 'Visite terrain - événements');
define('TEXT_CHRON_RA_HEIGHT', 'Lecture hauteur');
define('TEXT_CHRON_JGE', 'Jaugages');

// -----------------------------------------------
// RA list
define('TEXT_TITLE_RA_LIST', 'Liste des Rapports d’Activité');
define('TEXT_NEW_RA_PLUVIO', 'Nouveau RA - Pluvio.');
define('TEXT_NEW_RA_HYDRO', 'Nouveau RA - Hydro.');
define('TEXT_NEW_RA_PIEZO', 'Nouveau RA - Piézo.');

// -----------------------------------------------
// Period & sort filters
define('TEXT_PERIOD_LABEL', 'Période');
define('TEXT_PERIOD_1_MONTH', '1 mois');
define('TEXT_PERIOD_3_MONTHS', '3 mois');
define('TEXT_PERIOD_6_MONTHS', '6 mois');
define('TEXT_PERIOD_1_YEAR', '1 an');
define('TEXT_PERIOD_2_YEARS', '2 ans');
define('TEXT_PERIOD_5_YEARS', '5 ans');
define('TEXT_PERIOD_10_YEARS', '10 ans');
define('TEXT_PERIOD_ALL_DATA', 'Toutes');

define('TEXT_SORT_BY', 'TRIER PAR');
define('TEXT_SORT_LAST_VISIT', 'Dernière visite');
define('TEXT_SORT_STATION_NAME', 'Nom station');
define('TEXT_SORT_STATION_CODE', 'Code station');
define('TEXT_SORT_DATA_TYPE', 'Type données');
define('TEXT_SORT_ASCENDING', 'Croissant');
define('TEXT_SORT_DESCENDING', 'Décroissant');

define('TEXT_NB_LINES', 'NB LIGNES');
define('TEXT_NB_LINES_50', '50');
define('TEXT_NB_LINES_100', '100');
define('TEXT_NB_LINES_200', '200');
define('TEXT_NB_LINES_300', '300');
define('TEXT_NB_LINES_ALL', 'Toutes');

// -----------------------------------------------
// RA counters
define('TEXT_NB_RA_TO_VALIDATE', 'RA à valider : ');
define('TEXT_NB_TOTAL_RA', 'Total RA : ');

// -----------------------------------------------
// RA table headers
define('TEXT_TABLE_HEADER_STATUS', 'Statut');
define('TEXT_TABLE_HEADER_DATE', 'Date/Heure');
define('TEXT_TABLE_HEADER_DATA_TYPE', 'Type');
define('TEXT_TABLE_HEADER_STATION_CODE', 'Code station');
define('TEXT_TABLE_HEADER_STATION_NAME', 'Nom station');
define('TEXT_TABLE_HEADER_COMMUNE', 'Commune');
define('TEXT_TABLE_HEADER_AGENTS', 'Agents');

// -----------------------------------------------
// Loading states
define('TEXT_LOADING', 'Chargement...');
define('TEXT_PLEASE_WAIT', '- Patienter -');

// -----------------------------------------------
// Deletion confirmation dialog
define('TEXT_RA_DELETE_CONFIRMATION', 'Supprimer ce RA ?');
define('TEXT_RA_STATION_INFO', 'Station');
define('TEXT_RA_DATE_INFO', 'Date');
define('TEXT_DELETE_BUTTON', 'Supprimer');
define('TEXT_CANCEL_BUTTON', 'Annuler');

// -----------------------------------------------
// Deletion result messages
define('TEXT_RA_DELETE_SUCCESS', 'RA supprimé.');
define('TEXT_RA_DELETE_ERROR', 'Erreur lors de la suppression.');

// -----------------------------------------------
// Field toggle buttons
define('TEXT_TOGGLE_HIDE_FIELDS', '[ Masquer champs ]');
define('TEXT_TOGGLE_SHOW_FIELDS', '[ Afficher champs ]');

define('TEXT_SELECT2_STATION_PLACEHOLDER', 'Sélectionner station...');
define('TEXT_SELECT2_TYPE_PLACEHOLDER', 'Type...');
define('TEXT_SELECT2_NUMBER_PLACEHOLDER', 'N°...');
define('TEXT_SELECT2_PROBE_NUMBER_PLACEHOLDER', 'N° sonde...');
define('TEXT_SELECT2_INSTRUMENT_PLACEHOLDER', 'Instrument...');
define('TEXT_SELECT2_INSTRUMENT_NUMBER_PLACEHOLDER', 'N° instrument...');
define('TEXT_SELECT2_BENCHMARK_TYPE_PLACEHOLDER', 'Type de repère...');

// -----------------------------------------------
// RA tab - navigation & misc
define('TEXT_STEP_0', 'Étape 0');
define('TEXT_STEP_1', 'Étape 1');
define('TEXT_DELETE_RA', 'Supprimer RA');
define('TEXT_NO_RA_FOUND', 'Aucun RA trouvé.');

// -----------------------------------------------
// RA form labels
define('TEXT_MODIFY', 'Modifier');
define('TEXT_SELECT_STATION', '-- Station --');
define('TEXT_RA_VALIDATED', 'Validé');
define('TEXT_RA_NOT_VALIDATED', 'Non validé');
define('TEXT_ENTERED_ON', 'Saisi le : ');
define('TEXT_BY', 'par : ');
define('TEXT_RA_NOT_FOUND', 'RA introuvable.');
define('TEXT_CANNOT_CREATE_RA', 'Impossible de créer un RA (%s) avec les filtres actuels.');
define('TEXT_CANCEL', 'Annuler');
define('TEXT_SAVE', 'Enregistrer');
define('TEXT_READING', 'Lecture');
define('TEXT_READING_FILE_NAME', 'Nom fichier');
define('TEXT_DATE', 'Date');
define('TEXT_TIME', 'Heure');
define('TEXT_DEVICE', 'Appareil');
define('TEXT_TYPE', 'Type');
define('TEXT_NUMBER', 'N°');
define('TEXT_DEVICE_STATE', 'État');
define('TEXT_NB_TIPPINGS', 'Bascul.');
define('TEXT_NB_BYTES', 'Octets');
define('TEXT_BATTERY_NUM', 'Batt. n°');
define('TEXT_BATTERY_VOLTAGE', 'Tension batt.');
define('TEXT_PREVIOUS', 'Préc.');
define('TEXT_TOTALIZER', 'Totalis.');
define('TEXT_TOTAL_TYPE', 'Type');
define('TEXT_CUMUL_ARRIVAL', 'Cumul arrivée');
define('TEXT_CUMUL_DEPARTURE', 'Cumul départ');
define('TEXT_TIPPING_TIME', 'Heure bascul.');
define('TEXT_CONTROL', 'Contrôle');
define('TEXT_CUMUL_TOTAL', 'Cumul total');
define('TEXT_CUMUL_RAIN', 'Cumul pluie');
define('TEXT_TOTAL_RAIN', 'Total pluie');
define('TEXT_TIME_ADJUSTMENT', 'Ajust. heure');
define('TEXT_AUGET_TEST', 'Test seau');
define('TEXT_NEW_CASSETTE', 'Nouvelle cassette');
define('TEXT_CASSETTE_NUM', 'Num');
define('TEXT_INIT_TIME', 'Heure init.');
define('TEXT_FIRST_TIPPING_TIME', '1er bascul.');
define('TEXT_RECORDING_DURATION', 'Durée enregistr.');
define('TEXT_DAYS', 'JJ');
define('TEXT_HOURS', 'HH');
define('TEXT_MINUTES', 'MM');
define('TEXT_SECONDES', 'SS');
define('TEXT_LAST_RECORDING', 'Dernier enregistr.');
define('TEXT_OBSERVATIONS', 'Obs. / Actions');
define('TEXT_MARKABLE_ACTION', 'Action notable');
define('TEXT_FUTURE_ACTIONS', 'À faire');
define('TEXT_PARTICIPANTS', 'Participants');
define('TEXT_CLOGGING', 'Colmatage');
define('TEXT_TOTAL_OIL', 'Huile totale');
define('TEXT_CLEARING', 'Débroussaillage');
define('TEXT_BATTERY_WATER', 'Eau batterie');
define('TEXT_DATA_TRANSFER', 'Transfert données');
define('TEXT_MEMORY_CLEARED', 'Mémoire effacée');
define('TEXT_IMPORTANT_ACTION', 'Action clé');
define('TEXT_AGENTS_PARTICIPATED', 'Agents');
define('TEXT_FIRST_RA', '1er RA');
define('TEXT_PREVIOUS_RA', 'RA préc.');
define('TEXT_NEXT_RA', 'RA suiv.');
define('TEXT_LAST_RA', 'Dernier RA');
define('TEXT_RA_VALIDATION', 'Validation');

define('TEXT_PROBE_INFO', 'Infos capteur');
define('TEXT_WATER_LEVEL', 'Niveau eau');
define('TEXT_PROBE_HEIGHT', 'Hauteur sonde');
define('TEXT_SCALE_HEIGHT', 'Ech. limni.');
define('TEXT_SCALE_HEIGHT_2', 'Ech. limni. 2');
define('TEXT_HYDRO_CONTROL', 'Contrôle niveau');
define('TEXT_SCALE_PROBE_DIFF', 'Ech. - Sonde');
define('TEXT_PROBE_ADJUSTMENT', 'Décalage sonde');
define('TEXT_PROBE_TIME_ADJUSTMENT', 'Ajust. heure');
define('TEXT_DATA_PURGE', 'Purge données');
define('TEXT_GAUGING', 'Jaugeage');

define('TEXT_FIELD_FORM_PIEZO_DISPLAY', 'Fiche terrain - Piézo.');
define('TEXT_MODIFY_RA', 'Modifier RA');
define('TEXT_MEASUREMENT_POSITION', 'Localisation');
define('TEXT_X_GPS_POSITION', 'X - Position GPS');
define('TEXT_Y_GPS_POSITION', 'Y - Position GPS');
define('TEXT_COORD_SYSTEM', 'Système coord.');
define('TEXT_GPS_PRECISION', 'Précision GPS');
define('TEXT_CONDUCTIVITY_PROFILE', 'Profil conductivité');
define('TEXT_FIXED_PROBE_CHARACTERISTICS', 'Caract. sonde fixe');
define('TEXT_MANUAL_PROBE_CHARACTERISTICS', 'Caract. sonde manuelle');
define('TEXT_FIXED_PROBE_MEASUREMENT', 'Mesure sonde fixe');
define('TEXT_MANUAL_PROBE_MEASUREMENT', 'Mesure manuelle');
define('TEXT_WATER_TABLE_DEPTH', 'Prof. nappe');
define('TEXT_CONDUCTIVITY', 'Conductivité');
define('TEXT_TEMPERATURE', 'Température');
define('TEXT_MARKER_NATURE', 'Type repère');
define('TEXT_TOTAL_DEPTH', 'Prof. totale');
define('TEXT_FIXED_PROBE_ADJUSTMENT', 'Décalage sonde fixe');
define('TEXT_DIFF_MANUAL_FIXED', 'Diff. manuelle − fixe');
define('TEXT_DEVICE_STATE_FIXED_PROBE', 'État sonde fixe');
define('TEXT_NB_DATA', 'Nb données');
define('TEXT_BATTERY_PERCENT', 'Batt. %');
define('TEXT_PUMPING_IN_PROGRESS', 'Pompage en cours');
define('TEXT_NEARBY_PUMPING', 'Pompage proche');
define('TEXT_RAIN_FLOOD', 'Pluie et/ou crue');
define('TEXT_DRY_DAY', 'Journée sèche');
define('TEXT_PHOTOS', 'Photos');
define('TEXT_DEPTH_PROFILE', 'Profil profondeur');
define('TEXT_BACK', 'Retour');
define('TEXT_DEPTH', 'Profondeur');

define('TEXT_SELECT_DEVICE_TYPE', '-- Type appareil --');
define('TEXT_SELECT_DEVICE_NUMBER', '-- N° appareil --');
define('TEXT_SELECT_PROBE_NUMBER', '-- N° sonde --');
define('TEXT_SELECT_INSTRUMENT_TYPE', '-- Type instrum. --');
define('TEXT_SELECT_INSTRUMENT_NUMBER', '-- N° instrum. --');
define('TEXT_SELECT_NATURE_REPERE', '-- Nature Repère --');

define('TEXT_LAST_RA_DATE', 'Date');
define('TEXT_LAST_RA_TOTAL', 'Total');
define('TEXT_LAST_RA_TIPPINGS', 'Bascul.');
define('TEXT_NO_PREVIOUS_DATA', '-');

// -----------------------------------------------
// Field report save - validation & result messages
define('TEXT_DB_CONNECTION_ERROR', 'Erreur de connexion à la base de données !');
define('TEXT_STATION_NOT_EXIST', 'La station n’existe pas.<br>');
define('TEXT_INVALID_READING_DATE_FORMAT', 'La date de lecture n’est pas au bon format : jj-mm-aaaa.<br>');
define('TEXT_INVALID_READING_TIME_FORMAT', 'L’heure de lecture n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_DEVICE_TIME_FORMAT', 'L’heure de l’appareil n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_CASSETTE_INIT_TIME_FORMAT', 'L’heure d’init. de la cassette n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_CASSETTE_PROBE_HEIGHT', 'La hauteur de la sonde de la cassette doit être un nombre.<br>');
define('TEXT_INVALID_CASSETTE_FIRST_TIP_TIME_FORMAT', 'L’heure du 1er basculement de la cassette n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_FIRST_TOTALIZER_VALUE', 'La valeur cumulée initiale du totalisateur doit être un nombre.<br>');
define('TEXT_INVALID_LAST_TOTALIZER_VALUE', 'La valeur cumulée finale du totalisateur doit être un nombre.<br>');
define('TEXT_INVALID_TOTALIZER_TIP_TIME_FORMAT', 'L’heure de basculement du totalisateur n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_TOTALIZER_CUMUL_VALUE', 'La valeur cumulée du totalisateur doit être un nombre.<br>');
define('TEXT_INVALID_RAIN_CUMUL_VALUE', 'La valeur cumulée de pluie doit être un nombre.<br>');
define('TEXT_INVALID_TOTALIZER_RAIN_DIFF_VALUE', 'La différence totalisateur-pluie doit être un nombre.<br>');
define('TEXT_INVALID_RAIN_ADJUSTMENT_TIME_FORMAT', 'L’heure d’ajustement de pluie n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_TIPPINGS_COUNT_VALUE', 'Le nombre de basculements doit être un nombre.<br>');
define('TEXT_INVALID_WATER_LEVEL_TIME_FORMAT', 'L’heure de mesure du niveau d’eau n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_PROBE_HEIGHT_VALUE', 'La hauteur de la sonde doit être un nombre.<br>');
define('TEXT_INVALID_SCALE_HEIGHT_VALUE', 'La hauteur de l’échelle limnimétrique doit être un nombre.<br>');
define('TEXT_INVALID_SCALE_HEIGHT_2_VALUE', 'La hauteur de l’échelle limnimétrique 2 doit être un nombre.<br>');
define('TEXT_INVALID_PROBE_ADJUSTMENT_VALUE', 'La valeur de décalage de la sonde doit être un nombre.<br>');
define('TEXT_INVALID_PROBE_TIME_ADJUSTMENT_FORMAT', 'L’ajustement horaire de la sonde n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_WATER_TABLE_DEPTH_VALUE', 'La profondeur de la nappe doit être un nombre.<br>');
define('TEXT_INVALID_CONDUCTIVITY_VALUE', 'La conductivité doit être un nombre.<br>');
define('TEXT_INVALID_TEMPERATURE_VALUE', 'La température doit être un nombre.<br>');
define('TEXT_INVALID_PIEZO_ADJUSTMENT_VALUE', 'Le décalage piézométrique doit être un nombre.<br>');
define('TEXT_INVALID_PIEZO_PROBE_ADJUSTMENT_VALUE', 'Le décalage de la sonde piézométrique doit être un nombre.<br>');
define('TEXT_INVALID_PIEZO_PROBE_TIME_ADJUSTMENT_FORMAT', 'L’ajustement horaire de la sonde piézométrique n’est pas au bon format : hh:mm:ss ou hh:mm.<br>');
define('TEXT_INVALID_MANUAL_WATER_TABLE_DEPTH_VALUE', 'La profondeur manuelle de la nappe doit être un nombre.<br>');
define('TEXT_INVALID_TOTAL_WELL_DEPTH_VALUE', 'La profondeur totale du puits doit être un nombre.<br>');
define('TEXT_INVALID_GPS_X_VALUE', 'La coordonnée GPS X doit être un nombre.<br>');
define('TEXT_INVALID_GPS_Y_VALUE', 'La coordonnée GPS Y doit être un nombre.<br>');
define('TEXT_NEW_RA_CREATED', 'Le nouveau RA a été créé avec succès');
define('TEXT_STATION_DATE_INFO', 'Station');
define('TEXT_NEW_RA_ACTION', 'Création d’un nouveau RA<br>');
define('TEXT_RA_SUCCESSFULLY_SAVED', 'Le RA a été enregistré avec succès');
define('TEXT_RA_MODIFICATION_ACTION', 'Modification du RA<br>');
define('TEXT_RA_SAVE_ERROR', 'Erreur : le RA n’a pas pu être enregistré');
define('TEXT_SERVER_DATA_ERROR', 'Une erreur est survenue lors de l’envoi des données au serveur.');

define('TEXT_PIEZO_PROFIL_SAVE_WARNING',
    'Les modifications du profil ne sont sauvegardées qu’à l’enregistrement de la fiche RA elle-même. '
  . 'Cliquez sur le bouton Enregistrer de la fiche pour conserver vos modifications.');
define('TEXT_PIEZO_PROFIL_SAVE_REMINDER',
  'Profil modifié — pensez à enregistrer la fiche RA pour conserver vos modifications.');



// --- RA PDF sheet (field report export) ---
define('TEXT_RA_PDF_TITLE', 'Fiche terrain');
define('TEXT_RA_PDF_EDITED_ON', 'Édité le');
define('TEXT_RA_PDF_ENTRY_AGENT', 'Saisi par');
define('TEXT_RA_PDF_STATION', 'Nom station');
define('TEXT_RA_PDF_STATION_CODE', 'Code station');
define('TEXT_RA_PDF_COMMUNE', 'Commune');
define('TEXT_RA_PDF_DATE', 'Date du relevé');
define('TEXT_RA_PDF_TIME', 'Heure du relevé');
define('TEXT_RA_PDF_STATUS', 'Statut');
define('TEXT_RA_PDF_STATUS_FIELD', 'Terrain');
define('TEXT_RA_PDF_STATUS_VALID', 'Validé');
define('TEXT_RA_PDF_OBSERVATIONS', 'Observations');
define('TEXT_RA_PDF_FUTURE_ACTIONS', 'À faire');
define('TEXT_RA_PDF_PARTICIPANTS', 'Participants');
define('TEXT_RA_PDF_YES', 'Oui');
define('TEXT_RA_PDF_NO', 'Non');
define('TEXT_RA_PDF_NO_SELECTION', 'Aucune fiche sélectionnée.');
define('TEXT_RA_PDF_TOO_MANY', 'Trop de fiches sélectionnées (50 maximum). Affinez votre sélection.');
define('TEXT_RA_PDF_SECTION_READING_DEVICE', 'Relevé et appareil');
define('TEXT_RA_PDF_SECTION_DEVICE_STATE', 'État de l\'appareil');
define('TEXT_RA_PDF_SECTION_TOTALIZER', 'Totalisateur');
define('TEXT_RA_PDF_SECTION_CONTROL', 'Contrôle');
define('TEXT_RA_PDF_SECTION_OLD_EQUIPMENT', 'Ancien matériel (cassette)');
define('TEXT_RA_PDF_SECTION_MAINTENANCE', 'Entretien / opérations');
define('TEXT_RA_PDF_CHK_BOUCHAGE', 'Débouchage');
define('TEXT_RA_PDF_CHK_HUILE', 'Huile totalisateur');
define('TEXT_RA_PDF_CHK_DEBROUSS', 'Débroussaillage');
define('TEXT_RA_PDF_CHK_EAUBAT', 'Eau batterie');
define('TEXT_RA_PDF_CHK_TRANSFERT', 'Transfert données');
define('TEXT_RA_PDF_CHK_DELETEMEM', 'Effacement mémoire');
define('TEXT_RA_PDF_SECTION_CONTEXT', 'Contexte');
define('TEXT_RA_PDF_BADGE_VALID', 'Fiche validée');
define('TEXT_RA_PDF_BADGE_NOTVALID', 'Fiche non validée');
define('TEXT_RA_EXPORT_PDF', 'PDF');
define('TEXT_RA_EXPORT_CSV', 'CSV');
define('TEXT_RA_SELECTED', 'sélectionné(s)');
define('TEXT_RA_EXPORT_LIST_PDF', 'Liste PDF');
define('TEXT_RA_LIST_PDF_TITLE', 'Liste des fiches terrain');
define('TEXT_RA_LIST_COL_VALID', 'Validée');
define('TEXT_RA_LIST_COL_STATION_NUM', 'Code station');
define('TEXT_RA_LIST_COL_STATION_NAME', 'Nom station');
define('TEXT_RA_LIST_COL_DATE', 'Date relevé');
define('TEXT_RA_LIST_COL_TIME', 'Heure relevé');
define('TEXT_RA_LIST_COL_COMMENT', 'Commentaire');
define('TEXT_RA_LIST_COL_PLANNED', 'À faire');
define('TEXT_RA_LIST_COL_OPERATORS', 'Intervenant(s)');









// -----------------------------------------------
// DATA MODULE - TIME SERIES VIEWER
define('TEXT_INVALID_DATE_FORMAT_1', ' n’est pas une date valide au format ');
define('TEXT_INVALID_DATE_FORMAT_2', '');
define('TEXT_END_DATE_BEFORE_START', 'La date de fin ne peut pas être antérieure à la date de début.');
define('TEXT_SELECT_AT_LEAST_ONE_STATION', 'Vous devez sélectionner au moins une station');
define('TEXT_SELECT_AT_LEAST_ONE_CHRONIC', 'Au moins une série temporelle doit être sélectionnée.');

// -----------------------------------------------
// Station selection - step 1
define('TEXT_DATA_ACCESS_STEP1', 'Accès aux données - Étape 1 : Sélection des stations');
define('TEXT_STATIONS_TO_SELECT', 'Stations à sélectionner');
define('TEXT_STATIONS', 'station(s)');
define('TEXT_SELECTED_STATIONS', 'Stations sélectionnées');
define('TEXT_SELECT_STATIONS', 'Sélectionner des stations');
define('TEXT_REMOVE_SELECTION', 'Retirer la sélection');
define('TEXT_VALIDATE', 'Etape suivante >>');

// -----------------------------------------------
// Time-series selection - step 2
define('TEXT_DATA_ACCESS_STEP2', 'Accès aux données - Étape 2 : Sélection des séries temporelles');
define('TEXT_CHRONICLE_DETAILS', 'Détails des séries');
define('TEXT_SELECT_PERIOD', 'Sélectionner la période');
define('TEXT_ALL_PERIODS', 'Tout');
define('TEXT_CURRENT_YEAR', 'Année en cours');
define('TEXT_6_MONTHS', '6 mois');
define('TEXT_12_MONTHS', '12 mois');
define('TEXT_2_YEARS', '2 ans');
define('TEXT_5_YEARS', '5 ans');
define('TEXT_10_YEARS', '10 ans');
define('TEXT_20_YEARS', '20 ans');
define('TEXT_START_DATE', 'Date début');
define('TEXT_END_DATE', 'Date fin');
define('TEXT_GRAPH_VISUALIZATION', 'Visualisation graphique');
define('TEXT_COMBINED_GRAPH', 'Graphique combiné');
define('TEXT_EDIT', 'Editer');
define('TEXT_DATA_EXTRACTION', 'Extraction des données');
define('TEXT_EXPORT', 'Exporter');
define('TEXT_DELETE_DATA', 'Supprimer les données');
define('TEXT_DELETE', 'Supprimer');
define('TEXT_LONG_LOADING_WARNING', 'Si vous avez sélectionné beaucoup de données, le chargement peut prendre quelques minutes');
define('TEXT_DATE_ERROR', 'Erreur de date');
define('TEXT_START_BEFORE_END', 'La date de début doit être antérieure à la date de fin');
define('TEXT_INVALID_DATE_FORMAT', 'Au moins une des dates saisies est invalide ou au mauvais format (jj-mm-aaaa : format valide)');
define('TEXT_DELETE_CONFIRMATION', 'Confirmer la suppression pour la période : du ');
define('TEXT_TO', 'au ');

// -----------------------------------------------
// Time-series type selector
define('TEXT_SELECT_ALL', 'Select +/-');
define('TEXT_SELECT_CHRONIC_TYPE', 'Filtrer par type');
define('TEXT_NONE', '-');
define('TEXT_RA', 'RA');
define('TEXT_RA_DESC', 'Rapport d’activité');
define('TEXT_JGE', 'JGE');
define('TEXT_JGE_DESC', 'Jaugeage');
define('TEXT_ETL', 'ETL');
define('TEXT_ETL_DESC', 'Courbe d’étalonnage');
define('TEXT_DIAC', 'DIAC');
define('TEXT_DIAC_DESC', 'Diagraphie de conductivité');
define('TEXT_LAB', 'LAB');
define('TEXT_LAB_DATA', 'Données LAB (Pluie)');
define('TEXT_TOT', 'TOT');
define('TEXT_TOT_DATA', 'Données TOT (Pluie)');
define('TEXT_CHRONIC', 'Série');
define('TEXT_UNIT', 'Unité');
define('TEXT_DATA_COUNT', 'Nb data');
define('TEXT_CHRONICLES', ' séries');
define('TEXT_SELECT_ALL_SHORT', '+/-');
define('TEXT_NO_CHRONIC_FOUND', 'Aucune série temporelle trouvée pour cette période.');

// -----------------------------------------------
// Graph visualisation panel
define('TEXT_DATA_VISUALIZATION', 'Visualisation des données');
define('TEXT_SHOW_GAPS', 'Afficher les lacunes');
define('TEXT_STATS_LINES', 'Repères statistiques');
define('TEXT_RETURN_PERIOD', 'Période de retour (Tr)');
define('TEXT_YEARS', 'ans');
define('TEXT_ZOOM_CONTROL', 'Contrôle du zoom');
define('TEXT_Y_MIN', 'Y min');
define('TEXT_Y_MAX', 'Y max');
define('TEXT_ADJUST_SCALE', 'Ajuster l’échelle');
define('TEXT_FIXED_PERIOD', 'Période fixe');
define('TEXT_YEAR', 'Année');
define('TEXT_MONTH', 'Mois');
define('TEXT_GENERATE', 'Générer');
define('TEXT_CONFIGURATION', 'Configuration');
define('TEXT_TRACES', 'Traces');
define('TEXT_ENLARGE', 'Agrandir');
define('TEXT_GAPS_TABLE', 'Inventaire des lacunes');
define('TEXT_GAPS', 'Lacunes');
define('TEXT_NO_DATA_FOUND', 'Aucune donnée trouvée');
define('TEXT_ZOOM_MOVE_X', 'Zoom/Déplacer X');
define('TEXT_ZOOM_MOVE_Y', 'Zoom/Déplacer Y');
define('TEXT_ADD_DECIMAL', 'Ajouter une décimale');
define('TEXT_REMOVE_DECIMAL', 'Retirer une décimale');
define('TEXT_LOG_SCALE', 'Échelle logarithmique (base 10)');
define('TEXT_LOG_SCALE_SHORT', 'Log');
define('TEXT_GRAPH_FR_CTRLCLICK_HINT',
    'Clic sur un carré jaune ouvre le RA correspondant dans un nouvel onglet');
define('TEXT_EXPORT_CSV', 'Exporter en CSV');
define('TEXT_EXPORT_SVG', 'Exporter en SVG');
define('TEXT_EXPORT_PNG', 'Exporter en PNG');
define('TEXT_NUMERIC_ERROR', 'Erreur : les champs Ymin et Ymax doivent être des nombres');

define('TEXT_ZOOM_BACK',       '↶');
define('TEXT_ZOOM_BACK_TITLE', 'Revenir au zoom précédent');

define('TEXT_GRAPH_META_QUALCODE_TITLE', 'Code qualité');
define('TEXT_GRAPH_META_COVERAGE_TITLE', 'Codes qualité');
define('TEXT_GRAPH_META_NO_QUALCODE',    'Aucun code qualité');
define('TEXT_GRAPH_META_START', 'Date début');
define('TEXT_GRAPH_META_END',   'Date fin');
define('TEXT_GRAPH_META_NBPTS', 'Nb points');
define('TEXT_FILE_QUALIF',      'qualification');
define('TEXT_FILE_GAPS',        'lacunes');

// -----------------------------------------------
// Statistics panel
define('TEXT_STATS_MEAN', 'Moyenne');
define('TEXT_STATS_PERCENTILE_99', 'Percentile (99%)');
define('TEXT_STATS_PERCENTILE_90', 'Percentile (90%)');
define('TEXT_STATS_QUARTILE_75', '3e quartile (75%)');
define('TEXT_STATS_MEDIAN', 'Médiane (50%)');
define('TEXT_STATS_QUARTILE_25', '1er quartile (25%)');
define('TEXT_STATS_PERCENTILE_10', 'Percentile (10%)');
define('TEXT_STATS_PERCENTILE_1', 'Percentile (1%)');

define('TEXT_STATS_COMPLEMENTARY', 'Indicateurs complémentaires');
define('TEXT_STATS_INDICATOR',     'Indicateur');
define('TEXT_STATS_VALUE',         'Valeur');
define('TEXT_STATS_CARD_N',            'Effectif (N)');
define('TEXT_STATS_CARD_COMPLETENESS', 'Complétude');
define('TEXT_STATS_CARD_DATE_MIN',     'Date du minimum');
define('TEXT_STATS_CARD_DATE_MAX',     'Date du maximum');
define('TEXT_STATS_CARD_CV',           'Coefficient de variation');
define('TEXT_STATS_CARD_RANGE',        'Étendue');
define('TEXT_STATS_CARD_CUMUL_YEAR',   'Cumul moyen annuel');
define('TEXT_STATS_CARD_DRY_LE1',      'Sécheresse max (≤ 1 mm)');
define('TEXT_STATS_CARD_DRY_LE5',      'Sécheresse max (≤ 5 mm)');
define('TEXT_STATS_CARD_DRY_LE20',     'Sécheresse max (≤ 20 mm)');
define('TEXT_STATS_CARD_DAYS_UNIT',    'j');

// -----------------------------------------------
// Graph data processing messages
define('TEXT_TOO_MUCH_DATA', 'Le volume de données est trop important');
define('TEXT_RECORDS', 'enregistrements');
define('TEXT_SHORTER_PERIOD', 'La visualisation des données est possible sur une période plus courte');
define('TEXT_STATS_AVAILABLE', 'Les statistiques pour cette série peuvent être calculées ->');
define('TEXT_STATISTICS', 'Statistiques');
define('TEXT_QUALITY_CODE', 'Code qualité');
define('TEXT_MEAN', 'Moyenne');
define('TEXT_PERCENTILE_99', 'Percentile (99%)');
define('TEXT_PERCENTILE_90', 'Percentile (90%)');
define('TEXT_QUARTILE_75', '3e quartile (75%)');
define('TEXT_MEDIAN', 'Médiane');
define('TEXT_QUARTILE_25', '1er quartile (25%)');
define('TEXT_PERCENTILE_10', 'Percentile (10%)');
define('TEXT_PERCENTILE_1', 'Percentile (1%)');
define('TEXT_RETURN_PERIOD_2', '2 ans');
define('TEXT_RETURN_PERIOD_5', '5 ans');
define('TEXT_RETURN_PERIOD_10', '10 ans');
define('TEXT_RETURN_PERIOD_20', '20 ans');
define('TEXT_RETURN_PERIOD_30', '30 ans');
define('TEXT_RETURN_PERIOD_40', '40 ans');
define('TEXT_RETURN_PERIOD_50', '50 ans');
define('TEXT_RETURN_PERIOD_100', '100 ans');
define('TEXT_RA_HEIGHT', 'RA - Lecture manuelle');
define('TEXT_HEIGHT', 'Hauteur (H)');
define('TEXT_FLOW', 'Débit (Q)');
define('TEXT_OBSERVATION', 'Observation');
define('TEXT_MODIFY_CHRONIC', 'Modifier ou corriger la série');

// -----------------------------------------------
// Combined multi-station graph
define('TEXT_GRAPH_TITLE', 'Graphique combiné');
define('TEXT_AXIS_1', 'Axe 1');
define('TEXT_AXIS_2', 'Axe 2');
define('TEXT_STATION_NAME', 'Nom station');
define('TEXT_FLIP', 'Inverser');
define('TEXT_REFRESH_GRAPH', 'Rafraîchir le graphique');
define('TEXT_YMIN', 'Y min');
define('TEXT_YMAX', 'Y max');
define('TEXT_FULLSCREEN', 'Plein écran');
define('TEXT_TOOLS',            'Outils');
define('TEXT_DATA_QUALIF',      'Qualité données');
define('TEXT_EXPORT_GRAPH_CSV', 'Export');
define('TEXT_LOADING_WAIT', 'Si le volume de données est important, veuillez patienter (1 à 2 minutes)');


define('TEXT_GRAPH_HOVER_DATE', 'Date');
define('TEXT_GRAPH_HOVER_VALUE', 'Valeur');

// -----------------------------------------------
// Graph - loading & overflow messages
define('TEXT_GRAPH_TOO_MANY_ROWS', 'Le volume de données à afficher est trop important');
define('TEXT_GRAPH_TOO_MANY_ROWS_SUB', 'pour une bonne expérience utilisateur.');
define('TEXT_GRAPH_RECORDS', 'enregistrements');
define('TEXT_GRAPH_SHORTER_PERIOD', 'La visualisation des données est possible sur une période plus courte');
define('TEXT_GRAPH_STATS_AVAILABLE', 'Les statistiques pour cette série peuvent être calculées ->');
define('TEXT_GRAPH_STATS_LINK', 'STATISTIQUES');
define('TEXT_GRAPH_NO_DATA', 'Aucune donnée trouvée pour cette période');
define('TEXT_GRAPH_LOAD_PACKET',      'Charger la période');
define('TEXT_GRAPH_OR_LOAD_PACKET',   'Ou charger les données par période :');


// -----------------------------------------------
// Graph - hover labels
define('TEXT_GRAPH_HOVER_HEIGHT', 'Hauteur (H)');
define('TEXT_GRAPH_HOVER_FLOW', 'Débit (Q)');
define('TEXT_GRAPH_HOVER_OBS', 'Obs');
define('TEXT_GRAPH_HOVER_QUALCODE', 'Code qualité');
define('TEXT_GRAPH_HOVER_CORRECTION', 'Correction');
define('TEXT_GRAPH_HOVER_CORRECTION_OBS', 'Observation');

// -----------------------------------------------
// Graph - trace names
define('TEXT_CHRON_MEAN', 'Moyenne');

// -----------------------------------------------
// Graph - action buttons
define('TEXT_BTN_EDIT_CHRON', 'Modifier ou corriger la série');
define('TEXT_BTN_EDIT_ALL_DATA',       'Toutes les données');
define('TEXT_BTN_EDIT_ALL_DATA_TITLE', 'Cocher pour éditer la chronique entière, sinon utilise la période actuellement affichée');
define('TEXT_BTN_STATS', 'Statistiques');

// -----------------------------------------------
// Data gap table headers
define('TEXT_LAC_HEADER_TITLE',           'Liste des lacunes');
define('TEXT_LAC_HEADER_CHRON', 'Série');
define('TEXT_LAC_HEADER_DATE_START', 'Date début');
define('TEXT_LAC_HEADER_DATE_END', 'Date fin');

// -----------------------------------------------
// Statistics popup
define('TEXT_STATS_TITLE', 'Statistiques');
define('TEXT_STATS_STATION', 'Station');
define('TEXT_STATS_CHRONIQUE', 'Série temporelle');
define('TEXT_STATS_BTN_GENERAL', 'Données générales');
define('TEXT_STATS_BTN_BYYEAR', 'Synthèse annuelle');
define('TEXT_STATS_BTN_BYMONTH', 'Synthèse mensuelle');
define('TEXT_STATS_BTN_BYDAYS', 'Synthèse quotidienne');
define('TEXT_STATS_BTN_LOWFLOW', 'Analyse des étiages');
define('TEXT_STATS_PERIOD', 'Période évaluée');
define('TEXT_STATS_PERIOD_FROM', 'du');
define('TEXT_STATS_PERIOD_TO', 'au');
define('TEXT_STATS_DURATION', 'Étendue de la période');
define('TEXT_STATS_DATA', 'Données');
define('TEXT_STATS_DURATION_YEAR', 'an');
define('TEXT_STATS_DURATION_YEARS', 'ans');
define('TEXT_STATS_DURATION_MONTH', 'mois');
define('TEXT_STATS_DURATION_MONTHS', 'mois');
define('TEXT_STATS_DURATION_DAY', 'jour');
define('TEXT_STATS_DURATION_DAYS', 'jours');
define('TEXT_STATS_DURATION_AND', 'et');

define('TEXT_STATS_CLOSE', 'Fermer');
define('TEXT_STATS_CLOSE_X', 'X');

// -----------------------------------------------
// ABRÉVIATIONS DES MOIS
 
define('TEXT_MONTH_SHORT_JAN', 'Jan.');
define('TEXT_MONTH_SHORT_FEB', 'Fév.');
define('TEXT_MONTH_SHORT_MAR', 'Mar.');
define('TEXT_MONTH_SHORT_APR', 'Avr.');
define('TEXT_MONTH_SHORT_MAY', 'Mai');
define('TEXT_MONTH_SHORT_JUN', 'Juin');
define('TEXT_MONTH_SHORT_JUL', 'Juil.');
define('TEXT_MONTH_SHORT_AUG', 'Août');
define('TEXT_MONTH_SHORT_SEP', 'Sep.');
define('TEXT_MONTH_SHORT_OCT', 'Oct.');
define('TEXT_MONTH_SHORT_NOV', 'Nov.');
define('TEXT_MONTH_SHORT_DEC', 'Déc.');
 
 
// -----------------------------------------------
// STATISTIQUES GÉNÉRALES
 
define('TEXT_STATS_MEDIAN_LABEL',         'médian');
define('TEXT_STATS_MINIMUM',              'Minimum');
define('TEXT_STATS_MAXIMUM',              'Maximum');
define('TEXT_STATS_STD_DEV',              'Écart-type');
define('TEXT_STATS_CUMUL',                'Cumul');
define('TEXT_STATS_YEAR',                 'Année');
define('TEXT_STATS_DAY',                  'Jour');
define('TEXT_STATS_STATISTIC',            'Statistique');
define('TEXT_STATS_COMPUTED_VALUES',      'Valeurs calculées');
 
define('TEXT_STATS_PERCENTILE_5',         '5ème Percentile');
define('TEXT_STATS_PERCENTILE_95',        '95ème Percentile');
 
define('TEXT_STATS_INTERANNUAL_MEDIAN',   'Médiane Interannuelle');
 
define('TEXT_STATS_ANNUAL_SUMMARY',       'Synthèse des données annuelles');
define('TEXT_STATS_MONTHLY_SUMMARY',      'Synthèse des données mensuelles');
define('TEXT_STATS_DAILY_SUMMARY',        'Synthèse des données quotidiennes');
 
define('TEXT_STATS_MONTHLY_CHART',        'Graphique de la synthèse mensuelle');
define('TEXT_STATS_ANNUAL_CUMUL_CHART',   'Graphique des cumuls intra-annuels');
define('TEXT_STATS_ANNUAL_SUMMARY_CHART', 'Graphique de la synthèse intra-annuelle');
 
define('TEXT_STATS_GLOBAL_DATA',          'Données Générales');
 
define('TEXT_STATS_NON_EXCEEDANCE_FREQ',  'Fréquence de non-dépassement (%)');
define('TEXT_STATS_NON_EXCEEDANCE_PROB',  'Proba. de non-dépassement');
 
define('TEXT_STATS_OBSERVED_DATA',        'Données observées');
define('TEXT_STATS_CI_95',                'Intervalle de confiance 95%');
define('TEXT_STATS_CI_LOW',               'IC bas');
define('TEXT_STATS_CI_HIGH',              'IC haut');
 
 
// -----------------------------------------------
// LOI DE GUMBEL (TEMPS DE RETOUR)
 
define('TEXT_STATS_GUMBEL_LAW',     'Loi de Gumbel');
define('TEXT_STATS_GUMBEL_PARAMS',  'Paramètres de la loi de Gumbel (IC 95%)');
define('TEXT_STATS_GUMBEL_CHART',   'Ajustement de la loi de Gumbel - Calcul des temps de retour');
define('TEXT_STATS_GUMBEL_U',       'Position (u)');
define('TEXT_STATS_GUMBEL_A',       'Échelle (a)');
 
define('TEXT_STATS_ESTIMATE',       'Estimation');
define('TEXT_STATS_STD_ERROR',      'Erreur Type');
 
define('TEXT_STATS_EXTREME_RETURN', 'Temps de retour des évènements extrèmes max.');
define('TEXT_STATS_YEARS',          'ans');
 
define('TEXT_STATS_RETURN_2Y',      '2 ans');
define('TEXT_STATS_RETURN_5Y',      '5 ans');
define('TEXT_STATS_RETURN_10Y',     '10 ans');
define('TEXT_STATS_RETURN_20Y',     '20 ans');
define('TEXT_STATS_RETURN_30Y',     '30 ans');
define('TEXT_STATS_RETURN_40Y',     '40 ans');
define('TEXT_STATS_RETURN_50Y',     '50 ans');
define('TEXT_STATS_RETURN_100Y',    '100 ans');
 
 
// -----------------------------------------------
// NAVIGATION ANNÉES (VUE QUOTIDIENNE)
 
define('TEXT_STATS_PREV_YEAR', 'Année précédente');
define('TEXT_STATS_NEXT_YEAR', 'Année suivante');

 
 
// -----------------------------------------------
// MÉTRIQUES D'ÉTIAGE
define('TEXT_STATS_METHODOLOGY',   'Méthodologie');
define('TEXT_LOWFLOW_HELP_TITLE',  "Métriques d'étiage — note méthodologique");
define('TEXT_LOWFLOW_CHARTS_TITLE', 'Graphiques');
define('TEXT_LOWFLOW_PDF_SUMMARY', 'PDF synthèse');
define('TEXT_LOWFLOW_PDF_FULL',    'PDF développé');
 
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
 
define('TEXT_LOWFLOW_METRIC_VALUES',   'Valeur des métriques');
define('TEXT_LOWFLOW_PCT_MODULE',      '% du module interannuel');
define('TEXT_LOWFLOW_NON_EXCEEDANCE',  'Fréq. non-dépassement (% annuel)');
 
define('TEXT_LOWFLOW_SERIES_TOO_SHORT', 'Attention : La chronique de données n’est pas suffisamment longue pour calculer des métriques intégrant des temps de retour.');
define('TEXT_LOWFLOW_CDC_TITLE',        'Courbe des Débits Classés - Métriques d’Étiage');
 
define('TEXT_LOWFLOW_FREQUENCY',       'Fréquence');
define('TEXT_LOWFLOW_FLOW_CDC',        'Débits Classés');
define('TEXT_LOWFLOW_DAILY_FLOW_AXIS', 'Débit moyen journalier (m³/s)');
define('TEXT_LOWFLOW_FLOW_AXIS',       'Débit (m³/s)');
define('TEXT_LOWFLOW_FLOW_LABEL',      'Débits');
 
define('TEXT_LOWFLOW_ANNEX_METRICS',       'Annexe : Détails du calcul des métriques d’étiage');
define('TEXT_LOWFLOW_ANNEX_ANNUAL_MINIMA', 'Annexe : Résultats des Débits Minimaux Annuels (en m³/s)');
 
define('TEXT_LOWFLOW_LOGNORMAL_PARAMS',  'Paramètres de la loi log-normale (IC 95%)');
define('TEXT_LOWFLOW_LOGNORMAL_LAW',     'Loi log-normale');
define('TEXT_LOWFLOW_OBSERVED_POINTS',   'Points observés');
 
define('TEXT_LOWFLOW_LOG_MU',    'Moyenne-log-mu');
define('TEXT_LOWFLOW_LOG_SIGMA', 'Écart-type log-sigma');
 
define('TEXT_LOWFLOW_RESULTS_TITLE', 'Résultats');
define('TEXT_LOWFLOW_N_POINTS',      'Nombre de points retenus');
 
define('TEXT_LOWFLOW_BIENNIAL',     'Biennale (médiane)');
define('TEXT_LOWFLOW_QUINQUENNIAL', 'Quinquennale');
define('TEXT_LOWFLOW_DECENNIAL',    'Décennale');
define('TEXT_LOWFLOW_VICENNIAL',    'Vicennale');
 
define('TEXT_LOWFLOW_QMNA_LABEL',  'QMNA');
define('TEXT_LOWFLOW_DCE_LABEL',   'DCE (Q355)');
define('TEXT_LOWFLOW_VCN3_LABEL',  'VCN3');
define('TEXT_LOWFLOW_VCN7_LABEL',  'VCN7');
define('TEXT_LOWFLOW_VCN10_LABEL', 'VCN10');
define('TEXT_LOWFLOW_VCN30_LABEL', 'VCN30');
 
 
// -----------------------------------------------
// INDEX / RÉSUMÉ STATION
 
define('TEXT_INDEX_ANNUAL_STAT_LABEL', 'annuel');
define('TEXT_INDEX_BETWEEN',           'entre');
define('TEXT_INDEX_AND',               'et');
 
define('TEXT_INDEX_DATA_UNUSABLE',     'Données non exploitables');
define('TEXT_INDEX_DATA_MISSING',      'Graphique non disponible');
 
define('TEXT_INDEX_MEAN_FLOW',     'Débit Moyen');
define('TEXT_INDEX_CUMUL',         'Cumul');
define('TEXT_INDEX_MONTHLY_LABEL', 'Mensuel');

// EXPORT
define('TEXT_ACTION_EXPORT', 'Export des données');

// -----------------------------------------------
// Export page - UI labels
define('TEXT_EXPORT_PAGE_TITLE', 'Export des données de séries temporelles');
define('TEXT_EXPORT_COMPIL_LABEL', 'Génération du fichier');
define('TEXT_EXPORT_PROGRESS_LABEL', 'Avancement du traitement');
define('TEXT_EXPORT_TEXTAREA_DATETIME', 'Date Heure');
define('TEXT_EXPORT_TEXTAREA_WAITING', 'Compilation du fichier en cours - Veuillez patienter...');
define('TEXT_EXPORT_BTN_DOWNLOAD', 'Télécharger les fichiers générés');
define('TEXT_EXPORT_COPYRIGHT', '&copy; 2024 Vai-Natura. Tous droits réservés.');

// -----------------------------------------------
// Export - JS progress messages (PHP-injected)
define('TEXT_EXPORT_JS_ALL_DONE', 'Tous les fichiers ont été générés - Temps total de traitement');
define('TEXT_EXPORT_JS_SEC', 'sec.');
define('TEXT_EXPORT_JS_NB_DATA', 'Nb données');
define('TEXT_EXPORT_JS_COMPRESSING', 'Compression des fichiers - Veuillez patienter...');
define('TEXT_EXPORT_JS_TIME', 'Temps');

// -----------------------------------------------
// Archive file download
define('TEXT_COMPRESS_READY', 'Les fichiers sont prêts pour le téléchargement (format tar)');
define('TEXT_COMPRESS_FILE', 'Fichier');
define('TEXT_COMPRESS_SIZE', 'Taille');
define('TEXT_COMPRESS_SIZE_UNIT', 'Mo');

// -----------------------------------------------
// Export file content - CSV title lines
define('TEXT_CSV_TITLE_RA', 'Rapport d’activité - Station');
define('TEXT_CSV_TITLE_JGE', 'Jaugeages - Station');
define('TEXT_CSV_TITLE_REP', 'Repères piézométriques - Station');
define('TEXT_CSV_TITLE_CTE', 'Caractéristiques du puits - Station');

// -----------------------------------------------
// CSV column headers - shared
define('TEXT_CSV_COL_STATION_NUM', 'Numéro station');
define('TEXT_CSV_COL_STATION_NAME', 'Nom station');
define('TEXT_CSV_COL_DATE', 'Date');
define('TEXT_CSV_COL_OBS', 'Observation');
define('TEXT_CSV_COL_AGENTS', 'Agents');
define('TEXT_CSV_COL_FILE_NAME', 'Nom fichier');
define('TEXT_CSV_COL_FILE_OBS', 'Observation fichier');
define('TEXT_CSV_COL_PRE_EVENT', 'Avant événement');
define('TEXT_CSV_COL_EVENT', 'Événement notable');
define('TEXT_CSV_COL_FUTURE', 'Prévu');
define('TEXT_CSV_COL_COORD_X', 'Coord X');
define('TEXT_CSV_COL_COORD_Y', 'Coord Y');
define('TEXT_CSV_COL_QUALITY', 'Code qualité');

// -----------------------------------------------
// CSV column headers - rain gauge field report
define('TEXT_CSV_COL_PLU_RELEVE_DATE', 'Date relevé');
define('TEXT_CSV_COL_PLU_RELEVE_HEURE', 'Heure relevé');
define('TEXT_CSV_COL_PLU_APP_K7', 'N° cassette appareil');
define('TEXT_CSV_COL_PLU_APP_TYPE', 'Type appareil');
define('TEXT_CSV_COL_PLU_APP_NUM', 'N° appareil');
define('TEXT_CSV_COL_PLU_APP_HEURE', 'Heure appareil');
define('TEXT_CSV_COL_PLU_TOT_TYPE', 'Type totalisateur');
define('TEXT_CSV_COL_PLU_TOT_ARRIVE', 'Cumul arrivée (mm)');
define('TEXT_CSV_COL_PLU_TOT_DEPART', 'Cumul départ (mm)');
define('TEXT_CSV_COL_PLU_TOT_HEURE', 'Heure basculement');
define('TEXT_CSV_COL_PLU_DUR_JJ', 'Durée enregistr. JJ');
define('TEXT_CSV_COL_PLU_DUR_HH', 'Durée enregistr. HH');
define('TEXT_CSV_COL_PLU_DUR_MM', 'Durée enregistr. MM');
define('TEXT_CSV_COL_PLU_LAST_JJ', 'Dernier enregistr. JJ');
define('TEXT_CSV_COL_PLU_LAST_HH', 'Dernier enregistr. HH');
define('TEXT_CSV_COL_PLU_LAST_MM', 'Dernier enregistr. MM');
define('TEXT_CSV_COL_PLU_DUR_SS', 'SS');
define('TEXT_CSV_COL_PLU_LAST_SS', 'SS');
define('TEXT_CSV_COL_PLU_NB_BASC', 'Nb basculements');
define('TEXT_CSV_COL_PLU_NB_OCTET', 'Nb octets');
define('TEXT_CSV_COL_PLU_BAT_NUM', 'N° batterie');
define('TEXT_CSV_COL_PLU_BAT_TENSION', 'Tension batterie');
define('TEXT_CSV_COL_PLU_K7_NUM', 'N° cassette');
define('TEXT_CSV_COL_PLU_K7_INIT', 'Heure init. cassette');
define('TEXT_CSV_COL_PLU_K7_FIRST_BASC', 'Heure 1er basculement');
define('TEXT_CSV_COL_PLU_CUMUL_TOT', 'Cumul totalisateur');
define('TEXT_CSV_COL_PLU_CUMUL_PLU', 'Cumul pluviomètre');
define('TEXT_CSV_COL_PLU_DIFF', 'Différence : TOT - Pluie (mm)');
define('TEXT_CSV_COL_PLU_CALAGE_HEURE', 'Calage horaire (hh:mm)');
define('TEXT_CSV_COL_PLU_TEST_AUGET', 'Test auget');
define('TEXT_CSV_COL_PLU_BOUCHAGE', 'Action colmatage');
define('TEXT_CSV_COL_PLU_DEBROUSSAILLAGE', 'Action débroussaillage');
define('TEXT_CSV_COL_PLU_EAU_BAT', 'Action eau batterie');
define('TEXT_CSV_COL_PLU_HUILE_TOT', 'Action huile totalisateur');
define('TEXT_CSV_COL_PLU_TRANSFERT', 'Action transfert');
define('TEXT_CSV_COL_PLU_MEM_EFFACEE', 'Action mémoire effacée');
define('TEXT_CSV_COL_PLU_COMMENTAIRE', 'Commentaire');
define('TEXT_CSV_COL_PLU_NOM_OE2', 'Nom OE2');

// -----------------------------------------------
// CSV column headers - hydrometric field report
define('TEXT_CSV_COL_HYD_COTE_HEURE', 'Heure niveau eau');
define('TEXT_CSV_COL_HYD_COTE_SONDE', 'Niveau eau - lecture sonde');
define('TEXT_CSV_COL_HYD_COTE_ECHL', 'Niveau eau - lecture échelle');
define('TEXT_CSV_COL_HYD_COTE_ECHL2', 'Niveau eau - échelle secondaire');
define('TEXT_CSV_COL_HYD_NUM_SONDE', 'N° sonde');
define('TEXT_CSV_COL_HYD_NB_OCTET', 'Nb octets % Mém');
define('TEXT_CSV_COL_HYD_BAT_NUM', 'N° batterie');
define('TEXT_CSV_COL_HYD_BAT_TENSION', 'Tension batterie');
define('TEXT_CSV_COL_HYD_K7_NUM', 'Nouvelle cassette n°');
define('TEXT_CSV_COL_HYD_K7_INIT', 'Heure init. nouvelle cassette');
define('TEXT_CSV_COL_HYD_K7_SONDE', 'Hauteur sonde nouvelle cassette');
define('TEXT_CSV_COL_HYD_CTRL_HECH_HSPI', 'Contrôle : échelle vs sonde (Hech−Hspi)');
define('TEXT_CSV_COL_HYD_CTRL_RECAL_SONDE', 'Action recalibrage sonde');
define('TEXT_CSV_COL_HYD_CTRL_RECAL_DATA', 'Correction décalage appliquée');
define('TEXT_CSV_COL_HYD_PURGE', 'Action purge orifice/sonde');
define('TEXT_CSV_COL_HYD_JAUGEAGE', 'Action jaugeage');
define('TEXT_CSV_COL_HYD_DEBROUSSAILLAGE', 'Action débroussaillage');
define('TEXT_CSV_COL_HYD_EAU_BAT', 'Action eau batterie');
define('TEXT_CSV_COL_HYD_TRANSFERT', 'Action transfert');
define('TEXT_CSV_COL_HYD_MEM_EFFACEE', 'Action mémoire effacée');

// -----------------------------------------------
// CSV column headers - piezometric field report
define('TEXT_CSV_COL_PIE_SONDE_FIXE_TYPE', 'Sonde fixe - Type');
define('TEXT_CSV_COL_PIE_SONDE_FIXE_NUM', 'Sonde fixe - N°');
define('TEXT_CSV_COL_PIE_SONDE_FIXE_HEURE', 'Sonde fixe - Heure');
define('TEXT_CSV_COL_PIE_SONDE_MAN_TYPE', 'Sonde manuelle - Type');
define('TEXT_CSV_COL_PIE_SONDE_MAN_NUM', 'Sonde manuelle - N°');
define('TEXT_CSV_COL_PIE_MESURE_TOIT_M', 'Mesure sonde - profondeur nappe (m)');
define('TEXT_CSV_COL_PIE_MESURE_COND', 'Mesure sonde - Conductivité');
define('TEXT_CSV_COL_PIE_MESURE_TEMP', 'Mesure sonde - Température');
define('TEXT_CSV_COL_PIE_MAN_TOIT_M', 'Mesure manuelle - profondeur nappe (m)');
define('TEXT_CSV_COL_PIE_MAN_TOIT_CM', 'Mesure manuelle - profondeur nappe (cm)');
define('TEXT_CSV_COL_PIE_PROF_OUV', 'Profondeur totale (m)');
define('TEXT_CSV_COL_PIE_CTRL_DIFF', 'Contrôle - décalage (manuel − sonde)');
define('TEXT_CSV_COL_PIE_CTRL_RECAL_SONDE', 'Contrôle - recalibrage sonde');
define('TEXT_CSV_COL_PIE_CTRL_RECAL_HEURE', 'Contrôle - recalibrage horaire');
define('TEXT_CSV_COL_PIE_MEM_NB', 'Nb enregistrements mémoire');
define('TEXT_CSV_COL_PIE_MEM_EFFACEE', 'Mémoire effacée');
define('TEXT_CSV_COL_PIE_BAT', 'Batterie % Mém');
define('TEXT_CSV_COL_PIE_NATURE_REPERE', 'Type repère');
define('TEXT_CSV_COL_PIE_Z_REPERE', 'Z (mNGNC)');
define('TEXT_CSV_COL_PIE_POMPAGE_ENCOURS', 'Pompage en cours');
define('TEXT_CSV_COL_PIE_POMPAGE_PROCHE', 'Pompage proche');
define('TEXT_CSV_COL_PIE_PLUIE_CRUE', 'Pluie et/ou crue');
define('TEXT_CSV_COL_PIE_TEMPS_SEC', 'Période sèche');

// -----------------------------------------------
// CSV column headers - streamflow gaugings
define('TEXT_CSV_COL_JGE_START_HEURE', 'Heure début');
define('TEXT_CSV_COL_JGE_START_H_ECHL', 'Niveau eau début - échelle (cm)');
define('TEXT_CSV_COL_JGE_END_HEURE', 'Heure fin');
define('TEXT_CSV_COL_JGE_END_H_ECHL', 'Niveau eau fin - échelle (cm)');
define('TEXT_CSV_COL_JGE_HMOY', 'Hauteur moy. H (cm)');
define('TEXT_CSV_COL_JGE_Q', 'Débit Q (m³/s)');
define('TEXT_CSV_COL_JGE_SECT', 'Section mouillée (m²)');
define('TEXT_CSV_COL_JGE_VMOY', 'Vitesse moyenne (m/s)');
define('TEXT_CSV_COL_JGE_VSURF', 'Vitesse surface (m/s)');
define('TEXT_CSV_COL_JGE_RH', 'Rayon hydraulique (m)');
define('TEXT_CSV_COL_JGE_PROFMOY', 'Profondeur moyenne (m)');
define('TEXT_CSV_COL_JGE_NBVERT', 'Nb verticales');
define('TEXT_CSV_COL_JGE_MOULINET', 'Moulinet (Price / OTT)');
define('TEXT_CSV_COL_JGE_HELICE', 'Référence hélice');
define('TEXT_CSV_COL_JGE_COORD_GPS_X', 'Coord GPS X');
define('TEXT_CSV_COL_JGE_COORD_GPS_Y', 'Coord GPS Y');
define('TEXT_CSV_COL_JGE_COORD_SIG_X', 'Coord SIG X');
define('TEXT_CSV_COL_JGE_COORD_SIG_Y', 'Coord SIG Y');

// -----------------------------------------------
// CSV column headers - piezometric benchmarks
define('TEXT_CSV_COL_REP_NATURE', 'Type repère');
define('TEXT_CSV_COL_REP_CODE', 'Code repère');
define('TEXT_CSV_COL_REP_Z', 'Z repère');
define('TEXT_CSV_COL_REP_PRECISION', 'Précision repère');
define('TEXT_CSV_COL_REP_DATE_START', 'Validité début');
define('TEXT_CSV_COL_REP_DATE_END', 'Validité fin');
define('TEXT_CSV_COL_REP_NATURE_GEO1', 'Repère géomètre 1 - type');
define('TEXT_CSV_COL_REP_Z_GEO1', 'Z géomètre 1');
define('TEXT_CSV_COL_REP_NATURE_GEO2', 'Repère géomètre 2 - type');
define('TEXT_CSV_COL_REP_Z_GEO2', 'Z géomètre 2');

// -----------------------------------------------
// CSV column headers - borehole characteristics
define('TEXT_CSV_COL_CTE_PROF', 'Profondeur');
define('TEXT_CSV_COL_CTE_MAT_TETE', 'Matériau tête');
define('TEXT_CSV_COL_CTE_DIM_EXT', 'Dimension extérieure');
define('TEXT_CSV_COL_CTE_MAT_TUB', 'Matériau tubage');
define('TEXT_CSV_COL_CTE_DIM_TUB', 'Dimension tubage (mm)');
define('TEXT_CSV_COL_CTE_MAT_DALLE', 'Matériau dalle');
define('TEXT_CSV_COL_CTE_DIM_DALLE', 'Dimension dalle');
define('TEXT_CSV_COL_CTE_CAPOT', 'Couvercle présent');
define('TEXT_CSV_COL_CTE_DIST_CAPOT_TUBE', 'Distance couvercle/tube');
define('TEXT_CSV_COL_CTE_DIST_TUBE_DALLE', 'Distance tube/dalle');
define('TEXT_CSV_COL_CTE_DIST_DALLE_SOL', 'Distance dalle/sol');
define('TEXT_CSV_COL_CTE_ETAT', 'État');
define('TEXT_CSV_COL_CTE_ACTIVITE', 'Actif');
define('TEXT_CSV_COL_CTE_USAGE', 'Usage');
define('TEXT_CSV_COL_CTE_EQUIPEMENT', 'Équipement');
define('TEXT_CSV_COL_CTE_SCHEMA', 'Schéma');
define('TEXT_CSV_COL_CTE_PROTECTION', 'Protection');

// -----------------------------------------------
// CSV column headers - conductivity diagraphy
define('TEXT_CSV_COL_DIAC_PROFONDEUR', 'Profondeur');
define('TEXT_CSV_COL_DIAC_CONDUCTIVITE', 'Conductivité');
define('TEXT_CSV_COL_DIAC_TEMPERATURE', 'Température');

// -----------------------------------------------
// IMPORT MODULE
define('TEXT_IMPORT_PAGE_TITLE', 'Import des données - Étape 1 : Sélection des fichiers');
define('TEXT_IMPORT_PROCESS_LABEL', 'Processus de traitement');
define('TEXT_IMPORT_INSTRUCTIONS_LINK', 'Instructions d’import');
define('TEXT_IMPORT_BTN_UPLOAD', 'Charger les fichiers');
define('TEXT_IMPORT_BTN_IMPORT', 'Importer les données');

// -----------------------------------------------
// Import page - table headers
define('TEXT_IMPORT_TH_FILE', 'Fichier');
define('TEXT_IMPORT_TH_STATION', 'Station');
define('TEXT_IMPORT_TH_CHRON', 'Série');
define('TEXT_IMPORT_TH_UNIT', 'Unité');
define('TEXT_IMPORT_TH_SELECT', 'Sélection +/-');

// -----------------------------------------------
// Import - JS progress messages (PHP-injected)
define('TEXT_IMPORT_JS_FILE_LIST', 'Liste des fichiers sélectionnés');
define('TEXT_IMPORT_JS_UPLOAD_START', '-- CHARGEMENT DES FICHIERS EN COURS --');
define('TEXT_IMPORT_JS_UPLOAD_DONE', '-- CHARGEMENT DES FICHIERS TERMINÉ --');
define('TEXT_IMPORT_JS_NO_FILE', 'Aucun fichier sélectionné');
define('TEXT_IMPORT_JS_DATA_START', '-- DÉBUT - ENREGISTREMENT DES DONNÉES --');
define('TEXT_IMPORT_JS_DATA_DONE', '-- FIN - ENREGISTREMENT DES DONNÉES --');
define('TEXT_IMPORT_JS_PARSE_ERROR', 'Erreur d’analyse de la réponse serveur : ');
define('TEXT_IMPORT_JS_UPLOAD_ERROR', 'Erreur de chargement : ');
define('TEXT_IMPORT_JS_WAIT_UPLOAD', 'Chargement des fichiers - Veuillez patienter...');
define('TEXT_IMPORT_JS_WAIT_IMPORT', 'Enregistrement des données - Veuillez patienter...');

// -----------------------------------------------
// File loader - status messages
define('TEXT_LOAD_FILE_LABEL', 'Fichier');
define('TEXT_LOAD_FILE_CONFORM', ' - Valide.');
define('TEXT_LOAD_FILE_STATION_LABEL', 'Station');
define('TEXT_LOAD_FILE_CHRON_LABEL', 'Série');

// -----------------------------------------------
// File loader - error messages
define('TEXT_LOAD_ERR_NO_CHRON', 'Aucune série de données enregistrée n’a pu être identifiée dans le nom du fichier.');
define('TEXT_LOAD_ERR_NO_STATION', 'Aucune station n’a pu être identifiée dans le nom du fichier.');
define('TEXT_LOAD_ERR_BAD_EXT', 'Type de fichier invalide. Extension non enregistrée : ');
define('TEXT_LOAD_ERR_MOVE', 'Erreur lors du déplacement du fichier chargé.');
define('TEXT_LOAD_ERR_UPLOAD', 'Erreur de chargement : ');
define('TEXT_LOAD_ERR_NO_FILE', 'Aucun fichier reçu.');

// -----------------------------------------------
// File loader - table row tooltips
define('TEXT_LOAD_TIP_DATA_OK', 'Données chargées');
define('TEXT_LOAD_TIP_DATA_FAIL', 'Aucune donnée n’a pu être chargée');
define('TEXT_LOAD_TIP_DATA_WAIT', 'Traitement des données en cours');
define('TEXT_LOAD_TIP_DETAIL', 'Détails de l’import');
define('TEXT_LOAD_TIP_GRAPH', 'Voir les données importées');

// -----------------------------------------------
// File loader - series type labels (static fallbacks)
define('TEXT_CHRON_TYPE_RA', 'Rapport d’activité');
define('TEXT_CHRON_TYPE_JGE', 'Jaugeage');
define('TEXT_CHRON_TYPE_ETL', 'Courbe d’étalonnage (H→Q)');
define('TEXT_CHRON_TYPE_REP', 'Repère piézométrique');

// -----------------------------------------------
// Import processing - progress log
define('TEXT_IMPORT_CHRON_FILE', 'Fichier');
define('TEXT_IMPORT_CHRON_STATION', 'Station');
define('TEXT_IMPORT_CHRON_SERIES', 'Série');
define('TEXT_IMPORT_CHRON_DONE', 'Traitement du fichier terminé.');
define('TEXT_IMPORT_CHRON_DURATION', 'Temps de traitement');
define('TEXT_IMPORT_CHRON_SEC', 'sec.');
define('TEXT_IMPORT_CHRON_NB_IMPORTED', 'Enregistrements importés');
define('TEXT_IMPORT_CHRON_NB_ERRORS', 'Erreurs');
define('TEXT_IMPORT_CHRON_NB_DELETED', 'Enregistrements supprimés');
define('TEXT_IMPORT_CHRON_DATE_START', 'Début de la série');
define('TEXT_IMPORT_CHRON_DATE_END', 'Fin de la série');
define('TEXT_IMPORT_CHRON_FAIL', 'Les données n’ont pas pu être importées.');
define('TEXT_IMPORT_CHRON_DB_ERROR', 'Erreur d’insertion en base de données : ');

// -----------------------------------------------
// Import processing - warning/error detail lines (.txt log)
define('TEXT_IMPORT_WARN_FILE', 'Erreurs de format de fichier (structure invalide) : ');
define('TEXT_IMPORT_WARN_DATE', 'Erreurs de format de date (colonne 1) - non reconnue ou vide : ');
define('TEXT_IMPORT_WARN_VALEUR', 'Erreurs de format de valeur (colonne 2) - invalide ou vide : ');
define('TEXT_IMPORT_WARN_QUALITE', 'Avertissements de code qualité (colonne 3) - non enregistré ou vide : ');
define('TEXT_IMPORT_WARN_LINE', 'ligne(s) concernée(s).');
define('TEXT_IMPORT_WARN_LINE_Q', 'ligne(s) concernée(s).');

// -----------------------------------------------
// Import processing - action log
define('TEXT_ACTION_IMPORT', 'Import de données - Fichier');
define('TEXT_ACTION_IMPORT_STATION', 'Station');

// -----------------------------------------------
// RA import - progress log
define('TEXT_IMPORT_RA_NB_IMPORTED', 'Rapports d’activité importés');
define('TEXT_IMPORT_RA_SERIES_LABEL', 'RA');

// -----------------------------------------------
// RA import - action log
define('TEXT_ACTION_IMPORT_RA', 'Import de données de rapport d’activité - Fichier');

// -----------------------------------------------
// TOT import - progress log
define('TEXT_IMPORT_TOT_SERIES_LABEL', 'TOT');
define('TEXT_IMPORT_TOT_INFO_LABEL', 'Information(s)');
define('TEXT_IMPORT_TOT_DATA_UPDATED', 'Données mises à jour avec succès.');
define('TEXT_IMPORT_TOT_DB_ERROR', 'Erreur d’exécution de transaction : ');

// -----------------------------------------------
// TOT import - validation errors
define('TEXT_IMPORT_TOT_ERR_DATE', "La date '%s' n’est pas au format valide jj/mm/aaaa hh:mm:ss.");
define('TEXT_IMPORT_TOT_ERR_NO_DATE', 'Au moins une date est manquante.');
define('TEXT_IMPORT_TOT_ERR_COL2', 'Au moins une valeur de la colonne 2 n’est pas numérique.');
define('TEXT_IMPORT_TOT_ERR_COL3', 'Au moins une valeur de la colonne 3 n’est pas numérique.');
define('TEXT_IMPORT_TOT_ERR_COL4', 'Au moins une valeur de la colonne 4 n’est pas numérique.');

// -----------------------------------------------
// TOT import - action log
define('TEXT_ACTION_IMPORT_TOT', 'Import de données TOT - Fichier');

// -----------------------------------------------
// Streamflow gauging import - progress log
define('TEXT_IMPORT_JGE_SERIES_LABEL', 'JAUGEAGE');
define('TEXT_IMPORT_JGE_NB_IMPORTED', 'Mesures de jaugeage importées');
define('TEXT_IMPORT_JGE_ERR_DATE', 'Ligne %d : date invalide.');
define('TEXT_ACTION_IMPORT_JGE', 'Import de données de jaugeage - Fichier');

// -----------------------------------------------
// Rating curve (ETL) import - progress log
define('TEXT_IMPORT_ETL_SERIES_LABEL', 'ETL');
define('TEXT_IMPORT_ETL_DATA_UPDATED', 'Données mises à jour avec succès.');
define('TEXT_IMPORT_ETL_DB_ERROR', 'Erreur d’exécution de transaction : ');

// -----------------------------------------------
// Rating curve (ETL) import - validation errors
define('TEXT_IMPORT_ETL_ERR_ODD_COLS', 'Le fichier CSV doit avoir un nombre pair de colonnes (paires H/Q).');
define('TEXT_IMPORT_ETL_ERR_HQ_MISMATCH', 'Incohérence dans le nombre de paires hauteur/débit (H/Q) pour la période %s – %s.');
define('TEXT_IMPORT_ETL_ERR_DATE', "La date '%s' n’est pas au format valide jj/mm/aaaa hh:mm:ss.");
define('TEXT_IMPORT_ETL_ERR_HAUTEUR', 'Certaines valeurs de hauteur ne sont pas numériques : index %d - valeur %s.');
define('TEXT_IMPORT_ETL_ERR_DEBIT', 'Certaines valeurs de débit ne sont pas numériques.');

// -----------------------------------------------
// Rating curve (ETL) import - action log
define('TEXT_ACTION_IMPORT_ETL', 'Import de données ETL - Fichier');

// -----------------------------------------------
// Piezometric benchmark import - progress log
define('TEXT_IMPORT_REP_SERIES_LABEL', 'REP');
define('TEXT_IMPORT_REP_NB_IMPORTED', 'Repères piézométriques importés');
define('TEXT_IMPORT_REP_ERR_DATE', 'Ligne %d : date invalide.');

// -----------------------------------------------
// Piezometric benchmark import - action log
define('TEXT_ACTION_IMPORT_REP', 'Import de données de repères piézométriques - Fichier');

// -----------------------------------------------
// Station list - page title & header
define('TEXT_STATION_LIST_TITLE', 'Liste des stations de mesure');
define('TEXT_STATION_LIST_NEW', 'Nouvelle station');
define('TEXT_STATION_LIST_DOWNLOAD_TITLE', 'Télécharger les informations des stations sélectionnées');
define('TEXT_STATION_LIST_CREATING_FILE', 'Création du fichier...');

// -----------------------------------------------
// Station list - sort controls
define('TEXT_STATION_SORT_BY', 'TRIER PAR');
define('TEXT_STATION_SORT_NAME', 'Nom station');
define('TEXT_STATION_SORT_CODE', 'Code station');
define('TEXT_STATION_SORT_TYPE', 'Type de données');
define('TEXT_STATION_SORT_ASC', 'Croissant');
define('TEXT_STATION_SORT_DESC', 'Décroissant');
define('TEXT_STATION_STATUS_CHANGE', 'Changer le statut');

// -----------------------------------------------
// Station list - summary counters
define('TEXT_STATION_NB_TOTAL', 'Nb total stations');
define('TEXT_STATION_NB_ACTIVE', 'Nb stations actives');
define('TEXT_STATION_NB_SUIVI', 'Nb stations en suivi continu');
define('TEXT_STATION_NB_ARMEE', 'Nb stations avec équipement en panne');

// -----------------------------------------------
// Station list - table column headers
define('TEXT_STATION_COL_STATUS', 'Statut');
define('TEXT_STATION_COL_STATUS_TITLE', 'Active / Historique (Fermée)');
define('TEXT_STATION_COL_SUIVI', 'Suivi');
define('TEXT_STATION_COL_SUIVI_TITLE', 'Suivi continu / Mesures ponctuelles');
define('TEXT_STATION_COL_ETAT', 'État');
define('TEXT_STATION_COL_ETAT_TITLE', 'En service / Hors service');
define('TEXT_STATION_COL_TYPE', 'Type');
define('TEXT_STATION_COL_CODE', 'Code station');
define('TEXT_STATION_COL_NOM', 'Nom station');
define('TEXT_STATION_COL_COMMUNE', 'Commune');
define('TEXT_STATION_COL_REGIONHYDRO', 'Région hydro.');
define('TEXT_STATION_COL_REGIONHYDRO_TITLE', 'Région hydrologique ou bassin versant');
define('TEXT_STATION_COL_INSTALLATION', 'Installation');
define('TEXT_STATION_COL_INSTALLATION_TITLE', 'Date d’installation');
define('TEXT_STATION_COL_VISITE', 'Dernière visite');
define('TEXT_STATION_COL_VISITE_TITLE', 'Date de la dernière visite');
define('TEXT_STATION_COL_NB_RA', 'Nb RA');
define('TEXT_STATION_COL_NB_RA_TITLE', 'Nombre de rapports d’activité');
define('TEXT_STATION_COL_EXPORT', 'Export');
define('TEXT_STATION_COL_EXPORT_TITLE', 'Sélectionner les stations pour export XLS');
define('TEXT_STATION_COL_DELETE_TITLE', 'Supprimer');

// --- Excel export column headers (station download) ---
define('TEXT_STATION_COL_SHEET_IDENT', 'Identification');

define('TEXT_STATION_COL_SITE', 'Site');
define('TEXT_STATION_COL_REGION', 'Region');
define('TEXT_STATION_COL_NAPPE', 'Nappe');
define('TEXT_STATION_COL_X_RGNC', 'X_RGNC');
define('TEXT_STATION_COL_Y_RGNC', 'Y_RGNC');
define('TEXT_STATION_COL_X_WGS', 'X_WGS');
define('TEXT_STATION_COL_Y_WGS', 'Y_WGS');
define('TEXT_STATION_COL_DESCRIPTION', 'Description');
define('TEXT_STATION_COL_ZSOL', 'Z sol');
define('TEXT_STATION_COL_PRECISION', 'Precision');
define('TEXT_STATION_COL_AQUIFERE', 'Acquifere capte');
define('TEXT_STATION_COL_NATURE', 'Nature');
define('TEXT_STATION_COL_MAITRE_OUVRAGE', 'Maitre d’ouvrage');
define('TEXT_STATION_COL_DATE_REALISATION', 'Date de realisation');
define('TEXT_STATION_COL_SONDE', 'Sonde');
define('TEXT_STATION_COL_REP_NATURE', 'Nature du Repere');
define('TEXT_STATION_COL_REP_Z', 'Z Repere');
define('TEXT_STATION_COL_REP_PRECISION', 'Precision');
define('TEXT_STATION_COL_REP_CODE', 'Code Repere');
define('TEXT_STATION_COL_REP_DATE_DEBUT', 'Date debut');
define('TEXT_STATION_COL_REP_DATE_FIN', 'Date fin');
define('TEXT_STATION_COL_REP_NATURE_G1', 'Nature Repere Geometre 1');
define('TEXT_STATION_COL_REP_Z_G1', 'Z Repere Geometre 1');
define('TEXT_STATION_COL_REP_NATURE_G2', 'Nature Repere Geometre 2');
define('TEXT_STATION_COL_REP_Z_G2', 'Z Repere Geometre 2');
define('TEXT_STATION_COL_REP_OBS', 'Observation');
define('TEXT_STATION_COL_CAR_DATE_OBS', 'Date d’observation');
define('TEXT_STATION_COL_CAR_PROF', 'Profondeur');
define('TEXT_STATION_COL_CAR_MAT_TETE', 'Materiaux tete');
define('TEXT_STATION_COL_CAR_DIM_TETE', 'Dim. Tete Ext.');
define('TEXT_STATION_COL_CAR_MAT_TUB', 'Materiaux Tubage Interieur');
define('TEXT_STATION_COL_CAR_DIAM_TUB', 'Diam. Tubage Interieur');
define('TEXT_STATION_COL_CAR_MAT_DALLE', 'Materiaux Dalle');
define('TEXT_STATION_COL_CAR_DIM_DALLE', 'Dim. Dalle');
define('TEXT_STATION_COL_CAR_DIST_CAPOT', 'Dist. Capot Tubage');
define('TEXT_STATION_COL_CAR_DIST_TUB_DALLE', 'Dist. Tubage Dalle');
define('TEXT_STATION_COL_CAR_DIST_DALLE_SOL', 'Dist. Dalle Sol');
define('TEXT_STATION_COL_CAR_PRESENCE_CAPOT', 'Presence Capot');
define('TEXT_STATION_COL_CAR_ETAT', 'Etat');
define('TEXT_STATION_COL_CAR_ACTIVITE', 'En activite');
define('TEXT_STATION_COL_CAR_USAGE', 'Usage');
define('TEXT_STATION_COL_CAR_EQUIP', 'Equipement Exploitation');
define('TEXT_STATION_COL_CAR_SCHEMA_TETE', 'Schema Tete Ouvrage');
define('TEXT_STATION_COL_CAR_OBS', 'Remarque');

// list_stations.php — popup de confirmation de suppression
define('TEXT_STATION_DEL_CONFIRM_TITLE',   'Confirmer la suppression');
define('TEXT_STATION_DEL_CONFIRM_MSG',     'Vous êtes sur le point de supprimer la station suivante. Cette action est irréversible.');
define('TEXT_STATION_DEL_STATION_LABEL',   'Station');
define('TEXT_STATION_DEL_CHALLENGE_LABEL', 'Pour confirmer, résolvez ce calcul :');
define('TEXT_STATION_DEL_BTN_CANCEL',      'Annuler');
define('TEXT_STATION_DEL_BTN_CONFIRM',     'Supprimer');

// -----------------------------------------------
// process_station_delete.php — messages de suppression
define('TEXT_STATION_DELETE_SUCCESS',      'La station a bien été supprimée.');
define('TEXT_STATION_INFO',                'Station');
define('TEXT_STATION_DELETE_HAS_RECORDS',  'Cette station ne peut être supprimée car elle contient des enregistrements.');
define('TEXT_STATION_DELETE_NOT_FOUND',    "Cette station n'existe pas, elle ne peut être supprimée.");

// -----------------------------------------------
// Station list - empty result
define('TEXT_STATION_NONE_FOUND', 'Aucune station trouvée.');

// -----------------------------------------------
// Station list - JS error messages (PHP-injected)
define('TEXT_STATION_JS_NO_SELECTION', 'Aucune station sélectionnée - le fichier ne peut pas être créé.');
define('TEXT_STATION_JS_ERR_GENERATE', 'Erreur lors de la génération du fichier.');
define('TEXT_STATION_JS_ERR_SERVER', 'Erreur de requête serveur.');

// -----------------------------------------------
// Station edit - page title
define('TEXT_STATION_EDIT_TITLE_NEW', 'Nouvelle station de mesure');
define('TEXT_STATION_EDIT_TITLE_TYPE', ' - Station de mesure : ');
define('TEXT_STATION_EDIT_SAVE', 'Enregistrer');

// -----------------------------------------------
// AVAILABLE DATA TABLE

define('TEXT_BLOCK_INFO_CHRON_TITLE',    'Détails sur les Chroniques');
define('TEXT_BLOCK_HISTORY_CHRON_TITLE', 'Historique des actions sur les Chroniques');

define('TEXT_LOADDATA_COL_CHRON',     'Séries');
define('TEXT_LOADDATA_COL_NBDATA',    'Nb data');
define('TEXT_LOADDATA_COL_DATESTART', 'Date début');
define('TEXT_LOADDATA_COL_DATEEND',   'Date fin');
 
define('TEXT_LOADDATA_CODEQUAL_TITLE', 'Codes Qualité');
 
define('TEXT_LOADDATA_YAXIS_LABEL',   'Type de chroniques');

// process_loaddata.php — graph display strings
define('TEXT_LOADDATA_HOVER_DATE',     'Date');
define('TEXT_LOADDATA_HOVER_CODEQUAL', 'Code qualité');
define('TEXT_LOADDATA_LABEL_START',    'Début');
define('TEXT_LOADDATA_LABEL_END',      'Fin');

// -----------------------------------------------
// CHRONOLOGY HISTORY (process_history_chron.php)

define('TEXT_HISTORY_IMPORT',       'Import');
define('TEXT_HISTORY_COL_DATE',     "Date de l'opération");
define('TEXT_HISTORY_COL_DATA',     'Données');
define('TEXT_HISTORY_COL_OPERATION','Opération');
define('TEXT_HISTORY_COL_USER',     'Utilisateur');
define('TEXT_HISTORY_COL_OBS',      'Observations');
define('TEXT_HISTORY_COL_START',    'Début période');
define('TEXT_HISTORY_COL_END',      'Fin période');


// -----------------------------------------------
// CHRONOLOGY INFORMATION (process_info_chron.php)

define('TEXT_CHRON_RAW_DATA', 'Données brutes');
define('TEXT_CHRON_COL_ACRONYM',  'Acronyme');
define('TEXT_CHRON_COL_LABEL',    'Intitulé');
define('TEXT_CHRON_COL_UNIT',     'Unité');
define('TEXT_CHRON_COL_DATATYPE', 'Type de données');

define('TEXT_CHRON_JGE_LABEL',  'Jaugeages Ponctuels');
define('TEXT_CHRON_ETL_LABEL',  "Relation d'Etalonnage");
define('TEXT_CHRON_DIAG_LABEL', 'Profil de Conductivité Electrique (Diagraphie)');
define('TEXT_CHRON_RA_LABEL',   "Rapport d'Activité");

// -----------------------------------------------
// Station edit - tabs
define('TEXT_STATION_TAB_MONITORING', 'Suivi');
define('TEXT_STATION_TAB_FORM', 'Fiche');
define('TEXT_STATION_TAB_BENCHMARK', 'Repères');
define('TEXT_STATION_TAB_CHARACTERISTICS', 'Caractéristiques');
define('TEXT_STATION_TAB_ACCESS', 'Accès');
define('TEXT_STATION_TAB_PHOTOS', 'Photos');

// -----------------------------------------------
// Station edit - error state
define('TEXT_STATION_EDIT_ERROR_TITLE',     'Fichier station : Station de mesure');
define('TEXT_STATION_EDIT_NOT_FOUND',       'Aucune station trouvée.');
define('TEXT_STATION_EDIT_BACK_TO_LIST',    '>> Retour à la liste des stations');

// -----------------------------------------------
// Station form - section headers
define('TEXT_FORM1_MEASURE_TYPE',       'Type de mesure');
define('TEXT_FORM1_STATUS',             'Statut');
define('TEXT_FORM1_MONITORING',         'Suivi');
define('TEXT_FORM1_EQUIPMENT_FAULT',    'En panne');
define('TEXT_FORM1_CODE',               'Code / N° station');
define('TEXT_FORM1_NAME',               'Nom de la station');
define('TEXT_FORM1_IRH',                'IRH / N° enregistrement');
define('TEXT_FORM1_SHORT_NAME',         'Nom abrégé');

// -----------------------------------------------
// Station form - status & monitoring dropdown options
define('TEXT_FORM1_STATUS_ACTIVE',      'Station active');
define('TEXT_FORM1_STATUS_HISTORICAL',  'Station historique');
define('TEXT_FORM1_MONITORING_CONT',    'Mesures continues');
define('TEXT_FORM1_MONITORING_SPOT',    'Mesures ponctuelles');

// -----------------------------------------------
// Station form - geographic section
define('TEXT_FORM1_MUNICIPALITY',       'Commune');
define('TEXT_FORM1_SITE',               'Site');
define('TEXT_FORM1_WATERSHED',          'Bassin versant');
define('TEXT_FORM1_ORIENTATION',        'Orientation du drainage');
define('TEXT_FORM1_ALTITUDE',           'Altitude (m)');
define('TEXT_FORM1_RIVER',              'Cours d’eau');
define('TEXT_FORM1_AQUIFER',            'Aquifère');

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
define('TEXT_FORM1_MANAGER',            'Responsable station');
define('TEXT_FORM1_DATE_INSTALL',       'Date d’installation');
define('TEXT_FORM1_DATE_REMOVAL',       'Date de désaffectation');
define('TEXT_FORM1_DATE_PLACEHOLDER',   'jj-mm-aaaa');
define('TEXT_FORM1_DESCRIPTION',        'Description');

// -----------------------------------------------
// Station form - select2 placeholders
define('TEXT_FORM1_SELECT2_REGION',     'Sélectionner région...');
define('TEXT_FORM1_SELECT2_COMMUNE',    'Sélectionner commune...');
define('TEXT_FORM1_SELECT2_REGIONHYDRO', 'Sélectionner bassin versant...');
define('TEXT_FORM1_SELECT2_RIVER','Select rivière...');
define('TEXT_FORM1_SELECT2_AQUIFER',    'Sélectionner aquifère...');

// -----------------------------------------------
// Station monitoring tab - section titles
define('TEXT_STATION2_METADATA_TITLE',   'Métadonnées station');
define('TEXT_STATION2_DATA_TITLE',       'Données disponibles pour cette station');

// -----------------------------------------------
// Station monitoring tab - links panel
define('TEXT_STATION2_LINKS',            'Liens :');
define('TEXT_STATION2_LINK_DATA',        '>> Données station');
define('TEXT_STATION2_LINK_RA',          '>> Derniers RA');
define('TEXT_STATION2_LINK_JGE',         '>> Liste des jaugeages');
define('TEXT_STATION2_LINK_ETL',         '>> Courbes d’étalonnage (H→Q)');

// -----------------------------------------------
// Station monitoring tab - series info links
define('TEXT_STATION2_SERIES_DETAILS',   'Détails des séries');
define('TEXT_STATION2_MODIF_HISTORY',    'Historique des modifications');

// -----------------------------------------------
// Station monitoring tab - JS empty state
define('TEXT_STATION2_JS_NO_DATA',       'Aucune donnée enregistrée pour cette station.');

// -----------------------------------------------
// Station access tab - page structure
define('TEXT_ACCESS_EXPORT_BTN',        'Exporter la fiche d’accès');
define('TEXT_ACCESS_FORM_TITLE',        'Fiche d’accès');
define('TEXT_ACCESS_PLAN_TITLE',        'Plan d’accès');

// -----------------------------------------------
// Station access tab - contact fields
define('TEXT_ACCESS_OWNER',             'Propriétaire du site');
define('TEXT_ACCESS_CONTACT_NAME',      'Personne contact');
define('TEXT_ACCESS_CONTACT_PHONE',     'Téléphone');
define('TEXT_ACCESS_CONTACT_EMAIL',     'Email');
define('TEXT_ACCESS_CONTACT_ADDRESS',   'Adresse');
define('TEXT_ACCESS_CONTACT_PO_BOX',    'BP');
define('TEXT_ACCESS_CONTACT_POSTCODE',  'Code postal');
define('TEXT_ACCESS_CONTACT_COMMUNE',   'Commune');

// -----------------------------------------------
// Station access tab - access information
define('TEXT_ACCESS_INFO',              'Informations d’accès');
define('TEXT_ACCESS_PEDESTRIAN',        'Accès piéton');
define('TEXT_ACCESS_TIME',              'Temps d’accès');
define('TEXT_ACCESS_DIFFICULTY',        'Difficultés d’accès');
define('TEXT_ACCESS_REMARKS',           'Remarques complémentaires');

// -----------------------------------------------
// Station access tab - map upload
define('TEXT_ACCESS_PLAN_UPLOAD_LABEL', 'Télécharger plan (formats : .jpg .jpeg .png)');
define('TEXT_ACCESS_PLAN_UPLOAD_SIZE',  'Taille max. : 2 Mo.');
define('TEXT_ACCESS_PLAN_SAVE_BTN',     'Enregistrer plan');
define('TEXT_ACCESS_PLAN_LOADING',      'Chargement...');

// -----------------------------------------------
// Station access tab - select2 placeholder
define('TEXT_ACCESS_SELECT2_COMMUNE',   'Sélectionner commune...');

// -----------------------------------------------
// Access map upload handler
define('TEXT_PHOTO_ACCESS_ERR_FORMAT',  'Format de fichier non supporté. Formats acceptés : .jpg, .jpeg, .png.');
define('TEXT_PHOTO_ACCESS_ERR_SIZE',    'Taille max. dépassée (2 Mo).');
define('TEXT_PHOTO_ACCESS_ERR_UPLOAD',  'Erreur lors du téléchargement.');
define('TEXT_PHOTO_ACCESS_ERR_NO_FILE', 'Aucun fichier chargé.');
define('TEXT_PHOTO_ACCESS_SUCCESS',     'Plan enregistré avec succès.');

// -----------------------------------------------
// Access map display handler
define('TEXT_PLAN_DELETE_LINK',   'Supprimer plan');
define('TEXT_PLAN_VIEW_TITLE',    'Voir image');
define('TEXT_PLAN_ADD', 'Ajouter un plan');

// form_station_access.php — plan delete confirmation popup
define('TEXT_PLAN_DEL_CONFIRM_TITLE', 'Supprimer le plan acces');
define('TEXT_PLAN_DEL_CONFIRM_MSG',   'Confirmez-vous la suppression du plan d acces ? Cette action est irreversible.');
define('TEXT_PLAN_DEL_BTN_CANCEL',    'Annuler');
define('TEXT_PLAN_DEL_BTN_CONFIRM',   'Supprimer');

// -----------------------------------------------
// Access map delete handler
define('TEXT_PLAN_DELETE_SUCCESS', 'Plan d’accès supprimé avec succès.');
define('TEXT_PLAN_DELETE_FAIL',    'Impossible de supprimer le plan.');

// -----------------------------------------------
// Station photo gallery tab (form_station_photos)
define('TEXT_PHOTOS_UPLOAD_LABEL',    'Sélectionner une photo (.jpg .jpeg .png)');
define('TEXT_PHOTOS_UPLOAD_SIZE',     'Taille max. : 2 Mo.');
define('TEXT_PHOTOS_DESC',            'Description');
define('TEXT_PHOTOS_DATE',            'Date photo');
define('TEXT_PHOTOS_DATE_PLACEHOLDER', 'jj-mm-aaaa');
define('TEXT_PHOTOS_SAVE_BTN',        'Enregistrer photo');
define('TEXT_PHOTOS_LOADING',         'Chargement...');

// -----------------------------------------------
// Station photo gallery display handler (process_loadphotos)
define('TEXT_PHOTOS_COL_DATE',        'Date');
define('TEXT_PHOTOS_COL_DESC',        'Description');
define('TEXT_PHOTOS_DELETE_TITLE',    'Supprimer image');
define('TEXT_PHOTOS_VIEW_TITLE',      'Voir image');
define('TEXT_PHOTOS_ADD',            'Ajouter une image.');

define('TEXT_PHOTO_ERR_DATE', 'Format de date incorrect (jj-mm-aaaa).');

// form_station_photos.php — delete confirmation popup + missing image
define('TEXT_PHOTOS_DEL_CONFIRM_TITLE', 'Supprimer la photo');
define('TEXT_PHOTOS_DEL_CONFIRM_MSG',   'Confirmez-vous la suppression de cette photo ? Cette action est irreversible.');
define('TEXT_PHOTOS_DEL_PHOTO_LABEL',   'Photo');
define('TEXT_PHOTOS_DEL_BTN_CANCEL',    'Annuler');
define('TEXT_PHOTOS_DEL_BTN_CONFIRM',   'Supprimer');
define('TEXT_PHOTOS_MISSING',           'Image introuvable');

// -----------------------------------------------
// Station photo delete handler (process_delphoto)
define('TEXT_PHOTO_DELETE_SUCCESS', 'Photo supprimée avec succès.');
define('TEXT_PHOTO_DELETE_FAIL',    'Impossible de supprimer la photo.');

// -----------------------------------------------
// Piezo characteristics tab (form_station_caracteristique)
define('TEXT_CARACT_NEW_OBS',           'Nouvelle observation');
define('TEXT_CARACT_OBS_TITLE',         'Observation du ');
define('TEXT_CARACT_NEW_OBS_TITLE',     '(Nouvelle) Observation du ');

define('TEXT_CARACT_DELETE_TITLE',   'Supprimer');

define('TEXT_CARACT_DEL_CONFIRM_TITLE', 'Supprimer l’observation');
define('TEXT_CARACT_DEL_CONFIRM_MSG',   'Confirmez-vous la suppression de cette observation ? Cette action est irreversible.');
define('TEXT_CARACT_DEL_BTN_CANCEL',    'Annuler');
define('TEXT_CARACT_DEL_BTN_CONFIRM',   'Supprimer');

define('TEXT_CARACT_DATE_PLACEHOLDER',   'jj-mm-aaaa');

// Borehole condition states
define('TEXT_ETAT_BON',           'Bon');
define('TEXT_ETAT_MOYEN',         'Moyen');
define('TEXT_ETAT_MAUVAIS',       'Mauvais');
define('TEXT_ETAT_ABANDONNE',     'Abandonné');
define('TEXT_ETAT_COLMATE',       'Colmaté');
define('TEXT_ETAT_REBOUCHE',      'Rebouché');
define('TEXT_ETAT_NON_ACCESSIBLE','Non accessible');
define('TEXT_ETAT_DISPARU',       'Disparu');

// -----------------------------------------------
// Piezo characteristics tab - well construction fields
define('TEXT_CARACT_DEPTH',             'Profondeur [m]');
define('TEXT_CARACT_HEAD_MATERIAL',     'Matériau tête');
define('TEXT_CARACT_EXT_DIM',           'Dimension extérieure');
define('TEXT_CARACT_CASING_MATERIAL',   'Matériau tubage');
define('TEXT_CARACT_CASING_DIM',        'Diamètre tubage [mm]');
define('TEXT_CARACT_SCHEMA',            'Schéma');
define('TEXT_CARACT_PROTECTION',        'Protection');
define('TEXT_CARACT_SLAB_MATERIAL',     'Matériau dalle');
define('TEXT_CARACT_SLAB_DIM',          'Dimension dalle');
define('TEXT_CARACT_CAP_PRESENT',       'Couvercle présent');
define('TEXT_CARACT_DIST_CAP_TUBE',     'Distance couvercle/tubage (1)');
define('TEXT_CARACT_DIST_TUBE_SLAB',    'Distance tubage/dalle (2)');
define('TEXT_CARACT_DIST_SLAB_GROUND',    'Distance dalle/sol (3)');

// -----------------------------------------------
// Piezo characteristics tab - usage section
define('TEXT_CARACT_USAGE_TITLE',       'Usage');
define('TEXT_CARACT_STATE',             'État');
define('TEXT_CARACT_ACTIVE',            'En service');
define('TEXT_CARACT_USAGE',             'Usage');
define('TEXT_CARACT_EQUIPMENT',         'Équipement');
define('TEXT_CARACT_OBSERVATIONS',      'Observations');

// -----------------------------------------------
// Piezo characteristics delete handler (process_delcaracteristique)
define('TEXT_CARACT_DELETE_SUCCESS', 'Observation du %s supprimée avec succès.');
define('TEXT_CARACT_DELETE_FAIL',    'Impossible de supprimer l’observation.');

// -----------------------------------------------
// Piezo benchmark tab (form_station_repere) - table headers
define('TEXT_REPERE_COL_VALIDITY',    'Période validité');
define('TEXT_REPERE_DATE_PLACEHOLDER',   'jj-mm-aaaa');
define('TEXT_REPERE_COL_BENCHMARK',   'Repère');
define('TEXT_REPERE_COL_SURVEYOR',    'Données nivellement');
define('TEXT_REPERE_COL_DATE_START',  'Date début');
define('TEXT_REPERE_COL_DATE_END',    'Date fin');
define('TEXT_REPERE_COL_NATURE',      'Nature');
define('TEXT_REPERE_COL_CODE',        'Code');
define('TEXT_REPERE_COL_Z',           'Z [m]');
define('TEXT_REPERE_COL_PRECISION',   'Précision nivellement');
define('TEXT_REPERE_COL_BENCHMARK1',  'Repère 1');
define('TEXT_REPERE_COL_BENCHMARK2',  'Repère 2');
define('TEXT_REPERE_COL_OBS',         'Observation');

// -----------------------------------------------
// Piezo benchmark tab - actions
define('TEXT_REPERE_ADD_ROW',         'Ajouter un repère');
define('TEXT_REPERE_DELETE_TITLE',    'Supprimer');

// -----------------------------------------------
// Piezo benchmark delete handler (process_delrepere)
define('TEXT_REPERE_DELETE_SUCCESS', 'Repère (date début : %s) supprimé avec succès.');
define('TEXT_REPERE_DELETE_FAIL',    'Impossible de supprimer le repère.');

// form_station_repere.php — delete confirmation popup
define('TEXT_REPERE_DEL_CONFIRM_TITLE', 'Supprimer le repère');
define('TEXT_REPERE_DEL_CONFIRM_MSG',   'Confirmez-vous la suppression de ce repère ? Cette action est irréversible.');
define('TEXT_REPERE_DEL_BTN_CANCEL',    'Annuler');
define('TEXT_REPERE_DEL_BTN_CONFIRM',   'Supprimer');

// -----------------------------------------------
// Station save - Messages de succès
define('TEXT_STATION_SAVE_NEW_SUCCESS',         'La nouvelle Station a bien été créée');
define('TEXT_STATION_SAVE_UPDATE_SUCCESS',      'La Station a bien été enregistrée');
define('TEXT_STATION_SAVE_LABEL',               'Station :');
 
// -----------------------------------------------
// Station save - Erreurs serveur / authentification
define('TEXT_ERROR_SERVER_GENERIC',             'Erreur serveur. Veuillez contacter l’administrateur.');
define('TEXT_ERROR_USER_NOT_IDENTIFIED',        'Utilisateur non identifié.');
define('TEXT_ERROR_REQUEST_METHOD',             'Une erreur est survenue lors de l’envoi des données au serveur.');
define('TEXT_ERROR_SAVE_FAILED',                'Une erreur est survenue : la fiche Station n’a pas pu être enregistrée');
define('TEXT_ERROR_DB_WRITE',                   'Une erreur est survenue lors de l’écriture en base de données.');
define('TEXT_ERROR_RETRY_OR_CONTACT',           'Veuillez réessayer ou contacter l’administrateur.');
 
// -----------------------------------------------
// Station save - Validation des champs
define('TEXT_ERROR_CODE_STATION_REQUIRED',      'Le Code Station est un champ obligatoire.');
define('TEXT_ERROR_NAME_STATION_REQUIRED',      'Le Nom Station est un champ obligatoire.');
define('TEXT_ERROR_CODE_STATION_DUPLICATE_1',   'Le code ');
define('TEXT_ERROR_CODE_STATION_DUPLICATE_2',   ' est déjà attribué, il n’est pas possible de créer une nouvelle station avec ce Code Station.');
 
// -----------------------------------------------
// Station save - Validation des dates
define('TEXT_ERROR_DATE_INSTALLATION_FORMAT',   'Le format de la date d’installation n’est pas valide. Veuillez vérifier votre saisie : jj-mm-aaaa');
define('TEXT_ERROR_DATE_DECOMMISSION_FORMAT',   'Le format de la date de fermeture n’est pas valide. Veuillez vérifier votre saisie : jj-mm-aaaa');
define('TEXT_ERROR_DATE_DECOMMISSION_ORDER',    'La date de fermeture ne peut pas être antérieure ou égale à la date d’installation.');
 
// -----------------------------------------------
// Station save - Caractéristiques piézométriques (Puits)
define('TEXT_ERROR_WELL_DEPTH_NUMERIC',         'Le champ Profondeur, dans les Caractéristiques du Puits, doit être un nombre.');
define('TEXT_ERROR_WELL_CASING_SIZE_NUMERIC',   'Le champ Dimension du tubage, dans les Caractéristiques du Puits, doit être un nombre.');
define('TEXT_ERROR_WELL_DIST_CAP_TUBE',         'Le champ Dist. Capot/Tubage (1), dans les Caractéristiques du Puits, doit être un nombre.');
define('TEXT_ERROR_WELL_DIST_TUBE_SLAB',        'Le champ Dist. Tubage/Dalle (2), dans les Caractéristiques du Puits, doit être un nombre.');
define('TEXT_ERROR_WELL_DIST_SLAB_GROUND',      'Le champ Dist. Dalle/Sol (3), dans les Caractéristiques du Puits, doit être un nombre.');
define('TEXT_ERROR_WELL_DATE_FORMAT',           'Le format de la date, dans les Caractéristiques du Puits, n’est pas valide. Veuillez vérifier votre saisie : jj-mm-aaaa');
 
// -----------------------------------------------
// Station save - Repères piézométriques
define('TEXT_ERROR_REF_DATE_START_FORMAT',      'Le format de la date de début de validité d’un Repère n’est pas valide. Veuillez vérifier votre saisie : jj-mm-aaaa');
define('TEXT_ERROR_REF_DATE_END_FORMAT',        'Le format de la date de fin de validité d’un Repère n’est pas valide. Veuillez vérifier votre saisie : jj-mm-aaaa');
define('TEXT_ERROR_REF_DATE_START_REQUIRED', 'La date de début de validité du repère est obligatoire.');
define('TEXT_ERROR_REF_DATE_ORDER',             'La date de fin de validité d’un Repère ne peut pas être antérieure ou égale à sa date de début de validité.');
define('TEXT_ERROR_REF_Z_NUMERIC',              'Le champ Z - Repère, dans les Repères du Puits, doit être un nombre.');
define('TEXT_ERROR_REF_Z_SURVEYOR_1',           'Le champ Z - Relevé 1 Géomètre, dans les Repères du Puits, doit être un nombre.');
define('TEXT_ERROR_REF_Z_SURVEYOR_2',           'Le champ Z - Relevé 2 Géomètre, dans les Repères du Puits, doit être un nombre.');
 
// -----------------------------------------------
// Station save - Messages de log / actions
define('TEXT_ACTION_CREATE_STATION',            'Création nouvelle Station : ');
define('TEXT_ACTION_UPDATE_STATION',            'Modification d’une Station : ');

// -----------------------------------------------
// Correction page (graph_correct_chron.php) - page header
define('TEXT_CORRECT_TITLE',              'Correction de série');
define('TEXT_CORRECT_STATION',            'Station : ');
define('TEXT_CORRECT_SERIES',             'Série : ');

define('TEXT_CORRECT_LINE_WIDTH',     'Épaisseur');
define('TEXT_CORRECT_LINE_WIDTH_DEC', 'Plus fin');
define('TEXT_CORRECT_LINE_WIDTH_INC', 'Plus épais');

define('TEXT_CORRECT_ZOOM_BACK',       '↶');
define('TEXT_CORRECT_ZOOM_BACK_TITLE', 'Revenir au zoom précédent');
define('TEXT_CORRECT_ZOOM_FORWARD', '↪');
define('TEXT_CORRECT_ZOOM_FORWARD_TITLE', 'Zoom suivant');    

// -----------------------------------------------
// Correction page - left panel labels
define('TEXT_CORRECT_SERIES_DETAILS',     'Détails de la série');
define('TEXT_CORRECT_PERIOD_TITLE',       'Période de correction');
define('TEXT_CORRECT_DATE_START',         'Date début');
define('TEXT_CORRECT_DATE_END',           'Date fin');
define('TEXT_CORRECT_HOUR',               'Heure');
define('TEXT_CORRECT_Y_MIN',              'Y min');
define('TEXT_CORRECT_Y_MAX',              'Y max');
define('TEXT_CORRECT_APPLY_PERIOD', 'Appliquer la période');
define('TEXT_CORRECT_ADJUST_SCALE',       'Ajuster échelle');
define('TEXT_CORRECT_OPTIONS_TITLE',      'Options de correction');
define('TEXT_CORRECT_OPTIONS_OPEN',       'Ouvrir options de correction');
define('TEXT_CORRECT_DUPLICATE',          'Dupliquer série');
define('TEXT_CORRECT_TIMESTEP',           'Pas de temps');
define('TEXT_CORRECT_INTERVAL',    'Intervalle');
define('TEXT_CORRECT_UNIT_MIN',    'min');
define('TEXT_CORRECT_UNIT_HOUR',   'heure');
define('TEXT_CORRECT_UNIT_DAY',    'jour');
define('TEXT_CORRECT_UNIT_MONTH',  'mois');
define('TEXT_CORRECT_UNIT_YEAR',   'année');
define('TEXT_CORRECT_UNIT_MIN_PLURAL',    'min');
define('TEXT_CORRECT_UNIT_HOUR_PLURAL',   'heures');
define('TEXT_CORRECT_UNIT_DAY_PLURAL',    'jours');
define('TEXT_CORRECT_UNIT_MONTH_PLURAL',  'mois');
define('TEXT_CORRECT_UNIT_YEAR_PLURAL',   'années');
define('TEXT_CORRECT_INFO_TIMESTEP', 'Nouvelle chron. - Interval (%s) : %s %s');
define('TEXT_CORRECT_INTERVAL_MIN',       'Intervalle (min)');
define('TEXT_CORRECT_CALC_MODE',          'Mode calcul');
define('TEXT_CORRECT_CALC_MEAN',          'Moyenne');
define('TEXT_CORRECT_CALC_CUMUL',         'Somme cumulée');
define('TEXT_CORRECT_NEW_SERIES_BTN',     'Générer nouvelle série');
define('TEXT_CORRECT_TEMPORAL_GROUP',     'Agrégation temporelle');

define('TEXT_CORRECT_MARKERS_LABEL',  'Points');
define('TEXT_CORRECT_MARKERS_TITLE',  'Afficher les points de données sur la courbe');
define('TEXT_CORRECT_INFO_DELETE',    'Suppression : %d point(s)');
define('TEXT_CORRECT_DEL_COUNT_1',    'point retiré');
define('TEXT_CORRECT_DEL_COUNT_N',    'points retirés');
define('TEXT_CORRECT_DEL_UNDO',       '↩ Annuler');
define('TEXT_CORRECT_DEL_UNDO_TITLE', 'Annuler la dernière suppression (Ctrl+Z)');
define('TEXT_CORRECT_DEL_SAVE',       '✓ Enregistrer');
define('TEXT_CORRECT_DEL_SAVE_TITLE', 'Enregistrer comme correction');

define('TEXT_CORRECT_SELECT_PERIOD_HINT', "Pour sélectionner une période : maintenez Maj, puis cliquez-glissez sur le graphe.");

// Libellés info correction (affichés dans le tableau des corrections)
define('TEXT_CORRECT_INFO_OFFSET',    '%s seconde(s)');
define('TEXT_CORRECT_INFO_SMOOTHING', 'Lissage - seuil : %s %%');
define('TEXT_CORRECT_INFO_GAP',       'Lacune');

// Erreur + résumé pas de temps
define('TEXT_CORRECT_ERR_NO_BUCKET',     'Aucun intervalle complet dans votre sélection. Sélectionnez au moins un(e) %s complet(e).');
define('TEXT_CORRECT_SUMMARY_COMPLETE',  '%d intervalle(s) complet(s) généré(s)');
define('TEXT_CORRECT_SUMMARY_ANNOTATED', '%d intervalle(s) annoté(s) avec alerte lacune');
define('TEXT_CORRECT_SUMMARY_GAPZONE',   '%d zone(s) de lacune générée(s)');
define('TEXT_CORRECT_SUMMARY_EMPTY',     '%d intervalle(s) vide(s) ignoré(s) (pas de données source)');
define('TEXT_CORRECT_SUMMARY_PARTIAL',   '%d intervalle(s) partiel(s) ignoré(s)');

// Plage de période du message de succès
define('TEXT_CALCUL_SUCCESS_RANGE',   'du %s au %s');

// -----------------------------------------------
// Correction page - graph panel
define('TEXT_CORRECT_IN_PROGRESS',        'En cours de correction');
define('TEXT_CORRECT_ENLARGE',            'Agrandir');
define('TEXT_CORRECT_HQ_TITLE',           'Convertir série de hauteur en débit via courbe d’étalonnage');
define('TEXT_CORRECT_HQ_BTN',             'H → Q');
define('TEXT_CORRECT_ZOOM_X',             'Zoom / Déplacer X');
define('TEXT_CORRECT_ZOOM_Y',             'Zoom / Déplacer Y');
define('TEXT_CORRECT_LOADING',            'Chargement...');
define('TEXT_CORRECT_NO_DATA',            'Aucune donnée trouvée.');

// -----------------------------------------------
// Correction page - corrections table
define('TEXT_CORRECT_TABLE_TITLE',        'Liste des corrections en cours');
define('TEXT_CORRECT_COL_TYPE',           'Type');
define('TEXT_CORRECT_COL_START',          'Début');
define('TEXT_CORRECT_COL_END',            'Fin');

// -----------------------------------------------
// Correction page - save buttons
define('TEXT_CORRECT_SAVE',               'Enregistrer');
define('TEXT_CORRECT_SAVE_TITLE',         'Enregistrer dans la même série');
define('TEXT_CORRECT_SAVEAS',             'Enregistrer sous...');
define('TEXT_CORRECT_SAVEAS_TITLE',       'Enregistrer dans une autre série');
define('TEXT_CORRECT_PROCESSING',         'Traitement en cours');

define('TEXT_CORRECT_GAP_THRESHOLD',       'Seuil lacune');
define('TEXT_CORRECT_GAP_THRESHOLD_TITLE', 'Pourcentage maximum de lacunes autorisé dans un intervalle avant qu’il soit lui-même marqué comme lacune. Applicable uniquement au mode Moyenne (Cumul est strict : toute lacune = intervalle marqué lacune).');

// -----------------------------------------------
// Correction page - axis controls
define('TEXT_CORRECT_ADD_DECIMAL',        'Ajouter une décimale');
define('TEXT_CORRECT_REMOVE_DECIMAL',     'Retirer une décimale');
define('TEXT_CORRECT_LOG_SCALE',          'Log');
define('TEXT_CORRECT_LOG_SCALE_TITLE',    'Échelle logarithmique (base 10)');

// -----------------------------------------------
// Correction page - JS error/info messages
define('TEXT_CORRECT_JS_ERR_GENERATE',    'Erreur lors de la génération du fichier.');
define('TEXT_CORRECT_JS_ERR_SERVER',      'Erreur de requête serveur.');
define('TEXT_CORRECT_JS_ERR_SELECT_ONE',  'Vous devez sélectionner au moins une correction en cours.');
define('TEXT_CORRECT_JS_ERR_DATE_ORDER',  'La date et l’heure de début doivent être antérieures à la date et l’heure de fin.');
define('TEXT_CORRECT_JS_ERR_TIME_FMT',    'Au moins une des heures saisies est invalide ou au mauvais format (HH:MM ou HH:MM:SS).');
define('TEXT_CORRECT_JS_ERR_DATE_FMT',    'Au moins une des dates saisies est invalide ou au mauvais format (jj-mm-aaaa).');
define('TEXT_CORRECT_JS_ERR_Y_NUM',       'Erreur : les champs Ymin et Ymax doivent être des nombres.');

// -----------------------------------------------
// process_chron_calcul_view.php + process_chron_calcul_graph.php
define('TEXT_CALCUL_VIEW_DOWNLOAD_TITLE',   'Télécharger série');
define('TEXT_CALCUL_VIEW_APPLIED_TITLE',    'Correction appliquée');
define('TEXT_CALCUL_VIEW_DELETE_TITLE',     'Supprimer correction');
define('TEXT_CALCUL_VIEW_NONE',             'Aucune correction en cours.');
define('TEXT_CALCUL_VIEW_GAP_COL_SERIES',   'Série');
define('TEXT_CALCUL_VIEW_GAP_COL_START',    'Date début');
define('TEXT_CALCUL_VIEW_GAP_COL_END',      'Date fin');
define('TEXT_CALCUL_OPEN_TARGET_SERIES', 'Ouvrir cette chronique en correction (nouvel onglet)');

// -----------------------------------------------
// process_chron_calcul.php
define('TEXT_CALCUL_SUCCESS_TITLE',         'Correction générée avec succès.');
define('TEXT_CALCUL_SUCCESS_TYPE',          'Type');
define('TEXT_CALCUL_SUCCESS_PERIOD',        'Période');

// -----------------------------------------------
// process_chron_calcul_valid.php
define('TEXT_CALCUL_VALID_SUCCESS',         'Mise à jour des données terminée avec succès.');
define('TEXT_CALCUL_VALID_ERROR_WRITE',     'Problème lors de l’écriture dans les tables.');
define('TEXT_CALCUL_VALID_ERROR_DETAIL',    'Erreur survenue : ');
define('TEXT_CALCUL_VALID_NO_DATA',         'Aucune donnée reçue.');
define('TEXT_CALCUL_VALID_BASE_OBS', 'Série complète (copie source)');

// -----------------------------------------------
// process_chron_calcul_del.php
define('TEXT_CALCUL_DEL_SUCCESS',           'Correction supprimée avec succès :<br>%s<br>Période : %s - %s');
define('TEXT_CALCUL_DEL_FAIL',              'Impossible de supprimer la correction.');

// -----------------------------------------------
// block_calcul_options.php
define('TEXT_CALCUL_OPT_TITLE',             'Options de calcul pour la série');
define('TEXT_CALCUL_OPT_CLOSE',             'Fermer');
define('TEXT_CALCUL_OPT_LINEAR_TITLE',      'Correction par fonction linéaire');
define('TEXT_CALCUL_OPT_LINEAR_FN',         'Ynouveau = aY + b');
define('TEXT_CALCUL_OPT_LINEAR_BTN',        'Générer correction');
define('TEXT_CALCUL_OPT_OFFSET_TITLE',      'Décalage temporel (axe X)');
define('TEXT_CALCUL_OPT_SECONDS',           'secondes');
define('TEXT_CALCUL_OPT_OFFSET_BTN',        'Générer correction');
define('TEXT_CALCUL_OPT_GAP_TITLE',         'Insérer une lacune');
define('TEXT_CALCUL_OPT_GAP_BTN',           'Générer lacune');
define('TEXT_CALCUL_OPT_SMOOTH_TITLE',      'Lissage');
define('TEXT_CALCUL_OPT_SMOOTH_LOW',        'Seuil de variation faible');
define('TEXT_CALCUL_OPT_SMOOTH_THRESH',     'Seuil : ');
define('TEXT_CALCUL_OPT_SMOOTH_BTN',        'Lisser la série');

// -----------------------------------------------
// block_verif_savedata.php
define('TEXT_SAVEDATA_CONFIRM_TITLE',      'Confirmer l’enregistrement des corrections ?');
define('TEXT_SAVEDATA_CHRON_LABEL',        'Série à modifier :');
define('TEXT_SAVEDATA_CHRON_CURRENT',      'Série actuelle');
define('TEXT_CORRECT_JS_ERR_NO_TARGET', "Veuillez sélectionner la chronique d’accueil de la correction.");
define('TEXT_SAVEDATA_CHRON_PLACEHOLDER', "-- Choisir la chronique d'accueil --");
define('TEXT_SAVEDATA_QUAL_LABEL',         'Code qualité pour la correction');
define('TEXT_SAVEDATA_OBS_LABEL',          'Observation sur la correction');
define('TEXT_SAVEDATA_OVERWRITE_WARNING',  'Si des données existent déjà pour la même série, station et période, elles seront écrasées.');
define('TEXT_SAVEDATA_BTN_CONFIRM',        'Confirmer');
define('TEXT_SAVEDATA_BTN_CANCEL',         'Annuler');
define('TEXT_SAVEDATA_CURRENT_SERIES', 'Chronique en cours');
define('TEXT_SAVEDATA_CURRENT_BADGE',  'en cours');

// -----------------------------------------------
// GAUGING MODULE - STREAMFLOW GAUGINGS
define('TEXT_JGE_LIST_TITLE',           'Liste des jaugeages');
define('TEXT_JGE_LIST_NEW_BTN',         'Nouveau jaugeage');
define('TEXT_JGE_LIST_PERIODE',         'Période');
define('TEXT_JGE_LIST_PERIODE_1M',      '1 mois');
define('TEXT_JGE_LIST_PERIODE_3M',      '3 mois');
define('TEXT_JGE_LIST_PERIODE_6M',      '6 mois');
define('TEXT_JGE_LIST_PERIODE_1Y',      '1 an');
define('TEXT_JGE_LIST_PERIODE_2Y',      '2 ans');
define('TEXT_JGE_LIST_PERIODE_5Y',      '5 ans');
define('TEXT_JGE_LIST_PERIODE_10Y',     '10 ans');
define('TEXT_JGE_LIST_PERIODE_ALL',     'Toutes données');
define('TEXT_JGE_LIST_SORT_BY',         'TRIER PAR');
define('TEXT_JGE_LIST_SORT_NAME',       'Nom station');
define('TEXT_JGE_LIST_SORT_CODE',       'Code station');
define('TEXT_JGE_LIST_SORT_DATE',       'Date');
define('TEXT_JGE_LIST_ASC',             'Croissant');
define('TEXT_JGE_LIST_DESC',            'Décroissant');
define('TEXT_JGE_LIST_COUNT',           'Nombre de jaugeages : ');
define('TEXT_JGE_LIST_TH_TYPE',         'Type');
define('TEXT_JGE_LIST_TH_CODE',         'Code station');
define('TEXT_JGE_LIST_TH_STATION',      'Nom station');
define('TEXT_JGE_LIST_TH_DATE',         'Date');
define('TEXT_JGE_LIST_TH_HEURE',        'Heure');
define('TEXT_JGE_LIST_TH_BRAS',         'Bras');
define('TEXT_JGE_LIST_TH_Q',            'Débit [m³/s]');
define('TEXT_JGE_LIST_TH_H',            'Hauteur [cm]');
define('TEXT_JGE_LIST_EDIT_TITLE',      'Modifier valeurs H/Q');
define('TEXT_JGE_LIST_EDIT_FULL_TITLE', 'Saisir jaugeage détaillé par points');
define('TEXT_JGE_LIST_DEL_TITLE',       'Supprimer jaugeage');
define('TEXT_JGE_LIST_NOT_FOUND',       'Aucun jaugeage trouvé');

// -----------------------------------------------
// Gauging detail page
define('TEXT_JGE_PAGE_NEW',           'Nouveau jaugeage');
define('TEXT_JGE_PAGE_LABEL',         'Jaugeage : ');
define('TEXT_JGE_PAGE_SAVE',          'Enregistrer');
define('TEXT_JGE_PAGE_TITLE_ERROR',   'Jaugeage');
define('TEXT_JGE_PAGE_NOT_FOUND',     'Aucun jaugeage trouvé');
define('TEXT_JGE_SIDEBAR_Q',          'Débit [m³/s]');
define('TEXT_JGE_SIDEBAR_HMOY',       'Hauteur moy. [cm]');
define('TEXT_JGE_SIDEBAR_ETL_TITLE',  'Afficher courbe d’étalonnage H→Q');
define('TEXT_JGE_SIDEBAR_ETL_LINK',   '- Voir courbe d’étalonnage -');
define('TEXT_JGE_SIDEBAR_DATE',       'Date jaugeage');
define('TEXT_JGE_SIDEBAR_HEURE',      'Heure jaugeage');
define('TEXT_JGE_SIDEBAR_STATION',    'Station hydrométrique');
define('TEXT_JGE_SIDEBAR_CODE_QUAL',  'Code qualité');
define('TEXT_JGE_PANEL_SITUATION',    'Localisation');
define('TEXT_JGE_PANEL_DIST_SITE',    'Distance site [m]');
define('TEXT_JGE_PANEL_SITE',         'Position');
define('TEXT_JGE_PANEL_GPS_X',        'Coord. X (GPS)');
define('TEXT_JGE_PANEL_GPS_Y',        'Coord. Y (GPS)');
define('TEXT_JGE_PANEL_METHODE',      'Méthode de jaugeage');
define('TEXT_JGE_PANEL_TYPE',         'Type de jaugeage');
define('TEXT_JGE_PANEL_METHODE_SEL',  'Méthode');
define('TEXT_JGE_PANEL_DETAILS',      'Détails');
define('TEXT_JGE_PANEL_AGENTS',       'Agents terrain');
define('TEXT_JGE_PANEL_OBS',          'Observations');
define('TEXT_JGE_PANEL_FICHIER',      'Lien fichier');
define('TEXT_JGE_TAB_BRAS',           'JGE - Bras');
define('TEXT_JGE_TAB_NEW_BRAS',       'Nouveau bras');
define('TEXT_JGE_ETL_ERR_DATE',       'La date de jaugeage n’est pas au bon format (jj-mm-aaaa)');
define('TEXT_JGE_ETL_ERR_VALUES',     'Les valeurs de hauteur moyenne et de débit doivent être des nombres');

// -----------------------------------------------
// Gauging deletion
define('TEXT_JGE_DEL_STATION',              'Station : ');
define('TEXT_JGE_DEL_NOT_FOUND',            'Ce jaugeage n’existe pas et ne peut pas être supprimé.');
define('TEXT_JGE_DEL_INVALID',              'L’identifiant du jaugeage est invalide.');



// -----------------------------------------------
// Gauging deletion confirmation
define('TEXT_JGE_VERIFDEL_IRREVERSIBLE',    'Cette action est irréversible.');
define('TEXT_JGE_VERIFDEL_OK',              'Confirmer');
define('TEXT_JGE_VERIFDEL_CANCEL',          'Annuler');


// -----------------------------------------------
// Gauging deletion confirmation - arm
define('TEXT_JGE_BRAS_VERIFDEL_TITLE',      'Confirmer la suppression de ce bras de jaugeage ?');
define('TEXT_JGE_BRAS_VERIFDEL_WARNING',    'Attention !');
define('TEXT_JGE_BRAS_VERIFDEL_UNSAVED',    'Toute modification non enregistrée sera perdue.');

// -----------------------------------------------
// Gauging deletion confirmation - arm
define('TEXT_JGE_VERIFDEL_TITLE',           'Confirmer la suppression de ce jaugeage ?');
define('TEXT_JGE_BRAS_DEL_SUCCESS',   'Bras supprimé avec succès.');
define('TEXT_JGE_BRAS_DEL_ERR_DB',        'Erreur de connexion à la base de données.');
define('TEXT_JGE_BRAS_DEL_ERR_INVALID',   'Identifiant de bras invalide.');
define('TEXT_JGE_BRAS_DEL_ERR_NOT_FOUND', 'Bras introuvable ou hors de votre territoire.');
define('TEXT_JGE_BRAS_DEL_ERR_FAILED',    'Échec de la suppression du bras.');
define('TEXT_JGE_BRAS_DEL_LOG',           'Suppression d’un bras de jaugeage');


// -----------------------------------------------
// Gauging equipment - propeller detail popup
define('TEXT_JGE_HELICE_INFO_TITLE', 'Afficher le détail de l’équation de l’hélice');
define('TEXT_JGE_HELICE_TITLE',             'Description hélice : ');
define('TEXT_JGE_HELICE_CLOSE',             'Fermer');
define('TEXT_JGE_HELICE_EQ_TITLE',          'Équations de vitesse :');
define('TEXT_JGE_HELICE_MULT_N_PRE', '*');
define('TEXT_JGE_HELICE_N_LTE',             '<=');
define('TEXT_JGE_HELICE_N_GT',              '<');
define('TEXT_JGE_HELICE_V_EQ',              'v =');
define('TEXT_JGE_HELICE_MULT_N',            '* n +');
define('TEXT_JGE_HELICE_FORMULA_TITLE',     'Formule d’étalonnage de l’hélice');
define('TEXT_JGE_HELICE_FORMULA',           'v = k * n + a');
define('TEXT_JGE_HELICE_VAR_V',             ' : vitesse du courant [m/s]');
define('TEXT_JGE_HELICE_VAR_K',             ' : pas hydraulique de l’hélice [m]');
define('TEXT_JGE_HELICE_VAR_N',             ' : vitesse de rotation de l’hélice [tr/s]');
define('TEXT_JGE_HELICE_VAR_A',             ' : constante de frottement [m/s]');

define('TEXT_JGE_BRAS_STREAMPRO', 'StreamPro');

define('TEXT_JGE_BRAS_NEED_HELICE',          'Merci de sélectionner une hélice avant de saisir les points.');
define('TEXT_JGE_BRAS_MOULINET_PLACEHOLDER', 'Choisir un moulinet...');
define('TEXT_JGE_BRAS_HELICE_PLACEHOLDER',   'Choisir une hélice...');

// -----------------------------------------------
// Quick gauging entry popup

// -----------------------------------------------
// JGE SIMPLE — Labels du popup (probablement déjà définis, listés pour référence)
 
define('TEXT_JGE_SIMPLE_TITLE',                  'Saisie rapide d’un jaugeage');
define('TEXT_JGE_SIMPLE_STATION',                'Station');
define('TEXT_JGE_SIMPLE_DEBIT',                  'Débit (m3/s)');
define('TEXT_JGE_SIMPLE_HAUTEUR',                'Hauteur (cm)');
define('TEXT_JGE_SIMPLE_DATE',                   'Date');
define('TEXT_JGE_SIMPLE_HEURE',                  'Heure');
define('TEXT_JGE_SIMPLE_OBS',                    'Observation');
define('TEXT_JGE_SIMPLE_CODE_QUAL',              'Code qualité');
define('TEXT_JGE_SIMPLE_SAVE',                   'Enregistrer');

// -----------------------------------------------
// JGE DELETION — Suppression AJAX d'un jaugeage
 
// Popup de confirmation — indication du challenge mathématique
define('TEXT_JGE_VERIFDEL_CHALLENGE_HINT',       'Pour confirmer la suppression, résolvez cette opération simple :');
 
// Message de succès — %1$s = date, %2$s = code/nom de station
define('TEXT_JGE_DEL_SUCCESS',                   'Le jaugeage du %1$s a bien été supprimé - Station : %2$s.');
 
// Entrée de log (écrite dans TABLE_ACTIONS.info)
define('TEXT_JGE_DEL_LOG',                       'Suppression Jaugeage');
 
// Messages d'erreur
define('TEXT_JGE_DEL_ERR_INVALID',               'Identifiant de jaugeage invalide.');
define('TEXT_JGE_DEL_ERR_NOT_FOUND',             'Jaugeage introuvable ou hors de votre territoire.');
define('TEXT_JGE_DEL_ERR_FAILED',                'Échec de la suppression en base de données.');
define('TEXT_JGE_DEL_ERR_DB',                    'Impossible de se connecter à la base de données.');
 
// -----------------------------------------------
// JGE SIMPLE — Nouvelles constantes pour le refactor AJAX
 
define('TEXT_JGE_SIMPLE_CODE_QUAL_PLACEHOLDER',  'Choisir un code qualité...');
 
// Messages de succès — %s sera remplacé par sprintf() avec les infos de la station
define('TEXT_JGE_SIMPLE_CREATED',                'Jaugeage créé avec succès pour la station %s.');
define('TEXT_JGE_SIMPLE_UPDATED',                'Jaugeage mis à jour avec succès pour la station %s.');
 
// Entrées de log (écrites dans TABLE_ACTIONS.info)
define('TEXT_JGE_SIMPLE_LOG_CREATE',             'Création Jaugeage');
define('TEXT_JGE_SIMPLE_LOG_UPDATE',             'Modification Jaugeage');
 
// Messages d'erreur
define('TEXT_JGE_SIMPLE_ERR_HMOY',               'La hauteur est obligatoire et doit être un nombre.');
define('TEXT_JGE_SIMPLE_ERR_Q',                  'Le débit est obligatoire et doit être un nombre.');
define('TEXT_JGE_SIMPLE_ERR_DATE',               'Le format de la date est invalide. Attendu : jj-mm-aaaa.');
define('TEXT_JGE_SIMPLE_ERR_HEURE',              'Le format de l’heure est invalide. Attendu : hh:mm:ss.');
define('TEXT_JGE_SIMPLE_ERR_STATION',            'Une station valide doit être sélectionnée.');
define('TEXT_JGE_SIMPLE_ERR_DB',                 'Impossible de se connecter à la base de données.');

// -----------------------------------------------
// Measurement point observation popup
define('TEXT_JGE_OBS_TITLE',                'Observation du point de mesure');
define('TEXT_JGE_OBS_VERTICALE',            'N° verticale');
define('TEXT_JGE_OBS_DIST',                 'Distance depuis le début [m]');
define('TEXT_JGE_OBS_PROF',                 'Profondeur de mesure [m]');
define('TEXT_JGE_OBS_OBS',                  'Observation');
define('TEXT_JGE_OBS_VALIDATE',             'Valider');

// -----------------------------------------------
// Gauging points data entry
define('TEXT_JGE_PTS_TITLE',                'Saisie des données de jaugeage par points de mesure');
define('TEXT_JGE_PTS_CLOSE',                'Fermer');
define('TEXT_JGE_PTS_TT_VERTICALE', 'Verticale');
define('TEXT_JGE_PTS_CALC_TITLE',           'Calculer vitesses et débit');
define('TEXT_JGE_PTS_CALC_BTN',             'Valider et calculer le débit');
define('TEXT_JGE_PTS_HELP',                'Aide à la saisie');
define('TEXT_JGE_PTS_INPUT_LABEL',          'Données à saisir : ');
define('TEXT_JGE_PTS_OPT_TOPS',             'Nb tours hélice (TOPs)');
define('TEXT_JGE_PTS_OPT_TOPS_SEC',         'Tours hélice/seconde (TOPs/s)');
define('TEXT_JGE_PTS_OPT_VITESSE',          'Vitesse mesurée');
define('TEXT_JGE_PTS_COL_VERT',             'Vert. n°');
define('TEXT_JGE_PTS_COL_VERT_TITLE',       'Numéro verticale');
define('TEXT_JGE_PTS_COL_DIST',             'Dist. départ');
define('TEXT_JGE_PTS_COL_DIST_TITLE',       'Distance depuis le départ');
define('TEXT_JGE_PTS_COL_PROFMAX',          'Prof. totale [m]');
define('TEXT_JGE_PTS_COL_PROFMAX_TITLE',    'Profondeur totale de la verticale');
define('TEXT_JGE_PTS_COL_PROFMESURE',       'Prof. mesurée [m]');
define('TEXT_JGE_PTS_COL_PROFMESURE_TITLE', 'Profondeur de mesure');
define('TEXT_JGE_PTS_COL_TOPS',             'TOPs');
define('TEXT_JGE_PTS_COL_TOPS_TITLE',       'Nb tours hélice');
define('TEXT_JGE_PTS_COL_TEMPS',            'Temps [s]');
define('TEXT_JGE_PTS_COL_TEMPS_TITLE',      'Temps d’enregistrement');
define('TEXT_JGE_PTS_COL_TOPS_SEC',         'TOPs/s');
define('TEXT_JGE_PTS_COL_TOPS_SEC_TITLE',   'Tours hélice/seconde');
define('TEXT_JGE_PTS_COL_VITESSE',          'Vitesse [m/s]');
define('TEXT_JGE_PTS_COL_VITESSE_TITLE',    'Vitesse');
define('TEXT_JGE_PTS_COL_OBS',              'Observation');

define('TEXT_JGE_PTS_CONFIRM_TITLE',      'Fermer la saisie');
define('TEXT_JGE_PTS_CONFIRM_CLOSE',      'Voulez-vous calculer le débit avant de fermer, fermer sans enregistrer, ou rester sur la saisie ?');
define('TEXT_JGE_PTS_CONFIRM_CANCEL',     'Annuler');
define('TEXT_JGE_PTS_CONFIRM_CLOSE_ONLY', 'Fermer sans calculer');
define('TEXT_JGE_PTS_CONFIRM_CALC_CLOSE', 'Calculer et fermer');

// -----------------------------------------------
// Rating curve display popup (gauging view)
define('TEXT_JGE_ETL_BOX_TITLE',            'Courbe d’étalonnage');
define('TEXT_JGE_ETL_CLOSE',                'Fermer');
define('TEXT_JGE_ETL_LOADING',              'Chargement...');

// -----------------------------------------------
// Gauging arm form - channel / river
define('TEXT_JGE_BRAS_HEURE_FIRST',         'Heure début');
define('TEXT_JGE_BRAS_ECH_FIRST',           'Jauge début [cm]');
define('TEXT_JGE_BRAS_HEURE_END',           'Heure fin');
define('TEXT_JGE_BRAS_ECH_END',             'Jauge fin [cm]');
define('TEXT_JGE_BRAS_FOND',                'Substrat du lit');
define('TEXT_JGE_BRAS_BERGE',               'Berge de départ');
define('TEXT_JGE_BRAS_RIVE_GAUCHE',         'Rive gauche');
define('TEXT_JGE_BRAS_RIVE_DROITE',         'Rive droite');
define('TEXT_JGE_BRAS_OBS',                 'Observation');
define('TEXT_JGE_BRAS_DELETE',              'Supprimer bras');
define('TEXT_JGE_BRAS_DEPOUIL_TITLE',       'Calcul du jaugeage');
define('TEXT_UNSAVED_CHANGES',              'Modifications non enregistrées');
define('TEXT_JGE_BRAS_PERCHE_TITLE',        'Diamètre perche');
define('TEXT_JGE_BRAS_PERCHE_LABEL',        'Diam. perche [mm]');
define('TEXT_JGE_BRAS_MOULINET',            'Moulinet');
define('TEXT_JGE_BRAS_HELICE',              'Hélice');
define('TEXT_JGE_BRAS_SAISIE_BTN',          'Saisir données jaugeage');
define('TEXT_JGE_BRAS_Q_TITLE',             'Débit instantané');
define('TEXT_JGE_BRAS_Q_LABEL',             'Débit (Q) [m³/s]');
define('TEXT_JGE_BRAS_HMOY_TITLE',          'Hauteur moy.');
define('TEXT_JGE_BRAS_HMOY_LABEL',          'Hauteur moy. [cm]');
define('TEXT_JGE_BRAS_VMOY_TITLE',          'Vitesse moyenne');
define('TEXT_JGE_BRAS_VMOY_LABEL',          'Vit. moy. [m/s]');
define('TEXT_JGE_BRAS_VSURF_TITLE',         'Vitesse moy. de surface');
define('TEXT_JGE_BRAS_VSURF_LABEL',         'Vit. surf. moy. [m/s]');
define('TEXT_JGE_BRAS_SURFMOUIL_TITLE',     'Section mouillée');
define('TEXT_JGE_BRAS_SURFMOUIL_LABEL',     'Sect. mouillée [m²]');
define('TEXT_JGE_BRAS_PERIMOUIL_TITLE',     'Périmètre mouillé');
define('TEXT_JGE_BRAS_PERIMOUIL_LABEL',     'Périm. mouillé [m]');
define('TEXT_JGE_BRAS_PROFMOY_TITLE',       'Profondeur moyenne');
define('TEXT_JGE_BRAS_PROFMOY_LABEL',       'Prof. moy. [cm]');
define('TEXT_JGE_BRAS_DISTMAX_TITLE',       'Largeur totale du chenal');
define('TEXT_JGE_BRAS_DISTMAX_LABEL',       'Largeur tot. [m]');
define('TEXT_JGE_BRAS_RH_TITLE',            'Rayon hydraulique Rh');
define('TEXT_JGE_BRAS_RH_LABEL',            'Rayon hydr.');
define('TEXT_JGE_BRAS_GRAPH_PLACEHOLDER',   '- Graphique : Coupe transversale -');

// -----------------------------------------------
// Gauging save - validation & result messages
define('TEXT_JGE_SAVE_ERR_HMOY',                '- La valeur « Hauteur moyenne » doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_Q',                   '- La valeur « Débit » doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_DATE',                '- Le format de la date de jaugeage est incorrect : jj-mm-aaaa.');
define('TEXT_JGE_SAVE_ERR_HEURE',               '- Le format de l’heure de jaugeage est incorrect : hh:mm:ss ou hh:mm.');
define('TEXT_JGE_SAVE_ERR_STATION',             '- Le jaugeage doit être lié à une station.');
define('TEXT_JGE_SAVE_ERR_DIST',                '- La valeur « Distance site » doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_BRAS_FIRST_REQUIRED', '- Bras %d : l’heure de début et la hauteur d’échelle de début sont obligatoires.');
define('TEXT_JGE_SAVE_ERR_BRAS_HFIRST',         '- Bras %d : le format de l’heure de début est incorrect : hh:mm:ss ou hh:mm.');
define('TEXT_JGE_SAVE_ERR_BRAS_ECHFIRST',       '- Bras %d : la valeur de la jauge de début doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_BRAS_HEND',           '- Bras %d : le format de l’heure de fin est incorrect : hh:mm:ss ou hh:mm.');
define('TEXT_JGE_SAVE_ERR_BRAS_ECHEND',         '- Bras %d : la valeur de la jauge de fin doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_BRAS_PERCHE',         '- Bras %d : la valeur du diamètre de la perche doit être un nombre.');
define('TEXT_JGE_SAVE_ERR_BRAS_FIELDS',         '- Tous les champs d’heure et de jauge doivent être remplis.');
define('TEXT_JGE_SAVE_ERR_PTS_TITLE',           'Une erreur est survenue : le jaugeage n’a pas pu être enregistré.');
define('TEXT_JGE_SAVE_ERR_PTS_FORMAT',          '- Certaines valeurs du tableau « Points de jaugeage » ne sont pas au bon format (attendu : numérique).');
define('TEXT_JGE_SAVE_ERR_TRANSACTION',         'Un problème est survenu lors de l’écriture des données dans les tables.');
define('TEXT_JGE_SAVE_ERR_EXCEPTION',           'Une erreur est survenue : ');
define('TEXT_JGE_SAVE_ERR_GENERAL',             'Une erreur est survenue : le jaugeage n’a pas pu être enregistré.');
define('TEXT_JGE_SAVE_ERR_METHOD',              'Une erreur est survenue lors de l’envoi des données au serveur.');
define('TEXT_JGE_SAVE_CREATED',                 'Nouveau jaugeage créé avec succès.');
define('TEXT_JGE_SAVE_UPDATED',                 'Jaugeage mis à jour avec succès.');
define('TEXT_JGE_SAVE_STATION_LABEL',           'Station : ');
define('TEXT_JGE_SAVE_ACTION_CREATE',           'Nouveau jaugeage créé : ');
define('TEXT_JGE_SAVE_ACTION_UPDATE',           'Jaugeage mis à jour : ');

// -----------------------------------------------
// Gauging by points (js_jge.js)
// -----------------------------------------------
 
// Row delete button
define('TEXT_JGE_BTN_DELETE_TITLE',   'Supprimer');
 
// Console warning
define('TEXT_JGE_WARN_NO_FREE_ROW',   'Plus de lignes disponibles pour pré-remplir la verticale ');
 
// calc_q() return messages
define('TEXT_JGE_MSG_CALC_OK',        'Le calcul a bien été réalisé.');
define('TEXT_JGE_MSG_CALC_OK_REMIND', 'N’oubliez pas d’enregistrer la fiche de Jaugeage, sinon les données seront perdues');
define('TEXT_JGE_MSG_CALC_ERR',       'Erreur !!!');
define('TEXT_JGE_MSG_CALC_ERR_RUN',   'Le calcul du Jaugeage n’a pas pû s’exécuter');
define('TEXT_JGE_MSG_CALC_ERR_EMPTY', 'Aucune donnée n’a été saisie');
 
// Plotly graph — trace names
define('TEXT_JGE_TRACE_POINTS_NAME',  'Points du JGE');
define('TEXT_JGE_TRACE_BED_NAME',     'Profil du lit');
define('TEXT_JGE_TRACE_VSURF_NAME', 'Vitesse de surface');
define('TEXT_JGE_TRACE_VMOY_NAME',  'Vitesse moyenne');
define('TEXT_JGE_AXIS_VELOCITY',    'Vitesse [m/s]');
define('TEXT_JGE_TT_VERTICALE',     'Verticale'); 
 
// Plotly graph — tooltip labels
define('TEXT_JGE_TT_DISTANCE',        'Distance');
define('TEXT_JGE_TT_DEPTH',           'Profondeur');
define('TEXT_JGE_TT_VELOCITY',        'Vitesse');
define('TEXT_JGE_TT_OBSERVATION',     'Observation');
 
// Plotly graph — axis titles
define('TEXT_JGE_AXIS_DISTANCE',      'Distance [m]');
define('TEXT_JGE_AXIS_DEPTH',         'Profondeur [m]');

// -----------------------------------------------
// Gauging - rating curve graph
define('TEXT_JGE_ETL_STATION',              'Station :');
define('TEXT_JGE_ETL_PERIODE',              'Période de validité de la courbe d’étalonnage :');
define('TEXT_JGE_ETL_DU',                   'du');
define('TEXT_JGE_ETL_AU',                   'au');
define('TEXT_JGE_ETL_NO_ETL',               'Aucune courbe d’étalonnage ne couvre cette date de jaugeage.');
define('TEXT_JGE_ETL_NO_JGE',               'Aucune donnée de jaugeage (JGE) trouvée.');
define('TEXT_JGE_ETL_NB_PTS',               'Nombre de points de jaugeage dans la période :');
define('TEXT_JGE_ETL_ENCOURS',              'En cours');
define('TEXT_JGE_ETL_AXIS_H',               'Hauteur (cm)');
define('TEXT_JGE_ETL_AXIS_Q',               'Débit (m³/s)');

// =============================================================================
// AGENT MODULE
// =============================================================================
// -----------------------------------------------
// Agent list
define('TEXT_AGENT_LIST_TITLE',          'Liste des agents');
define('TEXT_AGENT_LIST_NEW_BTN',        'Nouvel agent');
define('TEXT_AGENT_LIST_SEARCH',         'Rechercher');
define('TEXT_AGENT_LIST_COUNT',          'Total agents : ');
define('TEXT_AGENT_LIST_COUNT_SERVICE',  'Service ');
define('TEXT_AGENT_LIST_COUNT_TERRAIN',  'Agents terrain : ');
define('TEXT_AGENT_LOADING',             'Chargement...');
define('TEXT_AGENT_LOADING_WAIT',        '- Veuillez patienter -');

// -----------------------------------------------
// Agent list - table headers
define('TEXT_AGENT_TH_NOM',         'Nom');
define('TEXT_AGENT_TH_PRENOM',      'Prénom');
define('TEXT_AGENT_TH_EMAIL',       'Email');
define('TEXT_AGENT_TH_TEL',         'Téléphone');
define('TEXT_AGENT_TH_INSTITUTION', 'Institution');
define('TEXT_AGENT_TH_FONCTION',    'Fonction');
define('TEXT_AGENT_TH_SERVICE',     '');
define('TEXT_AGENT_TH_TERRAIN',     'Agent terrain');

// -----------------------------------------------
// Agent form
define('TEXT_AGENT_NOM',               'Nom');
define('TEXT_AGENT_NOM_MARITAL',       'Nom marital');
define('TEXT_AGENT_PRENOM',            'Prénom');
define('TEXT_AGENT_SECTION_ACTIVITE',  'Activité');
define('TEXT_AGENT_INSTITUTION',       'Institution / Société');
define('TEXT_AGENT_FONCTION',          'Fonction');
define('TEXT_AGENT_NUMINSCRIPTION',    'N° inscription');
define('TEXT_AGENT_SECTION_COORDONNEES', 'Coordonnées');
define('TEXT_AGENT_TEL',               'Téléphone');
define('TEXT_AGENT_MOBILE',            'Mobile');
define('TEXT_AGENT_FAX',               'Fax');
define('TEXT_AGENT_EMAIL',             'Email');
define('TEXT_AGENT_SITEWEB',           'Site web');
define('TEXT_AGENT_SECTION_ADRESSE',   'Adresse');
define('TEXT_AGENT_RUE',               'Rue');
define('TEXT_AGENT_LIEUDIT',           'Lieu-dit');
define('TEXT_AGENT_BP',                'BP');
define('TEXT_AGENT_CODEPOSTAL',        'Code postal');
define('TEXT_AGENT_COMMUNE',           'Commune');
define('TEXT_AGENT_CHECK_TERRAIN',     'Agent terrain');
define('TEXT_AGENT_CHECK_SERVICE',     '');
define('TEXT_AGENT_BTN_SAVE',          'Enregistrer');
define('TEXT_AGENT_BTN_CANCEL',        'Annuler');
define('TEXT_AGENT_BTN_DELETE',        'Supprimer');
define('TEXT_AGENT_FICHE_TITLE',       'Agent :');
define('TEXT_AGENT_FICHE_NEW',         'Créer un nouvel agent');

// -----------------------------------------------
// Agent status badges
define('TEXT_AGENT_PUCE_SERVICE',  '');
define('TEXT_AGENT_PUCE_TERRAIN',  'Agent terrain');
define('TEXT_AGENT_DEL_LINK_TITLE', 'Supprimer agent');
define('TEXT_AGENT_NOT_FOUND',     'Aucun agent trouvé');
define('TEXT_AGENT_LABEL',         'Agent');

// -----------------------------------------------
// Agent deletion
define('TEXT_AGENT_DEL_CONFIRM_TITLE', 'Confirmer la suppression de cet agent ?');
define('TEXT_AGENT_DEL_NOM',           'Nom : ');
define('TEXT_AGENT_DEL_PRENOM',        'Prénom : ');
define('TEXT_AGENT_DEL_SUCCESS',    'L’agent a été supprimé avec succès');
define('TEXT_AGENT_DEL_ERROR',      'Une erreur est survenue lors de la suppression de l’agent');
define('TEXT_AGENT_DEL_ACTION_LOG', 'Agent supprimé');

// -----------------------------------------------
// Agent save - validation & result messages
define('TEXT_AGENT_SAVE_CREATED',          'Agent créé avec succès');
define('TEXT_AGENT_SAVE_UPDATED',          'Agent mis à jour avec succès');
define('TEXT_AGENT_SAVE_ERR_NOM',          'Le champ "Nom" est obligatoire');
define('TEXT_AGENT_SAVE_ERR_DUPLICATE',    'Un agent avec le même nom et prénom existe déjà :');
define('TEXT_AGENT_SAVE_ERR_DUPLICATE_SUFFIX', 'Cet agent ne peut pas être ajouté à nouveau');
define('TEXT_AGENT_SAVE_ERR_GENERAL',      'Erreur : l’agent n’a pas pu être enregistré');
define('TEXT_AGENT_SAVE_ERR_REQUEST',      'Une erreur est survenue lors de l’envoi des données au serveur');
define('TEXT_AGENT_SAVE_ACTION_CREATE',    'Nouvel agent créé');
define('TEXT_AGENT_SAVE_ACTION_UPDATE',    'Agent mis à jour');

// =============================================================================
// SETTINGS - GEOGRAPHIC ZONES
// =============================================================================
// -----------------------------------------------
// gestion_geo.php - page title, save button, tabs
define('TEXT_GEO_PAGE_TITLE',       'Saisie des données géographiques');
define('TEXT_GEO_BTN_SAVE',         'Enregistrer');

// Tab labels - TEXT_GEO_TAB_REGION has $theme_region appended at runtime
define('TEXT_GEO_TAB_REGION',       'Régions - ');
define('TEXT_GEO_TAB_COMMUNES',     'Communes');
define('TEXT_GEO_TAB_REGIONHYDRO',  'Régions hydrologiques');
define('TEXT_GEO_TAB_RIVIERES',     'Cours d’eau');
define('TEXT_GEO_TAB_AQUIFERES',    'Aquifères');
define('TEXT_GEO_TAB_TOURNEES',     'Tournées');

// -----------------------------------------------
// Shared table column headers (used by multiple process_tab_*.php files)
define('TEXT_GEO_TH_INTITULE',      'Nom');
define('TEXT_GEO_TH_DESCRIPTION',   'Description');
define('TEXT_GEO_BTN_DELETE',       'Supprimer');
define('TEXT_GEO_NO_DATA',          'Aucune donnée trouvée');

// -----------------------------------------------
// process_tab_region.php - geographic region table
define('TEXT_GEO_REGION_TH',        'Nom - ');
define('TEXT_GEO_REGION_ADD',       'Ajouter - ');

// -----------------------------------------------
// process_tab_commune.php - town table
define('TEXT_GEO_COMMUNE_TH_NOM',    'Nom commune');
define('TEXT_GEO_COMMUNE_TH_REGION', 'Région associée');
define('TEXT_GEO_COMMUNE_ADD',       'Ajouter une commune');

// -----------------------------------------------
// process_tab_regionhydro.php - hydrological region table
define('TEXT_GEO_REGIONHYDRO_ADD',   'Ajouter une région hydrologique');

// -----------------------------------------------
// process_tab_riviere.php - river table
define('TEXT_GEO_RIVIERE_TH_NOM',    'Nom cours d’eau');
define('TEXT_GEO_RIVIERE_TH_REGION', 'Région hydrologique associée');
define('TEXT_GEO_RIVIERE_ADD',       'Ajouter un cours d’eau');

// -----------------------------------------------
// process_tab_aquifere.php - aquifer table
define('TEXT_GEO_AQUIFERE_ADD',      'Ajouter un aquifère');

// -----------------------------------------------
// process_tab_tournee.php - round table
define('TEXT_GEO_TOURNEE_ADD',       'Ajouter une tournée');

// -----------------------------------------------
// process_datageo_save.php - bulk save result messages
define('TEXT_GEO_SAVE_OK',          'Les données géographiques (Régions, Communes, Régions hydrologiques, Cours d’eau, Aquifères et Tournées) ont été enregistrées avec succès.');
define('TEXT_GEO_SAVE_ERR_WRITE',   'Une erreur est survenue lors de l’écriture des données géographiques dans la base.');
define('TEXT_GEO_SAVE_ERR_DETAIL',  'Détails de l’erreur : ');
define('TEXT_GEO_SAVE_ERR_REQUEST', 'Une erreur est survenue lors de l’envoi des données au serveur.');
define('TEXT_GEO_SAVE_ACTION_LOG',  'Données géographiques enregistrées');

// -----------------------------------------------
// Deletion handlers
// -----------------------------------------------
// process_delaquifere.php - aquifer deletion
define('TEXT_GEO_AQUIFERE_DEL_OK',          'L’aquifère a été supprimé avec succès.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_LINKED',  'n’a pas pu être supprimé.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_STATION', 'Il est lié à au moins une station.');
define('TEXT_GEO_AQUIFERE_DEL_ERR_NOTFOUND', 'L’aquifère n’existe pas.');

// -----------------------------------------------
// process_delregiongeo.php - geographic region deletion messages
define('TEXT_GEO_REGION_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_GEO_REGION_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_GEO_REGION_DEL_ERR_DEPENDENCY', 'Il est lié à au moins une commune ou une station.');
define('TEXT_GEO_REGION_DEL_ERR_NOTFOUND', 'La région géographique n’existe pas.');

// -----------------------------------------------
// process_delcommune.php - town deletion messages
define('TEXT_GEO_COMMUNE_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_GEO_COMMUNE_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_GEO_COMMUNE_DEL_ERR_STATION',  'Il est lié à au moins une station.');
define('TEXT_GEO_COMMUNE_DEL_ERR_NOTFOUND', 'La commune n’existe pas.');

// -----------------------------------------------
// process_delregionhydro.php - hydrological region deletion messages
define('TEXT_GEO_REGIONHYDRO_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_DEPENDENCY', 'Il est lié à au moins un cours d’eau ou une station.');
define('TEXT_GEO_REGIONHYDRO_DEL_ERR_NOTFOUND', 'La région hydrologique n’existe pas.');

// -----------------------------------------------
// process_delriviere.php - river deletion messages
define('TEXT_GEO_RIVIERE_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_GEO_RIVIERE_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_GEO_RIVIERE_DEL_ERR_STATION',  'Il est lié à au moins une station.');
define('TEXT_GEO_RIVIERE_DEL_ERR_NOTFOUND', 'Le cours d’eau n’existe pas.');

// -----------------------------------------------
// process_deltournee.php - round deletion messages
define('TEXT_GEO_TOURNEE_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_GEO_TOURNEE_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_GEO_TOURNEE_DEL_ERR_STATION',  'Il est lié à au moins une station.');
define('TEXT_GEO_TOURNEE_DEL_ERR_NOTFOUND', 'La tournée n’existe pas.');

// -----------------------------------------------
// GEO DELETION — Popup de confirmation
// %s est remplacé par sprintf() avec le nom de l'entité (HTML en gras)
 
define('TEXT_GEO_VERIFDEL_TITLE',            'Confirmer la suppression');
define('TEXT_GEO_VERIFDEL_IRREVERSIBLE',     'Cette action est irréversible.');
define('TEXT_GEO_VERIFDEL_CHALLENGE_HINT',   'Pour confirmer la suppression, résolvez cette opération simple :');
define('TEXT_GEO_VERIFDEL_OK',               'Confirmer');
define('TEXT_GEO_VERIFDEL_CANCEL',           'Annuler');
 
// Phrases de cible — %s est remplacé par le nom de l'entité en gras
define('TEXT_GEO_VERIFDEL_TARGET_REGION',       'Supprimer la région %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_COMMUNE',      'Supprimer la commune %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_REGIONHYDRO',  'Supprimer la région hydrologique %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_RIVIERE',      'Supprimer la rivière %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_AQUIFERE',     'Supprimer l’aquifère %s ?');
define('TEXT_GEO_VERIFDEL_TARGET_TOURNEE',      'Supprimer la tournée %s ?');

// -----------------------------------------------
// GEO SAVE — Messages d'erreur de validation
// Les %s sont remplis par sprintf() au runtime
 
define('TEXT_GEO_SAVE_ERR_VALIDATION',    'Échec de l’enregistrement - merci de corriger les erreurs ci-dessous :');
 
// %1$s = libellé du contexte (région / commune / etc.)
define('TEXT_GEO_SAVE_ERR_NOM_EMPTY',     'Le nom de %s ne peut pas être vide.');
 
// %1$s = libellé du contexte, %2$d = longueur max
define('TEXT_GEO_SAVE_ERR_NOM_TOO_LONG',  'Le nom de %1$s est trop long (max %2$d caractères).');
 
// %1$s = libellé du contexte, %2$d = longueur max
define('TEXT_GEO_SAVE_ERR_DESC_TOO_LONG', 'La description de %1$s est trop longue (max %2$d caractères).');
 
// %1$s = libellé du contexte, %2$s = nom essayé
define('TEXT_GEO_SAVE_ERR_DUPLICATE',     'Un(e) %1$s nommé(e) "%2$s" existe déjà.');
 
 
// -----------------------------------------------
// GEO SAVE — Libellés de contexte (utilisés en %s dans les messages ci-dessus)
 
define('TEXT_GEO_CTX_REGION',       'région');
define('TEXT_GEO_CTX_COMMUNE',      'commune');
define('TEXT_GEO_CTX_REGIONHYDRO',  'région hydrologique');
define('TEXT_GEO_CTX_RIVIERE',      'rivière');
define('TEXT_GEO_CTX_AQUIFERE',     'aquifère');
define('TEXT_GEO_CTX_TOURNEE',      'tournée');

// =============================================================================
// SETTINGS - QUALITY CODES
// =============================================================================
// -----------------------------------------------
// gestion_quality_data.php - page title, save button, tab
define('TEXT_QD_PAGE_TITLE',        'Configuration des codes qualité');
define('TEXT_QD_BTN_SAVE',          'Enregistrer');
define('TEXT_QD_TAB_LABEL',         'Codes qualité');

// URL save-confirmation message shown after page reload
define('TEXT_QD_SAVE_URL_OK',       'Les codes qualité ont été enregistrés avec succès.');

// -----------------------------------------------
// form_qualitydata.php - table headers, new-entry row, dropdown default
define('TEXT_QD_TH_INIT',          'Code');
define('TEXT_QD_TH_NOM',           'Nom complet');
define('TEXT_QD_TH_INFO',          'Description');
define('TEXT_QD_TH_TYPE',          'Type de données');
define('TEXT_QD_TH_COLOR',          'Couleur');
define('TEXT_QD_NEW_ENTRY',        'Ajouter un code qualité');
define('TEXT_QD_TYPE_ALL',         'Tous les types');
define('TEXT_QD_BTN_DELETE',       'Supprimer');

// -----------------------------------------------
// process_delqualitydata.php - deletion feedback messages
define('TEXT_QD_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_QD_DEL_ERR_LINKED',   'n’a pas pu être supprimé.');
define('TEXT_QD_DEL_ERR_DATA',     'Il est lié à au moins un enregistrement de données.');
define('TEXT_QD_DEL_ERR_NOTFOUND', 'Le code qualité n’existe pas.');

// -----------------------------------------------
// process_qualitydata_save.php - save feedback messages
define('TEXT_QD_SAVE_OK',          'Les codes qualité ont été enregistrés avec succès.');
define('TEXT_QD_SAVE_ERR_WRITE',   'Une erreur est survenue lors de l’écriture des données qualité dans la base.');
define('TEXT_QD_SAVE_ERR_DETAIL',  'Détails de l’erreur : ');
define('TEXT_QD_SAVE_ERR_REQUEST', 'Une erreur est survenue lors de l’envoi des données au serveur.');
define('TEXT_QD_SAVE_ERR_DUPLICATE', 'Un code qualité avec le même libellé existe déjà et ne peut pas être ajouté à nouveau : ');
define('TEXT_QD_SAVE_ACTION_LOG',  'Codes qualité enregistrés');

// =============================================================================
// SETTINGS - TIME-SERIES TYPES & AXES
// =============================================================================
// -----------------------------------------------
// gestion_type_data.php - page title, save button, tabs
define('TEXT_TD_PAGE_TITLE', 'Configuration des types de séries temporelles et des axes de graphique');
define('TEXT_TD_BTN_SAVE',   'Enregistrer');
define('TEXT_TD_TAB_CHRON',  'Séries temporelles');
define('TEXT_TD_TAB_AXES',   'Axes');

// -----------------------------------------------
// process_tab_axe.php - axis table headers, new-entry label
define('TEXT_TD_AXE_TH_NAME',   'Nom axe');
define('TEXT_TD_AXE_TH_UNIT',   'Unité');
define('TEXT_TD_AXE_TH_ROUND',  'Arrondi');
define('TEXT_TD_AXE_NEW',       'Ajouter un axe');
define('TEXT_TD_AXE_NO_DATA',   'Aucune donnée trouvée');

// -----------------------------------------------
// process_delaxe.php - axis deletion messages
define('TEXT_TD_AXE_DEL_OK',          'a été supprimé avec succès.');
define('TEXT_TD_AXE_DEL_ERR_LINKED',  'n’a pas pu être supprimé.');
define('TEXT_TD_AXE_DEL_ERR_CHRON',   'Il est lié à au moins un type de série temporelle.');
define('TEXT_TD_AXE_DEL_ERR_NOTFOUND', 'L’axe n’existe pas.');

// -----------------------------------------------
// process_deltypedata.php - time-series type deletion messages
define('TEXT_TD_CHRON_DEL_OK',          'a été supprimé avec succès.');
define('TEXT_TD_CHRON_DEL_ERR_LINKED',  'n’a pas pu être supprimé.');
define('TEXT_TD_CHRON_DEL_ERR_DATA',    'Il est lié à au moins un enregistrement de données.');
define('TEXT_TD_CHRON_DEL_ERR_NOTFOUND', 'Le type de série temporelle n’existe pas.');

// -----------------------------------------------
// form_typedata_chron.php - filter label
define('TEXT_TD_FILTER_LABEL', 'Sélectionner : ');

// -----------------------------------------------
// process_tab_typedata.php - table headers, new-entry labels, dropdown values
define('TEXT_TD_TH_ACRONYM',        'Acronyme');
define('TEXT_TD_TH_NAME',           'Nom');
define('TEXT_TD_TH_DATATYPE',       'Type de données');
define('TEXT_TD_TH_AXIS',           'Axe');
define('TEXT_TD_TH_UNIT',           'Unité');
define('TEXT_TD_TH_ROUND',          'Arrondi');
define('TEXT_TD_TH_TIMESCALE',      'Échelle temporelle');
define('TEXT_TD_TH_PROCESSING',     'Traitement');
define('TEXT_TD_TH_GRAPHTYPE',      'Type de graphique');
define('TEXT_TD_TH_PERIOD_TRANSF',  'Période transf.');
define('TEXT_TD_TH_CHRON_TRANSF',   'Série transf.');
define('TEXT_TD_TH_RAWDATA', 'Données brutes');

define('TEXT_TD_NEW_CHRON',         'Ajouter un type de série');

// Graph-type dropdown options
define('TEXT_TD_GRAPH_LINEAR',      'linéaire');
define('TEXT_TD_GRAPH_BAR',         'histogramme');

// Processing dropdown options
define('TEXT_TD_PROC_VALUE',        'valeur');
define('TEXT_TD_PROC_CUMUL',        'cumul');

// Delete cell - shown when deletion is allowed; dash shown otherwise
define('TEXT_TD_BTN_DELETE',        'Supprimer');

// No-data fallback message
define('TEXT_TD_NO_DATA',           'Aucune donnée trouvée');


// -----------------------------------------------
// process_typedata_save.php - save feedback messages
// Duplicate-label errors
define('TEXT_TD_SAVE_ERR_DUP_CHRON', 'Un type de série avec le même acronyme existe déjà et ne peut pas être ajouté à nouveau : ');
define('TEXT_TD_SAVE_ERR_DUP_AXE',   'Un axe avec le même libellé existe déjà et ne peut pas être ajouté à nouveau : ');

// Success message
define('TEXT_TD_SAVE_OK',            'Les types de séries et axes ont été enregistrés avec succès.');

// Transaction error
define('TEXT_TD_SAVE_ERR_WRITE',     'Une erreur est survenue lors de l’écriture des données de séries et axes dans la base.');
define('TEXT_TD_SAVE_ERR_DETAIL',    'Détails de l’erreur : ');

// Wrong request method
define('TEXT_TD_SAVE_ERR_REQUEST',   'Une erreur est survenue lors de l’envoi des données au serveur.');

// Action log entry
define('TEXT_TD_SAVE_ACTION_LOG',    'Types de séries enregistrés');

// =============================================================================
// SETTINGS - GAUGING EQUIPMENT
// =============================================================================
// -----------------------------------------------
// Shared across all three equipment tabs
define('TEXT_EJ_BTN_DELETE',            'Supprimer');

// gestion eq jaugeage — shared delete confirmation popup
define('TEXT_EJ_DEL_CONFIRM_TITLE',  'Confirmer la suppression');
define('TEXT_EJ_DEL_CONFIRM_MSG',    'Confirmez-vous la suppression de cet equipement ? Cette action est irreversible.');
define('TEXT_EJ_DEL_CONFIRM_OK',     'Supprimer');
define('TEXT_EJ_DEL_CONFIRM_CANCEL', 'Annuler');

// -----------------------------------------------
// form_eq_jge_moulinets.php - current meter tab
define('TEXT_EJ_MOUL_TH_NUM',           'N°');
define('TEXT_EJ_MOUL_TH_FABRICANT',     'Fabricant');
define('TEXT_EJ_MOUL_TH_OBS',           'Observation');
define('TEXT_EJ_MOUL_NEW',              'Ajouter un moulinet');

// -----------------------------------------------
// process_delmoulinet.php - current meter deletion messages
define('TEXT_EJ_MOUL_DEL_OK',           'a été supprimé avec succès.');
define('TEXT_EJ_MOUL_DEL_ERR_LINKED',   'ne peut pas être supprimé.');
define('TEXT_EJ_MOUL_DEL_ERR_JGE',      'Il est lié à au moins un enregistrement de jaugeage.');
define('TEXT_EJ_MOUL_DEL_ERR_NOTFOUND', 'Le moulinet n’existe pas.');

// -----------------------------------------------
// form_eq_jge_helices.php - propeller tab
define('TEXT_EJ_HEL_TH_NUM',            'N°');
define('TEXT_EJ_HEL_TH_DIAM',           'Diamètre');
define('TEXT_EJ_HEL_TH_PAS',            'Pas');
define('TEXT_EJ_HEL_TH_L1',             'l1');
define('TEXT_EJ_HEL_TH_A1',             'a1');
define('TEXT_EJ_HEL_TH_B1',             'b1');
define('TEXT_EJ_HEL_TH_L2',             'l2');
define('TEXT_EJ_HEL_TH_A2',             'a2');
define('TEXT_EJ_HEL_TH_B2',             'b2');
define('TEXT_EJ_HEL_TH_A3',             'a3');
define('TEXT_EJ_HEL_TH_B3',             'b3');
define('TEXT_EJ_HEL_TH_FABRICANT',      'Fabricant');
define('TEXT_EJ_HEL_TH_OBS',            'Observation');
define('TEXT_EJ_HEL_NEW',               'Ajouter une hélice');

// gestion eq jaugeage — propeller equations popup
define('TEXT_EJ_HEL_TH_EQUATIONS', 'Equations');
define('TEXT_EJ_HEL_EQ_BTN',       'Equations');
define('TEXT_EJ_HEL_EQ_TITLE',     'Equations de vitesse de l helice :');
define('TEXT_EJ_HEL_EQ_SUBTITLE',  'Equations de vitesse par palier');
define('TEXT_EJ_HEL_EQ_RANGE_MID', '< n <=');
define('TEXT_EJ_HEL_EQ_OK',        'Valider');
define('TEXT_EJ_HEL_EQ_CANCEL',    'Annuler');
define('TEXT_EJ_HEL_EQ_CLOSE',     'Fermer');
define('TEXT_EJ_HEL_EQ_SAVE_REMINDER', 'Equations mises a jour. Pensez a cliquer sur Enregistrer pour sauvegarder vos modifications.');

// -----------------------------------------------
// process_delhelice.php - propeller deletion messages
define('TEXT_EJ_HEL_DEL_OK',            'a été supprimé avec succès.');
define('TEXT_EJ_HEL_DEL_ERR_LINKED',    'ne peut pas être supprimé.');
define('TEXT_EJ_HEL_DEL_ERR_JGE',       'Il est lié à au moins un enregistrement de jaugeage.');
define('TEXT_EJ_HEL_DEL_ERR_NOTFOUND',  'L’hélice n’existe pas.');

// -----------------------------------------------
// form_eq_jge_saumons.php - weight (saumon) tab
define('TEXT_EJ_SAU_TH_NUM',            'N°');
define('TEXT_EJ_SAU_TH_TITRE',          'Titre');
define('TEXT_EJ_SAU_TH_POIDS',          'Saumons');
define('TEXT_EJ_SAU_TH_DIST_AXE',       'Dist. axe');
define('TEXT_EJ_SAU_TH_TAIR',           'Tair');
define('TEXT_EJ_SAU_TH_RDIST',          'Rdist');
define('TEXT_EJ_SAU_TH_FABRICANT',      'Fabricant');
define('TEXT_EJ_SAU_TH_OBS',            'Observation');
define('TEXT_EJ_SAU_NEW',               'Ajouter un Saumon');

// -----------------------------------------------
// process_delsaumon.php - weight deletion messages
define('TEXT_EJ_SAU_DEL_OK',            'a été supprimé avec succès.');
define('TEXT_EJ_SAU_DEL_ERR_LINKED',    'ne peut pas être supprimé.');
define('TEXT_EJ_SAU_DEL_ERR_JGE',       'Il est lié à au moins un enregistrement de jaugeage.');
define('TEXT_EJ_SAU_DEL_ERR_NOTFOUND',  'Le Saumon n’existe pas.');

// -----------------------------------------------
// process_dataeqjge_save.php - save feedback messages
define('TEXT_EJ_SAVE_OK',               'Les équipements de jaugeage (moulinets, hélices et saumons) ont été enregistrés avec succès.');
define('TEXT_EJ_SAVE_ERR_WRITE',        'Une erreur est survenue lors de l’écriture des données d’équipement de jaugeage dans la base.');
define('TEXT_EJ_SAVE_ERR_DETAIL',       'Détails de l’erreur : ');
define('TEXT_EJ_SAVE_ERR_REQUEST',      'Une erreur est survenue lors de l’envoi des données au serveur.');
define('TEXT_EJ_SAVE_ACTION_LOG',       'Équipements de jaugeage enregistrés');

// -----------------------------------------------
// gestion_eq_jaugeage.php - page title, save button, tab labels, URL save message
define('TEXT_EJ_PAGE_TITLE',    'Configuration des équipements de jaugeage');
define('TEXT_EJ_BTN_SAVE',      'Enregistrer');
define('TEXT_EJ_TAB_HELICES',   'Hélices');
define('TEXT_EJ_TAB_MOULINETS', 'Moulinets');
define('TEXT_EJ_TAB_SAUMONS',   'Poids');
define('TEXT_EJ_SAVE_URL_OK',   '<span style="font-size:16px;">Les données des équipements de jaugeage ont été enregistrées avec succès.</span>');

// =============================================================================
// SETTINGS - PARAMETER EXPORT / IMPORT
// =============================================================================
// -----------------------------------------------
// export_param.php - page title, checkboxes, button
define('TEXT_EX_PAGE_TITLE',        'Export/Import des paramètres de la plateforme');
define('TEXT_EX_CHK_ZONEGEO',       'Zones géographiques (Régions géo. / Communes / Régions hydro. / Cours d’eau)');
define('TEXT_EX_CHK_TYPECHRON',     'Types de séries temporelles');
define('TEXT_EX_CHK_STNATURE',      'Types de stations de mesure (hydrométrique / pluviométrique / piézométrique)');
define('TEXT_EX_CHK_CODEQUAL',      'Codes qualité');
define('TEXT_EX_CHK_EQJGE',         'Équipements de jaugeage (Hélices / Moulinets / Saumons)');
define('TEXT_EX_BTN_EXPORT',        'Exporter les données');
define('TEXT_EX_WAIT_FILE',         'Création du fichier...');

// -----------------------------------------------
// export_param.php - JS inline feedback messages
define('TEXT_EX_ERR_NO_PARAM',      'Aucun paramètre sélectionné - le fichier ne peut pas être créé.');
define('TEXT_EX_ERR_GENERATE',      'Une erreur est survenue lors de la génération du fichier.');
define('TEXT_EX_ERR_SERVER',        'Une erreur est survenue lors de la communication avec le serveur.');

// -----------------------------------------------
// process_download.php - file-not-found message
define('TEXT_EX_FILE_NOT_FOUND',    'Le fichier demandé n’existe pas.');

// =============================================================================
// PLATFORM ACTIVITY LOGS
// =============================================================================
// -----------------------------------------------
// Shared column headers
define('TEXT_LS_COL_LOGIN',         'Identifiant');
define('TEXT_LS_COL_NAME',          'Prénom / Nom');
define('TEXT_LS_COL_DATE',          'Date');
define('TEXT_LS_COL_DETAILS',       'Détails');
define('TEXT_LS_COL_CONSULT',       'Consulter');
define('TEXT_LS_COL_TYPE',          'Type');
define('TEXT_LS_COL_STATION',       'Station');

// -----------------------------------------------
// Shared filter labels
define('TEXT_LS_FILTER_USER',       'Utilisateur');
define('TEXT_LS_FILTER_ACTION',     'Action');
define('TEXT_LS_FILTER_DELAY',      'Délai');
define('TEXT_LS_FILTER_STATION',    'Station');

// -----------------------------------------------
// Shared delay prefix labels
define('TEXT_LS_DELAY_LESS',        'moins de ');
define('TEXT_LS_DELAY_MORE',        'plus de ');

// -----------------------------------------------
// Shared empty-result message pattern
define('TEXT_LS_NO_RESULT',         'Aucun résultat trouvé.');

// -----------------------------------------------
// list_actions.php
define('TEXT_LS_ACT_PAGE_TITLE',    'Journal des activités de la plateforme - HydroPacifique');
define('TEXT_LS_ACT_COL_DELAY',     'Délai (jours)');
define('TEXT_LS_ACT_COL_DATE',      'Date de l’action');
define('TEXT_LS_ACT_COL_DETAIL',    'Détail');
define('TEXT_LS_ACT_NB_ACTIONS',    'Nombre d’actions : ');
define('TEXT_LS_ACT_NO_RESULT',     'Aucune action trouvée.');

// -----------------------------------------------
// list_exports.php
define('TEXT_LS_EXP_PAGE_TITLE',    'Exports récents - 24 derniers mois');
define('TEXT_LS_EXP_AVAIL_INFO',    'Les fichiers de données sont disponibles au téléchargement pendant 1 mois.');
define('TEXT_LS_EXP_COL_FILE',      'Fichier à télécharger');
define('TEXT_LS_EXP_NO_RESULT',     'Aucun export trouvé.');

// -----------------------------------------------
// list_imports.php
define('TEXT_LS_IMP_PAGE_TITLE',    'Imports récents - 24 derniers mois');
define('TEXT_LS_IMP_COL_FILE',      'Fichier importé');
define('TEXT_LS_IMP_COL_CHRON',     'Série temporelle');
define('TEXT_LS_IMP_COL_NBDATA',    'Nb données');
define('TEXT_LS_IMP_COL_DATE_S',    'Date début');
define('TEXT_LS_IMP_COL_DATE_E',    'Date fin');
define('TEXT_LS_IMP_NO_RESULT',     'Aucun import trouvé.');

// -----------------------------------------------
// corrections.php
define('TEXT_LS_COR_PAGE_TITLE',    'Journal des corrections de séries temporelles');
define('TEXT_LS_COR_SORT_LABEL',    'TRIER PAR');
define('TEXT_LS_COR_SORT_DATE',     'Date de correction');
define('TEXT_LS_COR_SORT_NAME',     'Nom station');
define('TEXT_LS_COR_SORT_CODE',     'Code station');
define('TEXT_LS_COR_SORT_TYPE',     'Type de données');
define('TEXT_LS_COR_ORDER_ASC',     'Croissant');
define('TEXT_LS_COR_ORDER_DESC',    'Décroissant');
define('TEXT_LS_COR_NB_CORR',       'Nombre de corrections : ');
define('TEXT_LS_COR_COL_CODE',      'Code station');
define('TEXT_LS_COR_COL_NAME',      'Nom station');
define('TEXT_LS_COR_COL_TYPE',      'Type de données');
define('TEXT_LS_COR_NO_RESULT',     'Aucune correction trouvée.');

// =============================================================================
// ADMINISTRATION MODULE
// =============================================================================

define('TEXT_MYACC_TITLE',          'Mon compte');
define('TEXT_MYACC_LOCKED',         'non modifiable');
define('TEXT_MYACC_SAVE_OK',        'Votre profil a été mis à jour.');
define('TEXT_MYACC_ERR_UNEXPECTED', 'Erreur serveur inattendue. Veuillez réessayer.');
define('TEXT_MYACC_ERR_HTTP',       'Erreur serveur');
define('TEXT_MYACC_ERR_NO_SESSION', 'Votre session a expiré. Veuillez vous reconnecter.');

// -----------------------------------------------
// gestion.php - admin home page
define('TEXT_US_APP_SETTINGS',          'Paramètres de l’application');
define('TEXT_US_MENU_USERS',            'Utilisateurs');
define('TEXT_US_MENU_USER_RIGHTS',      'Permissions et paramètres');
define('TEXT_US_MENU_USER_NEW',         'Créer un nouvel utilisateur');
define('TEXT_US_MENU_CONFIG',           'Configuration');
define('TEXT_US_MENU_PLATEFORM',           'Plateforme');
define('TEXT_US_MENU_SERVICE',          'Services (propriétaires de la données)');
define('TEXT_US_MENU_TYPE_MESURE',      'Type de mesure (Pluie / Débit)');

// -----------------------------------------------
// list_users.php - user list page
define('TEXT_US_LIST_PAGE_TITLE',       'Gestion des utilisateurs et permissions');
define('TEXT_US_LIST_COL_FROM',        'Service affilié');
define('TEXT_US_LIST_COL_LOGIN',        'Identifiant');
define('TEXT_US_LIST_COL_NOM',          'Nom');
define('TEXT_US_LIST_COL_PRENOM',       'Prénom');
define('TEXT_US_LIST_COL_EMAIL',       'Email');
define('TEXT_US_LIST_COL_INFO',       'Info');
define('TEXT_US_LIST_COL_DATE_CREATE',  'Date de création');
define('TEXT_US_LIST_COL_LAST_LOG',     'Date dernière connexion');
define('TEXT_US_LIST_COL_NB_LOG',       'Nb connexions');
define('TEXT_US_LIST_COL_ACTIVE',       'Actif');
define('TEXT_US_LIST_BTN_DELETE',       'Supprimer');

// -----------------------------------------------
// modif_user.php - user edit page
define('TEXT_US_EDIT_TITLE_NEW',        'Nouvel utilisateur');
define('TEXT_US_EDIT_TITLE_PREFIX',     'Utilisateur : ');
define('TEXT_US_EDIT_TAB_INFO',         'Informations');
define('TEXT_US_EDIT_TAB_RIGHTS',       'Droits d’accès');

// -----------------------------------------------
// form_user_1.php - user info tab
define('TEXT_US_F1_LOGIN_LABEL',        'Identifiant de connexion');
define('TEXT_US_F1_LOGIN_HINT',         'Ce champ ne doit pas contenir d’espaces, d’accents ou de caractères spéciaux');
define('TEXT_US_F1_NOM_LABEL',          'Nom');
define('TEXT_US_F1_PRENOM_LABEL',       'Prénom');
define('TEXT_US_F1_MAIL_LABEL',       'Courriel');
define('TEXT_US_F1_INFO_LABEL',         'Informations complémentaires');

define('TEXT_US_F1_LANG_LABEL', 'Langue');

define('TEXT_US_F1_PASS_GENERATE',      'Générer un nouveau mot de passe');
define('TEXT_US_F1_PASS_COPY',          'Copier ce mot de passe.<br>Pour des raisons de sécurité, le mot de passe est chiffré. Vous ne pourrez plus y accéder.');

define('TEXT_US_F1_SEND_MAIL_LABEL', 'Envoyer le courriel de connexion');

// -----------------------------------------------
// form_user_2.php - user rights tab
define('TEXT_US_F2_RIGHTS_TITLE',       'Gestion des droits et permissions');
define('TEXT_US_F2_RIGHT_DATA',         'Gestion des données');
define('TEXT_US_F2_RIGHT_DATA_EXPERT',  'Gestion des données - Expert');
define('TEXT_US_F2_RIGHT_PARAM',        'Paramètres');
define('TEXT_US_F2_RIGHT_CONFIG',       'Configuration de l’application');



// -----------------------------------------------
// process_user_save.php — user save processor

define('TEXT_US_CTRL_ERR_LOGIN_EMPTY',  'Le champ Identifiant est obligatoire.');
define('TEXT_US_CTRL_ERR_MAIL_EMPTY',  'Le champ Email est obligatoire.');
define('TEXT_US_CTRL_ERR_LOGIN_DUP',    'Un autre utilisateur possède déjà cet identifiant. Veuillez en choisir un autre.');
define('TEXT_US_CTRL_ERR_LOGIN_CHARS',  'L’identifiant ne doit pas contenir d’espaces, d’accents ou de caractères spéciaux.');
define('TEXT_US_CTRL_ERR_MAIL_INVALID', 'Veuillez saisir une adresse email valide.');
define('TEXT_US_CTRL_MSG_CREATED',      'L’utilisateur a été créé.');
define('TEXT_US_CTRL_MSG_UPDATED',      'L’utilisateur a été mis à jour.');


// -----------------------------------------------
// process_user_sendmail.php — user send mail

define('TEXT_US_WELCOME_MAIL_TITLE',     'Your account has been created');
define('TEXT_US_WELCOME_MAIL_BODY',      'Your account is ready. Click the link below to set your password.');
define('TEXT_US_WELCOME_MAIL_BTN_LINK',  'Set my password');
define('TEXT_US_WELCOME_MAIL_SUBJECT',   'Your account on %s');
define('TEXT_US_WELCOME_MAIL_OK',        'Welcome email sent successfully.');
define('TEXT_US_WELCOME_ERR_NOT_FOUND',  'User not found or email address missing.');

// -----------------------------------------------
// ctrl_user_active.php - bulk active/inactive save
define('TEXT_US_ACTIVE_MSG_OK',         'La liste des utilisateurs a été mise à jour.');

// -----------------------------------------------
// suppr_user.php - user deletion
define('TEXT_US_DEL_ERR_HAS_LOGS',      'Cet enregistrement ne peut pas être supprimé - l’utilisateur a déjà effectué des actions sur la plateforme.');
define('TEXT_US_DEL_ERR_HAS_LOGS2',     'Seul l’accès peut être désactivé.');
define('TEXT_US_DEL_OK',                'L’utilisateur "%s" a été supprimé.');
define('TEXT_US_DEL_ERR_NOT_FOUND',     'Cet utilisateur n’existe pas et ne peut pas être supprimé.');

// ============================================================
// Plateform configuration page
// ============================================================

// ---- Page header ----
define('TEXT_PF_PAGE_TITLE',             'Configuration de la Plateforme');
define('TEXT_PF_LABEL',                  'Général');
define('TEXT_PF_SAVE',                   'Enregistrer');

// ---- Block 1 — Territoire identification ----
define('TEXT_PF_F1_INIT',                'Initiales');
define('TEXT_PF_F1_INIT_HINT',           'Code court du territoire (ex : NC, PF, WF)');
define('TEXT_PF_F1_NOM',                 'Nom');

// ---- Block 2 — Regional settings ----
define('TEXT_PF_F1_THEME',               'Thème régional');
define('TEXT_PF_F1_REGION_DEFAULT',      'Région par défaut');
define('TEXT_PF_F1_SERVICE_HYDRO',       'Service hydro');

// ---- Block 3 — Locale and language ----
define('TEXT_PF_F1_TIMEZONE',            'Fuseau horaire');
define('TEXT_PF_F1_TIMEZONE_HINT',       'Format PHP (ex : Pacific/Tahiti, Pacific/Noumea)');
define('TEXT_PF_F1_LANG',                'Langue');

// ---- Block 4 — Map configuration ----
define('TEXT_PF_F1_MAP_LONG',            'Longitude');
define('TEXT_PF_F1_MAP_LAT',             'Latitude');
define('TEXT_PF_F1_MAP_ZOOM',            'Zoom par défaut');
define('TEXT_PF_F1_MAP_MIN_ZOOM',        'Zoom minimum');

// ---- Save feedback ----
define('TEXT_PF_SAVE_OK',                'Configuration enregistrée avec succès.');
define('TEXT_PF_SAVE_ACTION_LOG',        'Modification de la configuration de la plateforme');

// ---- Save errors ----
define('TEXT_PF_SAVE_ERR_THEME',         'Le thème régional est obligatoire.');
define('TEXT_PF_SAVE_ERR_SERVICE_HYDRO', 'Le service hydro est obligatoire.');
define('TEXT_PF_SAVE_ERR_REGION',        'La région par défaut sélectionnée n’appartient pas à ce territoire.');
define('TEXT_PF_SAVE_ERR_TIMEZONE',      'Fuseau horaire invalide');
define('TEXT_PF_SAVE_ERR_LANG',          'Langue non supportée');
define('TEXT_PF_SAVE_ERR_LONG',          'La longitude doit être comprise entre -180 et 180.');
define('TEXT_PF_SAVE_ERR_LAT',           'La latitude doit être comprise entre -90 et 90.');
define('TEXT_PF_SAVE_ERR_ZOOM',          'Le zoom par défaut doit être compris entre 2 et 16.');
define('TEXT_PF_SAVE_ERR_MIN_ZOOM',      'Le zoom minimum doit être compris entre 2 et 5.');
define('TEXT_PF_SAVE_ERR_ZOOM_ORDER',    'Le zoom minimum ne peut pas être supérieur au zoom par défaut.');
define('TEXT_PF_SAVE_ERR_WRITE',         'Erreur lors de l’enregistrement.');
define('TEXT_PF_SAVE_ERR_DETAIL',        'Détail : ');
define('TEXT_PF_SAVE_ERR_REQUEST',       'Méthode de requête invalide.');

// -----------------------------------------------
// gestion_service.php - service (ownder data) config page

define('TEXT_SV_PAGE_TITLE',       'Services (Propriétaire de la donnée)');
define('TEXT_SV_TAB_LABEL',        'Information');
define('TEXT_SV_SAVE',        'Save');

define('TEXT_SV_COL_NAME',        '');
define('TEXT_SV_COL_DESC',        'Description');
define('TEXT_SV_COL_LOCAL',        'Local');
define('TEXT_SV_COL_CONTACT',        'Contact');
define('TEXT_SV_COL_CONTACT_MAIL',        'Contact Email');
define('TEXT_SV_NEW_ENTRY',        'Nouveau Service');

define('TEXT_SV_DEL_CONFIRM_TITLE','Supprimer un service');
define('TEXT_SV_DEL_CONFIRM_MSG','Vous êtes sur le point de supprimer définitivement le service suivant.<br>Cette action est irréversible.');
define('TEXT_SV_DEL_CHALLENGE_HINT','Résolvez l’opération ci-dessous pour confirmer :');

define('TEXT_SV_SAVE_ERR_DUP_NAME',        'L’enregistrement a échoué, le nom du service est déjà utilisé');
define('TEXT_SV_SAVE_ERR_DUP_MAIL',        'L’enregistrement a échoué, l’adresse mail est déjà utilisé');
define('TEXT_SV_SAVE_ERR_MAIL',        'L’adresse mail n’pas dans un format valide');
define('TEXT_SV_SAVE_ACTION_LOG',        'Enregistrement paramètre des Services');
define('TEXT_SV_SAVE_OK',        'L’enregistrement des Services a été réalisé avec succès');
define('TEXT_SV_SAVE_ERR_WRITE',        'Une erreur est survenue lors de l’écriture des données dans la base.');
define('TEXT_SV_SAVE_ERR_DETAIL',        'Détails de l’erreur : ');
define('TEXT_SV_SAVE_ERR_REQUEST',        'Requête invalide.');

define('TEXT_SV_DEL_OK',        'Le Service "%s" a été supprimé.');
define('TEXT_SV_DEL_ERR_LINKED',        'Le Service "%s" ne peut pas être supprimé car il est lié à au moins une Station');
define('TEXT_SV_DEL_ERR_NOT_FOUND',        'Ce Service n’existe pas et ne peut pas être supprimé.');




// -----------------------------------------------
// gestion_type.php - measurement type config page
define('TEXT_US_TYPE_PAGE_TITLE',       'Configuration des types de mesure (Pluie, Débit, ...)');
define('TEXT_US_TYPE_TAB_LABEL',        'Type de mesure');
define('TEXT_US_TYPE_SAVE',        'Enregistrer');

// -----------------------------------------------
// form_type_1.php - measurement type table
define('TEXT_US_FT_COL_NAME',           'Nom');
define('TEXT_US_FT_COL_MESURE',         'Mesure');
define('TEXT_US_FT_COL_ORDER',          'Ordre');
define('TEXT_US_FT_COL_ACTIVE',         'Actif');
define('TEXT_US_FT_COL_COLOR_BORDER',   'Couleur bordure');
define('TEXT_US_FT_COL_COLOR_BG',       'Couleur fond');
define('TEXT_US_FT_COL_GRAPH',          'Type graphique');
define('TEXT_US_FT_NEW_ENTRY',          'Ajouter une entrée');
define('TEXT_US_FT_OPT_PONCTUEL',       'Ponctuel');
define('TEXT_US_FT_OPT_CUMUL',          'Cumulatif');
define('TEXT_US_FT_OPT_LINES',          'lignes');
define('TEXT_US_FT_OPT_BAR',            'barres');
define('TEXT_US_FT_BTN_DELETE',         'Supprimer');

// -----------------------------------------------
// ctrl_type.php - measurement type save processor
define('TEXT_US_TYPE_MSG_UPDATED',      'La liste des types de mesure a été mise à jour.');
define('TEXT_US_TYPE_MSG_CREATED',      'Le nouveau type de mesure a été enregistré.');

define('TEXT_US_TYPE_DEL_CONFIRM_TITLE','Supprimer un type de mesure');
define('TEXT_US_TYPE_DEL_CONFIRM_MSG','Vous êtes sur le point de supprimer définitivement le type de mesure suivant.<br>Cette action est irréversible.');
define('TEXT_US_TYPE_DEL_CHALLENGE_HINT','Résolvez l’opération ci-dessous pour confirmer :');

// -----------------------------------------------
// suppr_type.php - measurement type deletion
define('TEXT_US_TYPE_DEL_OK',           'Le type de données "%s" a été supprimé.');
define('TEXT_US_TYPE_DEL_ERR_LINKED',   'Le type de données "%s" ne peut pas être supprimé car il est lié à au moins une série de données.');
define('TEXT_US_TYPE_DEL_ERR_NOT_FOUND', 'Ce type de données n’existe pas et ne peut pas être supprimé.');

// =============================================================================
// DIAGRAPHY MODULE (PIEZOMETRIC)
// =============================================================================
// -----------------------------------------------
// data_diag_piezo.php - page title & filter panel
define('TEXT_DG_PAGE_TITLE',        'Diagraphies de conductivité - stations piézométriques');
define('TEXT_DG_BTN_SHOW',          'Afficher Diag.');
define('TEXT_DG_SORT_LABEL',        'TRIER PAR');
define('TEXT_DG_SORT_NAME',         'Nom station');
define('TEXT_DG_SORT_CODE',         'Code station');
define('TEXT_DG_ORDER_ASC',         'Croissant');
define('TEXT_DG_ORDER_DESC',        'Décroissant');
define('TEXT_DG_NB_STATIONS',       'Nombre de stations : ');

// -----------------------------------------------
// data_diag_piezo.php - table column headers & tooltips
define('TEXT_DG_COL_STATUS',        'Statut');
define('TEXT_DG_COL_STATUS_TITLE',  'Active ou Historique (Fermée)');
define('TEXT_DG_COL_SUIVI',         'Suivi');
define('TEXT_DG_COL_SUIVI_TITLE',   'Mesures continues ou ponctuelles');
define('TEXT_DG_COL_CODE',          'Code station');
define('TEXT_DG_COL_NAME',          'Nom station');
define('TEXT_DG_COL_COMMUNE',       'Commune');
define('TEXT_DG_COL_NB_DIAG',       'Nb diag.');
define('TEXT_DG_COL_NB_DIAG_TITLE', 'Nombre de diagraphies');
define('TEXT_DG_COL_LAST_DIAG',     'Dern. diag.');
define('TEXT_DG_COL_LAST_DIAG_TITLE', 'Date de la dernière diagraphie');
define('TEXT_DG_COL_SELECT',        'Select +/-');
define('TEXT_DG_COL_SELECT_TITLE',  'Sélectionner toutes les diagraphies');

// -----------------------------------------------
// data_diag_piezo.php - station status icon tooltips
define('TEXT_DG_STATUS_ACTIVE',     'Active');
define('TEXT_DG_STATUS_CLOSED',     'Historique (Fermée)');
define('TEXT_DG_SUIVI_CONTINU',     'Mesures continues');
define('TEXT_DG_SUIVI_PONCTUEL',    'Mesures ponctuelles');

// -----------------------------------------------
// data_diag_piezo.php - empty result & JS inline messages
define('TEXT_DG_NO_STATION',        'Aucune station trouvée.');
define('TEXT_DG_ERR_NO_DIAG',       'Aucune diagraphie sélectionnée - le graphique ne peut pas être généré.');

// -----------------------------------------------
// block_diag.php - popup panel labels
define('TEXT_DG_POPUP_TITLE',       'Diagraphies comparées');
define('TEXT_DG_POPUP_CLOSE',       'Fermer');
define('TEXT_DG_LIST_TITLE',        'Liste des diagraphies');
define('TEXT_DG_BTN_REFRESH',       'Rafraîchir graphique');
define('TEXT_DG_LOADING',           'Chargement...');

// -----------------------------------------------
// process_diag_graph.php - Plotly chart axis labels & hover template
define('TEXT_DG_AXIS_X',            'Conductivité');
define('TEXT_DG_AXIS_Y',            'Profondeur');
define('TEXT_DG_HOVER_DATE', '<b>Date</b>: ');
define('TEXT_DG_HOVER_COND',        '<b>Conductivité</b> : %{x:.0f}');
define('TEXT_DG_HOVER_PROF',        '<b>Profondeur</b> : %{y:.2f}');
define('TEXT_DG_HOVER_TEMP',        '<b>Température</b> : %{customdata[0]}');
define('TEXT_DG_HOVER_OBS',         '<b>Obs.</b> : %{customdata[1]}');

define('TEXT_DG_UNIT_COND', 'µS/cm');
define('TEXT_DG_UNIT_PROF', 'm');
define('TEXT_DG_UNIT_TEMP', '°C');

// Diag — mode édition
define('TEXT_DG_NEW_RA_LINK', 'Créer un nouveau RA pour cette station');
define('TEXT_DG_BTN_EDIT',           'Edit');
define('TEXT_DG_BTN_CANCEL_EDIT',    'Annuler l’édition');
define('TEXT_DG_EDIT_EDITING',       'Édition');
define('TEXT_DG_EDIT_TARGET',        'Well log éditée');
define('TEXT_DG_EDIT_NO_DATA',       'Aucun well log à éditer (rien de coché).');
define('TEXT_DG_EDIT_CHECK_LOCK',    'Impossible de décocher le well log en cours d’édition. Sauvegarder ou annuler d’abord.');
define('TEXT_DG_EDIT_SWITCH_BLOCKED','Modifications non sauvegardées — sauvegarder ou annuler avant de changer de well log.');
define('TEXT_DG_EDIT_MIN_POINTS',    'Un well log doit conserver au moins 1 point.');
define('TEXT_DG_EDIT_CANCEL_CONFIRM','Abandonner les modifications non sauvegardées ?');
define('TEXT_DG_EDIT_CONFIRM_TITLE', 'Confirmer la modification');
define('TEXT_DG_EDIT_CONFIRM_MSG',   'Vous allez remplacer tous les points de ce well log. Cette action est irréversible.');
define('TEXT_DG_EDIT_SAVE_ERR',      'Erreur de sauvegarde.');

// Aides sous le graph
define('TEXT_DG_EDIT_HINT_DRAG', 'Clic gauche sur un point : glisser pour déplacer');
define('TEXT_DG_EDIT_HINT_RDEL', 'Clic droit sur un point : supprimer');
define('TEXT_DG_EDIT_HINT_RADD', 'Clic droit sur zone vide : ajouter un point');

define('TEXT_DG_DEL_TITLE',  'Supprimer ce well log');
define('TEXT_DG_DEL_CONFIRM_TITLE',  'Confirmer la suppression');
define('TEXT_DG_DEL_CONFIRM_MSG',    'Vous êtes sur le point de supprimer définitivement tous les points de ce well log. Cette action est irréversible.');
define('TEXT_DG_DEL_BTN_CONFIRM',    'Supprimer');
define('TEXT_DG_DEL_ERR',            'Erreur de suppression.');

// =============================================================================
// RATING CURVE MODULE (ETL)
// =============================================================================
// -----------------------------------------------
// modif_etl.php - page-level labels
define('TEXT_ET_TIMELINE_TITLE', 'Frise chronologique des courbes ETL');
define('TEXT_ET_TIMELINE_HINT',  'Cliquer sur une barre pour sélectionner / désélectionner');
define('TEXT_ET_TITLE',                 'Courbe d’étalonnage Q=f(H)');
define('TEXT_ET_TITLE_STATION',                 ' - Station hydrométrique : ');
define('TEXT_ET_ERR_STATION',           'La station n’a pas pu être identifiée.');
define('TEXT_ET_LIST_TITLE',            'Courbes d’étalonnage');
define('TEXT_ET_BTN_REFRESH',           'Rafraîchir graphique');
define('TEXT_ET_LOADING',               'Chargement...');
define('TEXT_ET_BTN_NEW',               'Nouveau');
define('TEXT_ET_BTN_NEW_TITLE',         'Créer une nouvelle ETL');
define('TEXT_ET_BTN_MODIF',             'Modifier');
define('TEXT_ET_BTN_MODIF_TITLE',       'Modifier une des ETL sélectionnées');
define('TEXT_ET_BTN_DUPLIC',            'Dupliquer');
define('TEXT_ET_BTN_DUPLIC_TITLE',      'Dupliquer une des ETL sélectionnées');
define('TEXT_ET_BTN_DEL',               'Supprimer');
define('TEXT_ET_BTN_DEL_TITLE',         'Supprimer une des ETL sélectionnées');
define('TEXT_ET_BTN_DECIMAL_PLUS',      'Ajouter une décimale');
define('TEXT_ET_BTN_DECIMAL_MINUS',     'Retirer une décimale');
define('TEXT_ET_BTN_ADJUST',            'Ajuster échelle');
define('TEXT_ET_OPT_SWAP',            'Inverser les axes');
define('TEXT_ET_COORD',               'Coordonnées');
define('TEXT_ET_COORD_HMIN',   'Hauteur min');
define('TEXT_ET_COORD_HMAX',   'Hauteur max');
define('TEXT_ET_COORD_QMIN',   'Débit min');
define('TEXT_ET_COORD_QMAX',   'Débit max');
define('TEXT_ET_COORD_UNIT_H', 'cm');
define('TEXT_ET_COORD_UNIT_Q', 'm³/s');
define('TEXT_ET_TOOLTIP_DATE', 'Date :');
define('TEXT_ET_TOOLTIP_H',    'Hauteur :');
define('TEXT_ET_TOOLTIP_Q',    'Débit :');

// ===== ETL — Chart series labels =====
define('TEXT_ET_LABEL_ETL_REF', 'ETL - ref:');
define('TEXT_ET_LABEL_JGE_REF', 'JGE - ref:');
define('TEXT_ET_LABEL_JGE',     'JGE');


// -----------------------------------------------
// process_etl_tab.php - ETL list table
define('TEXT_ET_TAB_COL_REF',           'Réf.');
define('TEXT_ET_TAB_COL_DATE_START',    'Date début');
define('TEXT_ET_TAB_COL_DATE_END',      'Date fin');
define('TEXT_ET_TAB_COL_SELECT',        'Sélection');
define('TEXT_ET_TAB_NO_DATA',           'Aucune donnée trouvée.');

// -----------------------------------------------
// Shared popup labels (block_etl_*.php)
define('TEXT_ET_POPUP_ETL_CURVE',       'Courbe ETL');
define('TEXT_ET_POPUP_DATE_FMT',        'Date (jj-mm-aaaa)');
define('TEXT_ET_POPUP_TIME_FMT',        'Heure (hh:mm:ss)');
define('TEXT_ET_POPUP_PERIOD_START',    'Période début');
define('TEXT_ET_POPUP_PERIOD_END',      'Période fin');


define('TEXT_ET_SG_OPEN_HINT', 'Shift+Click pour ouvrir la JGE dans un nouvel onglet');

// -----------------------------------------------
// block_etl_delete.php
define('TEXT_ET_DEL_TITLE',             'Supprimer une ETL');


// -----------------------------------------------
// block_etl_modif.php
define('TEXT_ET_MODIF_TITLE',           'Modifier la période de validité d’une ETL');

// -----------------------------------------------
// block_etl_new.php
define('TEXT_ET_NEW_TITLE',             'Créer une nouvelle ETL');
define('TEXT_ET_NEW_CURVE_TYPE',        'Type de courbe');
define('TEXT_ET_NEW_H0_LABEL',          'Paramètre de hauteur (débit nul)');
define('TEXT_ET_NEW_H0_AUTO',        "Optimiser H₀ automatiquement");
define('TEXT_ET_NEW_H0_AUTO_RESULT', "optimisé à");
define('TEXT_ET_NEW_LOGLOG_AXES',    "Axes log-log");
define('TEXT_ET_NEW_EQ_1',              'Q = 10^b * H^a');
define('TEXT_ET_NEW_EQ_2',              'Q = a * H + b');
define('TEXT_ET_NEW_EQ_3',              'Q = log(H)');
define('TEXT_ET_NEW_DENSITY',           'Densité des points');
define('TEXT_ET_NEW_STEP1',          '1. Période d’analyse');
define('TEXT_ET_NEW_STEP2',          '2. Modèle de régression');
define('TEXT_ET_NEW_STEP3',          '3. Résultat de la régression');
define('TEXT_ET_NEW_STEP4',          '4. Plage de validité (H)');
define('TEXT_ET_NEW_STEP5',          '5. Conflits de période');
define('TEXT_ET_NEW_PREVIEW_TITLE',  'Prévisualisation');
define('TEXT_ET_NEW_DISABLED_HINT',  'Disponible après l’étape B');
define('TEXT_BTN_CANCEL',            'Annuler');
define('TEXT_ET_NEW_MODEL_POWER',      'Loi de puissance');
define('TEXT_ET_NEW_MODEL_POLY',       'Polynomiale');
define('TEXT_ET_NEW_MODEL_LINEAR', 'Linéaire');
define('TEXT_ET_NEW_BORNE_INF',        'Min');
define('TEXT_ET_NEW_BORNE_SUP',        'Max');
define('TEXT_ET_NEW_INTERVAL',         'Pas');
define('TEXT_ET_NEW_REGRESSION_HINT',  'Ajustez la période et le modèle pour voir le résultat.');

define('TEXT_ET_NEW_ADD_REGRESSION',      'Ajouter une régression');
define('TEXT_ET_NEW_ADD_REGRESSION_HINT', 'Examinez d’abord les jaugeages, puis ajoutez une régression');

define('TEXT_ET_NEW_SHOW_PI', 'Afficher l’intervalle de prédiction 95%');
define('TEXT_ET_NEW_PI_BAND', 'IP 95%');

define('TEXT_ET_NEW_JGE_EXCLUDED',          'exclu(s)');
define('TEXT_ET_NEW_JGE_EXCLUDED_LABEL',    'Jaugeage exclu');
define('TEXT_ET_NEW_JGE_CLICK_HINT',        'Cliquer pour exclure de la régression');
define('TEXT_ET_NEW_JGE_REINCLUDE_HINT',    'Cliquer pour réintégrer dans la régression');

define('TEXT_ET_NEW_JGE_FOUND',         'points de jaugeage trouvés sur la période.');
define('TEXT_ET_NEW_JGE_FEW',           'points de jaugeage — au moins 2 sont nécessaires pour calibrer une courbe.');
define('TEXT_ET_NEW_JGE_NONE',          'point de jaugeage — au moins 2 sont nécessaires pour calibrer une courbe.');
define('TEXT_ET_NEW_JGE_NONE_PERIOD',   'Aucun point de jaugeage sur cette période.');
define('TEXT_ET_NEW_DATE_HINT',         'Renseignez deux dates au format dd-mm-yyyy.');
define('TEXT_ET_NEW_EQ_LABEL',          'Équation :');
define('TEXT_ET_NEW_R2_LABEL',          'R² :');
define('TEXT_ET_NEW_MANUAL_EDIT',       'Courbe ajustée manuellement — la régression sert de guide.');
define('TEXT_ET_NEW_REG_FAILED',        'Régression impossible');
define('TEXT_ET_NEW_REG_NEED_PTS',      'Au moins 2 points sont nécessaires pour calibrer une courbe.');
define('TEXT_ET_NEW_PLOTLY_MISSING',    'Plotly indisponible — le graphe ne peut pas s’afficher.');
define('TEXT_ET_NEW_PT_TITLE',          'Édition d’un point');
define('TEXT_ET_NEW_CURVE_LABEL',       'Nouvelle courbe');
define('TEXT_ET_NEW_CURVE_HINT',        'Cliquez pour éditer · glissez pour déplacer');
define('TEXT_BTN_SAVE',                 'Enregistrer');


define('TEXT_ET_NEW_CONFLICTS_HINT',       'Les conflits seront détectés à l’enregistrement.');
define('TEXT_ET_NEW_CONFLICTS_NONE',       'Aucun chevauchement détecté sur cette période.');
define('TEXT_ET_NEW_CONFLICT_ACTION',      'Action');
define('TEXT_ET_NEW_CONFLICT_DELETE',     'Suppression (entièrement incluse dans la nouvelle)');
define('TEXT_ET_NEW_CONFLICT_TRUNC_R',    'Troncature de fin (s’arrêtera au début de la nouvelle)');
define('TEXT_ET_NEW_CONFLICT_TRUNC_L',    'Troncature de début (reprendra après la nouvelle)');
define('TEXT_ET_NEW_CONFLICT_BLOCKING',   'Conflit bloquant : nouvelle entièrement à l’intérieur');
define('TEXT_ET_NEW_BLOCKING_TITLE',       'Période impossible');
define('TEXT_ET_NEW_BLOCKING_MSG',         'La période choisie tombe entièrement à l’intérieur d’une ETL existante. Ajustez la période avant d’enregistrer.');
define('TEXT_ET_NEW_SAVE_CONFIRM_TITLE',   'Confirmer la création');
define('TEXT_ET_NEW_SAVE_CONFIRM_MSG',     'Vous allez créer une nouvelle ETL. Cette action est irréversible.');
define('TEXT_ET_NEW_CHALLENGE_CONFLICTS',  'ETL existantes qui seront modifiées :');
define('TEXT_ET_NEW_CHALLENGE_HINT',       'Pour confirmer, résolvez cette opération :');
define('TEXT_ET_NEW_CHALLENGE_NEW_PERIOD', 'Nouvelle période de la courbe :');
define('TEXT_ET_NEW_CHALLENGE_BEFORE',     'Avant');
define('TEXT_ET_NEW_CHALLENGE_AFTER',      'Après');
define('TEXT_ET_NEW_CHALLENGE_DELETED',    'Supprimée');
define('TEXT_ET_NEW_SAVE_ERR',             'Erreur à l’enregistrement.');
define('TEXT_ET_NEW_CONCURRENT_TITLE',     'Conflit non résolu');
define('TEXT_ET_NEW_CONCURRENT_MSG',       'Une autre ETL a été créée ou modifiée pendant votre saisie. Veuillez vérifier la liste des ETL et recommencer.');

define('TEXT_ET_NEW_PERIOD_CHANGED_TITLE',
    'Période modifiée');
define('TEXT_ET_NEW_PERIOD_CHANGED_MSG',
    'La période d’analyse a changé. Voulez-vous recalculer la régression sur le nouveau jeu de jaugeages ? <br><br>'
    . '<b>Oui</b> : recalcule la courbe (les ajustements manuels seront perdus).<br>'
    . '<b>Annuler</b> : conserve la courbe actuelle.');

define('TEXT_ET_NEW_CONFIRM_TITLE',   'Confirmation requise');
define('TEXT_ET_NEW_CONFIRM_OK',      'Continuer quand même');
define('TEXT_ET_NEW_CONFIRM_DISCARD',
    'Vous avez ajusté manuellement la courbe (points ou constante). '
  . 'Changer ce paramètre va recalculer la régression et effacer vos modifications.');



// -----------------------------------------------
// process_etl_graph.php - Plotly axis labels & hover templates
define('TEXT_ET_AXIS_H',                'Hauteur (cm)');
define('TEXT_ET_AXIS_Q',                'Débit (m<sup>3</sup>/s)');
define('TEXT_ET_HOVER_DATE',            '<b>Date</b> : %{customdata}<br>');
define('TEXT_ET_HOVER_H',               '<b>Hauteur</b> : %{x:.1f} cm<br>');
define('TEXT_ET_HOVER_Q',               '<b>Débit</b> : %{y:.3f} m³/s');
define('TEXT_ET_HOVER_H_ONLY',          '<b>Hauteur</b> : %{x:.1f} cm<br><b>Débit</b> : %{y:.3f} m³/s');

// -----------------------------------------------
// process_etl_new.php - new ETL creation messages
define('TEXT_ET_NEW_OK',                "La nouvelle courbe d’étalonnage 'ETL : %s %s → %s %s' a été créée.");
define('TEXT_ET_NEW_EQ_PREFIX',         'Équation : ');
define('TEXT_ET_NEW_R2_PREFIX',         'Qualité d’ajustement : R<sup>2</sup> = ');
define('TEXT_ET_NEW_ERR_FEW_PTS',       'Au moins deux points de jaugeage sont nécessaires pour ajuster une courbe d’étalonnage.');
define('TEXT_ET_NEW_ERR_OVERLAP',       "La période choisie est déjà couverte par une autre courbe d’étalonnage : %s %s → %s %s");
define('TEXT_ET_NEW_ERR_TRANSACTION',   'Erreur de transaction : ');


// -----------------------------------------------
define('TEXT_ET_BTN_EDIT',          'Editer');
define('TEXT_ET_BTN_EDIT_TITLE',    'Editer les points de cette courbe de tarage');
define('TEXT_ET_EDIT_TITLE',        'Edition des points de la RC');
define('TEXT_ET_EDIT_LOADING',      'Chargement…');
define('TEXT_ET_EDIT_LOAD_ERR',     'Impossible de charger cette RC.');
define('TEXT_ET_EDIT_HINT',         'Cliquez sur un point pour éditer ses valeurs, ou glissez-le pour le déplacer.');
define('TEXT_ET_EDIT_TOO_FEW_PTS',  'Cette courbe a moins de 2 points — rien à éditer.');
define('TEXT_ET_EDIT_CONFIRM_TITLE','Confirmer l’édition');
define('TEXT_ET_EDIT_CONFIRM_MSG',  'Vous allez remplacer tous les points de cette courbe. La période n’est pas modifiée.');
define('TEXT_ET_EDIT_SAVE_ERR',     'Erreur d’enregistrement.');

define('TEXT_ET_EDIT_CURVE_HINT',      'Glisser pour déplacer');
define('TEXT_ET_NEW_CURVE_DRAG_HINT',  'Glisser pour déplacer');
define('TEXT_ET_EDIT_HINT_DRAG',       'Glisser-déposer : déplacer un point');
define('TEXT_ET_EDIT_HINT_RCLICK',     'Clic-droit : ajouter (vide) ou supprimer (sur un point)');
define('TEXT_ET_EDIT_MIN_PTS',         'Une courbe doit conserver au moins 2 points.');


// -----------------------------------------------
// process_etl_duplic.php - duplication messages
define('TEXT_ET_DUPLIC_OK',             "La nouvelle courbe d’étalonnage 'ETL-%s : %s %s → %s %s' a été créée.");
define('TEXT_ET_DUPLIC_ERR_DATA',       'Une erreur est survenue lors de la duplication des données.');
define('TEXT_ET_DUPLIC_ERR_OVERLAP',    "La période choisie est déjà couverte par une autre courbe d’étalonnage : %s %s → %s %s");

// -----------------------------------------------
// process_etl_delete.php - deletion messages
define('TEXT_ET_DEL_OK',                "La courbe d’étalonnage ETL-%s : %s %s → %s %s a été supprimée.");
define('TEXT_ET_DEL_ERR_TRANSACTION',   'Erreur de transaction : ');

define('TEXT_ET_DEL_NO_SELECTION',    'Cochez au moins une RC à supprimer.');
define('TEXT_ET_DEL_CONFIRM_TITLE',   'Confirmer la suppression');
define('TEXT_ET_DEL_CONFIRM_MSG',     'Vous allez supprimer les courbes de tarage suivantes. Cette action est irréversible.');
define('TEXT_ET_DEL_RC_TO_DELETE',    'RC seront supprimées');
define('TEXT_ET_DEL_POINTS',          'points');
define('TEXT_ET_DEL_BTN_CONFIRM',     'Supprimer');
define('TEXT_ET_DEL_ERR',             'Erreur de suppression.');

// -----------------------------------------------
// data_etl.php - station list page
define('TEXT_ET_LIST_PAGE_TITLE',       'Courbes d’étalonnage - stations hydrométriques');
define('TEXT_ET_FILTER_CURVES',         'Courbes ETL');
define('TEXT_ET_FILTER_ALL_ST',         'Toutes les stations');
define('TEXT_ET_FILTER_ETL_ST',         'Stations avec ETL');
define('TEXT_ET_SORT_LABEL',            'TRIER PAR');
define('TEXT_ET_SORT_NAME',             'Nom station');
define('TEXT_ET_SORT_CODE',             'Code station');
define('TEXT_ET_ORDER_ASC',             'Croissant');
define('TEXT_ET_ORDER_DESC',            'Décroissant');
define('TEXT_ET_NB_STATIONS',           'Nombre de stations : ');
define('TEXT_ET_COL_STATUS',            'Statut');
define('TEXT_ET_COL_STATUS_TITLE',      'Active ou Historique (Fermée)');
define('TEXT_ET_COL_SUIVI',             'Suivi');
define('TEXT_ET_COL_SUIVI_TITLE',       'Mesures continues ou ponctuelles');
define('TEXT_ET_COL_STATION',           'Station (Code - Nom)');
define('TEXT_ET_COL_NB_JGE',            'Nb JGE');
define('TEXT_ET_COL_NB_JGE_TITLE',      'Nombre de jaugeages valides');
define('TEXT_ET_COL_NB_ETL',            'Nb ETL');
define('TEXT_ET_COL_NB_ETL_TITLE',      'Nombre de courbes d’étalonnage');
define('TEXT_ET_COL_CURVE_TITLE',       'Modifier courbe d’étalonnage (ETL)');
define('TEXT_ET_COL_HQ',                'H → Q');
define('TEXT_ET_COL_HQ_TITLE',          'Convertir hauteurs en débits');
define('TEXT_ET_STATUS_ACTIVE',         'Active');
define('TEXT_ET_STATUS_CLOSED',         'Historique (Fermée)');
define('TEXT_ET_SUIVI_CONTINU',         'Mesures continues');
define('TEXT_ET_SUIVI_PONCTUEL',        'Mesures ponctuelles');
define('TEXT_ET_LINK_ETL_TITLE',        'Modifier courbe d’étalonnage (ETL)');
define('TEXT_ET_LINK_HQ_TITLE',         'Convertir hauteurs en débits');
define('TEXT_ET_NO_STATION',            'Aucune station trouvée.');

// Timeline tooltip + duration units
define('TEXT_ET_TIMELINE_TT_RC',       'RC');
define('TEXT_ET_TIMELINE_TT_START',    'Début');
define('TEXT_ET_TIMELINE_TT_END',      'Fin');
define('TEXT_ET_TIMELINE_TT_DUR',      'Durée');
define('TEXT_ET_TIMELINE_UNIT_DAYS',   'j');
define('TEXT_ET_TIMELINE_UNIT_MONTHS', 'mois');
define('TEXT_ET_TIMELINE_UNIT_YEAR',   'an');
define('TEXT_ET_TIMELINE_UNIT_YEARS',  'ans');

 
// =============================================================================
// STAGE–DISCHARGE CONVERSION MODULE (H→Q)
// =============================================================================

// -----------------------------------------------
// convert_hq.php - page-level errors
define('TEXT_HQ_ERR_STATION',           'La station n’a pas pu être identifiée.');
define('TEXT_HQ_ERR_NO_ID',             'Aucun identifiant de station n’a été fourni. L’URL de la page n’est pas reconnue.');

define('TEXT_HQ_TOO_MANY_ROWS', 'Trop de données pour afficher le graphe sur cette période.');
define('TEXT_HQ_RECORDS',        'enregistrements');
define('TEXT_HQ_SHORTER_PERIOD', 'Veuillez sélectionner une période plus courte.');
define('TEXT_HQ_OR_LOAD_PACKET', 'ou chargez un bloc ci-dessous');
define('TEXT_HQ_LOAD_PACKET',    'Charger le bloc');

// -----------------------------------------------
// convert_hq.php - page title
define('TEXT_HQ_PAGE_TITLE_PREFIX',     'Conversion Hauteur -> Débit : ');
define('TEXT_HQ_PAGE_TITLE_STATION',    'Station hydrométrique : ');

// -----------------------------------------------
// convert_hq.php - left panel labels
define('TEXT_HQ_CHRON_H_LABEL',         'Série de hauteur à convertir');
define('TEXT_HQ_CHRON_Q_LABEL',         'Série de débit cible');
define('TEXT_HQ_SHOW_GAPS',             'Afficher les lacunes');
define('TEXT_HQ_BTN_CONVERT',           'Convert : H -> Q');
define('TEXT_HQ_BTN_CONVERT_TITLE',     'Lancer la conversion');
define('TEXT_HQ_BTN_VALIDATE',          'Enregistrer');
define('TEXT_HQ_BTN_SAVE_TITLE',        'Enregistrer des données');
define('TEXT_HQ_BTN_WAIT_LABEL',        'Conversion en cours');
define('TEXT_HQ_BTN_SAVE_WAIT_LABEL',   'Save data');
define('TEXT_HQ_ZOOM_LABEL',            'Contrôle du zoom');
define('TEXT_HQ_DATE_MIN_LABEL',        'Date début');
define('TEXT_HQ_DATE_MAX_LABEL',        'Date fin');
define('TEXT_HQ_Y_MIN_H',               'Hauteur min');
define('TEXT_HQ_Y_MAX_H',               'Hauteur max');
define('TEXT_HQ_Y_MIN_Q',               'Débit min');
define('TEXT_HQ_Y_MAX_Q',               'Débit max');
define('TEXT_HQ_BTN_ADJUST',            'Ajuster l’échelle');
define('TEXT_HQ_LOADING',               'Chargement...');
define('TEXT_HQ_NO_DATA',               'Aucune donnée trouvée.');
define('TEXT_HQ_PERIOD_SELECT_LABEL', 'Period Select'); 
define('TEXT_HQ_BTN_APPLY_PERIOD',    'Apply period'); 


define('TEXT_HQ_ETL_TOOLTIP_CURVE',  'Courbe d’étalonnage');
define('TEXT_HQ_ETL_TOOLTIP_PERIOD', 'Période');
define('TEXT_HQ_ETL_RANGE_PREFIX',   'Plage de hauteur');
define('TEXT_HQ_ETL_TOOLTIP_HINT',   'cliquer pour ouvrir dans le module ETL');
define('TEXT_HQ_ETL_NO_COVERAGE',    'pas de courbe');
define('TEXT_HQ_ETL_GAP_HINT',       'cliquer pour gérer les courbes d’étalonnage de cette station');
define('TEXT_HQ_ETL_NO_RC', "Aucune courbe d'étalonnage définie");


// Étapes du processus
define('TEXT_HQ_STEP_1_TITLE', 'Débit proposé');
define('TEXT_HQ_STEP_1_HINT',  'Sélection des séries et lancement de la conversion');
define('TEXT_HQ_STEP_2_TITLE', 'Vérification & validation');
define('TEXT_HQ_STEP_2_HINT',  'Comparer la proposition (verte) puis valider');
define('TEXT_HQ_STEP_3_TITLE', 'Débit calculé');
define('TEXT_HQ_STEP_3_HINT',  'Terminé — la série de débit est enregistrée');
 
// Panneau étape 1
define('TEXT_HQ_PERIOD_LABEL', 'Période');
define('TEXT_HQ_STEP_1_FOOT',  'Aucune donnée ne sera modifiée tant que vous n’aurez pas validé à l’étape 2.');
 
// Panneau étape 2
define('TEXT_HQ_REVIEW_READY',    '✓ Conversion prête');
define('TEXT_HQ_REVIEW_CONVERTED','points convertis');
define('TEXT_HQ_REVIEW_LOST_ABOVE','perdus (hauteur > plage de la courbe)');
define('TEXT_HQ_REVIEW_LOST_BELOW','perdus (hauteur < plage de la courbe)');
define('TEXT_HQ_REVIEW_LOST_NOCOV','perdus (pas de courbe d’étalonnage)');
define('TEXT_HQ_STEP_2_FOOT',   'La courbe verte sur le graphe est la proposition de conversion. Comparez-la avant de valider.');
define('TEXT_HQ_BTN_DISCARD',   'Annuler la proposition');
 
// Panneau étape 3
define('TEXT_HQ_SAVE_WARNING_TITLE', 'Attention — action irréversible');
define('TEXT_HQ_SAVED_OK',      '✓ Conversion enregistrée');
define('TEXT_HQ_SAVED_WRITTEN', 'points écrits en production');
define('TEXT_HQ_SAVED_REMOVED', 'anciens points supprimés');
define('TEXT_HQ_SAVED_AT',      'Enregistré le');
define('TEXT_HQ_BTN_AGAIN',     '↻ Lancer une autre conversion');


// Console flottante — en-tête & boutons
define('TEXT_HQ_CONSOLE_TITLE',       'Journal de conversion');
define('TEXT_HQ_CONSOLE_COPY',        'Copier le journal dans le presse-papiers');
define('TEXT_HQ_CONSOLE_COPY_LABEL',  'Copier');
define('TEXT_HQ_CONSOLE_CLEAR',       'Effacer la console');
define('TEXT_HQ_CONSOLE_CLEAR_LABEL', 'Effacer');
define('TEXT_HQ_CONSOLE_MIN',         'Réduire');
define('TEXT_HQ_CONSOLE_CLOSE',       'Fermer');
 
// Console flottante — messages
define('TEXT_HQ_LOG_START',           'Conversion démarrée pour la période');
define('TEXT_HQ_LOG_BAD_RESPONSE',    'Réponse serveur inattendue — veuillez réessayer.');
define('TEXT_HQ_LOG_ETL_FOUND',       'courbe(s) d’étalonnage trouvée(s)');
define('TEXT_HQ_LOG_SEGMENTS_READY',  'segment(s) d’interpolation préparé(s)');
define('TEXT_HQ_LOG_CONVERT_START',   'Conversion des hauteurs en débits en cours...');
define('TEXT_HQ_LOG_CONVERT_DONE',    'Conversion terminée.');
define('TEXT_HQ_LOG_SUMMARY',         'Récapitulatif :');
define('TEXT_HQ_LOG_CONVERTED',       'points convertis :');
define('TEXT_HQ_LOG_NO_COVERAGE',     'points perdus — aucune courbe ne couvre cette date :');
define('TEXT_HQ_LOG_STAGE_ABOVE',     'points perdus — hauteur au-dessus de la plage de la courbe :');
define('TEXT_HQ_LOG_STAGE_BELOW',     'points perdus — hauteur en-dessous de la plage de la courbe :');
define('TEXT_HQ_LOG_SOURCE_GAPS',     'lacunes source préservées :');
define('TEXT_HQ_LOG_READY_VALID',     'Prêt pour validation. Vérifiez la courbe verte, puis cliquez sur Valider pour enregistrer.');

// Journal de validation (Lot 5) — ajouté à la suite du journal de conversion
// quand l'utilisateur clique sur "Validate convert".
define('TEXT_HQ_LOG_SAVE_START',         'Enregistrement de la conversion en production...');
define('TEXT_HQ_LOG_SAVE_BAD_RESPONSE',  'Réponse serveur inattendue — veuillez réessayer.');
define('TEXT_HQ_LOG_SAVE_META_CREATED',  'Nouvel enregistrement de la série de débit créé');
define('TEXT_HQ_LOG_SAVE_REMOVED',       'anciens points de débit supprimés sur cette période :');
define('TEXT_HQ_LOG_SAVE_NO_REMOVE',     'aucune donnée de débit existante sur cette période');
define('TEXT_HQ_LOG_SAVE_COPIED',        'points de débit écrits en production :');
define('TEXT_HQ_LOG_SAVE_CLEANED',       'enregistrements temporaires supprimés :');
define('TEXT_HQ_LOG_SAVE_AT',            'Action enregistrée à');
define('TEXT_HQ_LOG_SAVE_DONE',          'Enregistrement terminé.');
define('TEXT_HQ_LOG_SAVE_SUCCESS',       'Conversion enregistrée avec succès.');

// -----------------------------------------------
// convert_hq.php - JS inline messages
define('TEXT_HQ_JS_SAVED',              'Les nouvelles données de débit ont été enregistrées.');
define('TEXT_HQ_JS_ERR_DATE_ORDER',     'La date de début doit être antérieure à la date de fin.');
define('TEXT_HQ_JS_ERR_DATE_FORMAT',    'Au moins une des dates saisies est invalide ou au mauvais format (jj-mm-aaaa requis).');

// -----------------------------------------------
// process_convert_graph_etl.php - Plotly chart title
define('TEXT_HQ_ETL_COVERAGE_TITLE',    'Couverture des courbes d’étalonnage');

// -----------------------------------------------
// process_convert_valid.php - server-side result messages
define('TEXT_HQ_VALID_OK',              'La nouvelle série de données a été enregistrée.');
define('TEXT_HQ_VALID_ERR',             'Une erreur est survenue lors de l’enregistrement des données.');
define('TEXT_HQ_LOG_INFO_PREFIX',       "Conversion de la série 'hauteur' en débit\n");
define('TEXT_HQ_LOG_STATION_PREFIX',    'Station : ');
define('TEXT_HQ_LOG_CHRON_PREFIX',      'Série : ');

// -----------------------------------------------
// Trace name suffix for the pending conversion series (process_convert_graph.php)
define('TEXT_HQ_TRACE_PENDING',         ' - en attente de validation');

define('TEXT_HQ_STEP_1_LABEL', '1. Affichage des données');
define('TEXT_HQ_STEP_2_LABEL', '2. Conversion');
define('TEXT_HQ_STEP_3_LABEL', '3. Enregistrement');

// =============================================================================
// PDF - STATION SHEET
// =============================================================================

 
define('TEXT_PDF_TITLE',          'Fiche station');
define('TEXT_PDF_EDITED_ON',      'Edité le');
define('TEXT_PDF_EDITED_BY',      'par');
 
define('TEXT_PDF_STATION_NAME',   'Nom station');
define('TEXT_PDF_STATION_CODE',   'Code station');
define('TEXT_PDF_SHORT_NAME',     'Nom abrégé');
define('TEXT_PDF_NUM_IRH',        'Num IRH');
 
define('TEXT_PDF_STATUS',         'Statut');
define('TEXT_PDF_STATUS_ACTIVE',  'Active');
define('TEXT_PDF_STATUS_CLOSED',  'Historique (fermée)');
 
define('TEXT_PDF_MONITORING',            'Suivi');
define('TEXT_PDF_MONITORING_CONTINUOUS', 'Mesures continues');
define('TEXT_PDF_MONITORING_SPOT',       'Mesures ponctuelles');
 
define('TEXT_PDF_EQUIPMENT',        'Equipement');
define('TEXT_PDF_EQUIPMENT_OK',     'En fonctionnement');
define('TEXT_PDF_EQUIPMENT_FAULTY', 'En panne');
 
define('TEXT_PDF_GEO_LOCATION', 'Situation Géographique');
define('TEXT_PDF_TERRITORY',    'Territoire');
define('TEXT_PDF_COMMUNE',      'Commune');
define('TEXT_PDF_SITE',         'Site');
define('TEXT_PDF_HYDRO_REGION', 'Région hydrologique / BV');
define('TEXT_PDF_RIVER',        'Rivière');
define('TEXT_PDF_ALTITUDE',     'Altitude (en m)');
 
define('TEXT_PDF_GEO_COORDS',  'Coordonnées géographiques');
define('TEXT_PDF_LONGITUDE',   'Longitude');
define('TEXT_PDF_LATITUDE',    'Latitude');
define('TEXT_PDF_UTM_X',       'UTM - X (WGS 84)');
define('TEXT_PDF_UTM_Y',       'UTM - Y (WGS 84)');
define('TEXT_PDF_LAMBERT_X',   'Lambert - X (RGNC 91)');
define('TEXT_PDF_LAMBERT_Y',   'Lambert - Y (RGNC 91)');
 
define('TEXT_PDF_INFORMATION',   'Informations');
define('TEXT_PDF_INSTALL_DATE',  'Date d’installation');
define('TEXT_PDF_CLOSE_DATE',    'Date de démontage');
define('TEXT_PDF_DESCRIPTION',   'Description');
 
define('TEXT_PDF_PHOTOS_TITLE', 'Photos de la station');
define('TEXT_PDF_PHOTO_DATE',   'Date : ');
define('TEXT_PDF_PHOTO_DESC',   'Description : ');
 
define('TEXT_PDF_RA_TITLE',  'Derniers passages (Rapports d’Activité - Fiches terrain)');
define('TEXT_PDF_RA_DATE',   'Date');
define('TEXT_PDF_RA_OBS',    'Observation');
define('TEXT_PDF_RA_TODO',   'A faire');
define('TEXT_PDF_RA_AGENTS', 'Agents présents');
 
define('TEXT_PDF_DATA_AVAILABLE', 'Données disponibles sur la station');
 
define('TEXT_PDF_FOOTER_PAGE',      'Page');
define('TEXT_PDF_FOOTER_OF',        'sur');
define('TEXT_PDF_FOOTER_GENERATED', 'Document généré le');


// process_station_access_pdf.php
define('TEXT_PDF_ACCESS_TITLE',      'Fiche acces station');
define('TEXT_PDF_ACCESS_CONTACT',    'Contact');
define('TEXT_PDF_ACCESS_OWNER',      'Proprietaire');
define('TEXT_PDF_ACCESS_NAME',       'Nom du contact');
define('TEXT_PDF_ACCESS_PHONE',      'Telephone');
define('TEXT_PDF_ACCESS_EMAIL',      'Email');
define('TEXT_PDF_ACCESS_ADDRESS',    'Adresse');
define('TEXT_PDF_ACCESS_PO_BOX',     'Boite postale');
define('TEXT_PDF_ACCESS_POSTCODE',   'Code postal');
define('TEXT_PDF_ACCESS_COMMUNE',    'Commune');
define('TEXT_PDF_ACCESS_DETAILS',    'Details d acces');
define('TEXT_PDF_ACCESS_PEDESTRIAN', 'Acces pietonnier');
define('TEXT_PDF_ACCESS_TIME',       'Temps d acces');
define('TEXT_PDF_ACCESS_INFO',       'Informations d acces');
define('TEXT_PDF_ACCESS_DIFFICULTY', 'Difficultes');
define('TEXT_PDF_ACCESS_REMARKS',    'Remarques');
define('TEXT_PDF_ACCESS_MAP',        'Plan d acces');
define('TEXT_PDF_ACCESS_YES',        'Oui');
define('TEXT_PDF_ACCESS_NO',         'Non');
define('TEXT_PDF_ACCESS_ERROR',      'Erreur lors de la creation du PDF.');
define('TEXT_ACCESS_PDF_JS_ERR_GENERATE', 'Erreur lors de la generation du PDF.');
define('TEXT_ACCESS_PDF_JS_ERR_SERVER',   'Erreur serveur, veuillez reessayer.');


// ============================================================
// MISE EN PAGE  (sync.php)
// ============================================================
 
define('TEXT_SYNC_PAGE_TITLE',          'Synchronisation des données : Nomad <-> Serveur');
define('TEXT_SYNC_BTN_TO_NOMAD',        'Charger les données depuis le serveur <<');
define('TEXT_SYNC_BTN_TO_SERVER',       'Décharger les données vers le serveur >>');
define('TEXT_SYNC_LAST_LOAD',           'Dernier chargement des données : ');
define('TEXT_SYNC_NB_AGENTS',           'Nb Agents : ');
define('TEXT_SYNC_NB_RA',               "Nb Rapports d'Activité : ");
define('TEXT_SYNC_NB_JGE',              'Nb Jaugeages : ');
define('TEXT_SYNC_PROCESS_RUNNING',     'Processus en cours');
define('TEXT_SYNC_BTN_STOP',            'Arrêter');
 
// ============================================================
// MESSAGES CÔTÉ CLIENT  (sync.php — JavaScript)
// ============================================================
 
define('TEXT_SYNC_JS_NO_CONNECTION',    "Erreur : aucune connexion n'a été détectée, la mise à jour n'est pas possible.");
define('TEXT_SYNC_JS_PROCESS_STOPPED',  'Le processus est interrompu');
define('TEXT_SYNC_JS_CONNECTION_OK',    'Connexion détectée : lancement du processus');
define('TEXT_SYNC_JS_LOADING_FROM',     'Chargement des données depuis le serveur ...');
define('TEXT_SYNC_JS_PUSHING_TO',       'Déchargement des données vers le serveur ...');
define('TEXT_SYNC_JS_CONNECT_FAILED',   'Le processus est interrompu : connexion impossible.');
define('TEXT_SYNC_JS_PLEASE_WAIT',      'Veuillez patienter, le chargement des données peut prendre quelques minutes ...');
define('TEXT_SYNC_JS_STOP_REQUESTED',   "Demande d'arrêt envoyée ... le processus va s'interrompre à la prochaine étape.");
 
// ============================================================
// TEST DE CONNEXION  (process_connect.php)
// ============================================================
 
define('TEXT_SYNC_CONN_NOMAD_OK',       'La connexion Nomad est établie');
define('TEXT_SYNC_CONN_NOMAD_FAIL',     'Impossible de se connecter à la base de données Nomad hors-ligne');
define('TEXT_SYNC_CONN_SERVER_OK',      'La connexion avec la plateforme est établie');
define('TEXT_SYNC_CONN_SERVER_FAIL',    'Impossible de se connecter à la base de données distante (serveur injoignable — vérifiez votre connexion Internet / proxy)');
 
// ============================================================
// MESSAGES COMMUNS DE TRAITEMENT  (process_tonomad.php & process_toserveur.php)
// ============================================================
 
define('TEXT_SYNC_DB_CONNECT_FAIL',     'Impossible de se connecter aux bases de données.');
define('TEXT_SYNC_DB_CONNECT_RETRY',    'Vérifiez votre connexion Internet puis réessayez.');
define('TEXT_SYNC_SUCCESS',             'La synchronisation a été effectuée avec succès.');
define('TEXT_SYNC_PROCESSING_TIME',     'Temps de traitement : ');
define('TEXT_SYNC_SECONDS_SHORT',       'sec.');
define('TEXT_SYNC_TECH_DETAIL',         'Détail technique (à transmettre au support si besoin) :');
 
// ---- Sens descendant : SERVEUR -> NOMAD  (process_tonomad.php) ----
 
define('TEXT_SYNC_DL_STOPPED',          'Chargement interrompu à votre demande.');
define('TEXT_SYNC_DL_STOPPED_ROLLBACK', 'Les données locales ont été remises dans leur état initial : tout a été annulé proprement.');
define('TEXT_SYNC_DL_STOPPED_RETRY',    'Vous pourrez relancer le chargement quand vous le souhaitez.');
define('TEXT_SYNC_DL_FAILED',           'Le chargement a échoué et a été entièrement annulé.');
define('TEXT_SYNC_DL_FAILED_SAFE',      'Votre base locale est restée dans son état précédent.');
define('TEXT_SYNC_DL_CONNECTION_LOST', "La connexion au serveur a été perdue pendant le transfert (le volume de données était probablement trop important pour le serveur en une seule fois). Rien n'a été modifié localement. Réessayez ; si le problème persiste, contactez le support pour augmenter les limites du serveur.");
 
// ---- Sens montant : NOMAD -> SERVEUR  (process_toserveur.php) ----
 
define('TEXT_SYNC_UP_NB_AGENTS',        "Nombre d'Agents synchronisés : ");
define('TEXT_SYNC_UP_NB_RA',            'Nombre de RA synchronisés : ');
define('TEXT_SYNC_UP_NB_JGE',           'Nombre de Jaugeages synchronisés : ');
define('TEXT_SYNC_UP_STOPPED',          'Synchronisation interrompue à votre demande.');
define('TEXT_SYNC_UP_STOPPED_ROLLBACK', "Aucune donnée n'a été envoyée au serveur : tout a été annulé proprement.");
define('TEXT_SYNC_UP_STOPPED_RETRY',    'Vous pourrez relancer la synchronisation quand vous le souhaitez.');
define('TEXT_SYNC_UP_FAILED',           'La synchronisation a échoué et a été entièrement annulée.');
define('TEXT_SYNC_UP_FAILED_SAFE',      "Aucune donnée n'a été envoyée : vos saisies terrain sont intactes.");

?>
<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Configuration des variables globales pour les Tables
----------------------------------------
*/

// Définition des tables de la base

// Tables Admin
define('TABLE_AUTORISATION',DB_TABLE_PREFIX_ADMIN . 'autorisation');
define('TABLE_SESSION',DB_TABLE_PREFIX_ADMIN . 'session');

define('TABLE_ACTIONS',DB_TABLE_PREFIX_ADMIN . 'actions');
define('TABLE_ACTIONS_TYPE',DB_TABLE_PREFIX_ADMIN . 'actions_type');

define('TABLE_SYNC_LOGS',DB_TABLE_PREFIX_ADMIN . 'sync_logs');

// Tables Géographique
define('TABLE_TERRITOIRE',DB_TABLE_PREFIX . 'geo_territoire');
define('TABLE_REGION',DB_TABLE_PREFIX . 'geo_region');
define('TABLE_COMMUNE',DB_TABLE_PREFIX . 'geo_commune');
define('TABLE_REGIONHYDRO',DB_TABLE_PREFIX . 'geo_regionhydro');
define('TABLE_RIVIERE',DB_TABLE_PREFIX . 'geo_riviere');
define('TABLE_GEO_AQUIFERE',DB_TABLE_PREFIX . 'geo_aquifere');
define('TABLE_TOURNEE',DB_TABLE_PREFIX . 'geo_tournee');

// Tables Utilisateurs
define('TABLE_USER',DB_TABLE_PREFIX_ADMIN . 'user');
define('TABLE_USER_ACCES',DB_TABLE_PREFIX_ADMIN . 'user_acces');
define('TABLE_USER_MENU',DB_TABLE_PREFIX_ADMIN . 'user_menu');
define('TABLE_USER_FILTER',DB_TABLE_PREFIX_ADMIN . 'user_filter');
define('TABLE_USER_COORD',DB_TABLE_PREFIX_ADMIN . 'user_coord');
define('TABLE_SERVICE',DB_TABLE_PREFIX_ADMIN . 'service');

define('TABLE_MENU',DB_TABLE_PREFIX_ADMIN . 'menu');

// Tables de contrôle d'accès
define('TABLE_IP_LOGIN',DB_TABLE_PREFIX_CTRL . 'ip_login');
define('TABLE_IP_SUSPECT',DB_TABLE_PREFIX_CTRL . 'ip_suspect');
define('TABLE_IP_OUT',DB_TABLE_PREFIX_CTRL . 'ip_out');


// Tables de structure des données

define('TABLE_EQ_TYPE',DB_TABLE_PREFIX . 'eq_type'); // Type de données (Hydro, Pluvio, Piézo, ...)
define('TABLE_TYPE_DATA',DB_TABLE_PREFIX . 'data_type'); // C'est la table du type de chroniques (CI, QI, ...)
define('TABLE_DATA_TYPE_AXE',DB_TABLE_PREFIX . 'data_type_axe');
define('TABLE_AGENT',DB_TABLE_PREFIX . 'agent');

define('TABLE_STATION',DB_TABLE_PREFIX . 'station');
define('TABLE_STATION_PROPRIO',DB_TABLE_PREFIX . 'station_proprietaire'); // Repère pour mesure (hérité de Piézobase)
define('TABLE_STATION_ACCESS',DB_TABLE_PREFIX . 'station_access'); // Information sur l'accès à la station
define('TABLE_STATION_NATURE',DB_TABLE_PREFIX . 'station_nature'); // Nature de station (hérité de Piézobase mais ajout avec les autres stations)
define('TABLE_STATION_PHOTOS',DB_TABLE_PREFIX . 'station_photos'); // Photos liées aux stations
define('TABLE_STATION_PIEZO_CARACTERISTIQUE',DB_TABLE_PREFIX . 'station_piezo_caracteristique'); // Caractéristique d'un forage ou point piézométrique (hérité de Piézobase)
define('TABLE_STATION_PIEZO_REPERE',DB_TABLE_PREFIX . 'station_piezo_repere'); // Repere de station (hérité de Piézobase)
define('TABLE_STATION_PIEZO_SCHEMA',DB_TABLE_PREFIX . 'station_piezo_schema'); // schema pour station piézo (hérité de Piézobase)
define('TABLE_STATION_SCHEMA_TO_NATURE',DB_TABLE_PREFIX . 'station_piezo_schema_to_nature');

define('TABLE_STATION_TO_TOURNEE',DB_TABLE_PREFIX . 'station_to_tournee'); // Repère pour mesure (hérité de Piézobase)
define('TABLE_TOURNEE_PERIODE',DB_TABLE_PREFIX . 'tournee_periode');
define('TABLE_OPTION_PASTEMPS',DB_TABLE_PREFIX . 'option_pastemps');


// Tables Equipements (Matériel)
define('TABLE_MOULINET',DB_TABLE_PREFIX . 'eq_moulinet');
define('TABLE_HELICE',DB_TABLE_PREFIX . 'eq_helice');
define('TABLE_SAUMON',DB_TABLE_PREFIX . 'eq_saumon');


// Tables Imports et Exports
define('TABLE_IMPORT',DB_TABLE_PREFIX . 'import');
define('TABLE_IMPORT_FILES',DB_TABLE_PREFIX . 'import_files'); // Caractéristiques des fichiers pouvant être importés.
define('TABLE_IMPORT_SUIVI',DB_TABLE_PREFIX . 'import_suivi'); // Caractéristiques des fichiers pouvant être importés.

define('TABLE_EXPORT_INTERVAL',DB_TABLE_PREFIX . 'export_interval');


// Tables des données hydrologiques
define('TABLE_DATA_RA',DB_TABLE_PREFIX . 'data_ra');
define('TABLE_DATA_RA_PIEZO_PROFIL',DB_TABLE_PREFIX . 'data_ra_piezo_profil'); // Profil en profondeur / Diachromie ; Lié à un RA (Mesure)
define('TABLE_DATA_META',DB_TABLE_PREFIX . 'data_meta');
define('TABLE_DATA_META_TEMP',DB_TABLE_PREFIX . 'data_meta_temp');
define('TABLE_DATA_QUALITE',DB_TABLE_PREFIX . 'data_qualite');

define('TABLE_DATA_ALL',DB_TABLE_PREFIX . 'data_all');
define('TABLE_DATA_ALL_TEMP',DB_TABLE_PREFIX . 'data_all_temp');
define('TABLE_DATA_LAB',DB_TABLE_PREFIX . 'data_lab');
define('TABLE_DATA_TOT',DB_TABLE_PREFIX . 'data_tot');

// TABLE POUR LA CORRECTION DES DONNEES
define('TABLE_DATA_CORRECTION',DB_TABLE_PREFIX . 'data_correction');
define('TABLE_DATA_ALL_CORRECTION',DB_TABLE_PREFIX . 'data_all_correction');
define('TABLE_DATA_META_CORRECTION',DB_TABLE_PREFIX . 'data_meta_correction');
define('TABLE_DATA_ETL_CORRECTION',DB_TABLE_PREFIX . 'data_etl_correction');


// JGE
define('TABLE_DATA_JGE',DB_TABLE_PREFIX . 'data_jge');
define('TABLE_DATA_JGE_BRAS',DB_TABLE_PREFIX . 'data_jge_bras');
define('TABLE_DATA_JGE_PTS',DB_TABLE_PREFIX . 'data_jge_points');
//define('TABLE_DATA_JGE_TO_AGENT',DB_TABLE_PREFIX . 'data_jge_to_agent');

define('TABLE_DATA_JGE_SITE',DB_TABLE_PREFIX . 'data_jge_site'); // Tab du Type de Site Jaugeage (Aval et Amont d'un pont ou de l'échelle limnimétrique)
define('TABLE_DATA_JGE_METHODE',DB_TABLE_PREFIX . 'data_jge_methode'); // Tab Jaugeage Méthode
define('TABLE_DATA_JGE_TYPE',DB_TABLE_PREFIX . 'data_jge_type'); // Tab Jaugeage Type
define('TABLE_DATA_JGE_FONDLIT',DB_TABLE_PREFIX . 'data_jge_fondlit'); // Tab Jaugeage Fond Lit

// ETL
define('TABLE_DATA_ETL',DB_TABLE_PREFIX . 'data_etl');
define('TABLE_DATA_ETL_DATA',DB_TABLE_PREFIX . 'data_etl_data');

//define('TABLE_DATA_PIEZO_REPERE',DB_TABLE_PREFIX . 'station_piezo_repere'); // Repère du piézomètre / puits
//define('TABLE_EQ_HELICE_TO_EQUATION',DB_TABLE_PREFIX . 'eq_helice_to_equation');





?>
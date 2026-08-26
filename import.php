<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Data import management page - Initialization
- Loads all reference tables needed for the import form
- Delegates form rendering to form_import_step1.php
----------------------------------------
*/

require('include/application_top.php');

// -----------------------------------------------
// Initialize page variables

$info_import = json_encode(TEXT_IMPORT_INSTRUCTIONS_LINK);

$message_info = '';
$data_step    = 1; // Current step in the import workflow
$verif_form   = 0; // Form validation flag (1 = all required fields filled)
$row          = 0;
$entete       = 0;

$select_station_tab = [];

// Default date range: full current year
$today       = date('d-m-Y');
$date_1      = $today;
$date_2      = $today;
$year_today  = date('Y');
$month_today = date('m');

$date_format = 'd-m-Y';

$year_1  = $year_today;
$month_1 = '01';
$day_1   = '01';
$year_2  = $year_today;
$month_2 = '12';
$day_2   = cal_days_in_month(CAL_GREGORIAN, $month_2, $year_2); // Last day of December


// -----------------------------------------------
// Query: Regions for this territory

$sql_region   = "SELECT DISTINCT id_region, nom_region
                 FROM " . TABLE_REGION . "
                 WHERE id_territoire = " . $territoire_id;
$region_query = tep_db_query($sql_link, $sql_region);
while ($region = tep_db_fetch_array($region_query))
{
    $region_array[$region['id_region']] = html_entity_decode($region['nom_region'] ?? '');
}


// -----------------------------------------------
// Query: All stations (used to match imported data to a station)

$sql_station_all   = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
                      FROM " . TABLE_STATION;
$station_all_query = tep_db_query($sql_link, $sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$station_all['code_station']] = [
        'id_station'   => $station_all['id_station'],
        'nom_station'  => html_entity_decode($station_all['nom_station'] ?? ''),
        'station_type' => $station_all['station_type'],
    ];
}


// -----------------------------------------------
// Query: Importable file format definitions
// Note: the 'algo' field may contain executable parsing logic — handle with care (security risk)

$sql_import_files   = "SELECT DISTINCT id, name_ext, multi_feuil, separateur, description, algo, valid
                       FROM " . TABLE_IMPORT_FILES . "
                       WHERE valid = 1
                       ORDER BY name_ext ASC";
$import_files_query = tep_db_query($sql_link, $sql_import_files);
while ($import_files_tab = tep_db_fetch_array($import_files_query))
{
    $name_ext = html_entity_decode($import_files_tab['name_ext'] ?? '');

    $import_files[$name_ext] = [
        'id'          => $import_files_tab['id'],
        'multi_feuil' => $import_files_tab['multi_feuil'],
        'separateur'  => $import_files_tab['separateur'],
        'description' => html_entity_decode($import_files_tab['description'] ?? ''),
        'algo'        => $import_files_tab['algo'], // WARNING: may contain executable parsing code
    ];
}


// -----------------------------------------------
// Query: Equipment types (Rain, Flow, etc.)

$sql_eq_type   = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_graph
                  FROM " . TABLE_EQ_TYPE . "
                  WHERE active_eq_type = 1
                  ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = [
        'nom_eq_type'      => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type'    => $eq_type_tab['unite_eq_type'],
        'valeur_data_type' => $eq_type_tab['valeur_data_type'],
        'type_graph'       => $eq_type_tab['type_graph'],
    ];
}


// -----------------------------------------------
// Query: Chronological data types (CI, PI, CIE, etc.)

$sql_type_chron   = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
                     FROM " . TABLE_TYPE_DATA . "
                     ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$type_chron_tab['init_type_data']] = [
        'id_data_type'    => $type_chron_tab['id_data_type'],
        'nom_type_data'   => html_entity_decode($type_chron_tab['nom_type_data'] ?? ''),
        'unite'           => $type_chron_tab['unite'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
    ];
}


// -----------------------------------------------
// Query: Data quality codes

$sql_quality_data   = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data
                       FROM " . TABLE_DATA_QUALITE;
$quality_data_query = tep_db_query($sql_link, $sql_quality_data);
while ($quality_data_tab = tep_db_fetch_array($quality_data_query))
{
    $quality_data_array[$quality_data_tab['init_qualite_data']] = [
        'id_data_qualite'  => $quality_data_tab['id_data_qualite'],
        'nom_qualite_data' => html_entity_decode($quality_data_tab['nom_qualite_data']  ?? ''),
        'info_qualite_data'=> html_entity_decode($quality_data_tab['info_qualite_data'] ?? ''),
    ];
}


// -----------------------------------------------
// Delegate form rendering and validation to step 1

require(DIR_WS_IMPORT . 'form_import_step1.php');

?>
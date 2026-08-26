<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Database query to find chronicles related to selected stations
Server-side AJAX procedure
----------------------------------------
*/
// Configuration
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');
// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die(TEXT_DB_CONNECTION_ERROR);
mysqli_query($sql_link, 'SET NAMES UTF8');
// Get JSON data from AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);
// Extract and sanitize data - ensure list_station_txt contains only integers
$ids_raw = explode(',', $dataInfo['list_station_txt']);
$ids_clean = array_filter($ids_raw, 'is_numeric');
$list_station_txt = implode(',', array_map('intval', $ids_clean));
$date_1 = $dataInfo['date_1'];
$date_2 = $dataInfo['date_2'];
// Initialize variables
$nb_stations_ref = 0;
$nb_chron_all = 0;
$nb_data_all = 0;
$id_station_encours = 0;
$min_date_all = null;
$max_date_all = null;
$result_html = '';
// Load required tables
// EQUIPMENT TYPE TABLE
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_color_background, type_graph
                FROM ".TABLE_EQ_TYPE."
                WHERE active_eq_type=1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_array = array();
while ($eq_type_tab = tep_db_fetch_array($eq_type_query)) {
    $eq_type_array[$eq_type_tab['id_eq_type']] = array(
        'id_eq_type' => $eq_type_tab['id_eq_type'],
        'nom_eq_type' => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type' => $eq_type_tab['unite_eq_type'],
        'valeur_data_type' => $eq_type_tab['valeur_data_type'],
        'type_color_border' => $eq_type_tab['type_color_border'],
        'type_color_background' => $eq_type_tab['type_color_background'],
        'type_graph' => $eq_type_tab['type_graph']
    );
}
// DATA TYPE AXIS
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite, nb_round FROM ".TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_array = array();
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query)) {
    $data_type_axe_array[$data_type_axe['id']] = array(
        'axe' => $data_type_axe['axe'],
        'unite' => $data_type_axe['unite'],        
        'nb_round' => $data_type_axe['nb_round'],
    );
}
// CHRONICLE TYPE TABLE
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode
                  FROM ".TABLE_TYPE_DATA.
                  " ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_array = array();
while ($type_chron_tab = tep_db_fetch_array($type_chron_query)) {
    $axe_nom = '';
    $axe_unite = '';
    if(isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe'])) {
        $axe_nom = $data_type_axe_array[$type_chron_tab['axe_data']]['axe'];
        $axe_unite = $data_type_axe_array[$type_chron_tab['axe_data']]['unite'];
    }
    $type_chron_array[$type_chron_tab['id_data_type']] = array(
        'init_type_data' => $type_chron_tab['init_type_data'],
        'nom_type_data' => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom' => $axe_nom,
        'unite' => $axe_unite,
        'to_periode' => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode']
    );
}

// -----------------------------------------------------------------------
// SELECTED STATIONS - Load station list and group by type in one pass
// -----------------------------------------------------------------------
$sql_station_select = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
                        FROM ".TABLE_STATION."
                        WHERE id_station IN (".$list_station_txt.")
                        ORDER BY station_type ASC, nom_station ASC";
$station_select_query = tep_db_query($sql_link, $sql_station_select);

$typedata_array = array();
$typedata_temp = 0;
$station_select_array = array();  // All selected stations indexed by id_station
$station_type_groups = array();   // Stations grouped by type: [type => [id_station, ...]]

while ($station_select = tep_db_fetch_array($station_select_query)) {
    $id_st = $station_select['id_station'];
    $type_st = $station_select['station_type'];

    // Build station index (replaces $station_all_array for selected stations)
    $station_select_array[$id_st] = array(
        'nom_station' => $station_select['nom_station'],
        'code_station' => html_entity_decode($station_select['code_station'] ?? ''),
        'type_station' => html_entity_decode($type_st ?? '')
    );

    // Group stations by data type
    if($typedata_temp != $type_st) {
        $typedata_array[] = $type_st;
        $typedata_temp = $type_st;
    }
    $station_type_groups[$type_st][] = $id_st;
}

// -----------------------------------------------------------------------
// BULK QUERY - Chronicle data for ALL selected stations at once
// Replaces the per-station query loop (N queries -> 1 query)
// -----------------------------------------------------------------------
$chron_data = array();  // Indexed by id_station

$sql_chroniques_data = "SELECT COUNT(*) as nb_data,
                            MIN(da.dateheure) as min_date,
                            MAX(da.dateheure) as max_date,
                            dm.id_station, dm.id, dm.id_typedata,
                            td.init_type_data, td.nom_type_data, td.axe_data, td.raw_data
                        FROM ".TABLE_DATA_ALL." da
                        JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                        JOIN ".TABLE_TYPE_DATA." td ON dm.id_typedata=td.id_data_type
                        WHERE dm.id_station IN (".$list_station_txt.")
                        AND da.dateheure >= '".datefr_us($date_1)." 00:00:00'
                        AND da.dateheure <= '".datefr_us($date_2)." 23:59:59'
                        GROUP BY dm.id_station, dm.id_typedata
                        ORDER BY dm.id_station, td.init_type_data ASC";
$data_chron_query = tep_db_query($sql_link, $sql_chroniques_data);

while ($data_chron_tab = tep_db_fetch_array($data_chron_query)) {
    $min_date = '';
    $max_date = '';
    if(isset($data_chron_tab['min_date']) && tep_not_null($data_chron_tab['min_date'])) {
        $min_date_array = explode(" ", $data_chron_tab['min_date']);
        $min_date = dateus_fr($min_date_array[0]);
    }
    if(isset($data_chron_tab['max_date']) && tep_not_null($data_chron_tab['max_date'])) {
        $max_date_array = explode(" ", $data_chron_tab['max_date']);
        $max_date = dateus_fr($max_date_array[0]);
    }
    // Track global min/max dates across all data
    $datetime_chron = DateTime::createFromFormat('Y-m-d H:i:s', $data_chron_tab['min_date']);
    if(is_null($min_date_all)) {
        $min_date_all = $datetime_chron;
    } else {
        if($datetime_chron < $min_date_all) $min_date_all = $datetime_chron;
    }
    $datetime_chron = DateTime::createFromFormat('Y-m-d H:i:s', $data_chron_tab['max_date']);
    if(is_null($max_date_all)) {
        $max_date_all = $datetime_chron;
    } else {
        if($datetime_chron > $max_date_all) $max_date_all = $datetime_chron;
    }
    $init_chron_data = tep_not_null($data_chron_tab['init_type_data']) ? html_entity_decode($data_chron_tab['init_type_data'] ?? '') : '';
    $nom_chron_data  = tep_not_null($data_chron_tab['nom_type_data'])  ? html_entity_decode($data_chron_tab['nom_type_data'] ?? '')  : '';
    $axe_data      = tep_not_null($data_chron_tab['axe_data'])          ? html_entity_decode($data_chron_tab['axe_data'] ?? '')          : '';

    // A time-series may have no axis linked in its configuration (axe_data
    // absent from TABLE_DATA_TYPE_AXE). In that case unit defaults to '' and
    // rounding to 0, instead of triggering an "Undefined array key" warning.
    $unite_data = '';
    $nb_round_data = 0;
    if (isset($axe_data) && isset($data_type_axe_array[$axe_data])) {
        $unite_data    = $data_type_axe_array[$axe_data]['unite'];
        $nb_round_data = $data_type_axe_array[$axe_data]['nb_round'];
    }

    $chron_data[$data_chron_tab['id_station']][] = array(
        'id' => $data_chron_tab['id'],
        'id_station' => $data_chron_tab['id_station'],
        'id_chron_data' => $data_chron_tab['id_typedata'],
        'init_chron_data' => $init_chron_data,
        'nom_chron_data' => $nom_chron_data,
        'unite_data' => $unite_data,
        'nb_data' => $data_chron_tab['nb_data'],
        'min_date' => $min_date,
        'max_date' => $max_date,
        'raw_data' => (int) ($data_chron_tab['raw_data'] ?? 0)
    );
    if($data_chron_tab['nb_data'] > 0) $nb_chron_all++;
}

// -----------------------------------------------------------------------
// BULK QUERY - LAB data for ALL selected stations at once
// -----------------------------------------------------------------------
$lab_array = array();  // Indexed by id_station

$sql_lab = "SELECT lab.id_station,
                MIN(lab.date_heure) AS date_heure_lab_min,
                MAX(lab.date_heure) AS date_heure_lab_max,
                COUNT(lab.id) AS nb_lab
            FROM ".TABLE_DATA_LAB." lab
            WHERE lab.id_station IN (".$list_station_txt.")
            AND lab.date_heure >= '".datefr_us($date_1)." 00:00:00'
            AND lab.date_heure <= '".datefr_us($date_2)." 23:59:59'
            GROUP BY lab.id_station";
$data_lab_query = tep_db_query($sql_link, $sql_lab);
while ($data_lab_tab = tep_db_fetch_array($data_lab_query)) {
    if($data_lab_tab['nb_lab'] > 0) {
        $min_date_lab = '';
        $max_date_lab = '';
        if(isset($data_lab_tab['date_heure_lab_min']) && tep_not_null($data_lab_tab['date_heure_lab_min'])) {
            $min_date_array = explode(" ", $data_lab_tab['date_heure_lab_min']);
            $min_date_lab = dateus_fr($min_date_array[0]);
        }
        if(isset($data_lab_tab['date_heure_lab_max']) && tep_not_null($data_lab_tab['date_heure_lab_max'])) {
            $max_date_array = explode(" ", $data_lab_tab['date_heure_lab_max']);
            $max_date_lab = dateus_fr($max_date_array[0]);
        }
        $lab_array[$data_lab_tab['id_station']] = array(
            'nb_lab' => $data_lab_tab['nb_lab'],
            'min_date_lab' => $min_date_lab,
            'max_date_lab' => $max_date_lab
        );
    }
}

// -----------------------------------------------------------------------
// BULK QUERY - TOT data for ALL selected stations at once
// -----------------------------------------------------------------------
$tot_array = array();  // Indexed by id_station

$sql_tot = "SELECT tot.id_station,
                MIN(tot.date_heure) AS date_heure_tot_min,
                MAX(tot.date_heure) AS date_heure_tot_max,
                COUNT(tot.id) AS nb_tot
            FROM ".TABLE_DATA_TOT." tot
            WHERE tot.id_station IN (".$list_station_txt.")
            AND tot.date_heure >= '".datefr_us($date_1)." 00:00:00'
            AND tot.date_heure <= '".datefr_us($date_2)." 23:59:59'
            GROUP BY tot.id_station";
$data_tot_query = tep_db_query($sql_link, $sql_tot);
while ($data_tot_tab = tep_db_fetch_array($data_tot_query)) {
    if($data_tot_tab['nb_tot'] > 0) {
        $min_date_tot = '';
        $max_date_tot = '';
        if(isset($data_tot_tab['date_heure_tot_min']) && tep_not_null($data_tot_tab['date_heure_tot_min'])) {
            $min_date_array = explode(" ", $data_tot_tab['date_heure_tot_min']);
            $min_date_tot = dateus_fr($min_date_array[0]);
        }
        if(isset($data_tot_tab['date_heure_tot_max']) && tep_not_null($data_tot_tab['date_heure_tot_max'])) {
            $max_date_array = explode(" ", $data_tot_tab['date_heure_tot_max']);
            $max_date_tot = dateus_fr($max_date_array[0]);
        }
        $tot_array[$data_tot_tab['id_station']] = array(
            'nb_tot' => $data_tot_tab['nb_tot'],
            'min_date_tot' => $min_date_tot,
            'max_date_tot' => $max_date_tot
        );
    }
}

// -----------------------------------------------------------------------
// BULK QUERY - RA data for ALL selected stations at once
// -----------------------------------------------------------------------
$ra_array = array();  // Indexed by id_station

$sql_ra = "SELECT ra.id_station,
               MIN(ra.date_heure_ra) AS date_heure_ra_min,
               MAX(ra.date_heure_ra) AS date_heure_ra_max,
               COUNT(ra.id_ra) AS nb_ra
           FROM ".TABLE_DATA_RA." ra
           WHERE ra.id_station IN (".$list_station_txt.")
           AND ra.date_heure_ra >= '".datefr_us($date_1)." 00:00:00'
           AND ra.date_heure_ra <= '".datefr_us($date_2)." 23:59:59'
           GROUP BY ra.id_station";
$data_ra_query = tep_db_query($sql_link, $sql_ra);
while ($data_ra_tab = tep_db_fetch_array($data_ra_query)) {
    if($data_ra_tab['nb_ra'] > 0) {
        $min_date_ra = '';
        $max_date_ra = '';
        if(isset($data_ra_tab['date_heure_ra_min']) && tep_not_null($data_ra_tab['date_heure_ra_min'])) {
            $min_date_array = explode(" ", $data_ra_tab['date_heure_ra_min']);
            $min_date_ra = dateus_fr($min_date_array[0]);
        }
        if(isset($data_ra_tab['date_heure_ra_max']) && tep_not_null($data_ra_tab['date_heure_ra_max'])) {
            $max_date_array = explode(" ", $data_ra_tab['date_heure_ra_max']);
            $max_date_ra = dateus_fr($max_date_array[0]);
        }
        $ra_array[$data_ra_tab['id_station']] = array(
            'nb_ra' => $data_ra_tab['nb_ra'],
            'min_date_ra' => $min_date_ra,
            'max_date_ra' => $max_date_ra
        );
    }
}

// -----------------------------------------------------------------------
// BULK QUERY - JGE data for hydrometric stations only (type 11)
// -----------------------------------------------------------------------
$jge_array = array();  // Indexed by id_station

// Only query if there are hydrometric stations in the selection
$hydro_station_ids = isset($station_type_groups[11]) ? implode(',', $station_type_groups[11]) : '';
if(!empty($hydro_station_ids)) {
    $sql_jge = "SELECT jge.id_station,
                    MIN(jge.datetime) AS date_heure_jge_min,
                    MAX(jge.datetime) AS date_heure_jge_max,
                    COUNT(jge.id) AS nb_jge
                FROM ".TABLE_DATA_JGE." jge
                WHERE jge.id_station IN (".$hydro_station_ids.")
                AND jge.datetime >= '".datefr_us($date_1)." 00:00:00'
                AND jge.datetime <= '".datefr_us($date_2)." 23:59:59'
                GROUP BY jge.id_station";
    $data_jge_query = tep_db_query($sql_link, $sql_jge);
    while ($data_jge_tab = tep_db_fetch_array($data_jge_query)) {
        if($data_jge_tab['nb_jge'] > 0) {
            $min_date_jge = '';
            $max_date_jge = '';
            if(isset($data_jge_tab['date_heure_jge_min']) && tep_not_null($data_jge_tab['date_heure_jge_min'])) {
                $min_date_array = explode(" ", $data_jge_tab['date_heure_jge_min']);
                $min_date_jge = dateus_fr($min_date_array[0]);
            }
            if(isset($data_jge_tab['date_heure_jge_max']) && tep_not_null($data_jge_tab['date_heure_jge_max'])) {
                $max_date_array = explode(" ", $data_jge_tab['date_heure_jge_max']);
                $max_date_jge = dateus_fr($max_date_array[0]);
            }
            $jge_array[$data_jge_tab['id_station']] = array(
                'nb_jge' => $data_jge_tab['nb_jge'],
                'min_date_jge' => $min_date_jge,
                'max_date_jge' => $max_date_jge
            );
        }
    }

    // -----------------------------------------------------------------------
    // BULK QUERY - ETL data for hydrometric stations only (type 11)
    // -----------------------------------------------------------------------
    $etl_array = array();  // Indexed by id_station

    $sql_etl = "SELECT etl.id_station,
                    MIN(etl.datetime_first) AS date_heure_etl_min,
                    MAX(etl.datetime_end) AS date_heure_etl_max,
                    COUNT(etl.id) AS nb_etl
                FROM ".TABLE_DATA_ETL." etl
                WHERE etl.id_station IN (".$hydro_station_ids.")
                AND etl.datetime_first >= '".datefr_us($date_1)." 00:00:00'
                AND etl.datetime_end <= '".datefr_us($date_2)." 23:59:59'
                GROUP BY etl.id_station";
    $data_etl_query = tep_db_query($sql_link, $sql_etl);
    while ($data_etl_tab = tep_db_fetch_array($data_etl_query)) {
        if($data_etl_tab['nb_etl'] > 0) {
            $min_date_etl = '';
            $max_date_etl = '';
            if(isset($data_etl_tab['date_heure_etl_min']) && tep_not_null($data_etl_tab['date_heure_etl_min'])) {
                $min_date_array = explode(" ", $data_etl_tab['date_heure_etl_min']);
                $min_date_etl = dateus_fr($min_date_array[0]);
            }
            if(isset($data_etl_tab['date_heure_etl_max']) && tep_not_null($data_etl_tab['date_heure_etl_max'])) {
                $max_date_array = explode(" ", $data_etl_tab['date_heure_etl_max']);
                $max_date_etl = dateus_fr($max_date_array[0]);
            }
            $etl_array[$data_etl_tab['id_station']] = array(
                'nb_etl' => $data_etl_tab['nb_etl'],
                'min_date_etl' => $min_date_etl,
                'max_date_etl' => $max_date_etl
            );
        }
    }
}

// -----------------------------------------------------------------------
// BULK QUERY - DIAC data for piezometer stations only (type 5)
// -----------------------------------------------------------------------
$diac_array = array();  // Indexed by id_station

// Only query if there are piezometer stations in the selection
$piezo_station_ids = isset($station_type_groups[5]) ? implode(',', $station_type_groups[5]) : '';
if(!empty($piezo_station_ids)) {
    $sql_diac = "SELECT ra.id_station,
                    MIN(ra.date_heure_ra) AS date_heure_diac_min,
                    MAX(ra.date_heure_ra) AS date_heure_diac_max,
                    COUNT(pp.id) AS nb_diac
                FROM ".TABLE_DATA_RA_PIEZO_PROFIL." pp
                JOIN ".TABLE_DATA_RA." ra ON ra.id_ra=pp.id_ra
                WHERE ra.id_station IN (".$piezo_station_ids.")
                AND ra.date_heure_ra >= '".datefr_us($date_1)." 00:00:00'
                AND ra.date_heure_ra <= '".datefr_us($date_2)." 23:59:59'
                GROUP BY ra.id_station";
    $data_diac_query = tep_db_query($sql_link, $sql_diac);
    while ($data_diac_tab = tep_db_fetch_array($data_diac_query)) {
        if($data_diac_tab['nb_diac'] > 0) {
            $min_date_diac = '';
            $max_date_diac = '';
            if(isset($data_diac_tab['date_heure_diac_min']) && tep_not_null($data_diac_tab['date_heure_diac_min'])) {
                $min_date_array = explode(" ", $data_diac_tab['date_heure_diac_min'] ?? '');
                $min_date_diac = dateus_fr($min_date_array[0]);
            }
            if(isset($data_diac_tab['date_heure_diac_max']) && tep_not_null($data_diac_tab['date_heure_diac_max'])) {
                $max_date_array = explode(" ", $data_diac_tab['date_heure_diac_max'] ?? '');
                $max_date_diac = dateus_fr($max_date_array[0]);
            }
            $diac_array[$data_diac_tab['id_station']] = array(
                'nb_diac' => $data_diac_tab['nb_diac'],
                'min_date_diac' => $min_date_diac,
                'max_date_diac' => $max_date_diac
            );
        }
    }
}

// Convert global min and max dates to strings for display
if(!is_null($min_date_all)) {
    $date_1 = $min_date_all->format('d-m-Y');
    $month_1 = $min_date_all->format('m');
    $year_1 = $min_date_all->format('Y');
}
if(!is_null($max_date_all)) {
    $date_2 = $max_date_all->format('d-m-Y');
    $month_2 = $max_date_all->format('m');
    $year_2 = $max_date_all->format('Y');
}

// -----------------------------------------------------------------------
// HTML GENERATION - Display data grouped by data type columns (Flow/Rain/Piezometer)
// Using output buffer for better performance on large HTML blocks
// -----------------------------------------------------------------------
ob_start();
 
for($td=0; $td<sizeof($typedata_array); $td++)
{
    $id_typedata = $typedata_array[$td];
 
    // Get the type color (from DB) — used for the title bar and card shadow
    $type_color  = $eq_type_array[$id_typedata]['type_color_border'];
    $type_name   = $eq_type_array[$id_typedata]['nom_eq_type'];
 
    $margin_left = ($td > 0) ? '0.5%' : '0';
 
    echo "<div class='ts-column' style='float:left;width:420px;padding:0 8px;margin-left:".$margin_left.";'>";
 
        // ---- Column title with colored vertical bar + Select +/- ----
        echo "<div class='ts-col-header' style='border-left-color:".$type_color.";'>";
            echo "<span class='ts-col-title' style='color:".$type_color.";'>".$type_name."</span>";
            echo "<span class='selectAll ts-select-all' onclick='toggleCheckboxes(0,".$id_typedata.",0);'>".TEXT_SELECT_ALL."</span>";
        echo "</div>";
 
        // ---- Series type filter dropdown ----
        echo "<div class='ts-filter'>";
            echo "<span class='ts-filter-label'>".TEXT_SELECT_CHRONIC_TYPE."</span>";
            echo "<select name='select_type_chron_".$id_typedata."' id='select_type_chron_".$id_typedata."' onchange='handleSelectChange(this);'>";
                echo "<option value='-1'>".TEXT_NONE."</option>";
 
                if(isset($type_chron_array)) {
                    foreach($type_chron_array as $id_type_chron => $type_chron) {
                        if($type_chron['id_eq_type_data'] == $id_typedata) {
                            echo "<option value='".$id_type_chron."'>".$type_chron['init_type_data']." - ".$type_chron['nom_type_data']."</option>";
                        }
                    }
                }
 
                // Special data type options
                $ra_true = false;
                $jge_true = false;
                $etl_true = false;
                $diac_true = false;
                if(isset($station_type_groups[$id_typedata])) {
                    foreach($station_type_groups[$id_typedata] as $st_id) {
                        if(isset($ra_array[$st_id]))   $ra_true   = true;
                        if(isset($jge_array[$st_id]))  $jge_true  = true;
                        if(isset($etl_array[$st_id]))  $etl_true  = true;
                        if(isset($diac_array[$st_id])) $diac_true = true;
                    }
                }
                if($ra_true)   echo "<option value='ra'>".TEXT_RA."</option>";
                if($jge_true)  echo "<option value='jge'>".TEXT_JGE."</option>";
                if($etl_true)  echo "<option value='etl'>".TEXT_ETL."</option>";
                if($diac_true) echo "<option value='diac'>".TEXT_DIAC."</option>";
            echo "</select>";
        echo "</div>";
 
        // ---- Station cards ----
        if(isset($station_type_groups[$id_typedata]))
        {
            for($st=0; $st<sizeof($station_type_groups[$id_typedata]); $st++)
            {
                $id_station_encours = $station_type_groups[$id_typedata][$st];
                $row = 0;
                $print_table   = '';
 
                // Build all data rows (chronicles + LAB + TOT + RA + JGE + ETL + DIAC)
                if(isset($chron_data[$id_station_encours])) {
                    for($md=0; $md<sizeof($chron_data[$id_station_encours]); $md++) {
                        if($chron_data[$id_station_encours][$md]['nb_data'] > 0) {
                            $row++;
                            $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                            $nb_data_chron = $chron_data[$id_station_encours][$md]['nb_data'];

                            // Raw-data series: render the acronym as a solid red
                            // badge so the user clearly sees it holds unprocessed
                            // (brute) measurements. Elaborated series stay plain.
                            $is_raw     = ($chron_data[$id_station_encours][$md]['raw_data'] == 1);
                            $acro_value = $chron_data[$id_station_encours][$md]['init_chron_data'];
                            $acro_title = $is_raw
                                        ? $chron_data[$id_station_encours][$md]['nom_chron_data'] . " — " . TEXT_CHRON_RAW_DATA
                                        : $chron_data[$id_station_encours][$md]['nom_chron_data'];

                            if ($is_raw)
                            {
                                $acro_html = "<span style='display:inline-block;background:#A32D2D;color:#fff;"
                                           . "font-weight:bold;padding:2px 8px;border-radius:4px;'>" . $acro_value . "</span>";
                            }
                            else
                            {
                                $acro_html = $acro_value;
                            }

                            $print_table .= "<tr ".$row_l.">";
                                $print_table .= "<td title='".$acro_title."'>".$acro_html."</td>";
                                $print_table .= "<td>".$chron_data[$id_station_encours][$md]['unite_data']."</td>";
                                $print_table .= "<td class='ts-num'>".number_format($nb_data_chron, 0, '.', ' ')."</td>";
                                $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_".$chron_data[$id_station_encours][$md]['id_chron_data']."' value='".$nb_data_chron."'>";
                                $print_table .= "<td class='ts-date'>".$chron_data[$id_station_encours][$md]['min_date']."</td>";
                                $print_table .= "<td class='ts-date'>".$chron_data[$id_station_encours][$md]['max_date']."</td>";
                                $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_".$chron_data[$id_station_encours][$md]['id_chron_data']."'></td>";
                            $print_table .= "</tr>";
                        }
                    }
                }
 
                // LAB row
                if(isset($lab_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_lab = $lab_array[$id_station_encours]['nb_lab'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_LAB_DATA."'>".TEXT_LAB."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_lab, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_55' value='".$nb_lab."'>";
                        $print_table .= "<td class='ts-date'>".$lab_array[$id_station_encours]['min_date_lab']."</td>";
                        $print_table .= "<td class='ts-date'>".$lab_array[$id_station_encours]['max_date_lab']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_55'></td>";
                    $print_table .= "</tr>";
                }
 
                // TOT row
                if(isset($tot_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_tot = $tot_array[$id_station_encours]['nb_tot'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_TOT_DATA."'>".TEXT_TOT."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_tot, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_58' value='".$nb_tot."'>";
                        $print_table .= "<td class='ts-date'>".$tot_array[$id_station_encours]['min_date_tot']."</td>";
                        $print_table .= "<td class='ts-date'>".$tot_array[$id_station_encours]['max_date_tot']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_58'></td>";
                    $print_table .= "</tr>";
                }
 
                // RA row
                if(isset($ra_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_ra = $ra_array[$id_station_encours]['nb_ra'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_RA_DESC."'>".TEXT_RA."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_ra, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_ra' value='".$nb_ra."'>";
                        $print_table .= "<td class='ts-date'>".$ra_array[$id_station_encours]['min_date_ra']."</td>";
                        $print_table .= "<td class='ts-date'>".$ra_array[$id_station_encours]['max_date_ra']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_ra'></td>";
                    $print_table .= "</tr>";
                }
 
                // JGE row
                if(isset($jge_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_jge = $jge_array[$id_station_encours]['nb_jge'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_JGE_DESC."'>".TEXT_JGE."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_jge, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_jge' value='".$nb_jge."'>";
                        $print_table .= "<td class='ts-date'>".$jge_array[$id_station_encours]['min_date_jge']."</td>";
                        $print_table .= "<td class='ts-date'>".$jge_array[$id_station_encours]['max_date_jge']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_jge'></td>";
                    $print_table .= "</tr>";
                }
 
                // ETL row
                if(isset($etl_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_etl = $etl_array[$id_station_encours]['nb_etl'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_ETL_DESC."'>".TEXT_ETL."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_etl, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_etl' value='".$nb_etl."'>";
                        $print_table .= "<td class='ts-date'>".$etl_array[$id_station_encours]['min_date_etl']."</td>";
                        $print_table .= "<td class='ts-date'>".$etl_array[$id_station_encours]['max_date_etl']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_etl'></td>";
                    $print_table .= "</tr>";
                }
 
                // DIAC row
                if(isset($diac_array[$id_station_encours])) {
                    $row++;
                    $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\"";
                    $nb_diac = $diac_array[$id_station_encours]['nb_diac'];
                    $print_table .= "<tr ".$row_l.">";
                        $print_table .= "<td title='".TEXT_DIAC_DESC."'>".TEXT_DIAC."</td>";
                        $print_table .= "<td>-</td>";
                        $print_table .= "<td class='ts-num'>".number_format($nb_diac, 0, '.', ' ')."</td>";
                        $print_table .= "<input type='hidden' name='nb_".$id_station_encours."_".$id_typedata."_diac' value='".$nb_diac."'>";
                        $print_table .= "<td class='ts-date'>".$diac_array[$id_station_encours]['min_date_diac']."</td>";
                        $print_table .= "<td class='ts-date'>".$diac_array[$id_station_encours]['max_date_diac']."</td>";
                        $print_table .= "<td class='ts-check'><input type='checkbox' name='check_chron[]' value='".$id_station_encours."_".$id_typedata."_diac'></td>";
                    $print_table .= "</tr>";
                }
 
                // ---- Render the station card ----
                // The --type-color CSS variable carries the type color into the
                // shadow effect (see timeseries_central.css)
                if($row > 0)
                {
                    echo "<div class='ts-card' style='--type-color:".$type_color.";'>";
                        echo "<div class='ts-card-header'>";
                            echo "<a class='ts-card-title' href='modif_station.php?ref=".$id_station_encours."' target='_blank'>";
                                echo $station_select_array[$id_station_encours]['code_station']." - ".$station_select_array[$id_station_encours]['nom_station'];
                            echo "</a>";
                            echo "<span class='ts-badge'>".$row." ".TEXT_CHRONICLES."</span>";
                        echo "</div>";
 
                        echo "<table id='table_tri' class='ts-table' cellspacing='0'>";
                            echo "<thead>";
                                echo "<tr class='header-row'>";
                                    echo "<th>".TEXT_CHRONIC."</th>";
                                    echo "<th>".TEXT_UNIT."</th>";
                                    echo "<th>".TEXT_DATA_COUNT."</th>";
                                    echo "<th>".TEXT_START_DATE."</th>";
                                    echo "<th>".TEXT_END_DATE."</th>";
                                    echo "<th class='ts-check' onclick='toggleCheckboxes(".$id_station_encours.",0,0);'><span class='selectAll'>".TEXT_SELECT_ALL_SHORT."</span></th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                                echo $print_table;
                            echo "</tbody>";
                        echo "</table>";
                    echo "</div>";
                }
                else
                {
                    // Empty card : station has no data in the period
                    echo "<div class='ts-card ts-card-empty' style='--type-color:".$type_color.";'>";
                        echo "<div class='ts-card-header'>";
                            echo "<a class='ts-card-title' href='modif_station.php?ref=".$id_station_encours."' target='_blank'>";
                                echo $station_select_array[$id_station_encours]['code_station']." - ".$station_select_array[$id_station_encours]['nom_station'];
                            echo "</a>";
                        echo "</div>";
                        echo "<p class='ts-no-data'>".TEXT_NO_CHRONIC_FOUND."</p>";
                    echo "</div>";
                }
            }
        }
 
    echo "</div>"; // .ts-column
}
 
$result_html = ob_get_clean();

$responseData = array(
    'result_html' => $result_html,
    'date_debut' => $date_1,
    'date_fin' => $date_2
);
// Encode and send response
echo json_encode($responseData);
?>
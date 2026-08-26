<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — geographic region table
Called by affiche_geo_region_data() in form_geo_regiongeo.php.
Receives territoire_id via JSON POST.
Returns JSON:
  tab_geo_region : bool   — false if no data found
  htmlcode       : string — full HTML table ready to inject
  message_info   : string — error message when tab_geo_region is false

Optimisation: station dependencies are preloaded in a single GROUP BY query
instead of one query per row (avoids N+1 problem on large datasets).
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Decode JSON payload sent by the AJAX call
$dataInfo      = json_decode(file_get_contents('php://input'), true);
$territoire_id = (int) $dataInfo['territoireId'];

$tab_geo_region  = true;
$message_info    = '';
$regiongeo_array = [];


// -----------------------------------------------
// Query: territoire metadata
// theme_region is used as a suffix in the column header and new-entry row label

$territoire_query = tep_db_query($sql_link,
    "SELECT DISTINCT theme_region, region_default
     FROM " . TABLE_TERRITOIRE . "
     WHERE id_territoire = " . $territoire_id);

while ($territoire = tep_db_fetch_array($territoire_query))
{
    $theme_region   = $territoire['theme_region'];
    $region_default = $territoire['region_default'];
}


// -----------------------------------------------
// Preload station dependencies in a single query (avoids N+1)
// Result: $stations_per_region[id_region] = count of stations referencing it

$stations_per_region = [];
$dep_query = tep_db_query($sql_link,
    "SELECT id_region, COUNT(*) AS nb
     FROM " . TABLE_STATION . "
     WHERE id_region IS NOT NULL
     GROUP BY id_region");
while ($d = tep_db_fetch_array($dep_query))
{
    $stations_per_region[(int)$d['id_region']] = (int)$d['nb'];
}


// -----------------------------------------------
// Query: geographic regions for this territoire

$regiongeo_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_region, nom_region
     FROM " . TABLE_REGION . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY LOWER(nom_region) ASC");

while ($regiongeo_tab = tep_db_fetch_array($regiongeo_query))
{
    $id_regiongeo = (int) $regiongeo_tab['id_region'];

    // A region linked to a station must not be deleted
    $del_regiongeo = !isset($stations_per_region[$id_regiongeo]);

    $regiongeo_array[$id_regiongeo] = [
        'nom_region'    => $regiongeo_tab['nom_region'],
        'del_regiongeo' => $del_regiongeo,
    ];
}


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<div class='table-container' style='float:left;height:70vh;'>";
    $htmlcode .= "<table id='table_tri' cellspacing='0'>";

        // Column header — label includes the territory's custom region type name
        $htmlcode .= "<thead>";
            $htmlcode .= "<tr>";
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_REGION_TH . $theme_region . "</th>";
                $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
            $htmlcode .= "</tr>";
        $htmlcode .= "</thead>";

        // New entry row — green border signals the creation input
        $htmlcode .= "<tr><td colspan='2' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_GEO_REGION_ADD . $theme_region . "</td></tr>\n";
        $htmlcode .= "<tr>";
            $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='regiongeo_nom_0'></td>";
            $htmlcode .= "<td>&nbsp;</td>";
        $htmlcode .= "</tr>";
        $htmlcode .= "<tr><td colspan='2' class='lignevide'>&nbsp;</td></tr>";

        if (!empty($regiongeo_array))
        {
            foreach ($regiongeo_array as $id => $data)
            {
                // Alternate row background for readability
                $row_l = (fmod($row, 2) == 0)
                    ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                    : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                $row++;

                $htmlcode .= "<tr {$row_l} id='row_rg_{$id}'>";

                    // Editable name — field name includes the id so process_datageo_save.php can identify it
                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:250px;'"
                                   . " name='regiongeo_nom_{$id}'"
                                   . " value='" . $data['nom_region'] . "'>\n";
                    $htmlcode .= "</td>";

                    // Delete button — disabled (shown as '-') when linked to a station
                    $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_regiongeo'])
                    {
                        $nom_js = addslashes($data['nom_region']);
                        $target = sprintf(TEXT_GEO_VERIFDEL_TARGET_REGION, "<b>" . htmlspecialchars($data['nom_region'], ENT_QUOTES) . "</b>");
                        $target_js = addslashes($target);
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;cursor:pointer;' title='" . TEXT_GEO_BTN_DELETE . "'"
                                   . " onClick=\"confirmDeleteGeo('{$target_js}', function(){ delete_regiongeo('{$id}'); });\">X</a>";
                    }
                    else { $htmlcode .= "<span>-</span>"; }
                    $htmlcode .= "</td>\n";

                $htmlcode .= "</tr>";
            }
        }
        else
        {
            $message_info = TEXT_GEO_NO_DATA;
        }

    $htmlcode .= "</table>";
$htmlcode .= "</div>\n";


// Return JSON response to the client
echo json_encode([
    'tab_geo_region' => $tab_geo_region,
    'htmlcode'       => $htmlcode,
    'message_info'   => $message_info,
]);
?>

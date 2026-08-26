<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — town table
Called by affiche_geo_commune_data() in form_geo_commune.php.
Receives territoire_id via JSON POST.
Returns JSON:
  tab_geo_commune : bool   — false if no data found
  htmlcode        : string — full HTML table ready to inject
  message_info    : string — error message when tab_geo_commune is false

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

$tab_geo_commune = true;
$message_info    = '';
$commune_array   = [];
$regiongeo_array = [];


// -----------------------------------------------
// Query: geographic regions — used to populate the region dropdown in each row

$regiongeo_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_region, nom_region
     FROM " . TABLE_REGION . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY LOWER(nom_region) ASC");

while ($regiongeo_tab = tep_db_fetch_array($regiongeo_query))
{
    $regiongeo_array[$regiongeo_tab['id_region']] = [
        'nom_region' => html_entity_decode($regiongeo_tab['nom_region'] ?? ''),
    ];
}


// -----------------------------------------------
// Preload station dependencies in a single query (avoids N+1)

$stations_per_commune = [];
$dep_query = tep_db_query($sql_link,
    "SELECT id_commune, COUNT(*) AS nb
     FROM " . TABLE_STATION . "
     WHERE id_commune IS NOT NULL
     GROUP BY id_commune");
while ($d = tep_db_fetch_array($dep_query))
{
    $stations_per_commune[(int)$d['id_commune']] = (int)$d['nb'];
}


// -----------------------------------------------
// Query: towns for this territoire

$commune_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_commune, nom_commune, id_region
     FROM " . TABLE_COMMUNE . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY id_region ASC, LOWER(nom_commune) ASC");

while ($commune_tab = tep_db_fetch_array($commune_query))
{
    $id_commune = (int) $commune_tab['id_commune'];

    $del_commune = !isset($stations_per_commune[$id_commune]);

    $commune_array[$id_commune] = [
        'nom_commune'       => $commune_tab['nom_commune'],
        'id_region_commune' => $commune_tab['id_region'],
        'del_commune'       => $del_commune,
    ];
}


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<div class='table-container' style='float:left;height:70vh;'>";
    $htmlcode .= "<table id='table_tri' cellspacing='0'>";

        $htmlcode .= "<thead>";
            $htmlcode .= "<tr class='header-row' style='background-color:#eef3f8;'>";
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_COMMUNE_TH_NOM    . "</th>";
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_COMMUNE_TH_REGION . "</th>";
                $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
            $htmlcode .= "</tr>";
        $htmlcode .= "</thead>";

        // New entry row
        $htmlcode .= "<tr><td colspan='3' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_GEO_COMMUNE_ADD . "</td></tr>\n";
        $htmlcode .= "<tr>";
            $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='commune_nom_0'></td>";
            $htmlcode .= "<td>";
                $htmlcode .= "<select name='select_commune_regiongeo_0' id='select_commune_regiongeo_0'"
                           . " style='width:250px;border:2px solid #609966;'>";
                foreach ($regiongeo_array as $key => $value)
                {
                    $htmlcode .= "<option value='{$key}'>" . $value['nom_region'] . "</option>";
                }
                $htmlcode .= "</select>";
            $htmlcode .= "</td>";
            $htmlcode .= "<td>&nbsp;</td>";
        $htmlcode .= "</tr>";
        $htmlcode .= "<tr><td colspan='3' class='lignevide'>&nbsp;</td></tr>";

        if (!empty($commune_array))
        {
            foreach ($commune_array as $id => $data)
            {
                $row_l = (fmod($row, 2) == 0)
                    ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                    : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                $row++;

                $htmlcode .= "<tr {$row_l} id='row_c_{$id}'>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:250px;'"
                                   . " name='commune_nom_{$id}'"
                                   . " value='" . $data['nom_commune'] . "'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<select name='select_commune_regiongeo_{$id}'"
                                   . " id='select_commune_regiongeo_{$id}' style='width:250px;'>";
                        foreach ($regiongeo_array as $key => $value)
                        {
                            $selected = ($key == $data['id_region_commune']) ? 'selected' : '';
                            $htmlcode .= "<option value='{$key}' {$selected}>" . $value['nom_region'] . "</option>";
                        }
                        $htmlcode .= "</select>";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_commune'])
                    {
                        $target = sprintf(TEXT_GEO_VERIFDEL_TARGET_COMMUNE, "<b>" . htmlspecialchars($data['nom_commune'], ENT_QUOTES) . "</b>");
                        $target_js = addslashes($target);
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;cursor:pointer;' title='" . TEXT_GEO_BTN_DELETE . "'"
                                   . " onClick=\"confirmDeleteGeo('{$target_js}', function(){ delete_commune('{$id}'); });\">X</a>";
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
$htmlcode .= "</div>";


echo json_encode([
    'tab_geo_commune' => $tab_geo_commune,
    'htmlcode'        => $htmlcode,
    'message_info'    => $message_info,
]);
?>

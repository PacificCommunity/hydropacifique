<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — hydrological region table
Called by affiche_geo_regionhydro_data() in form_geo_regionhydro.php.
Receives territoire_id via JSON POST.
Returns JSON:
  tab_geo_regionhydro : bool   — false if no data found
  htmlcode            : string — full HTML table ready to inject
  message_info        : string — error message when tab_geo_regionhydro is false

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

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$dataInfo      = json_decode(file_get_contents('php://input'), true);
$territoire_id = (int) $dataInfo['territoireId'];

$tab_geo_regionhydro = true;
$message_info        = '';
$regionhydro_array   = [];


// -----------------------------------------------
// Preload station dependencies in a single query (avoids N+1)

$stations_per_rh = [];
$dep_query = tep_db_query($sql_link,
    "SELECT id_regionhydro, COUNT(*) AS nb
     FROM " . TABLE_STATION . "
     WHERE id_regionhydro IS NOT NULL
     GROUP BY id_regionhydro");
while ($d = tep_db_fetch_array($dep_query))
{
    $stations_per_rh[(int)$d['id_regionhydro']] = (int)$d['nb'];
}


// -----------------------------------------------
// Query: hydrological regions for this territoire

$regionhydro_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom, description
     FROM " . TABLE_REGIONHYDRO . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY LOWER(nom) ASC");

while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $id_regionhydro = (int) $regionhydro['id'];

    $del_regionhydro = !isset($stations_per_rh[$id_regionhydro]);

    $regionhydro_array[$id_regionhydro] = [
        'nom_regionhydro'         => $regionhydro['nom'],
        'description_regionhydro' => $regionhydro['description'],
        'del_regionhydro'         => $del_regionhydro,
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
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_TH_INTITULE    . "</th>";
                $htmlcode .= "<th style='width:470px;'>" . TEXT_GEO_TH_DESCRIPTION . "</th>";
                $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
            $htmlcode .= "</tr>";
        $htmlcode .= "</thead>";

        // New entry row
        $htmlcode .= "<tr><td colspan='3' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_GEO_REGIONHYDRO_ADD . "</td></tr>\n";
        $htmlcode .= "<tr>";
            $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='regionhydro_nom_0'></td>";
            $htmlcode .= "<td><input type='text' style='width:450px;border:2px solid #609966;' name='regionhydro_description_0'></td>";
            $htmlcode .= "<td>&nbsp;</td>";
        $htmlcode .= "</tr>";
        $htmlcode .= "<tr><td colspan='3' class='lignevide'>&nbsp;</td></tr>";

        if (!empty($regionhydro_array))
        {
            foreach ($regionhydro_array as $id => $data)
            {
                $row_l = (fmod($row, 2) == 0)
                    ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                    : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                $row++;

                $htmlcode .= "<tr {$row_l} id='row_rh_{$id}'>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:250px;'"
                                   . " name='regionhydro_nom_{$id}'"
                                   . " value='" . $data['nom_regionhydro'] . "'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:450px;'"
                                   . " name='regionhydro_description_{$id}'"
                                   . " value='" . $data['description_regionhydro'] . "'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_regionhydro'])
                    {
                        $target = sprintf(TEXT_GEO_VERIFDEL_TARGET_REGIONHYDRO, "<b>" . htmlspecialchars($data['nom_regionhydro'], ENT_QUOTES) . "</b>");
                        $target_js = addslashes($target);
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;cursor:pointer;' title='" . TEXT_GEO_BTN_DELETE . "'"
                                   . " onClick=\"confirmDeleteGeo('{$target_js}', function(){ delete_regionhydro('{$id}'); });\">X</a>";
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
    'tab_geo_regionhydro' => $tab_geo_regionhydro,
    'htmlcode'            => $htmlcode,
    'message_info'        => $message_info,
]);
?>

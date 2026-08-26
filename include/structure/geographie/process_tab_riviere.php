<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — river table
Called by affiche_geo_riviere_data() in form_geo_riviere.php.
Receives territoire_id via JSON POST.
Returns JSON:
  tab_geo_riviere : bool   — false if no data found
  htmlcode        : string — full HTML table ready to inject
  message_info    : string — error message when tab_geo_riviere is false

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

$tab_geo_riviere   = true;
$message_info      = '';
$riviere_array     = [];
$regionhydro_array = [];


// -----------------------------------------------
// Query: hydrological regions — populates the dropdown in each river row

$regionhydro_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom
     FROM " . TABLE_REGIONHYDRO . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY LOWER(nom) ASC");

while ($regionhydro = tep_db_fetch_array($regionhydro_query))
{
    $regionhydro_array[$regionhydro['id']] = ['nom_regionhydro' => $regionhydro['nom']];
}


// -----------------------------------------------
// Preload station dependencies in a single query (avoids N+1)

$stations_per_riv = [];
$dep_query = tep_db_query($sql_link,
    "SELECT id_riviere, COUNT(*) AS nb
     FROM " . TABLE_STATION . "
     WHERE id_riviere IS NOT NULL
     GROUP BY id_riviere");
while ($d = tep_db_fetch_array($dep_query))
{
    $stations_per_riv[(int)$d['id_riviere']] = (int)$d['nb'];
}


// -----------------------------------------------
// Query: rivers for this territoire

$riviere_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom, description, id_regionhydro
     FROM " . TABLE_RIVIERE . "
     WHERE id_territoire = " . $territoire_id . "
     ORDER BY LOWER(nom) ASC");

while ($riviere_tab = tep_db_fetch_array($riviere_query))
{
    $id_riviere = (int) $riviere_tab['id'];

    $del_riviere = !isset($stations_per_riv[$id_riviere]);

    $riviere_array[$id_riviere] = [
        'nom_riviere'            => $riviere_tab['nom'],
        'description_riviere'    => $riviere_tab['description'],
        'id_regionhydro_riviere' => $riviere_tab['id_regionhydro'],
        'del_riviere'            => $del_riviere,
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
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_RIVIERE_TH_NOM    . "</th>";
                $htmlcode .= "<th style='width:370px;'>" . TEXT_GEO_TH_DESCRIPTION    . "</th>";
                $htmlcode .= "<th style='width:270px;'>" . TEXT_GEO_RIVIERE_TH_REGION . "</th>";
                $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
            $htmlcode .= "</tr>";
        $htmlcode .= "</thead>";

        // New entry row — green border signals the creation inputs
        $htmlcode .= "<tr><td colspan='4' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_GEO_RIVIERE_ADD . "</td></tr>\n";
        $htmlcode .= "<tr>";
            $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='riviere_nom_0'></td>";
            $htmlcode .= "<td><input type='text' style='width:350px;border:2px solid #609966;' name='riviere_description_0'></td>";
            $htmlcode .= "<td>";
                $htmlcode .= "<select name='select_riviere_regionhydro_0' id='select_riviere_regionhydro_0'"
                           . " style='width:250px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='0'></option>";
                foreach ($regionhydro_array as $key => $value)
                {
                    $htmlcode .= "<option value='{$key}'>" . $value['nom_regionhydro'] . "</option>";
                }
                $htmlcode .= "</select>";
            $htmlcode .= "</td>";
            $htmlcode .= "<td>&nbsp;</td>";
        $htmlcode .= "</tr>";
        $htmlcode .= "<tr><td colspan='4' class='lignevide'>&nbsp;</td></tr>";

        if (!empty($riviere_array))
        {
            foreach ($riviere_array as $id => $data)
            {
                $row_l = (fmod($row, 2) == 0)
                    ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                    : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                $row++;

                $htmlcode .= "<tr {$row_l} id='row_r_{$id}'>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:250px;'"
                                   . " name='riviere_nom_{$id}'"
                                   . " value='" . $data['nom_riviere'] . "'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:350px;'"
                                   . " name='riviere_description_{$id}'"
                                   . " value='" . $data['description_riviere'] . "'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<select name='select_riviere_regionhydro_{$id}'"
                                   . " id='select_riviere_regionhydro_{$id}' style='width:250px;'>";
                        $htmlcode .= "<option value='0'></option>";
                        foreach ($regionhydro_array as $key => $value)
                        {
                            $selected = ($key == $data['id_regionhydro_riviere']) ? 'selected' : '';
                            $htmlcode .= "<option value='{$key}' {$selected}>" . $value['nom_regionhydro'] . "</option>";
                        }
                        $htmlcode .= "</select>";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_riviere'])
                    {
                        $target = sprintf(TEXT_GEO_VERIFDEL_TARGET_RIVIERE, "<b>" . htmlspecialchars($data['nom_riviere'], ENT_QUOTES) . "</b>");
                        $target_js = addslashes($target);
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;cursor:pointer;' title='" . TEXT_GEO_BTN_DELETE . "'"
                                   . " onClick=\"confirmDeleteGeo('{$target_js}', function(){ delete_riviere('{$id}'); });\">X</a>";
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
    'tab_geo_riviere' => $tab_geo_riviere,
    'htmlcode'        => $htmlcode,
    'message_info'    => $message_info,
]);
?>

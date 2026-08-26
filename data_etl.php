<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
ETL station list — displays all hydrometric stations (station_type=11)
with their gauging and rating-curve counts. Provides links to the ETL
editor (modif_etl.php) and the H→Q converter (convert_hq.php).

Filter: show all stations or only those with at least one ETL.
Sort: station name or code, ascending or descending.
----------------------------------------
*/

require('include/application_top.php');

$row = 0;

// -----------------------------------------------
// ETL-existence filter (default: stations with ETL only)

$etl_exist    = 2;
$having_nbetl = " HAVING nb_etl > 0";
if (isset($_POST['etl_exist']))
{
    $etl_exist = $_POST['etl_exist'];
    if ($etl_exist == 1) { $having_nbetl = ""; }
}

// -----------------------------------------------
// Sort field and order

$tri_encours = 1;
$tri         = "s.nom_station";
if (isset($_POST['select_tri']))
{
    $tri_encours = $_POST['select_tri'];
    if ($tri_encours == 2) { $tri = "s.code_station"; }
}

$tri_order_encours = 1;
$tri_order         = " ASC,";
if (isset($_POST['order_tri']))
{
    $tri_order_encours = $_POST['order_tri'];
    if ($tri_order_encours == 2) { $tri_order = " DESC,"; }
}

// -----------------------------------------------
// Station filter (injected by filtre_stations_var.php)

$affiche_select_from          = true;
$affiche_select_type          = false;
$affiche_select_tournee       = false;
$affiche_search               = true;
$affiche_select_riviere       = false;
$affiche_select_station       = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// -----------------------------------------------
// Station list query (hydrometric stations only)

$station_array     = [];
$nb_station        = 0;
$nb_station_active = 0;
$nb_station_suivi  = 0;
$nb_station_armee  = 0;

$where_and_type = " AND s.station_type=11";

$station_query = tep_db_query($sql_link,
    "SELECT s.id_station, s.id_commune, s.nom_station, s.code_station,
            s.active_station, s.suivi, s.armee,
            COUNT(etl.id) AS nb_etl
     FROM "   . TABLE_STATION           . " s
     LEFT JOIN " . TABLE_COMMUNE         . " c   ON s.id_commune   = c.id_commune
     LEFT JOIN " . TABLE_DATA_ETL        . " etl ON etl.id_station = s.id_station
     LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station  = s.id_station
     WHERE s.id_territoire=" . $territoire_id  . $where_and_from
    . $where_and_type
    . $where_search . $where_and_regionhydro . $where_and_region . $where_and_commune
    . $where_and_riviere . $where_and_tournee
    . $where_and_active . $where_and_suivi . $where_and_armee
    . " GROUP BY s.id_station
       " . $having_nbetl . "
       ORDER BY " . $tri . $tri_order . " s.active_station ASC, s.suivi DESC, s.armee ASC");

while ($station = tep_db_fetch_array($station_query))
{
    $nom_station  = html_entity_decode($station['nom_station']  ?? '');
    $code_station = html_entity_decode($station['code_station'] ?? '');
    $nb_etl       = $station['nb_etl'];

    $active_station = 0;
    if ($station['active_station'] == 1) { $active_station = 1; $nb_station_active++; }

    $suivi_station = 0;
    if ($station['suivi'] == 1)          { $suivi_station = 1;  $nb_station_suivi++; }

    $armee_station = 0;
    if ($station['armee'] == 1)          { $armee_station = 1;  $nb_station_armee++; }

    $station_array[$station['id_station']] = [
        'active_station' => $active_station,
        'suivi_station'  => $suivi_station,
        'armee_station'  => $armee_station,
        'nom_station'    => $nom_station,
        'code_station'   => $code_station,
        'nb_etl'         => $nb_etl,
    ];
}
$nb_stations = count($station_array);


// -----------------------------------------------
// JGE point count per station (valid numeric hmoy + q, < 9999)

$nb_jge_array = [];
$jge_query    = tep_db_query($sql_link,
    "SELECT COUNT(*) as nb_jge, s.id_station
     FROM " . TABLE_DATA_JGE . " jge
     LEFT JOIN " . TABLE_STATION           . " s ON jge.id_station = s.id_station
     LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station  = s.id_station
     WHERE 1=1
     AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\.[0-9]+)?$'
     AND jge.depouil_hmoy < 9999
     AND jge.depouil_q   REGEXP '^-?[0-9]+(\.[0-9]+)?$'"
    . $where_and_type
    . $where_search . $where_and_regionhydro . $where_and_region . $where_and_commune
    . $where_and_riviere . $where_and_tournee
    . $where_and_active . $where_and_suivi . $where_and_armee
    . " GROUP BY jge.id_station");
while ($nb_jge_tab = tep_db_fetch_array($jge_query))
{
    $nb_jge_array[$nb_jge_tab['id_station']] = ['nb_jge' => $nb_jge_tab['nb_jge']];
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";

    echo "<div id='contenu_centre'>";
        echo "<div id='contenu_box2'>";

            echo "<h1><span>" . TEXT_ET_LIST_PAGE_TITLE . "</span></h1>";

            $lien_form = tep_href_link('data_etl.php');
            $name_form = 'form_select_etl';
            echo "<form name='" . $name_form . "' id='" . $name_form . "'"
               . " action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";

                echo "<div id='cadre_graph' style='float:left;width:272px;max-height:80vh;overflow-y: auto;'>\n";

                    echo "<div id='boxpopup' class='select-top' style='width:235px;padding:5px 3%;margin-bottom:10px;'>\n";

                        // ---- ETL-existence filter ----
                        echo "<p style='float:left;padding-top:5px;color:#186F65;margin-top:10px;'>"
                           . TEXT_ET_FILTER_CURVES . "</p>";

                        echo "<select name='etl_exist' id='etl_exist'"
                           . " onchange='" . $name_form . ".submit();' style='float:right;width:130px;margin-top:10px;'>";
                            $sel = ($etl_exist == 1) ? 'selected' : '';
                            echo "<option value='1' " . $sel . ">" . TEXT_ET_FILTER_ALL_ST . "</option>";
                            $sel = ($etl_exist == 2) ? 'selected' : '';
                            echo "<option value='2' " . $sel . ">" . TEXT_ET_FILTER_ETL_ST . "</option>";
                        echo "</select>";

                        echo "<hr>";

                        require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                        echo "<hr>";

                        // ---- Sort field ----
                        echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";
                        echo "<p style='float:left;width:60px;padding-top:5px;color:#186F65;margin-top:15px;'>"
                           . TEXT_ET_SORT_LABEL . "</p>";

                        echo "<select name='select_tri' id='select_tri'"
                           . " onchange='" . $name_form . ".submit();' style='float:right;width:140px;margin-top:15px;'>";
                            $sel = ($tri_encours == 1) ? 'selected' : '';
                            echo "<option value='1' " . $sel . ">" . TEXT_ET_SORT_NAME . "</option>";
                            $sel = ($tri_encours == 2) ? 'selected' : '';
                            echo "<option value='2' " . $sel . ">" . TEXT_ET_SORT_CODE . "</option>";
                        echo "</select>";

                        echo "<hr>";

                        // ---- Sort order ----
                        echo "<div style='float:right;'>";
                            $asc_checked  = ($tri_order_encours == 1) ? 'checked' : '';
                            $desc_checked = ($tri_order_encours == 2) ? 'checked' : '';

                            echo "<p style='float:left;width:55px;padding-top:3px;'>" . TEXT_ET_ORDER_ASC  . "</p>";
                            echo "<input type='radio' id='asc'  name='order_tri' value='1' style='float:left;'"
                               . " " . $asc_checked  . " onchange='" . $name_form . ".submit();'>";
                            echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>" . TEXT_ET_ORDER_DESC . "</p>";
                            echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;'"
                               . " " . $desc_checked . " onchange='" . $name_form . ".submit();'>";
                        echo "</div>";

                        // ---- Station count ----
                        echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                            echo "<p><span>" . TEXT_ET_NB_STATIONS
                               . number_format($nb_stations, 0, '.', ' ') . "</span></p>";
                        echo "</div>";

                    echo "</div>";

                echo "</div>"; // cadre_graph

            echo "</form>";


            // -----------------------------------------------
            // Station table

            if (!empty($station_array) && $nb_stations > 0)
            {
                echo "<div class='table-container' style='float:none;width:auto;height:80vh;'>";
                    echo "<div style='width:auto;height:78vh;overflow-y: auto;'>";
                        echo "<table id='table_tri' cellspacing='0'>";

                            echo "<thead>";
                                echo "<tr class='header-row'>";
                                    echo "<th style='width:60px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_STATUS_TITLE . "'>" . TEXT_ET_COL_STATUS  . "</th>";
                                    echo "<th style='width:60px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_SUIVI_TITLE  . "'>" . TEXT_ET_COL_SUIVI   . "</th>";
                                    echo "<th style='width:400px;padding-left:15px;'>" . TEXT_ET_COL_STATION . "</th>";
                                    echo "<th style='width:80px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_NB_JGE_TITLE . "'>" . TEXT_ET_COL_NB_JGE  . "</th>";
                                    echo "<th style='width:80px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_NB_ETL_TITLE . "'>" . TEXT_ET_COL_NB_ETL  . "</th>";
                                    echo "<th style='width:80px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_CURVE_TITLE  . "'>" . TEXT_ETL  . "</th>";
                                    echo "<th style='width:80px;text-align:center;'"
                                       . " title='" . TEXT_ET_COL_HQ_TITLE     . "'>" . TEXT_ET_COL_HQ . "</th>";
                                echo "</tr>";
                            echo "</thead>";

                            echo "<tr><td colspan='6' style='height:10px;'>&nbsp;</td></tr>";

                            foreach ($station_array as $key => $value)
                            {
                                $nb_jge = isset($nb_jge_array[$key]) ? $nb_jge_array[$key]['nb_jge'] : 0;
                                $nb_etl = $value['nb_etl'];

                                if (fmod($row, 2) == 0)
                                { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                                else
                                { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

                                echo "<tr " . $row_l . ">";

                                    // Status icon
                                    echo "<td style='text-align:center;'>";
                                    if ($value['active_station'] === 1)
                                    { echo "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_ET_STATUS_ACTIVE . "'>"; }
                                    else
                                    { echo "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;' title='" . TEXT_ET_STATUS_CLOSED . "'>"; }
                                    echo "</td>\n";

                                    // Monitoring icon
                                    echo "<td style='text-align:center;'>";
                                    if ($value['suivi_station'] === 1)
                                    { echo "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_ET_SUIVI_CONTINU  . "'>"; }
                                    else
                                    { echo "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;' title='" . TEXT_ET_SUIVI_PONCTUEL . "'>"; }
                                    echo "</td>\n";

                                    // Station name/code link
                                    $lien_etl = "modif_etl.php?st=" . $key;
                                    echo "<td style='padding-left:15px;'>";
                                        echo "<a href='" . $lien_etl . "' target='_blank'"
                                           . " title='" . $value['nom_station'] . "'>";
                                            echo $value['code_station'] . " - " . affichelettres($value['nom_station'], 50);
                                        echo "</a>\n";
                                    echo "</td>\n";

                                    echo "<td style='text-align:center;'>" . $nb_jge . "</td>\n";
                                    echo "<td style='text-align:center;'>" . $nb_etl . "</td>\n";

                                    // ETL editor link
                                    echo "<td style='text-align:center;cursor:pointer;'>";
                                    if ($nb_jge > 0 || $nb_etl > 0)
                                    {
                                        echo "<a href='" . $lien_etl . "' target='_blank'"
                                           . " title='" . TEXT_ET_LINK_ETL_TITLE . "'>";
                                            echo "<img src='" . DIR_WS_IMG_ICO . "reg.png' style='width:30px;'>";
                                        echo "</a>";
                                    }
                                    echo "</td>\n";

                                    // H→Q converter link
                                    $lien_hq = "convert_hq.php?st=" . $key;
                                    echo "<td style='text-align:center;cursor:pointer;'>";
                                    if ($nb_etl > 0)
                                    {
                                        echo "<a href='" . $lien_hq . "' target='_blank'"
                                           . " title='" . TEXT_ET_LINK_HQ_TITLE . "'>";
                                            echo "<img src='" . DIR_WS_IMG_ICO . "hq.png' style='width:30px;'>";
                                        echo "</a>";
                                    }
                                    else { echo '-'; }
                                    echo "</td>\n";

                                echo "</tr>\n";
                                $row++;
                            }

                        echo "</table>";
                    echo "</div>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup'>\n";
                    echo "<p class='alert'>" . TEXT_ET_NO_STATION . "</p>";
                echo "</div>";
            }

        echo "<hr>";
        echo "</div>"; // contenu_box2

    echo "<hr>";
    echo "</div>"; // contenu_centre

echo "<hr>";
echo "</div>"; // contour_general

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>

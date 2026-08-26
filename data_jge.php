<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging list page — sorted from most recent, with filters to assist selection
- Quick gauging save is handled via AJAX (see block_jge_simple.php)
----------------------------------------
*/

require('include/application_top.php');

// -----------------------------------------------
// Initialize variables

$row          = 0;
$date_format  = 'd-m-Y';
$heure_format = 'H:i:s';

$where_and_type = " AND s.station_type = 11"; // Hydrometric stations only (gauging)

$select_periode_encours = 60;
$where_and_periode      = " AND jge.datetime >= CURDATE() - INTERVAL " . $select_periode_encours . " MONTH";


// -----------------------------------------------
// Sort field

$tri_encours = 3;
$tri         = "jge.datetime";
if (isset($_POST['select_tri']))
{
    $tri_encours = $_POST['select_tri'];
    if ($_POST['select_tri'] == 1) { $tri = "s.nom_station";  } // sort by station name
    if ($_POST['select_tri'] == 2) { $tri = "s.code_station"; } // sort by station code
    if ($_POST['select_tri'] == 3) { $tri = "jge.datetime";   } // sort by date
}

$tri_order_encours = 2;
$tri_order         = " DESC,";
if (isset($_POST['order_tri']))
{
    $tri_order_encours = $_POST['order_tri'];
    if ($_POST['order_tri'] == 1) { $tri_order = " ASC,";  } // ascending
    if ($_POST['order_tri'] == 2) { $tri_order = " DESC,"; } // descending
}


// -----------------------------------------------
// Filter panel visibility flags

$affiche_select_from           = true;
$affiche_select_type           = false;
$affiche_select_tournee        = false;
$affiche_search                = true;
$affiche_select_riviere        = false;
$affiche_select_station        = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// -----------------------------------------------
// Form input: station search

if (isset($_POST['search_station']) || isset($_GET['search_station']))
{
    if (isset($_POST['search_station'])) { $search_station = post_secure($sql_link, $_POST['search_station']); }
    if (isset($_GET['search_station']))  { $search_station = post_secure($sql_link, $_GET['search_station']);  }
    $where_search = search_station($search_station, '');
}

// Form input: period filter
if (isset($_POST['select_periode']))
{
    $select_periode_encours = $_POST['select_periode'];
    $where_and_periode      = " AND datetime >= CURDATE() - INTERVAL " . $select_periode_encours . " MONTH";
    if ($select_periode_encours == 0) { $where_and_periode = ''; }
}


// -----------------------------------------------
// Query: Gauging type lookup table

$jge_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_TYPE);
while ($jge_type = tep_db_fetch_array($jge_type_query))
{
    $jge_type_array[$jge_type['id']] = html_entity_decode($jge_type['titre'] ?? '');
}

// Query: Gauging method lookup table
$jge_methode_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_METHODE);
while ($jge_methode = tep_db_fetch_array($jge_methode_query))
{
    $jge_methode_array[$jge_methode['id']] = html_entity_decode($jge_methode['titre'] ?? '');
}

// Query: Quality codes
$code_qual_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
     FROM " . TABLE_DATA_QUALITE . "
     WHERE (id_eq_type = 0 OR id_eq_type = 11)
     ORDER BY id_eq_type DESC, init_qualite_data");
while ($code_qual = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$code_qual['id_data_qualite']] = [
        'init_qualite_data' => html_entity_decode($code_qual['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($code_qual['nom_qualite_data']  ?? ''),
    ];
}


// -----------------------------------------------
// Quick gauging save and JGE deletion are now handled via AJAX
// (see block_jge_simple.php and block_verifdel_jge.php)


// -----------------------------------------------
// Query: Gauging list

$nb_jge = 0;

$sql_jge = "SELECT DISTINCT jge.id, jge.from_nomad, jge.new_nomad, jge.hp_load,
                            jge.id_station, s.code_station, s.nom_station, jge.datetime,
                            jge.nb_bras,
                            jge.id_methode, jge.id_typejge, jge.depouil_hmoy, jge.depouil_q, jge.obs,
                            jge.code_qualite,
                            s.active_station, s.suivi, s.armee
            FROM " . TABLE_DATA_JGE . " jge
            LEFT JOIN " . TABLE_STATION            . " s ON jge.id_station = s.id_station
            LEFT JOIN " . TABLE_REGION             . " r ON s.id_region    = r.id_region
            LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station   = s.id_station
            WHERE r.id_territoire = " . $territoire_id . $where_and_from .
            $where_search . $where_and_regionhydro . $where_and_region . $where_and_commune .
            $where_and_riviere . $where_and_type . $where_and_tournee . $where_and_station .
            $where_and_active . $where_and_suivi . $where_and_armee .
            $where_and_station .
            $where_and_periode .
            " ORDER BY " . $tri . $tri_order . " s.active_station DESC, s.suivi DESC, s.armee ASC";

$jge_query = tep_db_query($sql_link, $sql_jge);
while ($jge = tep_db_fetch_array($jge_query))
{
    $id         = $jge['id'];
    $from_nomad = $jge['from_nomad'];
    $new_nomad  = $jge['new_nomad'];
    $hp_load    = $jge['hp_load'];

    $id_station   = html_entity_decode($jge['id_station']   ?? '');
    $code_station = html_entity_decode($jge['code_station'] ?? '');
    $nom_station  = html_entity_decode($jge['nom_station']  ?? '');
    $obs          = html_entity_decode($jge['obs']          ?? '');
    $code_qualite = html_entity_decode($jge['code_qualite'] ?? '');

    $active_station = ($jge['active_station'] == 1) ? 1 : 0;
    $suivi_station  = ($jge['suivi']          == 1) ? 1 : 0;
    $armee_station  = ($jge['armee']          == 1) ? 1 : 0;

    $tab_date_heure_jge = explode(' ', $jge['datetime']);
    $date_jge           = dateus_fr($tab_date_heure_jge[0]);
    $heure_jge          = $tab_date_heure_jge[1];
    $date_heure_jge     = $date_jge . ' ' . $heure_jge;

    $nb_bras    = html_entity_decode($jge['nb_bras']     ?? '');
    $id_methode = html_entity_decode($jge['id_methode']  ?? '');
    $id_typejge = html_entity_decode($jge['id_typejge']  ?? '');

    $jge_methode_titre = isset($jge_methode_array[$id_methode]) ? $jge_methode_array[$id_methode] : '';
    $jge_type_titre    = isset($jge_type_array[$id_typejge])    ? $jge_type_array[$id_typejge]    : '';

    // Count arms from the bras table (more reliable than the nb_bras field)
    $jge_bras_query = tep_db_query($sql_link,
        "SELECT COUNT(*) AS nb_bras_bytab FROM " . TABLE_DATA_JGE_BRAS . " WHERE id_jge = " . $id);
    $jge_bras      = tep_db_fetch_array($jge_bras_query);
    $nb_bras_bytab = isset($jge_bras['nb_bras_bytab']) ? html_entity_decode($jge_bras['nb_bras_bytab'] ?? '') : 0;

    $depouil_hmoy = round(floatval($jge['depouil_hmoy']), 3);
    $depouil_q    = round(floatval($jge['depouil_q']),    3);

    if ($depouil_hmoy == '9999') { $depouil_hmoy = 'Err'; } // Flag gap/error value

    $jge_array[$id] = [
        'id_station'        => $id_station,
        'code_station'      => $code_station,
        'nom_station'       => $nom_station,
        'from_nomad'        => $from_nomad,
        'new_nomad'         => $new_nomad,
        'hp_load'           => $hp_load,
        'active_station'    => $active_station,
        'suivi_station'     => $suivi_station,
        'armee_station'     => $armee_station,
        'jge_methode_titre' => $jge_methode_titre,
        'jge_type_titre'    => $jge_type_titre,
        'date_heure'        => $date_heure_jge,
        'date'              => $date_jge,
        'heure'             => $heure_jge,
        'nb_bras'           => $nb_bras,
        'nb_bras_bytab'     => $nb_bras_bytab,
        'depouil_hmoy'      => $depouil_hmoy,
        'depouil_q'         => $depouil_q,
        'obs'               => $obs,
        'code_qualite'      => $code_qualite,
    ];
}
if (isset($jge_array)) { $nb_jge = sizeof($jge_array); }


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    echo "<div id='contenu_info' style='display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);min-width:400px;max-width:80%;padding:12px 20px;background:#fff;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:3000;'></div>";

    require(DIR_WS_JAUGEAGE  . 'block_jge_simple.php');    // Quick gauging entry popup (AJAX)
    require(DIR_WS_JAUGEAGE  . 'block_verifdel_jge.php');  // Gauging deletion confirmation
    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";

            echo "<h1><span>" . TEXT_JGE_LIST_TITLE . "</span></h1>";

            // ---- Left sidebar ----
            echo "<div style='float:left;width:272px;height:calc(100% - 115px);'>\n";

                // New gauging button (opens in a new tab)
                echo "<div style='text-align:center;margin-bottom:10px;'>";
                    echo "<a href='modif_jge.php' target='_blank' rel='noopener noreferrer'"
                       . " id='button_titre'"
                       . " style='display:inline-block;box-sizing:border-box;padding:6px 30px;text-decoration:none;'>";
                        echo TEXT_JGE_LIST_NEW_BTN;
                    echo "</a>";
                echo "</div>";

                // Filter form
                echo "<div id='boxpopup' class='select-top' style='width:230px;max-height:calc(100% - 20px);overflow-y:auto;margin:0;margin-bottom:5px;padding:0 8px;padding-top:10px;'>\n";

                    $lien_form = tep_href_link('data_jge.php');
                    $name_form = 'form_select_jge';
                    echo "<form name='" . $name_form . "' action='" . $lien_form . "' method='post' enctype='multipart/form-data'>";


                        echo "<div style='width:220px;'>";

                            // Period filter
                            echo "<p style='float:left;width:60px;margin-top:5px;padding-top:5px;color:#609966;'>" . TEXT_JGE_LIST_PERIODE . "</p>";
                            echo "<select name='select_periode' id='select_periode' onchange='" . $name_form . ".submit();' style='float:right;width:120px;margin-top:5px;'>";
                                $opts = [
                                    1   => TEXT_JGE_LIST_PERIODE_1M,
                                    3   => TEXT_JGE_LIST_PERIODE_3M,
                                    6   => TEXT_JGE_LIST_PERIODE_6M,
                                    12  => TEXT_JGE_LIST_PERIODE_1Y,
                                    24  => TEXT_JGE_LIST_PERIODE_2Y,
                                    60  => TEXT_JGE_LIST_PERIODE_5Y,
                                    120 => TEXT_JGE_LIST_PERIODE_10Y,
                                    0   => TEXT_JGE_LIST_PERIODE_ALL,
                                ];
                                foreach ($opts as $val => $label)
                                {
                                    $selected = ($select_periode_encours == $val) ? 'selected' : '';
                                    echo "<option value='" . $val . "' " . $selected . ">" . $label . "</option>";
                                }
                            echo "</select>";

                            echo "<hr>";
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:0;'></div>";
                            echo "<hr>";

                            require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                            echo "<hr>";

                            // Sort options
                            echo "<div style='width:100%;border-bottom:2px solid #176B87;margin-top:5px;'></div>";
                            echo "<p style='float:left;width:60px;padding-top:5px;color:#186F65;margin-top:15px;'>" . TEXT_JGE_LIST_SORT_BY . "</p>";
                            echo "<select name='select_tri' id='select_tri' onchange='" . $name_form . ".submit();' style='float:right;width:140px;margin-top:15px;'>";
                                $selected = ($tri_encours == 1) ? 'selected' : '';
                                echo "<option value='1' " . $selected . ">" . TEXT_JGE_LIST_SORT_NAME . "</option>";
                                $selected = ($tri_encours == 2) ? 'selected' : '';
                                echo "<option value='2' " . $selected . ">" . TEXT_JGE_LIST_SORT_CODE . "</option>";
                                $selected = ($tri_encours == 3) ? 'selected' : '';
                                echo "<option value='3' " . $selected . ">" . TEXT_JGE_LIST_SORT_DATE . "</option>";
                            echo "</select>";

                            echo "<hr>";

                            // Sort order
                            echo "<div style='float:right;'>";
                                $asc_checked  = ($tri_order_encours == 1) ? 'checked' : '';
                                $desc_checked = ($tri_order_encours == 2) ? 'checked' : '';
                                echo "<p style='float:left;width:55px;padding-top:3px;'>" . TEXT_JGE_LIST_ASC . "</p>";
                                echo "<input type='radio' id='asc'  name='order_tri' value='1' style='float:left;' " . $asc_checked  . " onchange='" . $name_form . ".submit();'>";
                                echo "<p style='float:left;width:65px;margin-left:10px;padding-top:3px;'>" . TEXT_JGE_LIST_DESC . "</p>";
                                echo "<input type='radio' id='desc' name='order_tri' value='2' style='float:left;' " . $desc_checked . " onchange='" . $name_form . ".submit();'>";
                            echo "</div>";

                            // Record count
                            echo "<div id='contenu_infos' style='width:97%;margin-top:10px;'>";
                                echo "<p><span style='margin:0;'>" . TEXT_JGE_LIST_COUNT . "<span id='jge_count_value'>" . number_format($nb_jge, 0, '.', ' ') . "</span></span></p>";
                            echo "</div>";

                            echo "<hr>";

                        echo "</div>";

                    echo "</form>";

                echo "</div>";

            echo "</div>";


            // -----------------------------------------------
            // Gauging list table

            if (isset($jge_array) && ($nb_jge > 0))
            {
                echo "<div style='float:none;height:calc(100% - 100px);overflow-y:auto;'>";
                    echo "<table id='table_tri' cellspacing='0'>";

                        echo "<thead>";
                            echo "<tr class='header-row'>";
                                echo "<th style='width:100px;padding-left:5px;'>" . TEXT_JGE_LIST_TH_TYPE     . "</th>";
                                echo "<th style='width:110px;'>"                  . TEXT_JGE_LIST_TH_CODE     . "</th>";
                                echo "<th style='width:300px;'>"                  . TEXT_JGE_LIST_TH_STATION  . "</th>";
                                echo "<th style='width:70px;'>"                   . TEXT_JGE_LIST_TH_DATE     . "</th>";
                                echo "<th style='width:70px;'>"                   . TEXT_JGE_LIST_TH_HEURE    . "</th>";
                                echo "<th style='width:70px;text-align:center;'>" . TEXT_JGE_LIST_TH_BRAS     . "</th>";
                                echo "<th style='width:90px;text-align:center;'>" . TEXT_JGE_LIST_TH_Q        . "</th>";
                                echo "<th style='width:90px;text-align:center;'>" . TEXT_JGE_LIST_TH_H        . "</th>";
                                echo "<th style='width:50px;'>&nbsp;</th>";
                                echo "<th style='width:50px;'>&nbsp;</th>";
                                echo "<th style='width:50px;'>&nbsp;</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tr><td colspan='11' style='height:10px;'>&nbsp;</td></tr>";

                        // Row-striping counter. Re-initialized here: the init at the
                        // top of the file gets overwritten by an included file whose
                        // fetch loop (while ($row = tep_db_fetch_array(...))) leaves
                        // $row = null, which triggers a PHP 8 deprecation in fmod().
                        $row = 0;

                        foreach ($jge_array as $key => $value)
                        {
                            // Hidden fields for JS access
                            // All values are passed through htmlspecialchars (ENT_QUOTES) so that
                            // observation/station text containing quotes (') or chevrons (< >) cannot
                            // break out of the value='...' attribute and leak into the page.
                            echo "<input type='hidden' id='id_jge_"       . $key . "' value='" . htmlspecialchars($key,                   ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='id_station_"   . $key . "' value='" . htmlspecialchars($value['id_station'],   ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='code_station_" . $key . "' value='" . htmlspecialchars($value['code_station'], ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='nom_station_"  . $key . "' value='" . htmlspecialchars($value['nom_station'],  ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='date_"         . $key . "' value='" . htmlspecialchars($value['date'],         ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='heure_"        . $key . "' value='" . htmlspecialchars($value['heure'],        ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='debit_"        . $key . "' value='" . htmlspecialchars($value['depouil_q'],    ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='hauteur_"      . $key . "' value='" . htmlspecialchars($value['depouil_hmoy'], ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='code_qualite_" . $key . "' value='" . htmlspecialchars($value['code_qualite'], ENT_QUOTES, 'UTF-8') . "'>\n";
                            echo "<input type='hidden' id='obs_"          . $key . "' value='" . htmlspecialchars($value['obs'],          ENT_QUOTES, 'UTF-8') . "'>\n";

                            $row_l = (fmod($row, 2) == 0)
                                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";

                            echo "<tr " . $row_l . ">";

                                echo "<td style='padding-left:10px;'>"  . $value['jge_type_titre']                          . "</td>\n";
                                echo "<td>"                              . $value['code_station']                            . "</td>\n";
                                echo "<td style='width:250px;' title=\"" . htmlspecialchars($value['nom_station'], ENT_QUOTES, 'UTF-8') . "\">"
                                     . affichelettres($value['nom_station'], 50)                                             . "</td>\n";
                                echo "<td>"                              . $value['date']                                    . "</td>\n";
                                echo "<td>"                              . $value['heure']                                   . "</td>\n";
                                echo "<td style='text-align:center;'>"  . $value['nb_bras']                                 . "</td>\n";
                                echo "<td style='text-align:center;'>"  . $value['depouil_q']                               . "</td>\n";
                                echo "<td style='text-align:center;'>"  . $value['depouil_hmoy']                            . "</td>\n";

                                if (HP_VERSION == 'Serveur' || ($value['from_nomad'] > 0 && $value['hp_load'] < 1))
                                {
                                    // Edit quick values
                                    echo "<td style='text-align:center;'>";
                                        echo "<img src='" . DIR_WS_IMG_ICO . "edit.png' style='width:20px;cursor:pointer;'
                                                  title='" . TEXT_JGE_LIST_EDIT_TITLE . "'
                                                  onClick='affiche_JGE(" . $key . ");'>";
                                    echo "</td>\n";

                                    // Edit full gauging
                                    echo "<td style='text-align:center;'>";
                                        echo "<a href='modif_jge.php?ref=" . $key . "' style='display:inline-block;'>";
                                            echo "<img src='" . DIR_WS_IMG_ICO . "jge.png' style='width:20px;cursor:pointer;'
                                                      title='" . TEXT_JGE_LIST_EDIT_FULL_TITLE . "'>";
                                        echo "</a>";
                                    echo "</td>\n";

                                    // Delete
                                    echo "<td style='text-align:center;'>";
                                        echo "<a style='font-size:12px;font-weight:bold;cursor:pointer;'"
                                           . " title='" . TEXT_JGE_LIST_DEL_TITLE . "'"
                                           . " onClick='verifDelJGE(" . $key . ");'>X</a>";
                                    echo "</td>\n";
                                }
                                else
                                {
                                    echo "<td style='text-align:center;'>-</td>\n";

                                    echo "<td style='text-align:center;'>";
                                        echo "<a href='modif_jge.php?ref=" . $key . "' style='display:inline-block;'>";
                                            echo "<img src='" . DIR_WS_IMG_ICO . "jge.png' style='width:20px;cursor:pointer;'
                                                      title='" . TEXT_JGE_LIST_EDIT_FULL_TITLE . "'>";
                                        echo "</a>";
                                    echo "</td>\n";

                                    echo "<td style='text-align:center;'>-</td>\n";
                                }

                            echo "</tr>\n";
                            $row++;
                        }

                    echo "</table>";
                echo "</div>";
            }
            else
            {
                echo "<div id='boxpopup' style='margin-left:1%;'>\n";
                    echo "<p class='alert'>" . TEXT_JGE_LIST_NOT_FOUND . "</p>";
                echo "</div>";
            }

        echo "</div>";
    echo "</div>";

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script type="text/javascript">

    var blockValidDel  = document.getElementById('box_del_jge');


    // Open the delete confirmation popup with the JGE id to delete
    // The popup's own script (block_verifdel_jge.php) handles the AJAX call.
    // generateJgeDelChallenge() picks a fresh math challenge for each open.
    function verifDelJGE(id_jge)
    {
        document.getElementById('del_jge_id').value = id_jge;
        blockValidDel.style.display = 'block';
        if (typeof generateJgeDelChallenge === 'function') { generateJgeDelChallenge(); }
    }

    function isValidDate(dateString)
    {
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) { return false; }
        const [day, month, year] = dateString.split("-").map(Number);
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
    }

</script>
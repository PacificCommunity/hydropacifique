<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — graph axis table builder
Called by affiche_axe() in form_typedata_axe.php.
No JSON payload required (no filter — all axes are always shown).
Queries TABLE_DATA_TYPE_AXE for all axis definitions; per row, checks
TABLE_TYPE_DATA to determine whether the axis can be safely deleted
(del_axe flag: false when at least one time-series type references it).
Returns JSON:
  tab_axedata  : bool   — false only when no axes exist
  htmlcode     : string — full <table> HTML injected into #tab_axe
  message_info : string — error message when tab_axedata is false
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

$tab_axedata  = true;
$message_info = '';


// -----------------------------------------------
// Query: all axes with their unit and rounding value
// Per row, check whether any time-series type references this axis

$data_type_axe_array = [];

$data_type_axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe, unite, nb_round
     FROM " . TABLE_DATA_TYPE_AXE . "
     ORDER BY LOWER(axe) ASC");

while ($data_type_axe = tep_db_fetch_array($data_type_axe_query))
{
    // Check if any time-series type references this axis
    $verif_chron_query = tep_db_query($sql_link,
        "SELECT COUNT(*) as nb_axe
         FROM " . TABLE_TYPE_DATA . "
         WHERE axe_data = " . $data_type_axe['id'] . "
         LIMIT 1");
    $verif_chron = tep_db_fetch_array($verif_chron_query);

    $data_type_axe_array[$data_type_axe['id']] = [
        'axe'      => $data_type_axe['axe'],
        'unite'    => $data_type_axe['unite'],
        'nb_round' => $data_type_axe['nb_round'],
        'del_axe'  => ($verif_chron['nb_axe'] < 1),
    ];
}


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<table id='table_tri' cellspacing='0'>";

    // ---- Header row ----
    $htmlcode .= "<thead>";
        $htmlcode .= "<tr class='header-row' style='background-color:#eef3f8;'>";
            $htmlcode .= "<th style='width:200px;'>" . TEXT_TD_AXE_TH_NAME  . "</th>";
            $htmlcode .= "<th style='width:80px;'>"  . TEXT_TD_AXE_TH_UNIT  . "</th>";
            $htmlcode .= "<th style='width:60px;'>"  . TEXT_TD_AXE_TH_ROUND . "</th>";
            $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
        $htmlcode .= "</tr>";
    $htmlcode .= "</thead>";

    // ---- New-entry row ----
    $htmlcode .= "<tr><td colspan='3' style='color:#000;font-size:14px;font-weight:bold;'>"
              .  TEXT_TD_AXE_NEW . "</td></tr>\n";

    $htmlcode .= "<tr>";
        $htmlcode .= "<td><input type='text' name='axe_nom_0'      style='width:180px;border:2px solid #609966;'></td>";
        $htmlcode .= "<td><input type='text' name='axe_unite_0'    style='width:60px;border:2px solid #609966;'></td>";
        $htmlcode .= "<td><input type='text' name='axe_nb_round_0' style='width:40px;border:2px solid #609966;'></td>";
        $htmlcode .= "<td>&nbsp;</td>";
    $htmlcode .= "</tr>";

    $htmlcode .= "<tr><td colspan='3' class='lignevide'>&nbsp;</td></tr>";


    // ---- Existing axis rows ----
    if (!empty($data_type_axe_array))
    {
        foreach ($data_type_axe_array as $id => $data)
        {
            $row_l = (fmod($row, 2) == 0)
                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
            $row++;

            $htmlcode .= "<tr {$row_l} id='row_axe_{$id}'>";

                // Axis name
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' style='width:180px;'"
                              .  " name='axe_nom_{$id}' value='{$data['axe']}'>\n";
                $htmlcode .= "</td>";

                // Unit
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' style='width:60px;'"
                              .  " name='axe_unite_{$id}' value='{$data['unite']}'>\n";
                $htmlcode .= "</td>";

                // Rounding / decimal places
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' style='width:40px;'"
                              .  " name='axe_nb_round_{$id}' value='{$data['nb_round']}'>\n";
                $htmlcode .= "</td>";

                // Delete cell — same visual style as the RA list and the
                // corrections table : a small bold "X" link when deletion
                // is safe, a dash "-" when the row is locked because at
                // least one time-series type still references this axis.
                // Pattern aligned on process_chron_calcul_view.php and
                // process_tab_ra.php for project-wide consistency.
                $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_axe'])
                    {
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;'"
                                  .  " id='del_axe_" . $id . "'"
                                  .  " onClick=\"delete_axe('{$id}');\""
                                  .  " title='" . TEXT_TD_BTN_DELETE . "'>X</a>";
                    }
                    else
                    {
                        $htmlcode .= "<span>-</span>";
                    }
                $htmlcode .= "</td>\n";

            $htmlcode .= "</tr>";
        }
    }
    else
    {
        $tab_axedata  = false;
        $message_info = TEXT_TD_AXE_NO_DATA;
    }

$htmlcode .= "</table>";


// Return JSON response to the client
echo json_encode([
    'tab_axedata'  => $tab_axedata,
    'htmlcode'     => $htmlcode,
    'message_info' => $message_info,
]);
?>
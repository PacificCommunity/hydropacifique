<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — time-series type table builder
Called by the parent page (gestion_typedata.php or equivalent) to render
the data-type management table as HTML inside a JSON response.
Receives idTypeData via JSON POST (0 = all types, >0 = filter to one type).
Queries:
  - TABLE_EQ_TYPE      : active measurement types for the data-type dropdown
  - TABLE_DATA_TYPE_AXE: axes for the axis dropdown
  - TABLE_TYPE_DATA    : time-series definitions; per row checks TABLE_DATA_META
                         to know whether deletion is safe (del_chron flag)
Returns JSON:
  tab_typedata : bool   — false only when the result set is empty
  htmlcode     : string — full <table> HTML
  message_info : string — error message when tab_typedata is false
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
$dataInfo    = json_decode(file_get_contents('php://input'), true);
$id_typedata = $dataInfo['idTypeData'];

// Optional WHERE clause — filter to a single type when idTypeData > 0
$where_typedata = '';
if ($id_typedata > 0)
{
    $where_typedata = 'WHERE id_eq_type_data = ' . $id_typedata;
}

$tab_typedata = true;
$message_info = '';


// -----------------------------------------------
// Query: active measurement types (Hydro, Rain, Piezo …)

$eq_type_array = [];

$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type = 1
     ORDER BY order_eq_type ASC");

while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = html_entity_decode($eq_type['nom_eq_type'] ?? '');
}


// -----------------------------------------------
// Query: axis definitions (name + unit)

$data_type_axe_array = [];

$data_type_axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe, unite
     FROM " . TABLE_DATA_TYPE_AXE . "
     ORDER BY LOWER(axe) ASC");

while ($data_type_axe = tep_db_fetch_array($data_type_axe_query))
{
    $data_type_axe_array[$data_type_axe['id']] = [
        'axe'   => html_entity_decode($data_type_axe['axe']   ?? ''),
        'unite' => html_entity_decode($data_type_axe['unite'] ?? ''),
    ];
}


// -----------------------------------------------
// Query: time-series type definitions
// Per row, also check whether at least one data_meta record references
// this type — if so, the delete button is suppressed (del_chron = false).

$chronique_array = [];

$chronique_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                     axe_data, unite, nb_round, time_scale,
                     to_periode, id_chon_periode, traitement, type_graph, raw_data
     FROM " . TABLE_TYPE_DATA . " td
     " . $where_typedata . "
     ORDER BY LOWER(td.init_type_data) ASC");

while ($chronique_data = tep_db_fetch_array($chronique_query))
{
    $id = $chronique_data['id_data_type'];

    // Check whether this type is already used in data_meta
    $verif_meta_query = tep_db_query($sql_link,
        "SELECT COUNT(*) as nb_meta
         FROM " . TABLE_DATA_META . "
         WHERE id_typedata = " . $id . "
         LIMIT 1");
    $verif_meta = tep_db_fetch_array($verif_meta_query);

    $chronique_array[$id] = [
        'init'           => html_entity_decode($chronique_data['init_type_data'] ?? ''),
        'nom_chron'      => html_entity_decode($chronique_data['nom_type_data']  ?? ''),
        'id_eq_type'     => $chronique_data['id_eq_type_data'],
        'axe_id'         => $chronique_data['axe_data'],
        'unite'          => $chronique_data['unite'],
        'nb_round'       => $chronique_data['nb_round'],
        'time_scale'     => $chronique_data['time_scale'],
        'to_periode'     => $chronique_data['to_periode'],
        'id_chon_periode'=> $chronique_data['id_chon_periode'],
        'traitement'     => $chronique_data['traitement'],
        'typegraph'      => $chronique_data['type_graph'],
        'raw_data'       => $chronique_data['raw_data'],
        'del_chron'      => ($verif_meta['nb_meta'] < 1),
    ];
}


// -----------------------------------------------
// Period transformation labels (index matches DB values 1-3)

// ($periode_transf lookup removed along with the two Period/Chronicle
// transformation columns it fed.)


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<table id='table_tri' cellspacing='0'>";

    // ---- Header row ----
    $htmlcode .= "<thead>";
        $htmlcode .= "<tr class='header-row' style='background-color:#eef3f8;'>";
            $htmlcode .= "<th style='width:90px;'>"  . TEXT_TD_TH_ACRONYM       . "</th>";
            $htmlcode .= "<th style='width:280px;'>" . TEXT_TD_TH_NAME          . "</th>";
            $htmlcode .= "<th style='width:140px;'>" . TEXT_TD_TH_DATATYPE      . "</th>";
            $htmlcode .= "<th style='width:140px;'>" . TEXT_TD_TH_AXIS          . "</th>";
            /*
            $htmlcode .= "<th style='width:80px;'>"  . TEXT_TD_TH_UNIT          . "</th>";
            $htmlcode .= "<th style='width:60px;'>"  . TEXT_TD_TH_ROUND         . "</th>";
            */
            $htmlcode .= "<th style='width:100px;'>" . TEXT_TD_TH_TIMESCALE     . "</th>";
            $htmlcode .= "<th style='width:100px;'>" . TEXT_TD_TH_PROCESSING    . "</th>";
            $htmlcode .= "<th style='width:100px;'>" . TEXT_TD_TH_GRAPHTYPE     . "</th>";
            $htmlcode .= "<th style='width:70px;text-align:center;'>" . TEXT_TD_TH_RAWDATA . "</th>";
            // (Period transformation / Chronicle transformation columns
            // removed — that workflow is now driven from the correction
            // form, no longer from the type-definition table.)
            $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
        $htmlcode .= "</tr>";
    $htmlcode .= "</thead>";

    // ---- New-entry row ----
    $htmlcode .= "<tr>
    
                <td colspan='11' style='color:#000;font-size:14px;font-weight:bold;'>"
              .  TEXT_TD_NEW_CHRON . "</td></tr>\n";

    $htmlcode .= "<tr>";

        // Acronym
        $htmlcode .= "<td><input type='text' style='width:60px;border:2px solid #609966;' name='chron_init_0'></td>";

        // Name
        $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='chron_nom_0'></td>";

        // Data type dropdown
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='chron_select_type_mesure_0' id='chron_select_type_mesure_0'"
                      .  " style='width:120px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='0'>-</option>";
                foreach ($eq_type_array as $key => $value)
                {
                    $htmlcode .= "<option value='{$key}'>{$value}</option>";
                }
            $htmlcode .= "</select>";
        $htmlcode .= "</td>";

        // Axis dropdown
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='chron_select_axe_0' id='chron_select_axe_0'"
                      .  " style='width:120px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='0'>-</option>";
                foreach ($data_type_axe_array as $key => $value)
                {
                    $htmlcode .= "<option value='{$key}'>{$value['axe']}</option>";
                }
            $htmlcode .= "</select>";
        $htmlcode .= "</td>";

        $htmlcode .= "<input type='hidden' name='chron_unite_0' value=''>";
        $htmlcode .= "<input type='hidden' name='chron_nb_round_0' value=''>";
        /*
        // Unit
        $htmlcode .= "<td><input type='text' id='chron_unite_0' name='chron_unite_0'"
                  .  " style='width:50px;border:2px solid #609966;'></td>";

        // Rounding / decimal places
        $htmlcode .= "<td><input type='text' id='chron_nb_round_0' name='chron_nb_round_0'"
                  .  " style='width:30px;border:2px solid #609966;'></td>";
        */

        // Time scale dropdown
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='chron_select_timescale_0' id='chron_select_timescale_0'"
                      .  " style='width:80px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='0'>-</option>";
                $htmlcode .= "<option value='1'>DAY</option>";
                $htmlcode .= "<option value='2'>MONTH</option>";
                $htmlcode .= "<option value='3'>YEAR</option>";
            $htmlcode .= "</select>";
        $htmlcode .= "</td>";

        // Processing dropdown (direct value vs. cumulative)
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='chron_select_traitement_0' id='chron_select_traitement_0'"
                      .  " style='width:80px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='0'>" . TEXT_TD_PROC_VALUE . "</option>";
                $htmlcode .= "<option value='1'>" . TEXT_TD_PROC_CUMUL . "</option>";
            $htmlcode .= "</select>";
        $htmlcode .= "</td>";

        // Graph type dropdown
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='chron_select_typegraph_0' id='chron_select_typegraph_0'"
                      .  " style='width:80px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='lines'>" . TEXT_TD_GRAPH_LINEAR . "</option>";
                $htmlcode .= "<option value='bar'>"   . TEXT_TD_GRAPH_BAR    . "</option>";
            $htmlcode .= "</select>";
        $htmlcode .= "</td>";

        // Raw data checkbox (1/0) — flags a type as holding raw,
        // uncorrected measurements.
        $htmlcode .= "<td style='text-align:center;'>";
            $htmlcode .= "<input type='checkbox' name='chron_raw_data_0' id='chron_raw_data_0' value='1'>";
        $htmlcode .= "</td>";

        // (Period transformation / Chronicle transformation cells
        // removed — handled at correction time now.)

        $htmlcode .= "<td>&nbsp;</td>";

    $htmlcode .= "</tr>";

    // Spacer row
    $htmlcode .= "<tr><td colspan='11' class='lignevide'>&nbsp;</td></tr>";


    // ---- Existing time-series rows ----
    if (isset($chronique_array) && !empty($chronique_array))
    {
        foreach ($chronique_array as $id => $data)
        {
            $row_l = (fmod($row, 2) == 0)
                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
            $row++;

            $htmlcode .= "<tr {$row_l} id='row_chron_{$id}'>";

                // Acronym
                $htmlcode .= "<td><input type='text' style='width:60px;'"
                          .  " name='chron_init_{$id}' value='{$data['init']}'>\n</td>";

                // Name
                $htmlcode .= "<td><input type='text' style='width:250px;'"
                          .  " name='chron_nom_{$id}' value='{$data['nom_chron']}'>\n</td>";

                // Data type dropdown
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='chron_select_type_{$id}' id='chron_select_type_{$id}' style='width:120px;'>";
                        $htmlcode .= "<option value='0'>-</option>";
                        foreach ($eq_type_array as $key => $value)
                        {
                            $sel = ($data['id_eq_type'] == $key) ? 'selected' : '';
                            $htmlcode .= "<option value='{$key}' {$sel}>{$value}</option>";
                        }
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";

                // Axis dropdown
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='chron_select_axe_{$id}' id='chron_select_axe_{$id}' style='width:120px;'>";
                        $htmlcode .= "<option value='0'>-</option>";
                        foreach ($data_type_axe_array as $key => $value)
                        {
                            $sel = ($data['axe_id'] == $key) ? 'selected' : '';
                            $htmlcode .= "<option value='{$key}' {$sel}>{$value['axe']}</option>";
                        }
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";


                $htmlcode .= "<input type='hidden' name='chron_unite_{$id}' value=''>";
                $htmlcode .= "<input type='hidden' name='chron_nb_round_{$id}' value=''>";
                /*
                // Unit
                $htmlcode .= "<td><input type='text' style='width:50px;'"
                          .  " name='chron_unite_{$id}' value='{$data['unite']}'>\n</td>";

                // Rounding / decimal places
                $htmlcode .= "<td><input type='text' style='width:30px;'"
                          .  " name='chron_nb_round_{$id}' value='{$data['nb_round']}'>\n</td>";
                */

                // Time scale dropdown
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='chron_select_timescale_{$id}' id='chron_select_timescale_{$id}' style='width:80px;'>";
                        $htmlcode .= "<option value='0'" . ($data['time_scale'] == 0 ? ' selected' : '') . ">-</option>";
                        $htmlcode .= "<option value='1'" . ($data['time_scale'] == 1 ? ' selected' : '') . ">DAY</option>";
                        $htmlcode .= "<option value='2'" . ($data['time_scale'] == 2 ? ' selected' : '') . ">MONTH</option>";
                        $htmlcode .= "<option value='3'" . ($data['time_scale'] == 3 ? ' selected' : '') . ">YEAR</option>";
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";

                // Processing dropdown
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='chron_select_traitement_{$id}' id='chron_select_traitement_{$id}' style='width:80px;'>";
                        $sel0 = ($data['traitement'] == 0) ? 'selected' : '';
                        $sel1 = ($data['traitement'] == 1) ? 'selected' : '';
                        $htmlcode .= "<option value='0' {$sel0}>" . TEXT_TD_PROC_VALUE . "</option>";
                        $htmlcode .= "<option value='1' {$sel1}>" . TEXT_TD_PROC_CUMUL . "</option>";
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";

                // Graph type dropdown
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='chron_select_typegraph_{$id}' id='chron_select_typegraph_{$id}' style='width:80px;'>";
                        $sel_l = ($data['typegraph'] == 'lines') ? 'selected' : '';
                        $sel_b = ($data['typegraph'] == 'bar')   ? 'selected' : '';
                        $htmlcode .= "<option value='lines' {$sel_l}>" . TEXT_TD_GRAPH_LINEAR . "</option>";
                        $htmlcode .= "<option value='bar'   {$sel_b}>" . TEXT_TD_GRAPH_BAR    . "</option>";
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";

                // Raw data checkbox (1/0)
                $htmlcode .= "<td style='text-align:center;'>";
                    $checked_raw = ($data['raw_data'] == 1) ? 'checked' : '';
                    $htmlcode .= "<input type='checkbox' name='chron_raw_data_{$id}' id='chron_raw_data_{$id}' value='1' {$checked_raw}>";
                $htmlcode .= "</td>";

                // (Period transformation / Chronicle transformation cells
                // removed — handled at correction time now.)

                // Delete cell — same visual style as the RA list and the
                // corrections table : a small bold "X" link when deletion
                // is safe, a dash "-" when the row is locked by referenced
                // data. Pattern aligned on process_chron_calcul_view.php
                // and process_tab_ra.php for project-wide consistency.
                $htmlcode .= "<td style='text-align:center;'>";
                    if ($data['del_chron'])
                    {
                        $htmlcode .= "<a style='font-size:12px;font-weight:bold;'"
                                  .  " id='del_" . $id . "'"
                                  .  " onClick=\"delete_typedata('{$id}','{$id_typedata}');\""
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
        // Empty result set
        $tab_typedata = false;
        $message_info = TEXT_TD_NO_DATA;
    }

$htmlcode .= "</table>";


// Return JSON response to the client
echo json_encode([
    'tab_typedata' => $tab_typedata,
    'htmlcode'     => $htmlcode,
    'message_info' => $message_info,
]);
?>
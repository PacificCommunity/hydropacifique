<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — ETL list table builder
Receives JSON: idStation.
Returns JSON: { html_text, ETL_array }
  html_text : HTML table of all ETLs for the station
  ETL_array : associative array of ETL date data used by JS popups
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

$data       = json_decode(file_get_contents('php://input'), true);
$id_station = $data['idStation'];

$nb_etl       = 0;
$first_id_etl = 0;
$ETL_array    = [];

$ETL_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, datetime_first, datetime_end
     FROM " . TABLE_DATA_ETL . "
     WHERE id_station=" . $id_station . "
     ORDER BY datetime_end DESC");

while ($ETL_tab = tep_db_fetch_array($ETL_query))
{
    $id_etl = $ETL_tab['id'];

    $obj_first    = new DateTime($ETL_tab['datetime_first']);
    $datetime_first = $obj_first->format('d-m-Y H:i:s');
    $date_first     = $obj_first->format('d-m-Y');

    $datetime_end = '-';
    $date_end     = '-';
    if (tep_not_null($ETL_tab['datetime_end']))
    {
        $obj_end      = new DateTime($ETL_tab['datetime_end']);
        $datetime_end = $obj_end->format('d-m-Y H:i:s');
        $date_end     = $obj_end->format('d-m-Y');
    }

    $ETL_array[$id_etl] = [
        'datetime_first' => $datetime_first,
        'date_first'     => $date_first,
        'datetime_end'   => $datetime_end,
        'date_end'       => $date_end,
    ];

    if ($nb_etl < 1) { $first_id_etl = $id_etl; }
    $nb_etl++;
}


// ---- Build HTML table ----

$html_text  = "<div class='table-container' style='max-height:calc(40vh - 100px);overflow-y:auto;padding-right:10px;'>\n";

if ($nb_etl > 0)
{
    $html_text .= "<table id='table_tri' cellspacing='0' style='width:100%;table-layout:fixed;font-size:10px;'>\n";
        $html_text .= "<thead>\n";
            $html_text .= "<tr class='header-row'>\n";
                $html_text .= "<th style='width:18px;font-size:11px;'>"  . TEXT_ET_TAB_COL_REF        . "</th>\n";
                $html_text .= "<th style='font-size:11px;white-space:nowrap;'>"  . TEXT_ET_TAB_COL_DATE_START . "</th>\n";
                $html_text .= "<th style='font-size:11px;white-space:nowrap;'>"  . TEXT_ET_TAB_COL_DATE_END   . "</th>\n";
                $html_text .= "<th style='width:42px;font-size:11px;color:#000;text-align:center;cursor:pointer'>\n";
                    $html_text .= "<span class='selectAll'>" . TEXT_ET_TAB_COL_SELECT . "</span>\n";
                $html_text .= "</th>\n";
            $html_text .= "</tr>\n";
        $html_text .= "</thead>\n";

    $row = 1;
    foreach ($ETL_array as $key => $value)
    {
        $row_l = (fmod($row, 2) == 0)
            ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "
            : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";

        // Compact date display: "dd-mm-yyyy HH:MM" (drop seconds, keep full year)
        $dt_first = $value['datetime_first'];
        $dt_end   = $value['datetime_end'];
        if ($dt_first !== '-') {
            $p = explode(' ', $dt_first);
            $d = explode('-', $p[0]);
            $h = explode(':', $p[1]);
            $dt_first_short = $d[0] . '-' . $d[1] . '-' . $d[2] . ' ' . $h[0] . ':' . $h[1];
        } else { $dt_first_short = '-'; }
        if ($dt_end !== '-') {
            $p = explode(' ', $dt_end);
            $d = explode('-', $p[0]);
            $h = explode(':', $p[1]);
            $dt_end_short = $d[0] . '-' . $d[1] . '-' . $d[2] . ' ' . $h[0] . ':' . $h[1];
        } else { $dt_end_short = '-'; }

        $html_text .= "<tr " . $row_l . "data-etl-num='" . $row . "'>\n";
            $html_text .= "<td style='padding-left:5px;white-space:nowrap;font-weight:normal;'>" . $row . "</td>\n";
            $html_text .= "<td style='color:#016A70;white-space:nowrap;font-weight:normal;' title='" . $dt_first . "'>" . $dt_first_short . "</td>\n";
            $html_text .= "<td style='color:#016A70;white-space:nowrap;font-weight:normal;' title='" . $dt_end   . "'>" . $dt_end_short   . "</td>\n";

            $check      = ($row < 2) ? 'checked' : '';
            $html_text .= "<td style='text-align:center;'>\n";
                $html_text .= "<input type='checkbox' name='check_ETL[]'"
                            . " value='" . $key . "_" . $row . "' " . $check . ">\n";
            $html_text .= "</td>\n";
        $html_text .= "</tr>\n";

        $row++;
    }

    $html_text .= "</table>\n";
}
else
{
    $html_text .= "<p style='margin-top:25px;text-align:center;'>" . TEXT_ET_TAB_NO_DATA . "</p>\n";
}

$html_text .= "</div>\n";

echo json_encode([
    'html_text' => $html_text,
    'ETL_array' => $ETL_array,
], JSON_UNESCAPED_UNICODE);
?>
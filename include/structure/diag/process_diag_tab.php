<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — Well log selection table builder
Receives JSON: listStation (comma-separated station IDs).
For each station, builds an accordion section listing its diagraphies
(date + checkbox) from TABLE_DATA_RA_PIEZO_PROFIL.
Each Well log cell carries data-id-ra="<id>" so the client can tint
its background with the matching trace colour after the chart is built
(the chart legend is hidden — the left-column rows act as the legend).
Returns JSON: { html_text: <string> }
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

$data         = json_decode(file_get_contents('php://input'), true);
$list_station = $data['listStation'];

// Defensive: keep only digits/commas
$list_station = preg_replace('/[^0-9,]/', '', $list_station);
if ($list_station === '') {
    echo json_encode(['html_text' => ''], JSON_UNESCAPED_UNICODE);
    exit;
}

$html_text  = '';
$nb_station = 1;
$type_data  = 5; // piezometric stations only

$station_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station FROM " . TABLE_STATION
    . " WHERE id_station IN (" . $list_station . ")");

while ($station = tep_db_fetch_array($station_query))
{
    $id_station   = (int) $station['id_station'];
    $code_station = $station['code_station'];
    $nom_station  = $station['nom_station'];

    $check = ($nb_station < 2) ? 'checked' : '';
    if ($nb_station > 1) { $html_text .= '<hr>'; }

    // ---- Station accordion header ----
    // Layout: [✓] [station name ............... ▼]  [+]
    // The header (checkbox + clickable <p>) is wrapped in its own flex
    // row. The arrow sits flush right regardless of the station name
    // length: the middle <p> grows to fill the space and pushes the
    // arrow to the far edge. The navdiag list below is OUTSIDE this
    // header — if it lived inside the flex container it would steal
    // space and the arrow would jump around.
    //
    // The "+" link to the right is OUTSIDE the toggle <p>, so clicking
    // it doesn't expand/collapse the accordion. It opens list_ra.php
    // pre-filtered on this station in a new tab — entry point to
    // create a new well log for the station.
    $html_text .= "
        <div style='width:95%;margin:0;'>
            <div style='display:flex;align-items:center;gap:6px;'>
                <input type='checkbox' name='check_station_diac[]' value='{$id_station}' {$check} onClick='checkboxDiagSelect();'>
                <p class='toggle-diag' data-menu-diag='{$id_station}'
                   style='flex:1;margin:0;height:20px;font-size:12px;color:#000;padding-top:3px;
                   display:flex;justify-content:space-between;align-items:center;cursor:pointer;'>
                    <span style='overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'>{$code_station} - {$nom_station}</span>
                    <span class='arrow' style='flex-shrink:0;margin-left:8px;'>&#9660;</span>
                </p>
                <a href='list_ra.php?st={$id_station}&new_ra=5' target='_blank'
                   title='" . TEXT_DG_NEW_RA_LINK . "'
                   style='flex-shrink:0;margin-left:8px;padding:1px 5px;font-size:12px;font-weight:bold;
                          font-family:monospace;color:#000;text-decoration:none;
                          background:transparent;border-radius:3px;line-height:1;
                          transition:background-color 0.15s,color 0.15s;'
                   onmouseover=\"this.style.backgroundColor='#000';this.style.color='#fff';\"
                   onmouseout=\"this.style.backgroundColor='transparent';this.style.color='#000';\">[+]</a>
            </div>
    ";

    $html_text .= "<div class='navdiag' style='display:none;'>";

        $ra_diac_query = tep_db_query($sql_link,
            "SELECT DISTINCT r.id_ra, r.date_heure_ra
             FROM " . TABLE_DATA_RA . " r
             JOIN " . TABLE_DATA_RA_PIEZO_PROFIL . " pp ON pp.id_ra = r.id_ra
             WHERE r.id_station=" . $id_station . "
             ORDER BY date_heure_ra DESC");

        $html_text .= "<table id='table_tri' cellspacing='0' style='width:100%;'>\n";

        $row      = 0;
        $rowColor = true;

        while ($ra_diac = tep_db_fetch_array($ra_diac_query))
        {
            $id_ra             = (int) $ra_diac['id_ra'];
            $date_ra           = DateTime::createFromFormat('Y-m-d H:i:s', $ra_diac['date_heure_ra']);
            $formatted_date_ra = $date_ra->format('d-m-Y');

            // Two well logs per row (each is a triplet date + checkbox + delete cross).
            // Open a new <tr> only on even rows; close it on odd rows.
            if (fmod($row, 2) == 0)
            {
                $bg = $rowColor ? "style='background-color: #f3f4fa;'" : "style=''";
                $html_text .= "<tr {$bg}>\n";
                $rowColor   = !$rowColor;
            }

            // Date link → RA detail page.
            // data-id-ra carries the id_ra so the client can later tint
            // this cell with the matching trace colour.
            // white-space:nowrap keeps "dd-mm-yyyy" on a single line.
            $html_text .= "<td class='diag-cell' data-id-ra='{$id_ra}' style='width:85px;white-space:nowrap;padding:3px 6px;transition:background-color 0.2s;'>
                <a href='list_ra.php?st={$id_station}&ra={$id_ra}&td={$type_data}' target='_blank'>
                    {$formatted_date_ra}
                </a>
            </td>\n";

            // Diagraphy checkbox — also carries data-id-ra for tinting.
            $check_diag = ($nb_station < 2) ? 'checked' : '';
            $html_text .= "<td class='diag-cell-check' data-id-ra='{$id_ra}'"
                        . " style='text-align:left;width:30px;padding:3px 6px;transition:background-color 0.2s;'>\n";
            $html_text .= "<input type='checkbox' name='check_diag[]'"
                        . " value='{$id_station}_{$id_ra}' {$check_diag}>\n";
            $html_text .= "</td>\n";

            // Delete cross — triggers a math-challenge popup that
            // confirms removing the well log's points from
            // TABLE_DATA_RA_PIEZO_PROFIL. The RA row itself is kept.
            // The cross is disabled while edit mode is active (the
            // client adds 'is-disabled' class on enterEditMode()).
            $html_text .= "<td class='diag-cell-del' data-id-ra='{$id_ra}'"
                        . " data-id-station='{$id_station}'"
                        . " data-label='{$formatted_date_ra}'"
                        . " style='text-align:center;width:22px;padding:3px 4px;cursor:pointer;color:#a32d2d;"
                        . "font-size:14px;line-height:1;user-select:none;transition:background-color 0.15s;'"
                        . " title='" . TEXT_DG_DEL_TITLE . "'>&times;</td>\n";

            if (fmod($row, 2) > 0) { $html_text .= "</tr>\n"; }
            $row++;
        }

        // Close any half-open <tr> if the count of well logs is odd
        if (fmod($row, 2) > 0) { $html_text .= "</tr>\n"; }

        $html_text .= "</table>\n";

    $html_text .= "</div>"; // navdiag
    $html_text .= "</div>"; // station wrapper

    $nb_station++;
}

echo json_encode(['html_text' => $html_text], JSON_UNESCAPED_UNICODE);
?>
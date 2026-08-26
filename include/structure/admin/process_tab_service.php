<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — service table builder
Called by gestion_service.php to render the service management table
as HTML inside a JSON response.

Business rule:
  Exactly one service has first = 1 (the primary/local service).
  That row is always displayed but every field is rendered as readonly 
  so it cannot be edited or deleted through this page.

Queries:
  - TABLE_SERVICE : all service definitions (primary first, then local flag)
  - TABLE_STATION : checked per row to know whether the service is safe
                    to delete (del flag)

Returns JSON:
  tab_service  : bool   — false only when the result set is empty
  htmlcode     : string — full <table> HTML
  message_info : string — error message when tab_service is false
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

// Response is JSON, not HTML
header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES utf8mb4');

// Decode JSON payload sent by the AJAX call (kept for future filtering needs)
$dataInfo = json_decode(file_get_contents('php://input'), true);

$tab_service  = true;
$message_info = '';


// -----------------------------------------------
// Query: service definitions
// Sort: primary service first, then local services, then most recent.
// Per row, also check whether at least one station references this service.
// If so, deletion is readonly  (del = false).
$service_array = [];

$service_query = tep_db_query($sql_link,
    "SELECT id_service, first, local,
            name, description, contact, contact_mail,
            serveur_db, name_db, user_db, pass_db
     FROM " . TABLE_SERVICE . "
     ORDER BY first DESC, local DESC, id_service DESC");

while ($service_tab = tep_db_fetch_array($service_query))
{
    $id_service = (int) $service_tab['id_service'];

    // Dependency check: is at least one station attached to this service?
    $del_query = tep_db_query($sql_link,
        "SELECT id_station FROM " . TABLE_STATION
        . " WHERE id_service = " . $id_service . " LIMIT 1");
    $del_info = tep_db_fetch_array($del_query);

    // Delete is allowed only if this is NOT the primary service AND no station
    // references it. Both conditions must be satisfied.
    $del = false;
    if ((int) $service_tab['first'] < 1 && !isset($del_info['id_station']))
    {
        $del = true;
    }

    $service_array[$id_service] = [
        'first'        => $service_tab['first'],
        'local'        => $service_tab['local'],
        'name'         => html_entity_decode($service_tab['name']         ?? ''),
        'description'  => html_entity_decode($service_tab['description']  ?? ''),
        'contact'      => html_entity_decode($service_tab['contact']      ?? ''),
        'contact_mail' => html_entity_decode($service_tab['contact_mail'] ?? ''),
        'serveur_db'   => html_entity_decode($service_tab['serveur_db']   ?? ''),
        'name_db'      => html_entity_decode($service_tab['name_db']      ?? ''),
        'user_db'      => html_entity_decode($service_tab['user_db']      ?? ''),
        'pass_db'      => html_entity_decode($service_tab['pass_db']      ?? ''),
        'del'          => $del,
    ];
}


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<table id='table_tri' cellspacing='0'>";

    // ---- Header row (6 columns: 5 data + 1 action) ----
    $htmlcode .= "<thead>";
        $htmlcode .= "<tr class='header-row' style='background-color:#eef3f8;'>";
            $htmlcode .= "<th style='width:180px;'>" . TEXT_SV_COL_NAME         . "</th>";
            $htmlcode .= "<th style='width:240px;'>" . TEXT_SV_COL_DESC         . "</th>";
            $htmlcode .= "<th style='width:40px;'>"  . TEXT_SV_COL_LOCAL        . "</th>";
            $htmlcode .= "<th style='width:180px;'>" . TEXT_SV_COL_CONTACT      . "</th>";
            $htmlcode .= "<th style='width:200px;'>" . TEXT_SV_COL_CONTACT_MAIL . "</th>";
            $htmlcode .= "<th style='width:40px;'>&nbsp;</th>";
        $htmlcode .= "</tr>";
    $htmlcode .= "</thead>";

    // ---- New-entry label row ----
    $htmlcode .= "<tr><td colspan='6' style='color:#000;font-size:14px;font-weight:bold;'>"
              .  TEXT_SV_NEW_ENTRY . "</td></tr>\n";

    // ---- New-entry input row ----
    // Green border highlights the row as the creation form.
    $htmlcode .= "<tr>";

        $htmlcode .= "<td>";
            $htmlcode .= "<input type='text' name='new_sv_name' style='width:160px;border:2px solid #609966;'>";
        $htmlcode .= "</td>\n";

        $htmlcode .= "<td>";
            $htmlcode .= "<input type='text' name='new_sv_desc' style='width:220px;border:2px solid #609966;'>";
        $htmlcode .= "</td>\n";

        $htmlcode .= "<td>";
            $htmlcode .= "<input type='checkbox' name='new_sv_local' style='width:20px;height:20px;'>";
        $htmlcode .= "</td>";

        $htmlcode .= "<td>";
            $htmlcode .= "<input type='text' name='new_sv_contact' style='width:160px;border:2px solid #609966;'>";
        $htmlcode .= "</td>\n";

        $htmlcode .= "<td>";
            $htmlcode .= "<input type='text' name='new_sv_contactmail' style='width:180px;border:2px solid #609966;'>";
        $htmlcode .= "</td>\n";

        // Placeholder cell to match the action column in existing rows
        $htmlcode .= "<td style='width:40px;'>&nbsp;</td>";

    $htmlcode .= "</tr>";

    // Spacer row between the new-entry form and the existing rows
    $htmlcode .= "<tr><td colspan='6' class='lignevide'>&nbsp;</td></tr>";


    // ---- Existing service rows ----
    if (!empty($service_array))
    {
        foreach ($service_array as $id => $data)
        {
            // Alternating row style with hover effect
            $row_l = (($row % 2) == 0)
                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
            $row++;

            $first = (int) $data['first'];
            $local = (int) $data['local'];
            $del   = $data['del'];

            // Escape all values before injecting them into HTML attributes
            $name         = htmlspecialchars($data['name'],         ENT_QUOTES, 'UTF-8');
            $description  = htmlspecialchars($data['description'],  ENT_QUOTES, 'UTF-8');
            $contact      = htmlspecialchars($data['contact'],      ENT_QUOTES, 'UTF-8');
            $contact_mail = htmlspecialchars($data['contact_mail'], ENT_QUOTES, 'UTF-8');

            $serveur_db   = htmlspecialchars($data['serveur_db'],   ENT_QUOTES, 'UTF-8');
            $name_db      = htmlspecialchars($data['name_db'],      ENT_QUOTES, 'UTF-8');
            $user_db      = htmlspecialchars($data['user_db'],      ENT_QUOTES, 'UTF-8');
            $pass_db      = htmlspecialchars($data['pass_db'],      ENT_QUOTES, 'UTF-8');

            // Primary service (first = 1): every field is rendered readonly .
            // Disabled inputs are not sent in the POST, so the save endpoint
            // naturally skips this row (the "continue" guard on missing sv_name_X).
            $readonly  = '';
            if ($first) { $readonly  = 'readonly '; }


            $htmlcode .= "<tr " . $row_l . ">";

                // Name
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' name='sv_name_" . $id . "'"
                        . " value=\"" . $name . "\" " . $readonly 
                        . " style='width:160px;'>";
                $htmlcode .= "</td>\n";

                // Description
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' name='sv_desc_" . $id . "'"
                        . " value=\"" . $description . "\" " . $readonly 
                        . " style='width:220px;'>";
                $htmlcode .= "</td>\n";

                // Local checkbox
                // For the primary service, show a non-interactive checked box
                // (purely visual — it cannot be altered or submitted).
                $htmlcode .= "<td>";
                    if (!$first)
                    {
                        $check = ($local === 1) ? 'checked' : '';
                        $htmlcode .= "<input type='checkbox' name='sv_local_" . $id . "'"
                            . " " . $check . " style='width:20px;height:20px;'>";
                    }
                    else
                    {
                        $htmlcode .= "<input type='checkbox' checked "
                            . " style='width:20px;height:20px;' disabled>";
                        $htmlcode .= "<input type='hidden' name='sv_local_" . $id . "' value='1' >";
                    }
                $htmlcode .= "</td>";

                // Contact name
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' name='sv_contact_" . $id . "'"
                        . " value=\"" . $contact . "\" " 
                        . " style='width:160px;'>";
                $htmlcode .= "</td>\n";

                // Contact email
                $htmlcode .= "<td>";
                    $htmlcode .= "<input type='text' name='sv_contactmail_" . $id . "'"
                        . " value=\"" . $contact_mail . "\" " 
                        . " style='width:180px;'>";
                $htmlcode .= "</td>\n";

                // Delete link — shown only if the row is deletable
                // (not the primary service AND no station references it).
                $htmlcode .= "<td style='text-align:center;'>";
                    if ($del)
                    {
                        // Pass the row name to the confirmation popup so the
                        // user can read what they're about to delete in the
                        // math-challenge dialog (anti-misclick).
                        // json_encode() doubles as a safe string escape for
                        // single quotes, accents, etc.
                        $safe_name_js = json_encode($name, JSON_UNESCAPED_UNICODE);
                        $htmlcode .= "<span class='del' title='" . TEXT_US_FT_BTN_DELETE . "'"
                            . " onClick='confirmDeleteService(" . $id . "," . $safe_name_js . ")'>X</span>";
                    }
                    else
                    {
                        $htmlcode .= "<span>-</span>";
                    }
                $htmlcode .= "</td>\n";

            $htmlcode .= "</tr>";
        }

        // Bottom spacer row — the last data row used to sit flush against
        // the table's bottom border, which felt cramped. An empty row with
        // a small fixed height adds visual breathing space without changing
        // the table layout.
        $htmlcode .= "<tr><td colspan='6' style='height:20px;border:0;'>&nbsp;</td></tr>";
    }
    else
    {
        // Empty result set — the new-entry row is still usable
        $tab_service  = false;
        $message_info = TEXT_TD_NO_DATA;
    }
$htmlcode .= "</table>";


// ---- Return JSON response to the client ----
echo json_encode([
    'tab_service'  => $tab_service,
    'htmlcode'     => $htmlcode,
    'message_info' => $message_info,
]);
?>
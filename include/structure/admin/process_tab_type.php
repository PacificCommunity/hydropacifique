<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — measurement type table builder
Called by gestion_type.php to render the measurement-type management
table as HTML inside a JSON response.

Queries:
  - TABLE_EQ_TYPE : all measurement types (active first, then by order/name)

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

// Response is JSON, not HTML
header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES utf8mb4');

// Decode JSON payload sent by the AJAX call (kept for future filtering needs)
$dataInfo = json_decode(file_get_contents('php://input'), true);

$tab_typedata = true;
$message_info = '';

// Color palette shared by every picker in this table — same set used by
// the graph configuration popup (graph_chron.php). Anchored to one source
// (function/general.php) so the available colors stay consistent across
// the whole platform.
$colorPickerPalette = colorPalette();


// -----------------------------------------------
// Build one color-picker widget.
//
// Renders:
//   - A clickable "swatch" showing the current color
//   - A hidden grid of 42 cells (the platform palette), shown on click
//   - A hidden <input> carrying the final value submitted by the form
//
// The accompanying JS handlers (toggleDropdownColor / selectColor) live
// in form_type_1.php — they're identical to the ones used by the graph
// configuration panel so the UX is the same everywhere.
//
// @param string $name        HTML name of the hidden input (the form
//                            still reads e.g. 'type_color_border_42')
// @param string $picker_id   Unique DOM id used by JS to find swatch+grid
//                            (e.g. 'tcb_42' for "type color border 42")
// @param string $current_hex Current color, or '' to default to first cell
function renderColorPicker($name, $picker_id, $current_hex, $palette)
{
    $current_hex = trim((string) $current_hex);
    // Defensive defaults — if the DB has no color stored yet, pick the
    // first palette entry so the swatch isn't empty.
    if ($current_hex === '') {
        $first = reset($palette);
        if ($first !== false) { $current_hex = $first; }
    }

    $safe_hex = htmlspecialchars($current_hex, ENT_QUOTES, 'UTF-8');

    $html  = "<div class='color-dropdown'>";

        // Visible swatch (clickable to open the grid)
        $html .= "<div id='swatch_" . $picker_id . "' class='dropdown-selected'"
              .  " onclick=\"toggleDropdownColor('" . $picker_id . "')\""
              .  " style='background-color:" . $safe_hex . ";'></div>";

        // Hidden grid of all available colors
        $html .= "<div id='grid_" . $picker_id . "' class='color-grid'>";
            foreach ($palette as $color) {
                $is_selected = (strcasecmp($color, $current_hex) === 0) ? ' is-selected' : '';
                $html .= "<div class='color-cell" . $is_selected . "'"
                      .  " style='background-color:" . $color . "'"
                      .  " title='" . $color . "'"
                      .  " onclick=\"selectColor('" . $picker_id . "', '" . $color . "')\"></div>";
            }
        $html .= "</div>";

    $html .= "</div>";

    // The hidden input is what process_type_save.php actually reads.
    // Its `name` matches the legacy text input — no backend change required.
    $html .= "<input type='hidden' id='input_" . $picker_id . "'"
          .  " name='" . $name . "' value='" . $safe_hex . "'>";

    return $html;
}


// -----------------------------------------------
// Query: measurement type definitions
// Sort: active rows first, then user-defined order, then name.
$type_array = [];

$type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, valeur_data_type, order_eq_type,
                     active_eq_type, type_color_border, type_color_background, type_graph
     FROM " . TABLE_EQ_TYPE . "
     ORDER BY active_eq_type DESC, order_eq_type ASC, nom_eq_type ASC");

while ($type_data = tep_db_fetch_array($type_query))
{
    $id = $type_data['id_eq_type'];

    // Check for dependent time-series records
    $del_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_data_type FROM " . TABLE_TYPE_DATA
        . " WHERE id_eq_type_data=" . $id . " LIMIT 1");
    $del_info = tep_db_fetch_array($del_query);

    $del = false;
    if (!in_array($id, [1, 5, 11], true) && !isset($del_info['id_data_type'])){$del = true;}

    $type_array[$id] = [
        'nom_eq_type'           => html_entity_decode($type_data['nom_eq_type'] ?? ''),
        'valeur_data_type'      => $type_data['valeur_data_type'],
        'order_eq_type'         => $type_data['order_eq_type'],
        'active_eq_type'        => $type_data['active_eq_type'],
        'type_color_border'     => $type_data['type_color_border'],
        'type_color_background' => $type_data['type_color_background'],
        'type_graph'            => $type_data['type_graph'],
        'del'            => $del,

    ];
}


// -----------------------------------------------
// Build the HTML table

$row      = 0;
$htmlcode = '';

$htmlcode .= "<table id='table_tri' cellspacing='0'>";

    // ---- Header row (8 columns: 7 data + 1 action) ----
    $htmlcode .= "<thead>";
        $htmlcode .= "<tr class='header-row' style='background-color:#eef3f8;'>";
            $htmlcode .= "<th style='width:240px;'>" . TEXT_US_FT_COL_NAME         . "</th>";
            //$htmlcode .= "<th style='width:100px;'>" . TEXT_US_FT_COL_MESURE       . "</th>";
            $htmlcode .= "<th style='width:70px;'>"  . TEXT_US_FT_COL_ORDER        . "</th>";
            $htmlcode .= "<th style='width:50px;'>"  . TEXT_US_FT_COL_ACTIVE       . "</th>";
            $htmlcode .= "<th style='width:130px;'>" . TEXT_US_FT_COL_COLOR_BORDER . "</th>";
            $htmlcode .= "<th style='width:130px;'>" . TEXT_US_FT_COL_COLOR_BG     . "</th>";
            //$htmlcode .= "<th style='width:110px;'>" . TEXT_US_FT_COL_GRAPH        . "</th>";
            $htmlcode .= "<th style='width:40px;'>&nbsp;</th>";
        $htmlcode .= "</tr>";
    $htmlcode .= "</thead>";

    // ---- New-entry label row ----
    $htmlcode .= "<tr><td colspan='6' style='color:#000;font-size:14px;font-weight:bold;'>"
              .  TEXT_US_FT_NEW_ENTRY . "</td></tr>\n";

    // ---- New-entry input row ----
    // Green border highlights the row as the creation form.
    $htmlcode .= "<tr>";
        $htmlcode .= "<td><input type='text' name='new_nom_eq_type'"
            . " style='width:200px;border:2px solid #609966;'></td>\n";

        $htmlcode .= "<input type='hidden' name='new_select_typemesure' value=''>";
        // Measurement type dropdown (1 = punctual, 2 = cumulative)
        /*
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='new_select_typemesure'"
                . " style='width:90px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='1'>" . TEXT_US_FT_OPT_PONCTUEL . "</option>";
                $htmlcode .= "<option value='2'>" . TEXT_US_FT_OPT_CUMUL    . "</option>";
            $htmlcode .= "</select>";
        $htmlcode .= "</td>\n";
        */

        $htmlcode .= "<td><input type='text' name='new_ordre_type'"
            . " style='width:40px;text-align:center;border:2px solid #609966;'></td>\n";

        $htmlcode .= "<td><input type='checkbox' name='new_active_type'"
            . " style='width:20px;height:20px;border:2px solid #609966;'></td>\n";

        // Color pickers — same widget as on existing rows below.
        // The "new_*" picker_id and name are what process_type_save.php
        // expects when inserting a new measurement type.
        $htmlcode .= "<td>"
                  . renderColorPicker('new_type_color_border',     'new_tcb', '', $colorPickerPalette)
                  . "</td>\n";

        $htmlcode .= "<td>"
                  . renderColorPicker('new_type_color_background', 'new_tcg', '', $colorPickerPalette)
                  . "</td>\n";


        $htmlcode .= "<input type='hidden' name='new_select_typegraph' value=''>";
        // Graph type dropdown (lines / bar)
        /*
        $htmlcode .= "<td>";
            $htmlcode .= "<select name='new_select_typegraph'"
                . " style='width:90px;height:32px;border:2px solid #609966;'>";
                $htmlcode .= "<option value='lines'>" . TEXT_US_FT_OPT_LINES . "</option>";
                $htmlcode .= "<option value='bar'>"   . TEXT_US_FT_OPT_BAR   . "</option>";
            $htmlcode .= "</select>";
        $htmlcode .= "</td>\n";
        */

    $htmlcode .= "</tr>";

    // Spacer row between the new-entry form and the existing rows
    $htmlcode .= "<tr><td colspan='6' class='lignevide'>&nbsp;</td></tr>";


    // ---- Existing measurement-type rows ----
    if (!empty($type_array))
    {
        foreach ($type_array as $id => $data)
        {
            // Alternating row style with hover effect
            $row_l = (($row % 2) == 0)
                ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
            $row++;

            // Escape all values before injecting them into HTML attributes
            $nom_eq_type           = htmlspecialchars($data['nom_eq_type'],           ENT_QUOTES, 'UTF-8');
            $eq_typemesure_encours = (int) $data['valeur_data_type'];
            $ordre_type            = htmlspecialchars($data['order_eq_type'],         ENT_QUOTES, 'UTF-8');
            $active_type           = (int) $data['active_eq_type'];
            $type_color_border     = htmlspecialchars($data['type_color_border'],     ENT_QUOTES, 'UTF-8');
            $type_color_background = htmlspecialchars($data['type_color_background'], ENT_QUOTES, 'UTF-8');
            $eq_typegraph_encours  = $data['type_graph'];
            $del  = $data['del'];

            $htmlcode .= "<tr " . $row_l . ">";

                // Name
                $htmlcode .= "<td><input type='text' style='width:200px;'"
                    . " name='nom_eq_type_" . $id . "'"
                    . " value=\"" . $nom_eq_type . "\"></td>";

                // Measurement type select
                $htmlcode .= "<input type='hidden' name='select_typemesure_" . $id . "' value=''>";
                /*
                $htmlcode .= "<td class='t_cont_m'>";
                    $htmlcode .= "<select name='select_typemesure_" . $id . "'"
                        . " id='select_typemesure_" . $id . "' style='width:90px;'>";
                        $sel1 = ($eq_typemesure_encours === 1) ? 'selected' : '';
                        $sel2 = ($eq_typemesure_encours === 2) ? 'selected' : '';
                        $htmlcode .= "<option value='1' " . $sel1 . ">" . TEXT_US_FT_OPT_PONCTUEL . "</option>";
                        $htmlcode .= "<option value='2' " . $sel2 . ">" . TEXT_US_FT_OPT_CUMUL    . "</option>";
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";
                */

                // Display order
                $htmlcode .= "<td><input type='text' style='width:40px;text-align:center;'"
                    . " name='ordre_type_" . $id . "'"
                    . " value='" . $ordre_type . "'></td>";

                // Active checkbox
                $htmlcode .= "<td>";
                    $check = ($active_type === 1) ? 'checked' : '';
                    $htmlcode .= "<input type='checkbox' name='active_type_" . $id . "'"
                        . " " . $check . " style='width:20px;height:20px;'>";
                $htmlcode .= "</td>";

                // Border color picker — picker_id 'tcb_42' is unique per row
                // so swatches don't collide when multiple types are listed.
                $htmlcode .= "<td>"
                          . renderColorPicker(
                                'type_color_border_' . $id,
                                'tcb_' . $id,
                                $type_color_border,
                                $colorPickerPalette
                            )
                          . "</td>";

                // Background color picker
                $htmlcode .= "<td>"
                          . renderColorPicker(
                                'type_color_background_' . $id,
                                'tcg_' . $id,
                                $type_color_background,
                                $colorPickerPalette
                            )
                          . "</td>";

                // Graph type select
                $htmlcode .= "<input type='hidden' name='select_typegraph_" . $id . "' value=''>";
                /*
                $htmlcode .= "<td>";
                    $htmlcode .= "<select name='select_typegraph_" . $id . "'"
                        . " id='select_typegraph_" . $id . "' style='width:90px;'>";
                        $sell = ($eq_typegraph_encours === 'lines') ? 'selected' : '';
                        $selb = ($eq_typegraph_encours === 'bar')   ? 'selected' : '';
                        $htmlcode .= "<option value='lines' " . $sell . ">" . TEXT_US_FT_OPT_LINES . "</option>";
                        $htmlcode .= "<option value='bar'   " . $selb . ">" . TEXT_US_FT_OPT_BAR   . "</option>";
                    $htmlcode .= "</select>";
                $htmlcode .= "</td>";
                */

                // Delete link — protected system types (1, 5, 11) cannot be deleted
                $htmlcode .= "<td style='text-align:center;'>";

                    if ($del)
                    {
                        // Pass the row name to the confirmation popup so the
                        // user can read what they're about to delete in the
                        // math-challenge dialog (anti-misclick).
                        // json_encode() doubles as a safe string escape for
                        // single quotes, accents, etc.
                        $safe_name_js = json_encode($nom_eq_type, JSON_UNESCAPED_UNICODE);
                        $htmlcode .= "<span class='del' title='" . TEXT_US_FT_BTN_DELETE . "'"
                                  .  " onClick='confirmDeleteType(" . $id . "," . $safe_name_js . ")'>X</span>";
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
        $tab_typedata = false;
        $message_info = TEXT_TD_NO_DATA;
    }
$htmlcode .= "</table>";


// ---- Return JSON response to the client ----
echo json_encode([
    'tab_typedata' => $tab_typedata,
    'htmlcode'     => $htmlcode,
    'message_info' => $message_info,
]);
?>
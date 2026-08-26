<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Quality codes tab
Included by gestion_quality_data.php.
Renders a table with:
  - one new-entry row (inputs with green border)
  - one editable row per existing quality code
Each existing row exposes: short code (init), full name, description,
data-type dropdown (hydro/rain/piezo/all), color picker, and a delete icon.
The form is submitted globally by saveQualityData() in the parent page.
Defines delete_qualitydata(id) to remove a single row via AJAX without
reloading the page.

Color picker
  Reuses the same widget/convention as graph_chron.php:
    - colorPalette()        : rich palette (42 colors by hue) shown in the grid
    - element ids           : selectedColor_<key> / dropdownList_<key> / input_color_<key>
    - JS                    : toggleDropdownColor() / selectColor() / rgbToHex()
  Picker keys are unique per row: 'q' + id (and 'q0' for the new-entry row).
  The selected hex value is stored in a hidden <input name='quality_color_<id>'>
  so process_qualitydata_save.php reads it back via the normal POST.
----------------------------------------
*/

$row = 0;

// Rich palette (same source as graph_chron.php) — 42 colors organized by hue
$colorPickerPalette = colorPalette();


// -----------------------------------------------
// render_qd_color_picker()
// Builds one color-picker cell content (swatch + grid + hidden input).
//   $picker_key  : unique key for this row ('q0', 'q12', ...)
//   $input_name  : name of the submitted hidden input ('quality_color_0', ...)
//   $current_hex : currently stored color (#rrggbb) or empty
function render_qd_color_picker($picker_key, $input_name, $current_hex, $colorPickerPalette)
{
    $current_hex = tep_not_null($current_hex) ? $current_hex : '#cccccc';

    $html  = "<div class='color-dropdown'>";

        // Current color swatch (clickable)
        $html .= "<div id='selectedColor_" . $picker_key . "' class='dropdown-selected'"
               . " onclick=\"toggleDropdownColor('" . $picker_key . "')\""
               . " style='background-color:" . $current_hex . ";'></div>";

        // Color grid — rich palette for free choice
        $html .= "<div id='dropdownList_" . $picker_key . "' class='color-grid'>";
            foreach ($colorPickerPalette as $color)
            {
                $sel = (strcasecmp($color, $current_hex) === 0) ? ' is-selected' : '';
                $html .= "<div class='color-cell" . $sel . "'"
                       . " style='background-color:" . $color . "'"
                       . " title='" . $color . "'"
                       . " onclick=\"selectColor('" . $color . "','" . $picker_key . "');\"></div>";
            }
        $html .= "</div>";

    $html .= "</div>";

    // Hidden input actually submitted with the form
    $html .= "<input type='hidden' id='input_color_" . $picker_key . "'"
           . " name='" . $input_name . "' value='" . $current_hex . "'>";

    return $html;
}


// -----------------------------------------------
// Query: active measurement types — used to populate the data-type dropdown

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
// Query: all quality codes, sorted by short code

$qualitydata_array = [];

$quality_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, couleur_qualite_data, id_eq_type
     FROM " . TABLE_DATA_QUALITE . "
     ORDER BY init_qualite_data ASC");

while ($quality_tab = tep_db_fetch_array($quality_query))
{
    $id = $quality_tab['id_data_qualite'];

    $qualitydata_array[$id] = [
        'init_qualite_data'    => html_entity_decode($quality_tab['init_qualite_data'] ?? ''),
        'nom_qualite_data'     => html_entity_decode($quality_tab['nom_qualite_data']  ?? ''),
        'info_qualite_data'    => html_entity_decode($quality_tab['info_qualite_data'] ?? ''),
        'couleur_qualite_data' => $quality_tab['couleur_qualite_data'] ?? '',
        'id_eq_type'           => $quality_tab['id_eq_type'],
    ];
}
?>

<!-- Color-picker styles (shared widget — same as graph_chron / form_type_1) -->
<style>
    /* Container that holds the visible swatch and the popout grid */
    .color-dropdown {
        position: relative;
        display: inline-block;
    }

    /* The clickable swatch shown in the table cell */
    .color-dropdown .dropdown-selected {
        width: 28px;
        height: 22px;
        border: 1px solid #444;
        border-radius: 3px;
        cursor: pointer;
        box-sizing: border-box;
    }

    /* Grid of all available colors — hidden by default, shown on click.
       position:fixed escapes the table's overflow:auto so the picker is
       never clipped by the scroll container. Coordinates are set in JS
       (toggleDropdownColor) based on the swatch's bounding rect. */
    .color-grid {
        display: none;
        position: fixed;
        z-index: 1000;
        background: #fff;
        border: 1px solid #888;
        border-radius: 3px;
        padding: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        width: 192px;
        grid-template-columns: repeat(6, 28px);
        gap: 4px;
    }
    .color-grid.is-open { display: grid; }

    .color-cell {
        width: 28px;
        height: 24px;
        border: 1px solid #aaa;
        border-radius: 2px;
        cursor: pointer;
        box-sizing: border-box;
    }
    .color-cell:hover         { transform: scale(1.08); border-color: #000; }
    .color-cell.is-selected   { outline: 2px solid #176B87; outline-offset: 1px; }
</style>

<?php
// -----------------------------------------------
// Render the table

echo "<div id='onglet_contenu' style='height:75vh;'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div class='table-container' style='float:left;height:70vh;'>";

            echo "<table id='table_tri' cellspacing='0'>";

                echo "<thead>";
                    echo "<tr class='header-row' style='background-color:#eef3f8;'>";
                        echo "<th>" . TEXT_QD_TH_INIT  . "</th>";
                        echo "<th>" . TEXT_QD_TH_NOM   . "</th>";
                        echo "<th>" . TEXT_QD_TH_INFO  . "</th>";
                        echo "<th>" . TEXT_QD_TH_TYPE  . "</th>";
                        echo "<th>" . TEXT_QD_TH_COLOR . "</th>";
                        echo "<th>&nbsp;</th>";
                    echo "</tr>";
                echo "</thead>";

                // ---- New entry row ----
                echo "<tr><td colspan='3' style='color:#000;font-size:14px;font-weight:bold;'>"
                   . TEXT_QD_NEW_ENTRY . "</td></tr>\n";
                echo "<tr>";
                    echo "<td><input type='text' class='input_texte_small' style='border:2px solid #609966;' name='quality_init_0'></td>";
                    echo "<td><input type='text' class='input_texte_200'   style='border:2px solid #609966;' name='quality_nom_0'></td>";
                    echo "<td><input type='text' class='input_texte_450'   style='border:2px solid #609966;' name='quality_info_0'></td>";
                    // Data-type dropdown for the new entry
                    echo "<td>";
                        echo "<select name='quality_select_type_0' id='quality_select_type_0' style='width:120px;'>";
                            echo "<option value='0'>" . TEXT_QD_TYPE_ALL . "</option>";
                            foreach ($eq_type_array as $key => $value)
                            {
                                echo "<option value='{$key}'>{$value}</option>";
                            }
                        echo "</select>";
                    echo "</td>";
                    // Color picker for the new entry
                    echo "<td>";
                        echo render_qd_color_picker('q0', 'quality_color_0', '#cccccc', $colorPickerPalette);
                    echo "</td>";
                    echo "<td>&nbsp;</td>";
                echo "</tr>";
                echo "<tr><td colspan='3' class='lignevide'>&nbsp;</td></tr>";

                // ---- Existing quality code rows ----
                foreach ($qualitydata_array as $id_quality => $data)
                {
                    $row_l = (fmod($row, 2) == 0)
                        ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
                        : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";
                    $row++;

                    echo "<tr {$row_l} id='row_qd_{$id_quality}'>";

                        echo "<td>";
                            echo "<input type='text' class='input_texte_small'"
                               . " name='quality_init_{$id_quality}'"
                               . " value='" . $data['init_qualite_data'] . "'>\n";
                        echo "</td>";

                        echo "<td>";
                            echo "<input type='text' class='input_texte_200'"
                               . " name='quality_nom_{$id_quality}'"
                               . " value='" . $data['nom_qualite_data'] . "'>\n";
                        echo "</td>";

                        echo "<td>";
                            echo "<input type='text' class='input_texte_450'"
                               . " name='quality_info_{$id_quality}'"
                               . " value='" . $data['info_qualite_data'] . "'>\n";
                        echo "</td>";

                        // Data-type dropdown — pre-selected to the code's current value
                        echo "<td>";
                            echo "<select name='quality_select_type_{$id_quality}'"
                               . " id='quality_select_type_{$id_quality}' style='width:120px;'>";
                                echo "<option value='0'>" . TEXT_QD_TYPE_ALL . "</option>";
                                foreach ($eq_type_array as $key => $value)
                                {
                                    $selected = ($data['id_eq_type'] == $key) ? 'selected' : '';
                                    echo "<option value='{$key}' {$selected}>{$value}</option>";
                                }
                            echo "</select>";
                        echo "</td>";

                        // Color picker — pre-set to the code's current color
                        echo "<td>";
                            echo render_qd_color_picker('q' . $id_quality, 'quality_color_' . $id_quality, $data['couleur_qualite_data'], $colorPickerPalette);
                        echo "</td>";

                        // Delete icon — triggers AJAX deletion without form submit
                        echo "<td class='t_icon'>";
                            echo "<img src='" . DIR_WS_IMG_ICO . "delete.png'"
                               . " style='width:20px;cursor:pointer;'"
                               . " title='" . TEXT_QD_BTN_DELETE . "'"
                               . " onClick=\"delete_qualitydata('{$id_quality}');\">";
                        echo "</td>\n";

                    echo "</tr>";
                }

            echo "</table>";

        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    // -----------------------------------------------
    // delete_qualitydata(id_qualitydata)
    // Sends an AJAX delete request for the given quality code.
    // On success, hides the table row immediately without reloading the page.
    // On error, shows a red-bordered feedback message.

    function delete_qualitydata(id_qualitydata)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/qualitydata/process_delqualitydata.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.display = 'block';

                if (r['del_qualitydata'])
                {
                    contenuInfo.style.border = '2px solid #09886d';
                    // Hide the deleted row immediately — no page reload needed
                    document.getElementById('row_qd_' + id_qualitydata).style.display = 'none';
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000';
                }
            }
        };

        xhr.send(JSON.stringify({ id_qualitydata: id_qualitydata }));
    }


    // =================================================================
    // Color picker — shared widget (same convention as graph_chron.php)
    //
    // Element ids per row:
    //   selectedColor_<key>  = clickable swatch
    //   dropdownList_<key>   = popout color grid
    //   input_color_<key>    = hidden input submitted with the form
    // (<key> = 'q0' for the new-entry row, 'q<id>' for existing rows)
    // =================================================================

    // Opens/closes the color grid for the given picker key and positions
    // it (position:fixed) right under the swatch, flipping/shifting if it
    // would overflow the viewport.
    function toggleDropdownColor(index)
    {
        var dropdown = document.getElementById('dropdownList_' + index);
        if (!dropdown) return;

        // Only one grid open at a time
        document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
            if (g !== dropdown) g.classList.remove('is-open');
        });

        if (dropdown.classList.contains('is-open'))
        {
            dropdown.classList.remove('is-open');
            return;
        }

        var swatch = document.getElementById('selectedColor_' + index);
        if (swatch)
        {
            var rect       = swatch.getBoundingClientRect();
            var gridWidth  = 192;
            var gridHeight = 230;
            var margin     = 4;

            var top  = rect.bottom + margin;
            var left = rect.left;

            // Flip above if it would overflow the viewport bottom
            if (top + gridHeight > window.innerHeight) {
                top = Math.max(margin, rect.top - gridHeight - margin);
            }
            // Shift left if it would overflow the viewport right edge
            if (left + gridWidth > window.innerWidth) {
                left = Math.max(margin, window.innerWidth - gridWidth - margin);
            }

            dropdown.style.top  = top  + 'px';
            dropdown.style.left = left + 'px';
        }

        dropdown.classList.add('is-open');
    }


    // Applies the chosen color: updates the swatch, the hidden input, and
    // the "is-selected" highlight inside the grid, then closes the grid.
    function selectColor(color, index)
    {
        var swatch = document.getElementById('selectedColor_' + index);
        if (swatch) { swatch.style.backgroundColor = color; }

        var input = document.getElementById('input_color_' + index);
        if (input) { input.value = color; }

        var grid = document.getElementById('dropdownList_' + index);
        if (grid)
        {
            grid.querySelectorAll('.color-cell').forEach(function(cell) {
                var cellColor = rgbToHex(cell.style.backgroundColor);
                cell.classList.toggle('is-selected',
                    cellColor && cellColor.toLowerCase() === color.toLowerCase());
            });
            grid.classList.remove('is-open');
        }
    }


    // Helper: "rgb(r, g, b)" → "#rrggbb"
    // Browsers normalise hex colors written in style attributes to rgb().
    function rgbToHex(rgb)
    {
        if (!rgb) return '';
        if (rgb[0] === '#') return rgb;
        var m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!m) return rgb;
        return '#' + ((1 << 24) + (parseInt(m[1]) << 16) + (parseInt(m[2]) << 8) + parseInt(m[3]))
                    .toString(16).slice(1);
    }


    // Close any open color grid when clicking outside a color picker
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.color-dropdown')) {
            document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
                g.classList.remove('is-open');
            });
        }
    });

</script>
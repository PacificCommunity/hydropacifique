<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Measurement type table — included by gestion_type.php.
Contains the container div where the AJAX-rendered table is injected,
plus the JS used by the page:
  - affiche_type()
      Fetches the current table HTML from process_tab_type.php and
      injects it into the container.
  - delete_type(id_typeData)
      Actually sends the delete request to process_deltype.php and
      refreshes the table. NEVER called directly from a click anymore —
      always goes through confirmDeleteType() first (math-challenge
      anti-misclick popup).
  - confirmDeleteType(id, name)
      Shows the math-challenge confirmation popup; runs delete_type()
      only when the user solves the challenge correctly.
  - toggleDropdownColor(picker_id) / selectColor(picker_id, color)
      Shared color-picker UI for the Border / Background columns,
      reusing the same widget as the graph configuration popup.
----------------------------------------
*/
?>

<!-- Color-picker styles, scoped to this management page -->
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
echo "<div id='onglet_contenu'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_datatype' class='table-container'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>

<script>

    // Container for the AJAX-loaded table
    var tabDatatype = document.getElementById('tab_datatype');


    // -----------------------------------------------
    // affiche_type()
    // Fetches the measurement-type table from the server and injects
    // its HTML into #tab_datatype. Called on initial page load and
    // after every successful save or delete.
    function affiche_type()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/admin/process_tab_type.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_typedata'])
                {
                    tabDatatype.innerHTML = r['htmlcode'];
                }
                else
                {
                    // Empty result set — show the message from the server
                    contenuInfo.innerHTML     = r['message_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send();
    }

    // Load the table on tab display
    affiche_type();


    // -----------------------------------------------
    // delete_type(id_typeData)
    // Sends the actual AJAX delete request. This is now an internal
    // function — the X button in the table calls confirmDeleteType()
    // first, which runs this only after the user has solved the math
    // challenge.
    function delete_type(id_typeData)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/admin/process_deltype.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                // Server reports success via the 'del_type' boolean (used to
                // be 'del_typedata' — a copy-paste leftover that always made
                // the bar red even when the delete succeeded).
                contenuInfo.style.border  = r['del_type']
                    ? '2px solid #09886d'
                    : '2px solid #930000';
                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.display = 'block';

                // Refresh the table to reflect the deletion
                affiche_type();
            }
        };

        xhr.send(JSON.stringify({ id_typeData: id_typeData }));
    }


    // =================================================================
    // Math-challenge confirmation popup before deletion
    //
    // Same anti-misclick pattern used in the RC modules (block_etl_edit
    // & friends): the user must mentally solve a one-liner arithmetic
    // problem before the destructive action runs. Prevents accidental
    // deletions from a stray click.
    // =================================================================

    function confirmDeleteType(idType, typeName)
    {
        // Bail out if a previous popup is still on screen
        var prev = document.getElementById('type_delete_challenge');
        if (prev) { prev.remove(); }

        // Math challenge (same generator as the RC modules)
        var op = ['+', '-', 'x'][Math.floor(Math.random() * 3)];
        var a, b, expected;
        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            expected = a + b;
        } else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            expected = a - b;
        } else {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            expected = a * b;
        }

        var overlay = document.createElement('div');
        overlay.id = 'type_delete_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';

        // Header colour intentionally red — destructive action.
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:460px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#a32d2d;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_US_TYPE_DEL_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   '<?php echo TEXT_US_TYPE_DEL_CONFIRM_MSG; ?>'
          +   '<div style="margin:10px 0;padding:8px 10px;background:#fff7e6;border-left:3px solid #BA7517;font-size:13px;">'
          +     '<b>' + (typeName || '') + '</b>'
          +   '</div>'
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo TEXT_US_TYPE_DEL_CHALLENGE_HINT; ?></div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="type_del_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="type_del_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="type_del_cancel"  class="button_close" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          +   '<button id="type_del_confirm" class="button"      style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     '<?php echo TEXT_US_FT_BTN_DELETE; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';

        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#type_del_answer');
        var feedback   = overlay.querySelector('#type_del_feedback');
        var confirmBtn = overlay.querySelector('#type_del_confirm');
        var cancelBtn  = overlay.querySelector('#type_del_cancel');

        // Subtle hover effect on both buttons — same style as the ETL
        // module's confirmation popup. Skipped for Confirm while it's
        // disabled so the disabled look stays unambiguous.
        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.filter = '';
            });
        });

        function setEnabled(on) {
            confirmBtn.disabled = !on;
            confirmBtn.style.opacity = on ? '1' : '0.45';
            confirmBtn.style.cursor  = on ? 'pointer' : 'not-allowed';
        }

        input.addEventListener('input', function() {
            var v = parseInt(input.value, 10);
            if (input.value === '' || isNaN(v)) { feedback.textContent = ''; setEnabled(false); }
            else if (v === expected)            { feedback.textContent = '\u2713'; feedback.style.color = '#0a7d34'; setEnabled(true); }
            else                                { feedback.textContent = '\u2717'; feedback.style.color = '#a32d2d'; setEnabled(false); }
        });

        function cleanup() {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
        }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); }
            if (e.key === 'Enter' && !confirmBtn.disabled) { cleanup(); delete_type(idType); }
        }
        document.addEventListener('keydown', onKey);

        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() { cleanup(); delete_type(idType); });

        setTimeout(function() { input.focus(); }, 100);
    }


    // =================================================================
    // Color picker — Border / Background columns
    //
    // Replaces the previous free-text hex inputs. One swatch per cell;
    // clicking it pops a fixed-position grid of platform colors. The
    // selected color is written to a hidden <input> whose name matches
    // the legacy text input, so process_type_save.php does not need any
    // change to read it back.
    //
    // Picker IDs follow the convention used by the markup:
    //   tcb_<id>   = Type Color Border for an existing row
    //   tcg_<id>   = Type Color Background for an existing row
    //   new_tcb    = Border color for the new-entry row
    //   new_tcg    = Background color for the new-entry row
    // =================================================================

    function toggleDropdownColor(pickerId)
    {
        var grid = document.getElementById('grid_' + pickerId);
        if (!grid) return;

        // Close every other open grid first — only one open at a time
        document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
            if (g !== grid) g.classList.remove('is-open');
        });

        var isOpen = grid.classList.contains('is-open');
        if (isOpen) { grid.classList.remove('is-open'); return; }

        // Position the grid right under the swatch. Uses getBoundingClientRect
        // because the grid is position:fixed (so it can escape the table's
        // overflow:auto context without being clipped).
        var swatch = document.getElementById('swatch_' + pickerId);
        if (swatch) {
            var rect       = swatch.getBoundingClientRect();
            var gridWidth  = 200;
            var gridHeight = 230;
            var margin     = 4;

            var top  = rect.bottom + margin;
            var left = rect.left;

            // Flip above if the grid would overflow the viewport bottom
            if (top + gridHeight > window.innerHeight) {
                top = Math.max(margin, rect.top - gridHeight - margin);
            }
            // Shift left if it would overflow the viewport right edge
            if (left + gridWidth > window.innerWidth) {
                left = Math.max(margin, window.innerWidth - gridWidth - margin);
            }

            grid.style.top  = top  + 'px';
            grid.style.left = left + 'px';
        }

        grid.classList.add('is-open');
    }


    function selectColor(pickerId, color)
    {
        // Update the visible swatch
        var swatch = document.getElementById('swatch_' + pickerId);
        if (swatch) { swatch.style.backgroundColor = color; }

        // Update the hidden input the form actually submits
        var input = document.getElementById('input_' + pickerId);
        if (input) { input.value = color; }

        // Update the highlighted cell in the grid (visual feedback when
        // the user reopens the picker later)
        var grid = document.getElementById('grid_' + pickerId);
        if (grid) {
            grid.querySelectorAll('.color-cell').forEach(function(cell) {
                cell.classList.remove('is-selected');
            });
            grid.querySelectorAll('.color-cell').forEach(function(cell) {
                // rgbToHex used because browsers normalise inline hex
                // background-color strings to rgb() form when read back.
                var cellHex = rgbToHex(cell.style.backgroundColor);
                if (cellHex && cellHex.toLowerCase() === color.toLowerCase()) {
                    cell.classList.add('is-selected');
                }
            });
        }

        // Close the grid
        if (grid) { grid.classList.remove('is-open'); }
    }


    // Helper: "rgb(r, g, b)" → "#rrggbb"
    function rgbToHex(rgb)
    {
        if (!rgb) return '';
        if (rgb[0] === '#') return rgb;
        var match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) return rgb;
        return '#' + ((1 << 24)
                    + (parseInt(match[1]) << 16)
                    + (parseInt(match[2]) << 8)
                    +  parseInt(match[3])).toString(16).slice(1);
    }


    // Close any open color grid when clicking outside of a color picker
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.color-dropdown')) {
            document.querySelectorAll('.color-grid.is-open').forEach(function(g) {
                g.classList.remove('is-open');
            });
        }
    });

</script>
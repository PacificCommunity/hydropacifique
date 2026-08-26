<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
FILTER - Station filter HTML rendering
Generates the HTML structure of the filters displayed in the left column.
Filter changes are saved to TABLE_USER_FILTER via AJAX so selections
persist across page reloads and navigation.
----------------------------------------
*/

?>
<script>

    function saveFilterAndSubmit(filterId, filterValue) 
    {
        const id_user = <?php echo json_encode($id_user); ?>;
        const xhr = new XMLHttpRequest();

        xhr.open('POST', 'include/structure/filtre/process_filter.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4) 
            {
                console.log('Filter save response:', xhr.responseText);
                <?php echo $name_form; ?>.submit();
            }
        };

        xhr.send(JSON.stringify({ 
            id_user:      id_user, 
            filter_id:    filterId, 
            filter_value: String(filterValue) 
        }));
    }
    
    // Collect all checked service checkboxes, save them, then submit
    function saveServicesAndSubmit() 
    {
        const checked = [...document.querySelectorAll('input[name="fromServices[]"]:checked')]
                            .map(cb => cb.value);
        const id_user = <?php echo json_encode($id_user); ?>;
        const xhr = new XMLHttpRequest();

        xhr.open('POST', 'include/structure/filtre/process_filter.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() 
        {
            if (xhr.readyState === 4) 
            {
                console.log('Services save response:', xhr.responseText);
                <?php echo $name_form; ?>.submit();
            }
        };

        xhr.send(JSON.stringify({ 
            id_user:      id_user, 
            filter_id:    'from_services', 
            filter_value: checked.join(',') 
        }));
    }
    
</script>

<?php

// Hidden field to detect form submission
echo "<input type='hidden' id='is_filtre_submitted' name='is_filtre_submitted' value='1' />";

// -----------------------------------------------------------------------
// Service / origin checkboxes

if ($affiche_select_from)
{
    echo "<div style='float:left;width:100%;border-bottom:1px solid #176B87;padding-bottom:5px;'>";

        echo "<p class='toggle-filtre' data-menu-filtre='data-from' style='display:flex;justify-content:space-between;align-items:center;font-weight:bold;color:#000;'>";
            echo "<span>" . TEXT_FILTER_OWNER . "</span>";
            echo "<span class='arrow' style='cursor:pointer;'>&#9650;</span>";
        echo "</p>";
        
        echo "<div class='navMenuGraph' style='display:none;'>";
            foreach ($fromData_array as $id_service => $data)
            {
                $is_checked = in_array($id_service, $from_select) ? 'checked' : '';
                // htmlspecialchars with ENT_QUOTES escapes single quotes so they don't break the attribute
                echo "<p style='float:left;width:45%;display:flex;align-items:center;' title='" . htmlspecialchars($data['description'], ENT_QUOTES) . "'>";
                    echo "<input type='checkbox'
                                name='fromServices[]'
                                id='checkDataFrom_" . $id_service . "'
                                value='" . $id_service . "'
                                " . $is_checked . "
                                onchange='saveServicesAndSubmit()'>";
                    echo "<label for='checkDataFrom_" . $id_service . "' style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                            . htmlspecialchars($data['name'], ENT_QUOTES) . "</label>";
                echo "</p>";
            }
        echo "</div>";

    echo "</div>";
}


// -----------------------------------------------------------------------
// Single station selector

if ($affiche_select_station)
{
    echo "<div style='float:left;width:100%;margin-top:10px;'>";

        echo "<p style='float:left;width:45%;margin-top:8px;padding-top:3px;text-align:left;color:#930000;'>"
                . TEXT_FILTER_STATION . "</p>";

        echo "<select name='select_station' id='select_station'
                      onchange='saveFilterAndSubmit(\"select_station\", this.value)'
                      style='float:right;width:55%;'>";

            echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

            if (isset($station_array))
            {
                foreach ($station_array as $key => $value)
                {
                    $selected = ($key == $select_station_encours) ? 'selected' : '';
                    echo "<option value='" . $key . "' " . $selected . ">"
                            . $value['code_station'] . " - " . $value['nom_station'] . "</option>";
                }
            }

        echo "</select>";

    echo "</div>";
}


// -----------------------------------------------------------------------
// Data type selector (Flow / Rain / Piezometry)

if ($affiche_select_type)
{
    echo "<div style='float:left;width:100%;border-bottom:1px solid #176B87;padding-bottom:10px;margin-top:10px;'>";

        echo "<p style='float:left;margin-top:8px;width:45%;padding-top:3px;text-align:left;color:#930000;'>"
                . TEXT_FILTER_TYPE . "</p>";

        echo "<select name='select_type_data' id='select_type_data'
                      onchange='saveFilterAndSubmit(\"select_type_data\", this.value)'
                      style='float:right;margin-top:5px;width:55%;'>";

            echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

            $hidden_type_color = '';
            if (isset($eq_type_array))
            {
                foreach ($eq_type_array as $key => $value)
                {
                    $selected = ($key == $select_type_encours) ? 'selected' : '';
                    echo "<option value='" . $key . "' " . $selected . ">"
                            . $value['nom_eq_type'] . "</option>";

                    // Hidden fields used for card border/background colours
                    $hidden_type_color .= "<input type='hidden' id='type_color_border_"     . $key . "' value=\"" . $value['type_color_border']     . "\" />\n";
                    $hidden_type_color .= "<input type='hidden' id='type_color_background_" . $key . "' value=\"" . $value['type_color_background'] . "\" />\n";
                }
            }

        echo "</select>";

    echo "</div>";
}


// -----------------------------------------------------------------------
// Station status filters (active / continuous / operational)

if ($affiche_select_statut_station)
{
    echo "<div style='float:left;width:100%;border-bottom:1px solid #176B87;padding-bottom:10px;margin-top:10px;'>";

        // Active / historical / all
        echo "<p style='float:left;width:42%;margin-top:3px;padding-top:3px;text-align:left;color:#006A67;'>"
                . TEXT_FILTER_STATUT . "</p>";

        echo "<select name='select_active' id='select_active'
                      onchange='saveFilterAndSubmit(\"select_active\", this.value)'
                      style='float:right;width:55%;margin-top:0px;'>";

            $s = ($select_active_encours == 0) ? 'selected' : '';
            echo "<option value='0' $s>" . TEXT_FILTER_ALL    . "</option>";
            $s = ($select_active_encours == 1) ? 'selected' : '';
            echo "<option value='1' $s>" . TEXT_FILTER_ACTIVE . "</option>";
            $s = ($select_active_encours == 2) ? 'selected' : '';
            echo "<option value='2' $s>" . TEXT_FILTER_CLOSED . "</option>";

        echo "</select>";

        if ($gestion_data > 0)
        {
            echo "<hr>\n";

            // Continuous / punctual / all
            echo "<p style='float:left;width:42%;margin-top:5px;padding-top:3px;text-align:left;color:#006A67;'>"
                    . TEXT_FILTER_SUIVI . "</p>";

            echo "<select name='select_suivi' id='select_suivi'
                          onchange='saveFilterAndSubmit(\"select_suivi\", this.value)'
                          style='float:right;width:55%;'>";

                $s = ($select_suivi_encours == 0) ? 'selected' : '';
                echo "<option value='0' $s>" . TEXT_FILTER_ALL      . "</option>";
                $s = ($select_suivi_encours == 1) ? 'selected' : '';
                echo "<option value='1' $s>" . TEXT_FILTER_CONTINU  . "</option>";
                $s = ($select_suivi_encours == 2) ? 'selected' : '';
                echo "<option value='2' $s>" . TEXT_FILTER_PONCTUEL . "</option>";

            echo "</select>";

            echo "<hr>\n";

            // Operational / faulty
            echo "<p style='float:left;width:42%;padding-top:3px;text-align:left;color:#006A67;'>"
                    . TEXT_FILTER_ETATEQ . "</p>";

            echo "<select name='select_armee' id='select_armee'
                          onchange='saveFilterAndSubmit(\"select_armee\", this.value)'
                          style='float:right;width:55%;'>";

                $s = ($select_armee_encours == 0) ? 'selected' : '';
                echo "<option value='0' $s>" . TEXT_FILTER_ALL      . "</option>";
                $s = ($select_armee_encours == 1) ? 'selected' : '';
                echo "<option value='1' $s>" . TEXT_FILTER_ETATPANNE . "</option>";

            echo "</select>";
        }

    echo "</div>";
}


// -----------------------------------------------------------------------
// Hydrological region (watershed)

if ($gestion_data > 0)
{
    echo "<div style='float:left;width:100%;margin-top:10px;'>";

        echo "<p style='float:left;margin-top:5px;width:45%;padding-top:3px;text-align:left;'>"
                . TEXT_FILTER_BV . "</p>";

        echo "<select name='select_regionhydro' id='select_regionhydro'
                      onchange='saveFilterAndSubmit(\"select_regionhydro\", this.value)'
                      style='float:right;margin-top:5px;width:55%;'>";

            echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

            if (isset($regionhydro_array))
            {
                foreach ($regionhydro_array as $key => $value)
                {
                    $selected = ($key == $select_regionhydro_encours) ? 'selected' : '';
                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                }
            }

        echo "</select>";

    echo "</div>";
}


// -----------------------------------------------------------------------
// Region / territory (Province or Island)

echo "<div style='float:left;width:100%;margin-top:10px;'>";

    echo "<p style='float:left;width:45%;margin-top:8px;padding-top:3px;text-align:left;'>"
            . $territoire_region . "</p>";

    echo "<select name='select_region' id='select_region'
                  onchange='saveFilterAndSubmit(\"select_region\", this.value)'
                  style='float:right;width:55%;'>";

        echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

        if (isset($region_array))
        {
            foreach ($region_array as $key => $value)
            {
                $selected = ($key == $select_region_encours) ? 'selected' : '';
                echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
            }
        }

    echo "</select>";

echo "</div>";


// -----------------------------------------------------------------------
// Municipality

echo "<div style='float:left;width:100%;margin-top:10px;'>";

    echo "<p style='float:left;width:45%;margin-top:8px;padding-top:3px;text-align:left;'>"
            . TEXT_FILTER_CITY . "</p>";

    echo "<select name='select_commune' id='select_commune'
                  onchange='saveFilterAndSubmit(\"select_commune\", this.value)'
                  style='float:right;width:55%;'>";

        echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

        if (isset($commune_array))
        {
            foreach ($commune_array as $key => $value)
            {
                $selected = ($key == $select_commune_encours) ? 'selected' : '';
                echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
            }
        }

    echo "</select>";

echo "</div>";


// -----------------------------------------------------------------------
// River

if ($affiche_select_riviere)
{
    echo "<div style='float:left;width:100%;margin-top:10px;'>";

        echo "<p style='float:left;width:42%;padding-top:3px;text-align:left;'>"
                . TEXT_FILTER_RIVER . "</p>";

        echo "<select name='select_riviere' id='select_riviere'
                      onchange='saveFilterAndSubmit(\"select_riviere\", this.value)'
                      style='float:right;width:55%;'>";

            echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

            if (isset($riviere_array))
            {
                foreach ($riviere_array as $key => $value)
                {
                    $selected = ($key == $select_riviere_encours) ? 'selected' : '';
                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                }
            }

        echo "</select>";

    echo "</div>";
}


// -----------------------------------------------------------------------
// Round (tournée)

if ($affiche_select_tournee)
{
    echo "<div style='float:left;width:100%;margin-top:10px;'>";

        echo "<p style='float:left;width:45%;margin-top:8px;padding-top:3px;text-align:left;'>"
                . TEXT_FILTER_ROUND . "</p>";

        echo "<select name='select_tournee' id='select_tournee'
                      onchange='saveFilterAndSubmit(\"select_tournee\", this.value)'
                      style='float:right;width:55%;'>";

            echo "<option value='0'>" . TEXT_FILTER_ALL . "</option>";

            if (isset($tournee_array))
            {
                foreach ($tournee_array as $key => $value)
                {
                    $selected = ($key == $select_tournee_encours) ? 'selected' : '';
                    echo "<option value='" . $key . "' " . $selected . ">" . $value . "</option>";
                }
            }

        echo "</select>";

    echo "</div>";
}

?>

<script>
// Initialise Select2 dropdowns with placeholder text
$(document).ready(function() {

    $('#select_type_data').select2({
        placeholder: 'Select Measurement Type...',
        allowClear: true,
    });

    $('#select_regionhydro').select2({
        placeholder: 'Select Watershed...',
        allowClear: true,
    });

    $('#select_region').select2({
        placeholder: 'Select State...',
        allowClear: true,
    });

    $('#select_commune').select2({
        placeholder: 'Select Municipality...',
        allowClear: true,
    });

    $('#select_tournee').select2({
        placeholder: 'Select Round...',
        allowClear: true,
    });

    $('#select_station').select2({
        placeholder: 'Select Station...',
        allowClear: true,
    });
});


// Accordion open/close state for the service origin section
$(document).ready(function()
{
    // Apply saved open/close states on load
    $('.toggle-filtre').each(function()
    {
        const menuId    = $(this).data('menu-filtre');
        const isOpen    = menuStates[menuId] === 1;
        const nav       = $(this).nextAll('.navMenuGraph').first();
        const arrow     = $(this).find('.arrow');

        if (isOpen) { nav.show(); arrow.html('&#9650;'); }
        else        { nav.hide(); arrow.html('&#9660;'); }
    });

    // Toggle section on header click and persist state
    $(document).on('click', '.toggle-filtre', function()
    {
        const id_user = <?php echo json_encode($id_user); ?>;
        const nav     = $(this).nextAll('.navMenuGraph').first();
        const menuId  = $(this).data('menu-filtre');
        const isOpen  = nav.is(':visible');

        nav.slideToggle('slow', function()
        {
            const arrow = $(this).prevAll('.toggle-filtre').find('.arrow');
            arrow.html(nav.is(':visible') ? '&#9650;' : '&#9660;');

            // Persist open/close state to TABLE_USER_MENU
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/box/process_menu.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({ id_user: id_user, menu_id: menuId, is_open: !isOpen }));
        });
    });
});
</script>
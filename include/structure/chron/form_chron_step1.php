<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Form to select stations and data to edit/view - Step 1: Station and Period Selection
----------------------------------------
- The left column offers filters to pre-select stations
- Stations are to be selected from the list in the center
- The data period is specified in the right column
*/

// Initialization of common filter variables
$affiche_select_from = true;
$affiche_select_type = true;
$affiche_select_tournee = false;
$affiche_search = true;
$affiche_select_riviere = false;
$affiche_select_station = true;
$affiche_select_statut_station = true;
require(DIR_WS_FILTRE . 'filtre_stations_var.php');

// Initialize empty arrays
$stations_selected_list = '';
$stations_selected = [];
$station_array = [];
$where_in = '';

if(isset($_POST['target_station_save']) && !empty($_POST['target_station_save'])) {
    $stations_selected_list = $_POST['target_station_save'];
    $stations_selected = explode(',', $_POST['target_station_save']);
    $where_in = " AND s.id_station IN (".$_POST['target_station_save'].") ";
}

// SELECT STATION
$nb_stations = 0;
$sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type
                FROM ".TABLE_STATION." s
                LEFT JOIN ".TABLE_STATION_TO_TOURNEE." t ON t.id_station = s.id_station
                WHERE s.id_territoire=".$territoire_id.$where_and_from .
                $where_search.$where_and_regionhydro.$where_and_region.$where_and_commune.$where_and_riviere.
                $where_and_type.$where_and_tournee.$where_and_station.
                $where_and_active.$where_and_suivi.$where_and_armee."
                ORDER BY s.nom_station";
$station_query = tep_db_query($sql_link, $sql_station);
while ($station = tep_db_fetch_array($station_query)) {
    $nom_eq_type = html_entity_decode($station['nom_eq_type'] ?? '');
    $nom_station = html_entity_decode($station['nom_station'] ?? '');
    $code_station = html_entity_decode($station['code_station'] ?? '');

    $id_station_type = $station['station_type'];
    $nom_eq_type = $eq_type_array[$id_station_type]['nom_eq_type'];
    $type_color_border = $eq_type_array[$id_station_type]['type_color_border'];
    $type_color_background = $eq_type_array[$id_station_type]['type_color_background'];

    $station_array[$station['id_station']] = array(
        'nom_eq_type' => $nom_eq_type,
        'type_color_border' => $type_color_border,
        'type_color_background' => $type_color_background,
        'active_station' => $station['active_station'],
        'nom_station' => $nom_station,
        'code_station' => $code_station
    );

    $nb_stations++;
}

// SELECT TARGET STATIONS
$nb_stations_target = 0;
if(!empty($where_in)) {
    $sql_station_target = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.station_type
                    FROM ".TABLE_STATION." s
                    WHERE s.id_territoire=".$territoire_id.
                    $where_in."
                    ORDER BY s.nom_station";

    $station_target_query = tep_db_query($sql_link, $sql_station_target);
    while ($station_target = tep_db_fetch_array($station_target_query)) {
        $nom_eq_type = html_entity_decode($station_target['nom_eq_type'] ?? '');
        $nom_station = html_entity_decode($station_target['nom_station'] ?? '');
        $code_station = html_entity_decode($station_target['code_station'] ?? '');

        $id_station_type = $station_target['station_type'];
        $nom_eq_type = $eq_type_array[$id_station_type]['nom_eq_type'];
        $type_color_border = $eq_type_array[$id_station_type]['type_color_border'];
        $type_color_background = $eq_type_array[$id_station_type]['type_color_background'];

        $station_target_array[$station_target['id_station']] = array(
            'nom_eq_type' => $nom_eq_type,
            'type_color_border' => $type_color_border,
            'type_color_background' => $type_color_background,
            'active_station' => $station_target['active_station'],
            'nom_station' => $nom_station,
            'code_station' => $code_station
        );

        $nb_stations_target++;
    }
}

// Start HTML page
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php'); // Top banner
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";
	echo "<div id='contenu_info' style='display:none;'></div>";

	echo "<div id='contenu_centre'>";

        echo "<div id='contenu_box2'>";

            echo "<h1>";
                echo "<span>".TEXT_DATA_ACCESS_STEP1."</span>";
            echo "</h1>";

			$lien_form = tep_href_link('data_chron.php');
			$name_form = 'form_filtre';
			echo "<form name='".$name_form."' id='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data'>";

                echo "<div id='cadre_graph' style='float:left;width:280px;max-height:80vh;overflow-y: auto;'>";
					echo "<div id='boxpopup' style='width:235px;padding:10px 10px;margin-bottom:10px;'>";

                        echo "<input type='hidden' name='target_station_save' id='target_station_save' value='".$stations_selected_list."'>";
                    	require(DIR_WS_FILTRE . 'filtre_stations_html.php');

                    echo "</div>";
                echo "<hr>";
                echo "</div>";

            echo "</form>";

            $lien_form = tep_href_link('data_chron.php');
			$name_form = 'form_valid';
			echo "<form name='".$name_form."' id='".$name_form."' action='".$lien_form."' method='post' enctype='multipart/form-data' onsubmit='validateForm(event)'>";

                echo "<input type='hidden' name='export' id='export' value='".$select_export."'>";

                echo "<div style='float:none;width:auto;height:45vh;'>";

                    echo "<div id='cadre_graph' style='float:left;width:55%;height:78vh;margin-left:5px;'>";

                        // Select Stations
                        echo "<div id='boxpopup' class='select' style='width:100%;margin-top: 0px;'>";

                            echo "<div style='float:left;width:45%;'>";

                                echo "<h2 id='selected_refcount' style='float:left;width:100%;'>";
                                    echo TEXT_STATIONS_TO_SELECT;
                                    echo " <span class='num'>".$nb_stations."</span> ";
                                    echo TEXT_STATIONS;
                                echo "</h2>";

                                // First dropdown with stations to select
                                echo "<select name='select_station_ref[]' id='select_station_ref' multiple='multiple' style='width:100%;height:60vh;' ondblclick=\"moveItems('select_station_ref', 'target_station_ref')\">";

                                    if(isset($station_array)) {
                                        foreach($station_array as $key => $value) {
                                            // Check if ID is in the exclude list
                                            if (in_array($key, $stations_selected)) {continue;}

                                            echo "<option value='".$key."' style='background-color:".$value['type_color_background'].";'>";
                                                echo $value['nom_eq_type']." - ".$value['code_station']." - ".$value['nom_station'];
                                            echo "</option>";
                                        }
                                    }

                                echo "</select>";

                            echo "</div>";

                            // Buttons to move items between lists
                            // Buttons to move items between lists (modernized)
                            echo "<div class='transfer-arrows' style='float:left;width:4%;margin:0 2%;margin-top:20vh;'>";
                            
                                // Forward arrow : add selected stations to target list
                                echo "<button type='button' class='transfer-btn' title='".TEXT_SELECT_STATIONS."'"
                                . " onclick=\"moveItems('select_station_ref', 'target_station_ref')\">";
                                    echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                                    . "<line x1='5' y1='12' x2='19' y2='12'/>"
                                    . "<polyline points='12 5 19 12 12 19'/>"
                                    . "</svg>";
                                echo "</button>";
                            
                                // Backward arrow : remove selected stations from target list
                                echo "<button type='button' class='transfer-btn' title='".TEXT_REMOVE_SELECTION."'"
                                . " onclick=\"moveItems('target_station_ref', 'select_station_ref')\">";
                                    echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                                    . "<line x1='19' y1='12' x2='5' y2='12'/>"
                                    . "<polyline points='12 19 5 12 12 5'/>"
                                    . "</svg>";
                                echo "</button>";
                            
                            echo "</div>";

                            echo "<div style='float:left;width:45%;'>";

                                echo "<h2 id='selected_targetcount' style='float:left;width:100%;'>";
                                    echo TEXT_SELECTED_STATIONS;
                                    echo " <span class='num'>".$nb_stations_target."</span> ";
                                    echo TEXT_STATIONS;
                                echo "</h2>";

                                // Second dropdown with selected stations
                                echo "<select name='target_station_ref[]' id='target_station_ref' multiple='multiple' style='width:100%;height:60vh;' ondblclick=\"moveItems('target_station_ref','select_station_ref')\">";

                                    foreach($stations_selected as $key) {
                                        if (isset($station_target_array[$key])) {
                                            echo "<option value='".$key."' style='background-color:".$station_target_array[$key]['type_color_background'].";'>".$station_target_array[$key]['nom_eq_type']." - ".$station_target_array[$key]['code_station']." - ".$station_target_array[$key]['nom_station']."</option>";
                                        }
                                    }

                                echo "</select>";

                            echo "</div>";

                        echo "<hr>";
                        echo "</div>";

                    echo "<hr>";
                    echo "</div>";

                    echo "<div id='cadre_graph' style='float:left;width:8%;height:60vh;margin-left:30px;'>";

                        echo "<div style='width:100%;padding:0 3%;'>";

                            echo "<input type='submit' class='button' name='valid_chron_step1' value='".TEXT_VALIDATE."' style='width:100%;margin:0px 0;' />";

                        echo "</div>";
                        echo "<hr>";
                        echo "</div>";
                    echo "</div>";

            echo "</form>";

        echo "<hr>";
        echo "</div>";

	echo "<hr>";
	echo "</div>";

echo "<hr>";
echo "</div>";

require('include/application_bottom.php');

echo "</body>";
echo "</html>";

?>

<script>

    // Declare JavaScript constants from PHP constants
    // This allows us to use translated text in our JavaScript while keeping translations in PHP files
    var TEXT_STATIONS_TO_SELECT = '<?php echo addslashes(TEXT_STATIONS_TO_SELECT); ?>';
    var TEXT_STATIONS = '<?php echo addslashes(TEXT_STATIONS); ?>';
    var TEXT_SELECTED_STATIONS = '<?php echo addslashes(TEXT_SELECTED_STATIONS); ?>';
    var TEXT_SELECT_AT_LEAST_ONE_STATION = '<?php echo addslashes(TEXT_SELECT_AT_LEAST_ONE_STATION); ?>';
    var TEXT_VALIDATE = '<?php echo addslashes(TEXT_VALIDATE); ?>';
    var TEXT_SELECT_STATIONS = '<?php echo addslashes(TEXT_SELECT_STATIONS); ?>';
    var TEXT_REMOVE_SELECTION = '<?php echo addslashes(TEXT_REMOVE_SELECTION); ?>';

    /**
     * Moves selected items between two select lists
     * @param {string} fromSelectId - ID of the source select element
     * @param {string} toSelectId - ID of the destination select element
     */
    function moveItems(fromSelectId, toSelectId) {
        var fromSelect = document.getElementById(fromSelectId);
        var toSelect = document.getElementById(toSelectId);

        // Get selected items from source list
        var selectedOptions = Array.from(fromSelect.selectedOptions);

        // Sort selected options alphabetically for better user experience
        selectedOptions.sort((a, b) => a.text.localeCompare(b.text));

        // Move each selected option to destination list
        selectedOptions.forEach(option => {
            fromSelect.removeChild(option); // Remove from source list
            toSelect.appendChild(option);    // Add to destination list
        });

        // Update hidden field with current selection for server-side processing
        syncHidden();

        // Update the visual count of selected items
        updateSelectedCount();
    }

    /**
     * Updates the displayed count of available and selected stations
     * Shows users how many stations they have selected vs how many are available
     */
    function updateSelectedCount() 
    {
        var refSelect = document.getElementById('select_station_ref');
        var targetSelect = document.getElementById('target_station_ref');
        var refCount = refSelect.options.length;
        var targetCount = targetSelect.options.length;
        var refCountDisplay = document.getElementById('selected_refcount');
        var targetCountDisplay = document.getElementById('selected_targetcount');
 
        // Rebuild full HTML so the <span class='num'> badge persists
        refCountDisplay.innerHTML =
            TEXT_STATIONS_TO_SELECT
            + " <span class='num'>" + refCount + "</span> "
            + TEXT_STATIONS;
 
        targetCountDisplay.innerHTML =
            TEXT_SELECTED_STATIONS
            + " <span class='num'>" + targetCount + "</span> "
            + TEXT_STATIONS;
    }

    /**
     * Validates the form before submission
     * @param {Event} event - The form submission event
     * @returns {boolean} - False if validation fails, true if validation passes
     */
    function validateForm(event) {
        var targetSelect = document.getElementById('target_station_ref');
        var numSelected = targetSelect.options.length;
        var msgInfo = document.getElementById('contenu_info');

        // If no stations are selected, prevent form submission and show error
        if (numSelected === 0) {
            msgInfo.innerText = TEXT_SELECT_AT_LEAST_ONE_STATION;
            msgInfo.style.display = 'block';
            event.preventDefault(); // Prevent form submission
            return false;
        }

        // Select all options to ensure they're submitted (fixes potential selection bugs)
        for (var i = 0; i < targetSelect.options.length; i++) {
            targetSelect.options[i].selected = true;
        }

        // Open form in new tab/window when valid
        var form = document.getElementById('form_valid');
        form.target = "_blank";

        return true;
    }

    /**
     * Synchronizes the hidden input field with the current selection
     * This ensures the selected stations are preserved when filtering again
     */
    function syncHidden() {
        const selectTarget = document.getElementById('target_station_ref');
        const hiddenSelect = document.getElementById('target_station_save');
        const valuesSelect = Array.from(selectTarget.options).map(opt => opt.value);
        hiddenSelect.value = valuesSelect.join(',');
    }
</script>

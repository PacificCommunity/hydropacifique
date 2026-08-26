<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Form to select stations and data to edit/view - Step 2: Chronicle Selection
----------------------------------------
- After stations are selected, they are sorted by data type (Hydro, Rain, Piezometer)
- Display of all available chronicles + RA, JGE, ETL
- Data access is handled via AJAX to manage database retrieval latency
- Once chronicles are selected, they can be exported or visualized
*/

// Initialization
$nb_stations_ref = 0;
$nb_chron_all = 0;
$nb_data_all = 0;

$id_station_encours = 0;
$id_eq_type = 0;
$sql_condition_typedata = '';

$min_date_all = null;
$max_date_all = null;

$print_table = '';

// -----------------------------------------------
// Default-period user preference
//
// The period <select> below is preselected from the user's last choice,
// persisted in TABLE_USER_MENU (same mechanism as the accordion menu in
// nav_accueil.php). is_open is an INT column, so we store the period as
// a numeric INDEX into $period_codes (not the string code). Index 6 =
// '10years' is the platform default when the user has no saved choice.
$period_codes = ['none', 'ytd', '6months', '12months', '2years', '5years', '10years', '20years'];

$default_period_index = 6; // '10years'

$pp_query = $sql_link->prepare(
    "SELECT is_open FROM " . TABLE_USER_MENU . " WHERE id_user = ? AND menu_id = 'chron_period' LIMIT 1"
);
if ($pp_query)
{
    $pp_query->bind_param("i", $id_user);
    $pp_query->execute();
    $pp_result = $pp_query->get_result();
    if ($pp_row = $pp_result->fetch_assoc())
    {
        $saved = (int) $pp_row['is_open'];
        // Guard against out-of-range stored values (e.g. an old commented
        // option) — fall back to the default if the index is invalid.
        if ($saved >= 0 && $saved < count($period_codes))
        {
            $default_period_index = $saved;
        }
    }
    $pp_query->close();
}

$default_period_code = $period_codes[$default_period_index];

// Start HTML
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    require(DIR_WS_BOX . 'block_verif_deletedata.php'); // Block for data deletion confirmation
    require(DIR_WS_BOX . 'block_info_chron.php'); // Block for chronicle information display

    require(DIR_WS_STRUCTURE . 'header.php'); // Top banner
    include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

    echo "<div id='contour_general'>";
        echo "<div id='contenu_info' style='display:none;'></div>";

        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";
                echo "<h1>";
                    echo "<span>".TEXT_DATA_ACCESS_STEP2."</span>";
                echo "</h1>";

                $lien_form = tep_href_link('data_chron.php');
                echo "<form name='select_chron_step2' id='select_chron_step2' action='".$lien_form."' method='post' enctype='multipart/form-data' onsubmit='validateForm(event)'>";

                    echo "<input type='hidden' name='select_step' value='2' />\n";
                    echo "<input type='hidden' name='select_type_data' value='".$select_type_encours."' />\n";
                    echo "<input type='hidden' name='select_list_station_txt' value='".$list_station_txt."' />\n";

                    echo "<input type='hidden' name='select_type_chron' id='select_type_chron' value='".$select_type_chron."' />\n";

                    echo "<input type='hidden' name='confirmationForm_step1' id='confirmationForm_step1' value='not_confirmed' />\n";
                    echo "<input type='hidden' name='confirmationForm_step2' id='confirmationForm_step2' value='not_confirmed' />\n";

                    // Left column for different data consultation modes
                    echo "<div id='cadre_graph' style='float:left;width:200px;margin-right:0.5%;height:75vh;overflow-y: auto;'>";
                        echo "<div style='float:left;width:100%;margin-top:8px;margin-bottom:15px;'>";
                            echo "<img src='".DIR_WS_IMG_ICO."info.png' style='float:left;width:20px;margin-left:5px;margin-right:10px;'>";
                            echo "<p style='float:left;margin-top:3px;'>";
                                echo "<a onClick='afficheBlockInfoChron();'>";
                                    echo "<span style='font-size:13px;font-weight:bold;'>".TEXT_CHRONICLE_DETAILS."</span>";
                                echo "</a>";
                            echo "</p>";
                        echo "</div>";

                        // PERIOD SELECTION
                        echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>";
                            echo "<div id='boite_small' style='width:100%;margin:0;margin-right:5%;'>";
                                echo "<p style=''>".TEXT_SELECT_PERIOD."</p>";
                                echo "<select id='select_periode' name='select_periode' style='width:60%;margin-bottom:10px;'>";
                                    // Each option is 'selected' when its code matches the
                                    // user's saved preference ($default_period_code, read
                                    // from TABLE_USER_MENU above; defaults to '10years').
                                    $sel = function($code) use ($default_period_code) {
                                        return ($code === $default_period_code) ? 'selected' : '';
                                    };
                                    echo "<option value='none' "     . $sel('none')     . ">" . TEXT_ALL_PERIODS  . "</option>";
                                    echo "<option value='ytd' "      . $sel('ytd')      . ">" . TEXT_CURRENT_YEAR . "</option>";
                                    echo "<option value='6months' "  . $sel('6months')  . ">" . TEXT_6_MONTHS     . "</option>";
                                    echo "<option value='12months' " . $sel('12months') . ">" . TEXT_12_MONTHS    . "</option>";
                                    echo "<option value='2years' "   . $sel('2years')   . ">" . TEXT_2_YEARS      . "</option>";
                                    /*
                                    echo "<option value='3years' "   . $sel('3years')   . ">" . TEXT_3_YEARS      . "</option>";
                                    echo "<option value='4years' "   . $sel('4years')   . ">" . TEXT_4_YEARS      . "</option>";
                                    */
                                    echo "<option value='5years' "   . $sel('5years')   . ">" . TEXT_5_YEARS      . "</option>";
                                    echo "<option value='10years' "  . $sel('10years')  . ">" . TEXT_10_YEARS     . "</option>";
                                    echo "<option value='20years' "  . $sel('20years')  . ">" . TEXT_20_YEARS     . "</option>";
                                echo "</select>";
                            echo "</div>";

                            // Start Date
                            echo "<div id='boite_small' class='select_date' style='margin:0;margin-right:5%;'>";
                                echo "<p style='width:70px;color:#428bca;'>".TEXT_START_DATE."</p>";
                                echo "<input class='input_texte' 
                                            style='width:65px;padding-bottom: 4px;' 
                                            name='date1_encours' 
                                            id='date1_encours' 
                                            type='text' 
                                            value='".$date_1."'
                                            onFocus='initDatepickers(this)'
                                            type='text' placeholder='dd-mm-yyyy'>";
                            echo "</div>";

                            // End Date
                            echo "<div id='boite_small' class='select_date' style='float:left;margin:0;'>";
                                echo "<p style='width:70px;color:#d9534f;'>".TEXT_END_DATE."</p>";
                                echo "<input class='input_texte' 
                                            style='width:65px;padding-bottom: 4px;' 
                                            name='date2_encours' 
                                            id='date2_encours' 
                                            type='text' 
                                            value='".$date_2."'
                                            onFocus='initDatepickers(this)'
                                            type='text' placeholder='dd-mm-yyyy'>";
                            echo "</div>";

                        echo "<hr>";
                        echo "</div>";

                        // Graphical visualization
                        if(!$select_export) {
                            echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>";
                                echo "<p style='font-weight: bold;color: #005b96;font-size: 13px;'>".TEXT_GRAPH_VISUALIZATION."</p>";
                                echo "<p>";
                                    echo "<input type='checkbox' name='one_graph' id='one_graph'>";
                                    echo "<span style='font-size: 11px;font-weight:bold;margin-left:5px;'>".TEXT_COMBINED_GRAPH."</span>";
                                echo "</p>";
                                echo "<input type='submit' class='button_graph' name='button_graph' id='button_graph_edit' style='display:none;' value='".TEXT_EDIT."' />";
                                echo "<hr>";
                            echo "</div>";
                        }

                        // Data export
                        echo "<div id='boxpopup' class='select-top' style='width:90%;margin-bottom:10px;padding-top:10px;padding-botton:5px;padding-left:10px;'>";
                            echo "<p style='font-weight: bold;color: #36802d;font-size: 13px;'>".TEXT_DATA_EXTRACTION."</p>";
                            echo "<input type='submit' class='button_export' name='button_export' id='button_export_edit' style='display:none;' value='".TEXT_EXPORT."' />";
                            echo "<hr>";
                        echo "</div>";

                        // Data deletion
                        if(!$select_export && $gestion_data > 1) {
                            echo "<div id='boxpopup' class='select-top' style='width:90%;padding-top:10px;padding-botton:5px;padding-left:10px;'>";
                                echo "<p style='font-weight: bold;color: #960a00;font-size: 13px;'>".TEXT_DELETE_DATA."</p>";
                                echo "<div id='button_del' style='float:left;width:50%;margin-top:5px;padding:4px 5px;display:none;' title='".TEXT_DELETE."'>";
                                    echo "<span>".TEXT_DELETE."</span>";
                                echo "</div>";
                                echo "<hr>";
                            echo "</div>";
                        }

                    echo "<hr>";
                    echo "</div>";

                    // Display list of available chronicles by selected stations
                    echo "<div id='cadre_graph' style='height:75vh;overflow-y: auto;'>";
                        echo "<div id='onglet_contenu'>";
                            echo "<div id='boite1' class='first' style='margin:0px;'>";
                                echo "<div id='wait' style='width:100%;height:65px;margin-top:30px;text-align:center;'>";
                                    echo "<div class='hp-loader' title='...'>";
                                        echo "<div class='hp-ring'></div>";
                                        echo "<div class='hp-mark'><span class='h'>H</span><span class='p'>P</span></div>";
                                    echo "</div>";
                                    echo "<p style='text-align:center;color:#000;'>".TEXT_LOADING."</p>";
                                    echo "<p style='text-align:center;margin-bottom:0px;'>".TEXT_LONG_LOADING_WARNING."</p>";
                                    echo "<p style='text-align:center;'>".TEXT_PLEASE_WAIT."</p>";
                                echo "</div>";
                                echo "<div id='result' style='width:100%;height:65px;text-align:center;'></div>";
                            echo "<hr>";
                            echo "</div>";
                        echo "<hr>";
                        echo "</div>";
                    echo "<hr>";
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
    var TEXT_LOADING = '<?php echo addslashes(TEXT_LOADING); ?>';
    var TEXT_LONG_LOADING_WARNING = '<?php echo addslashes(TEXT_LONG_LOADING_WARNING); ?>';
    var TEXT_PLEASE_WAIT = '<?php echo addslashes(TEXT_PLEASE_WAIT); ?>';
    var TEXT_SELECT_AT_LEAST_ONE_CHRONIC = '<?php echo addslashes(TEXT_SELECT_AT_LEAST_ONE_CHRONIC); ?>';
    var TEXT_DATE_ERROR = '<?php echo addslashes(TEXT_DATE_ERROR); ?>';
    var TEXT_START_BEFORE_END = '<?php echo addslashes(TEXT_START_BEFORE_END); ?>';
    var TEXT_INVALID_DATE_FORMAT = '<?php echo addslashes(TEXT_INVALID_DATE_FORMAT); ?>';
    var TEXT_DELETE_CONFIRMATION = '<?php echo addslashes(TEXT_DELETE_CONFIRMATION); ?>';
    var TEXT_TO = '<?php echo addslashes(TEXT_TO); ?>';

    var msgInfo = document.getElementById('contenu_info');
    var formSelectChron = document.getElementById('select_chron_step2');
    var selectPeriode = document.getElementById('select_periode');
    var date1Input = document.getElementById('date1_encours');
    var date2Input = document.getElementById('date2_encours');
    var resultData = document.getElementById('result');
    var deleteDataButton = document.getElementById('button_del');
    var graphEditButton = document.getElementById('button_graph_edit');
    var exportEditButton = document.getElementById('button_export_edit');
    var popupVerifDeleteData = document.getElementById('box_verif_deletedata');
    var detailDel = document.getElementById('detail_del');
    var detailDelText = document.getElementById('detail_del_text');
    var okButton = document.getElementById('ok_valid_deletedata');
    var noButton = document.getElementById('no_valid_deletedata');
    var list_station_txt = '<?php echo $list_station_txt; ?>';

    /**
     * Updates date inputs based on selected period
     */
    function selectDateData() {
        const today = new Date();
        let dateDebutSelect = new Date();

        switch (selectPeriode.value) {
            case 'ytd':
                dateDebutSelect = new Date(today.getFullYear(), 0, 1); // January 1st of current year
                break;
            case '6months':
                dateDebutSelect.setMonth(today.getMonth() - 6);
                break;
            case '12months':
                dateDebutSelect.setMonth(today.getMonth() - 12);
                break;
            case '2years':
                dateDebutSelect.setFullYear(today.getFullYear() - 2);
                break;
            case '5years':
                dateDebutSelect.setFullYear(today.getFullYear() - 5);
                break;
            case '10years':
                dateDebutSelect.setFullYear(today.getFullYear() - 10);
                break;
            case '20years':
                dateDebutSelect.setFullYear(today.getFullYear() - 20);
                break;
            case 'none':
                dateDebutSelect.setFullYear(today.getFullYear() - 80);
                break;
        }

        // Format dates as DD-MM-YYYY
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${day}-${month}-${year}`;
        };

        date1Input.value = formatDate(dateDebutSelect);
        date2Input.value = formatDate(today);
    }

    // Index map mirroring $period_codes on the PHP side. Used to convert
    // the selected period code into the numeric index stored in
    // TABLE_USER_MENU.is_open (an INT column).
    var periodCodes = ['none', 'ytd', '6months', '12months', '2years', '5years', '10years', '20years'];

    selectPeriode.addEventListener('change', function(event) {
        selectDateData();

        // Persist the choice so it is preselected next time, using the
        // same TABLE_USER_MENU mechanism as the accordion menu state
        // (process_menu.php). menu_id 'chron_period' holds the period
        // index; is_open carries that index (not a boolean here).
        var idx = periodCodes.indexOf(selectPeriode.value);
        if (idx < 0) { idx = 6; } // fallback to '10years'

        var prefXhr = new XMLHttpRequest();
        prefXhr.open('POST', 'include/structure/box/process_menu.php', true);
        prefXhr.setRequestHeader('Content-Type', 'application/json');
        prefXhr.send(JSON.stringify({
            id_user: <?php echo (int) $id_user; ?>,
            menu_id: 'chron_period',
            is_open: idx
        }));
    });

    formSelectChron.addEventListener('submit', function(event) {
        if(!isValidDatesInput() || !validateForm(event)) {
            event.preventDefault();
            return;
        }
    });

    /**
     * Loads data via AJAX based on selected parameters
     */
    function load_data() {
        if(isValidDatesInput()) {
            // Prepare data for JSON transmission
            var dataToSend = {
                list_station_txt: list_station_txt,
                date_1: date1Input.value,
                date_2: date2Input.value
            };

            // Convert to JSON
            var jsonData = JSON.stringify(dataToSend);

            // AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "include/structure/selectdata/process_select_chron.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    resultData.innerHTML = jsonResponse['result_html'];

                    document.getElementById('wait').style.display = 'none';
                    document.getElementById('result').style.display = 'block';
                    if(graphEditButton) graphEditButton.style.display = 'block';
                    if(exportEditButton) exportEditButton.style.display = 'block';
                    if(deleteDataButton) deleteDataButton.style.display = 'block';
                }
            };
            xhr.send(jsonData);
        }
    }

    load_data();
    selectDateData();

    /**
     * Deletes selected data via AJAX
     * @param {string} list_station_txt - List of station IDs
     */
    function delete_data() {
        var checkBoxesData = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        var checkedTabData = [];

        checkBoxesData.forEach(checkbox => {
            if (checkbox.checked) {
                checkedTabData.push(checkbox.value);
            }
        });

        // Prepare data for JSON transmission
        var dataToSend = {
            checkedTabData: checkedTabData,
            date_1: date1Input.value,
            date_2: date2Input.value
        };

        var jsonData = JSON.stringify(dataToSend);

        // AJAX request for deletion
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/selectdata/process_delete_chron.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                load_data();

                msgInfo.innerText = jsonResponse['js_text'];
                msgInfo.style.border = '2px solid #09886d';
                msgInfo.style.display = 'block';
            }
        };
        xhr.send(jsonData);
    }

    // Data deletion confirmation handling
    if(deleteDataButton) {
        deleteDataButton.addEventListener('click', function() {
            if(validateForm(event)) {
                if(isValidDatesInput()) {
                    msgInfo.style.display = 'none';
                    popupVerifDeleteData.style.display = 'block';
                    detailDel.style.display = 'block';

                    detailDelText.value = TEXT_DELETE_CONFIRMATION + ' ' + date1Input.value + ' ' + TEXT_TO + ' ' + date2Input.value;

                    okButton.addEventListener('click', function() {
                        var checkboxesDel = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
                        popupVerifDeleteData.style.display = 'none';
                        delete_data();
                    });

                    noButton.addEventListener('click', function() {
                        popupVerifDeleteData.style.display = 'none';
                    });
                }
            }
        });
    }

    /**
     * Toggles checkboxes for quick selection
     * @param {number} id_station - Station ID
     * @param {number} id_type - Data type ID
     * @param {string} id_chron - Chronicle ID
     */
    function toggleCheckboxes(id_station, id_type, id_chron) {
        let checkboxes;

        if (id_station && id_station > 0) {
            // Auto-select for entire station
            checkboxes = document.querySelectorAll('input[type="checkbox"][value^="' + id_station + '"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_type && id_type > 0) {
            // Auto-select for all data types
            checkboxes = document.querySelectorAll('input[type="checkbox"][value*="_' + id_type + '_"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron && id_chron > 0) {
            // Auto-select for specific chronicle type
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_' + id_chron + '"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron == 'ra') {
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_ra"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron == 'jge') {
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_jge"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }
        if (id_chron == 'etl') {
            checkboxes = document.querySelectorAll('input[type="checkbox"][value$="_etl"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');
        }

        // Check current state of checkboxes
        let allChecked = true;
        for (let i = 0; i < checkboxes.length; i++) {
            if (!checkboxes[i].checked) {
                allChecked = false;
                break;
            }
        }

        // Toggle all checkboxes based on current state
        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = !allChecked;
        }
    }
    

    function handleSelectChange(select) 
    {
        var selectedIndex = select.selectedIndex;
        var selectedValue = select.options[selectedIndex].value;
        var selectedText = select.options[selectedIndex].text;

        toggleCheckboxes(0,0,selectedValue);
    }

    /**
     * Validates the form before submission
     * @param {Event} event - Form submission event
     * @returns {boolean} - False if validation fails
     */
    function validateForm(event) {
        var checkBoxesData = document.querySelectorAll('input[type="checkbox"]:not(#multi_file):not(#format_export):not(#rapport_act):not(#one_graph):not(#new_page):not(#entete_col)');

        // Check if at least one checkbox is selected
        var isChecked = false;
        for (var i = 0; i < checkBoxesData.length; i++) {
            if (checkBoxesData[i].checked) {
                isChecked = true;
                break;
            }
        }

        if (!isChecked) {
            msgInfo.innerText = TEXT_SELECT_AT_LEAST_ONE_CHRONIC;
            msgInfo.style.display = 'block';
            event.preventDefault();
            return false;
        }

        var form = document.getElementById('select_chron_step2');
        form.target = "_blank";
        return true;
    }

    /**
     * Validates a date string
     * @param {string} dateString - Date in DD-MM-YYYY format
     * @returns {boolean} - True if valid date
     */
    function isValidDate(dateString) {
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) {
            return false;
        }

        const [day, month, year] = dateString.split("-").map(Number);
        const date = new Date(year, month - 1, day);
        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    }

    /**
     * Validates date inputs
     * @returns {boolean} - True if dates are valid
     */
    function isValidDatesInput() {
        if(isValidDate(date1Input.value) && isValidDate(date2Input.value)) {
            var date1Format = parseDate(date1Input.value);
            var date2Format = parseDate(date2Input.value);

            if(date1Format < date2Format) {
                return true;
            } else {
                msgInfo.innerText = TEXT_START_BEFORE_END;
                msgInfo.style.display = 'block';
                return false;
            }
        } else {
            msgInfo.innerText = TEXT_INVALID_DATE_FORMAT;
            msgInfo.style.display = 'block';
            return false;
        }
    }

    /**
     * Parses a date string into Date object
     * @param {string} dateString - Date in DD-MM-YYYY format
     * @returns {Date} - JavaScript Date object
     */
    function parseDate(dateString) {
        var [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }
</script>
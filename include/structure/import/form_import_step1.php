<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Import form - File selection and data loading
- Structures the page for upload tracking and import result display
- Orchestrates the import workflow steps:
  1. File upload
  2. File conformity check
  3. Station and series recognition validation
  4. Data loading with integration tracking
  5. Result display
----------------------------------------
*/
ini_set('memory_limit', '256M');

// -----------------------------------------------
// Generate a unique import session identifier

$timestamp = time();
$id_import = 'ID_' . date('YmdHis', $timestamp); // Prefix avoids ambiguity with numeric date strings


// -----------------------------------------------
// Page HTML structure

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    require(DIR_WS_BOX      . 'block_verif_savedata.php'); // Confirmation popup before saving data
    require(DIR_WS_STRUCTURE . 'header.php');              // Top banner
    include(DIR_WS_BOX      . 'nav_accueil.php');          // Navigation menu

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // ---- Page title ----
                echo "<h1>";
                    echo "<span>" . TEXT_IMPORT_PAGE_TITLE . "</span>";
                echo "</h1>";

                // ---- Left panel: file selection + progress log ----
                echo "<div style='float:left;width:32%;height:80vh;overflow-y:auto;'>\n";

                    $lien_form = tep_href_link('import.php');

                    // File picker
                    echo "<div id='boxpopup' class='select-top' style='width:92%;padding:10px;'>\n";
                        echo "<input type='file' id='fileInput' name='fileInput[]' multiple style='width:90%;'>";
                    echo "</div>\n";

                    echo "<hr>\n";

                    // Import instructions link
                    echo "<div style='cursor:pointer;'>";
                        echo "<img src='" . DIR_WS_IMG_ICO . "info.png' style='float:left;width:20px;margin-left:5px;margin-right:10px;'>";
                        echo "<p style='float:left;margin-top:3px;' id='info_popup'>";
                            echo "<span style='font-size:13px;font-weight:bold;'>" . TEXT_IMPORT_INSTRUCTIONS_LINK . "</span>";
                        echo "</p>\n";
                    echo "</div>\n";

                    // Processing log textarea
                    echo "<div id='boxpopup' class='select' style='width:92%;border:1px solid #000;'>\n";
                        echo "<p>";
                            echo "<span style='font-weight:bold;'>" . TEXT_IMPORT_PROCESS_LABEL . "</span>";
                        echo "</p>";
                        echo "<textarea id='fileListInfo' style='width:98%;height:30vh;'></textarea>";
                    echo "</div>\n";

                    echo "<hr>\n";

                    // Action buttons + loading spinners
                    echo "<div style='width:95%;'>\n";

                        // Upload spinner
                        echo "<div id='loadWaitImg1' style='float:right;display:none;'>\n";
                            echo "<p style='float:left;padding-top:10px;'>" . TEXT_IMPORT_JS_WAIT_UPLOAD . "</p>";
                            echo "<img src='" . DIR_WS_IMG . "wait.gif' style='float:right;width:30px;margin-left:10px;'>";
                        echo "</div>\n";

                        // Upload button
                        echo "<input type='submit' class='button' name='uploadButton' id='uploadButton'"
                           . " value='" . TEXT_IMPORT_BTN_UPLOAD . "'"
                           . " style='float:right;display:none;'/>";

                        // Import spinner
                        echo "<div id='loadWaitImg2' style='float:right;display:none;'>\n";
                            echo "<p style='float:left;padding-top:10px;'>" . TEXT_IMPORT_JS_WAIT_IMPORT . "</p>";
                            echo "<img src='" . DIR_WS_IMG . "wait.gif' style='float:right;width:30px;margin-left:10px;'>";
                        echo "</div>\n";

                        // Import data button
                        echo "<input type='submit' class='button' name='loadDataButton' id='loadDataButton'"
                           . " value='" . TEXT_IMPORT_BTN_IMPORT . "'"
                           . " style='float:right;display:none;'/>";

                    echo "</div>\n";

                echo "</div>\n"; // Left panel


                // ---- Right panel: series table populated after file upload ----
                // ---- Right panel: series table populated after file upload ----
                echo "<div style='float:left;width:67%;margin-left:1%;'>\n";
                    
                
                    // Flex container centers the table horizontally; "safe center" prevents
                    // clipping the table's left edge when it overflows (horizontal scroll)
                    echo "<div id='container_table_import' 
                        style='height:60vh;padding:10px;overflow:auto;border:1px solid #ccc;margin-bottom:10px;
                        display:flex;justify-content:safe center;align-items:flex-start;'>\n";
                        
                        echo "<table id='table_import_chron' cellspacing='0'>\n";

                            echo "<thead>\n";
                                echo "<tr class='header-row'>";
                                    echo "<th style='width:280px;'>" . TEXT_IMPORT_TH_FILE . "</th>\n";
                                    echo "<th style='width:280px;'>" . TEXT_IMPORT_TH_STATION . "</th>\n";
                                    echo "<th style='width:80px;'>" . TEXT_IMPORT_TH_CHRON . "</th>\n";
                                    echo "<th style='width:100px;text-align:center;'>" . TEXT_IMPORT_TH_UNIT . "</th>\n";
                                    echo "<th style='width:100px;font-size:12px;color:#000;text-align:center;"
                                       . "border:none;cursor:pointer;' onclick=\"toggleCheckboxes();\">";
                                        echo "<span class='selectAll'>" . TEXT_IMPORT_TH_SELECT . "</span>";
                                    echo "</th>\n";
                                    echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n";
                                    echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n";
                                    echo "<th style='width:40px;text-align:center;border:none;'>&nbsp;</th>\n";
                                echo "</tr>\n";
                            echo "</thead>\n";

                        echo "</table>\n";

                    echo "</div>\n";

                echo "</div>\n"; // Right panel

            echo "</div>\n"; // #contenu_box2

        echo "</div>"; // #contenu_centre

    echo "</div>"; // #contour_general

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    // -----------------------------------------------
    // PHP translation constants injected into JS scope

    var LANG_IMPORT = {
        fileList:    "<?= TEXT_IMPORT_JS_FILE_LIST ?>",
        uploadStart: "<?= TEXT_IMPORT_JS_UPLOAD_START ?>",
        uploadDone:  "<?= TEXT_IMPORT_JS_UPLOAD_DONE ?>",
        noFile:      "<?= TEXT_IMPORT_JS_NO_FILE ?>",
        dataStart:   "<?= TEXT_IMPORT_JS_DATA_START ?>",
        dataDone:    "<?= TEXT_IMPORT_JS_DATA_DONE ?>",
        parseError:  "<?= TEXT_IMPORT_JS_PARSE_ERROR ?>",
        uploadError: "<?= TEXT_IMPORT_JS_UPLOAD_ERROR ?>"
    };

    // -----------------------------------------------
    // DOM references

    var numLoadData    = 0;
    var tabLoadData    = [];

    const fileInput       = document.getElementById('fileInput');
    const uploadButton    = document.getElementById('uploadButton');
    const loadDataButton  = document.getElementById('loadDataButton');
    const loadWaitImg1    = document.getElementById('loadWaitImg1');
    const loadWaitImg2    = document.getElementById('loadWaitImg2');
    const fileListInfo    = document.getElementById('fileListInfo');
    const table_import_chron = document.getElementById('table_import_chron');

    const checkedValuesChron = []; // Stores checked series IDs selected for import
    const checkEntete        = document.getElementById('entete'); // Column header read checkbox

    // PHP session metadata passed to AJAX calls
    const jsonMeta = {
        id_user:   '<?php echo $id_user; ?>',
        id_import: '<?php echo $id_import; ?>'
    };
    const jsonMetaString = JSON.stringify(jsonMeta);


    // -----------------------------------------------
    // Step 1: File selection listener
    // Displays selected file names and sizes in the progress log

    fileInput.addEventListener('change', function(e)
    {
        var fileList = e.target.files;
        var fileText = LANG_IMPORT.fileList + '\n\n';

        uploadButton.style.display   = 'block';
        loadDataButton.style.display = 'none';

        for (var i = 0; i < fileList.length; i++)
        {
            var fileSizeInKB = fileList[i].size / 1024;
            fileText += fileList[i].name + ' - ' + Math.floor(fileSizeInKB).toLocaleString() + ' Ko \n';
        }

        fileText += '\n--\n\n';

        fileListInfo.value = fileText;
        fileListInfo.scrollTop = fileListInfo.scrollHeight;
        fileListInfo.readOnly = true;
    });


    // -----------------------------------------------
    // Step 2: Upload a single file via AJAX
    // Sends file + session metadata to load_file.php
    // Appends the returned HTML row to the series table

    function uploadFile(file, callback)
    {
        uploadButton.style.display  = 'none';
        loadWaitImg1.style.display  = 'block';

        const formData = new FormData();
        formData.append('file', file);
        formData.append('meta', jsonMetaString);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/import/load_file.php', true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                if (xhr.status === 200)
                {
                    console.log('Status :', xhr.status);
            console.log('Réponse brute :', xhr.responseText);

                    try
                    {
                        var reponse = JSON.parse(xhr.responseText);

                        fileListInfo.value += reponse.msg_info;
                        fileListInfo.scrollTop = fileListInfo.scrollHeight;

                        // Inject the returned table row into the series table
                        table_import_chron.insertAdjacentHTML('beforeend', reponse.tab_html);

                        if (callback) callback(null, reponse);
                    }
                    catch (e)
                    {
                        fileListInfo.value += LANG_IMPORT.parseError;
                        fileListInfo.scrollTop = fileListInfo.scrollHeight;
                        if (callback) callback(e, null);
                    }
                }
                else
                {
                    var errorMsg = LANG_IMPORT.uploadError + xhr.status + ' ' + xhr.statusText;
                    fileListInfo.value += errorMsg;
                    fileListInfo.scrollTop = fileListInfo.scrollHeight;
                    if (callback) callback(new Error(errorMsg), null);
                }
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Upload files sequentially (one at a time)
    // Recurses via callback until all files are processed

    function uploadFilesSequentially(files)
    {
        let index = 0;

        function uploadNext()
        {
            if (index < files.length)
            {
                uploadFile(files[index], () => {
                    index++;
                    uploadNext();
                });
            }
            else
            {
                // All files uploaded: show import button
                fileListInfo.value += '\n' + LANG_IMPORT.uploadDone + '\n\n';
                fileListInfo.scrollTop = fileListInfo.scrollHeight;

                loadWaitImg1.style.display   = 'none';
                loadDataButton.style.display = 'block';
            }
        }

        uploadNext();
    }


    // -----------------------------------------------
    // Upload button click: clears the table and uploads selected files

    uploadButton.addEventListener('click', function()
    {
        // Clear all existing tbody rows from the series table
        var tbodies = table_import_chron.getElementsByTagName('tbody');
        for (var i = 0; i < tbodies.length; i++)
        {
            var tbody = tbodies[i];
            while (tbody.firstChild) { tbody.removeChild(tbody.firstChild); }
        }

        const files = fileInput.files;

        if (files.length > 0)
        {
            fileListInfo.value += '\n' + LANG_IMPORT.uploadStart + '\n';
            fileListInfo.scrollTop = fileListInfo.scrollHeight;
            uploadFilesSequentially(files);
        }
        else
        {
            fileListInfo.value += LANG_IMPORT.noFile;
        }
    });


    // -----------------------------------------------
    // Step 3: Load data for one series via AJAX
    // Routes to the correct PHP endpoint based on series type (LAB, TOT, RA, JGE, ETL, REP, or standard)
    // Recurses until all selected series are processed

    function loadData()
    {
        var id         = tabLoadData[numLoadData];
        var nocheckId  = document.getElementById('nocheck_' + id);
        var checkId    = document.getElementById('check_'   + id);
        var noteId     = document.getElementById('note_'    + id);
        var graphId    = document.getElementById('graph_'   + id);
        var dataInit   = document.getElementById('dataInit_'+ id);
        var waitId     = document.getElementById('wait_'    + id);

        // Show spinner, hide status icons while loading
        nocheckId.style.display = 'none';
        checkId.style.display   = 'none';
        noteId.style.display    = 'none';
        graphId.style.display   = 'none';
        waitId.style.display    = 'block';

        var xhrLoadData = new XMLHttpRequest();

        // Route to the appropriate import endpoint based on series type
        switch (dataInit ? dataInit.value : '')
        {
            case 'LAB': xhrLoadData.open('POST', 'include/structure/import/load_data_lab.php',  true); break;
            case 'TOT': xhrLoadData.open('POST', 'include/structure/import/load_data_tot.php',  true); break;
            case 'RA':  xhrLoadData.open('POST', 'include/structure/import/load_data_ra.php',   true); break;
            case 'JGE': xhrLoadData.open('POST', 'include/structure/import/load_data_jge.php',  true); break;
            case 'ETL': xhrLoadData.open('POST', 'include/structure/import/load_data_etl.php',  true); break;
            case 'REP': xhrLoadData.open('POST', 'include/structure/import/load_data_rep.php',  true); break;
            default:    xhrLoadData.open('POST', 'include/structure/import/load_data_chron.php',true);
        }

        xhrLoadData.setRequestHeader('Content-Type', 'application/json');

        xhrLoadData.onreadystatechange = function()
        {
            if (xhrLoadData.readyState === 4 && xhrLoadData.status === 200)
            {
                var reponse = JSON.parse(xhrLoadData.responseText);

                fileListInfo.value += reponse.text;
                fileListInfo.scrollTop = fileListInfo.scrollHeight;

                // Update row status icons
                waitId.style.display = 'none';
                if (reponse.nbData > 0) { checkId.style.display = 'block'; }
                else                    { nocheckId.style.display = 'block'; }
                noteId.style.display  = 'block';
                graphId.style.display = 'block';

                if (numLoadData < tabLoadData.length - 1)
                {
                    numLoadData++;
                    loadData(); // Recurse: process next series
                }
                else
                {
                    // All series processed: reset counter and show completion message
                    numLoadData = 0;

                    loadDataButton.style.display = 'block';
                    loadWaitImg2.style.display   = 'none';

                    fileListInfo.value += '\n\n' + LANG_IMPORT.dataDone + '\n';
                    fileListInfo.scrollTop = fileListInfo.scrollHeight;
                }
            }
        };

        xhrLoadData.send(JSON.stringify(tabLoadData[numLoadData]));
    }


    // -----------------------------------------------
    // Import button click:
    // Collects all checked series, shows confirmation popup, then launches loadData()

    // Import button click:
    // Collects all checked series, shows confirmation popup (gated by the
    // arithmetic challenge defined in block_verif_savedata.php), then
    // launches loadData()

    loadDataButton.addEventListener('click', function()
    {
        let checkboxes = document.querySelectorAll('input[name="checkFile[]"]');

        tabLoadData = []; // Reset task queue

        if (checkboxes.length > 0)
        {
            checkboxes.forEach(checkbox => {
                if (checkbox.checked)
                {
                    var tab_check = checkbox.value.split('_'); // value = 'import_n', extract n
                    tabLoadData.push(tab_check[1]);
                }
            });
        }

        if (tabLoadData.length > 0)
        {
            // Show save confirmation popup. Its math challenge (handled in
            // block_verif_savedata.php) regenerates and disables OK on open.
            var popup_verif_savedata = document.getElementById('box_verif_savedata');
            popup_verif_savedata.style.display = 'block';

            initDraggable('title_info_chron_save', 'box_verif_savedata');

            var okButton = document.getElementById('ok_valid_savedata');
            var noButton = document.getElementById('no_valid_savedata');

            // Guard against duplicate event listener registration
            if (!okButton.dataset.listenerAdded)
            {
                okButton.addEventListener('click', function()
                {
                    // Hard gate: ignore the click while the challenge is unsolved
                    if (okButton.disabled) { return; }

                    popup_verif_savedata.style.display = 'none';

                    loadDataButton.style.display = 'none';
                    loadWaitImg2.style.display   = 'block';

                    fileListInfo.value += '\n' + LANG_IMPORT.dataStart + '\n\n';
                    fileListInfo.scrollTop = fileListInfo.scrollHeight;

                    loadData();
                });

                okButton.dataset.listenerAdded = true;
            }

            if (!noButton.dataset.listenerAdded)
            {
                noButton.addEventListener('click', function()
                {
                    popup_verif_savedata.style.display = 'none'; // Close popup without action
                });

                noButton.dataset.listenerAdded = true;
            }
        }
    });


    // -----------------------------------------------
    // Toggle all series checkboxes (select all / deselect all)

    function toggleCheckboxes()
    {
        let checkboxes = document.querySelectorAll('input[type="checkbox"][value^="import"]');

        // Check if all are currently checked
        let allChecked = true;
        for (let i = 0; i < checkboxes.length; i++)
        {
            if (!checkboxes[i].checked) { allChecked = false; break; }
        }

        // Toggle: if all checked → uncheck all, otherwise → check all
        for (let i = 0; i < checkboxes.length; i++)
        {
            checkboxes[i].checked = !allChecked;
        }
    }


    // -----------------------------------------------
    // Show the import instructions popup

    var infoPopup    = document.getElementById('info_popup');
    const infoImport = <?php echo $info_import; ?>;    

    if (infoPopup)
    {
        infoPopup.onclick = function()
        {
            openInfoPopup('infoimport', infoImport);
        };
    }
</script>
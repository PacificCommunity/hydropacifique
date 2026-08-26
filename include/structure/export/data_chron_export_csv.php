<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export preparation page
- Managed by async background tasks via AJAX
- Displays real-time progress of CSV file creation
- Offers a download link once all files are ready
----------------------------------------
*/

// -----------------------------------------------
// Timestamp for the current export session

$todayTime        = new DateTime();
$today_formatted  = $todayTime->format('YmdHi');
$today_text       = $todayTime->format('d-m-Y H:i');
$today_sql        = $todayTime->format('Y-m-d H:i:s'); // Stored in DB to track export creation date


// -----------------------------------------------
// Build the output folder name for this export session

$folder_download = $today_formatted . '_csv_' . $id_user;
$chemin_folder   = DIR_WS_DATA_EXPORT . $folder_download;


// -----------------------------------------------
// Page HTML structure

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    require(DIR_WS_STRUCTURE . 'block_graph.php');
    require(DIR_WS_STRUCTURE . 'header.php');       // Top banner
    include(DIR_WS_BOX      . 'nav_accueil.php');   // Navigation menu

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                // Page title
                echo "<h1 id='h1_graph'>";
                    echo "<span>" . TEXT_EXPORT_PAGE_TITLE . "</span>";
                echo "</h1>";

                echo "<div style='float:left;width:40%;text-align:left;'>\n";

                    // ---- Compilation progress bar ----
                    echo "<p style='float:left;width:100%;font-size:14px;font-weight:bold;'>" . TEXT_EXPORT_COMPIL_LABEL . "</p>";

                    echo "<div id='barre' class='bar_create_all' style='margin-top:10px;'>";
                        echo "<div id='pourcentage_compil' style='width:0%;'></div>";
                    echo "</div>";

                    // ---- Processing log textarea ----
                    echo "<p style='float:left;width:100%;font-size:14px;font-weight:bold;margin-top:25px;'>" . TEXT_EXPORT_PROGRESS_LABEL . "</p>";
                    echo "<textarea id='fileList' style='width:98%;height:300px;margin-bottom:25px;' readonly>";
                        echo TEXT_EXPORT_TEXTAREA_DATETIME . " : " . $today_text . " \n\n";
                        echo TEXT_EXPORT_TEXTAREA_WAITING;
                    echo "</textarea>";

                    // ---- Download button (hidden until all files are ready) ----
                    echo "<div id='block_download' style='display:none;'>";

                        $lien_form = tep_href_link('data_chron.php');
                        $name_form = 'form_download';

                        echo "<form name='" . $name_form . "' id='" . $name_form . "' action='" . $lien_form . "'"
                           . " method='post' enctype='multipart/form-data' target='_blank'>";

                            echo "<input type='hidden' id='file_download' name='file_download' value='" . $folder_download . "'>";

                            $extension = 'tar';
                            if ($zip) { $extension = 'zip'; }
                            echo "<input type='hidden' id='file_extension' name='file_extension' value='" . $extension . "'>";

                            echo "<div id='button_titre' onclick=\"document.getElementById('" . $name_form . "').submit();\">";
                                echo TEXT_EXPORT_BTN_DOWNLOAD;
                            echo "</div>";

                        echo "</form>";

                    echo "</div>"; // #block_download

                echo "</div>"; // left panel

            echo "<hr>";
            echo "</div>"; // #contenu_box2

        echo "<hr>";
        echo "</div>"; // #contenu_centre

    echo "<hr>";
    echo "</div>"; // #contour_general


    // ---- Footer / copyright ----
    echo "<div id='pied_page'>";
        echo "<div id='copyright'>";
            echo "<a href='http://www.vai-natura.com' target='_blank'>" . TEXT_EXPORT_COPYRIGHT . "</a>";
        echo "</div>";
    echo "</div>";

echo "</body>";
echo "</html>";


// -----------------------------------------------
// Build the export task list
// Iterates over selected stations and their associated series
// Collects all metadata needed by async AJAX CSV generation calls

$nb_data_all = 0;
$list_station = '';

foreach ($station_chron_array as $cle_station => $typedata_array)
{
    $count = count($typedata_array);
    $list_station .= $cle_station . ',';
    $n = 0;

    foreach ($typedata_array as $typedata_chron => $sql_chron)
    {
        $n++;

        $is_first = ($n === 1);
        $is_last  = ($n === $count);

        // Resolve the series label for the output filename
        $text_chron = isset($type_chron_array[$typedata_chron]['init_type_data'])
            ? $type_chron_array[$typedata_chron]['init_type_data']
            : strtoupper($typedata_chron); // Fallback for RA, JGE, ETL, etc.

        // Build a clean filename: CODE_CHRON_StationName.csv
        $nom_station_filename = ucfirst(strtolower(nettoyerNomFichier($station_all_array[$cle_station]['nom_station'])));
        $Filename = $station_all_array[$cle_station]['code_station'] . '_' . $text_chron . '_' . $nom_station_filename . '.csv';

        // Append task metadata for this series to the AJAX task queue
        $data_info[] = [
            'Filename'        => $Filename,
            'folder_download' => addslashes($folder_download),
            'chemin_folder'   => addslashes($chemin_folder),
            'id_station'      => $cle_station,
            'code_station'    => $station_all_array[$cle_station]['code_station'],
            'nom_station'     => $station_all_array[$cle_station]['nom_station'],
            'init_chron'      => $text_chron,
            'sql_chron'       => $sql_chron,
            'nbdata_chron'    => $nbdata_chron_array[$cle_station][$typedata_chron],
            'entete_col'      => $entete_col,
            'num_chron'       => $n,
            'is_first'        => $is_first,
            'is_last'         => $is_last,
        ];

        $nb_data_all += $nbdata_chron_array[$cle_station][$typedata_chron];
    }
}

$list_station = rtrim($list_station, ','); // Remove trailing comma

// Compression task payload
$data_compress = [
    'folder_download' => addslashes($folder_download),
    'chemin_folder'   => addslashes($chemin_folder),
];


// -----------------------------------------------
// Cleanup: delete previously generated export folders
// Protects the 'temp' folder from deletion

if (is_dir(DIR_WS_DATA_EXPORT))
{
    $dossiers_proteges = ['temp']; // Folders that must never be deleted

    $sous_dossiers = glob(DIR_WS_DATA_EXPORT . '*', GLOB_ONLYDIR);

    foreach ($sous_dossiers as $sous_dossier)
    {
        $nom_dossier = basename($sous_dossier);

        if (in_array($nom_dossier, $dossiers_proteges)) { continue; }

        // Delete all files inside the subfolder
        $fichiers = glob($sous_dossier . '/*');
        foreach ($fichiers as $fichier)
        {
            if (is_file($fichier)) { unlink($fichier); }
        }

        // Remove the subfolder itself if now empty
        $fichiers_restants = glob($sous_dossier . '/*');
        if (empty($fichiers_restants) && is_dir($sous_dossier))
        {
            usleep(500000); // 500ms delay to ensure file system availability before rmdir
            rmdir($sous_dossier);
        }
    }
}
?>

<script>

    var numIteration      = 0;
    var pourcentage       = 0;
    var id_station_encours = 0;

    var idTerritoire  = <?php echo $territoire_id; ?>;
    var listStation   = '<?php echo $list_station; ?>';
    var nb_data_all   = <?php echo $nb_data_all; ?>;
    var totalTime     = 0;

    // PHP translation constants injected into JS scope
    var LANG_EXPORT = {
        allDone:     "<?= TEXT_EXPORT_JS_ALL_DONE ?>",
        sec:         "<?= TEXT_EXPORT_JS_SEC ?>",
        nbData:      "<?= TEXT_EXPORT_JS_NB_DATA ?>",
        compressing: "<?= TEXT_EXPORT_JS_COMPRESSING ?>",
        time:        "<?= TEXT_EXPORT_JS_TIME ?>"
    };

    var fileList = document.getElementById('fileList');

    // Retrieve PHP-generated task queue and compression payload
    var jsonDataInfo     = <?php echo json_encode($data_info); ?>;
    var jsonDataCompress = <?php echo json_encode($data_compress); ?>;

    var cheminFolder = '<?php echo $chemin_folder; ?>';


    // -----------------------------------------------
    // AJAX: Sequential CSV file generation
    // Processes one series per call, then recurses to the next

    function executeAjaxExportCsv()
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/export/process_export_csv.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var reponse = JSON.parse(xhr.responseText);
                totalTime += parseFloat(reponse);

                var nbdata_chron_formatted = formatNumberThousandsSeparator(jsonDataInfo[numIteration]['nbdata_chron']);

                // Append result line to the progress log
                fileList.value += jsonDataInfo[numIteration]['init_chron'];
                fileList.value += " - " + LANG_EXPORT.time + " : " + reponse + " " + LANG_EXPORT.sec
                                + " - " + LANG_EXPORT.nbData + " : " + nbdata_chron_formatted + " \n";

                // Auto-scroll textarea to bottom
                fileList.scrollTop = fileList.scrollHeight;

                // Update compilation progress bar
                pourcentage += jsonDataInfo[numIteration]['nbdata_chron'] / nb_data_all;
                document.getElementById('pourcentage_compil').style.width = (pourcentage * 100) + '%';

                if (numIteration < jsonDataInfo.length - 1)
                {
                    numIteration++;         // Advance to the next series
                    executeAjaxExportCsv(); // Recurse: process next series
                }
                else
                {
                    // All files generated: display summary and launch compression
                    fileList.value += "\n";
                    fileList.value += LANG_EXPORT.allDone
                                    + " : " + Math.round(totalTime) + " " + LANG_EXPORT.sec
                                    + " - " + LANG_EXPORT.nbData + " : " + formatNumberThousandsSeparator(nb_data_all);
                    fileList.value += "\n\n--\n";
                    fileList.value += LANG_EXPORT.compressing;

                    fileList.scrollTop = fileList.scrollHeight;

                    lancerCompressionDossier();
                }
            }
        };

        // Prepend station header line when switching to a new station
        if (numIteration == 0) { fileList.value += "\n"; }
        if (jsonDataInfo[numIteration]['id_station'] !== id_station_encours)
        {
            fileList.value += "\n" + jsonDataInfo[numIteration]['code_station']
                            + ' - ' + jsonDataInfo[numIteration]['nom_station'] + "\n";
            id_station_encours = jsonDataInfo[numIteration]['id_station'];
        }

        xhr.send(JSON.stringify(jsonDataInfo[numIteration]));
    }


    // -----------------------------------------------
    // AJAX: Download station information as XLS
    // Runs in parallel with CSV generation (fire-and-forget)

    function downloadStation_xls()
    {
        var dataToSend = {
            idTerritoire: idTerritoire,
            listStation:  listStation,
            cheminFolder: cheminFolder,
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/export/process_station_download_xls.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify(dataToSend));
    }


    // -----------------------------------------------
    // Entry point: launch station XLS download and CSV generation in parallel

    downloadStation_xls();
    executeAjaxExportCsv();


    // -----------------------------------------------
    // AJAX: Compress the generated folder into a TAR archive

    function lancerCompressionDossier()
    {
        var xhrCompress = new XMLHttpRequest();
        xhrCompress.open('POST', 'include/structure/export/process_compress_tar.php', true);
        xhrCompress.setRequestHeader('Content-Type', 'application/json');

        xhrCompress.onreadystatechange = function()
        {
            if (xhrCompress.readyState === 4 && xhrCompress.status === 200)
            {
                var reponse = JSON.parse(xhrCompress.responseText);

                // Append compression result to the progress log
                fileList.value += reponse;
                fileList.scrollTop = fileList.scrollHeight;

                saveFileResult();
            }
        };

        xhrCompress.send(JSON.stringify(jsonDataCompress));
    }


    // -----------------------------------------------
    // AJAX: Save the progress log content to a text file on the server

    function saveFileResult()
    {
        var jsonTextResult = {
            text_result:     fileList.value,
            id_user:         '<?php echo $id_user; ?>',
            date_export:     '<?php echo $today_sql; ?>',
            folder_download: '<?php echo addslashes($folder_download); ?>',
            chemin_folder:   '<?php echo $chemin_folder; ?>',
        };

        var xhrResult = new XMLHttpRequest();
        xhrResult.open('POST', 'include/structure/export/process_export_result.php', true);
        xhrResult.setRequestHeader('Content-Type', 'application/json');

        xhrResult.onreadystatechange = function()
        {
            if (xhrResult.readyState === 4 && xhrResult.status === 200)
            {
                // All done: reveal the download button
                document.getElementById('block_download').style.display = 'block';
            }
        };

        xhrResult.send(JSON.stringify(jsonTextResult));
    }

</script>

<?php

// -----------------------------------------------
// Session cleanup

if ($autorisation) { regenerer_id($sql_link); }

tep_db_close($sql_link);
tep_session_end();

?>
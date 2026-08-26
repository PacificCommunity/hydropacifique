<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Platform parameter export (and import) page
Lets the user select which configuration datasets to include in the
export, then calls process_hp_param_xls.php via AJAX to generate an
XLSX file on the server and triggers a browser download.

Exportable datasets (checkboxes):
  zonegeo   — Geographic regions, communes, hydro regions, rivers
  typechron — Time-series type definitions
  st_nature — Station type / nature codes
  codequal  — Data quality codes
  eqjge     — Gauging equipment (propellers, current meters, weights)
----------------------------------------
*/

require('include/application_top.php');

// -----------------------------------------------
// Initialisation

$message_info   = '';
$data_step      = 1;
$verif_form     = 0;
$row            = 0;
$entete         = 0;

$select_station_tab = [];

$today       = date('d-m-Y');
$year_today  = date('Y');
$month_today = date('m');
$date_format = 'd-m-Y';

// Unique export identifier (used as a prefix for temp filenames)
$timestamp = time();
$id_export = 'HP_ExportParam_' . date('YmdHis', $timestamp);


// -----------------------------------------------
// Page output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    echo "<div id='contenu_info' style='display:none;'></div>";

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                echo "<h1>";
                    echo "<span>" . TEXT_EX_PAGE_TITLE . "</span>";
                echo "</h1>";

                echo "<div style='float:left;width:30%;'>\n";
                    echo "<div id='boxpopup' class='select-top' style='width:100%;padding:10px;'>\n";

                        // ---- Dataset checkboxes ----

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' value='zonegeo' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_EX_CHK_ZONEGEO . "</span>";
                        echo "</p>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' value='typechron' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_EX_CHK_TYPECHRON . "</span>";
                        echo "</p>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' value='st_nature' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_EX_CHK_STNATURE . "</span>";
                        echo "</p>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' value='codequal' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_EX_CHK_CODEQUAL . "</span>";
                        echo "</p>";

                        echo "<p style='margin-left:1%;'>";
                            echo "<input type='checkbox' value='eqjge' checked>";
                            echo "<span style='margin-left:5px;font-size:11px;font-weight:normal;'>"
                               . TEXT_EX_CHK_EQJGE . "</span>";
                        echo "</p>";

                        // ---- Export button ----
                        echo "<input type='submit' class='button_export' name='button_export'"
                           . " id='button_export_param'"
                           . " style='width:200px;margin-left:1.5%'"
                           . " value='" . TEXT_EX_BTN_EXPORT . "'"
                           . " onclick='downloadParam_xls()';"
                           . " />";

                        // ---- Spinner (shown while the XLSX is being generated) ----
                        echo "<div id='wait_file' style='float:right;text-align:center;margin-top:10px;display:none;'>";
                            echo "<img src='" . DIR_WS_IMG . "wait.gif' style='width:15px;'>";
                            echo "<span style='margin-left:10px;font-size:11px;font-weight:bold;color:#000;'>"
                               . TEXT_EX_WAIT_FILE . "</span>";
                        echo "</div>\n";

                    echo "</div>\n";
                echo "</div>\n";

            echo "</div>"; // contenu_box2
        echo "</div>"; // contenu_centre
    echo "</div>"; // contour_general

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    var idTerritoire       = <?php echo $territoire_id; ?>;
    var buttonExportParam  = document.getElementById('button_export_param');
    var contenuInfo        = document.getElementById('contenu_info');
    var waitFile           = document.getElementById('wait_file');


    // -----------------------------------------------
    // downloadParam_xls()
    // Collects the checked parameter categories, posts them to
    // process_hp_param_xls.php, then triggers a browser download of the
    // generated XLSX file.

    function downloadParam_xls()
    {
        buttonExportParam.style.display = 'none';
        waitFile.style.display          = 'block';

        // Step 1: collect checked categories
        var checkboxes     = document.querySelectorAll("input[type='checkbox']");
        var selectedParam  = [];

        checkboxes.forEach(function(checkbox)
        {
            if (checkbox.checked) { selectedParam.push(checkbox.value); }
        });

        if (selectedParam.length === 0)
        {
            contenuInfo.innerHTML     = <?php echo json_encode(TEXT_EX_ERR_NO_PARAM); ?>;
            contenuInfo.style.border  = '2px solid #930000';
            contenuInfo.style.display = 'block';
            waitFile.style.display    = 'none';
            return;
        }

        // Step 2: send category list to the server
        var cheminFolder = 'data/export/temp';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/export/process_hp_param_xls.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                if (xhr.status === 200)
                {
                    var r = JSON.parse(xhr.responseText);

                    if (r['statut'])
                    {
                        // Trigger download via a temporary invisible link
                        var link      = document.createElement('a');
                        link.href     = cheminFolder + '/' + r['xlsFile'];
                        link.download = r['xlsFile'];
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                    else
                    {
                        contenuInfo.innerHTML     = <?php echo json_encode(TEXT_EX_ERR_GENERATE); ?>;
                        contenuInfo.style.border  = '2px solid #930000';
                        contenuInfo.style.display = 'block';
                    }
                }
                else
                {
                    contenuInfo.innerHTML     = <?php echo json_encode(TEXT_EX_ERR_SERVER); ?>;
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }

                waitFile.style.display          = 'none';
                buttonExportParam.style.display = 'block';
            }
        };

        xhr.send(JSON.stringify({
            idTerritoire : idTerritoire,
            listParam    : selectedParam.join(','),
            cheminFolder : cheminFolder
        }));
    }

</script>

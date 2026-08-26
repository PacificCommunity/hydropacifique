<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station access tab
- Access information form (owner, contact, access details)
- Access map upload (jpg/jpeg/png, max 2 MB)
----------------------------------------
*/

// Default empty access record
$access_array = [
    'proprietaire'     => '',
    'contact_nom'      => '',
    'contact_phone'    => '',
    'contact_mail'     => '',
    'contact_adresse'  => '',
    'contact_bp'       => '',
    'contact_cp'       => '',
    'contact_commune'  => 0,
    'info_access'      => '',
    'pedestre_access'  => 0,
    'time_access'      => '',
    'difficulty_access'=> '',
    'remarque_access'  => '',
];

// Query: load existing access record for this station
$sql_access   = "SELECT DISTINCT proprietaire,
                        contact_nom, contact_phone, contact_mail, contact_adresse,
                        contact_bp, contact_cp, contact_commune,
                        info_access, pedestre_access, time_access, difficulty_access, remarque_access
                 FROM " . TABLE_STATION_ACCESS . "
                 WHERE id_station = " . $id_station;
$access_query = tep_db_query($sql_link, $sql_access);

while ($access_tab = tep_db_fetch_array($access_query))
{
    $access_array = [
        'proprietaire'     => html_entity_decode($access_tab['proprietaire']      ?? ''),
        'contact_nom'      => html_entity_decode($access_tab['contact_nom']       ?? ''),
        'contact_phone'    => html_entity_decode($access_tab['contact_phone']     ?? ''),
        'contact_mail'     => html_entity_decode($access_tab['contact_mail']      ?? ''),
        'contact_adresse'  => html_entity_decode($access_tab['contact_adresse']   ?? ''),
        'contact_bp'       => html_entity_decode($access_tab['contact_bp']        ?? ''),
        'contact_cp'       => html_entity_decode($access_tab['contact_cp']        ?? ''),
        'contact_commune'  => html_entity_decode($access_tab['contact_commune']   ?? ''),
        'info_access'      => html_entity_decode($access_tab['info_access']       ?? ''),
        'pedestre_access'  => html_entity_decode($access_tab['pedestre_access']   ?? ''),
        'time_access'      => html_entity_decode($access_tab['time_access']       ?? ''),
        'difficulty_access'=> html_entity_decode($access_tab['difficulty_access'] ?? ''),
        'remarque_access'  => html_entity_decode($access_tab['remarque_access']   ?? ''),
    ];
}

$check_pedestre = ($access_array['pedestre_access'] == 1) ? 'checked' : '';

// Build municipality options for select
$commune_options = "<option value=''></option>";
if (isset($commune_array))
{
    //$commune_options .= "<option value='0'>-</option>";
    foreach ($commune_array as $key_commune => $value_commune)
    {
        $selected        = ($access_array['contact_commune'] == $key_commune) ? 'selected' : '';
        $commune_options .= "<option value='" . $key_commune . "' " . $selected . ">"
                          . $value_commune . "</option>";
    }
}

// ---- Main container ----
echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>";

    // ---- Export PDF button (uniform text-button style, like the stats module) ----
    // Distinct id (img_pdf_access) to avoid clashing with the Metadata button (img_pdf).
    echo "<div style='float:left;margin-top:20px;'>";
        echo "<button type='button' class='hp-btn' id='img_pdf_access'"
           . " title='" . TEXT_ACCESS_EXPORT_BTN . "'>"
           . "<span class='ico'>&#128196;</span> PDF</button>";
    echo "</div>";

    echo "<hr>";

    echo "<div style='width:99%;padding-top:5px;padding-bottom:10px;'>";

        // ---- Access form card ----
        echo "<div style='float:left;width:720px;margin-right:30px;padding:20px;"
           . "border:1px solid #000;border-radius:4px;background-color:#fff;"
           . "box-shadow:0 2px 8px rgba(0, 0, 0, 0.08);'>";

            echo "<p class='titre_box' style='font-size:16px;'>" . TEXT_ACCESS_FORM_TITLE . "</p>";

            echo "<div style='float:left;margin-top:10px;'>";

                echo "<table>";
                    // Owner
                    echo "<tr>";
                        echo "<td style='width:130px;'><h2>" . TEXT_ACCESS_OWNER . "</h2></td>";
                        echo "<td><input name='proprietaire' id='proprietaire' value='" . $access_array['proprietaire'] . "' style='width:200px;' type='text'></td>";
                    echo "</tr>";

                    // Contact name
                    echo "<tr>";
                        echo "<td style='width:130px;'><h2>" . TEXT_ACCESS_CONTACT_NAME . "</h2></td>";
                        echo "<td><input name='contact_nom' id='contact_nom' value='" . $access_array['contact_nom'] . "' style='width:200px;' type='text'></td>";
                    echo "</tr>";

                    // Phone
                    echo "<tr>";
                        echo "<td style='width:130px;'><h2>" . TEXT_ACCESS_CONTACT_PHONE . "</h2></td>";
                        echo "<td><input name='contact_phone' id='contact_phone' value='" . $access_array['contact_phone'] . "' style='width:200px;' type='text'></td>";
                    echo "</tr>";

                    // Email
                    echo "<tr>";
                        echo "<td style='width:130px;'><h2>" . TEXT_ACCESS_CONTACT_EMAIL . "</h2></td>";
                        echo "<td><input name='contact_mail' id='contact_mail' value='" . $access_array['contact_mail'] . "' style='width:200px;' type='text'></td>";
                    echo "</tr>";

                echo "</table>";

            echo "</div>\n";

            echo "<div style='float:left;margin-top:10px;margin-left:50px;'>";

                echo "<table>";
                    // Address
                    echo "<tr>";
                        echo "<td style='width:80px;'><h2>" . TEXT_ACCESS_CONTACT_ADDRESS . "</h2></td>";
                        echo "<td>";
                        
                            echo "<textarea name='contact_adresse' id='contact_adresse' style='width:200px;height:40px;font-size:12px;'>";
                                echo $access_array['contact_adresse'];
                            echo  "</textarea>";
                    
                        echo "</td>";
                    echo "</tr>";

                    // PO box
                    echo "<tr>";
                        echo "<td style='width:80px;'><h2>" . TEXT_ACCESS_CONTACT_PO_BOX . "</h2></td>";
                        echo "<td><input name='contact_bp' id='contact_bp' value='" . $access_array['contact_bp'] . "' style='width:100px;' type='text'></td>";
                    echo "</tr>";

                    // Postcode
                    echo "<tr>";
                        echo "<td style='width:80px;'><h2>" . TEXT_ACCESS_CONTACT_POSTCODE . "</h2></td>";
                        echo "<td><input name='contact_cp' id='contact_cp' value='" . $access_array['contact_cp'] . "' style='width:100px;' type='text'></td>";
                    echo "</tr>";

                    // Municipality
                    echo "<tr>";
                        echo "<td style='width:80px;'><h2>" . TEXT_ACCESS_CONTACT_COMMUNE . "</h2></td>";
                        echo "<td>";
                            echo "<select name='contact_commune' id='contact_commune' style='width:212px;'>";
                                echo $commune_options;
                            echo "</select>";
                        echo "</td>";
                    echo "</tr>";
                    echo "</tr>";

                echo "</table>";

            echo "</div>\n";

            // ---- Access info + pedestrian + time ----
            echo "<div style='float:left;margin-top:20px;'>";

                echo "<div id='boite_small' style='margin-right:35px;'>";
                    echo "<h2 style='float:left;width:200px;padding-top:5px;'>" . TEXT_ACCESS_INFO . "</h2>";
                    echo "<br>";
                    echo "<textarea name='info_access' id='info_access'"
                       . " style='width:320px;height:60px;font-size:13px;'>"
                       . $access_array['info_access']
                       . "</textarea>";
                echo "</div>";

                echo "<div id='boite_small' style='margin-top:25px;'>";
                    echo "<h2 style='float:left;width:110px;padding-top:5px;'>" . TEXT_ACCESS_PEDESTRIAN . "</h2>";
                    echo "<input type='checkbox' name='pedestre_access' id='pedestre_access'"
                       . " style='float:left;width:20px;height:20px;margin:0;' " . $check_pedestre . ">";
                    echo "<hr>";
                    echo "<h2 style='float:left;width:110px;padding-top:5px;'>" . TEXT_ACCESS_TIME . "</h2>";
                    echo "<input name='time_access' id='time_access'"
                       . " value='" . $access_array['time_access'] . "'"
                       . " class='input_texte' style='width:100px;' type='text'>";
                echo "</div>";

            echo "</div>";

            // ---- Difficulties + remarks ----
            echo "<div style='float:left;margin-top:20px;'>";

                echo "<div id='boite_small'>";
                    echo "<h2 style='float:left;width:200px;padding-top:5px;'>" . TEXT_ACCESS_DIFFICULTY . "</h2>";
                    echo "<br>";
                    echo "<textarea name='difficulty_access' id='difficulty_access'"
                       . " style='width:320px;height:60px;font-size:13px;'>"
                       . $access_array['difficulty_access']
                       . "</textarea>";
                echo "</div>";

                echo "<div id='boite_small'>";
                    echo "<h2 style='float:left;width:200px;padding-top:5px;'>" . TEXT_ACCESS_REMARKS . "</h2>";
                    echo "<br>";
                    echo "<textarea name='remarque_access' id='remarque_access'"
                       . " style='width:320px;height:60px;font-size:13px;'>"
                       . $access_array['remarque_access']
                       . "</textarea>";
                echo "</div>";

            echo "</div>";

        echo "</div>"; // end access form card

        // ---- Access map upload card ----
        echo "<div style='overflow:hidden;padding:20px;padding-top:15px;"
           . "border:1px solid #000;border-radius:4px;background-color:#fff;"
           . "box-shadow:0 2px 8px rgba(0, 0, 0, 0.08);'>";

            echo "<p class='titre_box' style='font-size:16px;'>" . TEXT_ACCESS_PLAN_TITLE . "</p>";

            echo "<p style='float:left;width:100%;margin-top:10px;margin-bottom:3px;color:#000;font-size:13px;'>";
                echo TEXT_ACCESS_PLAN_UPLOAD_LABEL . "<br>";
                echo TEXT_ACCESS_PLAN_UPLOAD_SIZE;
            echo "</p>";

            echo "<hr>";

            echo "<div>";
                echo "<input type='file' id='file_photo_access' name='file_photo_access'"
                   . " style='float:left;margin-right:20px;background-color:#fff;'>";
                echo "<button id='new_photo_access' class='zoom_graph'"
                   . " style='width:150px;margin-left:35px;padding:8px 5px;display:block;'>";
                    echo TEXT_ACCESS_PLAN_SAVE_BTN;
                echo "</button>";
                echo "<div id='plan_wait' style='display:none;'>";
                    echo "<img src='" . DIR_WS_IMG . "wait.gif'"
                       . " style='float:left;width:15px;margin:0 15px 0 5px;'>";
                    echo "<span style='float:left;'>" . TEXT_ACCESS_PLAN_LOADING . "</span>";
                echo "</div>";
            echo "</div>";

            echo "<hr>";

            echo "<div id='tab_plan'></div>";

        echo "</div>"; // end map upload card

    echo "</div>";

echo "</div>"; // end main container
?>

<script>

    var id_station   = <?php echo $id_station; ?>;
    var code_station = <?php echo json_encode($code_station); ?>;

    var filePhotoAccess = document.getElementById('file_photo_access');
    var boutonLoadPlan  = document.getElementById('new_photo_access');
    var waitPlan        = document.getElementById('plan_wait');
    var tab_plan        = document.getElementById('tab_plan');
    var contenuInfo     = document.getElementById('contenu_info');


    // -----------------------------------------------
    // Generate and open the access PDF sheet

    var buttonPdfAccess = document.getElementById('img_pdf_access');

    // JS error strings injected from PHP constants
    var LANG_ACCESS_PDF = {
        errGenerate : '<?= TEXT_ACCESS_PDF_JS_ERR_GENERATE ?>',
        errServer   : '<?= TEXT_ACCESS_PDF_JS_ERR_SERVER ?>'
    };

    if (buttonPdfAccess)
    {
        buttonPdfAccess.addEventListener('click', function()
        {
            var prevHtml = buttonPdfAccess.innerHTML;
            buttonPdfAccess.disabled  = true;
            buttonPdfAccess.innerHTML = "<span class='hp-btn-spinner'></span> PDF";

            function restoreBtn() {
                buttonPdfAccess.disabled  = false;
                buttonPdfAccess.innerHTML = prevHtml;
            }

            var dataToSend = {
                idStation      : id_station,
                id_user        : <?php echo (int) $id_user; ?>,
                territoire_nom : <?php echo json_encode($territoire_nom); ?>,
                timezone_php   : <?php echo json_encode($timezone_php); ?>
            };

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/station/process_station_access_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4)
                {
                    restoreBtn();

                    if (xhr.status === 200)
                    {
                        var jsonResponse = JSON.parse(xhr.responseText);

                        if (jsonResponse['status'] === 'success')
                        {
                            window.open('data/pdf/' + jsonResponse['fileName'], '_blank');
                        }
                        else
                        {
                            contenuInfo.innerHTML     = jsonResponse['msg_info'] || LANG_ACCESS_PDF.errGenerate;
                            contenuInfo.style.border  = '2px solid #930000';
                            contenuInfo.style.display = 'block';
                        }
                    }
                    else
                    {
                        contenuInfo.innerHTML     = LANG_ACCESS_PDF.errServer;
                        contenuInfo.style.border  = '2px solid #930000';
                        contenuInfo.style.display = 'block';
                    }
                }
            };

            xhr.send(JSON.stringify(dataToSend));
        });
    }


    // -----------------------------------------------
    // Upload a new access map photo

    function new_photo_access()
    {
        boutonLoadPlan.style.display = 'none';
        waitPlan.style.display       = 'block';

        var formData = new FormData();
        formData.append('id_station',        id_station);
        formData.append('file_photo_access', filePhotoAccess.files[0]);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_newphoto_access.php', true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);

                contenuInfo.innerHTML     = jsonResponse['message_info'];
                contenuInfo.style.border  = jsonResponse['success']
                    ? '2px solid #09886d'  // green: success
                    : '2px solid #930000'; // red: error
                contenuInfo.style.display = 'block';

                // Reload the plan only when the file was actually saved
                if (jsonResponse['success']) { load_plan(); }
            }
            waitPlan.style.display       = 'none';
            boutonLoadPlan.style.display = 'block';
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Delete confirmation popup (Yes/No)
    // Built once and reused; styled to match list_stations.php

    // JS strings injected from PHP translation constants
    var LANG_PLAN = {
        confirmTitle : '<?= TEXT_PLAN_DEL_CONFIRM_TITLE ?>',
        confirmMsg   : '<?= TEXT_PLAN_DEL_CONFIRM_MSG ?>',
        btnCancel    : '<?= TEXT_PLAN_DEL_BTN_CANCEL ?>',
        btnConfirm   : '<?= TEXT_PLAN_DEL_BTN_CONFIRM ?>'
    };

    var boxDelPlan = document.createElement('div');
    boxDelPlan.id = 'box_del_plan';
    boxDelPlan.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;' +
        'background:rgba(0,0,0,0.45);z-index:9999;display:none;';

    boxDelPlan.innerHTML =
        "<div style='position:relative;width:460px;margin:8% auto 0 auto;" +
            "background-color:#FBF9F1;border-radius:6px;overflow:hidden;" +
            "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>" +

            // Red header
            "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;" +
                "color:#fff;background-color:#a52834;'>" + LANG_PLAN.confirmTitle + "</p>" +

            "<div style='padding:18px 22px;'>" +
                "<p style='margin:0 0 18px 0;font-size:14px;color:#333;'>" +
                    LANG_PLAN.confirmMsg + "</p>" +

                "<div style='display:flex;justify-content:flex-end;gap:12px;'>" +
                    "<input type='button' id='cancel_del_plan' class='button_close'" +
                        " value='" + LANG_PLAN.btnCancel + "' style='width:120px;'>" +
                    "<input type='button' id='ok_del_plan' class='button'" +
                        " value='" + LANG_PLAN.btnConfirm + "' style='width:120px;'>" +
                "</div>" +
            "</div>" +
        "</div>";

    document.body.appendChild(boxDelPlan);

    var okDelPlan     = document.getElementById('ok_del_plan');
    var cancelDelPlan = document.getElementById('cancel_del_plan');


    // Open the popup (called from the [delete] link on the plan)
    function del_plan()
    {
        boxDelPlan.style.display = 'block';
    }

    // Close / reset the popup
    function closeDelPlan()
    {
        boxDelPlan.style.display = 'none';
    }

    // Confirm: send the AJAX delete request, then reload the plan panel
    function confirmDelPlan()
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_delplan.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                contenuInfo.innerHTML     = xhr.responseText;
                contenuInfo.style.border  = '2px solid #09886d';
                contenuInfo.style.display = 'block';
                closeDelPlan();
                load_plan();
            }
        };

        xhr.send(JSON.stringify({ code_station: code_station }));
    }

    okDelPlan.addEventListener('click', confirmDelPlan);
    cancelDelPlan.addEventListener('click', closeDelPlan);

    // Close on click outside the popup card / Escape key
    boxDelPlan.addEventListener('click', function(event)
    {
        if (event.target === boxDelPlan) { closeDelPlan(); }
    });
    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && boxDelPlan.style.display === 'block') { closeDelPlan(); }
    });


    // -----------------------------------------------
    // Load and render the access map panel

    function load_plan()
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_loadplan.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                tab_plan.innerHTML = JSON.parse(xhr.responseText)['tab_html'];
            }
        };

        xhr.send(JSON.stringify({ code_station: code_station }));
    }


    // -----------------------------------------------
    // Button event + initial load

    boutonLoadPlan.addEventListener('click', function(event)
    {
        event.preventDefault();
        new_photo_access();
    });

    load_plan();


    // -----------------------------------------------
    // select2 for municipality dropdown

    $(document).ready(function()
    {
        $('#contact_commune').select2({
            placeholder : '<?php echo TEXT_ACCESS_SELECT2_COMMUNE; ?>',
            allowClear  : true,
        });
    });

</script>
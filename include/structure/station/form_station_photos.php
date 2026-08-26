<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station photo gallery tab
- Display existing photos
- Upload a new photo
- Delete a photo
----------------------------------------
*/

$row = 0;

echo "<div id='onglet_contenu' style='overflow-y:auto;height:calc(100vh - 200px);padding:0 20px;'>";

    echo "<div id='boite1' style='margin:0;margin-top:15px;'>\n";

        echo "<div style='
                    width:640px;
                    padding:10px;
                    border:1px solid #555;
                    border-radius:4px;
                    background-color:#fff;
                    box-shadow:0 2px 8px rgba(0, 0, 0, 0.08);'>";

            // ---- Upload instructions ----
            echo "<p style='float:left;width:100%;margin-bottom:3px;font-weight:bold;color:#000;font-size:13px;'>";
                echo TEXT_PHOTOS_UPLOAD_LABEL;
                echo "<br>";
                echo TEXT_PHOTOS_UPLOAD_SIZE;
            echo "</p>";

            // ---- Description field ----
            echo "<div id='boite_small' style='width:350px;'>\n";
                echo "<h2>".TEXT_PHOTOS_DESC."</h2>\n";
                echo "<input id='desc_photo' value='' class='input_texte_300' type='text'>";
            echo "</div>\n";

            // ---- Photo date field ----
            echo "<div id='boite_small' style='width:220px;margin-right:0;'>\n";
                echo "<h2>".TEXT_PHOTOS_DATE."</h2>\n";
                echo "<input class='datepicker' style='width:80px;'
                        id='date_photo' type='text'
                        onfocus='initDatepickers(this)'
                        placeholder='".TEXT_PHOTOS_DATE_PLACEHOLDER."'>";
            echo "</div>\n";

            echo "<hr>\n";

            echo "<input type='file' id='file_photo' name='file_photo' style='background-color:#fff;'>";

            echo "<div style='clear:both;height:15px;'></div>";

            // ---- Save button (shown after file selection) ----
            echo "<button id='new_photo' class='zoom_graph' style='width:200px;padding:8px 5px;display:block;'>";
                echo TEXT_PHOTOS_SAVE_BTN;
            echo "</button>\n";

            // ---- Loading spinner (shown during upload) ----
            echo "<button id='load_wait' class='zoom_graph' style='width:210px;padding:8px 5px;display:none;'>";
                echo "<img src='".DIR_WS_IMG."wait.gif' style='float:left;width:15px;margin:0 15px 0 5px;'>";
                echo "<span style='float:left;'>".TEXT_PHOTOS_LOADING."</span>";
            echo "</button>\n";

        echo "<hr>\n";
        echo "</div>";

    echo "<hr>\n";
    echo "</div>\n";

    // ---- Photo gallery container (populated via AJAX) ----
    echo "<div id='tab_photos'>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";

?>

<style>
    /* Photo gallery grid: homogeneous, responsive cards */
    .photos-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));
        gap:16px;
        padding:5px 0;
        align-items:start;
    }

    /* Each card: thumbnail + caption */
    .photo-card {
        border:1px solid #ddd;
        border-radius:6px;
        overflow:hidden;
        background:#fff;
        box-shadow:0 1px 4px rgba(0,0,0,0.08);
        transition:box-shadow 0.15s ease, transform 0.15s ease;
    }
    .photo-card:hover {
        box-shadow:0 4px 14px rgba(0,0,0,0.18);
        transform:translateY(-2px);
    }

    /* Fixed-ratio thumbnail area with cropping */
    .photo-thumb {
        position:relative;
        width:100%;
        aspect-ratio:4 / 3;
        background:#f4f4f4;
        overflow:hidden;
    }
    .photo-thumb img {
        width:100%;
        height:100%;
        object-fit:contain;
        display:block;
        cursor:pointer;
    }

    /* Placeholder when the file is missing on disk */
    .photo-missing {
        display:flex;
        align-items:center;
        justify-content:center;
        width:100%;
        height:100%;
        color:#999;
        font-size:12px;
        font-style:italic;
    }

    /* Delete pill, overlaid on the thumbnail top-right */
    .photo-delete {
        position:absolute;
        top:8px;
        right:8px;
        width:28px;
        height:28px;
        padding:0;
        border:none;
        border-radius:50%;
        background:rgba(0,0,0,0.45);
        color:#fff;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:0;
        transition:opacity 0.15s ease, background 0.15s ease;
    }
    .photo-card:hover .photo-delete { opacity:1; }
    .photo-delete:hover { background:#a52834; }
    .photo-delete svg { width:15px; height:15px; }

    /* Caption under the thumbnail */
    .photo-caption {
        padding:8px 12px;
        font-size:12px;
        color:#333;
    }
    .photo-caption p { margin:2px 0; }
    .photo-caption-label { font-weight:bold; }

    /* Keep the "Add a picture" placeholder the same size as a photo card.
       The external .photos-empty class forces width:350px / height:379px /
       float / margins; we neutralise all of that inside the grid so the
       placeholder behaves like a normal grid cell. */
    .photos-grid .photos-empty {
        float:none;
        width:auto;
        height:auto;
        min-height:0;
        margin:0;
        aspect-ratio:4 / 3;
        box-sizing:border-box;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:12px;
        padding:20px 10px;
    }
</style>

<script>

    var id_station  = <?php echo $id_station; ?>;

    var filePhoto   = document.getElementById('file_photo');
    var loadButton  = document.getElementById('new_photo');
    var waitPhoto   = document.getElementById('load_wait');
    var contenuInfo = document.getElementById('contenu_info');
    var tab_photos  = document.getElementById('tab_photos');


    // -----------------------------------------------
    // Show save button once a file is selected

    filePhoto.addEventListener('change', function()
    {
        loadButton.style.display = 'block';
    });


    // -----------------------------------------------
    // Upload a new photo

    function new_photo(id_station)
    {
        loadButton.style.display = 'none';
        waitPhoto.style.display  = 'block';

        var formData = new FormData();
        formData.append('id_station',  id_station);
        formData.append('desc_photo',  document.getElementById('desc_photo').value);
        formData.append('date_photo',  document.getElementById('date_photo').value);
        formData.append('file_photo',  filePhoto.files[0]);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_newphoto.php', true);

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

                // Reload the gallery only when a photo was actually saved
                if (jsonResponse['success']) { load_photos(id_station); }
            }
            waitPhoto.style.display  = 'none';
            loadButton.style.display = 'block';
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Load and render the photo gallery

    function load_photos(id_station)
    {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_loadphotos.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                tab_photos.innerHTML = JSON.parse(xhr.responseText)['tab_html'];
            }
        };

        xhr.send(JSON.stringify({ id_station: id_station }));
    }


    // -----------------------------------------------
    // Delete confirmation popup (Yes/No)
    // Built once and reused; styled to match list_stations.php

    // JS strings injected from PHP translation constants
    var LANG_PHOTOS = {
        confirmTitle : '<?= TEXT_PHOTOS_DEL_CONFIRM_TITLE ?>',
        confirmMsg   : '<?= TEXT_PHOTOS_DEL_CONFIRM_MSG ?>',
        photoLabel   : '<?= TEXT_PHOTOS_DEL_PHOTO_LABEL ?>',
        btnCancel    : '<?= TEXT_PHOTOS_DEL_BTN_CANCEL ?>',
        btnConfirm   : '<?= TEXT_PHOTOS_DEL_BTN_CONFIRM ?>'
    };

    // Holds the photo id awaiting deletion confirmation
    var pendingDeletePhoto = null;

    var boxDelPhoto = document.createElement('div');
    boxDelPhoto.id = 'box_del_photo';
    boxDelPhoto.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;' +
        'background:rgba(0,0,0,0.45);z-index:9999;display:none;';

    boxDelPhoto.innerHTML =
        "<div style='position:relative;width:460px;margin:8% auto 0 auto;" +
            "background-color:#FBF9F1;border-radius:6px;overflow:hidden;" +
            "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>" +

            // Red header
            "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;" +
                "color:#fff;background-color:#a52834;'>" + LANG_PHOTOS.confirmTitle + "</p>" +

            "<div style='padding:18px 22px;'>" +
                "<p style='margin:0 0 14px 0;font-size:14px;color:#333;'>" +
                    LANG_PHOTOS.confirmMsg + "</p>" +

                // Targeted photo highlighted block
                "<div style='border-left:4px solid #a52834;background-color:#fbeaec;" +
                    "padding:10px 14px;margin-bottom:18px;'>" +
                    "<span style='font-size:14px;font-weight:bold;color:#333;'>" +
                        LANG_PHOTOS.photoLabel + " : </span>" +
                    "<span id='del_photo_name' style='font-size:14px;color:#333;'></span>" +
                "</div>" +

                "<div style='display:flex;justify-content:flex-end;gap:12px;'>" +
                    "<input type='button' id='cancel_del_photo' class='button_close'" +
                        " value='" + LANG_PHOTOS.btnCancel + "' style='width:120px;'>" +
                    "<input type='button' id='ok_del_photo' class='button'" +
                        " value='" + LANG_PHOTOS.btnConfirm + "' style='width:120px;'>" +
                "</div>" +
            "</div>" +
        "</div>";

    document.body.appendChild(boxDelPhoto);

    var delPhotoName   = document.getElementById('del_photo_name');
    var okDelPhoto     = document.getElementById('ok_del_photo');
    var cancelDelPhoto = document.getElementById('cancel_del_photo');


    // Open the popup for a given photo (called from the overlay delete pill)
    function del_photos(id_photo, desc)
    {
        pendingDeletePhoto    = id_photo;
        delPhotoName.textContent = desc || '';
        boxDelPhoto.style.display = 'block';
    }

    // Close / reset the popup
    function closeDelPhoto()
    {
        boxDelPhoto.style.display = 'none';
        pendingDeletePhoto = null;
    }

    // Confirm: send the AJAX delete request, then reload the gallery
    function confirmDelPhoto()
    {
        if (pendingDeletePhoto === null) { return; }

        var id_photo = pendingDeletePhoto;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/station/process_delphoto.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                contenuInfo.innerHTML     = xhr.responseText;
                contenuInfo.style.border  = '2px solid #09886d';
                contenuInfo.style.display = 'block';
                closeDelPhoto();
                load_photos(id_station);
            }
        };

        xhr.send(JSON.stringify({ id_photo: id_photo }));
    }

    okDelPhoto.addEventListener('click', confirmDelPhoto);
    cancelDelPhoto.addEventListener('click', closeDelPhoto);

    // Close on click outside the popup card / Escape key
    boxDelPhoto.addEventListener('click', function(event)
    {
        if (event.target === boxDelPhoto) { closeDelPhoto(); }
    });
    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && boxDelPhoto.style.display === 'block') { closeDelPhoto(); }
    });


    // -----------------------------------------------
    // Button click handler + initial gallery load

    document.getElementById('new_photo').addEventListener('click', function(event)
    {
        event.preventDefault();
        new_photo(id_station);
    });

    load_photos(id_station);

</script>
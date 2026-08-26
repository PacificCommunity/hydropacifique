<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station photo gallery display handler - AJAX server-side process
- Called from include/structure/form_station_photos.php
- Returns an HTML gallery of station photos, or a placeholder if none exist
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Ensure proper UTF-8 encoding for accented characters
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Load translation strings for the active language

require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo   = json_decode(file_get_contents('php://input'), true);
$id_station = $dataInfo['id_station'];


// -----------------------------------------------
// Initialize variables

$tab_html       = '';
$dossier_photos = '../../../' . DIR_WS_DATA_PHOTOS;


// -----------------------------------------------
// Query: Station code (used to build file paths)

$sql_station   = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station
                  FROM " . TABLE_STATION . " s
                  WHERE s.id_station = " . $id_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);
$code_station  = $station_tab['code_station'];


// -----------------------------------------------
// Query: Station photos ordered by date descending

$sql_photos   = "SELECT DISTINCT id, id_station, date_photo, description_photo, file_photo
                 FROM " . TABLE_STATION_PHOTOS . "
                 WHERE id_station = " . $id_station . "
                 ORDER BY date_photo DESC";
$photos_query = tep_db_query($sql_link, $sql_photos);


// -----------------------------------------------
// Build photo gallery HTML (grid layout)

$num_photo = 0;

// Inline SVG for the round delete button (overlay pill on each thumbnail)
$delete_svg = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor'"
            . " stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>"
            . "<line x1='6' y1='6' x2='18' y2='18'/>"
            . "<line x1='6' y1='18' x2='18' y2='6'/>"
            . "</svg>";

// Grid wrapper opens here, closes after the loop
$tab_html .= "<div class='photos-grid'>\n";

// ---- Placeholder card ----
// Clicking the placeholder triggers the file input of the upload form
$tab_html .= "
    <div class='photos-empty' onclick=\"document.getElementById('file_photo').click();\"
            title='" . TEXT_PHOTOS_ADD . "'>
        <div class='photos-empty-icon'>
            <svg viewBox='0 0 24 24' fill='none' stroke='currentColor'
                    stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                <line x1='12' y1='5' x2='12' y2='19'/>
                <line x1='5' y1='12' x2='19' y2='12'/>
            </svg>
        </div>
        <div class='photos-empty-label'>" . TEXT_PHOTOS_ADD . "</div>
    </div>
";

while ($photos_tab = tep_db_fetch_array($photos_query))
{
    $num_photo++;
    $image_path = $dossier_photos . $photos_tab['file_photo'];

    // ---- Date (suppress zero date, em-dash if empty) ----
    $date_photo_fr = dateus_fr($photos_tab['date_photo']);
    if ($date_photo_fr == '00-00-0000' || empty($date_photo_fr)) { $date_photo_fr = '-'; }

    // ---- Description (em-dash if empty) ----
    $description_photo = trim($photos_tab['description_photo'] ?? '');
    if ($description_photo === '') { $description_photo = '-'; }

    // Description passed to the delete popup, escaped for a JS single-quoted string
    $desc_js = htmlspecialchars(
        addslashes($description_photo === '-' ? $date_photo_fr : $description_photo),
        ENT_QUOTES
    );

    $tab_html .= "<div class='photo-card' id='photo_card_" . $photos_tab['id'] . "'>\n";

        // ---- Thumbnail (or missing-image placeholder) ----
        $tab_html .= "<div class='photo-thumb'>";

            // Delete pill (overlay, top-right)
            $tab_html .= "<button type='button' class='photo-delete'"
                       . " title='" . TEXT_PHOTOS_DELETE_TITLE . "'"
                       . " onClick=\"del_photos(" . $photos_tab['id'] . ", '" . $desc_js . "');\">"
                       . $delete_svg . "</button>";

            if (is_file($image_path))
            {
                $tab_html .= "<img src='" . $dossier_photos . $photos_tab['file_photo'] . "'"
                           . " onclick='affichePhoto(this.src)'"
                           . " title='" . TEXT_PHOTOS_VIEW_TITLE . "'>";
            }
            else
            {
                // File recorded in DB but missing on disk
                $tab_html .= "<div class='photo-missing'>" . TEXT_PHOTOS_MISSING . "</div>";
            }

        $tab_html .= "</div>"; // .photo-thumb

        // ---- Caption: date + description ----
        $tab_html .= "<div class='photo-caption'>"
                   . "<p><span class='photo-caption-label'>" . TEXT_PHOTOS_COL_DATE . "</span> : " . $date_photo_fr . "</p>"
                   . "<p><span class='photo-caption-label'>" . TEXT_PHOTOS_COL_DESC . "</span> : " . $description_photo . "</p>"
                   . "</div>";

    $tab_html .= "</div>\n"; // .photo-card
}

$tab_html .= "</div>\n"; // .photos-grid




// -----------------------------------------------
// Return gallery HTML as JSON

echo json_encode(['tab_html' => $tab_html]);
?>
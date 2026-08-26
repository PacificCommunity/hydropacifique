<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Access map display handler - AJAX server-side process
- Looks up the access map image for the given station code
- Returns an HTML snippet with the image (or an actionable placeholder)
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
$dataInfo     = json_decode(file_get_contents('php://input'), true);
$code_station = isset($dataInfo['code_station']) ? $dataInfo['code_station'] : '';

// Sanitize: code_station is used to build a file path, so strip anything that
// is not a safe station-code character (prevents path traversal like ../).
$code_station = preg_replace('/[^A-Za-z0-9_\-]/', '', $code_station);

// -----------------------------------------------
// Locate the access map file (try jpg, jpeg, png)
$dossier_photos = '../../../' . DIR_WS_STATION_PHOTO_ACCESS;
$basePath       = $dossier_photos . $code_station . '_access';
$extensions     = ['jpg', 'jpeg', 'png'];

$file_found = false;
$tab_html   = '';

foreach ($extensions as $ext)
{
    $filePath = $basePath . '.' . $ext;
    if (is_file($filePath))
    {
        $tab_html = "
            <p style='float:right;margin-bottom:5px;'>
                [<a onClick='del_plan();'>" . TEXT_PLAN_DELETE_LINK . "</a>]
            </p>
            <img src='" . $filePath . "'
                 onclick='affichePhoto(this.src)'
                 title='" . TEXT_PLAN_VIEW_TITLE . "'
                 style='cursor:pointer;width:100%;'>
        ";
        $file_found = true;
        break; // Stop at first matching extension
    }
}

// ---- Placeholder when no plan exists ----
// Clicking it triggers the file_photo_access input from the upload form
if (!$file_found)
{
    $tab_html = "
        <div class='photos-empty plan-empty'
             onclick=\"document.getElementById('file_photo_access').click();\"
             title='" . TEXT_PLAN_ADD . "'>
            <div class='photos-empty-icon'>
                <svg viewBox='0 0 24 24' fill='none' stroke='currentColor'
                     stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                    <line x1='12' y1='5' x2='12' y2='19'/>
                    <line x1='5' y1='12' x2='19' y2='12'/>
                </svg>
            </div>
            <div class='photos-empty-label'>" . TEXT_PLAN_ADD . "</div>
        </div>
    ";
}

// -----------------------------------------------
// Return HTML snippet as JSON
echo json_encode(['tab_html' => $tab_html]);
?>
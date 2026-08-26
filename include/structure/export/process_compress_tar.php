<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Folder compression - TAR format (no compression) for download
----------------------------------------
*/

// -----------------------------------------------
// Load translation strings for the active language
require('../../config.php');
require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse incoming JSON payload from AJAX request

$jsonDataCompress = file_get_contents('php://input');
$dataCompress     = json_decode($jsonDataCompress, true);

$chemin_folder          = $dataCompress['chemin_folder'];
$folder                 = $dataCompress['folder_download'];
$chemin_folder_compress = '../../../' . $chemin_folder;


// -----------------------------------------------
// Build TAR archive if the source folder exists

$resultTar_text = '';

if (is_dir($chemin_folder_compress))
{
    // Count files in the source folder
    $fichiers    = scandir($chemin_folder_compress);
    $total_files = sizeof($fichiers);

    // -----------------------------------------------
    // Create a TAR archive (no compression) from the folder contents

    $tarFilename = '../../../data/export/' . $folder . '.tar';

    $phar = new PharData($tarFilename);

    $startTime = microtime(true);

    $phar->buildFromDirectory($chemin_folder_compress); // Add all folder contents to the archive

    $endTime    = microtime(true);
    $total_time = number_format($endTime - $startTime, 1);

    // Compute archive file size in MB
    $fileSize   = filesize($tarFilename);
    $fileSizeMb = round($fileSize / (1024 * 1024), 2);

    // Build the result message returned to the JS progress log
    $resultTar_text .= "\n\n" . TEXT_COMPRESS_READY;
    $resultTar_text .= "\n" . TEXT_COMPRESS_FILE . " : " . $folder . ".tar - " . TEXT_COMPRESS_SIZE . " : " . $fileSizeMb . " " . TEXT_COMPRESS_SIZE_UNIT;

    echo json_encode($resultTar_text, JSON_UNESCAPED_UNICODE);
}
?>
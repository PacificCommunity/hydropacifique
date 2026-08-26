<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
File download endpoint
Receives file_download (basename) and file_extension via POST,
constructs the full path under DIR_WS_DATA_EXPORT, and streams the
file to the browser as an attachment.
Returns an error message if the file does not exist.
----------------------------------------
*/


$file_download   = $_POST['file_download'];
$file_extension  = $_POST['file_extension'];
$chemin_folder   = DIR_WS_DATA_EXPORT . $file_download . '.' . $file_extension;

if (file_exists($chemin_folder))
{
    // Stream the file as a downloadable attachment
    header('Content-Type: application/"' . $file_extension . '"');
    header('Content-Disposition: attachment; filename="' . $chemin_folder . '"');
    header('Content-Length: ' . filesize($chemin_folder));
    readfile($chemin_folder);
    exit;
}
else
{
    echo TEXT_EX_FILE_NOT_FOUND;
    exit;
}
?>

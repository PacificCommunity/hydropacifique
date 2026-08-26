<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Low-flow methodological note — HTML provider for the on-screen popup.
Reads the localized HTML content from data/html/ and returns JSON { title, html }.
----------------------------------------
*/

require('../../config.php');

header('Content-Type: application/json; charset=utf-8');

require('../../text_content_' . LANGUAGE . '.php');

$html_file = '../../../data/html/lowflow_help_' . LANGUAGE . '.html';
if (!is_file($html_file)) { $html_file = '../../../data/html/lowflow_help_fr.html'; }
$html = file_get_contents($html_file);

echo json_encode([
    'title' => TEXT_LOWFLOW_HELP_TITLE,
    'html'  => $html,
], JSON_UNESCAPED_UNICODE);
?>

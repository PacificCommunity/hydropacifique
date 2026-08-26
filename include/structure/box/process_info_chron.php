<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Chronology information module
Displays information about data series types to guide the user
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

// Retrieve JSON data sent from the AJAX request
$jsonDataMap = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataJson = json_decode($jsonDataMap, true);

// Extract values from the decoded array
$id_typedata = $dataJson['idTypeData'];

$where_typedata = '';
if ($id_typedata > 0) { $where_typedata = 'AND id_eq_type_data=' . $id_typedata; }


// ----------------------------------------------
// DATA RETRIEVAL

// Query TABLE EQ_TYPE (data type: rainfall, streamflow, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                                type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type=1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = array(
        'id_eq_type'           => $eq_type_tab['id_eq_type'],
        'nom_eq_type'          => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type'        => $eq_type_tab['unite_eq_type'],
        'valeur_data_type'     => $eq_type_tab['valeur_data_type'],
        'type_color_border'    => $eq_type_tab['type_color_border'],
        'type_color_background' => $eq_type_tab['type_color_background'],
        'type_graph'           => $eq_type_tab['type_graph']
    );
}

// Query chronology types joined with data types
$sql_chronique = "SELECT DISTINCT td.id_data_type, td.init_type_data, td.nom_type_data, td.id_eq_type_data,
                                  td.axe_data, unite, td.to_periode, td.id_chon_periode, td.traitement, td.type_graph,
                                  et.active_eq_type
                  FROM " . TABLE_TYPE_DATA . " td
                  LEFT JOIN " . TABLE_EQ_TYPE . " et ON et.id_eq_type = td.id_eq_type_data
                  WHERE et.active_eq_type > 0 " .
                  $where_typedata . "
                  ORDER BY td.id_eq_type_data ASC, LOWER(td.init_type_data) ASC";
$chronique_query = tep_db_query($sql_link, $sql_chronique);
while ($chronique_data = tep_db_fetch_array($chronique_query))
{
    $id_eq_type     = $chronique_data['id_eq_type_data'];
    $active_eq_type = $chronique_data['active_eq_type'];

    if ($id_eq_type > 0)
    {
        $id_chron = $chronique_data['id_data_type'];

        $chron_array[$id_chron] = array(
            'id_eq_type'     => $id_eq_type,
            'active_eq_type' => $active_eq_type,
            'init'           => $chronique_data['init_type_data'],
            'nom_chron'      => $chronique_data['nom_type_data'],
            'nom_data'       => $eq_type_array[$id_eq_type]['nom_eq_type'],
            'color_data'     => $eq_type_array[$id_eq_type]['type_color_border'],
            'unite'          => $chronique_data['unite']
        );
    }
}


// ----------------------------------------------
// HTML GENERATION

$html = '';

if (isset($chron_array))
{
    $html .= "
    <div class='table-container' style='height:60vh;'>

        <table id='table_tri' cellspacing='0'>

            <thead>
                <tr class='header-row'>
                    <th style='width:80px;padding-left:10px;font-size:13px;'>" . TEXT_CHRON_COL_ACRONYM . "</th>
                    <th style='width:350px;font-size:13px;'>" . TEXT_CHRON_COL_LABEL . "</th>
                    <th style='width:80px;font-size:13px;text-align:center;'>" . TEXT_CHRON_COL_UNIT . "</th>
                    <th style='width:200px;font-size:13px;text-align:center;'>" . TEXT_CHRON_COL_DATATYPE . "</th>
                </tr>
            </thead>
    ";

        $row = 1;
        foreach ($chron_array as $key => $value)
        {
            $row++;
            if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
            else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }

            $color_type = 'color:' . $value['color_data'] . ';';

            $html .= "<tr " . $row_l . " style='font-size:12px;'>";
                $html .= "<td style='padding-left:20px;'>" . $value['init'] . "</td>";
                $html .= "<td>" . $value['nom_chron'] . "</td>";
                $html .= "<td style='text-align:center;'>" . $value['unite'] . "</td>";
                $html .= "<td style='text-align:center;'><span style='" . $color_type . "'>" . $value['nom_data'] . "</span></td>";
            $html .= "</tr>";
        }


        if ($value['active_eq_type'] > 0)
        {
            // Extra rows for hydrometry stations
            if ($value['id_eq_type'] == 0 || $value['id_eq_type'] == 11)
            {
                $nom_data    = $eq_type_array[11]['nom_eq_type'];
                $color_data  = $eq_type_array[11]['type_color_border'];
                $color_type  = 'color:' . $color_data . ';';

                // Separator row
                $row++;
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
                $html .= "<tr " . $row_l . "><td colspan=4>&nbsp;</td></tr>";

                // JGE — Spot gauging
                $row++;
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
                $html .= "<tr " . $row_l . " style='font-size:12px;'>";
                    $html .= "<td style='padding-left:20px;'>" . TEXT_JGE . "</td>";
                    $html .= "<td>" . TEXT_JGE_DESC . "</td>";
                    $html .= "<td style='text-align:center;'>m3/s</td>";
                    $html .= "<td style='text-align:center;'><span style='" . $color_type . "'>" . $nom_data . "</span></td>";
                $html .= "</tr>";

                // ETL — Rating curve
                $row++;
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
                $html .= "<tr " . $row_l . " style='font-size:12px;'>";
                    $html .= "<td style='padding-left:20px;'>" . TEXT_ETL . "</td>";
                    $html .= "<td>" . TEXT_ETL_DESC . "</td>";
                    $html .= "<td style='text-align:center;'>-</td>";
                    $html .= "<td style='text-align:center;'><span style='" . $color_type . "'>" . $value['nom_data'] . "</span></td>";
                $html .= "</tr>";
            }

            // Extra rows for piezometry stations
            if ($value['id_eq_type'] == 0 || $value['id_eq_type'] == 5)
            {
                $nom_data   = $eq_type_array[5]['nom_eq_type'];
                $color_data = $eq_type_array[5]['type_color_border'];
                $color_type = 'color:' . $color_data . ';';

                // Separator row
                $row++;
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
                $html .= "<tr " . $row_l . "><td colspan=4>&nbsp;</td></tr>";

                // DIAG — Conductivity profile
                $row++;
                if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
                else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
                $html .= "<tr " . $row_l . " style='font-size:12px;'>";
                    $html .= "<td style='padding-left:20px;'>" . TEXT_DIAC . "</td>";
                    $html .= "<td>" . TEXT_DIAC_DESC . "</td>";
                    $html .= "<td style='text-align:center;'>-</td>";
                    $html .= "<td style='text-align:center;'><span style='" . $color_type . "'>" . $nom_data . "</span></td>";
                $html .= "</tr>";
            }
        }

        // Separator row
        $row++;
        if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
        else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
        $html .= "<tr " . $row_l . "><td colspan=4>&nbsp;</td></tr>";

        // RA — Activity report
        $row++;
        if (fmod($row, 2) == 0) { $row_l = "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" "; }
        else                    { $row_l = "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" "; }
        $html .= "<tr " . $row_l . " style='font-size:12px;'>";
            $html .= "<td style='padding-left:20px;'>" . TEXT_RA . "</td>";
            $html .= "<td>" . TEXT_RA_DESC . "</td>";
            $html .= "<td style='text-align:center;'>-</td>";
            $html .= "<td style='text-align:center;'>-</td>";
        $html .= "</tr>";

    $html .= "</table>";
    $html .= "</div>";
}


$responseData = array(
    'js_html' => $html
);

// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;
?>

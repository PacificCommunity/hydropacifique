<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction view handler - AJAX server-side process
- Displays current corrections in a table
- Called from graph_correct_chron.php
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

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo      = json_decode(file_get_contents('php://input'), true);
$id_correction = $dataInfo['id_correction'];


// -----------------------------------------------
// Query: Users lookup table

$user_list_query = tep_db_query($sql_link, "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
while ($user_list = tep_db_fetch_array($user_list_query))
{
    $id = $user_list['id'];
    $user_list_array[$id] = [
        'id_statut' => $user_list['id_statut'],
        'login'     => html_entity_decode($user_list['login']  ?? ''),
        'nom'       => ucfirst(strtolower(html_entity_decode($user_list['nom']    ?? ''))),
        'prenom'    => ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? ''))),
    ];
}


// -----------------------------------------------
// Query: Stations lookup table

$station_all_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
     FROM " . TABLE_STATION . " ORDER BY nom_station ASC");
while ($station_all = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$station_all['id_station']] = [
        'code_station' => $station_all['code_station'],
        'nom_station'  => html_entity_decode($station_all['nom_station'] ?? ''),
        'station_type' => $station_all['station_type'],
    ];
}


// -----------------------------------------------
// Query: Equipment types lookup table

$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_graph
     FROM " . TABLE_EQ_TYPE . " WHERE active_eq_type=1 ORDER BY order_eq_type ASC");
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = [
        'nom_eq_type'       => $eq_type_tab['nom_eq_type'],
        'unite_eq_type'     => $eq_type_tab['unite_eq_type'],
        'valeur_data_type'  => $eq_type_tab['valeur_data_type'],
        'type_graph'        => $eq_type_tab['type_graph'],
        'type_color_border' => $eq_type_tab['type_color_border'],
    ];
}


// -----------------------------------------------
// Query: Series types lookup table

$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $axe_nom = isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe'])
               ? $data_type_axe_array[$type_chron_tab['axe_data']]['axe'] : '';

    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data'  => $type_chron_tab['init_type_data'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom'         => $axe_nom,
        'unite'           => $type_chron_tab['unite'],
        'to_periode'      => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode'],
    ];
}


// -----------------------------------------------
// Initialize variables

$tab_html         = '';
$text_result_file = '';
$row              = 0;
$id_meta_correct  = 0;


// -----------------------------------------------
// Build corrections table HTML

if ($id_correction > 0)
{
    // ---- Retrieve correction header record ----
    $correction_tab = tep_db_fetch_array(tep_db_query($sql_link,
        "SELECT c.id, c.id_user, c.datetime_correction, c.id_station, c.id_chron_init, c.datetime_first, c.datetime_end
         FROM " . TABLE_DATA_CORRECTION . " c WHERE c.id = " . $id_correction));

    $datetime_correction_tab      = explode(' ', $correction_tab['datetime_correction']);
    $datetime_correction_formated = dateus_fr($datetime_correction_tab[0]) . ' ' . $datetime_correction_tab[1];

    $id_station            = $correction_tab['id_station'];
    $code_station          = $station_all_array[$id_station]['code_station'];
    $nom_station           = $station_all_array[$id_station]['nom_station'];
    $station_type          = $station_all_array[$id_station]['station_type'];
    $intitule_station_type = $eq_type_array[$station_type]['nom_eq_type'];

    $id_chron_init = $correction_tab['id_chron_init'];
    $init_chron    = $type_chron_array[$id_chron_init]['init_type_data'];
    $nom_chron     = $type_chron_array[$id_chron_init]['nom_type_data'];

    $datetime_first_correction_tab      = explode(' ', $correction_tab['datetime_first']);
    $datetime_first_correction_formated = dateus_fr($datetime_first_correction_tab[0]) . ' ' . $datetime_first_correction_tab[1];
    $datetime_end_correction_tab        = explode(' ', $correction_tab['datetime_end']);
    $datetime_end_correction_formated   = dateus_fr($datetime_end_correction_tab[0]) . ' ' . $datetime_end_correction_tab[1];

    $id_user     = $correction_tab['id_user'];
    $login_user  = $user_list_array[$id_user]['login'];
    $prenom_user = $user_list_array[$id_user]['prenom'];
    $nom_user    = $user_list_array[$id_user]['nom'];

    // Build tracking file header
    $text_result_file .= "Bloc de correction - " . $datetime_correction_formated . "\r\n"
                       . "    --\r\n"
                       . "        Utilisateur : " . $login_user . " - " . $prenom_user . " " . $nom_user . "\r\n"
                       . "        Station : " . $code_station . " - " . $nom_station . "\r\n"
                       . "        Type : " . $intitule_station_type . "\r\n"
                       . "        Chronique : " . $init_chron . " - " . $nom_chron . "\r\n"
                       . "        Période : " . $datetime_first_correction_formated . " - " . $datetime_end_correction_formated . "\r\n"
                       . "    --\r\n\r\n\r\n"
                       . "        Liste des corrections \r\n"
                       . "    --\r\n";

    // ---- Iterate over individual correction entries ----
    $meta_correction_query = tep_db_query($sql_link,
        "SELECT mc.id, mc.id_station, mc.id_typedata, mc.info_correction, mc.axe_correction,
                mc.datetime_first, mc.datetime_end, mc.valid, mc.id_chron_modif
         FROM " . TABLE_DATA_META_CORRECTION . " mc WHERE id_correction = " . $id_correction);

    while ($meta_correction_tab = tep_db_fetch_array($meta_correction_query))
    {
        $id_meta_correct = $meta_correction_tab['id'];
        $info_correction = $meta_correction_tab['info_correction'];

        $datetime_first_meta_tab      = explode(' ', $meta_correction_tab['datetime_first']);
        $datetime_first_meta_formated = dateus_fr($datetime_first_meta_tab[0]) . ' ' . $datetime_first_meta_tab[1];

        $datetime_end_meta_tab      = explode(' ', $meta_correction_tab['datetime_end']);
        $datetime_end_meta_formated = dateus_fr($datetime_end_meta_tab[0]) . ' ' . $datetime_end_meta_tab[1];

        $id_chron_modif   = $meta_correction_tab['id_chron_modif'];
        $init_chron_modif = ($id_chron_modif > 0) ? $type_chron_array[$id_chron_modif]['init_type_data'] : '';

        $has_downloadable_series = (count(explode(':', $info_correction)) > 1);

        $download_icon = "<img src='" . DIR_WS_IMG_ICO . "download.png'"
                       . " style='width:13px;cursor:pointer;' id='download_" . $id_meta_correct . "'"
                       . " title='" . TEXT_CALCUL_VIEW_DOWNLOAD_TITLE . "'"
                       . " onclick=\"download_chron(" . $id_meta_correct . ");\">";

        $applied_icon = "<img src='" . DIR_WS_IMG_ICO . "check.png'"
                      . " style='width:13px;' id='valid_" . $id_meta_correct . "'"
                      . " title='" . TEXT_CALCUL_VIEW_APPLIED_TITLE . "'>";

        $checkbox = "<input type='checkbox' name='checkCorrection[]'"
                  . " value='meta_" . $id_meta_correct . "' id='meta_" . $id_meta_correct . "'>";

        $del_link = "<a style='font-size:12px;font-weight:bold;' id='del_" . $id_meta_correct . "'"
                  . " onClick='delCorrection(" . $id_meta_correct . ");'"
                  . " title='" . TEXT_CALCUL_VIEW_DELETE_TITLE . "'>X</a>";

        // Default: empty cell for the "open target series" column.
        // Only filled when the correction was saved into a DIFFERENT series
        // than the original one — gives the user a quick way to jump to that
        // series in correction mode (new tab).
        $open_target_textTab = '&nbsp;';

        if ($meta_correction_tab['valid'] > 0)
        {
            $download_textTab      = $has_downloadable_series ? $download_icon : '';
            $valid_textTab         = $applied_icon . " -> " . $init_chron_modif;
            $del_textTab           = '&nbsp;';
            $text_result_file_temp = "Validée dans la chronique " . $init_chron_modif;

            // ---- Open-in-new-tab link, only when correction was saved into
            //      a different time series than the original one.
            //      Posts the same payload as the "Edit chron" button on the
            //      main graph, so the target series opens directly in
            //      correction mode (data_chron.php → button_calcul flow).
            if ($id_chron_modif > 0 && $id_chron_modif != $id_chron_init)
            {
                // Ouvrir la série cible sur TOUTE la fenêtre du bloc
                // (datetime_first/end de l'en-tête = étendue du socle
                // complet copié à la validation), et non sur la seule
                // sous-période de la correction. L'utilisateur voit ainsi
                // la série corrigée entière, pas juste le segment modifié.
                $date_1_open = dateus_fr($datetime_first_correction_tab[0]);
                $date_2_open = dateus_fr($datetime_end_correction_tab[0]);

                // Ouvre la série cible en VISUALISATION SIMPLE via
                // data_chron.php : le flag button_graph route vers
                // graph_chron.php (graphe lecture seule, multi), au lieu de
                // button_calcul qui ouvrait data_chron_calcul.php en mode
                // correction. valid_chron_step1 reste requis pour peupler
                // la station cible côté routeur ; on n'envoie PAS one_graph
                // afin de rester sur la vue multi (graph_chron.php).
                $open_target_textTab = "
                    <form action='data_chron.php' method='post' target='_blank'
                          style='display:inline;margin:0;'>
                        <input type='hidden' name='valid_chron_step1'    value='1' />
                        <input type='hidden' name='button_graph'         value='1' />
                        <input type='hidden' name='target_station_ref[]' value='" . $id_station . "' />
                        <input type='hidden' name='check_chron[]'        value='" . $id_station . "_" . $station_type . "_" . $id_chron_modif . "' />
                        <input type='hidden' name='date_1' value='" . $date_1_open . "' />
                        <input type='hidden' name='date_2' value='" . $date_2_open . "' />
                        <button type='submit' title='" . TEXT_CALCUL_OPEN_TARGET_SERIES . "'
                                style='background:none;border:none;cursor:pointer;padding:0;
                                       font-size:12px;line-height:1;vertical-align:middle;'>
                            🔗
                        </button>
                    </form>
                ";
            }
        }
        else
        {
            $download_textTab      = $has_downloadable_series ? $download_icon : '';
            $valid_textTab         = $checkbox;
            $del_textTab           = $del_link;
            $text_result_file_temp = "Non validée";
        }

        // Alternate light-grey / white striping with a hover highlight.
        // Couleurs appliquées directement en style inline pour ne pas
        // dépendre du CSS #table_tri (ce tableau a l'id
        // #table_info_correction et n'hérite donc pas des règles
        // row1/row2 du global tableau.css).
        //
        // Palette: #ffffff (white) and #f5f6f8 (very light grey) for
        // the rows, #ffeead (soft yellow) on hover — same yellow used
        // everywhere else in the project for consistency.
        $row++;
        $base_bg  = (fmod($row, 2) == 0) ? '#ffffff' : '#f5f6f8';
        $hover_bg = '#ffeead';

        $row_l = "style=\"background-color:{$base_bg};\""
               . " onmouseover=\"this.style.backgroundColor='{$hover_bg}';\""
               . " onmouseout=\"this.style.backgroundColor='{$base_bg}';\"";

        $tab_html .= "<tr " . $row_l . ">
                        <td style='height:25px;'>" . $info_correction . "</td>
                        <td style='height:25px;'>" . $datetime_first_meta_formated . "</td>
                        <td style='height:25px;'>" . $datetime_end_meta_formated . "</td>
                        <td style='height:25px;text-align:center;'>" . $valid_textTab . "</td>
                        <td style='height:25px;text-align:center;'>" . $open_target_textTab . "</td>
                        <td style='height:25px;text-align:center;'>" . $download_textTab . "</td>
                        <td style='height:25px;text-align:center;'>" . $del_textTab . "</td>
                      </tr>";

        $text_result_file .= $info_correction . " : ["
                           . $datetime_first_meta_formated . " | " . $datetime_first_meta_formated
                           . "] => " . $text_result_file_temp . "\n";
    }

    // ---- Write corrections tracking file ----
    $folder         = '../../../data/corrections';
    $resultFilename = $folder . '/' . $code_station . '_'
                    . $type_chron_array[$id_chron_init]['init_type_data'] . '_' . $id_correction . '.txt';

    if (file_exists($resultFilename)) { unlink($resultFilename); }
    file_put_contents($resultFilename,
        mb_convert_encoding($text_result_file, 'ISO-8859-1', 'UTF-8'), FILE_APPEND);
}


// ---- Placeholder row if no corrections exist ----
if ($id_meta_correct < 1)
{
    $tab_html .= "<tr><td colspan='7' style='height:15px;'>&nbsp;</td></tr>
                  <tr><td colspan='7' style='height:15px;font-weight:bold;font-size:12px;'>"
               . TEXT_CALCUL_VIEW_NONE . "</td></tr>";
}


// -----------------------------------------------
// Return HTML table and last meta ID as JSON

echo json_encode([
    'tab_html' => $tab_html,
    'id_meta'  => $id_meta_correct,
]);
?>
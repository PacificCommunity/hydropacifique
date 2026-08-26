<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging record save handler - AJAX server-side process
- Saves (create or update) a gauging (JGE) record
- post_secure: security function in include/function/ to sanitize form inputs against JS/PHP injection
- For numeric, date and time field validation, empty values are valid;
  refer to validDate(), validTime(), validNumeric() helper functions
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/gestion_erreur.php');
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

// Suppress warnings/notices in output — they would corrupt the JSON
// response (harmonized with process_jge_simple_save.php). Real limits
// (max_input_vars, max_multipart_body_parts) must be set in php.ini.
@ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);


// -----------------------------------------------
// Initialize global variables

$msg_info         = '';
$msg_info_details = '';
$erreur           = false;
$newJGE           = false;


// -----------------------------------------------
// Process POST request only

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $id_user_agent = isset($_POST['id_user_agent']) ? (int) $_POST['id_user_agent'] : 0;
    $territoire_id = isset($_POST['territoire_id']) ? (int) $_POST['territoire_id'] : 0;

    $id_jge = isset($_POST['id_jge']) ? (int) $_POST['id_jge'] : 0;
    if ($id_jge < 1) { $newJGE = true; }


    // -----------------------------------------------
    // Query: Users lookup table

    $user_list_query = tep_db_query($sql_link, "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
    while ($user_list = tep_db_fetch_array($user_list_query))
    {
        $id = $user_list['id'];
        $user_list_array[$id] = [
            'id_statut' => $user_list['id_statut'],
            'login'     => ucfirst(strtolower(html_entity_decode($user_list['login']  ?? ''))),
            'nom'       => ucfirst(strtolower(html_entity_decode($user_list['nom']    ?? ''))),
            'prenom'    => ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? ''))),
        ];
    }


    // -----------------------------------------------
    // Query: Stations lookup table (gauging type, current territory)

    $station_query = tep_db_query($sql_link,
        "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.id_region
         FROM " . TABLE_STATION . " s
         JOIN " . TABLE_REGION  . " r ON s.id_region = r.id_region
         WHERE s.station_type = 11 AND r.id_territoire = " . $territoire_id . "
         ORDER BY s.nom_station");

    while ($station = tep_db_fetch_array($station_query))
    {
        $station_array[$station['id_station']] = [
            'act_station'  => ($station['active_station'] == 1),
            'nom_station'  => html_entity_decode($station['nom_station']  ?? ''),
            'code_station' => html_entity_decode($station['code_station'] ?? ''),
        ];
    }


    // -----------------------------------------------
    // Validate and retrieve general form fields

    $jge_hmoy = isset($_POST['jge_hmoy']) ? $_POST['jge_hmoy'] : '';
    if (!validNumeric($jge_hmoy))
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_HMOY . "<br>";
    }

    $jge_q = isset($_POST['jge_q']) ? $_POST['jge_q'] : '';
    if (!validNumeric($jge_q))
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_Q . "<br>";
    }

    $date_jge = isset($_POST['date_jge']) ? $_POST['date_jge'] : '';
    if (!validDate($date_jge))
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_DATE . "<br>";
        $date_jge = '';
    }

    $heure_jge = isset($_POST['heure_jge']) ? $_POST['heure_jge'] : '';
    if (!validTime($heure_jge))
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_HEURE . "<br>";
        $heure_jge = '';
    }

    if (!$erreur)
    {
        $dateheure_jge    = $date_jge . ' ' . $heure_jge;
        $dateheure_jge_us = datefr_us($date_jge) . ' ' . $heure_jge;
    }

    $id_station = isset($_POST['select_station']) ? (int) $_POST['select_station'] : 0;
    if ($id_station <= 0)
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_STATION . "<br>";
    }

    $code_station = isset($station_array[$id_station]['code_station']) ? $station_array[$id_station]['code_station'] : '';
    $nom_station  = isset($station_array[$id_station]['nom_station'])  ? $station_array[$id_station]['nom_station']  : '';

    $id_code_qual    = isset($_POST['select_code_qual'])  ? (int) $_POST['select_code_qual']  : 0;
    $dist_site       = isset($_POST['dist_site'])         ? $_POST['dist_site']         : '';
    if (!validNumeric($dist_site))
    {
        $erreur = true;
        $msg_info_details .= TEXT_JGE_SAVE_ERR_DIST . "<br>";
    }

    $id_site_jge     = isset($_POST['select_site_jge'])   ? (int) $_POST['select_site_jge']   : 0;
    $x_gps           = post_secure($sql_link, isset($_POST['x_gps'])        ? $_POST['x_gps']        : '');
    $y_gps           = post_secure($sql_link, isset($_POST['y_gps'])        ? $_POST['y_gps']        : '');
    $id_type_jge     = isset($_POST['select_type_jge'])   ? (int) $_POST['select_type_jge']   : 0;
    $id_methode_jge  = isset($_POST['select_methode_jge']) ? (int) $_POST['select_methode_jge'] : 0;
    $obs             = post_secure($sql_link, isset($_POST['obs'])          ? $_POST['obs']          : '');
    $fichier         = post_secure($sql_link, isset($_POST['file_link'])    ? $_POST['file_link']    : '');
    $agents          = post_secure($sql_link, isset($_POST['agents_text'])  ? $_POST['agents_text']  : '');


    // -----------------------------------------------
    // Validate and retrieve arm data

    $tab_info_bras = [];

    // If no arms yet (new record) create at least 1; otherwise handle possible new arm
    $nb_bras = isset($_POST['nb_bras']) ? $_POST['nb_bras'] : 0;
    $nb_bras++;
    for ($nbb = 1; $nbb <= $nb_bras; $nbb++)
    {
        $tab_info_bras[$nbb]['id_bras'] = isset($_POST['id_bras_' . $nbb]) ? (int) $_POST['id_bras_' . $nbb] : 0;

        $heure_first = isset($_POST['heure_first_' . $nbb]) ? $_POST['heure_first_' . $nbb] : '';
        $h_ech_first = isset($_POST['h_ech_first_' . $nbb]) ? $_POST['h_ech_first_' . $nbb] : '';
        $heure_end   = isset($_POST['heure_end_'   . $nbb]) ? $_POST['heure_end_'   . $nbb] : '';
        $h_ech_end   = isset($_POST['h_ech_end_'   . $nbb]) ? $_POST['h_ech_end_'   . $nbb] : '';

        // Detect empty arm: no validation, no save (silently skipped later)
        $bras_vide = (empty($heure_first) && empty($h_ech_first)
                   && empty($heure_end)   && empty($h_ech_end));

        $perche_diam = isset($_POST['perche_diam_' . $nbb]) ? $_POST['perche_diam_' . $nbb] : null;

        if (!$bras_vide)
        {
            // The two "first" fields are mandatory for a non-empty arm
            if (empty($heure_first) || empty($h_ech_first))
            {
                $erreur = true;
                $msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_FIRST_REQUIRED, $nbb) . "<br>";
            }
            else
            {
                // Validate "first" fields
                if (!validTime($heure_first))
                {
                    $erreur = true;
                    $msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_HFIRST, $nbb) . "<br>";
                }
                if (!validNumeric($h_ech_first))
                {
                    $erreur = true;
                    $msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_ECHFIRST, $nbb) . "<br>";
                }

                // If "end" fields are empty, copy them from "first"
                if (empty($heure_end)) { $heure_end = $heure_first; }
                if (empty($h_ech_end)) { $h_ech_end = $h_ech_first; }

                // Validate "end" fields (now guaranteed non-empty)
                if (!validTime($heure_end))
                {
                    $erreur = true;
                    $msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_HEND, $nbb) . "<br>";
                }
                if (!validNumeric($h_ech_end))
                {
                    $erreur = true;
                    $msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_ECHEND, $nbb) . "<br>";
                }
            }

            // perche_diam: only validate if arm is not empty
            if (!validNumeric($perche_diam))
            {
                //$erreur = true;
                //$msg_info_details .= sprintf(TEXT_JGE_SAVE_ERR_BRAS_PERCHE, $nbb) . "<br>";
            }
        }

        // Store all values (even for empty arms - the flag tells us to skip them)
        $tab_info_bras[$nbb]['bras_vide']    = $bras_vide;
        $tab_info_bras[$nbb]['heure_first']  = $heure_first;
        $tab_info_bras[$nbb]['h_ech_first']  = $h_ech_first;
        $tab_info_bras[$nbb]['heure_end']    = $heure_end;
        $tab_info_bras[$nbb]['h_ech_end']    = $h_ech_end;
        $tab_info_bras[$nbb]['perche_diam']  = $perche_diam;

        // ---- Other fields ----
        $tab_info_bras[$nbb]['fond_text']    = post_secure($sql_link, isset($_POST['fond_text_'  . $nbb]) ? $_POST['fond_text_'  . $nbb] : '');
        $tab_info_bras[$nbb]['obs']          = post_secure($sql_link, isset($_POST['bras_obs_'   . $nbb]) ? $_POST['bras_obs_'   . $nbb] : '');
        $tab_info_bras[$nbb]['berge_depart'] = isset($_POST['select_berge1_'   . $nbb]) ? (int) $_POST['select_berge1_'   . $nbb] : 0;
        $tab_info_bras[$nbb]['id_moulinet']  = isset($_POST['select_moulinet_' . $nbb]) ? (int) $_POST['select_moulinet_' . $nbb] : 0;
        $tab_info_bras[$nbb]['id_helice']    = isset($_POST['select_helice_'   . $nbb]) ? (int) $_POST['select_helice_'   . $nbb] : 0;

        // Computed fields (not user-editable, no validation needed)
        $tab_info_bras[$nbb]['depouil_nbvert']    = 0;
        $tab_info_bras[$nbb]['depouil_hmoy']      = isset($_POST['depouil_bras_hmoy_'      . $nbb]) ? $_POST['depouil_bras_hmoy_'      . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_profmoy']   = isset($_POST['depouil_bras_profmoy_'   . $nbb]) ? $_POST['depouil_bras_profmoy_'   . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_distmax']   = isset($_POST['depouil_bras_distmax_'   . $nbb]) ? $_POST['depouil_bras_distmax_'   . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_vmoy']      = isset($_POST['depouil_bras_vmoy_'      . $nbb]) ? $_POST['depouil_bras_vmoy_'      . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_vsurf']     = isset($_POST['depouil_bras_vsurf_'     . $nbb]) ? $_POST['depouil_bras_vsurf_'     . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_rh']        = isset($_POST['depouil_bras_rh_'        . $nbb]) ? $_POST['depouil_bras_rh_'        . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_surfmouil'] = isset($_POST['depouil_bras_surfmouil_' . $nbb]) ? $_POST['depouil_bras_surfmouil_' . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_perimouil'] = isset($_POST['depouil_bras_perimouil_' . $nbb]) ? $_POST['depouil_bras_perimouil_' . $nbb] : null;
        $tab_info_bras[$nbb]['depouil_q']         = isset($_POST['depouil_bras_q_'         . $nbb]) ? $_POST['depouil_bras_q_'         . $nbb] : null;
    }


    // -----------------------------------------------
    // Save to database (wrapped in a transaction)

    if (!$erreur)
    {
        tep_db_query($sql_link, "START TRANSACTION");

        try
        {
            // ---- Create or update gauging header record ----
            if ($newJGE)
            {
                tep_db_query($sql_link, "INSERT INTO " . TABLE_DATA_JGE . " (id_station) VALUES ('" . $id_station . "')");
                $id_jge = mysqli_insert_id($sql_link);

                if (HP_VERSION == 'Nomad')
                {
                    tep_db_query($sql_link, "UPDATE " . TABLE_DATA_JGE . " SET from_nomad=1, new_nomad=1 WHERE id=" . $id_jge);
                }

                $msg_info   .= "<span style='font-size:16px;'>" . TEXT_JGE_SAVE_CREATED . "</span><br><br>";
                $type_action = 10;
                $info_action = TEXT_JGE_SAVE_ACTION_CREATE . $code_station . " - " . $nom_station . " - " . $dateheure_jge;
            }
            else
            {
                $msg_info   .= "<span style='font-size:16px;'>" . TEXT_JGE_SAVE_UPDATED . "</span><br><br>";
                $type_action = 11; // JGE update (10 = creation)
                $info_action = TEXT_JGE_SAVE_ACTION_UPDATE . $code_station . " - " . $nom_station;
            }

            $msg_info .= TEXT_JGE_SAVE_STATION_LABEL . $nom_station;

            // Numeric header fields: empty -> NULL, comma -> dot, returned quoted or NULL.
            // x_gps / y_gps are TEXT columns: they stay quoted via post_secure (no truncation risk).
            $sql_dist_site    = preparerSQL($dist_site);

            // Safety net: the header values are derived from the arms, never
            // trusted from the POST alone. The browser already refreshes them
            // (syncHeaderFromBras in js_jge.js), but a Nomad upload or a stale
            // page can still post an out-of-date pair.
            //   depouil_q    = sum of the non-empty arms (flows add up)
            //   depouil_hmoy = first non-empty arm (each arm reads its own staff
            //                  gauge, with its own datum: averaging stages across
            //                  arms would mix reference frames)
            $sum_bras_q  = 0;
            $has_bras_q  = false;
            $hmoy_bras_1 = null;

            for ($nbb = 1; $nbb <= $nb_bras; $nbb++)
            {
                if ($tab_info_bras[$nbb]['bras_vide'])
                {
                    continue;
                }

                $q_bras = str_replace(',', '.', trim((string) $tab_info_bras[$nbb]['depouil_q']));
                if ($q_bras !== '' && is_numeric($q_bras))
                {
                    $sum_bras_q += (float) $q_bras;
                    $has_bras_q  = true;
                }

                if ($hmoy_bras_1 === null)
                {
                    $h_bras = str_replace(',', '.', trim((string) $tab_info_bras[$nbb]['depouil_hmoy']));
                    if ($h_bras !== '' && is_numeric($h_bras))
                    {
                        $hmoy_bras_1 = $h_bras;
                    }
                }
            }

            // No arm carries a value: keep whatever was posted, so a gauging
            // whose Q / Hmoy were entered straight in the sidebar is preserved.
            if ($has_bras_q)           { $jge_q    = $sum_bras_q;  }
            if ($hmoy_bras_1 !== null) { $jge_hmoy = $hmoy_bras_1; }

            $sql_depouil_hmoy = preparerSQL($jge_hmoy);
            $sql_depouil_q    = preparerSQL($jge_q);

            // Update gauging header fields
            tep_db_query($sql_link,
                "UPDATE " . TABLE_DATA_JGE . " SET
                    id_station='"    . $id_station     . "',
                    x_gps='"         . $x_gps          . "',
                    y_gps='"         . $y_gps          . "',
                    datetime='"      . $dateheure_jge_us . "',
                    dist_site="      . $sql_dist_site    . ",
                    id_site='"       . $id_site_jge     . "',
                    id_methode='"    . $id_methode_jge  . "',
                    id_typejge='"    . $id_type_jge     . "',
                    depouil_hmoy="   . $sql_depouil_hmoy . ",
                    depouil_q="      . $sql_depouil_q     . ",
                    code_qualite='"  . $id_code_qual    . "',
                    obs='"           . $obs             . "',
                    fichier='"       . $fichier         . "',
                    agents='"        . $agents          . "'
                    WHERE id=" . $id_jge);

            if (HP_VERSION == 'Nomad')
            {
                tep_db_query($sql_link, "UPDATE " . TABLE_DATA_JGE . " SET from_nomad=1 WHERE id=" . $id_jge);
            }


            // ---- Save each arm ----
            $nb_bras_real = 0;
            for ($nbb = 1; $nbb <= $nb_bras; $nbb++)
            {
                // Skip empty arms silently (no error, no save)
                if ($tab_info_bras[$nbb]['bras_vide'])
                {
                    continue;
                }

                $nb_bras_real++;

                // Numeric arm fields (FLOAT/INT columns): empty -> NULL, comma -> dot,
                // returned already quoted or NULL by preparerSQL(). They MUST be written
                // WITHOUT surrounding quotes in the SQL below (preparerSQL adds its own).
                // This replaces the previous real_escape_string approach, which turned an
                // empty/NULL value into '' and triggered "Data truncated" on numeric columns.
                // heure_first / heure_end (TIME) are guaranteed non-empty for a non-empty arm
                // and stay quoted. fond_text / obs already went through post_secure.
                foreach (['perche_diam', 'h_ech_first', 'h_ech_end',
                          'depouil_hmoy', 'depouil_nbvert', 'depouil_profmoy', 'depouil_distmax',
                          'depouil_vmoy', 'depouil_vsurf', 'depouil_surfmouil', 'depouil_perimouil',
                          'depouil_rh', 'depouil_q'] as $f)
                {
                    $tab_info_bras[$nbb][$f] = preparerSQL($tab_info_bras[$nbb][$f]);
                }

                // TIME fields: escape only (kept quoted)
                $tab_info_bras[$nbb]['heure_first'] = mysqli_real_escape_string($sql_link, (string) $tab_info_bras[$nbb]['heure_first']);
                $tab_info_bras[$nbb]['heure_end']   = mysqli_real_escape_string($sql_link, (string) $tab_info_bras[$nbb]['heure_end']);

                // Create arm record if new
                if ($tab_info_bras[$nbb]['id_bras'] < 1)
                {
                    tep_db_query($sql_link, "INSERT INTO " . TABLE_DATA_JGE_BRAS . " (id_jge) VALUES ('" . $id_jge . "')");
                    $tab_info_bras[$nbb]['id_bras'] = mysqli_insert_id($sql_link);
                }

                // Update arm record
                tep_db_query($sql_link,
                    "UPDATE " . TABLE_DATA_JGE_BRAS . " SET
                        num_bras='"          . $nb_bras_real                                 . "',
                        id_moulinet='"       . $tab_info_bras[$nbb]['id_moulinet']           . "',
                        id_helice='"         . $tab_info_bras[$nbb]['id_helice']             . "',
                        perche_diam="        . $tab_info_bras[$nbb]['perche_diam']           . ",
                        berge_depart='"      . $tab_info_bras[$nbb]['berge_depart']          . "',
                        heure_first='"       . $tab_info_bras[$nbb]['heure_first']           . "',
                        h_ech_first="        . $tab_info_bras[$nbb]['h_ech_first']           . ",
                        heure_end='"         . $tab_info_bras[$nbb]['heure_end']             . "',
                        h_ech_end="          . $tab_info_bras[$nbb]['h_ech_end']             . ",
                        fond_text='"         . $tab_info_bras[$nbb]['fond_text']             . "',
                        depouil_hmoy="       . $tab_info_bras[$nbb]['depouil_hmoy']          . ",
                        depouil_nbvert="     . $tab_info_bras[$nbb]['depouil_nbvert']        . ",
                        depouil_profmoy="    . $tab_info_bras[$nbb]['depouil_profmoy']       . ",
                        depouil_distmax="    . $tab_info_bras[$nbb]['depouil_distmax']       . ",
                        depouil_vmoy="       . $tab_info_bras[$nbb]['depouil_vmoy']          . ",
                        depouil_vsurf="      . $tab_info_bras[$nbb]['depouil_vsurf']         . ",
                        depouil_surfmouil="  . $tab_info_bras[$nbb]['depouil_surfmouil']     . ",
                        depouil_perimouil="  . $tab_info_bras[$nbb]['depouil_perimouil']     . ",
                        depouil_rh="         . $tab_info_bras[$nbb]['depouil_rh']            . ",
                        depouil_q="          . $tab_info_bras[$nbb]['depouil_q']             . ",
                        obs='"               . $tab_info_bras[$nbb]['obs']                   . "'
                        WHERE id=" . $tab_info_bras[$nbb]['id_bras'] . ";");


                // ---- Save measurement points for this arm ----
                $query_pts        = "INSERT INTO " . TABLE_DATA_JGE_PTS . " (id_bras, num_vert, dist_depart, prof_max, prof_pts, nb_tours, tps_pts, vitesse_calc, obs) VALUES ";
                $values_pts       = [];
                $nb_pts           = 100;
                $jge_data_valid   = true;

                for ($num_pts = 0; $num_pts < $nb_pts; $num_pts++)
                {
                    $jge_bra_vert       = isset($_POST['jge_bra_vert_'       . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_vert_'       . $nbb . '_' . $num_pts] : '';
                    $jge_bra_dist       = isset($_POST['jge_bra_dist_'       . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_dist_'       . $nbb . '_' . $num_pts] : '';
                    $jge_bra_profmax    = isset($_POST['jge_bra_profmax_'    . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_profmax_'    . $nbb . '_' . $num_pts] : '';
                    $jge_bra_profmesure = isset($_POST['jge_bra_profmesure_' . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_profmesure_' . $nbb . '_' . $num_pts] : '';
                    $jge_bra_nbtour     = isset($_POST['jge_bra_nbtour_'     . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_nbtour_'     . $nbb . '_' . $num_pts] : '';
                    $jge_bra_tps        = isset($_POST['jge_bra_tps_'        . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_tps_'        . $nbb . '_' . $num_pts] : '';
                    $jge_bra_vitesse    = isset($_POST['jge_bra_vitesse_'    . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_vitesse_'    . $nbb . '_' . $num_pts] : '';
                    $jge_bra_obs        = isset($_POST['jge_bra_obs_'        . $nbb . '_' . $num_pts]) ? $_POST['jge_bra_obs_'        . $nbb . '_' . $num_pts] : '';

                    // Validate and cast each field to the expected type
                    if ($jge_data_valid) { validate_and_convert($jge_bra_vert,       $jge_data_valid, 'int');   }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_dist,       $jge_data_valid, 'float'); }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_profmax,    $jge_data_valid, 'float'); }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_profmesure, $jge_data_valid, 'float'); }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_nbtour,     $jge_data_valid, 'int');   }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_tps,        $jge_data_valid, 'int');   }
                    if ($jge_data_valid) { validate_and_convert($jge_bra_vitesse,    $jge_data_valid, 'float'); }

                    if (isset($jge_bra_profmesure) && $jge_bra_profmesure !== '')
                    {
                        if ($jge_data_valid)
                        {
                            // The 7 numeric fields above are already type-checked by
                            // validate_and_convert (invalid format -> $jge_data_valid
                            // false -> no insert). Only the free-text observation needs
                            // escaping before being written into the SQL.
                            $jge_bra_obs_safe = mysqli_real_escape_string($sql_link, (string) $jge_bra_obs);

                            $values_pts[] = "('" . $tab_info_bras[$nbb]['id_bras'] . "',
                                             '"  . $jge_bra_vert       . "',
                                             '"  . $jge_bra_dist       . "',
                                             '"  . $jge_bra_profmax    . "',
                                             '"  . $jge_bra_profmesure . "',
                                             '"  . $jge_bra_nbtour     . "',
                                             '"  . $jge_bra_tps        . "',
                                             '"  . $jge_bra_vitesse    . "',
                                             '"  . $jge_bra_obs_safe   . "')";
                        }
                        else
                        {
                            $erreur            = true;
                            $msg_info_details .= "<span style='font-size:16px;'>" . TEXT_JGE_SAVE_ERR_PTS_TITLE . "</span><br><br>";
                            $msg_info_details .= TEXT_JGE_SAVE_ERR_PTS_FORMAT . "<br>";
                            break;
                        }
                    }
                }

                // Delete old points and insert new ones
                if (!$erreur && !empty($values_pts))
                {
                    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_PTS . " WHERE id_bras=" . $tab_info_bras[$nbb]['id_bras'] . ";");
                    tep_db_query($sql_link, $query_pts . implode(", ", $values_pts));
                }
            }

            // Update arm count on gauging header
            tep_db_query($sql_link, "UPDATE " . TABLE_DATA_JGE . " SET nb_bras='" . $nb_bras_real . "' WHERE id=" . $id_jge);

            // Log the action
            $today_us    = date('Y-m-d H:i:s');
            $info_action = post_secure($sql_link, $info_action);
            tep_db_query($sql_link,
                "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
                 VALUES (" . $id_user_agent . ", '" . $type_action . "', '" . $info_action . "', '" . $today_us . "')");

            tep_db_query($sql_link, "COMMIT");
        }
        catch (Exception $e)
        {
            // ROLLBACK can be enabled here once tep_db_query's error behavior is
            // confirmed (it must throw an exception for this catch to trigger).
            // tep_db_query($sql_link, "ROLLBACK");
            $msg_info_details .= TEXT_JGE_SAVE_ERR_TRANSACTION . "<br><br>" . TEXT_JGE_SAVE_ERR_EXCEPTION . $e->getMessage();
            $erreur = true;
        }
    }
    else
    {
        $msg_info .= "<span style='font-size:16px;'>" . TEXT_JGE_SAVE_ERR_GENERAL . "</span><br><br>";
        $erreur    = true;
    }
}
else
{
    $msg_info .= "<span style='font-size:16px;'>" . TEXT_JGE_SAVE_ERR_METHOD . "</span><br><br>";
    $erreur    = true;
}


// -----------------------------------------------
// Return result as JSON

$msg_info .= $msg_info_details;

echo json_encode([
    'erreur'     => $erreur,
    'id_station' => $id_station,
    'id_jge'     => $id_jge,
    'msg_info'   => $msg_info,
]);
?>
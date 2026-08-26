<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging (JGE) entry and automated flow rate calculation page
----------------------------------------
*/

require('include/application_top.php');

$message_info          = '';
$message_suprr_liaison = '';
$row                   = 0;
$reference             = '';
$libelle               = '';
$today                 = date('d-m-Y');
$today_us              = date('Y-m-d');
$date_format           = 'd-m-Y';
$current_time          = date('H:i:s');

$id_region       = $region_default;
$id_commune      = 0;
$id_station_old  = '';
$nb_bras_tab     = 0;
$modif           = false;
$error_jge       = false;
$from_nomad      = 1;
$new_nomad       = 1;
$hp_load         = 0;


// -----------------------------------------------
// Query: Field agents lookup table

$agent_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom, prenom FROM " . TABLE_AGENT . " WHERE terrain = 1 ORDER BY nom ASC");

while ($agent = tep_db_fetch_array($agent_query))
{
    $nom_agent      = ucwords(strtolower(noaccent(html_entity_decode($agent['nom']    ?? ''))));
    $prenom_agent   = noaccent(html_entity_decode($agent['prenom'] ?? ''));
    $prenom_initial = strtoupper(substr($prenom_agent, 0, 1)) . '.';
    $agent_array[$agent['id']] = $prenom_initial . ' ' . $nom_agent;
}


// -----------------------------------------------
// Query: Hydrometric stations (gauging type, current territory)

$station_query = tep_db_query($sql_link,
    "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.active_station, s.id_region
     FROM " . TABLE_STATION . " s
     JOIN " . TABLE_REGION  . " r ON s.id_region = r.id_region
     WHERE s.station_type = 11 AND r.id_territoire = " . $territoire_id . "
     ORDER BY s.nom_station");

while ($station = tep_db_fetch_array($station_query))
{
    $station_array[] = [
        'id'           => $station['id_station'],
        'act_station'  => ($station['active_station'] == 1),
        'nom_station'  => html_entity_decode($station['nom_station']  ?? ''),
        'code_station' => html_entity_decode($station['code_station'] ?? ''),
    ];
}


// -----------------------------------------------
// Query: Reference data tables (site, method, riverbed, type)

$data_jge_site_query = tep_db_query($sql_link, "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_SITE);
while ($r = tep_db_fetch_array($data_jge_site_query))
{
    $data_jge_site_array[$r['id']] = ['titre' => html_entity_decode($r['titre'] ?? ''), 'obs' => html_entity_decode($r['obs'] ?? '')];
}

$data_jge_methode_query = tep_db_query($sql_link, "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_METHODE);
while ($r = tep_db_fetch_array($data_jge_methode_query))
{
    $data_jge_methode_array[$r['id']] = ['titre' => html_entity_decode($r['titre'] ?? ''), 'obs' => html_entity_decode($r['obs'] ?? '')];
}

$data_jge_fondlit_query = tep_db_query($sql_link, "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_FONDLIT);
while ($r = tep_db_fetch_array($data_jge_fondlit_query))
{
    $data_jge_fondlit_array[$r['id']] = ['titre' => html_entity_decode($r['titre'] ?? ''), 'obs' => html_entity_decode($r['obs'] ?? '')];
}

$data_jge_type_query = tep_db_query($sql_link, "SELECT DISTINCT id, titre, obs FROM " . TABLE_DATA_JGE_TYPE);
while ($r = tep_db_fetch_array($data_jge_type_query))
{
    $data_jge_type_array[$r['id']] = ['titre' => html_entity_decode($r['titre'] ?? ''), 'obs' => html_entity_decode($r['obs'] ?? '')];
}


// -----------------------------------------------
// Query: Current meters (moulinets)

$moulinet_query = tep_db_query($sql_link, "SELECT DISTINCT id, num, fabricant, obs FROM " . TABLE_MOULINET);
while ($moulinet = tep_db_fetch_array($moulinet_query))
{
    $moulinet_array[$moulinet['id']] = [
        'num'       => html_entity_decode($moulinet['num']       ?? ''),
        'fabricant' => html_entity_decode($moulinet['fabricant'] ?? ''),
        'obs'       => html_entity_decode($moulinet['obs']       ?? ''),
    ];
}

// Tri des moulinets : C2 en premier, puis C31, puis les autres (tri alphabétique à l'intérieur)
uasort($moulinet_array, function($a, $b) {
    // Extraire le préfixe (premier mot avant l'espace) de chaque num
    $prefixA = strtoupper(explode(' ', trim($a['num']))[0]);
    $prefixB = strtoupper(explode(' ', trim($b['num']))[0]);

    // Fonction qui donne un "poids" de tri selon le préfixe
    $poids = function($prefix) {
        if ($prefix === 'C2')  return 1;
        if ($prefix === 'C31') return 2;
        return 3; // tous les autres
    };

    $pA = $poids($prefixA);
    $pB = $poids($prefixB);

    // Si les catégories diffèrent, on trie par catégorie
    if ($pA !== $pB) return $pA - $pB;

    // Sinon, tri alphabétique (naturel, pour que "C2 9" vienne avant "C2 10")
    return strnatcasecmp($a['num'], $b['num']);
});

// -----------------------------------------------
// Query: Helical flowmeters
// Equation coefficients stored in hidden fields so JS can compute
// velocity from rotation counts instantly without extra AJAX calls

$data_helice_hidden = '';

$helice_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num, diametre, pas, l1, a1, b1, l2, a2, b2, a3, b3, fabricant, obs
     FROM " . TABLE_HELICE . " ORDER BY num ASC");

while ($helice = tep_db_fetch_array($helice_query))
{
    $id_helice = $helice['id'];
    $l1 = floatval($helice['l1']); $a1 = floatval($helice['a1']); $b1 = floatval($helice['b1']);
    $l2 = floatval($helice['l2']); $a2 = floatval($helice['a2']); $b2 = floatval($helice['b2']);
    $a3 = floatval($helice['a3']); $b3 = floatval($helice['b3']);

    // Hidden inputs expose equation coefficients to JavaScript
    $data_helice_hidden .= "
        <input type='hidden' name='l1_{$id_helice}' id='l1_{$id_helice}' value='{$l1}'>
        <input type='hidden' name='a1_{$id_helice}' id='a1_{$id_helice}' value='{$a1}'>
        <input type='hidden' name='b1_{$id_helice}' id='b1_{$id_helice}' value='{$b1}'>
        <input type='hidden' name='l2_{$id_helice}' id='l2_{$id_helice}' value='{$l2}'>
        <input type='hidden' name='a2_{$id_helice}' id='a2_{$id_helice}' value='{$a2}'>
        <input type='hidden' name='b2_{$id_helice}' id='b2_{$id_helice}' value='{$b2}'>
        <input type='hidden' name='a3_{$id_helice}' id='a3_{$id_helice}' value='{$a3}'>
        <input type='hidden' name='b3_{$id_helice}' id='b3_{$id_helice}' value='{$b3}'>
    ";

    $helice_array[$id_helice] = [
        'num'       => html_entity_decode($helice['num']       ?? ''),
        'diametre'  => floatval($helice['diametre']),
        'pas'       => floatval($helice['pas']),
        'l1' => $l1, 'a1' => $a1, 'b1' => $b1,
        'l2' => $l2, 'a2' => $a2, 'b2' => $b2,
        'a3' => $a3, 'b3' => $b3,
        'fabricant' => html_entity_decode($helice['fabricant'] ?? ''),
        'obs'       => html_entity_decode($helice['obs']       ?? ''),
    ];
}


// -----------------------------------------------
// Query: Quality codes

$code_qual_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data
     FROM " . TABLE_DATA_QUALITE . "
     WHERE (id_eq_type = 0 OR id_eq_type = 11)
     ORDER BY id_eq_type DESC, init_qualite_data");

while ($code_qual = tep_db_fetch_array($code_qual_query))
{
    $code_qual_array[$code_qual['id_data_qualite']] = [
        'init_qualite_data' => html_entity_decode($code_qual['init_qualite_data'] ?? ''),
        'nom_qualite_data'  => html_entity_decode($code_qual['nom_qualite_data']  ?? ''),
    ];
}


// -----------------------------------------------
// Edit mode is enabled when a gauging reference (?ref=) is present.
// Arm deletion is no longer handled here: it now goes through the AJAX
// endpoint process_jge_bras_delete.php (see block_verifdel_jge_bras.php).

if (isset($_GET['ref']))
{
    $ref_id = mysqli_real_escape_string($sql_link, trim(addslashes($_GET['ref'])));
    $modif  = true;
}


// -----------------------------------------------
// Initialize gauging variables (defaults for a new record)

$nbb             = 0;
$id_jge          = 0;
$id_station      = 0;
$code_station    = '';
$nom_station     = '';

$date_jge        = $today;
$heure_jge       = $current_time;
$date_heure_jge  = $today . ' ' . $current_time;

$x_gps           = '';
$y_gps           = '';

$dist_site       = '';
$id_site         = 0;
$id_sitejge      = 0; // Site detail is in a separate table — pending DB update

$id_methode      = 0;
$id_typejge      = 0;

$depouil_hmoy    = '';
$depouil_q       = '';
$depouil_sect    = '';
$depouil_vmoy    = '';
$depouil_vsurf   = '';
$depouil_rh      = '';
$depouil_profmoy = '';
$depouil_nbvert  = 0;

$code_qualite    = 0;
$obs_jge         = '';
$fichier_lien    = '';

$title_jge = TEXT_JGE_PAGE_NEW;


// -----------------------------------------------
// Load existing gauging record (edit mode)

if ($modif)
{
    $jge_query = tep_db_query($sql_link,
        "SELECT DISTINCT jge.id, jge.from_nomad, jge.new_nomad, jge.hp_load,
                         jge.id_station, s.code_station, s.nom_station, jge.datetime,
                         jge.x_gps, jge.y_gps, jge.nb_bras, jge.dist_site, jge.id_site,
                         jge.id_methode, jge.id_typejge,
                         jge.depouil_hmoy, jge.depouil_q, jge.depouil_sect,
                         jge.depouil_vmoy, jge.depouil_vsurf, jge.depouil_rh,
                         jge.depouil_profmoy, jge.depouil_nbvert,
                         jge.code_qualite, jge.obs, jge.fichier, jge.agents
         FROM " . TABLE_DATA_JGE . " jge
         JOIN " . TABLE_STATION   . " s ON jge.id_station = s.id_station
         JOIN " . TABLE_REGION    . " r ON s.id_region    = r.id_region
         WHERE jge.id = " . $ref_id);

    $jge = tep_db_fetch_array($jge_query);

    if (isset($jge))
    {
        $id_jge       = html_entity_decode($jge['id']           ?? '');
        $from_nomad   = $jge['from_nomad'];
        $new_nomad    = $jge['new_nomad'];
        $hp_load      = $jge['hp_load'];
        $id_station   = html_entity_decode($jge['id_station']   ?? '');
        $code_station = html_entity_decode($jge['code_station'] ?? '');
        $nom_station  = html_entity_decode($jge['nom_station']  ?? '');
        $title_jge    = $code_station . ' - ' . $nom_station;

        $tab_date_heure_jge = explode(' ', $jge['datetime']);
        $date_jge           = dateus_fr($tab_date_heure_jge[0]);
        $heure_jge          = $tab_date_heure_jge[1];
        $date_heure_jge     = $date_jge . ' ' . $heure_jge;

        $x_gps      = html_entity_decode($jge['x_gps'] ?? '');
        $y_gps      = html_entity_decode($jge['y_gps'] ?? '');
        $dist_site  = floatval($jge['dist_site']);
        //$id_site    = html_entity_decode($jge['id_site']    ?? '');
        $id_sitejge = html_entity_decode($jge['id_site']    ?? '');

        $id_methode = html_entity_decode($jge['id_methode'] ?? '');
        $id_typejge = html_entity_decode($jge['id_typejge'] ?? '');

        $depouil_hmoy    = round(floatval($jge['depouil_hmoy']),  3);
        $depouil_q       = round(floatval($jge['depouil_q']),     3);
        $depouil_sect    = round(floatval($jge['depouil_hmoy']),  3);
        $depouil_vmoy    = round(floatval($jge['depouil_q']),     3);
        $depouil_vsurf   = round(floatval($jge['depouil_hmoy']),  3);
        $depouil_rh      = round(floatval($jge['depouil_q']),     3);
        $depouil_profmoy = round(floatval($jge['depouil_hmoy']),  3);
        $depouil_nbvert  = $jge['depouil_q'];

        $code_qualite = $jge['code_qualite'];
        $obs_jge      = html_entity_decode($jge['obs']     ?? '');
        $fichier_lien = html_entity_decode($jge['fichier'] ?? '');
        $agents_text  = html_entity_decode($jge['agents']  ?? '');


        // -----------------------------------------------
        // Query: Arm records (bras) for this gauging
        // Using bras table as source of truth for arm count
        // (more reliable than nb_bras field for legacy data)

        $jge_bras_query = tep_db_query($sql_link,
            "SELECT DISTINCT b.id, b.id_moulinet, b.id_helice, b.id_saumon, b.perche_diam,
                             b.berge_depart, b.heure_first, b.h_ech_first, b.heure_end, b.h_ech_end,
                             b.fond_text,
                             b.depouil_hmoy, b.depouil_nbvert, b.depouil_profmoy, b.depouil_distmax,
                             b.depouil_vmoy, b.depouil_vsurf, b.depouil_surfmouil, b.depouil_perimouil,
                             b.depouil_rh, b.depouil_q, b.obs
             FROM " . TABLE_DATA_JGE_BRAS . " b
             WHERE id_jge = " . $id_jge);

        while ($jge_bras = tep_db_fetch_array($jge_bras_query))
        {
            $nbb++;
            $jge_bras_array[$nbb] = [
                'id_bras'                => html_entity_decode($jge_bras['id']           ?? ''),
                'id_moulinet'            => html_entity_decode($jge_bras['id_moulinet']  ?? ''),
                'id_helice'              => html_entity_decode($jge_bras['id_helice']    ?? ''),
                'id_saumon'              => html_entity_decode($jge_bras['id_saumon']    ?? ''),
                'perche_diam'            => round(floatval($jge_bras['perche_diam']),    3),
                'berge_depart'           => html_entity_decode($jge_bras['berge_depart'] ?? ''),
                'heure_first'            => html_entity_decode($jge_bras['heure_first']  ?? ''),
                'h_ech_first'            => round(floatval($jge_bras['h_ech_first']),    3),
                'heure_end'              => html_entity_decode($jge_bras['heure_end']    ?? ''),
                'h_ech_end'              => round(floatval($jge_bras['h_ech_end']),      3),
                'fond_text'              => html_entity_decode($jge_bras['fond_text']    ?? ''),
                'depouil_bras_hmoy'      => round(floatval($jge_bras['depouil_hmoy']),      3),
                'depouil_bras_nbvert'    => round(floatval($jge_bras['depouil_nbvert']),    3),
                'depouil_bras_profmoy'   => round(floatval($jge_bras['depouil_profmoy']),   3),
                'depouil_bras_distmax'   => round(floatval($jge_bras['depouil_distmax']),   3),
                'depouil_bras_vmoy'      => round(floatval($jge_bras['depouil_vmoy']),      3),
                'depouil_bras_vsurf'     => round(floatval($jge_bras['depouil_vsurf']),     3),
                'depouil_bras_surfmouil' => round(floatval($jge_bras['depouil_surfmouil']), 3),
                'depouil_bras_perimouil' => round(floatval($jge_bras['depouil_perimouil']), 3),
                'depouil_bras_rh'        => round(floatval($jge_bras['depouil_rh']),        3),
                'depouil_bras_q'         => round(floatval($jge_bras['depouil_q']),         3),
                'bras_obs'               => html_entity_decode($jge_bras['obs']          ?? ''),
            ];
        }

        $nb_bras_tab = $nbb;
    }
    else
    {
        $error_jge = true;
    }
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

    // Info banner (red border by default; AJAX flows recolor it via JS)
    if (tep_not_null($message_info))
    {
        echo "<div id='contenu_info' style='border:2px solid #930000'>" . $message_info . "</div>";
    }
    else
    {
        echo "<div id='contenu_info' style='display:none;'></div>";
    }

    // Popup blocks
    require(DIR_WS_JAUGEAGE  . 'block_verifdel_jge_bras.php'); // Arm deletion confirmation
    require(DIR_WS_JAUGEAGE  . 'block_jge_affiche_etl.php');   // Rating curve popup
    require(DIR_WS_STRUCTURE . 'block_wait.php');               // Loading overlay

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";

            if (!$error_jge)
            {
                echo "<form id='formJGE' style='height:100%;'>";

                    echo $data_helice_hidden;

                    echo "<input type='hidden' value='" . $id_user       . "' name='id_user_agent'>";
                    echo "<input type='hidden' value='" . $territoire_id . "' name='territoire_id'>";
                    echo "<input type='hidden' value='" . $id_jge        . "' name='id_jge'>";

                    // ---- Page title and save button ----
                    echo "<h1 style='display:flex;align-items:center;gap:20px;'>";
                        echo "<span>" . TEXT_JGE_PAGE_LABEL . "</span>";
                        echo "<span id='titre_jge' style='font-size:18px;font-weight:bold;color:#000;'>" . $title_jge . "</span>";
                        if (HP_VERSION == 'Serveur' || ($from_nomad > 0 && $hp_load < 1))
                        {
                            echo "<input type='button' id='save_jge' name='save_jge'
                                         style='min-width:130px;padding:6px 18px;font-weight:bold;cursor:pointer;
                                                background-color:#fff;color:#930000;border:1px solid #930000;border-radius:4px;
                                                transition:background-color 0.15s,color 0.15s;'
                                         onmouseover=\"this.style.backgroundColor='#930000';this.style.color='#fff';\"
                                         onmouseout=\"this.style.backgroundColor='#fff';this.style.color='#930000';\"
                                         value='" . TEXT_JGE_PAGE_SAVE . "'
                                         onClick='saveJGE(event)';/>";
                        }
                    echo "</h1>";


                    // -----------------------------------------------
                    // Left sidebar: summary results + collapsible panels

                    echo "<div id='cadre_graph' style='float:left;width:320px;height:calc(100% - 90px);'>\n";

                        // ---- Key results box ----
                        echo "<div id='boxpopup' class='select-top' style='width:300px;margin-bottom:10px;padding:5px 10px;border:2px solid #016A70;'>\n";

                            // Flow rate
                            echo "<div id='boite_small' style='width:95%;margin-right:0;margin-bottom:5px;'>\n";
                                echo "<p style='float:left;font-weight:bold;color:#930000;font-size:13px;margin-top:10px;'>" . TEXT_JGE_SIDEBAR_Q . "</p>";
                                $value = $modif ? $depouil_q : '';
                                echo "<input type='text' style='float:right;width:60px;height:18px;border:1px solid #ddd;background-color:#FFFFDD;font-size:13px;'
                                             id='jge_q' name='jge_q' value='" . $value . "'>\n";
                            echo "</div>";

                            // Mean height
                            echo "<div id='boite_small' style='width:95%;margin-right:0;'>\n";
                                echo "<p style='float:left;font-weight:bold;color:#930000;font-size:13px;margin-top:10px;'>" . TEXT_JGE_SIDEBAR_HMOY . "</p>";
                                $value = $modif ? $depouil_hmoy : '';
                                echo "<input type='text' style='float:right;width:60px;height:18px;border:1px solid #ddd;background-color:#FFFFDD;font-size:13px;'
                                             id='jge_hmoy' name='jge_hmoy' value='" . $value . "'>\n";
                            echo "</div>";

                            // Rating curve link
                            echo "<div id='boite_small' style='width:95%;text-align:center;margin-right:0;'>\n";
                                echo "<p style='float:left;width:100%;font-weight:bold;color:#36802d;font-size:15px;margin:8px 0;'>";
                                    echo "<span style='cursor:pointer;'
                                                title='" . TEXT_JGE_SIDEBAR_ETL_TITLE . "'
                                                onMouseOver=\"this.style.textDecoration='underline';\"
                                                onMouseOut=\"this.style.color=''; this.style.textDecoration='none';\"
                                                onClick='afficheETL();'>";
                                        echo TEXT_JGE_SIDEBAR_ETL_LINK;
                                    echo "</span>";
                                echo "</p>";
                            echo "</div>";

                        echo "</div>";

                        echo "<div style='float:left;width:100%;height:calc(95% - 90px);overflow-y:auto;'>\n";

                            // ---- Date / Time / Station ----
                            echo "<div id='boxpopup' class='select-top' style='width:290px;margin-top:5px;padding:10px;padding-right:0;'>\n";

                                echo "<div id='boite_small' style='width:95%;margin-right:0;margin-bottom:5px;'>\n";
                                    echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_SIDEBAR_DATE . "</p>";
                                    $value = $modif ? $date_jge : $today;
                                    echo "<input style='float:right;width:65px;'"
                                                    . " name='date_jge' id='date_jge' type='text'"
                                                    . " onfocus='initDatepickers(this)'"
                                                    . " placeholder='dd-mm-yyyy' value='" . $value . "'>\n";
                                echo "</div>";

                                echo "<div id='boite_small' style='width:95%;margin-right:0;margin-bottom:5px;'>\n";
                                    echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_SIDEBAR_HEURE . "</p>";
                                    $value = $modif ? $heure_jge : $current_time;
                                    echo "<input style='float:right;width:65px;'"
                                                    . " name='heure_jge' id='heure_jge' type='text'"
                                                    . " placeholder='hh:mm:ss' value='" . $value . "'>\n";
                                echo "</div>";

                                // Station selector
                                echo "<div id='boite_small' style='width:95%;margin-right:0;'>\n";
                                    echo "<p style='float:left;width:100%;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_SIDEBAR_STATION . "</p>";
                                    echo "<select name='select_station' id='select_station' style='float:left;width:100%;'>";
                                        echo "<option value='0'>-</option>";
                                        if (isset($station_array))
                                        {
                                            for ($c = 0; $c < sizeof($station_array); $c++)
                                            {
                                                if ($station_array[$c]['id'] == $id_station)
                                                {
                                                    echo "<option value='" . $station_array[$c]['id'] . "' selected>" . $station_array[$c]['code_station'] . " - " . $station_array[$c]['nom_station'] . "</option>";
                                                }
                                                elseif ($station_array[$c]['act_station'])
                                                {
                                                    echo "<option value='" . $station_array[$c]['id'] . "'>" . $station_array[$c]['code_station'] . " - " . $station_array[$c]['nom_station'] . "</option>";
                                                }
                                            }
                                        }
                                    echo "</select>";
                                echo "</div>";

                                echo "<hr>\n";

                                // Quality code
                                echo "<div id='boite_small' style='width:95%;margin-right:0;'>\n";
                                    echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_SIDEBAR_CODE_QUAL . "</p>";
                                    echo "<select name='select_code_qual' id='select_code_qual' style='float:right;width:120px;'>";
                                        echo "<option value='0'>-</option>";
                                        if (isset($code_qual_array))
                                        {
                                            foreach ($code_qual_array as $key => $value)
                                            {
                                                $selected = ($key == $code_qualite) ? 'selected' : '';
                                                echo "<option value='" . $key . "' " . $selected . " title='" . $code_qual_array[$key]['nom_qualite_data'] . "'>";
                                                    echo $code_qual_array[$key]['init_qualite_data'] . " - " . $code_qual_array[$key]['nom_qualite_data'];
                                                echo "</option>";
                                            }
                                        }
                                    echo "</select>";
                                echo "</div>";

                            echo "</div>";


                            
                            if(INIT_T == 'NC')
                            {
                                // ---- Collapsible: Location ----
                                echo "<div id='boxpopup' class='select-top' style='width:290px;margin-top:5px;padding:5px 10px;padding-right:0;'>\n";
                                    echo "<h4 class='toggle-jge' data-menu-id='jge-situation' style='width:97%;margin:0;padding-left:0;border:none;'>";
                                        echo "<span style='margin:0;'>" . TEXT_JGE_PANEL_SITUATION . "</span>";
                                        echo "<span class='arrow'>&#9660;</span>";
                                    echo "</h4>";
                                    echo "<div class='navigation' style='display:none;border-top:1px solid #d2d2d2;'>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;margin-top:10px;margin-bottom:5px;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_DIST_SITE . "</p>";
                                            $value = $modif ? $dist_site : '';
                                            echo "<input type='text' style='float:right;width:40px;' id='dist_site' name='dist_site' value='" . $value . "'>\n";
                                        echo "</div>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;margin-bottom:5px;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_SITE . "</p>";
                                            echo "<select name='select_site_jge' id='select_site_jge' style='float:right;width:120px;'>";
                                                echo "<option value='0'>-</option>";
                                                if (isset($data_jge_site_array))
                                                {
                                                    foreach ($data_jge_site_array as $key => $value)
                                                    {
                                                        $selected = ($key == $id_sitejge) ? 'selected' : '';
                                                        echo "<option value='" . $key . "' " . $selected . " title='" . $data_jge_site_array[$key]['obs'] . "'>" . $data_jge_site_array[$key]['titre'] . "</option>";
                                                    }
                                                }
                                            echo "</select>";
                                        echo "</div>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;margin-bottom:5px;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_GPS_X . "</p>";
                                            $value = $modif ? $x_gps : '';
                                            echo "<input type='text' style='float:right;width:120px;' id='x_gps' name='x_gps' value='" . $value . "'>\n";
                                        echo "</div>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_GPS_Y . "</p>";
                                            $value = $modif ? $y_gps : '';
                                            echo "<input type='text' style='float:right;width:120px;' id='y_gps' name='y_gps' value='" . $value . "'>\n";
                                        echo "</div>";

                                    echo "</div>";
                                echo "</div>";

                                // ---- Collapsible: Method ----
                                echo "<div id='boxpopup' class='select-top' style='width:290px;margin-top:5px;padding:5px 10px;padding-right:0;'>\n";
                                    echo "<h4 class='toggle-jge' data-menu-id='jge-methode' style='width:97%;margin:0;padding-left:0;border:none;'>";
                                        echo "<span style='margin:0;'>" . TEXT_JGE_PANEL_METHODE . "</span>";
                                        echo "<span class='arrow'>&#9660;</span>";
                                    echo "</h4>";
                                    echo "<div class='navigation' style='display:none;border-top:1px solid #d2d2d2;'>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;margin-top:10px;margin-bottom:5px;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_TYPE . "</p>";
                                            echo "<select name='select_type_jge' id='select_type_jge' style='float:right;width:120px;'>";
                                                if (isset($data_jge_type_array))
                                                {
                                                    foreach ($data_jge_type_array as $key => $value)
                                                    {
                                                        $selected = ($key == $id_typejge) ? 'selected' : '';
                                                        echo "<option value='" . $key . "' " . $selected . " title='" . $data_jge_type_array[$key]['obs'] . "'>" . $data_jge_type_array[$key]['titre'] . "</option>";
                                                    }
                                                }
                                            echo "</select>";
                                        echo "</div>";

                                        echo "<div id='boite_small' style='width:95%;margin-right:0;'>\n";
                                            echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:5px;'>" . TEXT_JGE_PANEL_METHODE_SEL . "</p>";
                                            echo "<select name='select_methode_jge' id='select_methode_jge' style='float:right;width:120px;'>";
                                                if (isset($data_jge_methode_array))
                                                {
                                                    foreach ($data_jge_methode_array as $key => $value)
                                                    {
                                                        $selected = ($key == $id_methode) ? 'selected' : '';
                                                        echo "<option value='" . $key . "' " . $selected . " title='" . $data_jge_methode_array[$key]['obs'] . "'>" . $data_jge_methode_array[$key]['titre'] . "</option>";
                                                    }
                                                }
                                            echo "</select>";
                                        echo "</div>";

                                    echo "</div>";
                                echo "</div>";
                            }


                            // ---- Collapsible: Details ----
                            echo "<div id='boxpopup' class='select-top' style='width:290px;margin:5px 0;padding:5px 10px;padding-right:0;'>\n";
                                echo "<h4 class='toggle-jge' data-menu-id='jge-details' style='width:97%;margin:0;padding-left:0;border:none;'>";
                                    echo "<span style='margin:0;'>" . TEXT_JGE_PANEL_DETAILS . "</span>";
                                    echo "<span class='arrow'>&#9660;</span>";
                                echo "</h4>";
                                echo "<div class='navigation' style='display:none;border-top:1px solid #d2d2d2;'>";

                                    // Agents / participants
                                    echo "<div id='boite_small' style='width:95%;margin-right:0;margin-top:10px;'>\n";
                                        echo "<p style='float:left;width:100%;font-weight:bold;color:#000;font-size:11px;margin-top:0;'>" . TEXT_JGE_PANEL_AGENTS . "</p>";
                                        if (!isset($agents_text) || is_null($agents_text)) { $agents_text = ''; }
                                        if (isset($agent_array))
                                        {
                                            foreach ($agent_array as $key => $value)
                                            {
                                                $checked = (strpos($agents_text, $value) !== false) ? 'checked' : '';
                                                echo "<div style='float:left;'>\n";
                                                    echo "<input class='input_texte' style='width:25px;margin-left:0;padding:0;'
                                                                 name='check_agent_" . $key . "' id='check_agent_" . $key . "'
                                                                 type='checkbox' data-value='" . $value . "'
                                                                 onchange='updateSelectedAgents();' " . $checked . ">";
                                                    echo "<span style='float:left;margin-right:5px;font-size:10px;padding-top:3px;'>" . $value . "</span>";
                                                echo "</div>\n";
                                            }
                                        }
                                        echo "<input type='text' style='float:left;width:95%;' id='agents_text' name='agents_text' value='" . $agents_text . "'>\n";
                                    echo "</div>";

                                    // Observation
                                    echo "<div id='boite_small' style='width:95%;margin-right:0;margin-top:10px;'>\n";
                                        echo "<p style='float:left;font-weight:bold;color:#000;font-size:11px;margin-top:0;'>" . TEXT_JGE_PANEL_OBS . "</p>";
                                        $value = $modif ? $obs_jge : '';
                                        echo "<textarea id='obs' name='obs' style='width:95%;height:60px;font-size:11px;'>" . $value . "</textarea>\n";
                                    echo "</div>";

                                    // File link
                                    echo "<div id='boite_small' style='width:95%;margin-right:0;margin-top:10px;'>\n";
                                        echo "<p style='float:left;width:100%;font-weight:bold;color:#000;font-size:11px;margin-top:0;'>" . TEXT_JGE_PANEL_FICHIER . "</p>";
                                        $value = $modif ? $fichier_lien : '';
                                        echo "<input type='text' style='float:left;width:95%;' id='file_link' name='file_link' value='" . $value . "'>\n";
                                    echo "</div>";

                                echo "</div>";
                            echo "</div>";

                        echo "</div>";

                    echo "</div>";


                    // -----------------------------------------------
                    // Tab panel: one tab per gauging arm (bras)

                    echo "<div style='float:none;margin-left:345px;'>\n";
                        echo "<div id='onglet'>";
                            echo "<ul id='menu_onglet'>";
                                echo "<input type='hidden' value='" . $nb_bras_tab . "' id='nb_bras' name='nb_bras'>";

                                if ($nb_bras_tab > 0)
                                {
                                    for ($nbb = 1; $nbb <= $nb_bras_tab; $nbb++)
                                    {
                                        $class = ($nbb == 1) ? 'actif' : '';
                                        echo "<li onClick=\"javascript:ChangeOnglet_2(" . $nbb . ", " . ($nb_bras_tab + 1) . ", 'onglet-', 'contenu-');\"
                                                id='onglet-" . $nbb . "' class='" . $class . "' style='width:80px;'>";
                                            echo TEXT_JGE_TAB_BRAS . " " . $nbb;
                                        echo "</li>\n";
                                    }
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(" . $nbb . ", " . ($nb_bras_tab + 1) . ", 'onglet-', 'contenu-');\"
                                            id='onglet-" . $nbb . "'
                                            style='width:10px;font-size:22px;font-weight:bold;padding-top:2px;padding-bottom:8px;'
                                            title='" . TEXT_JGE_TAB_NEW_BRAS . "'>+</li>\n";
                                }
                                else
                                {
                                    $nbb = 1;
                                    echo "<li onClick=\"javascript:ChangeOnglet_2(1, 1, 'onglet-', 'contenu-');\" id='onglet-1'
                                            style='width:20px;font-size:22px;font-weight:bold;padding-top:2px;padding-bottom:8px;'
                                            title='" . TEXT_JGE_TAB_NEW_BRAS . "'>+</li>\n";
                                }

                            echo "</ul>";

                            if ($nb_bras_tab > 0)
                            {
                                for ($nbb = 1; $nbb <= $nb_bras_tab; $nbb++)
                                {
                                    $display = ($nbb == 1) ? 'display:block;' : 'display:none;';
                                    echo "<div id='contenu-" . $nbb . "' class='contenu' style='" . $display . "background:none;width:100%;'>";
                                        require(DIR_WS_JAUGEAGE . 'form_jge_bras.php');
                                    echo "</div>";
                                }
                                echo "<div id='contenu-" . $nbb . "' class='contenu' style='display:none;background:none;width:100%;'>";
                                    require(DIR_WS_JAUGEAGE . 'form_jge_bras.php');
                                echo "</div>";
                            }
                            else
                            {
                                echo "<div id='contenu-1' class='contenu' style='display:block;background:none;width:100%;'>";
                                    require(DIR_WS_JAUGEAGE . 'form_jge_bras.php');
                                echo "</div>";
                            }

                        echo "</div>";
                    echo "</div>";

                echo "</form>\n";
            }
            else
            {
                echo "<h1><span>" . TEXT_JGE_PAGE_TITLE_ERROR . "</span></h1>";
                echo "<div id='boxpopup' style='margin-left:1%;'>\n";
                    echo "<p class='alert'>" . TEXT_JGE_PAGE_NOT_FOUND . "</p>";
                echo "</div>";
            }

        echo "</div>";
    echo "</div>";

    
    
    require(DIR_WS_JAUGEAGE  . 'block_jge_pts.php');           // Measurement points popup
    
    
    require('include/application_bottom.php');



echo "</body>";
echo "</html>";



?>

<script>

    // -----------------------------------------------
    // Global element references

    var boxWait           = document.getElementById('box_wait');
    var contenuInfo       = document.getElementById('contenu_info');
    var blockValidDelBras = document.getElementById('box_del_jge_bras');
    var boxAfficheEtl     = document.getElementById('box_jge_affiche_etl');
    var boxGraphEtl       = document.getElementById('plot_etl');
    var boxEtlGraphWait   = document.getElementById('wait_graph');
    var infoEtl           = document.getElementById('info_etl');

    var idStation = <?php echo $id_station; ?>;
    var jgeHmoy   = document.getElementById('jge_hmoy');
    var jgeQ      = document.getElementById('jge_q');
    var jgeDate   = document.getElementById('date_jge');
    var jgeHeure  = document.getElementById('heure_jge');


    // -----------------------------------------------
    // Save gauging record via AJAX

    // -----------------------------------------------
    // Unsaved-changes safety net
    // A successful point calculation fills the form but does NOT persist it:
    // the user must click Save. We track a "dirty" flag so we can (1) highlight
    // the Save button and (2) warn before the page is left (beforeunload).

    var jgeDirty = false;

    function markJgeUnsaved()
    {
        jgeDirty = true;
        var btn = document.getElementById('save_jge');
        if (btn)
        {
            // Solid red emphasis + "unsaved" marker in the label.
            btn.style.backgroundColor = '#930000';
            btn.style.color           = '#fff';
            btn.style.boxShadow       = '0 0 0 3px rgba(147,0,0,0.25)';
            if (btn.value.indexOf('\u25CF') === -1)
            {
                btn.value = '\u25CF ' + btn.value;
            }
        }
    }

    function clearJgeUnsaved()
    {
        jgeDirty = false;

        // Also drop the yellow badge shown on every arm tab.
        if (typeof hideUnsavedWarning === 'function') { hideUnsavedWarning(); }

        var btn = document.getElementById('save_jge');
        if (btn)
        {
            btn.style.backgroundColor = '#fff';
            btn.style.color           = '#930000';
            btn.style.boxShadow       = 'none';
            btn.value = btn.value.replace(/^\u25CF\s*/, '');
        }
    }

    // Read-only accessor used by js_jge.js to preserve state across silent recomputes.
    function jgeIsDirty() { return jgeDirty; }

    // Native browser warning if the user tries to leave with unsaved changes.
    window.addEventListener('beforeunload', function(e)
    {
        if (jgeDirty)
        {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });


    function saveJGE(event)
    {
        boxWait.style.display = 'block';
        event.preventDefault();

        // Refresh the header fields (jge_q / jge_hmoy) from the arm result bars
        // before serialising. Values typed straight into an arm bar never went
        // through calc_q(), so without this the header would keep stale totals
        // while the arms are saved with the new ones.
        if (typeof syncHeaderFromBras === 'function') { syncHeaderFromBras(); }

        var form     = document.getElementById('formJGE');
        var formData = new FormData(form);

        // -----------------------------------------------
        // Strip empty measurement-point slots from the POST.
        // Each arm renders ~900 hidden point inputs whether filled or not,
        // which blows past PHP's max_input_vars (default 1000) on multi-arm
        // gaugings and silently truncates the request. The server treats a
        // missing field exactly like an empty one (isset ?: '') and only
        // inserts points whose profmesure is filled, so dropping empty slots
        // is functionally transparent. Done on the FormData (not the DOM):
        // no side effect on the form after a failed save.

        var pointFields   = ['vert', 'dist', 'profmax', 'profmesure', 'nbtour',
                             'tps', 'tourssec', 'vitesse', 'obs'];
        var emptySuffixes = [];

        formData.forEach(function(value, key)
        {
            if (key.indexOf('jge_bra_profmesure_') === 0 && String(value).trim() === '')
            {
                // key = jge_bra_profmesure_<bras>_<i> -> keep the <bras>_<i> suffix
                emptySuffixes.push(key.substring('jge_bra_profmesure_'.length));
            }
        });

        emptySuffixes.forEach(function(suffix)
        {
            pointFields.forEach(function(f)
            {
                formData.delete('jge_bra_' + f + '_' + suffix);
            });
        });

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/jaugeage/process_jge_save.php", true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var jsonResponse = JSON.parse(xhr.responseText);
                var erreur   = jsonResponse['erreur'];
                var id_jge   = jsonResponse['id_jge'];
                var msg_info = jsonResponse['msg_info'];

                contenuInfo.innerHTML     = msg_info;
                contenuInfo.style.display = 'block';

                if (!erreur)
                {
                    contenuInfo.style.border = '2px solid #09886d';
                    clearJgeUnsaved(); // saved → no longer dirty (prevents beforeunload on our own reload)
                    setTimeout(function() {
                        window.location.href = 'modif_jge.php?ref=' + id_jge;
                    }, 1000);
                }
                else
                {
                    contenuInfo.style.border = '2px solid #930000';
                }

                boxWait.style.display = 'none';
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Open arm deletion confirmation popup

    function delBras(id_bras)
    {
        blockValidDelBras.style.display = 'block';
        document.getElementById('del_bras').value = id_bras;
    }


    // -----------------------------------------------
    // Show rating curve (ETL) popup with Plotly graph

    function afficheETL()
    {
        contenuInfo.style.display = 'none';

        if (!isValidDate(jgeDate.value))
        {
            contenuInfo.innerHTML     = "<?php echo TEXT_JGE_ETL_ERR_DATE; ?>";
            contenuInfo.style.border  = '2px solid #930000';
            contenuInfo.style.display = 'block';
            return;
        }

        var jgeHmoyValue = parseFloat(jgeHmoy.value);
        var jgeQValue    = parseFloat(jgeQ.value);
        if (isNaN(jgeHmoyValue) || isNaN(jgeQValue))
        {
            contenuInfo.innerHTML     = "<?php echo TEXT_JGE_ETL_ERR_VALUES; ?>";
            contenuInfo.style.border  = '2px solid #930000';
            contenuInfo.style.display = 'block';
            return;
        }

        boxAfficheEtl.style.display   = 'block';
        boxGraphEtl.style.display     = 'none';
        boxEtlGraphWait.style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/jaugeage/process_jge_etlgraph.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                boxGraphEtl.style.display     = 'block';
                boxEtlGraphWait.style.display = 'none';

                var jsonResponse  = JSON.parse(xhr.responseText);
                infoEtl.innerHTML = jsonResponse['js_text'];

                initDraggableResize('title_box_etl', 'box_jge_affiche_etl');
                if (jsonResponse['edit_graph']) { eval(jsonResponse['js_graph']); }
            }
        };

        xhr.send(JSON.stringify({
            idStation: idStation,
            jgeHmoy:   jgeHmoy.value,
            jgeQ:      jgeQ.value,
            jgeDate:   jgeDate.value,
            jgeHeure:  jgeHeure.value,
            // Swap-axes preference, read live from the toggle in the popup.
            // The server returns the graph already drawn with axes swapped.
            swapAxes:  (document.getElementById('swap_axes_jge')
                        && document.getElementById('swap_axes_jge').checked) ? 1 : 0
        }));
    }


    // -----------------------------------------------
    // Update agents text field from checkboxes

    function updateSelectedAgents()
    {
        var checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name^="check_agent_"]'));

        var selectedValues = checkboxes
            .filter(function(cb) { return cb.checked; })
            .map(function(cb) { return cb.getAttribute('data-value').trim(); });

        var currentText = document.getElementById('agents_text').value;
        var manualText  = currentText
            .split(' / ')
            .map(function(v) { return v.trim(); })
            .filter(function(v) {
                return v !== '' &&
                    !selectedValues.includes(v) &&
                    !checkboxes.some(function(cb) { return cb.getAttribute('data-value').trim() === v; });
            });

        document.getElementById('agents_text').value = manualText.concat(selectedValues).join(' / ');
    }


    // -----------------------------------------------
    // Collapsible sidebar panels (jQuery)

    $(document).ready(function()
    {
        $('.toggle-jge').each(function()
        {
            var menuId     = $(this).data('menu-id');
            var isOpen     = menuStates[menuId] === 1;
            var navigation = $(this).nextAll('.navigation').first();
            var arrow      = $(this).find('.arrow');
            if (isOpen) { navigation.show(); arrow.html('&#9650;'); }
            else        { navigation.hide(); arrow.html('&#9660;'); }
        });

        $('.toggle-jge').click(function()
        {
            var id_user    = <?php echo json_encode($id_user); ?>;
            var navigation = $(this).nextAll('.navigation').first();
            var menuId     = $(this).data('menu-id');
            var isOpen     = navigation.is(':visible');

            navigation.slideToggle('slow', function()
            {
                var arrow = $(this).prevAll('.toggle-jge').find('.arrow');
                arrow.html(navigation.is(':visible') ? '&#9650;' : '&#9660;');

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "include/structure/box/process_menu.php", true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.send(JSON.stringify({ id_user: id_user, menu_id: menuId, is_open: !isOpen }));
            });
        });

        $('#select_station').select2({ placeholder: 'Select Station...', allowClear: true });
        //$('#select_code_qual').select2({ placeholder: 'Select Quality Code...', allowClear: true });
    });


    // -----------------------------------------------
    // Date validation helpers

    function isValidDate(dateString)
    {
        const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
        if (!dateRegex.test(dateString)) { return false; }
        const [day, month, year] = dateString.split("-").map(Number);
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
    }

    function parseDate(dateString)
    {
        const [day, month, year] = dateString.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

</script>
<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — return agent list table HTML + hidden fields
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: text/html; charset=utf-8');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$dataInfo      = json_decode(file_get_contents('php://input'), true);
$territoire_id = $dataInfo['territoire_id'];
$where_agents  = $dataInfo['where_agents'];


// -----------------------------------------------
// Query: Users lookup

$user_list_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, id_statut, login, nom, prenom FROM " . TABLE_USER);
while ($user_list = tep_db_fetch_array($user_list_query))
{
    $user_list_array[$user_list['id']] = [
        'id_statut' => $user_list['id_statut'],
        'login'     => html_entity_decode($user_list['login']  ?? ''),
        'nom'       => ucfirst(strtolower(html_entity_decode($user_list['nom']    ?? ''))),
        'prenom'    => ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? ''))),
    ];
}

// Query: Communes
$commune_query = tep_db_query($sql_link,
    "SELECT DISTINCT c.id_commune, c.nom_commune
     FROM " . TABLE_COMMUNE . " c
     JOIN " . TABLE_REGION  . " r ON c.id_region = r.id_region
     WHERE r.id_territoire = " . $territoire_id . "
     ORDER BY c.nom_commune ASC");
while ($commune = tep_db_fetch_array($commune_query))
{
    $commune_array[$commune['id_commune']] = $commune['nom_commune'];
}


// -----------------------------------------------
// Build agent list

$tab_html          = '';
$hidden_html_agent = '';
$nb_agents         = 0;
$nb_agents_service = 0;
$nb_agents_terrain = 0;
$row               = 0;

$agents_query = tep_db_query($sql_link,
    "SELECT DISTINCT a.id, from_nomad, new_nomad, hp_load,
                     a.nom, a.nom_marital, a.prenom, a.raisonsociale, a.numinscription, a.fonction,
                     a.adresse, a.lieudit, a.bp, a.codepostal, a.id_commune,
                     a.tel, a.mobile, a.fax, a.email, a.siteweb, a.type, a.terrain, a.niveau
     FROM " . TABLE_AGENT . " a " . $where_agents . "
     ORDER BY a.niveau DESC, a.terrain DESC, a.nom ASC");

if ($agents_query)
{
    while ($agents_tab = tep_db_fetch_array($agents_query))
    {
        $nb_agents++;
        $id_agent   = $agents_tab['id'];
        $from_nomad = $agents_tab['from_nomad'];
        $new_nomad  = $agents_tab['new_nomad'];
        $hp_load    = $agents_tab['hp_load'];

        // nettoyer_et_echapper() sanitizes special characters for HTML attribute output
        $nom            = nettoyer_et_echapper($agents_tab['nom']);
        $nom_marital    = nettoyer_et_echapper($agents_tab['nom_marital']);
        $prenom         = nettoyer_et_echapper($agents_tab['prenom']);
        $raisonsociale  = nettoyer_et_echapper($agents_tab['raisonsociale']);
        $numinscription = nettoyer_et_echapper($agents_tab['numinscription']);
        $fonction       = nettoyer_et_echapper($agents_tab['fonction']);
        $adresse        = nettoyer_et_echapper($agents_tab['adresse']);
        $lieudit        = nettoyer_et_echapper($agents_tab['lieudit']);
        $bp             = nettoyer_et_echapper($agents_tab['bp']);
        $codepostal     = nettoyer_et_echapper($agents_tab['codepostal']);
        $tel            = nettoyer_et_echapper($agents_tab['tel']);
        $mobile         = nettoyer_et_echapper($agents_tab['mobile']);
        $fax            = nettoyer_et_echapper($agents_tab['fax']);
        $email          = nettoyer_et_echapper($agents_tab['email']);
        $siteweb        = nettoyer_et_echapper($agents_tab['siteweb']);
        $terrain        = $agents_tab['terrain'];
        $niveau         = $agents_tab['niveau'];

        $id_commune  = $agents_tab['id_commune'];
        $nom_commune = isset($commune_array[$id_commune]) ? $commune_array[$id_commune] : '';

        // Hidden inputs for JS population of the edit form
        $h = $id_agent;
        $hidden_html_agent .= "<input type='hidden' id='from_nomad_{$h}'     value=\"{$from_nomad}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='hp_load_{$h}'        value=\"{$hp_load}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='nom_{$h}'            value=\"{$nom}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='nom_marital_{$h}'    value=\"{$nom_marital}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='prenom_{$h}'         value=\"{$prenom}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='raisonsociale_{$h}'  value=\"{$raisonsociale}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='numinscription_{$h}' value=\"{$numinscription}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='fonction_{$h}'       value=\"{$fonction}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='adresse_{$h}'        value=\"{$adresse}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='lieudit_{$h}'        value=\"{$lieudit}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='bp_{$h}'             value=\"{$bp}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='codepostal_{$h}'     value=\"{$codepostal}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='id_commune_{$h}'     value=\"{$id_commune}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='tel_{$h}'            value=\"{$tel}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='mobile_{$h}'         value=\"{$mobile}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='fax_{$h}'            value=\"{$fax}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='email_{$h}'          value=\"{$email}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='siteweb_{$h}'        value=\"{$siteweb}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='terrain_{$h}'        value=\"{$terrain}\">\n";
        $hidden_html_agent .= "<input type='hidden' id='service_hydro_{$h}'  value=\"{$niveau}\">\n";

        // Table row
        $row++;
        $row_l = (fmod($row, 2) == 0)
            ? "class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\""
            : "class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\"";

        $tab_html .= "<tr {$row_l}>";

            $tab_html .= "<td style='cursor:pointer;' onClick='loadFicheAgent({$id_agent});'>{$nom}</td>\n";
            $tab_html .= "<td style='cursor:pointer;' onClick='loadFicheAgent({$id_agent});'>{$prenom}</td>\n";
            $tab_html .= "<td><a href='mailto:{$email}'>{$email}</a></td>\n";
            $tab_html .= "<td style='cursor:pointer;' onClick='loadFicheAgent({$id_agent});'>{$tel}</td>\n";
            $tab_html .= "<td style='cursor:pointer;' onClick='loadFicheAgent({$id_agent});'>{$raisonsociale}</td>\n";
            $tab_html .= "<td style='cursor:pointer;' onClick='loadFicheAgent({$id_agent});'>{$fonction}</td>\n";

            // Service indicator
            if ($niveau > 0)
            {
                $nb_agents_service++;
                $puce_service = "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_AGENT_PUCE_SERVICE . "'>";
            }
            else
            {
                $puce_service = "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;'>";
            }
            $tab_html .= "<td class='t_cont_m' style='text-align:center;'>{$puce_service}</td>\n";

            // Field agent indicator
            if ($terrain > 0)
            {
                $nb_agents_terrain++;
                $puce_terrain = "<img src='" . DIR_WS_IMG_ICO . "puce_verte.png' style='width:12px;' title='" . TEXT_AGENT_PUCE_TERRAIN . "'>";
            }
            else
            {
                $puce_terrain = "<img src='" . DIR_WS_IMG_ICO . "puce_rouge.png' style='width:12px;'>";
            }
            $tab_html .= "<td class='t_cont_m' style='text-align:center;'>{$puce_terrain}</td>\n";

            // Delete link
            $tab_html .= "<td style='text-align:center;'>";
            if (HP_VERSION == 'Serveur' || ($from_nomad > 0 && $hp_load < 1))
            {
                $tab_html .= "<a style='font-size:12px;font-weight:bold;' id='del_{$id_agent}' onClick='verifDelAgent({$id_agent});' title='" . TEXT_AGENT_DEL_LINK_TITLE . "'>X</a>";
            }
            else { $tab_html .= "-"; }
            $tab_html .= "</td>\n";

        $tab_html .= "</tr>";
    }
}
else
{
    $tab_html .= "<tr><td colspan='9'><p class='alert'>" . TEXT_AGENT_NOT_FOUND . "</p></td></tr>";
}


echo json_encode([
    'nb_agents'         => $nb_agents,
    'hidden_html_agent' => $hidden_html_agent,
    'tab_html'          => $tab_html,
]);
?>

<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement des informations liées à l'appartenance au service hydro et à leur qualificaiton de terrain
*/

// On récupère d'abord tous les ID concernés
$sql_agent_field = "SELECT DISTINCT id FROM ".TABLE_AGENT." ".$where_search;
$agent_field_query = tep_db_query($sql_link,$sql_agent_field);
while ($agent_field = tep_db_fetch_array($agent_field_query))
{	
    $check_field = 0;
    if (isset($_POST['agent_terrain_'.$agent_field['id']])){$check_field = 1;}
    $check_niveau = 0;
    if (isset($_POST['agent_service_'.$agent_field['id']])){$check_niveau = 1;}

    tep_db_query($sql_link,"UPDATE ".TABLE_AGENT." SET niveau='".$check_niveau."', terrain='".$check_field."'					
													WHERE id=".$agent_field['id']);

    $message_info = htmlaccent('La qualité des Agents - '.$service_hydro.' / Terrain - a bien été mise à jour.');
}



?>
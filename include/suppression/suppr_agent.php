<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression d'une Fiche Agent
*/


// Récupération de la variable indiquant l'identifiant à supprimer
$id_a = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_a'])));


$sql_del_a = "SELECT DISTINCT id, nom, prenom FROM ".TABLE_AGENT." WHERE id=".$id_a;
$del_query = tep_db_query($sql_link,$sql_del_a);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id']) && tep_not_null($del_a['id']))
{
    // On vérifie si l'Agent est lié à un RA ou à un JGE
	/*
	$sql_agent = "SELECT EXISTS (
									SELECT 1 FROM ".TABLE_DATA_RA_TO_AGENT." WHERE id_agent = ".$id_a."
									UNION
									SELECT 1 FROM ".TABLE_DATA_JGE_TO_AGENT." WHERE id_agent = ".$id_a."
								) AS id_a_exists";

	$agent_query = tep_db_query($sql_link,$sql_agent);
	$del_agent = tep_db_fetch_array($agent_query);
	
	if($del_agent['id_a_exists'] == 1)	// Si l'agent est enregistré dans au moins un RA ou un JGE, la fiche ne peut pas être supprimée
    {
        $message_suppr_agent = htmlaccent('La fiche Agent - '.htmlaccent($del_a['nom']).' '.htmlaccent($del_a['prenom']).' - ne peut pas être supprimée car elle est liée à au moins une action de recueil de données.');
    }
    else // Si l'Agent n'est lié à aucune donnée
    {
		tep_db_query($sql_link,"DELETE FROM ".TABLE_AGENT." WHERE id=".$id_a);
        $message_suppr_agent = htmlaccent('La fiche Agent - '.htmlaccent($del_a['nom']).' '.htmlaccent($del_a['prenom']).' - a bien été supprimée.');
	}
	*/

	tep_db_query($sql_link,"DELETE FROM ".TABLE_AGENT." WHERE id=".$id_a);
	$message_suppr_agent = htmlaccent('La fiche Agent - '.htmlaccent($del_a['nom']).' '.htmlaccent($del_a['prenom']).' - a bien été supprimée.');
}
else
{
	$message_suppr_agent = htmlaccent('La fiche Agent n\'existe pas, elle ne peut pas être supprimée.');
}
	


?>

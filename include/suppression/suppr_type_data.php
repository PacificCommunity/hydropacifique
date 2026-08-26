<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression d'une Chronique
*/


// Récupération de la variable indiquant l'identifiant à supprimer
$id_td = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_td'])));


$sql_del_td = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data FROM ".TABLE_TYPE_DATA." WHERE id_data_type=".$id_td;
$del_query = tep_db_query($sql_link,$sql_del_td);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id_data_type']) && tep_not_null($del_a['id_data_type']))
{
    // On vérifie si la Chronique est liée à une Données
	$sql_meta_td = "SELECT EXISTS (
									SELECT 1 FROM ".TABLE_DATA_META." WHERE id_typedata=".$id_td." LIMIT 1
								  ) AS chronique_exists";
	$meta_td_query = tep_db_query($sql_link,$sql_meta_td);
	$del_meta_td = tep_db_fetch_array($meta_td_query);
	
	if($del_meta_td['chronique_exists'] == 1)	// Si la Chronique est liée à au moins une données on ne peut pas supprimer la Chronique
    {
        $message_suprr_chron = htmlaccent('La Chronique - '.htmlaccent($del_a['nom_type_data']).' ('.htmlaccent($del_a['init_type_data']).') - ne peut pas être supprimée car elle est liée à au moins une données.');
    }
    else // Si la Chronique n'est liée à aucune donnée
    {
		tep_db_query($sql_link,"DELETE FROM ".TABLE_TYPE_DATA." WHERE id_data_type=".$id_td);
		$message_suprr_chron = htmlaccent('La Chronique - '.htmlaccent($del_a['nom_type_data']).' ('.htmlaccent($del_a['init_type_data']).') - a bien été supprimée.');
	}
}
else
{
	$message_suprr_chron = htmlaccent('La Chronique n\'existe pas, elle ne peut pas être supprimée.');
}
	


?>

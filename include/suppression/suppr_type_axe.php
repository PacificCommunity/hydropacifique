<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression d'un Axe de données
*/


// Récupération de la variable indiquant l'identifiant à supprimer
$id_a = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_a'])));


$sql_del_a = "SELECT DISTINCT id, axe, unite FROM ".TABLE_DATA_TYPE_AXE." WHERE id=".$id_a;
$del_query = tep_db_query($sql_link,$sql_del_a);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id']) && tep_not_null($del_a['id']))
{
    // On vérifie si l'Axe est lié à une Chronique
	$sql_axe = "SELECT EXISTS (
                                SELECT 1 FROM ".TABLE_TYPE_DATA." WHERE axe_data=".$id_a." LIMIT 1
                              ) AS axe_exists";
	$axe_query = tep_db_query($sql_link,$sql_axe);
	$del_axe = tep_db_fetch_array($axe_query);
	
	if($del_axe['axe_exists'] == 1)	// Si l'axe est lié à au moins une Chronique on ne peut pas le supprimer
    {
        $message_suprr_chron = htmlaccent('L\'Axe - '.htmlaccent($del_a['axe']).' - ne peut pas être supprimé car il est lié à au moins une Chronique.');
    }
    else // Si la Chronique n'est liée à aucune donnée
    {
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_TYPE_AXE." WHERE id=".$id_a);
        $message_suprr_chron = htmlaccent('L\'Axe - '.htmlaccent($del_a['axe']).' - a bien été supprimé.');
	}
}
else
{
	$message_suprr_chron = htmlaccent('L\'Axe n\'existe pas, il ne peut pas être supprimé.');
}
	


?>

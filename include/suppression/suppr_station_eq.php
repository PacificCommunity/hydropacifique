<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/


$deleq = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['deleq'])));

$sql_del = "SELECT DISTINCT * FROM ".TABLE_STATION_TO_EQUIPEMENT." WHERE id=".$deleq;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id']))
{
	$sql_del_eq = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT." WHERE id=".$del_a['id_eq'];
	$del_eq_query = tep_db_query($sql_link,$sql_del_eq);
	$del_eq = tep_db_fetch_array($del_eq_query);
	
	$sql_nb_eq = "SELECT count(*) as nb_eq_idem FROM ".TABLE_STATION_TO_EQUIPEMENT." WHERE id_station=".$del_a['id_station']." AND id_eq=".$del_a['id_eq'];
	$nb_eq_query = tep_db_query($sql_link,$sql_nb_eq);
	$nb_eq = tep_db_fetch_array($nb_eq_query);
	
	
	if(tep_not_null($del_eq['id']))
	{
		if($nb_eq['nb_eq_idem']>1)
		{
			tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION_TO_EQUIPEMENT." WHERE id=".$deleq);
			$message_suprr_liaison = htmlaccent('Le matériel de mesure '.htmlaccent($del_eq['designation']).' n\'est plus lié à la station.');	
		}
		else
		{
			$sql_del_plu = "SELECT DISTINCT * FROM ".TABLE_DATA_PLUVIO." WHERE id_station=".$ref_id." AND id_materiel=".$del_a['id_eq'];
			$del_plu_query = tep_db_query($sql_link,$sql_del_plu);
			$del_plu = tep_db_fetch_array($del_plu_query);
			
			$sql_del_lim = "SELECT DISTINCT * FROM ".TABLE_DATA_LIMNI." WHERE id_station=".$ref_id." AND id_materiel=".$del_a['id_eq'];
			$del_lim_query = tep_db_query($sql_link,$sql_del_lim);
			$del_lim = tep_db_fetch_array($del_lim_query);
			
			if(!isset($del_plu['id']) && !isset($del_lim['id']))
			{
				tep_db_query($sql_link,"DELETE FROM ".TABLE_STATION_TO_EQUIPEMENT." WHERE id=".$deleq);
				$message_suprr_liaison = htmlaccent('Le format de fichier '.htmlaccent($del_eq['designation']).' n\'est plus lié à la station.');
			}
			else
			{
				$message_suprr_liaison = htmlaccent('Il n\'est pas possible de supprimer la liaison entre le format de fichier et la station : Des enregistrements ont été importés.');	
			}
		}
	}
}
else
{
	$message_suprr_liaison = htmlaccent('Ce format de fichier n\'est pas lié à la station');
}

?>

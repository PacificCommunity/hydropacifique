<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression des lignes Code Qualité
Ce n'est possible que si le code qualité n'a jamais été utilisé sur une données
*/


// Récupération de la variable indiquant l'identifiant à supprimer
$id_cq = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_cq'])));

$sql_del_cq = "SELECT DISTINCT id_data_qualite, init_qualite_data FROM ".TABLE_DATA_QUALITE." WHERE id_data_qualite=".$id_cq;
$del_query = tep_db_query($sql_link,$sql_del_cq);
$del_a = tep_db_fetch_array($del_query);

if(isset($del_a['id_data_qualite']) && tep_not_null($del_a['id_data_qualite']))
{
    // On vérifie si le Code Qualité a déjà été utilisé dans la table DATA_META ou dans la table DATA_ETL_DATA
	$sql_table_cq = "SELECT EXISTS (
                                        SELECT 1 FROM (
                                            SELECT 1 FROM ".TABLE_DATA_META." WHERE id_codequal = ".$id_cq."
                                            UNION
                                            SELECT 1 FROM ".TABLE_DATA_ETL_DATA." WHERE code_qualite = ".$id_cq."
                                        ) AS subquery
                                        LIMIT 1
                                    ) AS id_cq_exists";

	$cq_query = tep_db_query($sql_link,$sql_table_cq);
	$del_cq_exists = tep_db_fetch_array($cq_query);
	
	if($del_cq_exists['id_cq_exists'] == 1)	// Si le Code Qualité est utilisé
    {
        $message_suprr_quality = htmlaccent('Le Code Qualité - '.htmlaccent($del_a['init_qualite_data']).' - ne peut pas être supprimé car il est liée à au moins une donnée.');
    }
    else // Si le Code Qualité n'est lié à aucune données
    {
		tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_QUALITE." WHERE id_data_qualite=".$id_cq);
		$message_suprr_quality = htmlaccent('Le Code Qualité - '.htmlaccent($del_a['init_qualite_data']).' - a bien été supprimé.');
	}
}
else
{
	$message_suprr_quality = htmlaccent('Le Code Qualité n\'existe pas, il ne peut pas être supprimé.');
}
	


?>

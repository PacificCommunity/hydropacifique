<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

$error = 0;
$error_0 = 0;


$nom_eq_type_0 = '';
$select_typemesure_0 = 0;
$ordre_type_0 = 0;
$active_type_0 = 0;
$type_color_border_0 = '';
$type_color_background_0 = '';

		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

$sql_type = "SELECT DISTINCT id_eq_type FROM ".TABLE_EQ_TYPE;
$type_query = tep_db_query($sql_link,$sql_type);
while ($type = tep_db_fetch_array($type_query)) 
{
	${'nom_eq_type_'.$type['id_eq_type']} = post_secure($sql_link,$_POST['nom_eq_type_'.$type['id_eq_type']]);
	${'select_typemesure_'.$type['id_eq_type']} = post_secure($sql_link,$_POST['select_typemesure_'.$type['id_eq_type']]);
	${'ordre_type_'.$type['id_eq_type']} = post_secure($sql_link,$_POST['ordre_type_'.$type['id_eq_type']]);	
	
	${'active_type_'.$type['id_eq_type']} = 0;
	if(isset($_POST['active_type_'.$type['id_eq_type']])){${'active_type_'.$type['id_eq_type']} = 1;}
	
	${'type_color_border_'.$type['id_eq_type']} = post_secure($sql_link,$_POST['type_color_border_'.$type['id_eq_type']]);
	${'type_color_background_'.$type['id_eq_type']} = post_secure($sql_link,$_POST['type_color_background_'.$type['id_eq_type']]);
		
	${'active_type_'.$type['id_eq_type']} = 0;
	if(isset($_POST['active_type_'.$type['id_eq_type']])){${'active_type_'.$type['id_eq_type']} = 1;}
	

	tep_db_query($sql_link,"UPDATE ".TABLE_EQ_TYPE." SET nom_eq_type='".${'nom_eq_type_'.$type['id_eq_type']}."',
														 valeur_data_type='".${'select_typemesure_'.$type['id_eq_type']}."',
														 order_eq_type='".${'ordre_type_'.$type['id_eq_type']}."',
														 active_eq_type='".${'active_type_'.$type['id_eq_type']}."',
														 type_color_border='".${'type_color_border_'.$type['id_eq_type']}."',
														 type_color_background='".${'type_color_background_'.$type['id_eq_type']}."' 
													 WHERE id_eq_type=".$type['id_eq_type']);
}
$message_info .= htmlaccent('La liste Type de mesure a bien été mise à jour');



//nouveau pas de temps

if(tep_not_null($_POST['nom_eq_type']))
{
	$nom_eq_type_0 = post_secure($sql_link,$_POST['nom_eq_type']);
	$select_typemesure_0 = post_secure($sql_link,$_POST['select_typemesure']);
	$ordre_type_0 = post_secure($sql_link,$_POST['ordre_type']);
	
	$active_type_0 = 0;
	if(isset($_POST['active_type'])){$active_type_0 = 1;}
	
	$type_color_border_0 = post_secure($sql_link,$_POST['type_color_border']);
	$type_color_background_0 = post_secure($sql_link,$_POST['type_color_background']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_TYPE_DATA." (nom_eq_type,valeur_data_type,order_eq_type,active_eq_type,type_color_border,type_color_background) 
													VALUES ('".$nom_eq_type_0."','".$select_typemesure_0."','".$ordre_type_0."','".$active_type_0."','".$type_color_border_0."','".$type_color_background_0."')");	
		
	$message_info .= htmlaccent('Le nouveau Type de mesure a bien été enregistré');
}

?>

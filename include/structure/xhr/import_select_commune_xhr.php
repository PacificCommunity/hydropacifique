<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

$id_region = htmlspecialchars(stripslashes($_POST['id_region']),ENT_QUOTES);

// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');

$reponse_select = "<select name='select_commune' id='select_commune' >";

$sql_communes = "SELECT id_commune, nom_commune FROM ".TABLE_COMMUNE." WHERE id_region=".$id_region." ORDER BY nom_commune";;
$communes_query = tep_db_query($sql_link,$sql_communes);
while($communes = tep_db_fetch_array($communes_query))
{		
	$selected = '';					
	//if($stations['id'] == $region_modif){$selected = 'selected';}
	//else{if($stations['id'] == $region_default){$selected = 'selected';}}
	
	$reponse_select .= "<option value='".$communes['id_commune']."' ".$selected.">".htmlaccent($communes['nom_commune'])."</option>";
}

$reponse_select .="</select>";

echo $reponse_select;


?>

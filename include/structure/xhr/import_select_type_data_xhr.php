<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=ISO-8859-1');


$id_station = htmlspecialchars(stripslashes($_POST['id_station']),ENT_QUOTES);
$id_user = htmlspecialchars(stripslashes($_POST['id_user']),ENT_QUOTES);


// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');

$reponse_select = "<select name='select_type_eq' id='select_type_eq'  >";

$sql_eq_type = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." et, ".TABLE_EQUIPEMENT." e, ".TABLE_STATION_TO_EQUIPEMENT." ste WHERE ste.id_eq=e.id_eq AND et.id_eq_type=e.type_eq AND ste.id_station=".$id_station;
$eq_type_query = tep_db_query($sql_link,$sql_eq_type);




while($eq_type = tep_db_fetch_array($eq_type_query))
{		
	$selected = '';					
	//if($stations['id'] == $region_modif){$selected = 'selected';}
	//else{if($stations['id'] == $region_default){$selected = 'selected';}}
		
	$reponse_select .= "<option value='".$eq_type['id_eq_type']."' ".$selected.">".htmlaccent($eq_type['designation'])."</option>";
	
}


$reponse_select .="</select>";


echo $reponse_select;




?>

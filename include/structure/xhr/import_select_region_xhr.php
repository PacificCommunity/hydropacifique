<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=ISO-8859-1');


$id_region = htmlspecialchars(stripslashes($_POST['id_region']),ENT_QUOTES);
$type_station = htmlspecialchars(stripslashes($_POST['type_station']),ENT_QUOTES);

$onchange='import_select_station_ajax()';
if($type_station=='type_data'){$onchange='import_select_type_data_ajax()';}




// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');

$reponse_select = "<select name='select_station' id='select_station' onchange=\"document.getElementById('button_stats').style.display = 'none';".$onchange."\">";

$sql_stations = "SELECT * FROM ".TABLE_STATION." WHERE id_region=".$id_region." ORDER BY nom_station";
$stations_query = tep_db_query($sql_link,$sql_stations);
while($stations = tep_db_fetch_array($stations_query))
{		
	$selected = '';					
	//if($stations['id'] == $region_modif){$selected = 'selected';}
	//else{if($stations['id'] == $region_default){$selected = 'selected';}}
	
	$reponse_select .= "<option value='".$stations['id_station']."' ".$selected.">".htmlaccent($stations['nom_station'])."</option>";
}

$reponse_select .="</select>";

echo $reponse_select;


?>

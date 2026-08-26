<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');


$id_station = htmlspecialchars(stripslashes($_POST['id_station']),ENT_QUOTES);
$id_user = htmlspecialchars(stripslashes($_POST['id_user']),ENT_QUOTES);
$ext_file = '';


// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');

$reponse_select = "<select name='select_materiel' id='select_materiel' onchange=\"document.getElementById('button_stats').style.display = 'none';import_select_date_ini_ajax();\">";

$sql_equipements = "SELECT DISTINCT * FROM ".TABLE_STATION_TO_EQUIPEMENT." ste, ".TABLE_EQUIPEMENT." e WHERE ste.id_station=".$id_station." AND ste.id_eq=e.id_eq  GROUP BY designation ORDER BY designation";
$equipements_query = tep_db_query($sql_link,$sql_equipements);

$nb_eq=0;
while($equipements = tep_db_fetch_array($equipements_query))
{		
						
	if($nb_eq == 0){$ext_file = $equipements['ext_file'];}
	//else{if($stations['id'] == $region_default){$selected = 'selected';}}
		
	$reponse_select .= "<option value='".$equipements['id_eq']."' >".htmlaccent($equipements['designation'])."</option>";
		
	$nb_eq++;
}

$reponse_select .="</select>";

echo $reponse_select.':'.$ext_file;



?>

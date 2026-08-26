<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=ISO-8859-1');


$id_type_eq = htmlspecialchars(stripslashes($_POST['id_type_eq']),ENT_QUOTES);


// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');


$sql_type_int = "SELECT DISTINCT * FROM ".TABLE_EQ_TYPE." WHERE id=".$id_type_eq;
$type_int_query = tep_db_query($sql_link,$sql_type_int);
$type_int = tep_db_fetch_array($type_int_query);
$interval_type = $type_int['interval_type'];




$reponse_select = "<select name='select_interval' id='select_interval'>";

	$reponse_select .= "<option value='0' >".htmlaccent('données brutes')."</option>";
	
	if($interval_type==1)
	{	
		$sql_interval = "SELECT DISTINCT * FROM ".TABLE_EXPORT_INTERVAL;
		$interval_query = tep_db_query($sql_link,$sql_interval);
		while($interval = tep_db_fetch_array($interval_query))
		{
			$reponse_select .= "<option value='".$interval['min']."' >".htmlaccent($interval['libelle'])."</option>";
		}
	}
										

$reponse_select .="</select>";


echo $reponse_select;




?>

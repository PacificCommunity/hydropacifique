<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

$id_type = htmlspecialchars(stripslashes($_POST['id_type']),ENT_QUOTES);

// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');

$reponse_select = "<select name='select_equipement' id='select_equipement'>";

	$sql_eq = "SELECT DISTINCT * FROM ".TABLE_EQUIPEMENT." WHERE type_eq=".$id_type;
	$equipement_query = tep_db_query($sql_link,$sql_eq);
	while ($equipement = tep_db_fetch_array($equipement_query))
	{			
		$reponse_select .= "<option value='".$equipement['id_eq_type']."'>".htmlaccent($equipement['designation'])."</option>";
	}

$reponse_select .= "</select>";

echo $reponse_select;



?>

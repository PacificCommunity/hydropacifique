<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=ISO-8859-1');


$id_eq = htmlspecialchars(stripslashes($_POST['id_eq']),ENT_QUOTES);


// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');


$sql_eq = "SELECT * FROM ".TABLE_EQUIPEMENT." WHERE id_eq=".$id_eq;
$eq_query = tep_db_query($sql_link,$sql_eq);
$eq = tep_db_fetch_array($eq_query);

echo $eq['champ_datefirst'].':'.$eq['champ_dateend'].':'.$eq['ext_file'];



?>

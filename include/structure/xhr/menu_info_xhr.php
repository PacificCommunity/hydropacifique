<?php

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');


$id_user = htmlspecialchars(stripslashes($_POST['id_user']),ENT_QUOTES);




// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');

$sql = "SELECT DISTINCT * FROM ".TABLE_MENU." WHERE id=".$id_user;
$menu_query = tep_db_query($sql_link,$sql);
$menu = tep_db_fetch_array($menu_query);


for($i=1;$i<=18;$i++)
{
	echo $menu['menu_'.$i];
	if($i<18){echo ':';}
}
?>

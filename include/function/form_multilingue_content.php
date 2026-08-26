<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

//FONCTIONS FORM MULTILINGUE CONTENT




//DATE STATUT
function date_statut($init_langue,$name_form,$table_info,$modif) 
{


	$date_input = 'date_' . $init_langue;
	$calendar_name = 'calendar_' . $init_langue;
	$calendar_title = 'title_' . $init_langue;
?>

	<script type="text/javascript">
	<!--
	var calendar_name = '<?php print $calendar_name ; ?>';
	var form_name = '<?php print $name_form ; ?>';
	var input_name = '<?php print $date_input ; ?>';
	
	addCalendar(calendar_name, "Date", input_name, form_name);
	setWidth(90, 1, 15, 1);
	setFormat("dd-mm-yyyy");
	setWeekDay(1);
	setMonthNames("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	setDayNames("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
	setLinkNames("fermer |", " supprimer");
	-->
	</script>


<?php
	echo "<div id='boite2'>";
	echo "<h2>Date de mise en ligne</h2>";

	if($modif)
	{
		$date_save = date("d-m-Y");
		if(tep_not_null($table_info['date_statut'])){$date_save = dateus_fr($table_info['date_statut']);}	
		echo "<input type='text' name=" . $date_input . " id='date' value='" . $date_save . "' onFocus='this.blur()'/>";
	}
	else
	{echo "<input type='text' name=" . $date_input . " id='date' value='" . date("d-m-Y") . "' onFocus='this.blur()'/>";}

	echo "<img src='image/icones/calendar.gif' border='0' alt='Calendrier' width='16' height='16' onClick=\"showCal('" . $calendar_name . "');\" />";

	echo "</div>";

}



function tags($init_langue,$table_info,$modif) 
{
	echo "<h2>Tags</h2>";
	
	$nb_tags = 5;
	$tab_tags = array();
	if($modif && (tep_not_null($table_info['tags']))){$tab_tags = explode("|",$table_info['tags']);}
	
	
	for($j=1;$j<=$nb_tags;$j++)
	{
		if(isset($tab_tags[$j-1]))
		{echo tep_draw_input_field('tag_' . $init_langue . '_' . $j,$tab_tags[$j-1]);}
		else{echo tep_draw_input_field('tag_' . $init_langue . '_' . $j);}
	}
}


function liens_internes($init_langue,$table_info,$modif) 
{
	echo "<h2>Liens Internes</h2>";
}


function liens_externes($init_langue,$table_info,$modif) 
{
	echo "<h2>Liens Externes</h2>";
	
	for($j=1;$j<=2;$j++) //2 c'est le nombre de Liens Externes possibles par article
	{       
		$titre_liensE = '';
		$url_liensE = '';
	        
		if($modif && (tep_not_null($table_info['lien_ext'.$j])))
		{
			$contenu_liensE = explode("|", $table_info['lien_ext'.$j]);
			$titre_liensE = $contenu_liensE[0];
			$url_liensE = $contenu_liensE[1];
		}
		
		echo "titre : " . tep_draw_input_field('titre_linkout_' . $init_langue . '_' . $j,$titre_liensE,'id=\'titre\'');
		echo "url : " . tep_draw_input_field('url_linkout_' . $init_langue . '_' . $j,$url_liensE,'id=\'titre\'');
			
		if($j!=2){echo "<br><br>";}
	}     
}  



?>

/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


function verif_dates_stats() 
 {	
	html_info = "<div id='contenu_info' style='text-align:left;margin-left:20px;'>";
	
	var day_p1 = $F("day_p1");
	var month_p1 = $F("month_p1");
	var year_p1 = $F("year_p1");
	var date_1 = year_p1 + month_p1 + day_p1;
	
	var day_p2 = $F("day_p2");
	var month_p2 = $F("month_p2");
	var year_p2 = $F("year_p2");
	var date_2 = year_p2 + month_p2 + day_p2;
	
	if(date_1 > date_2)
	{
		html_info += "La première date doit-être inférieure à la seconde date";
		html_info += "</div><hr>";
		
		new Insertion.Bottom('box_result',html_info);
		
		return false;
	}
	else{return true;}


}


function verif_erase_data()
{
	if($F("replace_data"))
	{
		texte = 'Êtes-vous sûr de vouloir remplacer les données existantes ? \n Cette action est irréversible !!!';
		if (confirm(texte)){verif_erase_data_2();}
		else{return false;}
	}
	else{return true;}

}

function verif_erase_data_2()
{
	texte = 'Êtes-vous vraiment sûr de vouloir remplacer les données existantes ?\n Cette action est irréversible !!!';
	if (confirm(texte)){return true;}
	else{return false;}
}

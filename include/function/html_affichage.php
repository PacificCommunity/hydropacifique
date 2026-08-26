<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Foncitons permettant de générer des boutons
*/




function button_new($name_button,$link_button)
{
	$html = "<div id='button_titre' onclick=\"window.open('".$link_button."','_blank');\">\n";	
		$html .=  $name_button; 
	$html .= "</div>\n";
	
	return $html;
}	

function button_xls($name_button,$link_button)
{
	$html = "<div id='button_xls' onclick=\"".$link_button."\">\n";	
		$html .=  $name_button; 
	$html .= "</div>\n";
	
	return $html;
}

function button_visu($name_button,$link_button)
{
	$html = "<div id='button_visu' onclick=\"".$link_button."\">\n";	
		$html .=  $name_button; 
	$html .= "</div>\n";
	
	return $html;
}


function button_pdf($name_button,$link_button)
{
	$html = "<div id='button_pdf' onclick='".$link_button."'>\n";	
		$html .= "<img src='".DIR_WS_IMG_ICO."pdf.png' style='float:left;width:25px;margin-right:5px;' title='".htmlaccent('Export Format PDF')."' >";
		$html .= "<p style='float:left;margin-top:10px;'>".$name_button."<p>";
	$html .= "</div>\n";
	
	return $html;
}



function button_print($file,$name)
{
	$open_xindow = "window.open(\"".$file."\",\"".$name."\",\"location=no,toolbar=no,directories=no,menubar=no,resizable=yes,scrollbars=yes,status=no,width=1080,height=800,top=0,left=0\");";
	
	$html = "<p div='appli' class='print'>";	
		$html .=  "<img src='".DIR_WS_IMG_ICO."print.png' title='".htmlaccent('Imprimer')."' onClick='".$open_xindow."';>\n"; 		
	$html .= "</div>";
	
	return $html;
}


function button_return($file)
{
	$html = "<div id='appli' class='pdf'>";	
		$html .= "<a href='".$file."' >";
			$html .= "<img src='".DIR_WS_IMG_ICO."return.png' title='".htmlaccent('Revenir')."' >";
		$html .= "</a>\n"; 		
	$html .= "</div>";

	return $html;
}	




?>

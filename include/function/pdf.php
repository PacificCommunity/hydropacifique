<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

function pdf($file_in,$file_out) 
{ 
	//include($file_in);
	
	
	ob_start();
 	include($file_in);
	
	$content = ob_get_clean();
	

	require_once('include/class/html2pdf.class.php');

	$html2pdf = new HTML2PDF('P', 'A4', 'fr');
	$html2pdf->pdf->SetDisplayMode('fullpage');
	$html2pdf->WriteHTML($content, isset($_GET['vuehtml']));
	$html2pdf->Output($file_out.'.pdf');
	
}


?>

/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

function import_select_region_ajax() 
 {
	var id_region = document.getElementById('select_region').options[document.getElementById('select_region').selectedIndex].value;
	var type_station = 'simple';
	if(document.getElementById('type_data')){type_station = 'type_data';}
	
	
	new Ajax.Request('include/structure/xhr/import_select_region_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_region='+id_region+'&type_station='+type_station,
				onComplete: ajax_s
			}); 
	
	function ajax_s(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
		
		if(document.getElementById('select_station'))
		{
			var element = document.getElementById('select_station');
			element.parentNode.removeChild(element);
		}
		
		new Insertion.Top('station',tmp[0]);
		
		if(document.getElementById('select_materiel')){import_select_station_ajax();}
		if(document.getElementById('type_data')){import_select_type_data_ajax();}
		if(document.getElementById('type_data_2')){import_select_debitlimni_ajax() ();}
	} 
}



function import_select_station_ajax() 
{	
	var id_station = document.getElementById('select_station').options[document.getElementById('select_station').selectedIndex].value;
	var id_user = $F("id_user_f");

	new Ajax.Request('include/structure/xhr/import_select_station_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_station='+id_station+'&id_user='+id_user,
				onComplete: ajax_m
			}); 
	
	function ajax_m(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
			
		document.getElementById('extension').value = tmp[1];
		
		if(document.getElementById('select_materiel'))
		{
			var element = document.getElementById('select_materiel');
			element.parentNode.removeChild(element);
		}
		
		
		new Insertion.Top('materiel',tmp[0]);
		if(document.getElementById('type_data')){import_select_type_data_ajax();}
		if(document.getElementById('select_materiel')){import_select_date_ini_ajax();}
		
		
	} 
	
}


function import_select_date_ini_ajax() 
{	
	var id_eq = document.getElementById('select_materiel').options[document.getElementById('select_materiel').selectedIndex].value;

	new Ajax.Request('include/structure/xhr/import_select_eq_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_eq='+id_eq,
				onComplete: ajax_m
			}); 
	

	function ajax_m(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
		
		document.getElementById('extension').value = tmp[2];
		
		if(tmp[0]==1){document.getElementById('date_ini').style.display='block';}
		else{document.getElementById('date_ini').style.display='none';}
		
		if(tmp[1]==1){document.getElementById('date_end').style.display='block';}
		else{document.getElementById('date_end').style.display='none';}
		
	} 
	
	if(document.getElementById('button_stats')){document.getElementById('button_stats').style.display = 'block';}

}



function import_select_type_data_ajax() 
{	
	var id_station = document.getElementById('select_station').options[document.getElementById('select_station').selectedIndex].value;
	var id_user = $F("id_user_f");

	new Ajax.Request('include/structure/xhr/import_select_type_data_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_station='+id_station+'&id_user='+id_user,
				onComplete: ajax_m
			}); 
	
	function ajax_m(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
		
		if(document.getElementById('select_type_eq'))
		{
			var element = document.getElementById('select_type_eq');
			element.parentNode.removeChild(element);
		}
		
		new Insertion.Top('type_data',tmp[0]);
		
		if(document.getElementById('type_data_2')){import_select_debitlimni_ajax();}
		if(document.getElementById('button_stats')){document.getElementById('button_stats').style.display = 'block';}
	} 

}


function import_select_debitlimni_ajax() 
{	
	var id_typedata = document.getElementById('select_type_eq').options[document.getElementById('select_type_eq').selectedIndex].value;

	if(id_typedata==2){document.getElementById('type_init').style.display='block';}
	else{document.getElementById('type_init').style.display='none';}

}



/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

function import_select_region_commune_ajax() 
{
	var id_region = $('#select_region').val();
	$.post('include/structure/xhr/import_select_commune_xhr.php', { id_region: id_region })
		.done(function(data) 
		{
			var tmp = data.split(":");
			
			if ($('#select_commune').length) 
			{
				$('#select_commune').remove();
			}
			
			$('#commune').prepend(tmp[0]);
			
			if ($('#button_ordre').length) 
			{
				$('#button_ordre').css('display', 'block');
			}
		});
	
	
}


/*	
		var id_region = document.getElementById('select_region').options[document.getElementById('select_region').selectedIndex].value;
			
		new Ajax.Request('include/structure/xhr/import_select_commune_xhr.php',{
						
					method: 'post', 
					parameters: 'id_region='+id_region,
					onComplete: ajax_s
				}); 
		
		function ajax_s(ajax_response)
		{
			var tmp = ajax_response.responseText.split(":");
					
			if(document.getElementById('select_commune'))
			{
				var element = document.getElementById('select_commune');
				element.parentNode.removeChild(element);
			}
			
			new Insertion.Top('commune',tmp[0]);		
		} 
		
		if(document.getElementById('button_ordre')){document.getElementById('button_ordre').style.display = 'block';}
		*/





// ENCOURS DE DEVELOPPEMENT
function import_select_typedata_ajax() 
 {
	var id_type = document.getElementById('select_eq_type').options[document.getElementById('select_eq_type').selectedIndex].value;
	
	new Ajax.Request('include/structure/xhr/import_select_eq_type_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_type='+id_type,
				onComplete: ajax_s
			}); 
	
	function ajax_s(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
						
		if(document.getElementById('select_equipement'))
		{
			var element = document.getElementById('select_equipement');
			element.parentNode.removeChild(element);
		}
		
		new Insertion.Top('div_type_eq',tmp[0]);		
	} 
	
	
	if(document.getElementById('button_ordre')){document.getElementById('button_ordre').style.display = 'block';}
}
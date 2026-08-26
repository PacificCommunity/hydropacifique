/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/
/*

function close_view(elt_id,elt_id_child)
{			
	document.getElementById(elt_id).style.display = 'none';
	
	var element = document.getElementById(elt_id_child);
	element.parentNode.removeChild(element);
}
*/

function menu_save_ajax(id_user,up_down,num) 
 {	
	
	if(up_down=='up'){menu_val=0;nav_up(num,1);}
	if(up_down=='down'){menu_val=1;nav_down(num,1);}
		
	new Ajax.Request('include/structure/xhr/menu_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_user='+id_user+'&menu_name=menu_'+num+'&menu_val='+menu_val
				
			});
}



function menu_info_ajax(id_user) 
 {	
	new Ajax.Request('include/structure/xhr/menu_info_xhr.php',{
					 
				method: 'post', 
				parameters: 'id_user='+id_user,
				onComplete: ajax_m
			}); 
	
	
	function ajax_m(ajax_response)
	{
		var tmp = ajax_response.responseText.split(":");
		
		for(m=0;m<18;m++)
		{
			if(tmp[m]==0){nav_up((m+1),0);}
			else{nav_down((m+1),0);}
		}
		
	}
	
}


function nav_down(num,duration)
{
	document.getElementById('menu_'+num+'_plus').style.display = 'none';
	document.getElementById('menu_'+num+'_moins').style.display = 'block';
	
	if(duration==0){new Effect.BlindDown('nav'+num,{duration:0});}
	else{new Effect.BlindDown('nav'+num);}
}

function nav_up(num,duration)
{
	document.getElementById('menu_'+num+'_moins').style.display = 'none';
	document.getElementById('menu_'+num+'_plus').style.display = 'block';
	
	if(duration==0){new Effect.BlindUp('nav'+num,{duration:0});}
	else{new Effect.BlindUp('nav'+num);}
}

function histo_up()
{
	new Effect.BlindUp('archives');	
	document.getElementById('archives_moins').style.display = 'none';
	document.getElementById('archives_plus').style.display = 'block';
}
function histo_down()
{
	new Effect.BlindDown('archives');	
	document.getElementById('archives_plus').style.display = 'none';
	document.getElementById('archives_moins').style.display = 'block';
}





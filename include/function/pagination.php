<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/




function pagination($first_ligne_page,$nb_ligne_page,$nb_tot,$url,$select)  
{   
    
	$list_page = '';
    $lien_info = '';
    $page_encours = 1;
        
    $nb_page = ceil($nb_tot/$nb_ligne_page);

    //if($select_station>0){$lien_info .= '&select_station='.$select_station;}
    
    
	if($nb_page>1)
    {
    	for($i=1;$i<=$nb_page;$i++)
    	{    		
			$first_page = ($i-1)*$nb_ligne_page;    		
    		if($first_page==$first_ligne_page){$list_page .= "<span>".$i."</span>";}
			else{$list_page .= "<a href='".$url.".php?deb=".(($i-1)*$nb_ligne_page).$select."'>".$i."</a>";}
    	}
    }
	
	echo $list_page;
	
} 



?>

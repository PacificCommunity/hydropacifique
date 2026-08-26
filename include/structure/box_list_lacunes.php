<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/





if(isset($data_list_lacunes))
{
	
	echo "<div id='box_graph' class='gd'>";
	
		//echo "<h8 style='background-color:#df891a;border:1px solid #df891a;'>".htmlaccent('Lacunes Corrigées')."</h8>";
		echo "<h8 class='lgt'>";
		  echo "<img src='".DIR_WS_IMG_ICO."lacune.png' >";
		  echo htmlaccent('Lacunes Corrigées');
		echo "</h8>";	
		  
						
		echo "<hr><hr><hr>";
	
		echo "<table id='stats_tab' cellspacing='0'>";
		
			echo "<tr>";
				echo "<td class='top'>".htmlaccent('Début lacune')."</td>";
				echo "<td class='top'>".htmlaccent('Fin lacune')."</td>";
				echo "<td class='top'>".htmlaccent('Cumul (en mm)')."</td>";
				echo "<td class='top'>".htmlaccent('Observation')."</td>";
				if($import){echo "<td class='top'>&nbsp;</td>";}
			echo "</tr>";
			
			echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				if($import){echo "<td>&nbsp;</td>";}
			echo "</tr>";
			
			for($dl=0;$dl<sizeof($data_list_lacunes);$dl++)
			{
				$style='';
				if($dl%2==0){$style = "style='background-color:#E4E4E4;'";}
				
				
				echo "<tr>";
					echo "<td ".$style.">".$data_list_lacunes[$dl]['time_deb']."</td>";
					echo "<td ".$style.">".$data_list_lacunes[$dl]['time_fin']."</td>";
					echo "<td ".$style.">".$data_list_lacunes[$dl]['cumul_lacune']."</td>";
					echo "<td ".$style.">".$data_list_lacunes[$dl]['observation_lacune']."</td>";
					if($import)
					{
						echo "<td ".$style.">";
							$lien_suppr = "import_result.php?im=".$im."&lac=".$data_list_lacunes[$dl]['id'];
							echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;cursor:pointer;' title='".htmlaccent('Supprimer la correction')."' onClick=\"confirm_suppr('".$lien_suppr."','la lacune','".$data_list_lacunes[$dl]['time_deb']." - ".$data_list_lacunes[$dl]['time_fin']."');\">";
						echo "</td>";
					}
				echo "</tr>";
			}
			
				
		echo "</table>";
	
		echo "<hr>";	
		
		
		if($import && tep_not_null($message_suprr_lacune))
		{
			echo "<div class='affiche'>";
				echo "<span>".$message_suprr_lacune."</span>";
			echo "</div>";		
		}
		
	echo "</div>";

}		



if(isset($data_list_lacunes_limni))
{
	
	echo "<div id='box_graph' class='gd'>";
	
		//echo "<h8 style='background-color:#df891a;border:1px solid #df891a;'>".htmlaccent('Lacunes Corrigées')."</h8>";
		echo "<h8 class='lgt'>";
		  echo "<img src='".DIR_WS_IMG_ICO."lacune.png' >";
		  echo htmlaccent('Lacunes du limnimètre');
		echo "</h8>";	
		  
						
		echo "<hr><hr><hr>";
	
		echo "<table id='stats_tab' cellspacing='0'>";
		
			echo "<tr>";
				echo "<td class='top'>".htmlaccent('Début lacune')."</td>";
				echo "<td class='top'>".htmlaccent('Fin lacune')."</td>";
				echo "<td class='top'>".htmlaccent('Observation')."</td>";
				if($import){echo "<td class='top'>&nbsp;</td>";}
			echo "</tr>";
			
			echo "<tr>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
				if($import){echo "<td>&nbsp;</td>";}
			echo "</tr>";
			
			for($dl=0;$dl<sizeof($data_list_lacunes_limni);$dl++)
			{
				$style='';
				if($dl%2==0){$style = "style='background-color:#E4E4E4;'";}
				
				
				echo "<tr>";
					echo "<td ".$style.">".$data_list_lacunes_limni[$dl]['time_deb']."</td>";
					echo "<td ".$style.">".$data_list_lacunes_limni[$dl]['time_fin']."</td>";
					echo "<td ".$style.">".$data_list_lacunes_limni[$dl]['observation_lacune']."</td>";
					if($import)
					{
						echo "<td ".$style.">";
							$lien_suppr_l = "import_result.php?im=".$im."&lac_l=".$data_list_lacunes_limni[$dl]['id'];
							echo "<img src='".DIR_WS_IMG_ICO."delete.png' style='width:16px;cursor:pointer;' title='".htmlaccent('Supprimer la correction')."' onClick=\"confirm_suppr('".$lien_suppr_l."','la lacune','".$data_list_lacunes_limni[$dl]['time_deb']." - ".$data_list_lacunes_limni[$dl]['time_fin']."');\">";
						echo "</td>";
					}
				echo "</tr>";
			}
			
				
		echo "</table>";
	
		echo "<hr>";	
		
		
		if($import && tep_not_null($message_suprr_lacune))
		{
			echo "<div class='affiche'>";
				echo "<span>".$message_suprr_lacune."</span>";
			echo "</div>";		
		}
		
	echo "</div>";

}				
?>



<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Block pour Agrandir les graphiques
----------------------------------------
*/

$display_box = '';

echo "<div id='box_graph' class='block_view' style='width:100%;z-index:1300;background-color: rgba(255, 255, 255, 1);'>\n";

	echo "<div id='graph_view' style='width:100%;height:95vh;' \">";
	
		echo "<div id='titre_graph_block'>";	
		echo "<input type='button' class='button_close_graph' id='button_close_graph' style='float:right;margin-right:2%;' value='".TEXT_IX_POPUP_CLOSE."' />";
			echo "<div id='titre_graph'></div>\n";
		echo "</div>\n";

		// Uniquement lorsque l'on est dans la page de gestion des ETL
		if(isset($id_etl) && tep_not_null($id_etl))
		{
			echo "<div id='boite_small' class='select_date' style='margin-top:20px;margin-left:35%;' >\n";
								
				echo "<p style='float:left;width:100%;font-weight:bold;padding-top:5px;text-align:center;font-size:16px;'>".htmlaccent('Modifier les coordonnées du point sélectionné')."</p>\n";

				echo "<hr>\n";

				echo "<p style='float:left;width:130px;color:#428bca;padding-top:6px;font-size:14px;'>".htmlaccent('Hauteur-x (en cm)')."</p>\n";	
				echo "<input type='text' class='input_texte_60' id='newXlarge' value='' style='float:left;' />\n";
						
				echo "<p style='float:left;width:120px;margin-left:40px;color:#428bca;padding-top:6px;font-size:14px;'>".htmlaccent('Debit-y (en cm)')."</p>\n";		
				echo "<input type='text' class='input_texte_60' id='newYlarge' value='' style='float:left;' />\n";

				echo "<button id='valid_pts_large' class='valid_img'  style='margin-left:20px;'></button>\n";  
				
			echo "</div>\n";
		}

		echo "<div style='width:99%;height:85vh;overflow-y:auto;margin-top:0.5%;margin-left:0;'>";

			echo "<div id='cadre_limit' style='width:99%;height:70vh;margin-left:1%;margin-bottom:0px;padding-bottom:0px;'>";
			echo "</div>\n";    
			
			if(!isset($_POST['one_graph']))
			{
				echo "<div id='box_options' style='float:left;margin-left:40px;'>";
					echo "<div style='display:flex;flex-direction:column;gap:4px;align-items:flex-start;'>";
						echo "<div style='display:flex;gap:4px;'>";
							echo "<button id='plus_gd_1' class='decimal_axe'
								title='".htmlaccent('Ajouter une décimale')."'
								onCLick=\"updateDecimals('cadre_limit','yaxis','+');\">+</button>\n";
							echo "<button id='moins_gd_1' class='decimal_axe'
								title='".htmlaccent('Enlever une décimale')."'
								onCLick=\"updateDecimals('cadre_limit','yaxis','-');\">-</button>\n";
						echo "</div>";
						echo "<button id='log-button_gd_1' class='log_axe'
									title='" . TEXT_CORRECT_LOG_SCALE_TITLE . "'>"
									. TEXT_CORRECT_LOG_SCALE . "</button>\n";
					echo "</div>";
				echo "</div>\n"; 					
			}

		echo "</div>\n";

	echo "</div>\n";
	
echo "<hr>";
echo "</div>\n";

?>


<script type="text/javascript">

	// Ce script permet de conserver les modifications du graphique agrandit quand on revient sur la page initiale

	// Récupère le popup et le bouton qui l'ouvre
	var popup = document.getElementById('graph_view');
	var box = document.getElementById('box_graph');
	var bclose = document.getElementById('button_close_graph');
		

	bclose.addEventListener("click", boxClose);
	// Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			boxClose();
		}
    });

	function boxClose() 
	{	
		// Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
		//if((event.target !== popup && event.target === box) || (event.target === bclose))
		if(box.style.display === 'block')
		{	
			box.style.display = 'none'; // Ferme le popup

			var plotName = 'plot_' + idPlotZoom;
			var plotData = window[plotName].data;
			var plotLayout = window[plotName].layout;

			var xaxisRange_lg = plotLayout.xaxis.range;

			
			if(xaxisRange_lg[0].includes(' ')) 
			{
				var x1_date_time_lg = xaxisRange_lg[0].split(' ');
				
				var x1_date_lg = x1_date_time_lg[0].split('-').reverse().join('-'); // Convertir les dates au format 'yyyy-mm-dd' en 'dd-mm-yyyy'
				if(x1_date_time_lg[1]){var x1_time_lg = x1_date_time_lg[1].split('.')[0]; }// Récupérer l'heure
				else{var x1_time_lg = '00:00:00';}
			}else 
			{
				var x1_date_time_lg = xaxisRange_lg[0];
				var x1_date_lg = x1_date_time_lg.split('-').reverse().join('-');
			}
			if(document.getElementById('date_min')){document.getElementById('date_min').value = x1_date_lg;} // version graph combiné
			if(document.getElementById('x1Zoom')){document.getElementById('x1Zoom').value = x1_date_lg;} // version graph 1 axe
			if(document.getElementById('x1Zoom_h')){document.getElementById('x1Zoom_h').value = x1_time_lg;}

			if(xaxisRange_lg[1].includes(' ')) 
			{
				var x2_date_time_lg = xaxisRange_lg[1].split(' ');

				// Convertir les dates au format 'yyyy-mm-dd' en 'dd-mm-yyyy'
				var x2_date_lg = x2_date_time_lg[0].split('-').reverse().join('-');
				if(x2_date_time_lg[1]){var x2_time_lg = x2_date_time_lg[1].split('.')[0]; }// Récupérer l'heure
				else{var x2_time_lg = '00:00:00';}
				
			}else 
			{
				var x2_date_time_lg = xaxisRange_lg[1];
				var x2_date_lg = x2_date_time_lg.split('-').reverse().join('-');
			}
			if(document.getElementById('date_max')){document.getElementById('date_max').value = x2_date_lg;} // version graph combiné
			if(document.getElementById('x2Zoom')){document.getElementById('x2Zoom').value = x2_date_lg;} // version graph 1 axe
			if(document.getElementById('x2Zoom_h')){document.getElementById('x2Zoom_h').value = x2_time_lg;}

			text_periode_lacune = 'du '+x1_date_lg+' au '+x2_date_lg;
			if(document.getElementById('periode_lacune')){document.getElementById('periode_lacune').value = text_periode_lacune;}
			
			var yaxisRange_lg = '';
			if(plotLayout.yaxis)
			{
				yaxisRange_lg = plotLayout.yaxis.range;
				if(document.getElementById('y1Zoom')){document.getElementById('y1Zoom').value = parseInt(yaxisRange_lg[0]);} // version graph 1 axe
				if(document.getElementById('y2Zoom')){document.getElementById('y2Zoom').value = parseInt(yaxisRange_lg[1]);} // version graph 1 axe

				if(document.getElementById('y_min_1')){document.getElementById('y_min_1').value = parseInt(yaxisRange_lg[0]);} // version graph combiné
				if(document.getElementById('y_max_1')){document.getElementById('y_max_1').value = parseInt(yaxisRange_lg[1]);} // version graph combiné
			}

			var yaxis2Range_lg = '';
			if(plotLayout.yaxis2)
			{
				yaxis2Range_lg = plotLayout.yaxis2.range;
				if(document.getElementById('y_min_2')){document.getElementById('y_min_2').value = parseInt(yaxis2Range_lg[0]);} // version graph combiné
				if(document.getElementById('y_max_2')){document.getElementById('y_max_2').value = parseInt(yaxis2Range_lg[1]);} // version graph combiné
			}
			
			
			Plotly.react('plot_'+idPlotZoom, plotData, plotLayout, config);			
		}
	}
	
		  
</script>
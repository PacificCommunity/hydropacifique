<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Modification des ETL (Etalonnages - Stations hydrométrique)
----------------------------------------
*/

require('include/application_top.php');

// --------------------------------------
// INIT VAR
$message_info = '';

$modif=false;

// Régler le fuseau horaire sur votre emplacement
//date_default_timezone_set('Europe/Paris');
$date_now = date('d/m/Y H:i');

$nom_station = '';	
$code_station = '';



$nb_data=0;
$graph_x = '';
$graph_y = '';
$min_y = 0;
$max_y = 0;

$min_h = 0;
$max_h = 0;
$min_q = 0;
$max_q = 0;

$row = 0;
$id_etl = 0;
$id_etl_first = 0;

//$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" onclick=\"this.className='rowSelect';\"";
$row_l="class='row1' onclick=\"this.className='rowSelect';\"";
$print_row = '';   

$titre_etl = '';
$js_load_trace = '';
$data_graph_all = ''; // Variables données pour les graphiques 


// --------------------------------------

if(isset($_GET['st']))
{
    $st_id = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['st'])));
    
    $sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.nom_court, s.code_station, 
									s.id_region, s.id_commune
					FROM ".TABLE_STATION." s 
					WHERE s.id_station=".$st_id;
	
	$station_query = tep_db_query($sql_link,$sql_station);
	$station = tep_db_fetch_array($station_query);

    if(isset($station['id_station']))
    {
        $nom_station = htmlaccent($station['nom_station']);	
        $code_station = htmlaccent($station['code_station']);
        $modif=true;
    }
    else{$message_info .= htmlaccent('La station n\'est pas identifiée.');}
}

// Requête SQL
if($modif)
{
    if(!empty($_POST['check_ETL']))
	{
        
        $list_idETL = '';
        // Boucle pour parcourir toutes les cases à cocher cochées        
		foreach($_POST['check_ETL'] as $id_ETL){$list_idETL .= $id_ETL.',';}

        $list_idETL = '('.substr($list_idETL, 0, -1).')';    


        $sql_ETL = "SELECT DISTINCT id, datetime_first, datetime_end
                    FROM ".TABLE_DATA_ETL."
                    WHERE id IN ".$list_idETL;

        $ETL_query = tep_db_query($sql_link,$sql_ETL);
        while($ETL_tab = tep_db_fetch_array($ETL_query))
        {
            $id_etl = $ETL_tab['id'];
            $row++;

            $dateAuFormatInitial = $ETL_tab['datetime_first'];
            $dateObjet = new DateTime($dateAuFormatInitial);
            $datetime_first = $dateObjet->format("d/m/Y H:i:s");        
            $date_first = $dateObjet->format("d/m/Y");

            $datetime_end = '-';
            $date_end = '-';

            $dateAuFormatInitial = $ETL_tab['datetime_end'];        
            if(tep_not_null($dateAuFormatInitial))
            {
                $dateObjet = new DateTime($dateAuFormatInitial);
                $datetime_end = $dateObjet->format("d/m/Y H:i:s");   
                $date_end = $dateObjet->format("d/m/Y");
            }

            $titre_etl = "ETL : ".$date_first." - ".$date_end;
        
            ${'graph_x_'.$id_etl} = "";
            ${'graph_y_'.$id_etl} = "";        
            ${'index_pts_'.$id_etl} = "";             
        

            $sql_ETL_data = "SELECT DISTINCT id, hauteur, debit, code_qualite
                            FROM ".TABLE_DATA_ETL_DATA." etl
                            WHERE id_etl=".$id_etl." ORDER BY hauteur ASC";
            
            $ETL_data_query = tep_db_query($sql_link,$sql_ETL_data);
            while($ETL_data_tab = tep_db_fetch_array($ETL_data_query))
            {
                $id_etl_data = $ETL_data_tab['id'];
                $hauteur = $ETL_data_tab['hauteur'];            
                $debit = $ETL_data_tab['debit'];     
                //$code_qualite = $ETL_data_tab['code_qualite'];

                ${'index_pts_'.$id_etl} .= $id_etl_data.',';
                ${'graph_x_'.$id_etl} .= $hauteur.',';
                ${'graph_y_'.$id_etl} .= $debit.',';           
                //${'color_pts_'.$id_etl} .= '\'#071952\',';      
                    

                if($max_h < $hauteur){$max_h=$hauteur;}
                if($max_q < $debit){$max_q=$debit;}           
            }           
            
            ${'index_pts_'.$id_etl} = substr(${'index_pts_'.$id_etl}, 0, -1);
            ${'graph_x_'.$id_etl} = substr(${'graph_x_'.$id_etl}, 0, -1);
            ${'graph_y_'.$id_etl} = substr(${'graph_y_'.$id_etl}, 0, -1);
            //${'color_pts_'.$id_etl} = substr(${'color_pts_'.$id_etl}, 0, -1);


            $data_graph_all .=
            "
                var trace_".$id_etl." = 
                { 
                    x: [". ${'graph_x_'.$id_etl} ."],
                    y: [". ${'graph_y_'.$id_etl} ."],    
                    //colors: colors,        
                    //ids: [". ${'index_pts_'.$id_etl} ."],

                    name: '".$titre_etl."',

                    mode: 'markers+lines', // type de trace (scatter plot)
                    type: 'scatter', // type de graphique
                    marker: { size: 8}, // taille des marqueurs   
                };  
            ";

            $js_load_trace .= "trace_".$id_etl.",";
        }
        
        $data_graph_all .= "var data = [".substr($js_load_trace, 0, -1)."];"; // substr($str, 0, -1) pour enlever la dernière virgule de la chaine de caractère

        $data_graph_all .= "var layout = 
                                    {
                                        xaxis: 
                                        {
                                            title: {
                                                text: 'Hauteur (cm)',
                                                standoff: 20 // Ajuster la distance entre le titre et l'axe
                                            },

                                            tickfont: {size: 11}, // Taille des caractères des graduations

                                            titlefont: {family: 'roboto, arial, helvetica',
                                                size: 14,
                                                bold: true,
                                                color: '#000000'},

                                            autorange: false,
                                            range: [".($min_h-30).", ".($max_h*1.1)."], 
                                        },
                                        yaxis:
                                        {
                                            title: {
                                                    text: 'Débit (m3/s)',
                                                    standoff: 15 // Ajuster la distance entre le titre et l'axe
                                                },

                                            tickfont: {size: 11}, // Taille des caractères des graduations
                                            
                                            titlefont: {family: 'roboto, arial, helvetica',
                                                    size: 14,
                                                    bold: true,
                                                    color: '#000000'},
                                            
                                            autorange: false,
                                            range: [".($min_q-100).", ".($max_q*1.1)."], 
                                        },
                                        
                                        hovermode: 'x',
                                        hoverlabel: { bgcolor: '#fff', font: { size: 12, color: '#000' } },
                                        margin: {l: 60, r: 30, t: 30, b: 40}, // Par défault : l: 60, r: 60, t: 80, b: 60 
                                        
                                    };
                                ";
        
    }                                
}



// --------------------------------------
// Debut page HTML

require(DIR_WS_STRUCTURE . 'header_web.php');


echo "<body>";

require(DIR_WS_STRUCTURE . 'block_graph.php');
require(DIR_WS_STRUCTURE . 'header.php'); // Bando Haut
include(DIR_WS_BOX . 'nav_accueil.php'); // Menu

echo "<div id='contour_general'>";

	if(tep_not_null($message_info)){echo "<div id='contenu_info'>".$message_info."</div>";}        
	
	echo "<div id='contenu_centre'>";
		
            echo "<h1 id='h1_graph'>";
                            
                echo "<span style=font-weight:bold;>".htmlaccent('Relation d\'Etalonnage (ETL)')."</span>";
                echo "<span>".htmlaccent(' - Station hydrométrique : '.$code_station.' - '.$nom_station)."</span>";
                
            echo "</h1>";

            echo "<hr>";

            if($modif)
            {
                // Div des graphiques

                echo "<div id='cadre_graph' style='position:fixed;width:90%;max-height:70%;overflow-y: auto;overflow-x: none;'>\n";
                        
                    echo "<div id='boxpopup' class='select' style='width:95%;margin-top:5px;'>\n";

                        //echo "<p id='graph_title' class='titre'>".$titre_graph."</p>";

                        echo "<div id='plot' class='graph'></div>\n";	
                        
                    echo "<hr>\n";
                    echo "</div>\n";	
                    
                echo "<hr>\n";
                echo "</div>\n";    
            }   
            else
            {
                echo "<div id='boxpopup' >\n";
                    echo "<p class='alert'>".htmlaccent('Aucune données n\'a été trouvée')."</p>";
                echo "<hr>";
                echo "</div>";
            }
	
	echo "<hr>";
	echo "</div>";
	
echo "<hr>";
echo "</div>";
	
require('include/application_bottom.php'); 
	
echo "</body>";

echo "</html>";

?>


<script>

// Génération des graphiques Config generale
var config = 
{
    responsive: true,
    doubleClickDelay: 1000, //Delay du zoom
    
    displayModeBar: true, // Affichage constant du menu de la figure
    scrollZoom: true, // Zoom avec la roulette de la souris

    modeBarButtonsToRemove: ['select2d','lasso2d','autoScale2d','zoomIn2d','zoomOut2d'],
    modeBarOrientation: 'v',

    displaylogo: false,
};

<?php 
    echo $data_graph_all; 
    
    echo "Plotly.newPlot('plot', data, layout, config);";
?>

</script>
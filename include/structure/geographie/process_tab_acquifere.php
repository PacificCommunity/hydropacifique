<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Processus permettant de supprimer une région géographique
Appelé depuis gestion_geo.php -> pform_geo_regiongeo.php
----------------------------------------
*/

// ----------------------------------------------
// nécessaire pour la configuration du script

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');

// Récupération des données JSON envoyées depuis la requête AJAX
$jsonDataInfo = file_get_contents('php://input');

// Décoder les données JSON en un tableau associatif PHP
$dataInfo = json_decode($jsonDataInfo, true);

$territoire_id = $dataInfo['territoireId'];

// Initialisation Variables
$tab_geo_acquifere = true;
$message_info = '';


$acquifere_array = array();


// Extraction données de la BDD

// Requête sur TABLE_acquifere
$sql_acquifere = "SELECT DISTINCT id, nom, description 
                    FROM ".TABLE_GEO_ACQUIFERE." 
                    WHERE id_territoire=".$territoire_id." ORDER BY LOWER(nom) ASC";
$acquifere_query = tep_db_query($sql_link,$sql_acquifere);
while ($acquifere = tep_db_fetch_array($acquifere_query))
{	
    $id_acquifere = $acquifere['id'];    
	$nom_acquifere = $acquifere['nom'];
	$description_acquifere= $acquifere['description'];

    $del_acquifere = true;
    $sql_acquifere_station = "SELECT id_station FROM ".TABLE_STATION."
                        WHERE id_acquifere=".$id_acquifere."
                        LIMIT 1";
    $acquifere_station_query = tep_db_query($sql_link,$sql_acquifere_station);  
    $acquifere_station = tep_db_fetch_array($acquifere_station_query);
    if(isset($acquifere_station)){$del_acquifere = false;}    

    $acquifere_array[$id_acquifere] = array('nom_acquifere' => $nom_acquifere,
                                        'description_acquifere' => $description_acquifere,  
                                        'del_acquifere' => $del_acquifere
                                        );

} 

// Construction des données
$row = 0;
$htmlcode = '';

$htmlcode .= "<div class='table-container' style='float:left;height:70vh;'>";
			
    $htmlcode .= "<table id='table_tri' cellspacing='0' style=''>";
    
        $htmlcode .= "<thead>";
            $htmlcode .= "<tr class='header-row' style='background-color: #eef3f8;'>";
                $htmlcode .= "<th style='width:270px;'>".htmlaccent('Intitulé')."</th>";	
                $htmlcode .= "<th style='width:470px;'>".htmlaccent('Description')."</th>";	
                $htmlcode .= "<th style='width:60px;text-align:center;'>&nbsp;</th>";
            $htmlcode .= "</tr>";
        $htmlcode .= "</thead>";		
        
        // Nouvelle Entrée

        $htmlcode .= "<tr><td colspan='3' style='color:#000;font-size:14px;font-weight:bold;'>".htmlaccent('Ajouter un Acquifere')."</td></tr>\n";

        $htmlcode .= "<tr>";
        
            $htmlcode .= "<td><input type='text' style='width:250px;border:2px solid #609966;' name='acquifere_nom_0' ></td>";

            $htmlcode .= "<td><input type='text' style='width:450px;border:2px solid #609966;' name='acquifere_description_0' ></td>";

            $htmlcode .= "<td>&nbsp;</td>";
            
        $htmlcode .= "<tr>";

        $htmlcode .= "<tr><td colspan='3' class='lignevide'>&nbsp;</td></tr>";

        if(isset($acquifere_array))
        {   
            // Mise en forme des lignes pour affichage des régions hydrologiques
            foreach($acquifere_array as $id => $data)
            {				
                if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 

                $htmlcode .= "<tr ".$row_l." id='row_rh_".$id."' >";
                    
                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:250px;' name='acquifere_nom_".$id."' value='".$data['nom_acquifere']."'>\n";
                    $htmlcode .= "</td>";

                    $htmlcode .= "<td>";
                        $htmlcode .= "<input type='text' style='width:450px;' name='acquifere_description_".$id."' value='".$data['description_acquifere']."'>\n";
                    $htmlcode .= "</td>";
                    
                    // supprimer
                    $htmlcode .= "<td style='text-align:center;'>";

                        if($data['del_acquifere'])
                        {
                            $htmlcode .= "<span class='del' title='".htmlaccent('Supprimer')."' onClick=\"delete_acquifere('".$id."');\">";
                                $htmlcode .= "X";
                            $htmlcode .= "</span>";
                        }
                        else{$htmlcode .= "<span>-</span>";}
                    
                    $htmlcode .= "</td>\n";
                
                $htmlcode .= "</tr>";					
            }
        }
        else
        {
            $tab_geo_acquifere = false;
            $message_info .= "Aucune donnée n'a été trouvée";
        }
    
    $htmlcode .= "</table>";

$htmlcode .= "</div>";


// Remplissage du tableau de retour

$responseData = array(
    'tab_geo_acquifere' => $tab_geo_acquifere,
    'htmlcode' => $htmlcode,
    'message_info' => $message_info
);

// Encodage du tableau associatif en JSON
$jsonResponse = json_encode($responseData);

// Envoi des données coté Client
echo $jsonResponse;

?>
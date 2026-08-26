<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Affichage d'un tableau pour présenter la liste des derniers exports
----------------------------------------
*/

// TABLE SQL - Recupération des données utilisateurs
$sql_user_list = "SELECT DISTINCT id, id_statut, login, nom, prenom FROM ".TABLE_USER;
$user_list_query = tep_db_query($sql_link,$sql_user_list);
while ($user_list = tep_db_fetch_array($user_list_query))
{
    $id = $user_list['id'];
    $id_statut = $user_list['id_statut'];
	$login = htmlaccent(html_entity_decode($user_list['login'] ?? $default_string));
	$nom = ucfirst(strtolower(htmlaccent(html_entity_decode($user_list['nom'] ?? $default_string))));
	$prenom = ucfirst(strtolower(htmlaccent(html_entity_decode($user_list['prenom'] ?? $default_string))));

	$user_list_array[$id] = array('id_statut' => $id_statut,
                                    'login' => $login,
                                    'nom' => $nom,
                                    'prenom' => $prenom
                                    );
}


// TABLE SQL - Recupération de la liste des Actions à afficher

$nb_actions = 0;

$sql_action = "SELECT DISTINCT id, id_user, type_action, info, dateheure, file_export
				FROM ".TABLE_ACTIONS."
				WHERE type_action=36
				ORDER BY dateheure DESC                
                LIMIT 100";

$action_query = tep_db_query($sql_link,$sql_action);
while ($action_tab = tep_db_fetch_array($action_query))
{	
	$id =  $action_tab['id'];
    $id_user_action =  $action_tab['id_user'];    
    //$type_action =  $action_tab['type_action'];
	$info = htmlaccent(html_entity_decode($action_tab['info'] ?? $default_string));
    
    $file_export =  $action_tab['file_export'];
    $file_info = strstr($file_export, '.', true).'.txt'; // true pour récupérer la partie avant le premier point

    $file_exist = false;
    if(file_exists(DIR_WS_DATA_EXPORT.$action_tab['file_export'])){$file_exist = true;}

    $file_exist_txt = false;
    if(file_exists(DIR_WS_DATA_EXPORT.$file_info)){$file_exist_txt = true;}

    // Crée un objet DateTime pour la dernière date valide
    $dateheure = new DateTime($action_tab['dateheure']);
    // Formatte la date au format "d-m-Y HH:mm:ss"
    $dateheure_formatted = $dateheure->format('d-m-Y H:i:s');
	
    // Tableau station avec toutes les données	
	$action_array[$id] = array('id_user_action' => $id_user_action,
                                'info' => $info,
                                'dateheure' => $dateheure_formatted,
                                'file_export' => $file_export,
                                'file_exist' => $file_exist,
                                'file_info' => $file_info,
                                'file_exist_txt' => $file_exist_txt        
                                );
}
if(isset($action_array)){$nb_actions = sizeof($action_array);}


// Affichage Tableau
if(isset($action_array) && ($nb_actions>0))
{
    echo "<div class='table-container' style='float:left;width:98%;height:75vh;'>";

        echo "<table id='table_tri' cellspacing='0'>";
    
            echo "<thead>";
                echo "<tr class='header-row'>";		
                    echo "<th style='width:90px;font-size:12px;padding-left:20px;'>".htmlaccent('Login')."</th>";						
                    echo "<th style='width:150px;font-size:12px;'>".htmlaccent('Prénom Nom')."</th>";         
                    echo "<th style='width:150px;font-size:12px;'>".htmlaccent('Date')."</th>";          
                    echo "<th style='width:100px;font-size:12px;text-align: center;'>".htmlaccent('Détails')."</th>";
                    echo "<th style='width:150px;font-size:12px;'>".htmlaccent('Fichier à télécharger')."</th>";

                echo "</tr>";
            echo "</thead>";
    
            //ligne vide dans le tableau	
            echo "<tr>";
                echo "<td colspan='6' style='height:15px;'>&nbsp;</td>";
            echo "</tr>";	
                
            foreach($action_array as $key => $value)
            {	
                if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
                
                echo "<tr ".$row_l." style='height:20px;'>";

                    echo "<td style='width:90px;padding-left:20px;' >".$user_list_array[$value['id_user_action']]['login']."</td>\n"; 							
                    echo "<td style='width:150px;'>".$user_list_array[$value['id_user_action']]['prenom']." ".$user_list_array[$value['id_user_action']]['nom']."</td>\n";								
                    echo "<td style='width:150px;' >".$value['dateheure']."</td>\n";
                    echo "<td style='width:100px;text-align: center;'>";
                        if($value['file_exist_txt'])
                        {
                            echo "<a href='".DIR_WS_DATA_EXPORT.$value['file_info']."' target='blank_' >"; 
                                echo "<img src='".DIR_WS_IMG_ICO."detail.png' style='width:20px;cursor:pointer;'>";							
                            echo "</a>";
                        }
                        else{echo '-';}  
                    echo "</td>\n"; 
                    echo "<td style='width:300px;'>";
                        if($value['file_exist']){echo "<a href='".DIR_WS_DATA_EXPORT.$value['file_export']."' download>".$value['file_export']."</td>\n";}
                        else{echo '-';}                             
                    
                echo "</tr>\n";
                
                $row++;
            }
            
        echo "</table>";
        
    echo "</div>";
}
else
{
    echo "<div id='boxpopup' style='margin-left: 1%;'>\n";
        echo "<p class='alert'>".htmlaccent('Aucune - Action - n\'a été trouvée')."</p>";
    echo "</div>";
}


?>
<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce block permet d'afficher les instructions de format de fichiers attendu pour l'export
Cette fenêtre s'affiche quand on clique sur un lien d'information
----------------------------------------
*/

echo "<div id='box_import_info' class='block_view' >\n"; // style='background:transparent;'

	echo "<div id='cadre_view_2' style='float:left;width:72%;margin-top:20px;margin-left:18%;padding:0px;background-color:#FBF9F1;' >\n";

        echo "<p style='float:left;width:100%;height:30px;padding:5px 0;color:#fff;background-color:#000;'>";
            echo "<span style='font-size:20px;font-weight:bold;margin-left:5px;'>";
                echo htmlaccent('Instruction pour l\'importation des fichiers');
            echo "</span>";
            echo "<span style='float:right;font-size:24px;margin-right:5px;cursor:pointer;' onCLick=\"document.getElementById('box_import_info').style.display='none';\" title='Fermer'>X</span>";
        echo "</p>\n";  

	
		echo "<div id='cadre_limit' style='height:50%;margin-top: 0px;padding:10px 5px;'>";	

            echo "<div id='cadre_info_page' style='width:29%;margin-top:0px;' >\n";
            
                $text_info = "
                    {{Vous pouvez choisir un ou plusieurs fichiers à importer en cliquant sur le bouton de sélection de fichiers}}                    
                    
                    La station doit être enregistrée dans l'application
                    Menu : 'Module / Stations de mesure'

                    Le type de chronique doit être enregistré dans l'application
                    Menu : 'Paramétrage / Chroniques'   
                    ";
                $text_info = preg_replace('/{{(.*?)}}/','<strong>$1</strong>',htmlaccent($text_info));
                echo $text_info; 


                $text_info = "\n{{Les type de fichiers valides : }}\n";

                    foreach($import_files as $key_name_ext => $tab_ext)
                    {
                        $text_info .=  $key_name_ext." - ".$tab_ext['description'];
                        if(tep_not_null($tab_ext['separateur'])){$text_info .= " - Séparateur : '".$tab_ext['separateur']."'";}
                        $text_info .=  "\n";
                    }
                
                $text_info = preg_replace('/{{(.*?)}}/','<strong>$1</strong>',htmlaccent($text_info));
                echo $text_info;    

            echo "<hr>\n";
            echo "</div>\n";
            

            echo "<div id='cadre_info_page' style='width:29%;margin-left:10px;margin-top:0px;'>\n";
            
                $text_info = "
                    {{Format CSV}}
                    
                    Nom de fichier : codeStation_initialChronique
                    (ex : {{5700503100_CI.csv}})         
                    
                    --

                    {{Format Excel}} : l'importation multi-feuilles est valide
                    
                    Nom de fichier : codeStation_texte
                    (ex : {{5700503100_Dumbea.xlsx}})    
                    
                    Nom d'une feuille : codeStation_initialChronique_texte
                    (ex : {{5700503100_PJE_1}})
                    Le numéro de station doit être identique à celui du fichier                                                             
                    ";

                $text_info = preg_replace('/{{(.*?)}}/','<strong>$1</strong>',htmlaccent($text_info));
                echo $text_info;    

            echo "<hr>\n";
            echo "</div>\n";

            echo "<div id='cadre_info_page' style='width:29%;margin-left:10px;margin-top:0px;'>\n";
            
                $text_info = "                    
                    Les données doivent se présenter en 3 colonnes :

                    {{date_heure}} : dd/mm/yyyy hh:mm:ss (ex : 13/01/2018 10:53:24)
                    
                    {{valeur}} : nombre décimal ('.' ou ',')
                    
                    {{qualite}} : code qualité (peut être nulle ou vide)
                    Le code qualité doit être enregistré dans l'application.
                    Menu : 'Paramétrage / Codes qualités'.

                    Si la première ligne contient les en-têtes de colonnes cochez la case optionnelle.
                ";

                $text_info = preg_replace('/{{(.*?)}}/','<strong>$1</strong>',htmlaccent($text_info));
                echo $text_info;                    


            echo "<hr>\n";
            echo "</div>\n";
    
            
		
		echo "</div>\n";	
		
	echo "</div>\n";

echo "</div>\n";

?>
<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Popup pour permettre une confirmation de l'enregistrement des données
----------------------------------------
*/

$day = date('d');
$month = date('m');
$year = date('Y');


// Champs Code Qualité
$sql_quality = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, id_eq_type 
FROM ".TABLE_DATA_QUALITE."
WHERE (id_eq_type=".$id_typedata_encours." OR id_eq_type='') AND init_qualite_data<>'' 
ORDER BY init_qualite_data ASC";
$quality_query = tep_db_query($sql_link,$sql_quality);
while ($quality_tab = tep_db_fetch_array($quality_query))
{
    $quality_array[$quality_tab['id_data_qualite']] = array('init_qualite_data' => htmlaccent(html_entity_decode($quality_tab['init_qualite_data'] ?? $default_string)),
                                                'nom_qualite_data' => htmlaccent(html_entity_decode($quality_tab['nom_qualite_data'] ?? $default_string)),
                                                'info_qualite_data' => html_entity_decode($quality_tab['info_qualite_data'] ?? $default_string),
                                                'id_eq_type' => html_entity_decode($quality_tab['id_eq_type'] ?? $default_string)
                                                );
}


$display_box = '';

echo "<div id='box_verif_savedata' class='block_view' >\n";

    echo "<div id='cadre_view' style='width:500px;margin-top:50px;padding:0;background-color:#FBF9F1;' >";

        echo "<p style='width:95%;height:35px;padding-top:8px;padding-left:5%;font-size:17px;font-weight:bold;color:#fff;background-color:#000;'>";
            echo "Êtes vous sûr de vouloir valider les corrections ?";
        echo "</p>\n";  

        echo "<div id='cadre_modif_chron' style='float:left;width:90%;margin-top:10px;margin-left:5%;display:none;'>";

            echo "<div style='width:100%;margin-bottom:5px;'>";
            
                echo "<p style='float:left;width:50%;padding-top:5px;font-size:16px;'>";
                    echo "Chronique qui sera modifiée :";                
                echo  "</p>\n";

                echo "<select name='select_type_chron' id='select_type_chron' style='float:left;width:50%;display:none;font-size:14px;' >";
                                                                    
                    if(isset($type_chron_array))
                    {
                        foreach($type_chron_array as $id_type_chron => $type_chron)
                        {
                            //if(($type_chron['id_eq_type_data']==$id_eq_type) &&  ($id_type_chron != $id_chron_encours))
                            if(($type_chron['id_eq_type_data']==$id_typedata_encours))
                            {
                                if($id_type_chron != $typedata_chron)
                                {
                                    echo "<option value='".$id_type_chron."' >".$type_chron['init_type_data']." - ".$type_chron['nom_type_data']."</option>\n";
                                }
                                else
                                {
                                    echo "<option value='".$id_type_chron."' title='(Chronique en cours)' SELECTED>* ".$type_chron['init_type_data']." - ".$type_chron['nom_type_data']."</option>\n";
                                }
                            }
                        }
                    }
                    
                echo "</select>"; 

            echo "</div>";
                       
            
            echo "<input type='hidden'  id='id_modif_chron' >";  
            echo "<input type='text'  id='text_modif_chron' readonly 
                    style='float:left;width:100%;font-size:16px;font-weight:bold;color:#930000;background:none;border:none;'>";  


        echo "</div>";

        echo "<div id='cadre_chron_qual' style='float:left;width:90%;margin:10px 0;margin-left:5%;'>";

            echo "<p style='width:100%;font-size:14px;'>";
                echo "Code Qualité pour la correction";
            echo "</p>";	
            
            echo "<select name='select_qual_chron' id='select_qual_chron' style='float:left;width:210px;font-size:14px;' >";

                echo "<option value='0' >-</option>\n";
                                    
                if(isset($quality_array))
                {
                    foreach($quality_array as $id_quality => $quality_data)
                    {
                        echo "<option value='".$id_quality."' >".$quality_data['init_qualite_data']." - ".$quality_data['nom_qualite_data']."</option>\n";
                    }
                }
                
            echo "</select>";

        echo "</div>";

        echo "<div style='float:left;width:90%;margin:10px 0;margin-left:5%;'>";
                    
                echo "<p style='width:100%;font-size:14px;'>";
                    echo "Observation sur la correction";
                echo "</p>";

                echo "<textarea id='obs_user' name='obs_user' style='float:left;width:95%;font-size:13px;height:80px;'></textarea>\n";       
                
        echo "</div>";     


        echo "<div style='float:left;width:90%;margin-top:15px;margin-left:5%;'>";

            echo "<p style='width:100%;font-size:18px;font-weight:bold;'>";
                echo "Si des données existent pour la même chronique, sur la même station et la même période, elles seront effacées.";
            echo  "</p>\n"; 

        echo "</div>";


        echo "<div style='float:left;width:90%;margin-top:20px;margin-left:10%;'>";
        
                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' class='button' id='ok_valid_savedata' value='Valider'>";
                echo "</div>";

                echo "<div style='float:left;width:45%;'>";
                    echo "<input type='button' id='no_valid_savedata' class='button_close' value='Annuler'>";
                echo "</div>";
            
        echo "<hr>";
        echo "</div>";
    
    echo "<hr>";
    echo "</div>";
    

echo "</div>";

?>


<script type="text/javascript">
	
	// Récupère le popup et le bouton qui l'ouvre
	var popup = document.getElementById('cadre_view');
	var box_verif_savedata = document.getElementById('box_verif_savedata');

    var idSelectChron = document.getElementById('id_modif_chron');
    var textSelectChron = document.getElementById('text_modif_chron');
		
	// Ajoute un événement de clic au document
	document.addEventListener("click", function(event)
	{
		// Vérifie si l'élément cliqué est à l'intérieur ou à l'extérieur du popup
		if (event.target !== popup && event.target === box_verif_savedata) 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			box_verif_savedata.style.display = "none";
		}
	});

    // Ajout d'un gestionnaire d'événements pour la touche Echap
	document.addEventListener("keydown", function(event) 
	{
		if (event.key === "Escape") 
		{
			// Ferme le popup et le popup d'info s'il a été ouvert
			box_verif_savedata.style.display = "none";
		}
    });



    // Fonction permettant de modifier la chronique d'accueil de la correction
    let selectChron = document.getElementById('select_type_chron');
    selectChron.addEventListener('change', chooseChron);

    function chooseChron()
    {
        // Récupérer l'index de l'option sélectionnée
        let selectedIndex  = selectChron.selectedIndex;

        // Récupérer l'option sélectionnée
        let selectedOption = selectChron.options[selectedIndex];

        // Récupérer le texte de l'option sélectionnée
        let textSelectOption = selectedOption.text;

        // Récupérer la valeur de l'option sélectionnée
        let valueSelectOption = selectedOption.value;

        idSelectChron.value = valueSelectOption;
        textSelectChron.value = textSelectOption;
    }

    

		  
</script>
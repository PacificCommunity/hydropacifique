<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

$update_sql = array();
$validUpdate = true;

$sql_ETL = "SELECT DISTINCT id, datetime_first, datetime_end
                FROM ".TABLE_DATA_ETL."
				WHERE id_station=".$st_id;

$ETL_query = tep_db_query($sql_link,$sql_ETL);
while($ETL_tab = tep_db_fetch_array($ETL_query))
{
    $id_etl = $ETL_tab['id']; 
    
    $sql_ETL_data = "SELECT DISTINCT id, hauteur, debit, code_qualite
                        FROM ".TABLE_DATA_ETL_DATA."
                        WHERE id_etl=".$id_etl." ORDER BY hauteur ASC";
        
    $ETL_data_query = tep_db_query($sql_link,$sql_ETL_data);
    while($ETL_data_tab = tep_db_fetch_array($ETL_data_query))
    {
        $id_etl_data = $ETL_data_tab['id'];
        
        if(isset($_POST['input_hauteur_'.$id_etl.'_'.$id_etl_data]))
        {
            // Récupération des valeurs depuis la méthode POST
            $hauteur = post_secure($sql_link,$_POST['input_hauteur_'.$id_etl.'_'.$id_etl_data]);
            $debit = post_secure($sql_link,$_POST['input_debit_'.$id_etl.'_'.$id_etl_data]);

            // Vérification si les champs sont vides
            if (empty($hauteur) || empty($debit))
            {
                $message_modif_ETL = htmlaccent('Les champs Hauteur et Débit ne doivent pas être vides.');
            }
            else
            {
                // Vérification si les champs contiennent des valeurs numériques
                if (is_numeric($hauteur) && is_numeric($debit))
                {
                    $hauteur = str_replace(',', '.', $hauteur);
                    $debit = str_replace(',', '.', $debit);
                    
                    // Ajouter la requête de mise à jour au tableau
                    $update_sql[] = "UPDATE ".TABLE_DATA_ETL_DATA." SET hauteur='".$hauteur."', debit='".$debit."' WHERE id=".$id_etl_data.";";
                } else 
                {
                    $message_modif_ETL = htmlaccent('Les champs Hauteur et Débit doivent contenir uniquement des valeurs numériques.');
                    $validUpdate = false;
                }
            }
        }
    }
}

if($validUpdate)
{
    // Exécution des requêtes de mise à jour
    foreach ($update_sql as $query)
    {
        mysqli_query($sql_link, $query);
    }
    $message_modif_ETL = htmlaccent('Les données ont été mises à jour avec succès.');
}

?>
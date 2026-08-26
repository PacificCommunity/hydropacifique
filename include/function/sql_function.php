<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Dédier au projet de HydroPacifique
Suppression des données dans table Data_all et Meta associée
----------------------------------------
*/


function deleteDataAndMeta($sql_link,$id_station, $id_chron_type, $dateDebut_us = null, $dateFin_us = null)
{
    // ÉTAPE 1 : Identifier les id_meta concernés par la suppression des données de la table DATA
    $whereClauseDate = "";
    //if ($dateDebut_us !== null && $dateFin_us !== null) 
    if (!empty($dateDebut_us) && !empty($dateFin_us))
    {
        $whereClauseDate = " AND da.dateheure >= '".$dateDebut_us."' AND da.dateheure <= '".$dateFin_us."'";
    }

    $sql_select_meta = "SELECT DISTINCT da.id_meta
                        FROM ".TABLE_DATA_ALL." da
                        JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                        WHERE dm.id_station = ".$id_station."
                        AND dm.id_typedata = ".$id_chron_type;
    $sql_select_meta .= $whereClauseDate;                  

    $select_meta_tab = array();
    $nb_meta_select = 0;
    
    $select_meta_query = tep_db_query($sql_link,$sql_select_meta); 
    while ($select_meta = tep_db_fetch_array($select_meta_query))
	{
        $select_meta_tab[] = $select_meta['id_meta'];        
        $nb_meta_select++;
    }

    // ÉTAPE 2 : Supprimer les données de la table DATA
    $sql_delete_data = "DELETE da FROM ".TABLE_DATA_ALL." da
                        JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                        WHERE dm.id_station = ".$id_station."
                        AND dm.id_typedata = ".$id_chron_type;
    $sql_delete_data .= $whereClauseDate;                         



    mysqli_query($sql_link,$sql_delete_data);
    $rows_deleted = mysqli_affected_rows($sql_link);
    

    // ÉTAPE 3 : Compter le nombre d'occurrence encore existante pour chaque id_meta potentiellement concerné par la suppression
    foreach ($select_meta_tab as $key_id_meta) 
    {
        $sql_count_data = "SELECT COUNT(*) AS nb_occurrences_meta FROM ".TABLE_DATA_ALL." WHERE id_meta = ". $key_id_meta;
        
        $count_data_query = tep_db_query($sql_link,$sql_count_data); 
        $count_data = tep_db_fetch_array($count_data_query);

        $nb_occurrences_meta = $count_data['nb_occurrences_meta'];

        // ÉTAPE 4 : Supprimer les id_meta de la table Meta si NO = 0
        if($nb_occurrences_meta == 0) 
        {
            $sql_delete_meta = "DELETE FROM ".TABLE_DATA_META." WHERE id = ". $key_id_meta;
            tep_db_query($sql_link,$sql_delete_meta);
        }
    }

    return $rows_deleted;
}



?>
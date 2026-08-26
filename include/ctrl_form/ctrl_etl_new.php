<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

// Définir le modèle d'expression régulière pour le format attendu
$pattern = "/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/";
$dataInitETL = post_secure($sql_link,$_POST['dataInitETL']);

// Utiliser la fonction preg_match pour vérifier si la date correspond au modèle
if(preg_match($pattern, $dataInitETL))
{
    $sql_last_ETL = "SELECT id, datetime_first, datetime_end
                    FROM ".TABLE_DATA_ETL." WHERE id_station=".$st_id." ORDER BY id DESC LIMIT 1";
    $last_ETL_query = tep_db_query($sql_link,$sql_last_ETL);
    $last_ETL = tep_db_fetch_array($last_ETL_query);

    if(isset($last_ETL['id']))
    {
        $originalDate = DateTime::createFromFormat('d/m/Y H:i', $dataInitETL);
        $DatetimeFirst_Input = $originalDate->format('Y-m-d H:i:s');
        
        $DatetimeFirst_Last = $last_ETL['datetime_first'];
        
        if($DatetimeFirst_Input > $DatetimeFirst_Last)
        {
            // Moins 1 min pour la date de fin de validité du dernier ETL enregistré
            $originalDate->sub(new DateInterval('PT1M'));
            $DatetimeEnd_Last = $originalDate->format('Y-m-d H:i:s');

            // Mise à jour de la date de fin de validité du dernier ETL en cours
            $query_updateETL = "UPDATE ".TABLE_DATA_ETL." SET datetime_end='".$DatetimeEnd_Last."'
                                WHERE id=".$last_ETL['id'];
            tep_db_query($sql_link,$query_updateETL);   
            
            // ---------------
            // Insérer une nouvelle ligne dans TABLE_DATA_ETL pour obtenir le nouvel id_etl    
            $query_newETL = "INSERT INTO ".TABLE_DATA_ETL." (id_station, datetime_first)
                            VALUES (".$st_id.", '".$DatetimeFirst_Input."')";
            tep_db_query($sql_link,$query_newETL); 

            $sql_new_ETL = "SELECT id, datetime_first, datetime_end
                            FROM ".TABLE_DATA_ETL." WHERE id_station=".$st_id." ORDER BY id DESC LIMIT 1";
            $new_ETL_query = tep_db_query($sql_link,$sql_new_ETL);
            $new_ETL = tep_db_fetch_array($new_ETL_query);

            // ---------------    
            // Engregistrement des valeurs pour le nouvel ETL
            $query_dataETL = "INSERT INTO ".TABLE_DATA_ETL_DATA." (id_etl, hauteur, debit, code_qualite)
                            SELECT ".$new_ETL['id'].", hauteur, debit, code_qualite
                            FROM ".TABLE_DATA_ETL_DATA."
                            WHERE id_etl = ".$last_ETL['id']." ORDER BY hauteur ASC";            
            tep_db_query($sql_link,$query_dataETL); 

            $message_add_ETL = htmlaccent('Un nouvel ETL a bien été créé pour la station : '.$code_station.' - '.$nom_station);
        }
        else
        {
            $message_add_ETL = htmlaccent('!!! Erreur !!! - La date de début du nouvel ETL doit être postérieure à celle du dernier ETL en cours.');
        }

    }
    else
    {
        $message_add_ETL = htmlaccent('!!! Erreur !!! - Un premier ETL doit déjà exister pour la station hydrométrique.');
    }
} 
else
{
    $message_add_ETL = htmlaccent('La date d\'initialisation du nouvel ETL n\'est pas au format attendu - jj/mm/aaaa hh:mm');
}

?>
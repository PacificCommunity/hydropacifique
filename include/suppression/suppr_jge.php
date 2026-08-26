<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Script de suppression de Jaugeage
*/

$del = (int)$_GET['del'];

if ($del > 0) // Vérifiez que l'ID est valide
{ 
    // Requête pour vérifier si le jaugeage existe
    $sql_del_jge = "SELECT DISTINCT jge.id, s.code_station, s.nom_station, jge.datetime 
                    FROM ".TABLE_DATA_JGE." jge
                    JOIN ".TABLE_STATION." s ON jge.id_station=s.id_station
                    WHERE id=".$del;
    $del_jge_query = tep_db_query($sql_link,$sql_del_jge);
    $del_jge = tep_db_fetch_array($del_jge_query);

    // Si le jaugeage existe, on procède à la suppression
    if(isset($del_jge['id']))
    {
        tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JGE." WHERE id=".$del);
        
        $sql_del_bras = "SELECT DISTINCT id, id_jge
                    FROM ".TABLE_DATA_JGE_BRAS."
                    WHERE id_jge=".$del;
        $del_bras_query = tep_db_query($sql_link,$sql_del_bras);
        while ($bras_tab = tep_db_fetch_array($del_bras_query))
        {
            tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JGE_BRAS." WHERE id_jge=".$del);
            tep_db_query($sql_link,"DELETE FROM ".TABLE_DATA_JGE_PTS." WHERE id_bras=".$bras_tab['id']);
        }

        $message_info = htmlaccent('Le jaugeage du '.dateus_fr($del_jge['datetime']).' a bien été supprimée.');
        $message_info .= '<br><br>';
        $message_info .= htmlaccent('Station : '.($del_jge['code_station']).' - '.$del_jge['nom_station']);
    }
    else
    {
        $message_info = htmlaccent('Cette mesure de débit n\'existe pas, elle ne peut être supprimée');
    }
} 
else 
{
    // Si l'ID fourni est invalide
    $message_info = htmlaccent('L\'identifiant du jaugeage est invalide.');
}

?>

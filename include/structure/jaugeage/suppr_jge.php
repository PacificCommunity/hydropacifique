<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Gauging record deletion handler
- Deletes a gauging (JGE) record along with all its arms and measurement points
----------------------------------------
*/

$del = (int)$_GET['del'];

if ($del > 0)
{
    // Check the gauging record exists before deleting
    $del_jge_query = tep_db_query($sql_link,
        "SELECT DISTINCT jge.id, s.code_station, s.nom_station, jge.datetime
         FROM " . TABLE_DATA_JGE . " jge
         JOIN " . TABLE_STATION  . " s ON jge.id_station = s.id_station
         WHERE jge.id = " . $del);
    $del_jge = tep_db_fetch_array($del_jge_query);

    if (isset($del_jge['id']))
    {
        // Delete the gauging header
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE . " WHERE id = " . $del);

        // Delete all arms and their measurement points
        $del_bras_query = tep_db_query($sql_link,
            "SELECT DISTINCT id, id_jge FROM " . TABLE_DATA_JGE_BRAS . " WHERE id_jge = " . $del);

        while ($bras_tab = tep_db_fetch_array($del_bras_query))
        {
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_BRAS . " WHERE id_jge = "  . $del);
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_PTS  . " WHERE id_bras = " . $bras_tab['id']);
        }

        $message_info  = sprintf(TEXT_JGE_DEL_SUCCESS, dateus_fr($del_jge['datetime']));
        $message_info .= '<br><br>';
        $message_info .= TEXT_JGE_DEL_STATION . $del_jge['code_station'] . ' - ' . $del_jge['nom_station'];
    }
    else
    {
        $message_info = TEXT_JGE_DEL_NOT_FOUND;
    }
}
else
{
    $message_info = TEXT_JGE_DEL_INVALID;
}
?>

<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Gauging arm deletion handler
- Deletes a single arm (bras) and its measurement points
- Updates the arm count on the parent gauging record
----------------------------------------
*/

$del_bras = false;
$db       = (int)$_GET['db'];

if ($db > 0)
{
    // Check the arm exists before deleting
    $del_bras_query = tep_db_query($sql_link,
        "SELECT DISTINCT id FROM " . TABLE_DATA_JGE_BRAS . " WHERE id = " . $db);
    $del_bras = tep_db_fetch_array($del_bras_query);

    if (isset($del_bras['id']))
    {
        // Delete measurement points (by id_bras) and the arm record
        // NOTE: points must be matched on id_bras (the arm they belong to),
        // not on id — using WHERE id left the arm's points orphaned in the DB.
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_PTS  . " WHERE id_bras = " . $db);
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_BRAS . " WHERE id = "      . $db);

        // Decrement arm count on the parent gauging record (floor at 0)
        tep_db_query($sql_link,
            "UPDATE " . TABLE_DATA_JGE . " jge
             SET nb_bras = CASE
                               WHEN nb_bras > 0 THEN nb_bras - 1
                               ELSE 0
                           END
             WHERE id = " . $ref_id);

        $del_bras     = true;
        $message_info = TEXT_JGE_BRAS_DEL_SUCCESS;
    }
    else
    {
        $message_info = TEXT_JGE_BRAS_DEL_NOT_FOUND;
    }
}
else
{
    $message_info = TEXT_JGE_BRAS_DEL_INVALID;
}
?>
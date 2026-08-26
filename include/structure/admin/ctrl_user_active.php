<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Bulk user active/inactive toggle — included by list_users.php on POST.
Iterates over all non-admin users and sets active=1 or active=0 based
on whether the matching checkbox was submitted.
----------------------------------------
*/

$action        = true;
$action_result = true;
$message_action = '';

$user_query = tep_db_query($sql_link,
    "SELECT DISTINCT id FROM " . TABLE_USER . " WHERE admin = 0 ORDER BY login");
while ($user = tep_db_fetch_array($user_query))
{
    $active = isset($_POST['active_' . $user['id']]) ? 1 : 0;
    tep_db_query($sql_link,
        "UPDATE " . TABLE_USER . " SET active='" . $active . "' WHERE id=" . $user['id']);
}

$message_action = TEXT_US_ACTIVE_MSG_OK;

// Log the action
$today_us   = date('Y-m-d H:i:s');
$type_action = 31;
$info_action = "User access settings updated";
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
     VALUES (" . $id_user . ",'" . $type_action . "','" . $info_action . "','" . $today_us . "')");

<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
File    : suppr_user.php
Purpose : User deletion — included by list_users.php on ?del=<id>.

Rules:
  - A user with logged actions (nb_log > 0) cannot be deleted;
    only their active flag can be toggled to 0 instead.
  - Only non-admin accounts can be deleted (admin = 0 guard).
  - Deletion cascades to TABLE_USER_ACCES.
  - Every successful deletion is logged to TABLE_ACTIONS (type_action = 31).

Security:
  - $del_id is validated upstream in list_users.php via filter_var()
    and cast to (int) here as a second line of defence.
  - All dynamic values inserted into SQL are escaped or cast.
----------------------------------------
*/

$action        = true;
$action_result = false;
$message_action = '';

// $del_id was already validated upstream (filter_var FILTER_VALIDATE_INT).
// Cast again defensively — this file may be included from other contexts.
$del_id = (int) $_GET['del'];

// $id_user and $info_user are expected to be set by application_top.php
$id_user   = (int)    ($id_user   ?? 0);
$info_user = (string) ($info_user ?? '');


// -----------------------------------------------
// Fetch the target user — non-admin accounts only

$del_query = tep_db_query(
    $sql_link,
    "SELECT id, login, nom, prenom, nb_log
     FROM "  . TABLE_USER . "
     WHERE id    = " . $del_id . "
     AND   admin = 0"          // Guard: never delete an admin account
);

$del_a = tep_db_fetch_array($del_query);


// -----------------------------------------------
// Process deletion or report errors

if (!empty($del_a['id']))
{
    $login_del  = html_entity_decode($del_a['login']);
    $nom_del    = html_entity_decode($del_a['nom']);
    $prenom_del = html_entity_decode($del_a['prenom']);

    if ((int) $del_a['nb_log'] > 0)
    {
        // User has existing action logs — deletion is not allowed.
        // The admin should deactivate the account instead (active = 0).
        $message_action = TEXT_US_DEL_ERR_HAS_LOGS
                        . "<br><br>"
                        . TEXT_US_DEL_ERR_HAS_LOGS2;
    }
    else
    {
        // -----------------------------------------------
        // Delete the user and their access rights

        tep_db_query(
            $sql_link,
            "DELETE FROM " . TABLE_USER       . " WHERE id = " . $del_id
        );

        tep_db_query(
            $sql_link,
            "DELETE FROM " . TABLE_USER_ACCES . " WHERE id = " . $del_id
        );

        $action_result  = true;
        $message_action = sprintf(TEXT_US_DEL_OK, $login_del);

        // -----------------------------------------------
        // Log the deletion to TABLE_ACTIONS (type_action = 31)
        // Use the application timezone set upstream via date_default_timezone_set()

        $now         = new DateTime('now', new DateTimeZone($timezone_php));
        $info_action = mysqli_real_escape_string(
            $sql_link,
            "User deleted: " . $login_del
            . " (" . $prenom_del . " " . $nom_del . ")"
            . " - " . $info_user
        );

        tep_db_query(
            $sql_link,
            "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
             VALUES (
                 "   . $id_user . ",
                 31,
                 '"  . $info_action . "',
                 '"  . $now->format('Y-m-d H:i:s') . "'
             )"
        );
    }
}
else
{
    // No matching non-admin user found for this id
    $message_action = TEXT_US_DEL_ERR_NOT_FOUND;
}
?>
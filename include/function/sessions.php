<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
File    : sessions.php
Purpose : Session lifecycle management — open, validate, refresh, and clean up
          admin sessions stored in TABLE_SESSION.

Functions:
  - suiviSession()       Check and validate the current session
  - regenerer_id()       Refresh the session ID and update last_access
  - tep_session_end()    Close the session gracefully
  - getAdminInfo()       Fetch current session data joined with user data
  - controleSessionInfo() Validate session integrity and timeout
  - double_connexion()   Detect concurrent logins for the same user
  - clean_connexion()    Purge all expired sessions from the database
----------------------------------------
*/


/**
 * Validates the current admin session.
 *
 * Retrieves session data and runs integrity checks.
 * Logs the IP as suspicious if the session fails validation.
 *
 * @param  resource $sql_link  Database connection handle
 * @return bool                true if the session is valid, false otherwise
 */
function suiviSession($sql_link)
{
    $session_data = getAdminInfo($sql_link);

    if (tep_not_null($session_data))
    {
        if (controleSessionInfo($sql_link, $session_data))
        {
            return true;
        }

        // Session check failed — log the IP as suspicious
        save_ip_suspect($sql_link, 'admin_session_modifies');
        return false;
    }

    return false;
}


/**
 * Refreshes the session ID in the database and updates last_access.
 *
 * Note: session_regenerate_id() is intentionally left disabled here.
 * Re-enable it if the application can reliably propagate the new SID
 * across all active requests without race conditions.
 *
 * @param  resource $sql_link  Database connection handle
 * @return void
 */
function regenerer_id($sql_link)
{
    $session_data = getAdminInfo($sql_link);

    // Uncomment to also regenerate the PHP session ID:
    // session_regenerate_id(true);

    tep_db_query(
        $sql_link,
        "UPDATE " . TABLE_SESSION . "
         SET    sid         = '" . session_id() . "',
                last_access = "  . time() . "
         WHERE  id          = "  . (int) $session_data['id']
    );
}


/**
 * Closes the current session gracefully.
 *
 * Writes session data and releases the session lock.
 *
 * @return bool  Result of session_write_close()
 */
function tep_session_end()
{
    return session_write_close();
}


/**
 * Fetches the current session record joined with its user account.
 *
 * Matches on the active PHP session ID and requires the user account
 * to be active (active = 1). Returns false if no matching session exists.
 *
 * @param  resource    $sql_link  Database connection handle
 * @return array|false            Session + user data, or false if not found
 */
function getAdminInfo($sql_link)
{
    $sql = "SELECT s.id, s.admin_id, s.last_access, s.ip, s.browser,
                   a.id_service, a.nom, a.prenom, a.info, a.email, a.lang, a.active, a.id_statut
            FROM "  . TABLE_SESSION . " s
            JOIN "  . TABLE_USER    . " a ON s.admin_id = a.id
            WHERE   a.active = 1
            AND     s.sid    = '" . session_id() . "'";

    return tep_db_fetch_array(tep_db_query($sql_link, $sql));
}


/**
 * Validates session integrity and checks for timeout.
 *
 * Invalidates the session (sid = '') and returns false if:
 *   - Required fields (last_access, admin_id) are missing
 *   - The session has exceeded SESSION_TIMEOUT seconds of inactivity
 *
 * Note: IP and browser checks are intentionally disabled to avoid
 * false positives caused by proxy rotation or browser updates.
 *
 * Note: last_access must be stored as a Unix timestamp (integer).
 *       If the storage format changes, the timeout comparison will
 *       break silently.
 *
 * @param  resource $sql_link  Database connection handle
 * @param  array    $data      Session record from getAdminInfo()
 * @return bool                true if the session is valid, false otherwise
 */
function controleSessionInfo($sql_link, $data)
{
    // Required fields must be present
    if (!tep_not_null($data['last_access']) || !tep_not_null($data['admin_id']))
    {
        tep_db_query(
            $sql_link,
            "UPDATE " . TABLE_SESSION . "
             SET   sid = ''
             WHERE id  = " . (int) $data['id']
        );
        return false;
    }

    /*
     * IP and browser checks are disabled — see function docblock.
     *
     * if (getIP() != $data['ip']) { ... }
     * if (getUser_agent() != $data['browser']) { ... }
     */

    // Check inactivity timeout
    if (time() - $data['last_access'] >= SESSION_TIMEOUT)
    {
        tep_db_query(
            $sql_link,
            "UPDATE " . TABLE_SESSION . "
             SET   sid = ''
             WHERE id  = " . (int) $data['id']
        );
        return false;
    }

    return true;
}


/**
 * Checks whether the given user already has an active session.
 *
 * A session is considered active when:
 *   - its sid is not empty (not explicitly invalidated)
 *   - its last_access timestamp is within the SESSION_TIMEOUT window
 *
 * Note: last_access must be stored as a Unix timestamp (integer).
 *
 * @param  resource $sql_link  Database connection handle
 * @param  int      $id        User ID to check
 * @param  string   $nom       Username — appended to the suspicious IP log entry
 * @return bool                true if an active session exists, false otherwise
 */
function double_connexion($sql_link, $id, $nom)
{
    $sql = "SELECT id FROM " . TABLE_SESSION . "
            WHERE  admin_id    =  " . (int) $id . "
            AND    sid         <> ''
            AND    (" . time() . " - last_access) < " . SESSION_TIMEOUT;

    $result = tep_db_fetch_array(tep_db_query($sql_link, $sql));

    if (!empty($result['id']))
    {
        // Log the IP — possible session hijack or concurrent login attempt
        save_ip_suspect($sql_link, 'double_connect_' . $nom);
        return true;
    }

    return false;
}


/**
 * Purges all expired sessions from the database in a single query.
 *
 * A session is considered expired when its last_access timestamp
 * is older than SESSION_TIMEOUT seconds ago.
 *
 * Note: last_access must be stored as a Unix timestamp (integer).
 *
 * @param  resource $sql_link  Database connection handle
 * @return void
 */
function clean_connexion($sql_link)
{
    // Delete all sessions whose last activity exceeds the timeout — single query, no loop needed
    tep_db_query(
        $sql_link,
        "DELETE FROM " . TABLE_SESSION . "
         WHERE  sid        <> ''
         AND    last_access <= " . (time() - SESSION_TIMEOUT)
    );
}
?>
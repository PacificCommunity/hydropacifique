<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Top header bar (modernized)
- Original PNG logo preserved on the left
- Centered server/nomad name
- Last update + version info card (server only)
- User avatar with initials + name + role
- Inline SVG action icons (admin, logout)
----------------------------------------
*/

$date_update = '';
$color_top   = $color_service;
if (HP_VERSION == 'Nomad') { $color_top = "#3282B8"; }


// -----------------------------------------------
// Compute user initials for avatar
$initial_p = mb_substr(trim($prenom_user), 0, 1);
$initial_n = mb_substr(trim($nom_user),    0, 1);
$initials  = mb_strtoupper($initial_p . $initial_n);


echo "<div id='bando' style='background-color:" . $color_top . ";'>";

    // -----------------------------------------------
    // Logo (preserved PNG)
    echo "<a href='index.php' title='" . TEXT_TOP_FIRST . "'>";
        echo "<img src='" . DIR_WS_IMG . "header_logo_header.png' style='height:50px;'>";
    echo "</a>";


    // -----------------------------------------------
    // Centered title
    echo "<div id='top_center'>";
        $text_head = HP_SERVEUR;
        if (HP_VERSION == 'Nomad') { $text_head = HP_NOMAD; }
        echo "<span style='font-weight:bold;'>" . $text_head . "</span>";
    echo "</div>";


    // -----------------------------------------------
    // Right-side navigation block
    echo "<div id='nav_icon'>";


        // ---- Last update + Version info card ----
        if (HP_VERSION == 'Serveur')
        {
            $sql_update_data = "SELECT dateheure
                                FROM " . TABLE_DATA_ALL . "
                                ORDER BY dateheure DESC
                                LIMIT 1";
            $update_data_query = tep_db_query($sql_link, $sql_update_data);
            $update_data       = tep_db_fetch_array($update_data_query);
            $date_update       = '-';
            if ($update_data && !empty($update_data['dateheure']))
            {
                $date_obj    = new DateTime($update_data['dateheure']);
                $date_update = $date_obj->format('d/m/Y');
            }

            echo "<div class='bando-info'>";
                // Calendar icon
                echo "<svg class='info-ico' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                   . "<rect x='3' y='4' width='18' height='18' rx='2' ry='2'/>"
                   . "<line x1='16' y1='2' x2='16' y2='6'/>"
                   . "<line x1='8' y1='2' x2='8' y2='6'/>"
                   . "<line x1='3' y1='10' x2='21' y2='10'/>"
                   . "</svg>";

                echo "<div class='info-text'>";
                    echo "<div><strong>" . TEXT_TOP_DATE_DATA_UPDATE . "</strong> " . $date_update . "</div>";
                    echo "<div><strong>" . TEXT_TOP_VERSION_HP       . "</strong> " . VERSION_HP . " <span class='sep'>&middot;</span> " . DATE_VERSION_HP . "</div>";
                echo "</div>";
            echo "</div>";
        }
        else
        {
            // Nomad : version only
            echo "<div class='bando-info'>";
                echo "<svg class='info-ico' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                   . "<circle cx='12' cy='12' r='10'/>"
                   . "<polyline points='12 6 12 12 16 14'/>"
                   . "</svg>";

                echo "<div class='info-text'>";
                    echo "<div><strong>" . TEXT_TOP_VERSION_HP . "</strong> " . VERSION_HP . " <span class='sep'>&middot;</span> " . DATE_VERSION_HP . "</div>";
                echo "</div>";
            echo "</div>";
        }


        // ---- User block : avatar + name + role ----
        //
        // Clickable: opens the personal-account popup (block_my_account.php)
        // so the user can edit their own profile in-place. The actual
        // toggle wiring lives in that block; here we only need the
        // onClick + cursor hint + accessibility role/keyboard handler.
        echo "<div class='bando-user'"
           . " onclick=\"if(window.openMyAccount){openMyAccount();}\""
           . " style='cursor:pointer;'"
           . " title='" . TEXT_MYACC_TITLE . "'"
           . " role='button' tabindex='0'"
           . " onkeydown=\"if((event.key==='Enter'||event.key===' ')&&window.openMyAccount){event.preventDefault();openMyAccount();}\">";
            echo "<div class='avatar' aria-hidden='true'>" . $initials . "</div>";
            echo "<div class='user-text'>";
                echo "<div class='name'>" . $prenom_user . " " . $nom_user . "</div>";
                echo "<div class='role'>" . $info_user . "</div>";
            echo "</div>";
        echo "</div>";


        // ---- Action icons ----
        echo "<div class='bando-icons'>";

            // Admin / settings (only if user has config rights)
            if ($config == 1)
            {
                echo "<a href='gestion.php' title='" . TEXT_TOP_ADMIN . "' class='gestion'>";
                    echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                       . "<circle cx='12' cy='12' r='3'/>"
                       . "<path d='M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z'/>"
                       . "</svg>";
                echo "</a>";
            }

            // Password change has been moved to the personal-account
            // popup (block_my_account.php). The dedicated icon used to
            // live here is no longer needed.

            // Logout
            echo "<a href='logout.php' title='" . TEXT_TOP_CLOSE . "' class='close logout'>";
                echo "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>"
                   . "<path d='M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4'/>"
                   . "<polyline points='16 17 21 12 16 7'/>"
                   . "<line x1='21' y1='12' x2='9' y2='12'/>"
                   . "</svg>";
            echo "</a>";

        echo "</div>";


    echo "</div>"; // #nav_icon

echo "</div>"; // #bando


// -----------------------------------------------
// Personal-account popup
//
// Included right after the header markup so the user block above can
// reference window.openMyAccount() immediately. The block itself is
// hidden by default (display:none); it only renders on demand.
require(DIR_WS_ADMIN . 'block_my_account.php');

?>
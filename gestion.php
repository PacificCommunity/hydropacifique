<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Admin home page — entry point for user management and general
application configuration.
----------------------------------------
*/

require('include/application_top.php');

$message_suprr_tech = '';
$row = 0;

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

require(DIR_WS_STRUCTURE . 'header.php');
include(DIR_WS_BOX       . 'nav_accueil.php');

echo "<div id='contour_general'>";
    echo "<div id='contenu_centre'>";

        echo "<h1><span>" . TEXT_US_APP_SETTINGS . "</span></h1>";

        echo "<div id='box_result' style='width:auto;margin:0;padding: 20px;'>\n";
            echo "<div id='contenu_box2' style='width:auto;'>";

                if ($config == 1)
                {
                    // ---- Users section ----
                    echo "<div id='gestion_list'>\n";
                        echo "<img src='" . DIR_WS_IMG_ICO . "users.png'>";
                        echo "<p>" . TEXT_US_MENU_USERS . "</p>";
                        echo "<ul>";
                            echo "<li><a href='list_users.php'>" . TEXT_US_MENU_USER_RIGHTS . "</a></li>";
                            echo "<li><a href='modif_user.php'>" . TEXT_US_MENU_USER_NEW   . "</a></li>";
                        echo "</ul>";
                    echo "</div>";

                    // ---- Configuration section ----
                    echo "<div id='gestion_list'>\n";
                        echo "<img src='" . DIR_WS_IMG_ICO . "param.png'>";
                        echo "<p>" . TEXT_US_MENU_CONFIG . "</p>";
                        echo "<ul>";
                            echo "<li><a href='gestion_plateform.php' target='_blank'>" . TEXT_US_MENU_PLATEFORM . "</a></li>";
                            echo "<li><a href='gestion_services.php' target='_blank'>" . TEXT_US_MENU_SERVICE . "</a></li>";
                            echo "<li><a href='gestion_type.php' target='_blank'>" . TEXT_US_MENU_TYPE_MESURE . "</a></li>";
                        echo "</ul>";
                    echo "</div>";
                }

            echo "</div>";
        echo "</div>";

    echo "</div>";
echo "</div>";

require('include/application_bottom.php');
echo "</body>";
echo "</html>";
?>

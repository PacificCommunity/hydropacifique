<?php
/*
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
User rights tab — included by modif_user.php.
Shows checkboxes for data, settings, and config access levels.
----------------------------------------
*/

echo "<div id='onglet_contenu'>\n";

    echo "<div id='gestion_droit'>\n";

        echo "<h3>" . TEXT_US_F2_RIGHTS_TITLE . "</h3>";

        echo "<table>";

            // Data management
            echo "<tr>";
                $check = ($gestion_data_u > 0) ? 'checked' : '';
                echo "<td><input type='checkbox' name='gestion_data' id='gestion_data' " . $check . "></td>";
                echo "<td>" . TEXT_US_F2_RIGHT_DATA . "</td>";
            echo "</tr>";

            // Data management — Expert
            echo "<tr>";
                $check = ($gestion_data_u > 1) ? 'checked' : '';
                echo "<td><input type='checkbox' name='gestion_data_expert' id='gestion_data_expert' " . $check . "></td>";
                echo "<td>" . TEXT_US_F2_RIGHT_DATA_EXPERT . "</td>";
            echo "</tr>";

            // Settings
            echo "<tr>";
                $check = ($parametre_u > 0) ? 'checked' : '';
                echo "<td><input type='checkbox' name='parametre' id='parametre' " . $check . "></td>";
                echo "<td>" . TEXT_US_F2_RIGHT_PARAM . "</td>";
            echo "</tr>";

            // Application configuration
            echo "<tr>";
                $check = ($config_u > 0) ? 'checked' : '';
                echo "<td><input type='checkbox' name='config' id='config' " . $check . "></td>";
                echo "<td>" . TEXT_US_F2_RIGHT_CONFIG . "</td>";
            echo "</tr>";

        echo "</table>";

    echo "<hr>";
    echo "</div>";

echo "<hr>\n";
echo "</div>\n";
?>

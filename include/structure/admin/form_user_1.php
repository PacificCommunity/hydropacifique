<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
User information tab — included by modif_user.php.
Shows login, email, name, additional info, station manager,
language preference, and (in edit mode) password generation
and welcome mail features.
----------------------------------------
*/

echo "<div id='onglet_contenu' style='padding:0 20px;padding-top:10px;overflow-y:auto;max-height:calc(100vh - 200px);'>\n";

    echo "<div style='float:left;width:700px;margin-bottom:20px;padding:15px 0;
                      border:1px solid #000;border-radius:4px;background-color:#fff;
                      box-shadow:5px 20px 38px -27px #232323;'>";

        echo "<div style='float:left;padding-left:15px;'>";

            echo "<table>";

                // ---- Login ----
                echo "<tr>";
                    echo "<td style='width:140px;'><h2 style='color:#930000;'>" . TEXT_US_F1_LOGIN_LABEL . "</h2></td>";
                    $val = $modif ? $login : '';
                    echo "<td>";
                        echo "<input name='login' id='login' value='" . $val . "' style='width:250px;' type='text'>";
                    echo "</td>";
                    echo "<td>";
                        echo "<p style='width:250px;margin:0;text-align:left;font-size:12px;font-weight:bold;color:#616;'>" . TEXT_US_F1_LOGIN_HINT . "</p>";
                    echo "</td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr>";

            echo "<table>";

                // ---- Email address ----
                echo "<tr>";
                    echo "<td><h2 style='color:#930000;'>" . TEXT_US_F1_MAIL_LABEL . "</h2></td>";
                    $val = $modif ? $email : '';
                    echo "<td><input name='email' id='email' value='" . $val . "' style='width:250px;' type='text'></td>";
                echo "</tr>";

                // ---- Last name ----
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_US_F1_NOM_LABEL . "</h2></td>";
                    $val = $modif ? $nom : '';
                    echo "<td><input name='nom' id='nom' value='" . $val . "' style='width:250px;' type='text'></td>";
                echo "</tr>";

                // ---- First name ----
                echo "<tr>";
                    echo "<td><h2>" . TEXT_US_F1_PRENOM_LABEL . "</h2></td>";
                    $val = $modif ? $prenom : '';
                    echo "<td><input name='prenom' id='prenom' value='" . $val . "' style='width:250px;' type='text'></td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr>";

            echo "<table>";

                // ---- Additional info ----
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_US_F1_INFO_LABEL . "</h2></td>";
                    $val = $modif ? $info : '';
                    echo "<td>";
                        echo "<textarea name='info_user' id='info_user'"
                                . " style='width:250px;height:50px;'>" . $val . "</textarea>\n";
                    echo "</td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr>";

            echo "<table>";

                // ---- Station manager ----
                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_US_LIST_COL_FROM . "</h2></td>";
                    $val = $modif ? $id_service : '';
                    echo "<td>";
                        echo "<select name='select_idService' id='select_idService' style='width:262px;'>";
                        foreach ($fromData_array as $id_fromData => $data)
                        {
                            $selected = ($id_fromData == $id_service) ? 'selected' : '';
                            echo "<option value='" . $id_fromData . "' " . $selected . ">"
                                    . $data['name'] . " - " . $data['description'] . "</option>";
                        }
                        echo "</select>\n";
                    echo "</td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr>";

            echo "<table>";

                // ---- Language ----
                // On edit, use the user's stored language.
                // On creation, default to the platform language (LANGUAGE_TERRITOIRE).
                // $languages_array is defined globally in include/config.php.
                $lang_selected = $modif ? ($lang ?? LANGUAGE_TERRITOIRE) : LANGUAGE_TERRITOIRE;

                echo "<tr>";
                    echo "<td style='width:140px;'><h2>" . TEXT_US_F1_LANG_LABEL . "</h2></td>";
                    echo "<td>";
                        echo "<select name='lang' id='lang' style='width:262px;'>";
                        foreach ($languages_array as $code => $label)
                        {
                            $selected = ($code == $lang_selected) ? 'selected' : '';
                            echo "<option value='" . $code . "' " . $selected . ">" . $label . "</option>";
                        }
                        echo "</select>\n";
                    echo "</td>";
                echo "</tr>";

            echo "</table>";

            echo "<hr>";


            if ($modif)
            {
                echo "<table>";

                    // ---- GENERATE PASSWORD ----
                    echo "<tr>";
                        echo "<td style='width:140px;'>";

                            echo "<img src='" . DIR_WS_IMG_ICO . "reload.png' style='float:left;width:20px;cursor:pointer;'>";
                            echo "<h2 onclick='pass_reload(" . $ref_id . "," . $id_user . ");' style='float:left;width:110px;margin-left:10px;cursor:pointer;'>"
                                    . TEXT_US_F1_PASS_GENERATE;
                            echo "</h2>\n";
                        echo "</td>";

                        echo "<td>";
                            echo "<input name='pass' id='pass' style='width:250px;' type='text' disabled='disabled'>";
                            echo "<p id='pass_info' style='display:none;color:#C33;'>" . TEXT_US_F1_PASS_COPY . "</p>";
                        echo "</td>";
                    echo "</tr>";

                echo "</table>";

                // ---- SEND WELCOME MAIL ----
                // Only visible if the stored email passes basic format validation
                if (filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                    echo "<hr>";

                    echo "<table>";
                        echo "<tr>";

                            echo "<td style='width:140px;'>";
                            echo "</td>";

                            echo "<td style='width:250px;'>";
                                echo "<img src='" . DIR_WS_IMG_ICO . "mail.png' id='ico-mail' style='float:left;width:40px;cursor:pointer;'>";
                                echo "<img src='" . DIR_WS_IMG . "wait.gif' id='ico-wait' style='float:left;width:40px;display:none;'>";
                                echo "<h2 onclick='sendWelcomeMail(" . $ref_id . ");'"
                                        . " style='float:left;width:190px;margin-top:14px;margin-left:10px;cursor:pointer;'>"
                                        . TEXT_US_F1_SEND_MAIL_LABEL . "</h2>";

                                echo "<hr>";
                                echo "<p id='welcome_mail_info' style='display:none;color:#C33;'></p>";
                            echo "</td>";
                        echo "</tr>";
                    echo "</table>";
                }
            }


        echo "</div>\n";

    echo "</div>\n";


echo "<hr>\n";
echo "</div>\n";

?>
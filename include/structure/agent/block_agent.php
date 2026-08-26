<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Agent record popup form — create or edit an agent
----------------------------------------
- Aligned on the standard popup convention :
  1st direct child of #cadre_view_2 is a <p> header with title + close X
  Body content is wrapped in #cadre_view_inner
- The hidden input #titre_fiche_agent is preserved (used by saveAgent /
  fillAgent JS callbacks elsewhere)
----------------------------------------
*/

$today      = date('d-m-Y');
$time       = date('H:i');
$today_time = date('d-m-Y H:i');
$day        = date('d');
$month      = date('m');
$year       = date('Y');

echo "<div id='box_agent' class='block_view'"
                . " style='position:absolute;width:900px;height:490px;top:20px;left:25%;background:none;'>\n";

    echo "<div id='cadre_view_2' style='padding:0;margin:0;'>\n";

        // ---- Standard popup header (matches the convention) ----
        // Title text is filled at runtime by JS (fillAgent / saveAgent etc.)
        echo "<p id='title_box_agent' style='margin:0;'>";
            echo "<span id='title_box_agent_text'></span>";
            echo "<span id='button_close'"
               . " onclick=\"document.getElementById('box_agent').style.display='none';\">X</span>";
        echo "</p>\n";


        // ---- Popup body ----
        echo "<div id='cadre_view_inner' style='padding:15px;'>\n";

            echo "<div id='cadre_limit'>";

                echo "<form id='formAgent' name='formAgent'>";

                    echo "<input type='hidden' name='id_agent_fiche' id='id_agent_fiche' value=''>";

                    // Title bar (hidden — display is now done in the teal popup header.
                    // The <input> below is kept hidden because its value is read by
                    // saveAgent / fillAgent / etc.)
                    echo "<table id='tab_titre_popup' cellspacing='0' style='display:none;'>";
                        echo "<tr><td class='titre'>";
                            echo "<p style='width:80%;margin:0;'>";
                                echo "<input name='titre_fiche_agent' id='titre_fiche_agent' value='' class='input_texte'
                                             style='width:100%;font-size:24px;font-weight:bold;' type='text' readonly>";
                            echo "</p>\n";
                        echo "</td></tr>";
                    echo "</table>";

                    // ---- Identity ----
                    echo "<div id='boxpopup' style=''>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p style='width:80px;'>" . TEXT_AGENT_NOM . "</p>\n";
                            echo "<input name='nom' id='nom' value='' class='input_texte' style='width:200px;' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p style='width:80px;'>" . TEXT_AGENT_NOM_MARITAL . "</p>\n";
                            echo "<input name='nom_marital' id='nom_marital' value='' class='input_texte' style='width:200px;' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p style='width:80px;'>" . TEXT_AGENT_PRENOM . "</p>\n";
                            echo "<input name='prenom' id='prenom' value='' class='input_texte' style='width:200px;' type='text'>";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";

                    // ---- Activity ----
                    echo "<div id='boxpopup'>\n";
                        echo "<h2>" . TEXT_AGENT_SECTION_ACTIVITE . "</h2>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_INSTITUTION . "</p>\n";
                            echo "<input name='raisonsociale' id='raisonsociale' value='' class='input_texte' style='width:250px;' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_FONCTION . "</p>\n";
                            echo "<input name='fonction' id='fonction' value='' class='input_texte' style='width:250px;' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_NUMINSCRIPTION . "</p>\n";
                            echo "<input name='numinscription' id='numinscription' value='' class='input_texte' type='text'>";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";

                    // ---- Contact details ----
                    echo "<div id='boxpopup'>\n";
                        echo "<h2>" . TEXT_AGENT_SECTION_COORDONNEES . "</h2>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_TEL . "</p>\n";
                            echo "<input name='tel' id='tel' value='' class='input_texte' type='text' style='width:80px;'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_MOBILE . "</p>\n";
                            echo "<input name='mobile' id='mobile' value='' class='input_texte' type='text' style='width:80px;'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_FAX . "</p>\n";
                            echo "<input name='fax' id='fax' value='' class='input_texte' type='text' style='width:80px;'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_EMAIL . "</p>\n";
                            echo "<input name='email' id='email' value='' class='input_texte_200' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_SITEWEB . "</p>\n";
                            echo "<input name='siteweb' id='siteweb' value='' class='input_texte_200' type='text'>";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";

                    // ---- Address ----
                    echo "<div id='boxpopup'>\n";
                        echo "<h2>" . TEXT_AGENT_SECTION_ADRESSE . "</h2>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_RUE . "</p>\n";
                            echo "<input name='adresse' id='adresse' value='' class='input_texte' type='text' style='width:250px;'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_LIEUDIT . "</p>\n";
                            echo "<input name='lieudit' id='lieudit' value='' class='input_texte' type='text'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_BP . "</p>\n";
                            echo "<input name='bp' id='bp' value='' class='input_texte' type='text' style='width:80px;'>";
                        echo "</div>\n";

                        echo "<hr>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_CODEPOSTAL . "</p>\n";
                            echo "<input name='codepostal' id='codepostal' value='' class='input_texte' type='text' style='width:80px;'>";
                        echo "</div>\n";

                        echo "<div id='boite_small'>\n";
                            echo "<p>" . TEXT_AGENT_COMMUNE . "</p>\n";
                            echo "<select name='select_commune' id='select_commune' style='width:140px;'>";
                                echo "<option value='0'>-</option>";
                                $sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune
                                                FROM " . TABLE_COMMUNE . " c, " . TABLE_REGION . " r
                                                WHERE c.id_region = r.id_region AND r.id_territoire = " . $territoire_id . "
                                                ORDER BY c.nom_commune";
                                $commune_query = tep_db_query($sql_link, $sql_commune);
                                while ($commune_list = tep_db_fetch_array($commune_query))
                                {
                                    echo "<option value='" . $commune_list['id_commune'] . "'>" . html_entity_decode($commune_list['nom_commune']) . "</option>";
                                }
                            echo "</select>";
                        echo "</div>\n";

                    echo "<hr>\n";
                    echo "</div>\n";

                    echo "<hr>";

                    // ---- Bottom bar: checkboxes + save/cancel ----
                    echo "<div id='popup_barredown'>\n";

                        echo "<div id='popup_nav' style='width:470px;'>\n";
                            echo "<div style='float:left;width:150px;margin-top:5px;'>";
                                echo "<p style='float:left;font-size:14px;font-weight:bold;text-align:center;padding-top:5px;'>" . TEXT_AGENT_CHECK_TERRAIN . "</p>";
                                echo "<input type='checkbox' name='check_terrain' id='check_terrain' style='float:left;width:20px;height:20px;margin-left:20px;'>";
                            echo "</div>";
                            echo "<div style='float:left;width:200px;margin-top:5px;margin-left:10px;'>";
                                echo "<p style='float:left;font-size:14px;font-weight:bold;text-align:center;padding-top:5px;'>" . TEXT_AGENT_CHECK_SERVICE . $service_hydro . "</p>";
                                echo "<input type='checkbox' name='check_service_hydro' id='check_service_hydro' style='float:left;width:20px;height:20px;margin-left:20px;'>";
                            echo "</div>";
                        echo "</div>";

                        echo "<div id='popup_nav' style='float:right;'>\n";
                            echo "<table id='stats_select' cellspacing='0' style='width:300px;'>";
                                echo "<tr>";
                                    echo "<td class='bold'>";
                                        echo "<input type='submit' class='button' id='save_agent' name='save_agent' value='" . TEXT_AGENT_BTN_SAVE . "' onclick='saveAgent(event);'/>";
                                    echo "</td>";
                                    echo "<td>&nbsp;</td>";
                                    echo "<td class='bold'>";
                                        echo "<input type='button' class='button_close' value='" . TEXT_AGENT_BTN_CANCEL . "' onclick=\"document.getElementById('box_agent').style.display='none';\"/>";
                                    echo "</td>";
                                echo "</tr>";
                            echo "</table>";
                        echo "</div>\n";

                    echo "</div>\n";

                echo "</form>";

            echo "</div>\n";

        echo "</div>\n"; // closes #cadre_view_inner

    echo "</div>\n";     // closes #cadre_view_2

echo "</div>\n";         // closes #box_agent
?>

<script type="text/javascript">

    var popup = document.getElementById('cadre_view');
    var box   = document.getElementById('box_agent');

    // Close on outside click
    document.addEventListener("click", function(event)
    {
        if (event.target !== popup && event.target === box) { box.style.display = "none"; }
    });

    // Close on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape") { box.style.display = "none"; }
    });

</script>
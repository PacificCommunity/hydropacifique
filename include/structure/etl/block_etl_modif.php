<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
ETL period-edit popup — included by modif_etl.php.
Lets the user choose an ETL and change its validity period.
----------------------------------------
*/

echo "<div id='box_elt_modif' class='block_view'"
   . " style='width:450px;height:300px;margin-left:35%;background:transparent;'>\n";

    echo "<div id='cadre_view_2' style='padding:0;background-color:#FBF9F1;'>\n";

        echo "<div>";
            echo "<p style='float:left;width:100%;height:30px;padding-left:8px;"
               . "font-size:18px;font-weight:bold;color:#fff;margin:0;background-color:#000;'>";
                echo TEXT_ET_MODIF_TITLE;
            echo "</p>\n";
        echo "</div>\n";

        echo "<div style='margin: 0 5%;margin-top:55px;'>";

            // ETL curve selector
            echo "<div style='width:100%;'>";
                echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_ET_POPUP_ETL_CURVE . "</p>\n";
                echo "<select name='modif_ref_etl' id='modif_ref_etl'"
                   . " style='width:90%;height:35px;font-size:12px;'"
                   . " onchange='modifUpdateFieldsDate(this.value)'>";
                echo "</select>";
            echo "</div>";

            // Validity period
            echo "<div style='width:100%;margin-top:25px;'>";

                // Start
                echo "<p style='font-size:14px;font-weight:bold;'>" . TEXT_ET_POPUP_PERIOD_START . "</p>\n";
                echo "<table style='font-size:12px;'>";
                    echo "<tr>";
                        echo "<td style='width:120px;'>" . TEXT_ET_POPUP_DATE_FMT . "</td>";
                        echo "<td style='width:120px;'>" . TEXT_ET_POPUP_TIME_FMT . "</td>";
                    echo "</tr>";
                    echo "<tr>";
                        echo "<td><input style='width:90px;height:20px;font-size:14px;' id='modif_date_debut_periode' type='text' value=''></td>";
                        echo "<td><input style='width:90px;height:20px;font-size:14px;' id='modif_heure_debut_periode' type='text' value=''></td>";
                    echo "</tr>";
                echo "</table>";

                // End
                echo "<p style='font-size:14px;font-weight:bold;margin-top:10px;'>" . TEXT_ET_POPUP_PERIOD_END . "</p>\n";
                echo "<table style='font-size:12px;'>";
                    echo "<tr>";
                        echo "<td style='width:120px;'>" . TEXT_ET_POPUP_DATE_FMT . "</td>";
                        echo "<td style='width:120px;'>" . TEXT_ET_POPUP_TIME_FMT . "</td>";
                    echo "</tr>";
                    echo "<tr>";
                        echo "<td><input style='width:90px;height:20px;font-size:14px;' id='modif_date_fin_periode' type='text' value=''></td>";
                        echo "<td><input style='width:90px;height:20px;font-size:14px;' id='modif_heure_fin_periode' type='text' value=''></td>";
                    echo "</tr>";
                echo "</table>";

            echo "</div>\n";

            echo "<div style='float:left;margin-top:25px;'>";
                echo "<input type='submit' style='float:left;width:120px;' class='button'"
                   . " name='modif_etl' id='modif_etl' value='" . TEXT_ET_BTN_MODIF . "'>";
                echo "<input type='button' style='float:left;width:120px;margin-left:30px;' class='button_close'"
                   . " value='Annuler'"
                   . " onclick=\"document.getElementById('box_elt_modif').style.display='none';\">";
            echo "</div>\n";

            echo "<hr>";

        echo "</div>\n";

    echo "</div>\n";

echo "</div>\n";
?>

<script>
    var box_elt_modif = document.getElementById('box_elt_modif');

    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") { box_elt_modif.style.display = "none"; }
    });

    modifDateDebut  = document.getElementById('modif_date_debut_periode');
    modifHeureDebut = document.getElementById('modif_heure_debut_periode');
    modifDateFin    = document.getElementById('modif_date_fin_periode');
    modifHeureFin   = document.getElementById('modif_heure_fin_periode');

    function modifUpdateFieldsDate(selectedValue)
    {
        if (!selectedValue) return;
        const idETL = selectedValue.split('_')[0];
        if (ETL_array[idETL]) {
            modifDateDebut.value  = ETL_array[idETL].datetime_first.split(' ')[0];
            modifHeureDebut.value = ETL_array[idETL].datetime_first.split(' ')[1];
            modifDateFin.value    = ETL_array[idETL].datetime_end.split(' ')[0];
            modifHeureFin.value   = ETL_array[idETL].datetime_end.split(' ')[1];
        } else {
            modifDateDebut.value = modifHeureDebut.value = modifDateFin.value = modifHeureFin.value = '';
        }
    }
</script>

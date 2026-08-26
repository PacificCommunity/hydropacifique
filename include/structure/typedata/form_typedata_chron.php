<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Time-series type tab — included by gestion_type_data.php
Renders a measurement-type filter dropdown and an empty container
#tab_datatypechron which is populated by affiche_typedata() via AJAX
(process_tab_typedata.php).
Defines:
  affiche_typedata(idTypeData) — loads the filtered time-series table
  delete_typedata(id_typedata) — deletes one type and refreshes both tabs
  selectTypeData()             — called on filter-dropdown change
----------------------------------------
*/

// -----------------------------------------------
// Query: active measurement types — used to populate the filter dropdown

$eq_type_array = [];

$eq_type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type
     FROM " . TABLE_EQ_TYPE . "
     WHERE active_eq_type = 1
     ORDER BY order_eq_type ASC");

while ($eq_type = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type['id_eq_type']] = $eq_type['nom_eq_type'];
}
?>

<div id='onglet_contenu' style='height:75vh;'>

    <div id='boite1' class='first'>

        <div id='' style='float:left;margin-bottom:5px;'>

            <p style='float:left;margin-right:20px;padding-top:5px;color:#000;font-size:14px;font-weight:bold;'>
                <?php echo TEXT_TD_FILTER_LABEL; ?>
            </p>

            <select name='chron_filter' id='chron_filter' style='float:left;width:150px;' onchange='selectTypeData()'>
                <option value='0'>-</option>
                <?php foreach ($eq_type_array as $key => $value) : ?>
                    <option value='<?php echo $key; ?>'><?php echo $value; ?></option>
                <?php endforeach; ?>
            </select>

        </div>

        <div id='tab_datatypechron' class='table-container' >
        </div>

    <hr>
    </div>

<hr>
</div>

<script>

    var tabDatatypechron = document.getElementById('tab_datatypechron'); // Container for AJAX-loaded table


    // -----------------------------------------------
    // affiche_typedata(idTypeData)
    // Fetches the time-series type table HTML from process_tab_typedata.php,
    // optionally filtered to the given measurement type (0 = all types).

    function affiche_typedata(idTypeData)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/typedata/process_tab_typedata.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_typedata'])
                {
                    tabDatatypechron.innerHTML = r['htmlcode'];
                }
                else
                {
                    contenuInfo.innerHTML     = r['message_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(JSON.stringify({ idTypeData: idTypeData }));
    }

    affiche_typedata(0); // Load all types on tab display


    // -----------------------------------------------
    // delete_typedata(id_typedata)
    // Sends an AJAX delete request for the given time-series type.
    // On completion, refreshes both the time-series and axis tabs and shows feedback.

    function delete_typedata(id_typeChron,id_typeData)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/typedata/process_deltypedata.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.style.border  = r['del_typedata']
                    ? '2px solid #09886d'
                    : '2px solid #930000';
                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.display = 'block';

                // Refresh both tabs regardless of success/error
                affiche_typedata(id_typeData);
                affiche_axe();
            }
        };

        xhr.send(JSON.stringify({ id_typeChron: id_typeChron }));
    }


    // -----------------------------------------------
    // selectTypeData()
    // Called when the measurement-type filter dropdown changes.

    function selectTypeData()
    {
        var idTypeDataSelect = document.getElementById('chron_filter').value;
        affiche_typedata(idTypeDataSelect);
    }

</script>

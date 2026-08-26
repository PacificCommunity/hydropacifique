<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Partie qui permet de générer une liste de couleur à sélectionner

----------------------------------------
*/
$colorGraph = colorList();

echo "<div class='color-dropdown' >";
    echo "<div id='selectedColor_".$index_color."' class='dropdown-selected' onclick='toggleDropdownColor(".$index_color.")' style='background-color:".$colorGraph[$index_color].";'></div>";
    echo "<div id='dropdownList_".$index_color."' class='dropdown-list'>";
        foreach ($colorGraph as $id => $color)
        {
            echo "<div class='dropdown-item' style='background-color:".$color."' onclick=\"selectColor('".$color."',".$index_color.")\"></div>";
        }
    echo "</div>";
echo "</div>";

echo "<input type='hidden' id='input_color_".$index_color."' value='".$colorGraph[$index_color]."' />\n"; 

?>

<script>

    function toggleDropdownColor(index) 
    {
        let dropdown = document.getElementById('dropdownList_'+index);
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    function selectColor(color,index) 
    {
        document.getElementById('selectedColor_'+index).style.backgroundColor = color;
        document.getElementById('dropdownList_'+index).style.display = 'none';
        document.getElementById('input_color_'+index).value = color;

        Plotly.restyle('plot_0', { 
            'marker.color': color,           // Change la couleur des barres
            'marker.line.color': color,      // Change la couleur du contour des barres
            'line.color': color              // Change la couleur des lignes
        }, [(index-1)]);  // Cibler la trace par son index
        
    }

</script>
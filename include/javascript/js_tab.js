/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions JS pour Remplir avoir des tableau dynamique
Ca ne fonctionne pas pour le moment
*/


// Fonction de tri du tableau en fonction de la colonne spécifiée
function sortTable(columnIndex) 
{
    var table = document.getElementById("table_tri");
    var rows = Array.from(table.querySelectorAll("tr")).slice(2); // Ignorer les deux premières lignes (en-têtes et espace)

    rows.sort(function(rowA, rowB) {
        var cellA = rowA.cells[columnIndex].textContent.trim();
        var cellB = rowB.cells[columnIndex].textContent.trim();

        // Comparaison des valeurs comme des chaînes de caractères
        return cellA.localeCompare(cellB, undefined, { numeric: true, sensitivity: "base" });
    });

    // Rafraîchir le tableau
    rows.forEach(function(row) {
        table.appendChild(row);
    });
}
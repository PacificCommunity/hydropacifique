 /*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonction pour la gestion des onglets
*/



function ChangeOnglet_2(active, nombre, tab_prefix, contenu_prefix) 
{ 
    for (var i = 1; i <= nombre; i++) {
        var contenu = document.getElementById(contenu_prefix + i);
        var onglet = document.getElementById(tab_prefix + i);

        if (contenu) {
            contenu.style.display = 'none';
        }

        if (onglet) {
            onglet.classList.remove('actif');
        }
    }

    var contenuActif = document.getElementById(contenu_prefix + active);
    var ongletActif = document.getElementById(tab_prefix + active);

    if (contenuActif) {
        contenuActif.style.display = 'block';
    }

    if (ongletActif) {
        ongletActif.classList.add('actif');
    }
}



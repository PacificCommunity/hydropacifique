<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Block de fiche RA
----------------------------------------
*/
$day = date('d');
$month = date('m');
$year = date('Y');
$display_box = '';

// Affichage du bloc principal
echo "<div id='box_ra' class='block_view' style='height:100%;'></div>";
?>

<script>
    
    // Récupération des éléments du DOM
    var popup = document.getElementById('cadre_view');
    var contenuInfo = document.getElementById('contenu_info'); // popup d'affichage d'info
    var boxRa = document.getElementById('box_ra');
    var boxRaProfil = document.getElementById('box_ra_piezoprofil');

    // Gestion des clics pour fermer les popups
    document.addEventListener("click", function(event) {
        // Re-fetch boxRaProfil at click time — it's created by loadRA()
        // (response of process_ra_piezo_affiche.php) and didn't exist
        // yet when this script first ran.
        var boxRaProfilNow = document.getElementById('box_ra_piezoprofil');

        // Fermeture via le bouton de fermeture (#button_close).
        // Note : la popup `box_ra_piezoprofil` a son propre bouton
        // #button_close avec un onclick='...' inline qui se ferme
        // elle-même. Ici on ne ferme `boxRa` (la fiche RA principale)
        // QUE si le bouton cliqué appartient à boxRa, pas à la popup
        // profil qui se superpose — sinon cliquer le X du profil
        // fermerait la fiche en dessous.
        if (event.target.id === 'button_close') {
            if (boxRaProfilNow && boxRaProfilNow.contains(event.target)) {
                // X cliqué dans la popup profil → ne fait rien ici,
                // l'inline onclick se charge de fermer la popup.
                return;
            }
            contenuInfo.style.display = "none";
            boxRa.style.display = "none";
            if(boxRaProfilNow) { boxRaProfilNow.style.display = "none"; }
        }

        // Fermeture du profil piézométrique au clic en dehors du panel.
        // Désactivé maintenant que le popup est draggable — le clic
        // hors de la popup ne doit plus la fermer (sinon c'est trop
        // facile de la perdre en manipulant la fiche en dessous).
        // if (event.target === boxRaProfilNow) {
        //     if(boxRaProfilNow) {boxRaProfilNow.style.display = "none"; }
        // }
    });

    // Fermeture avec la touche Echap
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") {
            contenuInfo.style.display = "none";
            boxRa.style.display = "none";
        }
    });

    // Fonction pour gérer la saisie des agents présents
    function updateSelectedAgents() {
        // Récupère toutes les cases à cocher des agents
        var checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name^="check_agent_"]'));

        // 1. Récupère les valeurs des cases cochées
        var selectedValues = checkboxes
            .filter(function(checkbox) { return checkbox.checked; })
            .map(function(checkbox) { return checkbox.getAttribute('data-value').trim(); });

        // 2. Traite le texte manuel
        var currentText = document.getElementById('agents_complement').value;
        var manualText = currentText
            .split(' / ')
            .map(function(value) { return value.trim(); })
            .filter(function(value) {
                // Exclut les valeurs vides
                if (value === '') return false;

                // Exclut les doublons avec les cases cochées
                if (selectedValues.includes(value)) return false;

                // Exclut les valeurs correspondant à des cases non cochées
                var isDuplicateOfCheckbox = checkboxes.some(function(chk) {
                    return chk.getAttribute('data-value').trim() === value;
                });

                return !isDuplicateOfCheckbox;
            });

        // 3. Combine et met à jour le champ
        var combinedText = manualText.concat(selectedValues).join(' / ');
        document.getElementById('agents_complement').value = combinedText;
    }

    // Fill the reading time field with the current time (hh:mm) on focus,
    // only when empty (new RA). Existing values are preserved.
    function setHeureNow(input) {
        if (input.value === '') {
            var d = new Date();
            var hh = ('0' + d.getHours()).slice(-2);
            var mm = ('0' + d.getMinutes()).slice(-2);
            input.value = hh + ':' + mm;
        }
    }

</script>
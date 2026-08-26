/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions JS pour créer un lien vers une chronique spécifique et la visualiser dans le graphique
*/


// Fonction pour envoyer des informations en mode formulaire à la page data_chron.php pour afficher les données importées
function convertImgToPost(graph_chron) 
{
    var url = "data_chron.php"; // URL du script PHP
    var params = {
        graph_chron: graph_chron
    };

    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", url);
    form.setAttribute("target", "_blank"); // Ouvrir dans un nouvel onglet

    for (var key in params) 
    {
        if (params.hasOwnProperty(key)) 
        {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);
            form.appendChild(hiddenField);
        }
    }

    document.body.appendChild(form);
    form.submit();
}



// Fonction pour envoyer des informations en mode formulaire à la page data_chron.php pour aller sur la page de correction de chronique
function convertToPostCalcul(graph_chron, id_correction) 
{    
    var url = "data_chron.php"; // URL du script PHP
    var params = {
        graph_chron: graph_chron,
        button_calcul: "true", // Cette valeur sera utilisée pour valider if(isset($_POST['button_calcul']))
        id_correction: id_correction
    };

    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", url);
    form.setAttribute("target", "_blank"); // Ouvrir dans un nouvel onglet

    for (var key in params) 
    {
        if (params.hasOwnProperty(key)) 
        {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);
            form.appendChild(hiddenField);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

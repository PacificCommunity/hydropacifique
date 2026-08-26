/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

var text_info='';


function confirm_suppr(chemin,objet,name_objet)
{
	texte = 'Êtes-vous sûr de vouloir supprimer ' + objet + ': ' + name_objet + ' ?\n';
	if (confirm(texte))
    {confirm_suppr2(chemin,objet,name_objet);}
}


function confirm_suppr2(chemin,objet,name_objet)
{
	texte = 'Êtes-vous sûr vraiment sûr de vouloir supprimer ' + objet + ': ' + name_objet + '. Cette action est irréversible ?\n';
	if (confirm(texte))
    {document.location.href = chemin;}
}


function info_supp(objet,lier)
{
	text_info += 'Les ' + lier + ' lié(e)s à ' + objet + ' seront également supprimé(e)s !\n';	
}

function affiche_info()
{alert(text_info);}


// Fonctions pour double contrôle avant suppression de donnée avec une vérification côté serveur

function confirmFormStep1()
{
	if (confirm('Etes-vous sûr de vouloir supprimer les données sélectionnées ?')) 
	{
        document.getElementById("confirmationForm_step1").value = "confirmed";
        return confirmFormStep2();
    } else 
	{
        document.getElementById("confirmationForm_step1").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}

function confirmFormStep2() 
{
    if (confirm('Etes-vous VRAIMENT sûr de vouloir supprimer les données sélectionnées. Cette action est irréversible')) 
	{
        document.getElementById("confirmationForm_step2").value = "confirmed";
        return true;
    } else {
        document.getElementById("confirmationForm_step2").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}


function confirmDeleteETL()
{
	if (confirm('Etes-vous sûr de vouloir supprimer le dernier ETL ?')) 
	{
        document.getElementById("confirmationDelete_step1").value = "confirmed";
        return confirmDeleteETL2();
    } else 
	{
        document.getElementById("confirmationDelete_step1").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}

function confirmDeleteETL2() 
{
    if (confirm('Etes-vous VRAIMENT sûr de vouloir supprimer les données sélectionnées. Cette action est irréversible')) 
	{
        document.getElementById("confirmationDelete_step2").value = "confirmed";
        return true;
    } else {
        document.getElementById("confirmationDelete_step2").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}



function confirmUpdateETL()
{
	if (confirm('Etes-vous sûr de vouloir modifier les ETLs de cette station ?')) 
	{
        document.getElementById("confirmationUpdate_step1").value = "confirmed";
        return confirmUpdateETL2();
    } else 
	{
        document.getElementById("confirmationUpdate_step1").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}

function confirmUpdateETL2() 
{
    if (confirm('Etes-vous VRAIMENT sûr de vouloir modifier les ETLs de cette station. Cette action est irréversible')) 
	{
        document.getElementById("confirmationUpdate_step2").value = "confirmed";
        return true;
    } else {
        document.getElementById("confirmationUpdate_step2").value = "not_confirmed";
        return false; // Annule la soumission du formulaire
    }
}
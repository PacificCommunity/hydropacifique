/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonctions de verification de dates en JS 
*/

// Fonction pour valider une date réelle
function isValidDate(dateString) 
{
    // Vérifier le format avec une regex
    const dateRegex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-(\d{4})$/;
    if (!dateRegex.test(dateString)) 
    {
        return false; // Format invalide
    }

    // Découper la date
    const [day, month, year] = dateString.split("-").map(Number);

    // Créer une date JavaScript et vérifier sa validité
    const date = new Date(year, month - 1, day); // Mois commence à 0 en JS
    return (
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day
    );
}

// Fonction pour convertir une date (format valide) en objet Date
function parseDate(dateString) 
{
    [day, month, year] = dateString.split("-").map(Number);
    return new Date(year, month - 1, day);
}


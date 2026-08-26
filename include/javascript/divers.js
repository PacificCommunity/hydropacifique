/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Fonction JS qui permettent que contrôler à la volée le format de saisie dans des champs 
 

- evalInt() : permet de vérifier le formt INT dans un champs de saisie
- evalFoat() : permet de vérifier le formt FLOAT dans un champs de saisie
- formatNumberThousandsSeparator() : Fonction pour la régression linéaire en JS (équivalente de Trend dans VB)
- linearTrend() : Fonction pour la régression linéaire en JS (équivalente de Trend dans VB)

Ces fonctions sont utilisées dans js_jge.js 
*/



// Fonction pour vérifier le formt INT dans un champs de saisie
function evalInt(valeurChamp, valeurReturn) 
{
	if (valeurChamp === null || isNaN(valeurChamp) || valeurChamp === '') 
	{
		return valeurReturn; // Assigner 1 si la valeur est nulle, non numérique ou une chaîne vide
	} else {
		const parsedValue = parseInt(valeurChamp);
		return !isNaN(parsedValue) ? parsedValue : valeurReturn; // Affecter la valeur convertie, sinon affecter 1
	}
}

// Fonction pour vérifier le formt FLOAT dans un champs de saisie
function evalFloat(valeurChamp, valeurReturn) 
{
	if (valeurChamp == null || isNaN(valeurChamp) || valeurChamp == '') 
	{
		return valeurReturn; // Assigner 1 si la valeur est nulle, non numérique ou une chaîne vide
	} else {
		const parsedValue = parseFloat(valeurChamp);
		return !isNaN(parsedValue) ? parsedValue : valeurReturn; // Affecter la valeur convertie, sinon affecter 1
	}
}

// Fonction pour intégrer un séparateur de millier pour les nombres
function formatNumberThousandsSeparator(number) 
{
    // Convertir le nombre en chaîne de caractères
    var numberString = number.toString();
    
    // Séparer les parties entières et décimales
    var parts = numberString.split('.');
    var integerPart = parts[0];
    var decimalPart = parts.length > 1 ? '.' + parts[1] : '';

    // Insérer les séparateurs de milliers dans la partie entière
    var formattedIntegerPart = '';
    for (var i = integerPart.length - 1, j = 0; i >= 0; i--, j++) 
	{
        formattedIntegerPart = integerPart.charAt(i) + formattedIntegerPart;
        if (j % 3 === 2 && i > 0) 
		{
            formattedIntegerPart = ' ' + formattedIntegerPart;
        }
    }

    // Concaténer la partie entière et décimale
    return formattedIntegerPart + decimalPart;
}



// Fonction pour la régression linéaire en JS (équivalente de Trend dans VB)
function linearTrend(knownYs, knownXs, newXs) 
{
    const n = knownYs.length;

    // Calcul des sommes nécessaires
    const sumX = knownXs.reduce((sum, value) => sum + value, 0);
    const sumY = knownYs.reduce((sum, value) => sum + value, 0);
    const sumXY = knownXs.reduce((sum, value, index) => sum + value * knownYs[index], 0);
    const sumX2 = knownXs.reduce((sum, value) => sum + value * value, 0);

    // Calcul de la pente (m) et de l'ordonnée à l'origine (b)
    const a = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
    const b = (sumY - a * sumX) / n;

    // Prévisions pour les nouvelles valeurs de x
	newY = a * newXs + b;
    return newY;
}


// Version initDraggable uniquement avec Déplacement
function initDraggable(handleId, containerId) 
{
    var handle    = document.getElementById(handleId);
    var container = document.getElementById(containerId);

    if (!handle || !container) return;

    // Cloner le handle pour supprimer tous les anciens listeners
    var newHandle = handle.cloneNode(true);
    handle.parentNode.replaceChild(newHandle, handle);
    handle = newHandle;

    container.style.boxSizing = "border-box";
    container.style.position  = "absolute";
    container.style.width     = container.offsetWidth  + "px";
    container.style.height    = container.offsetHeight + "px";

    var isDragging = false;
    var offsetX = 0, offsetY = 0;

    handle.style.cursor = "grab";

    handle.addEventListener("mousedown", function(e) {
        isDragging = true;
        var rect = container.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        handle.style.cursor = "grabbing";
        e.preventDefault();
    });

    document.addEventListener("mousemove", function(e) {
        if (!isDragging) return;
        var x = Math.max(0, Math.min(e.clientX - offsetX, window.innerWidth  - container.offsetWidth));
        var y = Math.max(0, Math.min(e.clientY - offsetY, window.innerHeight - container.offsetHeight));
        container.style.left = x + "px";
        container.style.top  = y + "px";
    });

    document.addEventListener("mouseup", function() {
        if (isDragging) {
            isDragging = false;
            handle.style.cursor = "grab";
        }
    });
}

// Version initDraggableMove avec Déplacement et redimensionnemnet
function initDraggableResize(handleId, containerId) 
{
    var handle = document.getElementById(handleId);
    var container = document.getElementById(containerId);

    if (!handle || !container) return;

    // Forcer les styles de base nécessaires
    container.style.boxSizing = "border-box";
    container.style.position  = "absolute"; // ou "fixed" selon ton contexte
    // Figer width/height en px dès l'init pour sortir du 100% CSS
    container.style.width  = container.offsetWidth  + "px";
    container.style.height = container.offsetHeight + "px";

    var isDragging = false;
    var isResizing = false;
    var resizeDir = "";
    var offsetX = 0, offsetY = 0;
    var startX, startY, startW, startH, startLeft, startTop;

    const BORDER = 8; // Zone de détection en px
    const MIN_SIZE = 80;

    handle.style.cursor = "grab";

    // --- Détection de la direction de resize selon la position souris ---
    function getResizeDir(e) {
        var rect = container.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var w = rect.width;
        var h = rect.height;

        var onLeft   = x <= BORDER;
        var onRight  = x >= w - BORDER;
        var onTop    = y <= BORDER;
        var onBottom = y >= h - BORDER;

        if (onTop    && onLeft)  return "nw";
        if (onTop    && onRight) return "ne";
        if (onBottom && onLeft)  return "sw";
        if (onBottom && onRight) return "se";
        if (onTop)               return "n";
        if (onBottom)            return "s";
        if (onLeft)              return "w";
        if (onRight)             return "e";
        return "";
    }

    // --- Curseurs CSS selon la direction ---
    const cursors = {
        n: "n-resize", s: "s-resize",
        e: "e-resize", w: "w-resize",
        ne: "ne-resize", nw: "nw-resize",
        se: "se-resize", sw: "sw-resize"
    };

    // --- Curseur dynamique au survol du container ---
    container.addEventListener("mousemove", function(e) {
        if (isResizing || isDragging) return;
        var dir = getResizeDir(e);
        container.style.cursor = dir ? cursors[dir] : "";
        // Rétablir le curseur grab sur la poignée si pas sur un bord
        if (!dir && e.target === handle) handle.style.cursor = "grab";
    });

    // --- Mousedown : resize ou drag ---
    container.addEventListener("mousedown", function(e) {
        var dir = getResizeDir(e);

        if (dir) {
            // Lancement du resize
            isResizing = true;
            resizeDir = dir;
            startX    = e.clientX;
            startY    = e.clientY;
            startW    = container.offsetWidth;
            startH    = container.offsetHeight;
            startLeft = container.offsetLeft;
            startTop  = container.offsetTop;
            e.preventDefault();
        }
    });

    handle.addEventListener("mousedown", function(e) {
        if (isResizing) return;
        var dir = getResizeDir(e);
        if (dir) return; // Priorité au resize si on est sur un bord

        isDragging = true;
        var rect = container.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        handle.style.cursor = "grabbing";
        e.preventDefault();
        e.stopPropagation(); // Évite de déclencher le mousedown du container
    });

    // --- Mousemove global ---
    document.addEventListener("mousemove", function(e) {
        if (isDragging) {
            var x = e.clientX - offsetX;
            var y = e.clientY - offsetY;
            x = Math.max(0, Math.min(x, window.innerWidth  - container.offsetWidth));
            y = Math.max(0, Math.min(y, window.innerHeight - container.offsetHeight));
            container.style.left = x + "px";
            container.style.top  = y + "px";
        }

        if (isResizing) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var newW = startW, newH = startH;
            var newLeft = startLeft, newTop = startTop;

            // Calcul selon la direction
            if (resizeDir.includes("e")) newW = Math.max(MIN_SIZE, startW + dx);
            if (resizeDir.includes("s")) newH = Math.max(MIN_SIZE, startH + dy);
            if (resizeDir.includes("w")) {
                newW = Math.max(MIN_SIZE, startW - dx);
                newLeft = startLeft + (startW - newW);
            }
            if (resizeDir.includes("n")) {
                newH = Math.max(MIN_SIZE, startH - dy);
                newTop = startTop + (startH - newH);
            }

            container.style.width  = newW   + "px";
            container.style.height = newH   + "px";
            container.style.left   = newLeft + "px";
            container.style.top    = newTop  + "px";
        }
    });

    // --- Mouseup global ---
    document.addEventListener("mouseup", function() {
        if (isDragging) {
            isDragging = false;
            handle.style.cursor = "grab";
        }
        if (isResizing) {
            isResizing = false;
            resizeDir  = "";
            container.style.cursor = "";
        }
    });
}

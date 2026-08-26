<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
En-tête HTML présent sur chaque page générée
----------------------------------------
*/

header('Pragma: no-cache');
header('Cache-Control: no-cache');

//<!DOCTYPE html>
?>

<!DOCTYPE html>

<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Style-Type" content="text/css" />

<?php echo "<title>".TITRE_SITE."</title>"; ?>


<meta name="DC.Creator" content="vai-natura.com"/>
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<meta name="DC.Date.created" scheme="W3CDTF" content="2023"/>

<link rel="shortcut icon" type="images/x-icon" href="image/ico.png" />
<link rel="stylesheet" type="text/css" href="css/general.css" media="print, screen" />


<!-- Chargement de fichier JS contenant toutes les fonctions nécessaire et les librairies -->

<script type="text/javascript" src="include/javascript/ctrl_supp.js"></script>
<script type="text/javascript" src="include/javascript/quality_pass.js"></script>

<script type="text/javascript" src="vendor/npm-asset/flatpickr/dist/flatpickr.min.js"></script>
<script type="text/javascript" src="vendor/npm-asset/flatpickr/dist/l10n/fr.js"></script>

<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/select2.min.js"></script>
<!-- librairie graph Plotly -->
<script type="text/javascript" src="include/javascript/plotly-2.20.0.min.js"></script> 
<!--<script type="text/javascript" src="include/javascript/plotly-3.3.0.min.js"></script>-->


<script type="text/javascript" src="include/javascript/leaflet/leaflet.js"></script> <!-- Appel du script pour la carte leaflet.js -->
<script type="text/javascript" src="include/javascript/leaflet/markercluster/leaflet.markercluster.js"></script> <!-- Appel du script pour de groupement des mark dans leaflet -->
<script type="text/javascript" src="include/javascript/leaflet/fullscreen/Leaflet.fullscreen.js"></script> <!-- Appel du script pour l'affichage plein écran de la carte -->
<script type="text/javascript" src="include/javascript/coordmap/proj4.js"></script> <!-- Inclure la bibliothèque proj4js pour la conversion de coordonnées -->
<script type="text/javascript" src="include/javascript/coordmap/epsg.io_2154.js"></script> <!-- Définitions de projection pour Lambert 93 -->
<script type="text/javascript" src="include/javascript/leaflet/leaflet-image.js"></script> <!-- Appel du script pour enregistrer en image une carte leaflet -->
<script type="text/javascript" src="include/javascript/leaflet/esri-leaflet.js"></script>

	
<!-- <script type="text/javascript" src="include/javascript/select.js"></script> Fonction liée à des select mais n'est plus utiliser - sans doute à supprimer -->
<script type="text/javascript" src="include/javascript/divers.js"></script> <!-- -->
<script type="text/javascript" src="include/javascript/onglets-dym.js"></script>
<script type="text/javascript" src="include/javascript/formlink.js"></script> <!-- Appel du script pourTransmettre des données à une page par proctocole FORM --><script type="text/javascript">
    window.JGE_I18N = <?php echo json_encode([
        'BTN_DELETE_TITLE'   => TEXT_JGE_BTN_DELETE_TITLE,
        'WARN_NO_FREE_ROW'   => TEXT_JGE_WARN_NO_FREE_ROW,
        'MSG_CALC_OK'        => TEXT_JGE_MSG_CALC_OK,
        'MSG_CALC_OK_REMIND' => TEXT_JGE_MSG_CALC_OK_REMIND,
        'MSG_CALC_ERR'       => TEXT_JGE_MSG_CALC_ERR,
        'MSG_CALC_ERR_RUN'   => TEXT_JGE_MSG_CALC_ERR_RUN,
        'MSG_CALC_ERR_EMPTY' => TEXT_JGE_MSG_CALC_ERR_EMPTY,
        'TRACE_POINTS_NAME'  => TEXT_JGE_TRACE_POINTS_NAME,
        'TRACE_BED_NAME'     => TEXT_JGE_TRACE_BED_NAME,
        'TT_DISTANCE'        => TEXT_JGE_TT_DISTANCE,
        'TT_DEPTH'           => TEXT_JGE_TT_DEPTH,
        'TT_VELOCITY'        => TEXT_JGE_TT_VELOCITY,
        'TT_OBSERVATION'     => TEXT_JGE_TT_OBSERVATION,
        'AXIS_DISTANCE'      => TEXT_JGE_AXIS_DISTANCE,
        'AXIS_DEPTH'         => TEXT_JGE_AXIS_DEPTH,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script type="text/javascript" src="include/javascript/js_jge.js"></script>
<script type="text/javascript" src="include/javascript/js_jge_simple.js"></script> <!-- Appel du script de Fonctions liées aux JGE simple (saisie hauteur débit) -->



<!-- AJAX -->

<script type="text/javascript" src="include/javascript/ajax/pass.js"></script>
<script type="text/javascript" src="include/javascript/ajax/auto_select.js"></script>



<script type="text/javascript">
	
    // Gestion de la boite calendrier datepicker pour les champs date
	function initDatepickers(element) 
    {
        if (!element._flatpickr) 
        {
            flatpickr(element, {
                "locale": '<?php echo LANGUAGE;?>',
                dateFormat: "d-m-Y",
                allowInput: true,
                // Limit to today by default, unless the field opts out
                // with data-allow-future="1" (e.g. rating-curve Period End,
                // which may extend into the future).
                maxDate: (element.getAttribute('data-allow-future') === '1') ? null : "today",
                appendTo: document.body,

                onOpen: function(selectedDates, dateStr, instance) {
                    var input = instance.input;
                    var cal = instance.calendarContainer;

                    // Détecte tous les parents scrollables
                    function getScrollableParents(el) {
                        var parents = [window];
                        var parent = el.parentElement;
                        while (parent) {
                            var style = getComputedStyle(parent);
                            var overflow = style.overflow + style.overflowY + style.overflowX;
                            if (/auto|scroll/.test(overflow)) {
                                parents.push(parent);
                            }
                            parent = parent.parentElement;
                        }
                        return parents;
                    }

                    function repositionCalendar() {
                        var rect = input.getBoundingClientRect();
                        var calWidth = cal.offsetWidth;
                        var windowWidth = window.innerWidth;

                        var top = rect.bottom + window.scrollY;
                        var left = rect.left + window.scrollX;

                        if (left + calWidth > windowWidth) {
                            left = windowWidth - calWidth - 10;
                        }

                        cal.style.position = "absolute";
                        cal.style.top = top + "px";
                        cal.style.left = left + "px";
                        cal.style.zIndex = "9999";
                    }

                    repositionCalendar();

                    // Attache le scroll sur tous les parents scrollables détectés
                    var scrollableParents = getScrollableParents(input);
                    scrollableParents.forEach(function(parent) {
                        parent.addEventListener("scroll", repositionCalendar);
                    });

                    instance._scrollHandler = repositionCalendar;
                    instance._scrollableParents = scrollableParents;
                },

                onClose: function(selectedDates, dateStr, instance) {
                    if (instance._scrollHandler && instance._scrollableParents) {
                        instance._scrollableParents.forEach(function(parent) {
                            parent.removeEventListener("scroll", instance._scrollHandler);
                        });
                        instance._scrollHandler = null;
                        instance._scrollableParents = null;
                    }
                }

            }).open();
        }
    }

    // Désactive l'autocomplétion sur tous les inputs de type texte et nombre
    $(document).ready(function() 
    {        
        $('input[type="text"], input[type="number"], textarea').attr('autocomplete', 'off');
    });
   

    
    /**
     * Removes the target="_blank" attribute from all links on the page,
     * forcing them to open in the same tab instead of a new one.
     */
    document.addEventListener("DOMContentLoaded", function() {
        // Select all links with target="_blank"
        const links = document.querySelectorAll('a[target="_blank"]');
        // Loop through each link and remove the target="_blank" attribute
        links.forEach(function(link) {
            link.removeAttribute('target');
        });
    });
	
</script>



</head>
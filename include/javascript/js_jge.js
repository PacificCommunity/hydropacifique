/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
JS functions used to fill in measurement records (RA / Agents / gauging) without
reloading the page.

INTERNATIONALISATION
  All user-facing strings are pulled from the global `window.JGE_I18N` object
  injected by header_web.php, which is built from the TEXT_JGE_* constants
  defined in text_content_<lang>.php.

  The `t()` helper returns the translation, or a built-in fallback when the
  key is missing — so a missing translation never breaks the page.

NOTES (clean-up pass, behaviour preserved):
  - Added null-checks on document.getElementById() calls that were missing one,
    so a missing field will silently no-op instead of throwing.
  - Variables that were previously implicit globals (numVertElt, profMaxElt,
    vTrend, n, msg_info, row_l, b, ...) have been declared with let/var so they
    stay scoped to the function. No behavioural change.
  - The redundant in-place sort inside calc_q() has been left untouched on
    purpose: nettoyage_pts() (called just before calc_q in calcul_jge) already
    sorts the data, so this sort is a no-op in practice.
----------------------------------------
*/

// Maximum number of point rows handled by the gauging table.
const nbPts = 100;


// -----------------------------------------------------------------------------
// Vertical (verticale) highlight colour — a single green tint used to light up
// the active verticale: table row background, graph points and band.
// -----------------------------------------------------------------------------
const JGE_VERT_POINT_COLOR = '#1d9e75';  // active verticale points + band fill
const JGE_VERT_ROW_BG      = '#e1f5ee';  // active verticale table-row background

// Index of the table row currently being edited (-1 = none). Used to draw the
// yellow star on the matching graph point.
let jgeActiveRow = -1;

// Verticale number currently highlighted (the active row's verticale). Only
// this verticale is coloured (table rows + graph points + band). '' = none.
let jgeActiveVert = '';

// Dirty flag: true as soon as the user edits any point in the table. Used to
// skip the close-confirmation dialog when nothing has changed.
let jgePtsDirty = false;

// Per-verticale velocities computed by calc_q (exact values, like the Access
// CalculBras): jgeVertVelocities[bras] = [{ distance, vSurf, vMoy }, ...].
// Read by f_editgraph_jge_popup to draw the velocity curves above the profile.
// Stays empty until "Calculate" has run.
let jgeVertVelocities = {};

// Colour helpers — a single green tint (numVert kept for signature symmetry).
function jgeVertColor(numVert)
{
    return JGE_VERT_POINT_COLOR;
}
function jgeVertBg(numVert)
{
    return JGE_VERT_ROW_BG;
}


// -----------------------------------------------------------------------------
// i18n helper — reads from window.JGE_I18N with a safe fallback.
// -----------------------------------------------------------------------------
function t(key, fallback)
{
    if (typeof window !== 'undefined' && window.JGE_I18N && window.JGE_I18N[key]) {
        return window.JGE_I18N[key];
    }
    return (fallback !== undefined) ? fallback : key;
}


// -----------------------------------------------------------------------------
// Hook for arm deletion (kept as a stub so existing callers do not break).
// -----------------------------------------------------------------------------
function del_bras(bras)
{

}


// -----------------------------------------------------------------------------
// helice_eq()
// Triggered when the user changes the propeller (helice) selected for an arm.
// -----------------------------------------------------------------------------
function helice_eq()
{
    var selectElementHelice = document.getElementById('select_helice_pts');
    if (!selectElementHelice) return;

    var selectValueHelice = selectElementHelice.value;

    var selectedOptionText = '';
    if (selectElementHelice.selectedIndex >= 0) {
        selectedOptionText = selectElementHelice.options[selectElementHelice.selectedIndex].text;
    }

    var nameHeliceBox = document.getElementById('name_helice_box');
    if (nameHeliceBox) {
        nameHeliceBox.textContent = selectedOptionText;
    }

    if (selectValueHelice === '0' || selectValueHelice === '') {
        selectElementHelice.style.border = '';
        return;
    }

    selectElementHelice.style.border = '';

    var l1Elt = document.getElementById('l1_' + selectValueHelice);
    var a1Elt = document.getElementById('a1_' + selectValueHelice);
    var b1Elt = document.getElementById('b1_' + selectValueHelice);
    var l2Elt = document.getElementById('l2_' + selectValueHelice);
    var a2Elt = document.getElementById('a2_' + selectValueHelice);
    var b2Elt = document.getElementById('b2_' + selectValueHelice);
    var a3Elt = document.getElementById('a3_' + selectValueHelice);
    var b3Elt = document.getElementById('b3_' + selectValueHelice);

    if (!l1Elt || !a1Elt) return;

    document.getElementById('l1_bras').value = l1Elt.value;
    document.getElementById('l1_inf_bras').value = l1Elt.value;
    document.getElementById('a1_bras').value = a1Elt.value;
    document.getElementById('b1_bras').value = b1Elt.value;
    document.getElementById('l2_bras').value = l2Elt.value;
    document.getElementById('l2_inf_bras').value = l2Elt.value;
    document.getElementById('a2_bras').value = a2Elt.value;
    document.getElementById('b2_bras').value = b2Elt.value;
    document.getElementById('a3_bras').value = a3Elt.value;
    document.getElementById('b3_bras').value = b3Elt.value;

    var lsignPre = document.getElementById('lsign_pre');
    var lsign    = document.getElementById('lsign');

    if ((l2Elt.value > 0) && (l2Elt.value < 99.99))
    {
        // Two segments: l1_inf < n <= l2 (third row visible)
        document.getElementById('hidden_helice').style.visibility = 'visible';
        if (lsignPre) { lsignPre.textContent = '<'; }
        if (lsign)    { lsign.textContent    = '<='; }
    }
    else
    {
        // Single upper segment: l1_inf < n (third row hidden)
        document.getElementById('hidden_helice').style.visibility = 'hidden';
        if (lsignPre) { lsignPre.textContent = '<'; }
        if (lsign)    { lsign.textContent    = ''; }
    }
}


// -----------------------------------------------------------------------------
// Per-arm equipment <-> shared popup bridge.
//
// Equipment (current meter / propeller / rod diameter) is stored PER ARM in
// hidden fields rendered by form_jge_bras.php:
//     select_moulinet_<bras>, select_helice_<bras>, perche_diam_<bras>
// (these names are also what process_jge_save.php reads on save).
//
// The points popup shows a single, shared editor with fixed ids:
//     select_moulinet_pts, select_helice_pts, perche_diam_pts
//
// loadBrasEquipment() copies the active arm's hidden values into the popup
// editor (called on open). saveBrasEquipment() writes the popup editor back
// into the active arm's hidden fields (called on every change), so nothing is
// lost even if the user closes the popup without recalculating.
// -----------------------------------------------------------------------------
function loadBrasEquipment(bras)
{
    var hMoulinet = document.getElementById('select_moulinet_' + bras);
    var hHelice   = document.getElementById('select_helice_'   + bras);
    var hPerche   = document.getElementById('perche_diam_'     + bras);

    var pMoulinet = document.getElementById('select_moulinet_pts');
    var pHelice   = document.getElementById('select_helice_pts');
    var pPerche   = document.getElementById('perche_diam_pts');

    // Meter and propeller use Select2: set value then notify it via 'change'.
    if (pMoulinet)
    {
        pMoulinet.value = (hMoulinet && hMoulinet.value !== '') ? hMoulinet.value : '0';
        if (window.jQuery) { jQuery(pMoulinet).trigger('change.select2'); }
    }
    if (pHelice)
    {
        pHelice.value = (hHelice && hHelice.value !== '') ? hHelice.value : '0';
        if (window.jQuery) { jQuery(pHelice).trigger('change.select2'); }
    }
    // Rod diameter is a plain text field (only present for territory 'NC').
    if (pPerche) { pPerche.value = (hPerche ? hPerche.value : ''); }

    // Refresh the displayed velocity equation for the loaded propeller.
    helice_eq();
}


function saveBrasEquipment()
{
    // Resolve the arm currently edited in the popup.
    var numbras = document.getElementById('jge_bra_numbras');
    if (!numbras) { return; }
    var bras = numbras.value;

    var pMoulinet = document.getElementById('select_moulinet_pts');
    var pHelice   = document.getElementById('select_helice_pts');
    var pPerche   = document.getElementById('perche_diam_pts');

    var hMoulinet = document.getElementById('select_moulinet_' + bras);
    var hHelice   = document.getElementById('select_helice_'   + bras);
    var hPerche   = document.getElementById('perche_diam_'     + bras);

    if (hMoulinet && pMoulinet) { hMoulinet.value = pMoulinet.value; }
    if (hHelice   && pHelice)   { hHelice.value   = pHelice.value; }
    if (hPerche   && pPerche)   { hPerche.value   = pPerche.value; }
}


/**
 * Returns the current meter type ("C2" or "C31") for the given arm, or null.
 */
function getTypeMoulinet()
{
    const selectMoulinet = document.getElementById('select_moulinet_pts');
    if (!selectMoulinet || selectMoulinet.value === '0' || selectMoulinet.value === '')
    {
        return null;
    }

    const texteMoulinet = selectMoulinet.options[selectMoulinet.selectedIndex].text;
    if (!texteMoulinet) return null;

    const premierMot = texteMoulinet.trim().split(' ')[0];

    if (premierMot === 'C2' || premierMot === 'C31') {
        return premierMot;
    }

    return null;
}


/**
 * Computes the list of measurement depths to create at a given vertical.
 */
function getProfondeursMesure(profMax, type)
{
    const roundDown2 = (x) => Math.floor(x * 100) / 100;

    if (type === "C2")
    {
        if (profMax < 0.06) return [];
        if (profMax === 0.06) return [0.03];
        if (profMax === 0.07) return [0.04];
        if (profMax <= 0.25) return [0.03, roundDown2(profMax - 0.03)];
        return [0.03, roundDown2(profMax / 2), roundDown2(profMax - 0.03)];
    }

    if (type === "C31")
    {
        if (profMax < 0.20) return [];
        if (profMax <= 0.30) return [roundDown2(profMax / 2)];
        if (profMax <= 0.50) return [0.10, roundDown2(profMax - 0.10)];
        return [0.10, roundDown2(profMax / 2), roundDown2(profMax - 0.10)];
    }

    return [];
}


/**
 * Triggered on `change` of a profmax field.
 */
function preremplirVerticale(ligne)
{
    const bras = document.getElementById('jge_bra_numbras').value;

    const profMaxElt    = document.getElementById('jge_bra_profmax_' + ligne);
    const profMesureElt = document.getElementById('jge_bra_profmesure_' + ligne);
    const distElt       = document.getElementById('jge_bra_dist_' + ligne);
    const vertElt       = document.getElementById('jge_bra_vert_' + ligne);

    const profMax = parseFloat(profMaxElt.value);
    if (isNaN(profMax) || profMax <= 0) return;

    if (profMesureElt.value !== '' && !isNaN(parseFloat(profMesureElt.value))) return;

    const type = getTypeMoulinet();
    if (type === null) {
        return;
    }

    const profondeurs = getProfondeursMesure(profMax, type);
    if (profondeurs.length === 0) {
        return;
    }

    let numVert = getNextNumVerticale();
    const distance = distElt.value;

    vertElt.value = numVert;
    profMesureElt.value = profondeurs[0];

    let prochaineLigne = trouverProchaineLigneVide(ligne + 1);
    for (let k = 1; k < profondeurs.length; k++) {
        if (prochaineLigne === -1) {
            console.warn(t('WARN_NO_FREE_ROW',
                "Plus de lignes disponibles pour pré-remplir la verticale ") + numVert);
            break;
        }
        document.getElementById('jge_bra_vert_'       + prochaineLigne).value = numVert;
        document.getElementById('jge_bra_dist_'       + prochaineLigne).value = distance;
        document.getElementById('jge_bra_profmax_'    + prochaineLigne).value = profMax;
        document.getElementById('jge_bra_profmesure_' + prochaineLigne).value = profondeurs[k];
        prochaineLigne = trouverProchaineLigneVide(prochaineLigne + 1);
    }
}


function getNextNumVerticale()
{
    let maxVert = 0;
    for (let i = 0; i < nbPts; i++) {
        const el = document.getElementById('jge_bra_vert_' + i);
        if (el && el.value !== '') {
            const v = parseInt(el.value);
            if (!isNaN(v) && v > maxVert) maxVert = v;
        }
    }
    return maxVert + 1;
}


function trouverProchaineLigneVide(depart)
{
    for (let i = depart; i < nbPts; i++) {
        const el = document.getElementById('jge_bra_profmesure_' + i);
        if (el && el.value === '') return i;
    }
    return -1;
}


// -----------------------------------------------------------------------------
// showDataJge()
// -----------------------------------------------------------------------------
function showDataJge(bras)
{
    const tableContentPts = document.getElementById('table_content_pts');

    // i18n: title of the row delete button.
    const btnDeleteTitle = t('BTN_DELETE_TITLE', 'Supprimer');

    let htmlContrentPts = "";
    htmlContrentPts += "<input type='hidden' id='jge_bra_numbras' name='jge_bra_numbras' value='" + bras + "'>\n";
    htmlContrentPts += "<table id='table_tri' cellspacing='0' >";

    for (let i = 0; i < nbPts; i++)
    {
        const vertField = document.getElementById('jge_bra_vert_' + bras + '_' + i);
        if (!vertField) continue;

        const vert       = vertField.value;
        const dist       = document.getElementById('jge_bra_dist_'       + bras + '_' + i).value;
        const profmax    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i).value;
        const profmesure = document.getElementById('jge_bra_profmesure_' + bras + '_' + i).value;
        const nbtour     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i).value;
        const tps        = document.getElementById('jge_bra_tps_'        + bras + '_' + i).value;
        const tourssec   = document.getElementById('jge_bra_tourssec_'   + bras + '_' + i).value;
        const vitesse    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i).value;
        const obs        = document.getElementById('jge_bra_obs_'        + bras + '_' + i).value;

        // Row background stays neutral; the verticale is highlighted only
        // when one of its rows is active (see jgeHighlightVerticale).
        let row_l = "class='row2' data-row='" + i + "' data-vert='" + vert + "'"
                  + " onmouseover=\"if(this.dataset.active!=='1'){this.className='row2hover';}\""
                  + " onmouseout=\"if(this.dataset.active!=='1'){this.className='row2';}\"";

        htmlContrentPts +=
            "<tr " + row_l + ">" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:32px;height:18px;font-size:12px;' id='jge_bra_vert_"+i+"' name='jge_bra_vert_"+i+"' value='" + vert + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_dist_"+i+"' name='jge_bra_dist_"+i+"' value='" + dist + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_profmax_"+i+"' "
                        + "name='jge_bra_profmax_"+i+"' value='" + profmax + "'"
                        + " onchange='preremplirVerticale("+i+");' >\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_profmesure_"+i+"' name='jge_bra_profmesure_"+i+"' value='" + profmesure + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_nbtour_"+i+"' name='jge_bra_nbtour_"+i+"' value='" + nbtour + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_tps_"+i+"' name='jge_bra_tps_"+i+"' value='" + tps + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_tourssec_"+i+"' name='jge_bra_tourssec_"+i+"' value='" + tourssec + "'>\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:40px;height:18px;font-size:12px;' id='jge_bra_vitesse_"+i+"' name='jge_bra_vitesse_"+i+"' value='" + vitesse + "' >\n" +
                "</td>" +
                "<td style='padding-left:0;'>" +
                    "<input type='text' style='width:140px;height:18px;font-size:12px;' id='jge_bra_obs_"+i+"' name='jge_bra_obs_"+i+"' value='" + obs + "' >\n" +
                "</td>" +
                "<td style='text-align:center;padding-left:0;'>" +
                    "<a style='font-size:12px;font-weight:bold;cursor:pointer;' title=\""+ btnDeleteTitle +"\" onClick='delPts("+i+");'>X</a>"+
                "</td>" +
        "</tr>"
        ;
    }

    htmlContrentPts += "</table>";

    tableContentPts.innerHTML = htmlContrentPts;

    // Wire focus/blur on each row so editing highlights the matching graph
    // point (yellow star) and redraws the cross-section on blur.
    jgeWireRowInteractions(bras);

    toggleFieldsJge();

    // Table just (re)built from stored data → considered clean.
    jgePtsDirty = false;

    // Run the calculation once on open so the velocity curves are already
    // shown (without the user clicking "Calculate"). Only if there is at least
    // one complete measured point; the info-banner message is suppressed so it
    // does not pop up just from opening the popup.
    var hasMeasured = false;
    for (var ci = 0; ci < nbPts; ci++)
    {
        var dEl = document.getElementById('jge_bra_dist_'       + ci);
        var pEl = document.getElementById('jge_bra_profmax_'    + ci);
        var mEl = document.getElementById('jge_bra_profmesure_' + ci);
        if (dEl && pEl && mEl && dEl.value !== '' && pEl.value !== '' && mEl.value !== ''
            && !isNaN(parseFloat(mEl.value)))
        {
            hasMeasured = true;
            break;
        }
    }

    if (hasMeasured)
    {
        var infoEl   = document.getElementById('contenu_info');
        var infoHTML = infoEl ? infoEl.innerHTML  : '';
        var infoDisp = infoEl ? infoEl.style.display : '';

        // Preserve the sheet's unsaved state across this SILENT recompute:
        // opening the popup must neither create nor clear a real "unsaved" flag.
        var wasDirty = (typeof jgeIsDirty === 'function') ? jgeIsDirty() : false;

        maj_JgePts(true);   // silent recompute → fills jgeVertVelocities → draws curves

        // Restore the info banner to its pre-open state (suppress the
        // "calculation done" message that maj_JgePts writes).
        if (infoEl)
        {
            infoEl.innerHTML     = infoHTML;
            infoEl.style.display = infoDisp;
        }
        // maj_JgePts rebuilt nothing in the table, but mark it clean again.
        jgePtsDirty = false;
        // Restore the pre-open unsaved state (don't let a silent recompute
        // flip it either way).
        if (!wasDirty && typeof clearJgeUnsaved === 'function') { clearJgeUnsaved(); }
    }
    else
    {
        // Initial draw of the popup graph (no velocity curves yet).
        if (document.getElementById('plot_jge_popup')) {
            f_editgraph_jge_popup(bras);
        }
    }
}


// -----------------------------------------------------------------------------
// jgeWireRowInteractions()
// Attaches focus/blur listeners to the geometry inputs of each table row so
// that editing a row highlights the matching point on the popup graph and
// redraws the cross-section when the user leaves the field.
// -----------------------------------------------------------------------------
function jgeWireRowInteractions(bras)
{
    var container = document.getElementById('table_content_pts');
    if (!container) { return; }

    var rows = container.querySelectorAll("tr[data-row]");
    rows.forEach(function(tr)
    {
        var rowIdx = parseInt(tr.getAttribute('data-row'), 10);
        var inputs = tr.querySelectorAll("input[type='text']");

        inputs.forEach(function(inp)
        {
            // Any edit marks the table dirty and invalidates the (now stale)
            // per-verticale velocity curves until the next calculation.
            inp.addEventListener('input', function()
            {
                jgePtsDirty = true;
                jgeVertVelocities = {};
            });

            inp.addEventListener('focus', function()
            {
                // Turn off the previously highlighted verticale (if different).
                if (jgeActiveRow !== -1 && jgeActiveRow !== rowIdx)
                {
                    jgeHighlightVerticale(jgeActiveVert, false);
                }

                jgeActiveRow  = rowIdx;
                // Read the verticale number live from the row's vert input.
                var vEl = document.getElementById('jge_bra_vert_' + rowIdx);
                jgeActiveVert = vEl ? vEl.value : '';

                // Light up every row of this verticale + the active outline.
                jgeHighlightVerticale(jgeActiveVert, true);
                tr.classList.add('row2active');

                highlightPopupPoint(bras, rowIdx);
            });

            inp.addEventListener('blur', function()
            {
                setTimeout(function()
                {
                    // Another row took focus: it now owns the highlight.
                    if (jgeActiveRow !== rowIdx) { return; }

                    if (!tr.contains(document.activeElement))
                    {
                        jgeHighlightVerticale(jgeActiveVert, false);

                        // Lightweight on-the-fly: recompute only THIS row's
                        // velocity (from nb tours / temps / tops-sec), then
                        // redraw the popup geometry. Full flow calculation
                        // stays on the "Validate and calculate flow" button.
                        calcVitesseLigne(rowIdx, bras);

                        jgeActiveRow  = -1;
                        jgeActiveVert = '';
                        f_editgraph_jge_popup(bras);
                    }
                }, 60);
            });
        });
    });
}


// -----------------------------------------------------------------------------
// calcVitesseLigne()
// Recomputes the velocity of ONE table row (visible inputs, no bras prefix)
// from its TOPs/time or TOPs-sec, using the selected propeller's coefficients.
// Same formula as calc_vitesse_pts(), applied to a single line — used for the
// lightweight on-the-fly velocity update when the user edits nb tours / temps
// / tops-sec on a row.
// -----------------------------------------------------------------------------
function calcVitesseLigne(i, bras)
{
    var champs_saisi = document.getElementById('select_saisie');
    if (!champs_saisi || champs_saisi.value == 3) { return; } // mode 3 = direct velocity

    var selHelice = document.getElementById('select_helice_pts');
    if (!selHelice) { return; }
    var hv = selHelice.value;
    if (!hv || hv === '0') { return; } // no propeller -> no velocity equation

    var getF = function(id) {
        var el = document.getElementById(id);
        return (el && el.value !== '') ? parseFloat(el.value) : 0;
    };
    var l1 = getF('l1_' + hv), a1 = getF('a1_' + hv), b1 = getF('b1_' + hv);
    var l2 = getF('l2_' + hv), a2 = getF('a2_' + hv), b2 = getF('b2_' + hv);
    var a3 = getF('a3_' + hv), b3 = getF('b3_' + hv);

    var nbtourEl   = document.getElementById('jge_bra_nbtour_'   + i);
    var tpsEl      = document.getElementById('jge_bra_tps_'      + i);
    var tourssecEl = document.getElementById('jge_bra_tourssec_' + i);
    var vitesseEl  = document.getElementById('jge_bra_vitesse_'  + i);
    if (!vitesseEl) { return; }

    var n = 0;
    var calcul = false;

    if (champs_saisi.value == 1)
    {
        if (nbtourEl && tpsEl && !isNaN(parseInt(nbtourEl.value)) && !isNaN(parseInt(tpsEl.value)))
        {
            var nbtourElt = evalFloat(nbtourEl.value, '');
            var tpsElt    = evalFloat(tpsEl.value, '');
            if (nbtourElt > 0 && tpsElt > 0)
            {
                n = nbtourElt / tpsElt;
                if (tourssecEl) { tourssecEl.value = n.toFixed(3); }
            }
            calcul = true;
        }
    }
    else if (champs_saisi.value == 2)
    {
        if (tourssecEl && !isNaN(parseFloat(tourssecEl.value)))
        {
            n = evalFloat(tourssecEl.value, '');
            tourssecEl.value = n.toFixed(3);
            calcul = true;
        }
    }

    if (!calcul) { return; }

    var coefA = 0, coefB = 0;
    if (n > 0)
    {
        if ((l1 > 0) && (l1 < 99.99) && (n <= l1))
        {
            coefA = a1; coefB = b1;
        }
        else if ((l1 > 0) && (l1 < 99.99) && (l2 === 0 || (l2 > 0 && l2 < 99.99)) && (l1 < n))
        {
            coefA = a2; coefB = b2;
        }
        else if ((l2 > 0) && (l2 < 99.99) && (l2 < n))
        {
            coefA = a3; coefB = b3;
        }
        vitesseEl.value = (coefA * n + coefB).toFixed(3);
    }
    else
    {
        vitesseEl.value = 0;
    }
}


// -----------------------------------------------------------------------------
// jgeHighlightVerticale()
// Lights (or clears) the background colour of every table row sharing the
// given verticale number. Colour comes from the verticale palette.
// -----------------------------------------------------------------------------
function jgeHighlightVerticale(numVert, on)
{
    var n = parseInt(numVert, 10);
    if (isNaN(n) || n < 1) { return; }

    var container = document.getElementById('table_content_pts');
    if (!container) { return; }

    var bg = on ? jgeVertBg(numVert) : '';
    var rows = container.querySelectorAll("tr[data-vert='" + numVert + "']");
    rows.forEach(function(tr)
    {
        tr.style.backgroundColor = bg;
        if (!on)
        {
            tr.classList.remove('row2active');
            tr.className = 'row2';
        }
    });
}


function delPts(pts)
{
    document.getElementById('jge_bra_vert_'       + pts).value = '';
    document.getElementById('jge_bra_dist_'       + pts).value = '';
    document.getElementById('jge_bra_profmax_'    + pts).value = '';
    document.getElementById('jge_bra_profmesure_' + pts).value = '';
    document.getElementById('jge_bra_nbtour_'     + pts).value = '';
    document.getElementById('jge_bra_tps_'        + pts).value = '';
    document.getElementById('jge_bra_tourssec_'   + pts).value = '';
    document.getElementById('jge_bra_vitesse_'    + pts).value = '';
    document.getElementById('jge_bra_obs_'        + pts).value = '';

    // Clearing a row counts as a modification + invalidates velocity curves.
    jgePtsDirty = true;
    jgeVertVelocities = {};
    if (document.getElementById('plot_jge_popup')) { f_editgraph_jge_popup(0); }
}


// -----------------------------------------------------------------------------
// sortJgePtsRows()
// Reorders the VISIBLE point rows (the editable jge_bra_*_<i> inputs) by bank
// distance, in place, before the calculation runs.
//
// Rules:
//   - Row 0 (the start-bank reference point) always stays first.
//   - Fully empty rows (no distance and no depth) are pushed to the end so the
//     filled rows remain contiguous at the top.
//   - Among filled rows, sort by ascending distance; for points sharing the
//     same distance (same vertical), keep depth (profmesure) descending.
//   - The sort is stable, so rows that compare equal keep their input order.
// -----------------------------------------------------------------------------
function sortJgePtsRows()
{
    // The per-row field keys that make up a visible row.
    var FIELDS = ['vert', 'dist', 'profmax', 'profmesure', 'nbtour', 'tps', 'tourssec', 'vitesse', 'obs'];

    // Snapshot every existing row into a plain object (keeps all field values).
    var rows = [];
    for (var i = 0; i < nbPts; i++)
    {
        var distEl = document.getElementById('jge_bra_dist_' + i);
        if (!distEl) { continue; } // row i not rendered

        var rec = { _idx: i, _vals: {} };
        FIELDS.forEach(function(f)
        {
            var el = document.getElementById('jge_bra_' + f + '_' + i);
            rec._vals[f] = el ? el.value : '';
        });
        rows.push(rec);
    }

    if (rows.length <= 2) { return; } // nothing meaningful to reorder

    // Keep the first row (start bank) pinned; sort the rest.
    var firstRow = rows.shift();

    var parseNum = function(v) {
        var n = parseFloat(v);
        return isNaN(n) ? null : n;
    };
    var isEmpty = function(r) {
        return parseNum(r._vals.dist) === null && parseNum(r._vals.profmesure) === null;
    };

    // Decorate with original index for a stable comparator.
    rows.forEach(function(r, k) { r._ord = k; });

    rows.sort(function(a, b)
    {
        var aEmpty = isEmpty(a), bEmpty = isEmpty(b);
        if (aEmpty && bEmpty) { return a._ord - b._ord; } // both empty: keep order
        if (aEmpty) { return 1; }   // empty rows last
        if (bEmpty) { return -1; }

        var da = parseNum(a._vals.dist), db = parseNum(b._vals.dist);
        if (da === null) da = Infinity;
        if (db === null) db = Infinity;
        if (da !== db) { return da - db; } // ascending distance

        // Same vertical: depth (profmesure) descending.
        var pa = parseNum(a._vals.profmesure), pb = parseNum(b._vals.profmesure);
        if (pa === null) pa = -Infinity;
        if (pb === null) pb = -Infinity;
        if (pa !== pb) { return pb - pa; }

        return a._ord - b._ord; // stable fallback
    });

    var ordered = [firstRow].concat(rows);

    // Write the reordered values back into the visible inputs (positional).
    for (var pos = 0; pos < ordered.length; pos++)
    {
        var src = ordered[pos];
        FIELDS.forEach(function(f)
        {
            var el = document.getElementById('jge_bra_' + f + '_' + pos);
            if (el) { el.value = src._vals[f]; }
        });
    }
}


// -----------------------------------------------------------------------------
// maj_JgePts()
// -----------------------------------------------------------------------------
function maj_JgePts(silent)
{
    // Guard: TOPs (1) and TOPs/sec (2) modes need a propeller equation to
    // convert rotation into velocity. Without one, the calculation cannot run.
    // On a real Validate click (silent !== true) we warn and abort; on a silent
    // recompute (popup opening) we just skip — calc_vitesse_pts is null-safe.
    var champsSaisiEl = document.getElementById('select_saisie');
    var saisieMode = champsSaisiEl ? parseInt(champsSaisiEl.value, 10) : 1;
    if (isNaN(saisieMode)) { saisieMode = 1; }

    if (saisieMode === 1 || saisieMode === 2)
    {
        var helEl = document.getElementById('select_helice_pts');
        var helVal = helEl ? helEl.value : '';
        if (!helVal || helVal === '0' || helVal === '')
        {
            if (silent === true) { return; } // popup opening: skip quietly

            var info = document.getElementById('contenu_info');
            if (info)
            {
                info.innerHTML     = t('MSG_NEED_HELICE',
                    "Veuillez sélectionner une hélice : le calcul de la vitesse à partir des rotations en a besoin.");
                info.style.display = 'block';
                info.style.zIndex  = '3000';
                info.style.border  = '2px solid #930000';
            }
            // Flag the propeller select to draw the eye to it.
            if (helEl) { helEl.style.outline = '2px solid #930000'; }
            return; // abort: do not compute
        }
    }
    // Clear any previous propeller-missing highlight.
    var helElOk = document.getElementById('select_helice_pts');
    if (helElOk) { helElOk.style.outline = ''; }

    // Re-sort the visible rows by bank distance before computing. The user may
    // have entered a point out of order (e.g. forgotten then appended below).
    // Ordering by ascending distance is required for the area-between-verticals
    // computation. Only done on an explicit Validate click (not on the silent
    // recompute when the popup opens, which must not reshuffle rows unexpectedly).
    if (silent !== true) { sortJgePtsRows(); }

    let numVert = 0;
    let distance = 0;
    let profMax = 0;

    let bras = document.getElementById('jge_bra_numbras').value;

    for (let i = 0; i < nbPts; i++)
    {
        const vertElement       = document.getElementById('jge_bra_vert_' + i);
        const distElement       = document.getElementById('jge_bra_dist_' + i);
        const profmaxElement    = document.getElementById('jge_bra_profmax_' + i);
        const profmesureElement = document.getElementById('jge_bra_profmesure_' + i);
        const nbtourElement     = document.getElementById('jge_bra_nbtour_' + i);
        const tpsElement        = document.getElementById('jge_bra_tps_' + i);
        const tourssecElement   = document.getElementById('jge_bra_tourssec_' + i);
        const vitesseElement    = document.getElementById('jge_bra_vitesse_' + i);
        const obsElement        = document.getElementById('jge_bra_obs_' + i);

        const hVert       = document.getElementById('jge_bra_vert_'       + bras + '_' + i);
        const hDist       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        const hProfmax    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        const hProfmesure = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        const hNbtour     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i);
        const hTps        = document.getElementById('jge_bra_tps_'        + bras + '_' + i);
        const hTourssec   = document.getElementById('jge_bra_tourssec_'   + bras + '_' + i);
        const hVitesse    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);
        const hObs        = document.getElementById('jge_bra_obs_'        + bras + '_' + i);

        if (hVert)       hVert.value = '';
        if (hDist)       hDist.value = '';
        if (hProfmax)    hProfmax.value = '';
        if (hProfmesure) hProfmesure.value = '';
        if (hNbtour)     hNbtour.value = '';
        if (hTps)        hTps.value = '';
        if (hTourssec)   hTourssec.value = '';
        if (hVitesse)    hVitesse.value = '';
        if (hObs)        hObs.value = '';

        if (vertElement)
        {
            if (profmesureElement.value !== '' && !isNaN(parseFloat(profmesureElement.value)))
            {
                let distElt = evalFloat(distElement.value, distance);
                if (distElt !== distance)
                {
                    numVert++;
                    distance = distElt;
                }

                let numVertElt = evalInt(vertElement.value, numVert);
                numVert = numVertElt;

                let profMesureElt = evalFloat(profmesureElement.value, '');
                let profMaxElt = evalFloat(profmaxElement.value, profMax);
                profMax = profMaxElt;

                let nbtourElt = evalInt(nbtourElement.value, '');
                let tpsElt = evalInt(tpsElement.value, 30);
                let vitesseElt = evalFloat(vitesseElement.value, '');

                let tourssecElt = evalFloat(tourssecElement.value, '');

                if (hVert)       hVert.value = numVertElt;
                if (hDist)       hDist.value = distElt;
                if (hProfmax)    hProfmax.value = profMaxElt;
                if (hProfmesure) hProfmesure.value = profMesureElt;
                if (hNbtour)     hNbtour.value = nbtourElt;
                if (hTps)        hTps.value = tpsElt;
                if (hTourssec)   hTourssec.value = tourssecElt;
                if (hVitesse)    hVitesse.value = vitesseElt;
                if (hObs)        hObs.value = obsElement ? obsElement.value : '';
            }
        }
    }

    calcul_jge(bras);
}


// -----------------------------------------------------------------------------
// calcul_jge()
// -----------------------------------------------------------------------------
function calcul_jge(bras)
{
    var contenuInfo = document.getElementById('contenu_info');
    var nb_bras = document.getElementById('nb_bras');
    var champs_saisi = document.getElementById('select_saisie');

    // select_saisie lives in the points popup, which may not be loaded yet
    // when the tab graph is drawn on page load. Fall back to the saved user
    // preference (menuStates), else mode 1 (TOPs).
    var saisieVal;
    if (champs_saisi) {
        saisieVal = parseInt(champs_saisi.value, 10);
    } else if (typeof menuStates !== 'undefined' && menuStates['jge-pts-saisie']) {
        saisieVal = parseInt(menuStates['jge-pts-saisie'], 10);
    }
    if (isNaN(saisieVal) || !saisieVal) { saisieVal = 1; }
    var nbBrasVal = nb_bras ? nb_bras.value : 1;

    nettoyage_pts(bras);

    if (saisieVal < 3) { calc_vitesse_pts(bras); }

    calc_q(bras, nbBrasVal);
    f_editgraph_jge(bras);

    // Refresh the popup cross-section so the freshly computed velocity curves
    // (per verticale) appear, and mirror the results into the popup panel.
    if (document.getElementById('plot_jge_popup')) { f_editgraph_jge_popup(bras); }
    syncPopupResults(bras);
}


// -----------------------------------------------------------------------------
// syncPopupResults()
// Copies the tab's computed result fields (depouil_bras_*_<bras>) into the
// read-only result panel shown inside the points popup (popup_depouil_bras_*).
// -----------------------------------------------------------------------------
function syncPopupResults(bras)
{
    var fields = ['depouil_bras_q', 'depouil_bras_hmoy', 'depouil_bras_vmoy',
                  'depouil_bras_vsurf', 'depouil_bras_surfmouil', 'depouil_bras_perimouil',
                  'depouil_bras_profmoy', 'depouil_bras_distmax', 'depouil_bras_rh'];

    fields.forEach(function(f)
    {
        var src = document.getElementById(f + '_' + bras);
        var dst = document.getElementById('popup_' + f);
        if (src && dst) { dst.value = src.value; }
    });
}


// -----------------------------------------------------------------------------
// jgeNumValue()
// Reads a numeric form field by id. Returns NaN when the field is missing or
// blank, so callers can tell "no value" from a real 0. Accepts the comma as a
// decimal separator (field crews type both).
// -----------------------------------------------------------------------------
function jgeNumValue(id)
{
    var el = document.getElementById(id);
    if (!el) { return NaN; }

    var raw = String(el.value).trim().replace(',', '.');
    if (raw === '') { return NaN; }

    return parseFloat(raw);
}


// -----------------------------------------------------------------------------
// jgeBrasIsEmpty()
// Mirrors the server-side "empty arm" rule of process_jge_save.php: an arm with
// no time and no gauge reading at all is skipped on save. Keeping both sides in
// sync matters, otherwise the header total would count arms that never reach
// the database.
// -----------------------------------------------------------------------------
function jgeBrasIsEmpty(bras)
{
    var ids = ['heure_first_', 'h_ech_first_', 'heure_end_', 'h_ech_end_'];

    for (var i = 0; i < ids.length; i++)
    {
        var el = document.getElementById(ids[i] + bras);
        if (el && String(el.value).trim() !== '') { return false; }
    }

    return true;
}


// -----------------------------------------------------------------------------
// syncHeaderFromBras()
// Derives the two sidebar header fields from the arm result bars:
//   jge_q    = sum of depouil_bras_q over the non-empty arms (flows add up)
//   jge_hmoy = depouil_bras_hmoy of the FIRST non-empty arm
//
// Hmoy is deliberately not averaged across arms: each arm reads its own staff
// gauge, with its own datum, so averaging would mix reference frames and
// produce a stage that no rating curve can use. The reference gauge lives in a
// single arm - the one the crew starts from, i.e. the first non-empty arm.
// "First non-empty" rather than "arm 1" because process_jge_save.php renumbers
// arms on save, skipping the empty ones.
//
// Called both at the end of calc_q() (recompute from the measurement points)
// and by saveJGE() (manual edits straight into an arm result bar).
// -----------------------------------------------------------------------------
function syncHeaderFromBras()
{
    var jgeQEl    = document.getElementById('jge_q');
    var jgeHmoyEl = document.getElementById('jge_hmoy');
    if (!jgeQEl && !jgeHmoyEl) { return; }

    // Collect the arm numbers actually present in the DOM. Reading them from
    // the fields themselves (rather than from nb_bras) also covers the blank
    // "new arm" tab, which the empty-arm test below then discards.
    var prefix   = 'depouil_bras_q_';
    var brasList = [];

    Array.prototype.forEach.call(
        document.querySelectorAll("input[id^='" + prefix + "']"),
        function(el)
        {
            var n = parseInt(el.id.substring(prefix.length), 10);
            if (!isNaN(n)) { brasList.push(n); }
        });

    brasList.sort(function(a, b) { return a - b; });

    var sumQ    = 0;
    var hasQ    = false;
    var hmoyRef = NaN;

    brasList.forEach(function(n)
    {
        if (jgeBrasIsEmpty(n)) { return; }

        var q = jgeNumValue(prefix + n);
        if (!isNaN(q)) { sumQ += q; hasQ = true; }

        if (isNaN(hmoyRef))
        {
            var h = jgeNumValue('depouil_bras_hmoy_' + n);
            if (!isNaN(h)) { hmoyRef = h; }
        }
    });

    // Leave the header untouched when no arm carries a value: a gauging whose
    // Q / Hmoy were typed straight into the sidebar (legacy or summary record)
    // must not be wiped by an empty recompute.
    if (jgeQEl    && hasQ)            { jgeQEl.value    = sumQ.toFixed(3); }
    if (jgeHmoyEl && !isNaN(hmoyRef)) { jgeHmoyEl.value = hmoyRef.toFixed(0); }
}


// -----------------------------------------------------------------------------
// showUnsavedWarning() / hideUnsavedWarning()
// The yellow "unsaved changes" badge is rendered once per arm tab, so every
// badge is toggled together: the sheet as a whole is unsaved, and switching
// tabs must not make the warning disappear.
// -----------------------------------------------------------------------------
function showUnsavedWarning()
{
    Array.prototype.forEach.call(
        document.querySelectorAll("span[id^='unsaved_warning_']"),
        function(el) { el.style.display = 'inline-block'; });

    // Also highlight the Save button and arm the beforeunload guard.
    if (typeof markJgeUnsaved === 'function') { markJgeUnsaved(); }
}


function hideUnsavedWarning()
{
    Array.prototype.forEach.call(
        document.querySelectorAll("span[id^='unsaved_warning_']"),
        function(el) { el.style.display = 'none'; });
}


// -----------------------------------------------------------------------------
// jgeWireUnsavedTracking()
// Any edit inside the gauging form marks the sheet as unsaved - including a
// value typed straight into an arm result bar, which used to change nothing at
// all. Delegation on the form (rather than one listener per field) also covers
// fields added after page load.
//
// Programmatic assignments (el.value = ...) do NOT fire 'input' or 'change', so
// the silent recompute run by view_jge_pts() cannot raise a false warning.
// -----------------------------------------------------------------------------
function jgeWireUnsavedTracking()
{
    var form = document.getElementById('formJGE');
    if (!form || form.dataset.unsavedWired === '1') { return; }

    form.dataset.unsavedWired = '1';

    ['input', 'change'].forEach(function(evt)
    {
        form.addEventListener(evt, function(e)
        {
            var tag = e.target ? e.target.tagName : '';
            if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA')
            {
                showUnsavedWarning();
            }
        });
    });
}


// -----------------------------------------------------------------------------
// StreamPro switch
//
// The box is not backed by a database field: it mirrors the arm comment, where
// a StreamPro gauging already records its coefficient.
//
// JGE_STREAMPRO_MARKER is stored data, not interface text, so it is
// deliberately left untranslated - detection has to work whatever language the
// record was captured in.
//
// The pattern also matches the legacy New Caledonia wording ("Coef. SP : 6.8").
// "SP" is anchored to "coef" so a stray "SP" elsewhere in a comment cannot tick
// the box by accident.
// -----------------------------------------------------------------------------
var JGE_STREAMPRO_MARKER = 'StreamPro - coef. : ';
var JGE_STREAMPRO_RE     = /stream\s*pro|coef\.?\s*sp\b/i;


function jgeObsHasStreamPro(bras)
{
    var obs = document.getElementById('bras_obs_' + bras);
    return !!obs && JGE_STREAMPRO_RE.test(obs.value);
}


// -----------------------------------------------------------------------------
// toggleStreamPro()
// Applies the switch: hides or restores the entry button and the graph, and on
// ticking seeds the comment with the coefficient prefix for the user to fill in.
//
// Unticking erases nothing. The comment belongs to the user and a coefficient
// already typed must never be lost. The trade-off is that the marker survives,
// so the box ticks itself again on the next reload: clearing it is a deliberate
// edit of the comment, which is the intended behaviour.
// -----------------------------------------------------------------------------
function toggleStreamPro(bras)
{
    var cb = document.getElementById('streampro_' + bras);
    if (!cb) { return; }

    var on = cb.checked;

    var box = document.getElementById('box_saisie_' + bras);
    if (box) { box.style.display = on ? 'none' : 'inline-block'; }

    // Restoring '' rather than 'block' keeps the flex sizing declared inline.
    var plot = document.getElementById('plot_jge_bras_' + bras);
    if (plot) { plot.style.display = on ? 'none' : ''; }

    if (!on) { return; }

    var obs = document.getElementById('bras_obs_' + bras);
    if (!obs || JGE_STREAMPRO_RE.test(obs.value)) { return; }

    var existing = obs.value.trim();
    obs.value = JGE_STREAMPRO_MARKER + (existing !== '' ? '\n' + existing : '');

    // Drop the caret right after the prefix so the coefficient can be typed
    // straight away.
    obs.focus();
    obs.setSelectionRange(JGE_STREAMPRO_MARKER.length, JGE_STREAMPRO_MARKER.length);
}


// -----------------------------------------------------------------------------
// jgeApplyStreamPro()
// Derives every box from its arm comment on load, then applies the switch, so a
// StreamPro gauging - new or legacy - reopens without the entry button.
//
// Detection runs once, at load. Watching the comment live would fight the rule
// above: after unticking, the user's next keystroke in the comment would tick
// the box straight back.
// -----------------------------------------------------------------------------
function jgeApplyStreamPro()
{
    Array.prototype.forEach.call(
        document.querySelectorAll("input[id^='streampro_']"),
        function(cb)
        {
            var n = parseInt(cb.id.substring('streampro_'.length), 10);
            if (isNaN(n)) { return; }

            cb.checked = jgeObsHasStreamPro(n);
            toggleStreamPro(n);
        });
}


function jgeInit()
{
    jgeWireUnsavedTracking();
    jgeApplyStreamPro();
}


if (document.readyState === 'loading')
{
    document.addEventListener('DOMContentLoaded', jgeInit);
}
else
{
    jgeInit();
}



// -----------------------------------------------------------------------------
// nettoyage_pts()
// -----------------------------------------------------------------------------
function nettoyage_pts(bras)
{
    let numVert = 0;
    let distance = 0;
    let profMax = 0;

    let data = [];

    for (let i = 0; i < nbPts; i++)
    {
        let numVertElement    = document.getElementById('jge_bra_vert_'       + bras + '_' + i);
        let distElement       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        let profMaxElement    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        let profMesureElement = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        let nbtourElement     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i);
        let tpsElement        = document.getElementById('jge_bra_tps_'        + bras + '_' + i);
        let vitesseElement    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);
        let obsElement        = document.getElementById('jge_bra_obs_'        + bras + '_' + i);

        if (profMesureElement && profMaxElement.value == '' && !isNaN(parseFloat(profMesureElement.value)))
        {
            let distElt = evalFloat(distElement.value, distance);
            if (distElt != distance)
            {
                numVert++;
                distance = distElt;
            }

            let numVertElt = evalInt(numVertElement.value, numVert);
            numVert = numVertElt;

            let profMesureElt = evalFloat(profMesureElement.value, 0);

            let profMaxElt = evalFloat(profMaxElement.value, profMax);
            profMax = profMaxElt;

            let nbtourElt = evalInt(nbtourElement.value, 0);
            let tpsElt    = evalInt(tpsElement.value, 30);

            let vitesseElt = evalFloat(vitesseElement.value, 0);

            var rowData =
            {
                numVert: numVertElt,
                distance: distElt,
                profMax: profMaxElt,
                profMesure: profMesureElt,
                nbTour: nbtourElt,
                tps: tpsElt,
                vitesse: vitesseElt,
                obs: obsElement
            };

            data.push(rowData);
        }
    }

    data.sort(function(a, b)
    {
        let distanceA = parseFloat(a.distance);
        let distanceB = parseFloat(b.distance);

        if (distanceA === distanceB) {
            let profMesureA = parseFloat(a.profMesure);
            let profMesureB = parseFloat(b.profMesure);
            return profMesureB - profMesureA;
        }
        return distanceA - distanceB;
    });


    for (let i = 0; i < data.length; i++)
    {
        var numVertElement    = document.getElementById('jge_bra_vert_'       + bras + '_' + i);
        var distElement       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        var profMaxElement    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        var profMesureElement = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        var nbtourElement     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i);
        var tpsElement        = document.getElementById('jge_bra_tps_'        + bras + '_' + i);
        var vitesseElement    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);
        var obsElement        = document.getElementById('jge_bra_obs_'        + bras + '_' + i);

        if (numVertElement)
        {
            numVertElement.value    = evalInt(data[i].numVert, '');
            distElement.value       = evalFloat(data[i].distance, 0);
            profMaxElement.value    = evalFloat(data[i].profMax, 0);
            profMesureElement.value = evalFloat(data[i].profMesure, 0);
            nbtourElement.value     = evalInt(data[i].nbTour, 0);
            tpsElement.value        = evalInt(data[i].tps, 0);
            vitesseElement.value    = evalFloat(data[i].vitesse, 0);
            obsElement.value        = data[i].obs;
        }
    }
}


// -----------------------------------------------------------------------------
// calc_vitesse_pts()
// -----------------------------------------------------------------------------
function calc_vitesse_pts(bras)
{
    var champs_saisi = document.getElementById('select_saisie');
    var saisieVal;
    if (champs_saisi) {
        saisieVal = parseInt(champs_saisi.value, 10);
    } else if (typeof menuStates !== 'undefined' && menuStates['jge-pts-saisie']) {
        saisieVal = parseInt(menuStates['jge-pts-saisie'], 10);
    }
    if (isNaN(saisieVal) || !saisieVal) { saisieVal = 1; }

    var selectElementHelice = document.getElementById('select_helice_pts');
    var selectValueHelice = selectElementHelice ? selectElementHelice.value : '';

    var l1 = 0; var a1 = 0; var b1 = 0;
    var l2 = 0; var a2 = 0; var b2 = 0;
    var a3 = 0; var b3 = 0;
    var coefA = 0; var coefB = 0;

    // No propeller selected -> no velocity equation available. Abort velocity
    // computation safely (the Validate guard normally prevents reaching here,
    // but other call paths must not crash on a missing selection).
    if (!selectValueHelice || selectValueHelice === '0') { return; }

    var l1Elt = document.getElementById('l1_' + selectValueHelice);
    var a1Elt = document.getElementById('a1_' + selectValueHelice);
    var b1Elt = document.getElementById('b1_' + selectValueHelice);
    var l2Elt = document.getElementById('l2_' + selectValueHelice);
    var a2Elt = document.getElementById('a2_' + selectValueHelice);
    var b2Elt = document.getElementById('b2_' + selectValueHelice);
    var a3Elt = document.getElementById('a3_' + selectValueHelice);
    var b3Elt = document.getElementById('b3_' + selectValueHelice);

    // Coefficient fields missing (e.g. propeller row not rendered) -> abort.
    if (!l1Elt || !a1Elt || !b1Elt || !l2Elt || !a2Elt || !b2Elt || !a3Elt || !b3Elt) { return; }

    if (l1Elt.value != '') { l1 = parseFloat(l1Elt.value); }
    if (a1Elt.value != '') { a1 = parseFloat(a1Elt.value); }
    if (b1Elt.value != '') { b1 = parseFloat(b1Elt.value); }
    if (l2Elt.value != '') { l2 = parseFloat(l2Elt.value); }
    if (a2Elt.value != '') { a2 = parseFloat(a2Elt.value); }
    if (b2Elt.value != '') { b2 = parseFloat(b2Elt.value); }
    if (a3Elt.value != '') { a3 = parseFloat(a3Elt.value); }
    if (b3Elt.value != '') { b3 = parseFloat(b3Elt.value); }

    for (let i = 0; i < nbPts; i++)
    {
        let vertElement       = document.getElementById('jge_bra_vert_'       + bras + '_' + i);
        let distElement       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        let profmaxElement    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        let profmesureElement = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        let nbtourElement     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i);
        let tpsElement        = document.getElementById('jge_bra_tps_'        + bras + '_' + i);
        let tourssecElement   = document.getElementById('jge_bra_tourssec_'   + bras + '_' + i);
        let vitesseElement    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);

        let calcul = false;
        let n = 0;
        let nbtourElt;
        let tpsElt;
        let tourssecElt;
        let vitesseElt;

        if (vitesseElement)
        {
            n = 0;

            if (saisieVal == 1)
            {
                if (nbtourElement && tpsElement && !isNaN(parseInt(nbtourElement.value)) && !isNaN(parseInt(tpsElement.value)))
                {
                    nbtourElt = evalFloat(nbtourElement.value, '');
                    tpsElt    = evalFloat(tpsElement.value, '');

                    if (nbtourElt > 0)
                    {
                        n = nbtourElt / tpsElt;
                        tourssecElement.value = n.toFixed(3);
                    }
                    calcul = true;
                }
            }

            if (saisieVal == 2)
            {
                if (tourssecElement && !isNaN(parseFloat(tourssecElement.value)))
                {
                    tourssecElt = evalFloat(tourssecElement.value, '');
                    n = tourssecElt;

                    tourssecElement.value = n.toFixed(3);
                    calcul = true;
                }
            }

            if (calcul == true)
            {
                if (n > 0)
                {
                    if ((l1 > 0) && (l1 < 99.99) && (n <= l1))
                    {
                        coefA = a1;
                        coefB = b1;
                    }
                    else if ((l1 > 0) && (l1 < 99.99) && (l2 === 0 || (l2 > 0 && l2 < 99.99)) && (l1 < n))
                    {
                        coefA = a2;
                        coefB = b2;
                    }
                    else if ((l2 > 0) && (l2 < 99.99) && (l2 < n))
                    {
                        coefA = a3;
                        coefB = b3;
                    }
                    vitesseElt = (coefA * n + coefB).toFixed(3);
                }
                else { vitesseElt = 0; }
            }
            else
            {
                vitesseElt = '';
            }

            vitesseElement.value = vitesseElt;

            if (vertElement)       document.getElementById('jge_bra_vert_'       + i).value = vertElement.value;
            if (distElement)       document.getElementById('jge_bra_dist_'       + i).value = distElement.value;
            if (profmaxElement)    document.getElementById('jge_bra_profmax_'    + i).value = profmaxElement.value;
            if (profmesureElement) document.getElementById('jge_bra_profmesure_' + i).value = profmesureElement.value;
            if (nbtourElement)     document.getElementById('jge_bra_nbtour_'     + i).value = nbtourElement.value;
            if (tpsElement)        document.getElementById('jge_bra_tps_'        + i).value = tpsElement.value;
            if (tourssecElement)   document.getElementById('jge_bra_tourssec_'   + i).value = tourssecElement.value;
            document.getElementById('jge_bra_vitesse_' + i).value = vitesseElement.value;
        }
    }
}


// -----------------------------------------------------------------------------
// calc_q()
// -----------------------------------------------------------------------------
function calc_q(bras, nb_bras)
{
    var contenuInfo = document.getElementById('contenu_info');

    let numVert = -1;
    let verticalTab = [];
    let ptsByVert = {};
    let count = 0;
    let numPtsVert = 0;

    for (let i = 0; i < nbPts; i++)
    {
        let numVertElement    = document.getElementById('jge_bra_vert_'       + bras + '_' + i);
        let distElement       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        let profMaxElement    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        let profMesureElement = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        let vitesseElement    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);

        if (numVertElement)
        {
            let currentNumVert = parseInt(numVertElement.value);

            if (!isNaN(currentNumVert))
            {
                if (currentNumVert != numVert)
                {
                    numVert = currentNumVert;
                    if (!ptsByVert[numVert])
                    {
                        ptsByVert[numVert] = [];
                    }

                    verticalTab[numVert] =
                    {
                        distance: parseFloat(distElement.value) || 0,
                        profMax:  parseFloat(profMaxElement.value) || 0,
                        count: 0
                    };
                }

                ptsByVert[numVert].push(
                {
                    profMesure: parseFloat(profMesureElement.value) || 0,
                    vitesse:    parseFloat(vitesseElement.value) || 0
                });

                verticalTab[numVert].count = ptsByVert[numVert].length;
            }
        }
    }

    if (verticalTab.length > 0)
    {
        let vSurfTot = 0;
        let vSurfPrec = 0;
        let profMoy = 0;
        let profMaxPrec = 0;
        let surfaceMouillee = 0;
        let perimetreMouillee = 0;
        let largeurProfil = verticalTab[(verticalTab.length - 1)].distance;
        let distancePrec = 0;

        let debitTotal = 0;
        let debitPrec = 0;
        let vitesseMoy = 0;
        let vTrend;

        // Reset the per-verticale velocity store for this bras (exact values).
        jgeVertVelocities[bras] = [];

        for (let vert in verticalTab)
        {
            let vPts = new Array(2);
            let pPts = new Array(2);

            let vAvant = 0;
            let vActuel = 0;
            let pAvant = 0;
            let pActuel = 0;
            let vCalc = 0;
            let profCalc = 0;

            let aire = 0;
            let vSurf = 0;
            let vFond = 0;
            let profMaxVert = verticalTab[vert].profMax;
            let distanceVert = verticalTab[vert].distance;

            let distanceCalc = distanceVert - distancePrec;
            distancePrec = distanceVert;

            ptsByVert[numVert].sort(function(a, b)
            {
                return b.profMesure - a.profMesure;
            });

            for (let i = 0; i < verticalTab[vert].count; i++)
            {
                vCalc = ptsByVert[vert][i].vitesse;
                profCalc = profMaxVert - ptsByVert[vert][i].profMesure;

                if (verticalTab[vert].count == 1)
                {
                    vAvant = vCalc * 0.95;
                    vActuel = vCalc;
                    pAvant = 0;
                    pActuel = profCalc;
                    aire = (pActuel - pAvant) * (vActuel + vAvant) / 2;

                    vAvant = vCalc;
                    vActuel = vCalc * 0.7;
                    pAvant = pActuel;
                    pActuel = profMaxVert;

                    aire = aire + (pActuel - pAvant) * ((vActuel + vAvant) / 2);

                    vSurf = vCalc * 0.95;
                    vFond = vCalc * 0.7;
                }

                if (verticalTab[vert].count > 1)
                {
                    if (i <= 1)
                    {
                        vPts[i] = vCalc;
                        pPts[i] = profCalc;
                    }
                    else
                    {
                        vPts[0] = vPts[1];
                        pPts[0] = pPts[1];
                        vPts[1] = vCalc;
                        pPts[1] = profCalc;
                    }

                    if (i == 1)
                    {
                        vTrend = linearTrend(vPts, pPts, 0);

                        if (vTrend)
                        {
                            vAvant = vTrend * 0.99;
                            pAvant = 0;
                            vActuel = vPts[0];
                            pActuel = pPts[0];
                            aire = aire + (pActuel - pAvant) * (vActuel + vAvant) / 2;

                            vSurf = vTrend * 0.99;
                        }
                    }

                    if (i >= 1 && !(i == 2 && verticalTab[vert].count == 2))
                    {
                        vAvant = vActuel;
                        vActuel = vPts[1];
                        pAvant = pActuel;
                        pActuel = pPts[1];
                        aire = aire + (pActuel - pAvant) * (vActuel + vAvant) / 2;
                    }

                    if (i == (verticalTab[vert].count - 1))
                    {
                        vAvant = vActuel;
                        vTrend = linearTrend(vPts, pPts, profMaxVert);

                        if (vTrend)
                        {
                            vActuel = vTrend * 0.7;
                            if (vActuel < 0) { vActuel = 0; }
                            pAvant = pActuel;
                            pActuel = profMaxVert;
                            aire = aire + (pActuel - pAvant) * (vActuel + vAvant) / 2;

                            vFond = vTrend * 0.7;
                        }
                    }
                }
            }

            vSurfTot = vSurfTot + distanceCalc * (vSurf + vSurfPrec) / 2;
            profMoy = profMoy + distanceCalc * (profMaxVert + profMaxPrec) / 2;
            surfaceMouillee = surfaceMouillee + distanceCalc * profMaxVert;
            perimetreMouillee = perimetreMouillee + Math.sqrt(Math.pow(distanceCalc, 2) + Math.pow((profMaxVert - profMaxPrec), 2));

            debitTotal = debitTotal + distanceCalc * (aire + debitPrec) / 2;

            // Store this verticale's exact velocities for the velocity curves:
            // vSurf (extrapolated to the surface) and vMoy = integrated area /
            // depth (the depth-averaged velocity of the verticale).
            var vMoyVert = (profMaxVert > 0) ? (aire / profMaxVert) : 0;
            jgeVertVelocities[bras].push({
                distance: distanceVert,
                vSurf:    vSurf,
                vMoy:     vMoyVert
            });

            vSurfPrec = vSurf;
            profMaxPrec = profMaxVert;
            debitPrec = aire;
        }

        vSurfTot = vSurfTot / largeurProfil;
        profMoy = profMoy / largeurProfil;
        debitTotal = debitTotal * 1.02;
        vitesseMoy = debitTotal / surfaceMouillee;
        let rh = surfaceMouillee / perimetreMouillee;

        let h_ech_first = evalFloat(document.getElementById('h_ech_first_' + bras).value, 0);
        let h_ech_end   = evalFloat(document.getElementById('h_ech_end_'   + bras).value, 0);
        let hMoy_terrain = evalFloat((h_ech_first + h_ech_end) / 2, 0);


        document.getElementById('depouil_bras_q_' + bras).value = debitTotal.toFixed(3);
        document.getElementById('depouil_bras_hmoy_' + bras).value = hMoy_terrain.toFixed(0);

        document.getElementById('depouil_bras_vmoy_'  + bras).value = vitesseMoy.toFixed(3);
        document.getElementById('depouil_bras_vsurf_' + bras).value = vSurfTot.toFixed(3);
        document.getElementById('depouil_bras_rh_'    + bras).value = rh.toFixed(3);

        document.getElementById('depouil_bras_surfmouil_' + bras).value = surfaceMouillee.toFixed(3);
        document.getElementById('depouil_bras_perimouil_' + bras).value = perimetreMouillee.toFixed(3);

        document.getElementById('depouil_bras_profmoy_' + bras).value = profMoy.toFixed(3);
        document.getElementById('depouil_bras_distmax_' + bras).value = largeurProfil.toFixed(2);

        // Header totals now come from a single shared helper, which skips empty
        // arms. The previous loop added parseFloat('') for a saved-but-blank arm
        // and turned the whole total into NaN.
        syncHeaderFromBras();

        // i18n: success message displayed in the info banner.
        var msg_info = t('MSG_CALC_OK', 'Le calcul a bien été réalisé.');
        msg_info += "<br>" + t('MSG_CALC_OK_REMIND',
            "N'oubliez pas d'enregistrer la fiche de Jaugeage, sinon les données seront perdues");

        if (contenuInfo)
        {
            contenuInfo.innerHTML = msg_info;
            contenuInfo.style.display = 'block';
            contenuInfo.style.border = '2px solid #09886d';
        }

        // Mark the gauging sheet as having unsaved changes: yellow badge on every
        // arm tab, Save button highlighted, beforeunload guard armed.
        showUnsavedWarning();
    }
    else
    {
        // i18n: error banner shown when the table is empty.
        var msg_info = t('MSG_CALC_ERR', 'Erreur !!!');
        msg_info += "<br>" + t('MSG_CALC_ERR_RUN',
            "Le calcul du Jaugeage n'a pas pû s'exécuter");
        msg_info += "<br>" + t('MSG_CALC_ERR_EMPTY',
            "Aucune donnée n'a été saisie");

        if (contenuInfo)
        {
            contenuInfo.innerHTML = msg_info;
            contenuInfo.style.display = 'block';
            contenuInfo.style.border = '2px solid #930000';
        }
    }
}


// -----------------------------------------------------------------------------
// f_editgraph_jge()
// Builds (or rebuilds) the interactive Plotly chart for one arm.
// -----------------------------------------------------------------------------
function f_editgraph_jge(bras = 0)
{
    // i18n: trace names, tooltip labels, and axis titles.
    const tracePointsName = t('TRACE_POINTS_NAME', 'Points du JGE');
    const traceBedName    = t('TRACE_BED_NAME',    'Profil du lit');
    const ttDistance      = t('TT_DISTANCE',       'Distance');
    const ttDepth         = t('TT_DEPTH',          'Profondeur');
    const ttVelocity      = t('TT_VELOCITY',       'Vitesse');
    const ttObservation   = t('TT_OBSERVATION',    'Observation');
    const axisDistance    = t('AXIS_DISTANCE',     'Distance [m]');
    const axisDepth       = t('AXIS_DEPTH',        'Profondeur [m]');

    // Series for the measurement points (blue trace).
    var xData = [];
    var yData = [];
    var vData = [];
    var oData = [];
    // Series for the bed profile (red trace).
    var xDataT = [];
    var yDataT = [];
    var oDataT = [];

    var distValue = 0;
    var profMaxValue = 0;
    var profMesureValue = 0;
    var vitesseElementValue = 0;

    var plotText = document.getElementById('plot_text_' + bras);

    // Track the last (distance, profMax) we pushed onto the bed-profile trace
    // so that a vertical with several measurements does not get plotted as
    // multiple stacked bed-profile points.
    var lastBedDist    = Number.NEGATIVE_INFINITY;
    var lastBedProfMax = Number.NEGATIVE_INFINITY;

    for (let i = 0; i < nbPts; i++)
    {
        var distElement       = document.getElementById('jge_bra_dist_'       + bras + '_' + i);
        var profmaxElement    = document.getElementById('jge_bra_profmax_'    + bras + '_' + i);
        var profmesureElement = document.getElementById('jge_bra_profmesure_' + bras + '_' + i);
        var nbtourElement     = document.getElementById('jge_bra_nbtour_'     + bras + '_' + i);
        var vitesseElement    = document.getElementById('jge_bra_vitesse_'    + bras + '_' + i);

        var obsElement = document.getElementById('jge_bra_obs_' + bras + '_' + i);
        var obsElementValue = '-';
        if (obsElement && obsElement.value.trim() !== '')
        {
            obsElementValue = obsElement.value;
        }

        var hasDist       = distElement && distElement.value !== '' && !isNaN(parseFloat(distElement.value));
        var hasProfMax    = profmaxElement && profmaxElement.value !== '' && !isNaN(parseFloat(profmaxElement.value));
        var hasProfMesure = profmesureElement && profmesureElement.value !== '' && !isNaN(parseFloat(profmesureElement.value));

        if (!hasDist || !hasProfMax) {
            continue;
        }

        distValue = parseFloat(distElement.value);
        profMaxValue = (-1) * parseFloat(profmaxElement.value);

        if (distValue !== lastBedDist || profMaxValue !== lastBedProfMax) {
            xDataT.push(distValue);
            yDataT.push(profMaxValue);
            oDataT.push(obsElementValue);
            lastBedDist    = distValue;
            lastBedProfMax = profMaxValue;
        }

        if (!hasProfMesure) {
            continue;
        }

        profMesureValue = (-1) * (parseFloat(profmaxElement.value) - parseFloat(profmesureElement.value));

        if (vitesseElement && vitesseElement.value !== '') {
            vitesseElementValue = vitesseElement.value;
        } else {
            vitesseElementValue = '';
        }

        xData.push(distValue);
        yData.push(profMesureValue);
        vData.push(vitesseElementValue);
        oData.push(obsElementValue);
    }

    var Xmax = Math.max(...xData);
    var Ymin = Math.min(...yData);
    var YminT = Math.min(...yDataT);

    var YminEch = Ymin;
    if (YminT < Ymin) { YminEch = YminT; }
    var Ymax = Math.abs(YminEch) * 0.05;


    // Trace 1: measurement points (blue).
    var data_profil =
    {
        hovermode: 'closest',
        x: xData,
        y: yData,
        customdata: vData,
        text: oData,

        name: tracePointsName,

        // Tooltip uses the i18n labels. The trace name re-uses tracePointsName
        // so the colour-coded title and the legend stay in sync.
        hovertemplate:  '<span style="color:#1f77b4"><b>' + tracePointsName + '</b></span><br>' +
                    '<b>' + ttDistance    + ' :</b> %{x:.2f} m<br>' +
                    '<b>' + ttDepth       + ' :</b> %{y:.2f} m<br>' +
                    '<b>' + ttVelocity    + ' :</b> %{customdata} m/s<br>' +
                    '<b>' + ttObservation + ' :</b> %{text}' +
                    '<extra></extra>',

        hoverlabel: {
                        bgcolor: 'rgba(255, 255, 255, 0.95)',
                        bordercolor: '#1f77b4',
                        font: { family: 'Arial, sans-serif', size: 14, color: '#333' },
                        align: 'left'
                    },

        mode: 'markers',
        type: 'scatter',
        marker: { size: 8 },
    };

    // Trace 2: channel bed profile (red).
    var data_profil_2 =
    {
        hovermode: 'closest',
        x: xDataT,
        y: yDataT,
        text: oDataT,

        name: traceBedName,

        hovertemplate:  '<span style="color:#C44545"><b>' + traceBedName + '</b></span><br>' +
                    '<b>' + ttDistance    + ' :</b> %{x:.2f} m<br>' +
                    '<b>' + ttDepth       + ' :</b> %{y:.2f} m<br>' +
                    '<b>' + ttObservation + ' :</b> %{text}' +
                    '<extra></extra>',

        hoverlabel: {
                        bgcolor: 'rgba(255, 255, 255, 0.95)',
                        bordercolor: '#C44545',
                        font: { family: 'Arial, sans-serif', size: 14, color: '#333' },
                        align: 'left'
                    },

        mode: 'markers+lines',
        type: 'scatter',
        marker: {
            size: 8,
            color: 'red'
        }
    };

    // ---- Velocity curves per verticale (exact values from calc_q) ----------
    // Same as the popup graph: surface + depth-averaged velocity on a compact
    // secondary Y axis, 0 aligned with the water line. Shown only after calc.
    var veloTab    = jgeVertVelocities[bras] || [];
    var veloX2     = [], veloVSurf2 = [], veloVMoy2 = [];
    var vMaxVal2   = 0;
    veloTab.forEach(function(v)
    {
        veloX2.push(v.distance);
        veloVSurf2.push(v.vSurf);
        veloVMoy2.push(v.vMoy);
        if (v.vSurf > vMaxVal2) { vMaxVal2 = v.vSurf; }
        if (v.vMoy  > vMaxVal2) { vMaxVal2 = v.vMoy;  }
    });
    var hasVelo2 = (veloX2.length > 0 && vMaxVal2 > 0);

    var depthBottom2 = (YminEch * 1.2);
    var depthTop2    = Math.abs(YminEch) * 0.20;
    if (depthTop2 <= 0) { depthTop2 = 0.05; }
    var totalSpan2 = depthTop2 - depthBottom2;
    var fTop2      = depthTop2 / totalSpan2;
    var vAxisMax2  = (vMaxVal2 > 0) ? (vMaxVal2 / 0.85) : 1;
    var vAxisMin2  = -vAxisMax2 * (1 - fTop2) / fTop2;

    var data_vsurf2 = {
        x: veloX2, y: veloVSurf2, yaxis: 'y2',
        name: t('TRACE_VSURF_NAME', 'Vitesse de surface'),
        hovertemplate: '<b>' + t('TRACE_VSURF_NAME', 'Vitesse de surface') + '</b><br>' +
                       '<b>' + ttDistance + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttVelocity + ' :</b> %{y:.3f} m/s<extra></extra>',
        mode: 'markers+lines', type: 'scatter',
        marker: { size: 6, color: '#5b3fbf' }, line: { color: '#5b3fbf' }
    };
    var data_vmoy2 = {
        x: veloX2, y: veloVMoy2, yaxis: 'y2',
        name: t('TRACE_VMOY_NAME', 'Vitesse moyenne'),
        hovertemplate: '<b>' + t('TRACE_VMOY_NAME', 'Vitesse moyenne') + '</b><br>' +
                       '<b>' + ttDistance + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttVelocity + ' :</b> %{y:.3f} m/s<extra></extra>',
        mode: 'markers+lines', type: 'scatter',
        marker: { size: 6, color: '#e07b00' }, line: { color: '#e07b00', dash: 'dot' }
    };

    var config =
    {
        responsive: true,
        doubleClickDelay: 1000,

        displaylogo: false,
        displayModeBar: true,
        scrollZoom: true,
        modeBarOrientation: 'v',

        modeBarButtons: [
            [
                {
                    name: 'Export SVG',
                    icon: Plotly.Icons.disk,
                    click: function (gd) {
                        Plotly.downloadImage(gd, { format: 'svg', filename: 'mon_grap' });
                    }
                },
                'toImage',
                'zoom2d',
                'pan2d',
                'resetScale2d'
            ]
        ],

        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2']
    };

    var layout_profil =
    {
        xaxis:
        {
            title: {
                text: axisDistance,
                standoff: 5
            },

            tickfont: { size: 11 },
            titlefont: {
                family: 'roboto, arial, helvetica',
                size: 14,
                bold: true,
                color: '#000000'
            },

            tickformat: ',.2f',
            tickangle: 0,
            ticklen: 5,
            showline: true,
            linewidth: 1,
            automargin: true,
            range: [-0.15, (Xmax * 1.1)],
            side: 'top'
        },

        yaxis:
        {
            title: {
                text: axisDepth,
                standoff: 10
            },
            tickfont: { size: 11 },
            titlefont: {
                family: 'roboto, arial, helvetica',
                size: 14,
                bold: true,
                color: '#000000'
            },

            tickformat: '.2f',
            ticklen: 5,
            showline: true,
            linewidth: 1,
            automargin: true,
            range: [depthBottom2, depthTop2],
        },

        yaxis2:
        {
            title: { text: t('AXIS_VELOCITY', 'Vitesse [m/s]') },
            titlefont: { family: 'roboto, arial, helvetica', size: 12, color: '#000000' },
            tickfont: { size: 10, color: '#000000' },
            overlaying: 'y', side: 'right',
            range: [vAxisMin2, vAxisMax2],
            zeroline: false, showgrid: false, showline: true, linewidth: 1, linecolor: '#000',
            automargin: true
        },

        hovermode: '',

        hoverlabel: { bgcolor: '#fff', font: { size: 16, color: '#000' } },
        cursor: 'pointer',
        margin: { l: 100, r: 40, t: 70, b: 20 },
        showlegend: true,
        legend:
        {
            x: 0,
            y: 0,
            orientation: 'v',
        },
    };

    if (Xmax > 0)
    {
        plotText.style.display = 'none';

        var tracesTab = [data_profil, data_profil_2];
        if (hasVelo2) { tracesTab.push(data_vsurf2, data_vmoy2); }

        setTimeout(function () {
            Plotly.newPlot('plot_jge_bras_' + bras, tracesTab, layout_profil, config);
        }, 200);
    }
}


// -----------------------------------------------------------------------------
// f_editgraph_jge_popup()
// Cross-section graph dedicated to the points-entry popup (id 'plot_jge_popup').
// Same traces / hovertemplates / axes as f_editgraph_jge(), with three extras:
//   - measurement points coloured by verticale number,
//   - a translucent "water column" band per verticale (surface -> bed point),
//   - the row currently being edited drawn as a large yellow star.
// -----------------------------------------------------------------------------
function f_editgraph_jge_popup(bras = 0)
{
    var plotDiv = document.getElementById('plot_jge_popup');
    if (!plotDiv) { return; }

    // i18n labels (same keys as f_editgraph_jge).
    const tracePointsName = t('TRACE_POINTS_NAME', 'Points du JGE');
    const traceBedName    = t('TRACE_BED_NAME',    'Profil du lit');
    const ttDistance      = t('TT_DISTANCE',       'Distance');
    const ttDepth         = t('TT_DEPTH',          'Profondeur');
    const ttVelocity      = t('TT_VELOCITY',       'Vitesse');
    const ttObservation   = t('TT_OBSERVATION',    'Observation');
    const ttVerticale     = t('TT_VERTICALE',      'Verticale');
    const axisDistance    = t('AXIS_DISTANCE',     'Distance [m]');
    const axisDepth       = t('AXIS_DEPTH',        'Profondeur [m]');

    // Measurement points (one entry per measured row).
    var xData = [], yData = [], vData = [], oData = [], cData = [], sData = [], symData = [], wData = [];
    // Bed profile (one entry per distinct distance/profmax).
    var xDataT = [], yDataT = [], oDataT = [];

    // Per-verticale aggregation for the background bands.
    var vertGroups = {}; // numVert -> { dist, profmax }

    var lastBedDist    = Number.NEGATIVE_INFINITY;
    var lastBedProfMax = Number.NEGATIVE_INFINITY;

    for (let i = 0; i < nbPts; i++)
    {
        var vertElement       = document.getElementById('jge_bra_vert_' + i);
        var distElement       = document.getElementById('jge_bra_dist_' + i);
        var profmaxElement    = document.getElementById('jge_bra_profmax_' + i);
        var profmesureElement = document.getElementById('jge_bra_profmesure_' + i);
        var vitesseElement    = document.getElementById('jge_bra_vitesse_' + i);
        var obsElement        = document.getElementById('jge_bra_obs_' + i);

        var obsValue = '-';
        if (obsElement && obsElement.value.trim() !== '') { obsValue = obsElement.value; }

        var hasDist       = distElement && distElement.value !== '' && !isNaN(parseFloat(distElement.value));
        var hasProfMax    = profmaxElement && profmaxElement.value !== '' && !isNaN(parseFloat(profmaxElement.value));
        var hasProfMesure = profmesureElement && profmesureElement.value !== '' && !isNaN(parseFloat(profmesureElement.value));

        if (!hasDist || !hasProfMax) { continue; }

        var distValue    = parseFloat(distElement.value);
        var profMaxValue = (-1) * parseFloat(profmaxElement.value);
        var numVert      = vertElement ? vertElement.value : '';

        // Bed profile point (deduplicated on distance+profmax).
        if (distValue !== lastBedDist || profMaxValue !== lastBedProfMax)
        {
            xDataT.push(distValue);
            yDataT.push(profMaxValue);
            oDataT.push(obsValue);
            lastBedDist    = distValue;
            lastBedProfMax = profMaxValue;
        }

        // Track verticale extent for the band (surface -> bed point).
        var nv = parseInt(numVert, 10);
        if (!isNaN(nv) && nv >= 1)
        {
            if (!vertGroups[nv]) { vertGroups[nv] = { dist: distValue, profmax: parseFloat(profmaxElement.value) }; }
        }

        if (!hasProfMesure) { continue; }

        var profMesureValue = (-1) * (parseFloat(profmaxElement.value) - parseFloat(profmesureElement.value));
        var vitesseValue    = (vitesseElement && vitesseElement.value !== '') ? vitesseElement.value : '';

        var isActive    = (i === jgeActiveRow);
        var isActiveVert = (jgeActiveVert !== '' && String(numVert) === String(jgeActiveVert));

        xData.push(distValue);
        yData.push(profMesureValue);
        vData.push(vitesseValue);
        oData.push(obsValue);
        wData.push(numVert === '' ? '-' : numVert);
        // Colour only the active verticale; the active point is a larger yellow dot.
        cData.push(isActive ? '#EFB036' : (isActiveVert ? jgeVertColor(numVert) : '#1f77b4'));
        sData.push(isActive ? 18 : 9);
        symData.push('circle');
    }

    var Xmax  = xData.length  ? Math.max.apply(null, xData)  : 0;
    var Ymin  = yData.length  ? Math.min.apply(null, yData)  : 0;
    var YminT = yDataT.length ? Math.min.apply(null, yDataT) : 0;
    var YminEch = (YminT < Ymin) ? YminT : Ymin;
    var Ymax = Math.abs(YminEch) * 0.05;

    // Background band: only for the currently active verticale (if any).
    var bands = [];
    Object.keys(vertGroups).forEach(function(nv)
    {
        if (jgeActiveVert === '' || String(nv) !== String(jgeActiveVert)) { return; }
        var g = vertGroups[nv];
        var halfW = (Xmax > 0) ? (Xmax * 0.018) : 0.08;
        if (halfW < 0.04) { halfW = 0.04; }
        bands.push({
            type: 'rect', xref: 'x', yref: 'y', layer: 'below',
            x0: g.dist - halfW, x1: g.dist + halfW,
            y0: (-1) * g.profmax, y1: 0,
            fillcolor: jgeVertColor(nv), opacity: 0.13, line: { width: 0 }
        });
    });

    // Trace 1: measurement points, coloured by verticale.
    var data_points =
    {
        x: xData, y: yData, customdata: vData, text: oData,
        meta: wData,
        name: tracePointsName,
        hovertemplate: '<span style="color:#185FA5"><b>' + tracePointsName + '</b></span><br>' +
                       '<b>' + ttVerticale   + ' :</b> %{meta}<br>' +
                       '<b>' + ttDistance    + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttDepth       + ' :</b> %{y:.2f} m<br>' +
                       '<b>' + ttVelocity    + ' :</b> %{customdata} m/s<br>' +
                       '<b>' + ttObservation + ' :</b> %{text}' +
                       '<extra></extra>',
        hoverlabel: { bgcolor: 'rgba(255,255,255,0.95)', bordercolor: '#185FA5',
                      font: { family: 'Arial, sans-serif', size: 14, color: '#333' }, align: 'left' },
        mode: 'markers', type: 'scatter',
        marker: { size: sData, color: cData, symbol: symData, line: { color: '#000', width: 0.5 } }
    };

    // Trace 2: channel bed profile (red).
    var data_bed =
    {
        x: xDataT, y: yDataT, text: oDataT,
        name: traceBedName,
        hovertemplate: '<span style="color:#C44545"><b>' + traceBedName + '</b></span><br>' +
                       '<b>' + ttDistance    + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttDepth       + ' :</b> %{y:.2f} m<br>' +
                       '<b>' + ttObservation + ' :</b> %{text}' +
                       '<extra></extra>',
        hoverlabel: { bgcolor: 'rgba(255,255,255,0.95)', bordercolor: '#C44545',
                      font: { family: 'Arial, sans-serif', size: 14, color: '#333' }, align: 'left' },
        mode: 'markers+lines', type: 'scatter',
        marker: { size: 8, color: 'red' }, line: { color: '#C44545' }
    };

    // ---- Velocity curves per verticale (exact values from calc_q) ----------
    // Surface velocity + depth-averaged velocity, on a secondary Y axis kept
    // deliberately compact (the velocity is an indication, not the main read).
    var velo       = jgeVertVelocities[bras] || [];
    var veloX      = [], veloVSurf = [], veloVMoy = [];
    var vMaxVal    = 0;

    velo.forEach(function(v)
    {
        veloX.push(v.distance);
        veloVSurf.push(v.vSurf);
        veloVMoy.push(v.vMoy);
        if (v.vSurf > vMaxVal) { vMaxVal = v.vSurf; }
        if (v.vMoy  > vMaxVal) { vMaxVal = v.vMoy;  }
    });

    var hasVelo = (veloX.length > 0 && vMaxVal > 0);

    var data_vsurf = {
        x: veloX, y: veloVSurf, yaxis: 'y2',
        name: t('TRACE_VSURF_NAME', 'Vitesse de surface'),
        hovertemplate: '<b>' + t('TRACE_VSURF_NAME', 'Vitesse de surface') + '</b><br>' +
                       '<b>' + ttDistance + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttVelocity + ' :</b> %{y:.3f} m/s<extra></extra>',
        mode: 'markers+lines', type: 'scatter',
        marker: { size: 6, color: '#5b3fbf' }, line: { color: '#5b3fbf' }
    };

    var data_vmoy = {
        x: veloX, y: veloVMoy, yaxis: 'y2',
        name: t('TRACE_VMOY_NAME', 'Vitesse moyenne'),
        hovertemplate: '<b>' + t('TRACE_VMOY_NAME', 'Vitesse moyenne') + '</b><br>' +
                       '<b>' + ttDistance + ' :</b> %{x:.2f} m<br>' +
                       '<b>' + ttVelocity + ' :</b> %{y:.3f} m/s<extra></extra>',
        mode: 'markers+lines', type: 'scatter',
        marker: { size: 6, color: '#e07b00' }, line: { color: '#e07b00', dash: 'dot' }
    };

    // --- Axis ranges with the velocity curves above the water line ---------
    // Depth axis: from the bottom (negative) up to a headroom ABOVE the water
    // line large enough to host the velocity curves (≈60% of the max depth).
    var depthBottom = (YminEch * 1.2);            // negative (channel bed)
    var depthTop    = Math.abs(YminEch) * 0.20;   // positive headroom for velocities
    if (depthTop <= 0) { depthTop = 0.05; }

    // Velocity axis: 0 must coincide with depth 0 (the water line). The water
    // line is at fraction (depthTop / total) from the TOP of the plot.
    var totalSpan = depthTop - depthBottom;
    var fTop      = depthTop / totalSpan;          // share of height above water
    // Make the velocity curves fill that top band: vMax maps to the top edge.
    var vAxisMax  = (vMaxVal > 0) ? (vMaxVal / 0.85) : 1; // 0.85 = small top margin
    var vAxisMin  = -vAxisMax * (1 - fTop) / fTop; // keeps velocity 0 on the water line


    var config =
    {
        responsive: true, doubleClickDelay: 1000,
        displaylogo: false, displayModeBar: true, scrollZoom: true, modeBarOrientation: 'v',
        modeBarButtons: [[
            { name: 'Export SVG', icon: Plotly.Icons.disk,
              click: function (gd) { Plotly.downloadImage(gd, { format: 'svg', filename: 'jge_section' }); } },
            'toImage', 'zoom2d', 'pan2d', 'resetScale2d'
        ]],
        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };

    var layout =
    {
        shapes: bands,
        xaxis: {
            title: { text: axisDistance, standoff: 5 },
            tickfont: { size: 11 },
            titlefont: { family: 'roboto, arial, helvetica', size: 14, color: '#000000' },
            tickformat: ',.2f', tickangle: 0, ticklen: 5, showline: true, linewidth: 1,
            automargin: true, range: [-0.15, (Xmax * 1.1)], side: 'top'
        },
        yaxis: {
            title: { text: axisDepth, standoff: 10 },
            tickfont: { size: 11 },
            titlefont: { family: 'roboto, arial, helvetica', size: 14, color: '#000000' },
            tickformat: '.2f', ticklen: 5, showline: true, linewidth: 1,
            automargin: true, range: [depthBottom, depthTop]
        },
        yaxis2: {
            title: { text: t('AXIS_VELOCITY', 'Vitesse [m/s]') },
            titlefont: { family: 'roboto, arial, helvetica', size: 12, color: '#000000' },
            tickfont: { size: 10, color: '#000000' },
            overlaying: 'y', side: 'right',
            range: [vAxisMin, vAxisMax],
            zeroline: false, showgrid: false, showline: true, linewidth: 1, linecolor: '#000',
            automargin: true
        },
        hovermode: 'closest',
        hoverlabel: { bgcolor: '#fff', font: { size: 16, color: '#000' } },
        margin: { l: 70, r: 35, t: 60, b: 35 },
        showlegend: true,
        legend: { x: 0, y: 0, orientation: 'v' }
    };

    if (Xmax > 0)
    {
        var traces = [data_bed, data_points];
        if (hasVelo) { traces.push(data_vsurf, data_vmoy); }
        Plotly.react('plot_jge_popup', traces, layout, config);
    }
    else
    {
        Plotly.purge('plot_jge_popup');
    }
}


// -----------------------------------------------------------------------------
// highlightPopupPoint()
// Redraws the popup cross-section so the active row's point becomes a yellow
// star and only the active verticale is coloured (points + band). A full
// redraw is used because the band (a layout shape) cannot be changed by
// Plotly.restyle — and Plotly.react makes this cheap.
// -----------------------------------------------------------------------------
function highlightPopupPoint(bras, row)
{
    f_editgraph_jge_popup(bras);
}
<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Service table — included by gestion_service.php.
Contains the container div where the AJAX-rendered table is injected,
plus the JS used by the page:
  - affiche_service()
      Fetches the current table HTML from process_tab_service.php and
      injects it into the container.
  - delete_service(idService)
      Actually sends the delete request to process_delservice.php and
      refreshes the table. NEVER called directly from a click anymore —
      always goes through confirmDeleteService() first (math-challenge
      anti-misclick popup).
  - confirmDeleteService(id, name)
      Shows the math-challenge confirmation popup; runs delete_service()
      only when the user solves the challenge correctly.
----------------------------------------
*/
echo "<div id='onglet_contenu'>\n";
    echo "<div id='boite1' class='first'>\n";
        echo "<div id='tab_dataservice' class='table-container'>";
        echo "</div>\n";
    echo "<hr>\n";
    echo "</div>\n";
echo "<hr>\n";
echo "</div>\n";
?>
<script>

    // Container for the AJAX-loaded table
    var tabDataService = document.getElementById('tab_dataservice');


    // -----------------------------------------------
    // affiche_service()
    // Fetches the service table from the server and injects its HTML
    // into #tab_dataservice. Called on initial page load and after
    // every successful save or delete.
    function affiche_service()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/admin/process_tab_service.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (r['tab_service'])
                {
                    tabDataService.innerHTML = r['htmlcode'];
                }
                else
                {
                    // Empty result set — show the message from the server
                    contenuInfo.innerHTML     = r['message_info'];
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send();
    }

    // Load the table on tab display
    affiche_service();


    // -----------------------------------------------
    // delete_service(idService)
    // Sends the actual AJAX delete request. This is now an internal
    // function — the X button in the table calls confirmDeleteService()
    // first, which runs this only after the user has solved the math
    // challenge.
    function delete_service(idService)
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "include/structure/admin/process_delservice.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                contenuInfo.style.border  = r['del_service']
                    ? '2px solid #09886d'
                    : '2px solid #930000';
                contenuInfo.innerHTML     = r['message_info'];
                contenuInfo.style.display = 'block';

                // Refresh the table to reflect the deletion
                affiche_service();
            }
        };

        // Key name must match what process_delservice.php reads from the payload
        xhr.send(JSON.stringify({ idService: idService }));
    }


    // =================================================================
    // Math-challenge confirmation popup before deletion
    //
    // Same anti-misclick pattern as the measurement-type module
    // (form_type_1.php) and the RC modules: the user must mentally
    // solve a one-liner arithmetic problem before the destructive
    // action runs. Prevents accidental deletions from a stray click.
    // =================================================================

    function confirmDeleteService(idService, serviceName)
    {
        // Bail out if a previous popup is still on screen
        var prev = document.getElementById('service_delete_challenge');
        if (prev) { prev.remove(); }

        // Math challenge (same generator as the RC and type modules)
        var op = ['+', '-', 'x'][Math.floor(Math.random() * 3)];
        var a, b, expected;
        if (op === '+') {
            a = Math.floor(Math.random() * 16) + 5;
            b = Math.floor(Math.random() * 14) + 2;
            expected = a + b;
        } else if (op === '-') {
            a = Math.floor(Math.random() * 21) + 10;
            b = Math.floor(Math.random() * (a - 1)) + 1;
            expected = a - b;
        } else {
            a = Math.floor(Math.random() * 8) + 2;
            b = Math.floor(Math.random() * 8) + 2;
            expected = a * b;
        }

        var overlay = document.createElement('div');
        overlay.id = 'service_delete_challenge';
        overlay.style.cssText =
            'position:fixed;top:0;left:0;width:100vw;height:100vh;'
          + 'background:rgba(0,0,0,0.4);z-index:3000;'
          + 'display:flex;align-items:center;justify-content:center;';

        // Header colour intentionally red — destructive action.
        overlay.innerHTML =
            '<div style="background:#FBF9F1;border-radius:4px;width:460px;max-width:90vw;'
          + 'box-shadow:0 8px 24px rgba(0,0,0,0.3);overflow:hidden;font-family:inherit;">'
          + '<div style="background:#a32d2d;color:#fff;padding:10px 14px;font-size:14px;font-weight:bold;">'
          +   '<?php echo TEXT_SV_DEL_CONFIRM_TITLE; ?>'
          + '</div>'
          + '<div style="padding:14px 16px;font-size:13px;line-height:1.5;color:#333;">'
          +   '<?php echo TEXT_SV_DEL_CONFIRM_MSG; ?>'
          +   '<div style="margin:10px 0;padding:8px 10px;background:#fff7e6;border-left:3px solid #BA7517;font-size:13px;">'
          +     '<b>' + (serviceName || '') + '</b>'
          +   '</div>'
          +   '<div style="margin-top:14px;padding:10px;background:#fff;border:1px solid #ddd;border-radius:3px;">'
          +     '<div style="font-size:12px;color:#666;margin-bottom:6px;"><?php echo TEXT_SV_DEL_CHALLENGE_HINT; ?></div>'
          +     '<div style="display:flex;align-items:center;gap:8px;">'
          +       '<span style="font-size:16px;font-weight:bold;">' + a + ' ' + op + ' ' + b + ' = </span>'
          +       '<input id="service_del_answer" type="text" style="width:60px;font-size:16px;padding:4px;" autofocus>'
          +       '<span id="service_del_feedback" style="font-size:12px;"></span>'
          +     '</div>'
          +   '</div>'
          + '</div>'
          + '<div style="padding:8px 14px 14px;display:flex;justify-content:flex-end;gap:8px;">'
          +   '<button id="service_del_cancel"  class="button_close" style="width:120px;"><?php echo TEXT_BTN_CANCEL; ?></button>'
          +   '<button id="service_del_confirm" class="button"      style="width:140px;opacity:0.45;cursor:not-allowed;" disabled>'
          +     '<?php echo TEXT_US_FT_BTN_DELETE; ?>'
          +   '</button>'
          + '</div>'
          + '</div>';

        document.body.appendChild(overlay);

        var input      = overlay.querySelector('#service_del_answer');
        var feedback   = overlay.querySelector('#service_del_feedback');
        var confirmBtn = overlay.querySelector('#service_del_confirm');
        var cancelBtn  = overlay.querySelector('#service_del_cancel');

        // Subtle hover effect on both buttons — same style as the ETL
        // module's confirmation popup. Skipped for Confirm while it's
        // disabled so the disabled look stays unambiguous.
        [confirmBtn, cancelBtn].forEach(function(btn) {
            btn.addEventListener('mouseenter', function() {
                if (btn.disabled) return;
                btn.style.filter = 'brightness(0.9)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.filter = '';
            });
        });

        function setEnabled(on) {
            confirmBtn.disabled = !on;
            confirmBtn.style.opacity = on ? '1' : '0.45';
            confirmBtn.style.cursor  = on ? 'pointer' : 'not-allowed';
        }

        input.addEventListener('input', function() {
            var v = parseInt(input.value, 10);
            if (input.value === '' || isNaN(v)) { feedback.textContent = ''; setEnabled(false); }
            else if (v === expected)            { feedback.textContent = '\u2713'; feedback.style.color = '#0a7d34'; setEnabled(true); }
            else                                { feedback.textContent = '\u2717'; feedback.style.color = '#a32d2d'; setEnabled(false); }
        });

        function cleanup() {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
        }
        function onKey(e) {
            if (e.key === 'Escape') { cleanup(); }
            if (e.key === 'Enter' && !confirmBtn.disabled) { cleanup(); delete_service(idService); }
        }
        document.addEventListener('keydown', onKey);

        cancelBtn.addEventListener('click', cleanup);
        confirmBtn.addEventListener('click', function() { cleanup(); delete_service(idService); });

        setTimeout(function() { input.focus(); }, 100);
    }

</script>
<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Shared delete-confirmation popup for the gauging equipment tabs
- Included once by gestion_eq_jaugeage.php
- Used by the 3 tabs (propellers / current meters / weights)
- Yes/No confirmation (red header), Escape / X / Cancel to close
- On confirm, calls the matching process_del<type>.php endpoint via AJAX
  and hides the row in place (no page reload)
The delete cross itself is only rendered for equipment NOT linked to a
gauging record (the form files compute that), so this popup is only ever
opened for deletable items. The server still re-checks as a safety net.
----------------------------------------
*/
?>

<div id="box_eq_del" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                            background:rgba(0,0,0,0.45);z-index:9999;
                            align-items:flex-start;justify-content:center;">

    <div style="position:relative;width:460px;max-width:92%;margin-top:8%;
                background-color:#FBF9F1;border-radius:6px;overflow:hidden;
                box-shadow:0 8px 30px rgba(0,0,0,0.35);">

        <p style="margin:0;padding:14px 20px;font-size:17px;font-weight:bold;
                  color:#fff;background-color:#a52834;">
            <?= TEXT_EJ_DEL_CONFIRM_TITLE ?>
        </p>

        <div style="padding:18px 22px;">
            <p style="margin:0 0 18px 0;font-size:14px;color:#333;">
                <span id="eq_del_label" style="font-weight:bold;"></span>
                <br>
                <?= TEXT_EJ_DEL_CONFIRM_MSG ?>
            </p>

            <div style="display:flex;justify-content:flex-end;gap:12px;">
                <input type="button" id="eq_del_cancel"  class="button_close" value="<?= TEXT_EJ_DEL_CONFIRM_CANCEL ?>" style="width:120px;">
                <input type="button" id="eq_del_confirm" class="button"       value="<?= TEXT_EJ_DEL_CONFIRM_OK ?>"     style="width:120px;">
            </div>
        </div>

    </div>

</div>

<script type="text/javascript">

    // -----------------------------------------------
    // Shared delete-confirmation popup for equipment tabs

    var boxEqDel       = document.getElementById('box_eq_del');
    var eqDelLabel     = document.getElementById('eq_del_label');
    var eqDelCancelBtn = document.getElementById('eq_del_cancel');
    var eqDelConfirmBtn = document.getElementById('eq_del_confirm');

    // Maps an equipment type to its endpoint, JSON id key, response flag and row id prefix
    var EQ_DEL_MAP = {
        helice:   { url: 'include/structure/eq_jge/process_delhelice.php',   idKey: 'id_helice',   okKey: 'del_helice',   rowPrefix: 'row_eqh_' },
        moulinet: { url: 'include/structure/eq_jge/process_delmoulinet.php', idKey: 'id_moulinet', okKey: 'del_moulinet', rowPrefix: 'row_eqm_' },
        saumon:   { url: 'include/structure/eq_jge/process_delsaumon.php',   idKey: 'id_saumon',   okKey: 'del_saumon',   rowPrefix: 'row_eqs_' }
    };

    var eqDelPending = { type: null, id: null };


    // Open the confirmation popup. Called from the delete cross of each row.
    function confirmEqDelete(type, id, label)
    {
        if (!EQ_DEL_MAP[type]) { return; }

        eqDelPending.type = type;
        eqDelPending.id   = id;

        eqDelLabel.textContent = label ? label : '';
        boxEqDel.style.display = 'flex';
    }

    function closeEqDel()
    {
        boxEqDel.style.display = 'none';
        eqDelPending.type = null;
        eqDelPending.id   = null;
    }

    // Confirm: send the AJAX delete for the pending equipment
    function runEqDelete()
    {
        var type = eqDelPending.type;
        var id   = eqDelPending.id;
        if (!type || !EQ_DEL_MAP[type]) { return; }

        var cfg     = EQ_DEL_MAP[type];
        var msgInfo = document.getElementById('contenu_info');

        var payload = {};
        payload[cfg.idKey] = id;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', cfg.url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4 && xhr.status === 200)
            {
                var r = JSON.parse(xhr.responseText);

                if (msgInfo)
                {
                    msgInfo.innerHTML     = r['message_info'];
                    msgInfo.style.display = 'block';
                }

                if (r[cfg.okKey])
                {
                    if (msgInfo) { msgInfo.style.border = '2px solid #09886d'; }
                    var row = document.getElementById(cfg.rowPrefix + id);
                    if (row) { row.style.display = 'none'; }
                }
                else
                {
                    if (msgInfo) { msgInfo.style.border = '2px solid #930000'; }
                }

                closeEqDel();
            }
        };

        xhr.send(JSON.stringify(payload));
    }

    eqDelConfirmBtn.addEventListener('click', runEqDelete);
    eqDelCancelBtn.addEventListener('click', closeEqDel);

    document.addEventListener('keydown', function(event)
    {
        if (event.key === 'Escape' && boxEqDel.style.display === 'flex') { closeEqDel(); }
    });

</script>
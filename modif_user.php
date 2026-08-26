<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
File    : modif_user.php
Purpose : User edit page — creates a new user or edits an existing one.

Modes:
  - Edit mode   : loaded with ?ref=<id> — pre-fills the form with existing data
  - New mode    : loaded without ?ref   — empty form for account creation

Tabs:
  - Tab 1 : User information  (form_user_1.php)
  - Tab 2 : Access rights     (form_user_2.php)

Save flow:
  - The Save button triggers saveUser() via JS (no full-page reload)
  - Data is POSTed to process_user_save.php and the result shown inline
----------------------------------------
*/

require('include/application_top.php');

$action  = false;
$modif   = false;
$ref_id  = 0;

// -----------------------------------------------
// CSRF token — generated once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


// -----------------------------------------------
// Edit mode — validate and load existing user data

$login          = '';
$nom            = '';
$prenom         = '';
$email          = '';
$info           = '';
$lang           = '';
$id_service     = 0;
$gestion_data_u = 0;
$parametre_u    = 0;
$config_u       = 0;

if (isset($_GET['ref']) && tep_not_null($_GET['ref']))
{
    // Accept only positive integers as a valid user id
    $ref_id = filter_var($_GET['ref'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($ref_id === false)
    {
        die("Invalid identifier.");
    }

    $modif = true;
}

if ($modif)
{
    // Fetch user record — non-admin accounts only
    $eq_query = tep_db_query(
        $sql_link,
        "SELECT id, id_service, login, nom, prenom, email, info, lang
         FROM "  . TABLE_USER . "
         WHERE id    = " . (int) $ref_id . "
         AND   admin = 0"
    );
    $user = tep_db_fetch_array($eq_query);

    if (empty($user['id']))
    {
        die("User not found.");
    }

    $login      = html_entity_decode(post_secure($sql_link, $user['login']));
    $nom        = html_entity_decode(post_secure($sql_link, $user['nom']));
    $prenom     = html_entity_decode(post_secure($sql_link, $user['prenom']));
    $email      = html_entity_decode(post_secure($sql_link, $user['email']));
    $info       = html_entity_decode(post_secure($sql_link, $user['info']));
    $lang       = $user['lang'];
    $id_service = html_entity_decode(post_secure($sql_link, $user['id_service']));

    // Fetch access rights for this user
    $acces_query = tep_db_query(
        $sql_link,
        "SELECT gestion_data, parametre, config
         FROM " . TABLE_USER_ACCES . "
         WHERE id = " . (int) $ref_id
    );
    $acces = tep_db_fetch_array($acces_query);

    $gestion_data_u = (int) post_secure($sql_link, $acces['gestion_data']);
    $parametre_u    = (int) post_secure($sql_link, $acces['parametre']);
    $config_u       = (int) post_secure($sql_link, $acces['config']);
}


$sql_fromData   = "SELECT DISTINCT id_service, name, description
                   FROM " . TABLE_SERVICE . "
                   ORDER BY id_service ASC";
$fromData_query = tep_db_query($sql_link, $sql_fromData);
while ($fromData = tep_db_fetch_array($fromData_query))
{
    $fromData_array[$fromData['id_service']] = [
        'name'        => html_entity_decode($fromData['name']        ?? ''),
        'description' => html_entity_decode($fromData['description'] ?? ''),
    ];
}


// -----------------------------------------------
// HTML output

require(DIR_WS_STRUCTURE . 'header_web.php');
echo "<body>";

    // Single feedback banner — hidden by default, populated and shown by JS after save
    echo "<div id='contenu_info' style='display:none;'></div>";

    // Full-page loading overlay — shown during the save XHR
    require(DIR_WS_STRUCTURE . 'block_wait.php');

    require(DIR_WS_STRUCTURE . 'header.php');
    include(DIR_WS_BOX       . 'nav_accueil.php');

    echo "<div id='contour_general'>";
        echo "<div id='contenu_centre'>";
            echo "<div id='contenu_box2'>";

                $lien_form = $modif
                    ? tep_href_link('modif_user.php?ref=' . (int) $ref_id)
                    : tep_href_link('modif_user.php');

                // No file upload — multipart/form-data is not needed
                echo "<form name='user' id='formUser' action='" . $lien_form . "' method='post'>";

                    // Hidden fields — user id in edit mode, CSRF token for both modes
                    if ($modif)
                    {
                        echo "<input type='hidden' name='ref_id'     value='" . (int) $ref_id . "'>";
                    }
                    echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token, ENT_QUOTES) . "'>";

                    echo "<h1>";

                        echo "<p style='float:left;margin-right:25px;'>";

                            if ($modif)
                            {
                                echo TEXT_US_EDIT_TITLE_PREFIX;
                                echo "<span style='color:#000;'>" . htmlspecialchars($prenom . ' ' . $nom, ENT_QUOTES) . "</span>";
                            }
                            else
                            {
                                echo TEXT_US_EDIT_TITLE_NEW;
                            }

                        echo "</p>";

                        echo button_return('list_users.php');

                        // Save button — triggers XHR via saveUser(), no full-page reload
                        echo "<input type='button' class='button' id='button_save' name='button_save'"
                           . " style='float:right;margin-left:30px;' value='" . TEXT_STATION_EDIT_SAVE . "'>";

                        

                    echo "</h1>";

                    // Two-tab layout: Information | Access rights
                    echo "<div id='onglet'>";

                        echo "<ul id='menu_onglet'>";
                            echo "<li onclick=\"ChangeOnglet_2(1, 2, 'onglet-', 'contenu-');\""
                               . " id='onglet-1' class='actif'>" . TEXT_US_EDIT_TAB_INFO . "</li>\n";
                            echo "<li onclick=\"ChangeOnglet_2(2, 2, 'onglet-', 'contenu-');\""
                               . " id='onglet-2'>" . TEXT_US_EDIT_TAB_RIGHTS . "</li>\n";
                        echo "</ul>";

                        echo "<div id='contenu-1' class='contenu'>";
                            require(DIR_WS_ADMIN . 'form_user_1.php');
                        echo "</div>";

                        echo "<div id='contenu-2' class='contenu' style='display:none;'>";
                            require(DIR_WS_ADMIN . 'form_user_2.php');
                        echo "</div>";

                    echo "</div>"; // #onglet

                echo "</form>\n";

            echo "</div>"; // #contenu_box2
        echo "</div>"; // #contenu_centre
    echo "</div>"; // #contour_general

    require('include/application_bottom.php');

echo "</body>";
echo "</html>";
?>

<script>

    // -----------------------------------------------
    // Page-level DOM references

    const boxWait     = document.getElementById('box_wait');      // Full-page loading overlay
    const contenuInfo = document.getElementById('contenu_info');  // Inline feedback banner
    const btnSave     = document.getElementById('button_save');   // Save button

    const appTimezone = '<?php echo htmlspecialchars($timezone_php, ENT_QUOTES); ?>';
    const isNewUser = <?php echo $modif ? 'false' : 'true'; ?>;


    // -----------------------------------------------
    // saveUser()
    // Serialises #formUser and POSTs it to process_user_save.php via XHR.
    // Displays the server response inline without reloading the page.

    function saveUser(event)
    {
        event.preventDefault();

        boxWait.style.display = 'block';
        contenuInfo.style.display = 'none';

        var formData = new FormData(document.getElementById('formUser'));

        // Flag consumed by process_user_save.php to identify a save request
        formData.append('button_save', '1');
        formData.append('timezone', appTimezone); 

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/admin/process_user_save.php', true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                boxWait.style.display = 'none';

                if (xhr.status === 200)
                {
                    try
                    {
                        var r = JSON.parse(xhr.responseText);

                        contenuInfo.innerHTML    = r['msg_info'];
                        contenuInfo.style.border = r['erreur']
                            ? '2px solid #930000'
                            : '2px solid #09886d';
                        contenuInfo.style.display = 'block';

                        // New user created successfully — redirect to edit mode after a short delay
                        // so the user can read the confirmation message before the page reloads.
                        // process_user_save.php must return r['id'] on creation.
                        if (!r['erreur'] && isNewUser && r['id'])
                        {
                            setTimeout(function()
                            {
                                window.location.href = 'modif_user.php?ref=' + r['id'];
                            }, 1500);
                        }
                    }
                    catch (e)
                    {
                        // Server returned a non-JSON response (e.g. PHP error)
                        contenuInfo.innerHTML     = 'Unexpected server error. Please try again.';
                        contenuInfo.style.border  = '2px solid #930000';
                        contenuInfo.style.display = 'block';
                    }
                }
                else
                {
                    // HTTP error (500, 403, etc.)
                    contenuInfo.innerHTML     = 'Server error ' + xhr.status + '. Please try again.';
                    contenuInfo.style.border  = '2px solid #930000';
                    contenuInfo.style.display = 'block';
                }
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // sendWelcomeMail(refId)
    // Sends a welcome e-mail to the user via process_user_sendmail.php.
    // Updates the mail icon and inline status message on completion.

    function sendWelcomeMail(refId)
    {
        var infoEl  = document.getElementById('welcome_mail_info');
        var icoWait = document.getElementById('ico-wait');
        var icoMail = document.getElementById('ico-mail');

        infoEl.innerHTML      = '';
        icoMail.style.display = 'none';
        icoWait.style.display = 'block';

        var formData = new FormData();
        formData.append('ref_id', refId);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/admin/process_user_sendmail.php', true);

        xhr.onreadystatechange = function()
        {
            if (xhr.readyState === 4)
            {
                icoWait.style.display = 'none';
                icoMail.style.display = 'block';

                if (xhr.status === 200)
                {
                    try
                    {
                        var r = JSON.parse(xhr.responseText);
                        infoEl.innerHTML     = r['msg_info'];
                        infoEl.style.color   = r['erreur'] ? '#930000' : '#09886d';
                        infoEl.style.display = 'block';
                    }
                    catch (e)
                    {
                        infoEl.innerHTML     = 'Unexpected server error.';
                        infoEl.style.color   = '#930000';
                        infoEl.style.display = 'block';
                    }
                }
                else
                {
                    infoEl.innerHTML     = 'Server error ' + xhr.status + '.';
                    infoEl.style.color   = '#930000';
                    infoEl.style.display = 'block';
                }
            }
        };

        xhr.send(formData);
    }


    // -----------------------------------------------
    // Event listeners — attached here rather than inline in HTML

    btnSave.addEventListener('click', saveUser);

</script>
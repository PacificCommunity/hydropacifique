<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
Popup — Image display block for data chronology information
- Uses the standard #cadre_view_2 popup shell so the header (teal) and the
  close button match the other platform popups (e.g. Data Series Details).
----------------------------------------
*/


echo "<div id='box_img' class='block_view'
            style='position:absolute;width:auto;max-width:85vw;top:20px;left:0;
                   background:none;display:none;'>\n";

    echo "<div id='cadre_view_2' style='display:inline-block;width:auto;margin-top:20px;padding:0;'>\n";

        // Header (first child of #cadre_view_2 -> styled teal by the global CSS)
        echo "<p id='title_img'>";

            echo "<span id='title_img_text'></span>";

            echo "<span id='button_close_img' class='button_close'"
               . " title='" . TEXT_POPUP_CLOSE . "'>X</span>";

        echo "</p>\n";

        // Image container (image injected by affichePhoto())
        echo "<div id='cadre_view_img' style='padding:10px;text-align:center;'></div>\n";

    echo "</div>\n";

echo "</div>\n";

?>


<script>

    var box_img          = document.getElementById('box_img');
    var button_close_img = document.getElementById('button_close_img');

    // Close the popup when the close button is clicked
    document.addEventListener("click", function(event)
    {
        if (event.target.id === 'button_close_img')
        {
            box_img.style.display = "none";
        }

        // Close if clicking outside the popup
        if (event.target === box_img)
        {
            box_img.style.display = "none";
        }
    });

    // Close the popup on Escape key
    document.addEventListener("keydown", function(event)
    {
        if (event.key === "Escape")
        {
            box_img.style.display = "none";
        }
    });

</script>
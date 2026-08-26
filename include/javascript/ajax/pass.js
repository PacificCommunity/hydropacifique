/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Cette page permet de générer un nouveau mot de passe en utilisant js Ajax
----------------------------------------
*/


function pass_reload(id_user_appli,id_user_admin) 
{
    $.ajax(
		{
			type: "POST",
			url: 'include/structure/xhr/pass_xhr.php',
			data: {
				id_user_appli: id_user_appli,
				id_user_admin: id_user_admin
			},
			success: function(responseText) {
				ajax_m(responseText);
			},
			error: function(error) {
				console.error('Une erreur s\'est produite :', error);
        	}
		}
	);

    function ajax_m(responseText) 
	{
        var tmp = responseText.split(":");
        $('#pass_info').css('display', 'block');
        $('#pass').val(tmp[0]);
    }
}

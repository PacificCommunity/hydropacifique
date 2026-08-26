/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/


function evalpass(pass)
{
	var quality = 0;
	
	if (pass.length >= 6)
	{
		quality++;
		
		if (pass.search("[A-Z]") != -1){quality++;}
		
		if (pass.search("[0-9]") != -1){quality++;}
		
		if (pass.length >= 8 || pass.search("[\x20-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E]") != -1)
		{quality++;}
	}

	if (quality == 0)
	{
		document.getElementById("faible").style.background = "none";
		document.getElementById("moyen").style.background = "none";
		document.getElementById("fort").style.background = "none";
	}
	if (quality == 1)
	{
		document.getElementById("faible").style.background = "#F3EDC5";
		document.getElementById("moyen").style.background = "none";
		document.getElementById("fort").style.background = "none";
	}
	if (quality == 2)
	{
		document.getElementById("faible").style.background = "none";
		document.getElementById("moyen").style.background = "#C4C8F4";
		document.getElementById("fort").style.background = "none";
	}
	if (quality > 2)
	{
		document.getElementById("faible").style.background = "none";
		document.getElementById("moyen").style.background = "none";
		document.getElementById("fort").style.background = "#C7F5C2";
	}
}

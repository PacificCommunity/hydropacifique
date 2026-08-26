<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

// MOULINETS
//-------------------------


// Update
$sql_moulinet = "SELECT DISTINCT id
				FROM ".TABLE_MOULINET."
				WHERE num<>'' ORDER BY num ASC";
$moulinet_query = tep_db_query($sql_link,$sql_moulinet);
while ($moulinet = tep_db_fetch_array($moulinet_query)) 
{
    $id_moulinet = $moulinet['id'];

	$num = post_secure($sql_link,$_POST['num_'.$id_moulinet]);
	$fabricant = post_secure($sql_link,$_POST['fabricant_'.$id_moulinet]);
	$obs = post_secure($sql_link,$_POST['obs_'.$id_moulinet]);
	

	tep_db_query($sql_link,"UPDATE ".TABLE_MOULINET." SET num='".$num."',
                                                            fabricant='".$fabricant."',
														    obs='".$obs."'
														    WHERE id=".$id_moulinet);
}
$message_info .= htmlaccent('La liste des " Moulinets " a bien été mise à jour.');

// Nouveau
if(tep_not_null($_POST['num']))
{
	$num_0 = post_secure($sql_link,$_POST['num']);
	$fabricant_0 = post_secure($sql_link,$_POST['fabricant']);
	$obs_0 = post_secure($sql_link,$_POST['obs']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_MOULINET." (num,fabricant,obs) 
													VALUES ('".$num_0."','".$fabricant_0."','".$obs_0."')");	
		
	$message_info .= "<br>".htmlaccent('Le nouveau " Moulinet " '.$num_0.' a bien été enregistré');
}


// HELICE
//-------------------------


// Update
$sql_helice = "SELECT DISTINCT id
				FROM ".TABLE_HELICE."
				WHERE num<>'' ORDER BY num ASC";
$helice_query = tep_db_query($sql_link,$sql_helice);
while ($helice = tep_db_fetch_array($helice_query)) 
{
    $id_helice = $helice['id'];

	$num = post_secure($sql_link,$_POST['num_'.$id_helice]);
    $diam = post_secure($sql_link,$_POST['diam_'.$id_helice]);
    $pas = post_secure($sql_link,$_POST['pas_'.$id_helice]);
    $l1 = post_secure($sql_link,$_POST['l1_'.$id_helice]);
    $a1 = post_secure($sql_link,$_POST['a1_'.$id_helice]);
    $b1 = post_secure($sql_link,$_POST['b1_'.$id_helice]);
    $l2 = post_secure($sql_link,$_POST['l2_'.$id_helice]);
    $a2 = post_secure($sql_link,$_POST['a2_'.$id_helice]);
    $b2 = post_secure($sql_link,$_POST['b2_'.$id_helice]);
    $a3 = post_secure($sql_link,$_POST['a3_'.$id_helice]);
    $b3 = post_secure($sql_link,$_POST['b3_'.$id_helice]);
	$fabricant = post_secure($sql_link,$_POST['fabricant_'.$id_helice]);
	$obs = post_secure($sql_link,$_POST['obs_'.$id_helice]);	

	tep_db_query($sql_link,"UPDATE ".TABLE_HELICE." SET num='".$num."',
                                                        diametre='".$diam."',
                                                        pas='".$pas."',   
                                                        l1='".$l1."',   
                                                        a1='".$a1."',   
                                                        b1='".$b1."',   
                                                        l2='".$l2."',   
                                                        a2='".$a2."',   
                                                        b2='".$b2."',   
                                                        a3='".$a3."',   
                                                        b3='".$b3."',      
                                                        fabricant='".$fabricant."',
                                                        obs='".$obs."'
                                                        WHERE id=".$id_helice);
}
$message_info .= "<br>".htmlaccent('La liste des " Hélices " a bien été mise à jour.');

// Nouveau
if(tep_not_null($_POST['num']))
{
	$num_0 = post_secure($sql_link,$_POST['num']);
    $diam_0 = post_secure($sql_link,$_POST['num']);
    $pas_0 = post_secure($sql_link,$_POST['num']);
    $l1_0 = post_secure($sql_link,$_POST['l1']);
    $a1_0 = post_secure($sql_link,$_POST['a1']);
    $b1_0 = post_secure($sql_link,$_POST['b1']);
    $l2_0 = post_secure($sql_link,$_POST['l2']);
    $a2_0 = post_secure($sql_link,$_POST['a2']);
    $b2_0 = post_secure($sql_link,$_POST['b2']);
    $a3_0 = post_secure($sql_link,$_POST['a3']);
    $b3_0 = post_secure($sql_link,$_POST['b3']);
	$fabricant_0 = post_secure($sql_link,$_POST['fabricant']);
	$obs_0 = post_secure($sql_link,$_POST['obs']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_HELICE." (num,diametre,pas,l1,a1,b1,l2,a2,b2,a3,b3,fabricant,obs) 
													VALUES ('".$num_0."',
                                                            '".$diam_0."',
                                                            '".$pas_0."',
                                                            '".$l1_0."',
                                                            '".$a1_0."',
                                                            '".$b1_0."',
                                                            '".$l2_0."',
                                                            '".$a2_0."',
                                                            '".$b2_0."',
                                                            '".$a3_0."',
                                                            '".$b3_0."',
                                                            '".$fabricant_0."',
                                                            '".$obs_0."')");	
		
	$message_info .= "<br>".htmlaccent('La nouvelle " Helice " '.$num_0.' a bien été enregistré');
}



// SAUMON
//-------------------------


// Update
$sql_saumon = "SELECT DISTINCT id
				FROM ".TABLE_SAUMON."
				WHERE num<>'' ORDER BY num ASC";
$saumon_query = tep_db_query($sql_link,$sql_saumon);
while ($saumon = tep_db_fetch_array($saumon_query)) 
{
    $id_saumon = $saumon['id'];

	$num = post_secure($sql_link,$_POST['num_'.$id_saumon]);
    $titre = post_secure($sql_link,$_POST['titre_'.$id_saumon]);
    $poids = post_secure($sql_link,$_POST['poids_'.$id_saumon]);
    $distance_axe = post_secure($sql_link,$_POST['distance_axe_'.$id_saumon]);
    $t_air = post_secure($sql_link,$_POST['t_air'.$id_saumon]);
    $r_dist = post_secure($sql_link,$_POST['r_dist_'.$id_saumon]);
	$fabricant = post_secure($sql_link,$_POST['fabricant_'.$id_moulinet]);
	$obs = post_secure($sql_link,$_POST['obs_'.$id_moulinet]);	

	tep_db_query($sql_link,"UPDATE ".TABLE_SAUMON." SET num='".$num."',
                                                        titre='".$titre."',
                                                        poids='".$poids."',   
                                                        distance_axe='".$distance_axe."',   
                                                        t_air='".$t_air."',   
                                                        r_dist='".$r_dist."',   
                                                        fabricant='".$fabricant."',
                                                        obs='".$obs."'
                                                        WHERE id=".$id_helice);
}
$message_info .= "<br>".htmlaccent('La liste des " Saumons " a bien été mise à jour.');

// Nouveau
if(tep_not_null($_POST['num']))
{
	$num_0 = post_secure($sql_link,$_POST['num']);
    $titre_0 = post_secure($sql_link,$_POST['titre']);
    $poids_0 = post_secure($sql_link,$_POST['poids']);
    $distance_axe_0 = post_secure($sql_link,$_POST['distance_axe']);
    $t_air_0 = post_secure($sql_link,$_POST['t_air']);
    $r_dist_0 = post_secure($sql_link,$_POST['r_dist']);
	$fabricant_0 = post_secure($sql_link,$_POST['fabricant']);
	$obs_0 = post_secure($sql_link,$_POST['obs']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_SAUMON." (num,titre,poids,distance_axe,t_air,r_dist,fabricant,obs) 
													VALUES ('".$num_0."',
                                                            '".$titre_0."',
                                                            '".$poids_0."',
                                                            '".$distance_axe_0."',
                                                            '".$t_air_0."',
                                                            '".$r_dist_0."',
                                                            '".$fabricant_0."',
                                                            '".$obs_0."')");	
		
	$message_info .= "<br>".htmlaccent('Le nouveau " Saumon " '.$num_0.' a bien été enregistré');
}



?>

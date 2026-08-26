<?php
/*  
----------------------------------------
Copyright (c) 2015 - Vai-Natura
----------------------------------------
*/

//REFERENCE
$reference = htmlspecialchars(stripslashes($_POST['reference']),ENT_QUOTES);

//DATE CREATION
$date_fact = datefr_us(stripslashes($_POST['date_fact']));

//OBJET
$objet = htmlspecialchars(stripslashes($_POST['objet']),ENT_QUOTES);

//CONTACT
$contact = htmlspecialchars(stripslashes($_POST['contact']),ENT_QUOTES);

//REMISE
$remise = htmlspecialchars(stripslashes($_POST['remise']),ENT_QUOTES);

//REMARQUE
$remarque = htmlspecialchars(stripslashes($_POST['info']),ENT_QUOTES);

for($op=1;$op<40;$op++)
{
	${'op_' . $op} = htmlspecialchars(stripslashes($_POST['op_' . $op]),ENT_QUOTES);
	${'tva_' . $op} = htmlspecialchars(stripslashes($_POST['tva_' . $op]),ENT_QUOTES);
	${'qte_' . $op} = htmlspecialchars(stripslashes($_POST['qte_' . $op]),ENT_QUOTES);
	${'prix_' . $op} = htmlspecialchars(stripslashes($_POST['prix_' . $op]),ENT_QUOTES);
}

//calcul de la reference
if(!tep_not_null($reference)){$reference='visualisation';}

tep_db_query($sql_link,"DELETE FROM ".$table_op." WHERE id_soc=".$id_soc);
tep_db_query($sql_link,"DELETE FROM ".$table_opcontenu." WHERE id_soc=".$id_soc);


tep_db_query($sql_link,"INSERT INTO ".$table_op."(id_soc,reference,objet,client,remise,remarque,date_creation) " . 
											   "VALUES (" . $id_soc .
													",'" . $reference . 									
													"', '" . $objet . 	
													"', '" . $contact .
													"', '" . $remise .
													"', '" . $remarque .
													"', '" . $date_fact ."')");   

for($op=1;$op<40;$op++)
{
	if(tep_not_null(${'op_' . $op}))
	{
		tep_db_query($sql_link,"INSERT INTO ".$table_opcontenu."(id_soc,reference,description,tva,qte,prix) " . 
													"VALUES (" . $id_soc .
													",'" . $reference . 									
													"', '" . ${'op_' . $op} .
													"', '" . ${'tva_' . $op} .
													"', '" . ${'qte_' . $op} .
													"', '" . ${'prix_' . $op} .
													"')");
	}
}	

// données société
$sql_societe = "SELECT DISTINCT * FROM ".TABLE_INFOSOC." WHERE id=".$id_soc;
$societe_query = tep_db_query($sql_link,$sql_societe);
$societe = tep_db_fetch_array($societe_query);

if(isset($societe))
{
	$soc = htmlaccent(html_entity_decode($societe['titre']));
	$num_tahiti = htmlaccent(html_entity_decode($societe['num_tahiti']));
	$num_rc = htmlaccent(html_entity_decode($societe['num_rc']));
	
	$logo = htmlaccent(html_entity_decode($societe['logo']));
	if(tep_not_null($logo)){$logo_img = DIR_SITE_VIS.$logo;}
	else{$logo_img = '';}
	
	
	$gerant = htmlaccent(html_entity_decode($societe['gerant']));
	$mail = htmlaccent(html_entity_decode($societe['mail']));
	$tel = htmlaccent(html_entity_decode($societe['tel']));
	$fax = htmlaccent(html_entity_decode($societe['fax']));
	$adresse = htmlaccent(html_entity_decode($societe['adresse']));
	$cp = htmlaccent(html_entity_decode($societe['cp']));
	$ville = htmlaccent(html_entity_decode($societe['ville']));
	
	$pays_id = htmlaccent(html_entity_decode($societe['pays']));
	// pays 
	$sql_pays = "SELECT * FROM ".TABLE_PAYS." WHERE id=".$pays_id;
	$pays_query = tep_db_query($sql_link,$sql_pays);
	$pays_a = tep_db_fetch_array($pays_query);
	$pays = $pays_a['pays'];
	
	
	$num_tahiti = htmlaccent(html_entity_decode($societe['num_tahiti']));
	$num_rc = htmlaccent(html_entity_decode($societe['num_rc']));
	
	$banque = htmlaccent(html_entity_decode($societe['banque']));
	$banque_iban = htmlaccent(html_entity_decode($societe['banque_iban']));
}

	
// données recettes
$sql_recette = "SELECT DISTINCT * FROM ".$table_op;
$where_recette = " WHERE id_soc=".$id_soc." AND reference='".$reference."'";

$recette_query = tep_db_query($sql_link,$sql_recette.$where_recette);
$recette = tep_db_fetch_array($recette_query);
  
if(isset($recette))
{	
	//référence
	$reference = htmlaccent(html_entity_decode($recette['reference']));
	
	//objet
	$objet = htmlaccent(html_entity_decode($recette['objet']));
	 
	
	//client
	$client = htmlaccent(html_entity_decode($recette['client']));
	
	//remise
	$remise = htmlaccent(html_entity_decode($recette['remise']));
	
	if($remise != 0)
	{
		$sql_remise = "SELECT * FROM ".TABLE_REMISE." WHERE id=".$remise;
		$remise_query = tep_db_query($sql_link,$sql_remise);
		$remise_tab = tep_db_fetch_array($remise_query);
		
		$remise_type = htmlaccent($remise_tab['type']);
		$remise_taux = $remise_tab['taux'];
	}		
	
	//remarque
	$remarque = htmlaccent(html_entity_decode($recette['remarque']));
	
	//etat
	$etat = htmlaccent(html_entity_decode($recette['etat']));
	
	//date création	
	$tab_date = date_mise_en_forme($recette['date_creation']);
	$date_c = dateus_fr($recette['date_creation']);
	
	
	
	// données contact
	$sql_contact = "SELECT DISTINCT * FROM ".TABLE_OPCONTACT." WHERE id_soc=".$id_soc." AND id=".$client;
	$contact_query = tep_db_query($sql_link,$sql_contact);
	$contact = tep_db_fetch_array($contact_query);
	
	if(isset($contact))
	{
		$soc_contact = htmlaccent(html_entity_decode($contact['titre']));
		$gerant_contact = htmlaccent(html_entity_decode($contact['gerant']));
		$mail_contact = htmlaccent(html_entity_decode($contact['mail']));
		$tel_contact = htmlaccent(html_entity_decode($contact['tel']));
		$adresse_contact = htmlaccent(html_entity_decode($contact['adresse']));
		$cp_contact = htmlaccent(html_entity_decode($contact['cp']));
		$ville_contact = htmlaccent(html_entity_decode($contact['ville']));
		
		$pays_id = htmlaccent(html_entity_decode($contact['pays']));
		// pays 
		$sql_pays_contact = "SELECT * FROM ".TABLE_PAYS." WHERE id=".$pays_id;
		$pays_contact_query = tep_db_query($sql_link,$sql_pays_contact);
		$pays_contact = tep_db_fetch_array($pays_contact_query);
		$pays_c = $pays_contact['pays'];			
	}
	
	//données produits
	$total_remise=0;
	$prix_ht_tot_sans_remise=0;
	$prix_ht_tot=0;
	$prix_ttc_tot=0;
	$prix_ht_tot_final = 0;
	
	$sql_tva = "SELECT * FROM ".TABLE_TVA;
	$tva_query = tep_db_query($sql_link,$sql_tva);
	while($tva_a = tep_db_fetch_array($tva_query))
	{		
		$tva_array[$tva_a['id']] = array('type' => $tva_a['type'],
										 'taux' => $tva_a['taux'],
										 'valeur' => 0);
	}
	
	
	
	$sql_produit = "SELECT DISTINCT * FROM ".$table_opcontenu;
	$where_produit = " WHERE id_soc=".$id_soc." AND reference='".$reference."' ORDER BY id ASC";
	
	$produit_query = tep_db_query($sql_link,$sql_produit.$where_produit);
	while ($produit = tep_db_fetch_array($produit_query))
	{	
		//description
		$description = htmlaccent(html_entity_decode($produit['description']));
		
		//qte
		$qte = htmlaccent(html_entity_decode($produit['qte']));
		
		//prix
		$prix_unitaire = htmlaccent(html_entity_decode($produit['prix']));
		
		//prix des produits
		 $prix = $qte * $prix_unitaire;
		
		//remise pour le produit
		if($remise != 0){$remise_produit = $prix * ($remise_taux / 100 );}
		else {$remise_produit = 0;}
		
		//prix des produits final
		$prix_produit = $prix - $remise_produit;

		//calcul de la remise totale
		$total_remise += $remise_produit;
		
		//prix HT sans remise
		$prix_ht_tot_sans_remise += $prix;
		
		//prix HT avec remise
		$prix_ht_tot += $prix_produit;
		
		//prix TTC
		if($produit['tva'] != 0){$tva_array[$produit['tva']]['valeur'] += $prix_produit * $tva_array[$produit['tva']]['taux'] / 100;}
		
		$produit_array[] = array('id' => $produit['id'],
							   'description' => $produit['description'],
							   'qte' => $produit['qte'],
							   'prix' => $prix,
							   'prix_unitaire' => $prix_unitaire);
	}

	$prix_ttc_tot = $prix_ht_tot;
}
	
	
	
	
	

	// edition
	$edition = "";
	
	
	// edition bon commande
	$edition .= "
	
	<div id='block_all_view'  onClick=\"document.getElementById('block_all_view').style.display = 'none';\">\n
		
		<div id='cadre_view'>\n
		
		<div id='cadre_view_2'>\n
		
		<table class='header'> \n
			<tr> \n";
			
				if(tep_not_null($logo_img))
				{
					$edition .= 
					"<td class='space'><img src='".$logo_img ."' alt='".$soc."' /></td> \n";
				}
				else{$edition .= "<td class='space'><h1>".$soc."</h1></td> \n";}
				
				$edition .= 
				"<td class='tls'>
					".$soc_contact."<br />\n
					".$adresse_contact."<br />\n		
					".$cp_contact."<br />\n
					".$ville_contact."<br />\n	
					".$pays_c."<br />\n	
				</td>  \n
			</tr>  \n
		</table>\n
		
		<p><span style='font-size:20px;color:#990000;'>Facture</span></p>\n
		<br>
		<p>".htmlaccent('Référence')." : <span>".$reference."</span></p>\n
		<p>".htmlaccent('Date')." : <span>".$date_c."</span></p>\n
		<p>".htmlaccent('Objet')." : <span>".$objet."</span></p>\n
		
		<table> \n
			<tr> \n
				<td class='entete' style='text-align:left;'>".htmlaccent('Description')."</td> \n				
				<td class='entete'>".htmlaccent('Quantité')."</td>  \n
				<td class='entete'>".htmlaccent('Prix unitaire')."</td>  \n
				<td class='entete' style='text-align:right;'>".htmlaccent('Prix HT')."</td>  \n
			</tr>  \n
		
			<tr> \n
				<td colspan='4' class='space'>&nbsp;</td> \n
			</tr>\n";
		
		
			if(isset($produit_array))
			{
				for($i=0;$i<sizeof($produit_array);$i++)
				{
					$edition .= 
					"<tr>  \n
						<td class='width_titre'>".$produit_array[$i]['description']."</td>  \n
						<td class='width'>".$produit_array[$i]['qte']."</td>  \n				
						<td class='width'>".number_format($produit_array[$i]['prix_unitaire'], 0, ',', ' ')."</td>  \n								
						<td class='width' style='text-align:right;'>".number_format($produit_array[$i]['prix'], 0, ',', ' ')."</td>  \n
					</tr>  \n";
				}
			}
			
		$edition .= 
		"
			<tr> \n
				<td colspan='4' class='space'>&nbsp;</td> \n
			</tr>\n";
			
			
		//Remise de la facture
		if($remise != 0)
		{			
			$edition .= 
			"
				<tr class='resume'> \n
					<td colspan='2' class='space'>&nbsp;</td>  \n
					<td class='width'>".htmlaccent('Total')."</td>  \n
					<td class='width' style='text-align:right;'>".number_format($prix_ht_tot_sans_remise, 0, ',', ' ')."</td>  \n
				</tr> \n
						
				<tr class='resume'> \n
					<td colspan='2' class='space'>&nbsp;</td>  \n
					<td class='width'>".$remise_type."</td>  \n
					<td class='width' style='text-align:right;'>".number_format($total_remise, 0, ',', ' ')."</td>  \n
				</tr> \n
				
				<tr> \n
					<td colspan='4' class='space'>&nbsp;</td> \n
				</tr>\n
						
				<tr class='resume'> \n
					<td colspan='2' class='space'>&nbsp;</td>  \n
					<td class='width'>".htmlaccent('Total HT')."</td>  \n
					<td class='width' style='text-align:right;'>".number_format($prix_ht_tot, 0, ',', ' ')."</td>  \n
				</tr> \n";
		}	
		else
		{
			$edition .= 
			"
				<tr class='resume'> \n
					<td colspan='2' class='space'>&nbsp;</td>  \n
					<td class='width' >".htmlaccent('Total HT')."</td>  \n
					<td class='width' style='text-align:right;'>".number_format($prix_ht_tot_sans_remise, 0, ',', ' ')."</td>  \n
				</tr> \n";
		}	
		
			
		
		foreach ($tva_array as $cle=>$valeur) 
		{
			if($tva_array[$cle]['valeur'] != 0)
			{
				$prix_ttc_tot += $tva_array[$cle]['valeur']."<br>";
				
				$edition .= 
				"
				<tr class='resume'> \n
					<td colspan='2' class='space'>&nbsp;</td>  \n
					<td class='width'>".$tva_array[$cle]['type']."</td>  \n
					<td class='width' style='text-align:right;'>".number_format($tva_array[$cle]['valeur'], 0, ',', ' ')."</td>  \n
				</tr> \n";
			}			
		}
	
		$edition .= 
		"
			<tr> \n 
				<td colspan='2' class='space'>&nbsp;</td>  	\n
				<td class='end'>".htmlaccent('Total TTC ('.DEVISE.')')."</td>  \n
				<td class='end' style='text-align:right;'>".number_format($prix_ttc_tot, 0, ',', ' ')."</td>  
			</tr>  \n
		
		</table>\n
	
	
		<div id='info'>   \n
			<span style='font-weight:bold;'>".htmlaccent($soc.' • '.$adresse.' '.$cp.' '.$ville.' • '.$pays)."</span /><br />  \n
			".htmlaccent('Téléphone: '.$tel.' - Fax:'.$fax.' - Email: '.$mail)."<br/>\n
			".htmlaccent('n° TAHITI: '.$num_tahiti.' - n° RC: '.$num_rc)."<br/>\n
			".htmlaccent('code banque: '.$banque.' - code banque IBAN: '.$banque_iban)."<br/>\n
		</div>\n
	
		</div>\n
		
		</div>\n
	
	</div>\n";
	
?>

<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Onglet Jaugeage Pour le dépouillement d'un jaugeage avec graphique... Une feuille par bras
*/
$row = 0;

// Initialisation var equation Hélice
$l1=0;$a1=0;$b1=0;$l2=0;$a2=0;$b2=0;$a3=0;$b3=0;

// Récupération des données du JGE (PTS)
// Dépouillement du jaugeage, on va récupérer les données points par points
// $nbb est la variable qui indique le num du bras

if($nbb > 0)
{
    $num_vert_encours = 0;
    $dist_encours = 0;
    $prof_max_encours = 0;
    $num_pts = 1;

    $sql_jge_pts = "SELECT DISTINCT id, id_bras, num_vert, dist_depart, prof_max, prof_pts, nb_tours, tps_pts, obs, dist_calc, 
                                    debit_lam, prof_calc, vitesse_calc, vitesse_surf, vitesse_fond, vitesse_moy
                    FROM ".TABLE_DATA_JGE_PTS."
                    WHERE id_bras=".$jge_bras_array[$nbb]['id_bras']. "
                    ORDER BY dist_depart ASC, prof_pts DESC";
    
    $jge_pts_query = tep_db_query($sql_link,$sql_jge_pts);
    while ($jge_pts = tep_db_fetch_array($jge_pts_query))
    {   
        // Données Saisies        
        //$num_vert =  intval($jge_pts['num_vert']); On préfère renuméroter les verticales automatiquement
        $dist_depart =  round(floatval($jge_pts['dist_depart']),3);
        $prof_max =  round(floatval($jge_pts['prof_max']),3);
        $prof_pts =  round(floatval($jge_pts['prof_pts']),3);
        $nb_tours =  intval($jge_pts['nb_tours']);
        $tps_pts =  intval($jge_pts['tps_pts']);

        if($dist_depart > $dist_encours) // Si on est sur le premier points ou que l'on doit changer de vertical
        {
            $num_vert_encours++;            
            $dist_encours = $dist_depart;
            $prof_max_encours = $prof_max;
        }
        else
        {
            $dist_depart = $dist_encours;
            $prof_max = $prof_max_encours;
        }
        $num_vert = $num_vert_encours;


        $num_pts++;
        
        // Données Calculées
        $dist_calc =  round(floatval($jge_pts['dist_calc']),3);
        $debit_lam =  round(floatval($jge_pts['debit_lam']),3);
        $prof_calc =  round(floatval($jge_pts['prof_calc']),3);
        $vitesse_calc =  round(floatval($jge_pts['vitesse_calc']),3);
        $vitesse_surf =  round(floatval($jge_pts['vitesse_surf']),3);
        $vitesse_fond =  round(floatval($jge_pts['vitesse_fond']),3);
        $vitesse_moy =  round(floatval($jge_pts['vitesse_moy']),3);

        $obs = htmlaccent(html_entity_decode($jge_pts['obs'] ?? $default_string));    

        $jge_pts_array[$jge_pts['id']] = array('id_bras' => $jge_bras_array[$nbb]['id_bras'],
                                            'num_vert' => $num_vert,
                                            'dist_depart' => $dist_depart,
                                            'prof_max' => $prof_max,
                                            'prof_pts' => $prof_pts,
                                            'nb_tours' => $nb_tours,
                                            'tps_pts' => $tps_pts,
                                            'dist_calc' => $dist_calc,
                                            'debit_lam' => $debit_lam,
                                            'prof_calc' => $prof_calc,
                                            'vitesse_calc' => $vitesse_calc,
                                            'vitesse_surf' => $vitesse_surf,
                                            'vitesse_fond' => $vitesse_fond,
                                            'vitesse_moy' => $vitesse_moy,
                                            'obs' => $obs);
    }
}
$nb_jge_pts = 0;
if(isset($jge_pts_array)){$nb_jge_pts = sizeof($jge_pts_array);}// Nb de Points de Jaugeage
	



// Affichage de l'onglet 

echo "<div id='onglet_contenu'>\n";

    if($nbb > 0){echo "<input type='hidden' name='id_bras_".$nbb."' id='id_bras_".$nbb."' value='".$jge_bras_array[$nbb]['id_bras']."'>";}    
    else{echo "<input type='hidden' name='id_bras_".$nbb."' id='id_bras_".$nbb."' value='0'>";}

    echo "<div id='boite1'  class='first'>\n";

        echo "<div style='float:left;width:33%;' >\n";

            // Heure début du Jaugeage        
            echo "<div id='boite_small'>\n";
                            
                echo "<p style='float:left;width:80px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Heure Début');
                echo "</p>";
                        
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['heure_first'];}
                echo "<input type='text' style='width:60px; id='heure_first_".$nbb."_".$row."' name='heure_first_".$nbb."_".$row."' value='".$value."'>\n";
                        
            echo "</div>\n";

            // Hauteur d'échelle début        
            echo "<div id='boite_small'>\n";
                            
                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Echelle Début [cm]');
                echo "</p>";
                        
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['h_ech_first'];}
                echo "<input type='text' style='width:60px; id='h_ech_first_".$nbb."_".$row."' name='h_ech_first_".$nbb."_".$row."' value='".$value."'>\n";
                        
            echo "</div>\n";

            // Heure fin du Jaugeage        
            echo "<div id='boite_small'>\n";
                            
                echo "<p style='float:left;width:80px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Heure Fin');
                echo "</p>";
                        
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['heure_end'];}
                echo "<input type='text' style='width:60px; id='heure_end_".$nbb."_".$row."' name='heure_end_".$nbb."_".$row."' value='".$value."'>\n";
                        
            echo "</div>\n";

            // Hauteur d'échelle fin        
            echo "<div id='boite_small'>\n";
                            
                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Echelle Fin [cm]');
                echo "</p>";
                        
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['h_ech_end'];}
                echo "<input type='text' style='width:60px; id='h_ech_end_".$nbb."_".$row."' name='h_ech_end_".$nbb."_".$row."' value='".$value."'>\n";
                        
            echo "</div>\n";

             //Description / Observation						
             echo "<div id='boite_small' >\n";
                
                echo "<p style='float:left;width:140px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:0px;'>";
                    echo htmlaccent('Observation');
                echo "</p>";
                        
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['bras_obs'];}
                echo "<textarea name='jge_obs_".$nbb."' id='jge_obs_".$nbb."' style='width:100%;height:60px;'>".$value."</textarea>\n";
                            
            echo "</div>\n";

        echo "</div>\n";
         
        echo "<div style='float:left;width:20%;' >\n";

            // Fond du lit
            echo "<div id='boite_small'>\n";
                        
                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Fond du lit');
                echo "</p>";

                echo "<select name='select_fondlit_".$nbb."' id='select_fondlit_".$nbb."' style='width:100px;' >";

                    echo "<option value='0'>-</option>";
                        
                    $selected = '';			
                    if(isset($data_jge_fondlit_array))
                    {
                        foreach ($data_jge_fondlit_array as $key => $value)
                        {
                            if($key == $jge_bras_array[$nbb]['id_fondlit']){$selected="selected";}	
                            else{$selected = '';}											
                            echo "<option value='".$key."' ".$selected." >".$data_jge_fondlit_array[$key]['titre']."</option>";
                        }
                    }		
                    
                echo "</select>";                       
                
            echo "</div>\n";

            // Berge de départ
            echo "<div id='boite_small'>\n";

                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                        echo htmlaccent('Berge de départ');
                echo "</p>";

                echo "<select name='select_berge1_".$nbb."' id='select_berge1_".$nbb."' style='width:100px;' >";

                    $selected = '';
                    if($jge_bras_array[$nbb]['berge_depart'] == 1){$selected="selected";}	
                    echo "<option value='1' ".$selected." >".htmlaccent('Rive Gauche')."</option>";
                    if($jge_bras_array[$nbb]['berge_depart'] == 2){$selected="selected";}	
                    echo "<option value='2' ".$selected." >".htmlaccent('Rive Droite')."</option>";
                    
                echo "</select>";                       
                
            echo "</div>\n";

            // Moulinet
            echo "<div id='boite_small'>\n";

                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Moulinet');
                echo "</p>";
                
                echo "<select name='select_moulinet_".$nbb."' id='select_moulinet_".$nbb."' style='width:100px;' >";
                                                
                    echo "<option value='0'>-</option>";
                        
                    $selected = '';								 
                    if(isset($moulinet_array))
                    {
                        foreach ($moulinet_array as $key => $value)
                        {
                            if($key == $jge_bras_array[$nbb]['id_moulinet']){$selected="selected";}	
                            else{$selected = '';}											
                            echo "<option value='".$key."' ".$selected." >".$moulinet_array[$key]['num']."</option>";
                        }
                    }
                
                echo "</select>";
                        
            echo "</div>\n";

            // Helice
            echo "<div id='boite_small'>\n";

                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                    echo htmlaccent('Helice');
                echo "</p>";
                            
                echo "<select name='select_helice_".$nbb."' id='select_helice_".$nbb."' style='width:100px;' onChange='helice_eq(".$nbb.");'>";
                                                
                    echo "<option value='0'>-</option>";
                        
                    $selected = '';		
                    if(isset($helice_array))
                    {
                        foreach ($helice_array as $key => $value)
                        {
                            if($key == $jge_bras_array[$nbb]['id_helice']){$selected="selected";}	
                            else{$selected = '';}											
                            echo "<option value='".$key."' ".$selected." >".$helice_array[$key]['num']." - ".$helice_array[$key]['fabricant']."</option>";
                        }
                    }

                echo "</select>";

                $id_helice_encours = $jge_bras_array[$nbb]['id_helice'];
                    
                // Permet d'initialiser les données de l'équation Hélice si on est dans une modif des jaugeages
                if(isset($helice_array[$id_helice_encours]))
                {
                    $l1 = $helice_array[$id_helice_encours]['l1'];
                    $a1 = $helice_array[$id_helice_encours]['a1']; 
                    $b1 = $helice_array[$id_helice_encours]['b1'];
                    $l2 = $helice_array[$id_helice_encours]['l2'];
                    $a2 = $helice_array[$id_helice_encours]['a2']; 
                    $b2 = $helice_array[$id_helice_encours]['b2']; 
                    $a3 = $helice_array[$id_helice_encours]['a3']; 
                    $b3 = $helice_array[$id_helice_encours]['b3']; 
                }
                    
            echo "</div>\n";

            
            // Diamètre de la perche
            echo "<div id='boite_small' >\n";

                echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:0px;' title='".htmlaccent('Diamètre de la perche')."'>";
                    echo htmlaccent('Diam. perche [mm]');
                echo "</p>";
                    
                $value = '';
                if($modif){$value = $jge_bras_array[$nbb]['perche_diam'];}
                echo "<input type='text' style='width:60px;' id='perche_diam_".$nbb."_".$row."' name='perche_diam_".$nbb."_".$row."' value='".$value."'>\n";
                
            echo "</div>\n";
                
        echo "</div>\n";  
        
        echo "<div style='float:left;width:47%;'>\n";

            // Tableau Equation permettant de préciser l'équation en cours pour la conversion des tours d'hélice en vitesse
            $hidden = "style='visibility: hidden;'";
            if(($l2 > 0) && ($l2 < 99.99)){$hidden = "style='visibility: visible;'";}       

            echo "<div id='boite_small' style='float:left;width:300px;height:75px;padding:10px;border: 1.5px solid #000;'>\n";	
                    
                echo "<h2 style='color:#000;'>".htmlaccent('Equations de vitesse :')."</h2>\n";
                
                echo "<table>";
                    
                    echo "<tr>";

                        echo "<td style='width:35px;'>&nbsp;</td>";
                        echo "<td style='width:35px;'><span style='font-weight:bold;'>".htmlaccent('<=')."</td>";
                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='l1_bras_".$nbb."' id='l1_bras_".$nbb."' value='".$l1."' readonly>";
                        echo "</td>";

                        echo "<td style='width:10px;'>&nbsp;</td>";
                                
                        echo "<td style='width:22px;'><span style='font-weight:bold;'>".htmlaccent('v =')."</td>";
                        echo "<td style='width:38px;'>";
                            echo "<input type='text' style='width:38px;padding:0;font-size:12px;border:0px;background:none;' name='a1_bras_".$nbb."' id='a1_bras_".$nbb."' value='".$a1."' readonly>";
                        echo "</td>";
                        echo "<td style='width:25px;'><span style='font-weight:bold;'>".htmlaccent(' * n + ')."</td>";
                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='b1_bras_".$nbb."' id='b1_bras_".$nbb."' value='".$b1."' readonly>";
                        echo "</td>";

                    echo "</tr>";
                    
                    echo "<tr>";
                        
                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='l1_inf_bras_".$nbb."' id='l1_inf_bras_".$nbb."' value='".$l1."' readonly>"; 
                        echo "</td>";                         
                        
                        if(($l2 > 0) && ($l2 < 99.99))
                        {
                            echo "<td style='width:35px;'>";
                                echo "<input type='text' style='width:35px;padding:0;font-weight:bold;font-size:12px;border:0px;background:none;' name='lsign_".$nbb."' id='lsign_".$nbb."' value='".htmlaccent('< n <=')."' readonly>";
                            echo "</td>";
                            echo "<td style='width:35px;'>";
                                echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='l2_bras_".$nbb."' id='l2_bras_".$nbb."' value='".$l2."' readonly>";
                            echo "</td>";
                        }
                        else
                        {
                            echo "<td style='width:35px;'>";
                                echo "<input type='text' style='width:35px;padding:0;font-weight:bold;font-size:12px;border:0px;background:none;' name='lsign_".$nbb."' id='lsign_".$nbb."' value='".htmlaccent('< n')."' readonly>";
                            echo "</td>";
                            echo "<td style='width:35px;'>";
                                echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='l2_bras_".$nbb."' id='l2_bras_".$nbb."' value='' readonly>";
                            echo "</td>";
                        }
                        
                        echo "<td style='width:10px;'>&nbsp;</td>";
                                
                        echo "<td style='width:22px;'><span style='font-weight:bold;'>".htmlaccent('v =')."</span></td>";
                        echo "<td style='width:38px;'>";
                            echo "<input type='text' style='width:38px;padding:0;font-size:12px;border:0px;background:none;' name='a2_bras_".$nbb."' id='a2_bras_".$nbb."' value='".$a2."' readonly>";
                        echo "</td>";
                        echo "<td style='width:25px;'><span style='font-weight:bold;'>".htmlaccent('* n +')."</span></td>";
                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='b2_bras_".$nbb."' id='b2_bras_".$nbb."' value='".$b2."' readonly>";
                        echo "</td>";

                    echo "</tr>";
                
                    echo "<tr class='hidden_helice' ".$hidden.">";

                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='l2_inf_bras_".$nbb."' id='l2_inf_bras_".$nbb."' value='".$l2."' readonly>";
                        echo "</td>";                         
                        echo "<td style='width:35px;'><span style='font-weight:bold;'>".htmlaccent('< n')."</span></td>";
                        echo "<td style='width:35px;'>&nbsp;</td>";  

                        echo "<td style='width:10px;'>&nbsp;</td>";
                                
                        echo "<td style='width:22px;'><span style='font-weight:bold;'>".htmlaccent('v =')."</span></td>";
                        echo "<td style='width:38px;'>";
                            echo "<input type='text' style='width:38px;padding:0;font-size:12px;border:0px;background:none;' name='a3_bras_".$nbb."' id='a3_bras_".$nbb."' value='".$a3."' readonly>";
                        echo "</td>";
                        echo "<td style='width:25px;'><span style='font-weight:bold;'>".htmlaccent('* n +')."</span></td>";
                        echo "<td style='width:35px;'>";
                            echo "<input type='text' style='width:35px;padding:0;font-size:12px;border:0px;background:none;' name='b3_bras_".$nbb."' id='b3_bras_".$nbb."' value='".$b3."' readonly>";
                        echo "</td>";

                    echo "</tr>";
                    
                    
                echo "</table>";
            
            echo "</div>\n";

           // Cadre d'information des équations des hélices
            echo "<div id='boite_small' style='float:left;width:210px;padding:5px;border:1.5px solid #000;'>\n";

                echo "<h2 style='color:#000;'>";
                    echo htmlaccent('Formule d\'étalonnage de l\'hélice')."</span>";
                    echo "<br>".htmlaccent('v = k * n + a');
                echo "</h2>\n";

                echo "<table>";
                
                    echo "<tr style='height:15px;'>";
                        echo "<td><span style='font-weight:bold;'>v</span>".htmlaccent(' : vitesse du courant [m/s}')."</span></td>";
                    echo "</tr>";
                    echo "<tr style='height:15px;'>";
                        echo "<td><span style='font-weight:bold;'>k</span>".htmlaccent(' : pas hydraulique Hélice [m]')."</span></td>";
                    echo "</tr>";
                    echo "<tr style='height:15px;'>";
                        echo "<td><span style='font-weight:bold;'>n</span>".htmlaccent(' : vitesse rotation Hélice [tr/s]')."</span></td>";
                    echo "</tr>";
                    echo "<tr style='height:15px;'>";
                        echo "<td><span style='font-weight:bold;'>a</span>".htmlaccent(' : constante de frottements [m/s]')."</span></td>";
                    echo "</tr>";
                    
                echo "</table>";

            
            echo "</div>\n";

        echo "</div>\n";  

    echo "<hr>\n";
    echo "</div>\n";
    
    // Saut Ligne
    echo "<div style='width:100%;margin-top:20px;border-bottom:2px solid #176B87;'></div>\n";

    // ---------------------------------------------------------------------------
    // Dépouillement du Jaugeage
    // ---------------------------------------------------------------------------

    echo "<div id='boite1' style='margin-top:10px;float:left;width:98%;'>\n";

        echo "<div style='float:left;width:42%;' >\n";

            echo "<h2 style='margin-bottom:15px;font-size:16px;'>".htmlaccent('Dépouillement du jaugeage')."</h2>\n";

            echo "<table id='table_tri' cellspacing='0'  style='margin-left:0px;height:70vh;overflow-y: auto;'>";
                
                echo "<thead>";
                    echo "<th style='width:50px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Numéro de la verticale')."'>".htmlaccent('Num<br>Vert.')."</th>"; 
                    echo "<th style='width:60px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Distance du départ')."'>".htmlaccent('Dist. départ<br>[m]')."</th>";				
                    echo "<th style='width:60px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Profondeur totale de la verticale')."'>".htmlaccent('Prof. Tot.<br>[m]')."</th>";                    		
                    echo "<th style='width:60px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Profondeur de la mesure')."'>".htmlaccent('Prof.<br>[m]')."</th>";
                    echo "<th style='width:50px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Nombre de tour fait par l\'hélice')."'>".htmlaccent('TOPs')."</th>";                      
                    echo "<th style='width:50px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Temps d\'enregistrement')."'>".htmlaccent('Temps<br>[s]')."</th>";                    
                    echo "<th style='width:60px;color:#000;font-size:12px;border-bottom: 1px solid #fff;' title='".htmlaccent('Vitesse')."'>".htmlaccent('Vitesse<br>[m/s]')."</th>";
                echo "</thead>";	

                echo "<tr>";
                    echo "<td colspan='6' style='height:10px;'>&nbsp;</td>";
                echo "</tr>";

                //for($i=1;$i<=50;$i++)
                $row=1;

                if(isset($jge_pts_array))
                {
                    foreach($jge_pts_array as $key => $value)
                    {
                
                        if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                        else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";}

                        echo "<tr ".$row_l.">";
                                
                            echo "<td style='width:50px;'>";
                                echo "<input type='text' style='width:30px;' id='jge_bra_vert_".$nbb."_".$row."' name='jge_bra_vert_".$nbb."_".$row."' value='".$value['num_vert']."'>\n";
                            echo "</td>";
                            
                            echo "<td style='width:60px;'>";
                                echo "<input type='text' style='width:45px;' id='jge_bra_dist_".$nbb."_".$row."' name='jge_bra_dist_".$nbb."_".$row."' value='".$value['dist_depart']."'>\n";
                            echo "</td>";
                            
                            echo "<td style='width:60px;'>";
                                echo "<input type='text' style='width:45px;' id='jge_bra_profmax_".$nbb."_".$row."' name='jge_bra_profmax_".$nbb."_".$row."' value='".$value['prof_max']."'>\n";
                            echo "</td>";
                            
                            echo "<td style='width:60px;'>";
                                echo "<input type='text' style='width:45px;' id='jge_bra_profmesure_".$nbb."_".$row."' name='jge_bra_profmesure_".$nbb."_".$row."' value='".$value['prof_pts']."'>\n";
                            echo "</td>";

                            echo "<td style='width:50px;'>";
                                echo "<input type='text' style='width:30px;' id='jge_bra_nbtour_".$nbb."_".$row."' name='jge_bra_nbtour_".$nbb."_".$row."' value='".$value['nb_tours']."'>\n";
                            echo "</td>";

                            echo "<td style='width:50px;'>";
                                echo "<input type='text' style='width:30px;' id='jge_bra_tps_".$nbb."_".$row."' name='jge_bra_tps_".$nbb."_".$row."' value='".$value['tps_pts']."'>\n";
                            echo "</td>";
                            
                            echo "<td style='width:60px;'>";
                                echo "<input type='text' style='width:45px;border:0px;background-color:#FFFFDD;' id='jge_bra_vitesse_".$nbb."_".$row."' name='jge_bra_vitesse_".$nbb."_".$row."' value='".$value['vitesse_calc']."' readonly>\n";
                            echo "</td>";

                        echo "</tr>";

                        $row++;
                    }
                }

                while($row <= 50)
                {
                    if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";}

                    echo "<tr ".$row_l.">";

                        echo "<td style='width:50px;'>";
                            echo "<input type='text' style='width:30px;' id='jge_bra_vert_".$nbb."_".$row."' name='jge_bra_vert_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";
                        
                        echo "<td style='width:60px;'>";
                            echo "<input type='text' style='width:45px;' id='jge_bra_dist_".$nbb."_".$row."' name='jge_bra_dist_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";
                        
                        echo "<td style='width:60px;'>";
                            echo "<input type='text' style='width:45px;' id='jge_bra_profmax_".$nbb."_".$row."' name='jge_bra_profmax_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";
                        
                        echo "<td style='width:60px;'>";
                            echo "<input type='text' style='width:45px;' id='jge_bra_profmesure_".$nbb."_".$row."' name='jge_bra_profmesure_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";

                        echo "<td style='width:50px;'>";
                            echo "<input type='text' style='width:30px;' id='jge_bra_nbtour_".$nbb."_".$row."' name='jge_bra_nbtour_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";

                        echo "<td style='width:50px;'>";
                            echo "<input type='text' style='width:30px;' id='jge_bra_tps_".$nbb."_".$row."' name='jge_bra_tps_".$nbb."_".$row."' value=''>\n";
                        echo "</td>";
                        
                        echo "<td style='width:60px;'>";
                            echo "<input type='text' style='width:45px;border:0px;background-color:#FFFFDD;' id='jge_bra_vitesse_".$nbb."_".$row."' name='jge_bra_vitesse_".$nbb."_".$row."' value='' readonly>\n";
                        echo "</td>";
                    
                    echo "</tr>";

                
                    $row++;
                }

            echo "</table>";

        echo "</div>\n";

        echo "<div style='float:left;width:57%;' >\n";

            echo "<div style='float:left;' >\n";
            
                echo "<div style='float:left;width:30%' >\n";

                    // Echelle Moyenne    
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:110px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Echelle Moy. [cm]');
                        echo "</p>";
                        
                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_hmoy'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_hmoy_".$nbb."_".$row."' name='depouil_bras_hmoy_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Profondeur Moyenne      
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:110px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Prof. Moy. [cm]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_profmoy'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_profmoy_".$nbb."_".$row."' name='depouil_bras_profmoy_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Largeur Totale     
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:110px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;' onClick='calcul_jge(".$nbb.");'>";
                            echo htmlaccent('Largeur Tot. [m]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_distmax'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_distmax_".$nbb."_".$row."' name='depouil_bras_distmax_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                echo "</div>\n";

                echo "<div style='float:left;width:32%' >\n";

                    // Vitesse Moyenne       
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Vitesse Moy. [m/s]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_vmoy'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_vmoy_".$nbb."_".$row."' name='depouil_bras_vmoy_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Vitesse Surface      
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Vit. Surf. Moy.[m/s]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_vsurf'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_vsurf_".$nbb."_".$row."' name='depouil_bras_vsurf_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Rayon Hydraulique       
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:120px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;' title='".htmlaccent('Rayon Hydraulique')."' >";
                            echo htmlaccent('Rayon Hyd. - RH');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_rh'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_rh_".$nbb."_".$row."' name='depouil_bras_rh_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                echo "</div>\n";


                echo "<div style='float:left;width:34%' >\n";

                    // Surface Mouillée      
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:140px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Surface Mouillée [m2]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_surfmouil'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_surfmouil_".$nbb."_".$row."' name='depouil_bras_surfmouil_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Périmètre mouillé       
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:140px;font-weight: bold;color: #000;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Périmètre Mouillé [m]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_perimouil'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_perimouil_".$nbb."_".$row."' name='depouil_bras_perimouil_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";

                    // Débit - Q       
                    echo "<div id='boite_small'>\n";
                                    
                        echo "<p style='float:left;width:140px;font-weight: bold;color: #930000;;font-size: 13px;margin-top:5px;margin-bottom:10px;'>";
                            echo htmlaccent('Débit - QI [m3/s]');
                        echo "</p>";

                        $value = '';
                        if($modif){$value = $jge_bras_array[$nbb]['depouil_bras_q'];}
                        echo "<input type='text' style='width:60px;border:0px;background-color:#FFFFDD;' id='depouil_bras_q_".$nbb."_".$row."' name='depouil_bras_q_".$nbb."_".$row."' value='".$value."' readonly>\n";
                                
                    echo "</div>\n";


                echo "</div>\n";

            echo "</div>\n";    

            echo "<div id='boite1' class='first' style='float:left;width:100%;margin:0px;padding:0;'>\n";
                
                echo "<div id='boxpopup' class='select' style='width:100%;height:550px;margin:0;padding:0;'>\n";

                    //echo "<p class='titre' style='margin-bottom:5px;'>".htmlaccent('Graphique')."</p>\n";	
                    echo "<p class='titre' style='height:20px;margin-bottom:5px;'>";
                        echo "<button id='refresh' class='inverse_axe' style='width:150px;' onClick='calcul_jge(".$nbb.");return false;'>";
                            echo htmlaccent('Calcul / Graph - JGE');
                        echo "</button>"; 
                    echo "</p>\n";	

                    echo "<div id='plot_jge_bras_".$nbb."' style='padding:10px;'></div>\n";

                echo "</div>\n";

            echo "</div>\n";
        
        echo "</div>\n";

    echo "<hr>\n";
    echo "</div>\n";

echo "<hr>\n";
echo "</div>\n";

?>

<script type="text/javascript">
	
f_editgraph_jge(<?php echo $nbb; ?>);

		  
</script>
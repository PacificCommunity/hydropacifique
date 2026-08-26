<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station
- Edits time-series data to display a summary table and the available-data graph
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


// Retrieve JSON data sent from the AJAX request
$jsonDataGraph = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataJson = json_decode($jsonDataGraph, true);

// Extract values from the decoded array
// Cast to int: these are record IDs used directly in SQL WHERE clauses.
// Casting here neutralises SQL injection for every query that uses them.
$id_station = isset($dataJson['idStation']) ? (int)$dataJson['idStation'] : 0;
$id_eq_type = isset($dataJson['idEqType']) ? (int)$dataJson['idEqType'] : 0;


// ------------------------------------------------------  
// General data retrieval from DB

// TABLE TYPE DATA (DEBIT, PLUIE, PIEZO, ...)
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, type_color_border, type_color_background 
				FROM ".TABLE_EQ_TYPE." 
				WHERE active_eq_type=1 
				ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link,$sql_eq_type);	
while ($eq_type = tep_db_fetch_array($eq_type_query))
{
	$eq_type_array[$eq_type['id_eq_type']] = array('nom_eq_type' => html_entity_decode($eq_type['nom_eq_type'] ?? ''),
													'type_color_border' => html_entity_decode($eq_type['type_color_border'] ?? ''),							
													'type_color_background' => html_entity_decode($eq_type['type_color_background'] ?? ''),
													);
}

// TABLE DATA_CHRON (TYPE CHRON - CI,PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
				  FROM ".TABLE_TYPE_DATA." 
				  ORDER BY init_type_data ASC";

$type_chron_query = tep_db_query($sql_link,$sql_type_chron);									
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
	$type_chron_array[$type_chron_tab['id_data_type']] = array('init_type_data' => html_entity_decode($type_chron_tab['init_type_data'] ?? ''),
															'nom_type_data' => html_entity_decode($type_chron_tab['nom_type_data'] ?? ''),
															'id_eq_type_data' => html_entity_decode($type_chron_tab['id_eq_type_data'] ?? ''),
															'unite' => html_entity_decode($type_chron_tab['unite'] ?? '')
															);
}

// -----------------------------------------------------
// Graph colour initialisation 
$colorGraph = colorList();
$colorNC = '#000000';

// Quality code table.
// Each quality code now carries its own colour, configured in the quality-code
// settings page and stored in TABLE_DATA_QUALITE.couleur_qualite_data. We use
// that colour when set; otherwise we fall back to the legacy auto-assignment
// from colorList() so codes without a configured colour still display.
$id_color = 2;
$sql_code_qual = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, couleur_qualite_data FROM ".TABLE_DATA_QUALITE;
$code_qual_query = tep_db_query($sql_link,$sql_code_qual);
while ($code_qual_tab = tep_db_fetch_array($code_qual_query))
{				
	if(!empty($code_qual_tab['init_qualite_data']))
	{
		// Configured colour from the quality-code table; auto-assigned fallback.
		$couleur_qualite = trim($code_qual_tab['couleur_qualite_data'] ?? '');
		if($couleur_qualite == '')
		{
			$couleur_qualite = $colorGraph[$id_color % count($colorGraph) + 1];
		}

		$code_qual_array[$code_qual_tab['id_data_qualite']] =  array('init_qualite' => html_entity_decode($code_qual_tab['init_qualite_data'] ?? ''),
																	'nom_qualite' => html_entity_decode($code_qual_tab['nom_qualite_data'] ?? ''),
																	'color' => $couleur_qualite
																	);
		$id_color++;
	}
} 

$initQualiteLookup = array(); // used to check whether a quality code is already active



// ------------------------------------------------------  
// Retrieve the station time-series data

// --------------------------------------------------------------------
// Retrieve and build the summary info for the data linked to the station
$nb_data_all = 0; // number of time-series records
$nb_data_general = 0; // total record count including TOT, LAB, JGE, RA

// Initialise graph variables
$data_graph ='';
$load_data ='';

// DATA_LAB et DATA TOT
if($id_eq_type == 1) // rain-gauge stations only
{
	// DATA LAB
	$graph_data_x = '';
	$graph_data_y = '';

	$load_data_lab = '';

	$nb_lab = 0;
	$nb_data_lab = 0;

	$mindate_all_lab = '';
	$maxdate_all_lab = '';
	

	$sql_lab = "SELECT DISTINCT COUNT(*) as nb_data, MIN(lab.date_heure) as min_date, MAX(lab.date_heure) as max_date, lab.id_data_qualite
				FROM ".TABLE_DATA_LAB." lab
				WHERE lab.id_station=".$id_station."		
				GROUP BY lab.id_data_qualite
				ORDER BY min_date ASC";

	$lab_query = tep_db_query($sql_link,$sql_lab);
	while($lab_tab = tep_db_fetch_array($lab_query))
	{
		$timestamp = strtotime($lab_tab['min_date']); 
		$mindate_lab_js = date("Y-m-d", $timestamp);
		if($nb_lab < 1){$mindate_all_lab = $mindate_lab_js;}

		$timestamp = strtotime($lab_tab['max_date']); 
		$maxdate_lab_js = date("Y-m-d", $timestamp);
		$maxdate_all_lab = $maxdate_lab_js;

		//--

		$init_chron_encours= '';
		if(!empty($code_qual_encours[$lab_tab['id_data_qualite']]['init_qualite']))
		{
			$init_chron_encours = $code_qual_encours[$lab_tab['id_data_qualite']]['init_qualite'];
		}

		if(!empty($code_qual_array[$lab_tab['id_data_qualite']]['init_qualite']))// && isset($code_qual_array[$meta_data_tab['id_codequal']]))
		{
			$code_qual_encours = $code_qual_array[$lab_tab['id_data_qualite']]['init_qualite'];
			$nom_qual_encours = $code_qual_array[$lab_tab['id_data_qualite']]['nom_qualite'];
			$color_qual_encours = $code_qual_array[$lab_tab['id_data_qualite']]['color'];
		}
		else
		{
			$code_qual_encours = '(nc)';
			$nom_qual_encours = 'inconnu';
			$color_qual_encours = $colorNC;
		}

		
		if(!isset($initQualiteLookup[$code_qual_encours]) && ($code_qual_encours <> 'L')) // Skip records flagged as gaps
		{
			$select_CodeQual[$lab_tab['id_data_qualite']] = array('init_qualite' => $code_qual_encours,
																	'nom_qualite' => $nom_qual_encours,
																	'color' => $color_qual_encours
																	);
			
			// Ajouter le code au tableau de correspondance
			$initQualiteLookup[$code_qual_encours] = true;
		}
		//--

		// All LAB points
		if($code_qual_encours <> 'L') // Skip records flagged as gaps
		{
			$graph_data_x = "'".$mindate_lab_js."','".$maxdate_lab_js."'";
			$graph_data_y = "'".TEXT_LAB."','".TEXT_LAB."'";

			$nb_lab++;
			$nb_data_lab += $lab_tab['nb_data'];

			$data_graph .= "
							var trace_lab_".$nb_lab." = 
							{ 
								x: [".$graph_data_x."],
								y: [".$graph_data_y."],   

								mode: 'line', // type de trace (scatter plot)
								type: 'scatter', // chart type

								name:'',
								
								hovermode: 'closest',
								hovertemplate: '<b>".TEXT_LOADDATA_HOVER_DATE."</b>: %{x|%d-%m-%Y}<br><b>".TEXT_LOADDATA_HOVER_CODEQUAL."</b>: %{text}',
								text: ['".$code_qual_encours."','".$nom_qual_encours."'],

								line: {
										width: 12,
										color: '".$color_qual_encours."'
									},  
								marker: 
										{
											size: 10, // marker size
											symbol: 'line-ns-open',
											line: {width: 2}
										},
								
							}; 
							"; 
			
			$load_data_lab .= "trace_lab_".$nb_lab.","; // reverse the display order on the graph
		}

	}	
	$load_data_lab = rtrim($load_data_lab, ',');
	
	// Build the table that lists the station time series
	$lab_data_array = array('init_type_data' => TEXT_LAB,
							'nom_type_data' => TEXT_LAB_DATA,
							'nb_data' => $nb_data_lab,
							'min_date' => $mindate_all_lab,
							'max_date' => $maxdate_all_lab
							); 
	
	$nb_data_general += $nb_lab;


	// -----------------------------------------------------
	// DATA TOT
	$graph_data_x = '';
	$graph_data_y = '';

	$load_data_tot = '';

	$nb_tot = 0;
	$nb_data_tot = 0;

	$mindate_all_tot = '';
	$maxdate_all_tot = '';


	$sql_tot = "SELECT DISTINCT COUNT(*) as nb_data, MIN(tot.date_heure) as min_date, MAX(tot.date_heure) as max_date, tot.id_data_qualite
				FROM ".TABLE_DATA_TOT." tot
				WHERE tot.id_station=".$id_station."		
				GROUP BY tot.id_data_qualite
				ORDER BY min_date ASC";

	$tot_query = tep_db_query($sql_link,$sql_tot);
	while($tot_tab = tep_db_fetch_array($tot_query))
	{
		$timestamp = strtotime($tot_tab['min_date']); 
		$mindate_tot_js = date("Y-m-d", $timestamp);
		if($nb_tot < 1){$mindate_all_tot = $mindate_tot_js;}

		$timestamp = strtotime($tot_tab['max_date']); 
		$maxdate_tot_js = date("Y-m-d", $timestamp);
		$maxdate_all_tot = $maxdate_tot_js;

		//--

		$init_chron_encours= '';
		if(!empty($code_qual_encours[$tot_tab['id_data_qualite']]['init_qualite']))
		{
			$init_chron_encours = $code_qual_encours[$tot_tab['id_data_qualite']]['init_qualite'];
		}

		if(!empty($code_qual_array[$tot_tab['id_data_qualite']]['init_qualite']))// && isset($code_qual_array[$meta_data_tab['id_codequal']]))
		{
			$code_qual_encours = $code_qual_array[$tot_tab['id_data_qualite']]['init_qualite'];
			$nom_qual_encours = $code_qual_array[$tot_tab['id_data_qualite']]['nom_qualite'];
			$color_qual_encours = $code_qual_array[$tot_tab['id_data_qualite']]['color'];
		}
		else
		{
			$code_qual_encours = '(nc)';
			$nom_qual_encours = 'inconnu';
			$color_qual_encours = $colorNC;
		}

		
		if(!isset($initQualiteLookup[$code_qual_encours]) && ($code_qual_encours <> 'L')) // Skip records flagged as gaps
		{
			$select_CodeQual[$tot_tab['id_data_qualite']] = array('init_qualite' => $code_qual_encours,
																	'nom_qualite' => $nom_qual_encours,
																	'color' => $color_qual_encours
																	);
			
			// Ajouter le code au tableau de correspondance
			$initQualiteLookup[$code_qual_encours] = true;
		}
		//--

		// All TOT points
		if($code_qual_encours <> 'L')
		{
			$graph_data_x = "'".$mindate_tot_js."','".$maxdate_tot_js."'";
			$graph_data_y = "'".TEXT_TOT."','".TEXT_TOT."'";

			$nb_tot++;
			$nb_data_tot += $tot_tab['nb_data'];

			$data_graph .= "
							var trace_tot_".$nb_tot." = 
							{ 
								x: [".$graph_data_x."],
								y: [".$graph_data_y."],   

								mode: 'markers', // type de trace (scatter plot)
								type: 'scatter', // chart type

								name:'',
								
								hovermode: 'closest',
								hovertemplate: '<b>".TEXT_LOADDATA_HOVER_DATE."</b>: %{x|%d-%m-%Y}<br><b>".TEXT_LOADDATA_HOVER_CODEQUAL."</b>: ".$code_qual_encours."',

								//line: {width: 2.5,color: '".$color_qual_encours."'},  
								marker: 
										{
											size: 5, // marker size
											symbol: 'square', // marker shape
											//line: {width: 2,color: '".$color_qual_encours."'},
											color: '".$color_qual_encours."'
										},
								
							}; 

							"; 
			
			$load_data_tot .= "trace_tot_".$nb_tot.","; // reverse the display order on the graph
		}

	}	
	$load_data_tot = rtrim($load_data_tot, ',');

	// Build the table that lists the station time series
	$tot_data_array = array('init_type_data' => TEXT_TOT,
							'nom_type_data' => TEXT_TOT_DATA,
							'nb_data' => $nb_data_tot,
							'min_date' => $mindate_all_tot,
							'max_date' => $maxdate_all_tot
							); 	
								
	$nb_data_general += $nb_tot;
}	

// DATA_JGE DATA_ETL
if($id_eq_type == 11) // hydro stations only
{
	// DATA JAUGEAGE
	$graph_data_x = '';
	$graph_data_y = '';

	$nb_jge = 0;

	$mindate_all_jge = '';
	$maxdate_all_jge = '';

	$sql_jge = "SELECT DISTINCT j.id, j.datetime
				FROM ".TABLE_DATA_JGE." j 
				WHERE j.id_station=".$id_station."
				ORDER BY j.datetime ASC";

	$jge_query = tep_db_query($sql_link,$sql_jge);
	while($jge_tab = tep_db_fetch_array($jge_query))
	{
		$timestamp = strtotime($jge_tab['datetime']); 
		$date_jge_js = date("Y-m-d", $timestamp);
		
		if($nb_jge < 1){$mindate_all_jge = $date_jge_js;}
		$maxdate_all_jge = $date_jge_js;

		// All JGE points
		$graph_data_x .= "'".$date_jge_js."',";
		$graph_data_y .= "'".TEXT_JGE."',";

		$nb_jge++;
	}

	// Build the table that lists the station time series
	$jge_data_array = array('init_type_data' => TEXT_JGE,
							'nom_type_data' => TEXT_JGE_DESC,
							'nb_data' => $nb_jge,
							'min_date' => $mindate_all_jge,
							'max_date' => $maxdate_all_jge
							); 		
							
	$nb_data_general += $nb_jge;

	// After the loop, rtrim() removes the trailing comma
	$graph_data_x .= rtrim($graph_data_x, ',');
	$graph_data_y .= rtrim($graph_data_y, ',');

	$data_graph .= "
						var trace_jge = 
						{ 
							hovermode: 'closest',
							x: [".$graph_data_x."],
							y: [".$graph_data_y."],  
							
							name:'',

							hovertemplate: '<b>Date</b>: %{x|%d-%m-%Y}',

							mode: 'markers', // type de trace (scatter plot)
							type: 'scatter', // chart type
							marker: {
										size: 5,
										symbol: 'square', // marker shape
										color: '#A2D2DF'  // marker colour (hex)							
									}, // marker size
						}; 
						"; 



	// DATA ETL
	
	$load_data_etl = '';
	$previous_end_date = null; // stores the previous date_end
	$current_date = date("Y-m-d"); // Obtenir la date actuelle

	$nb_etl = 0;
	$sql_etl = "SELECT DISTINCT etl.id, etl.datetime_first, etl.datetime_end
				FROM ".TABLE_DATA_ETL." etl 
				WHERE etl.id_station=".$id_station."
				ORDER BY etl.datetime_first ASC";

	$etl_query = tep_db_query($sql_link,$sql_etl);
	while($etl_tab = tep_db_fetch_array($etl_query))
	{
		$graph_data_x = '';
		$graph_data_y = '';
		
		$timestamp_first = strtotime($etl_tab['datetime_first']); 
		$datefirst_etl_js = date("Y-m-d", $timestamp_first);

		$timestamp = strtotime($etl_tab['datetime_end']); 
		$dateend_etl_js = date("Y-m-d", $timestamp);

		// Check whether datetime_first equals the previous datetime_end
		if($previous_end_date && $datefirst_etl_js == $previous_end_date) 
		{
			// If equal, add one day to datetime_first
			$timestamp_first = strtotime("+1 day", $timestamp_first);
			$datefirst_etl_js = date("Y-m-d", $timestamp_first);
		}

		// Check whether dateend_etl_js is later than today
		if ($dateend_etl_js > $current_date) 
		{
			$dateend_etl_js = $current_date; // Si oui, remplacer par la date actuelle
		}

		$previous_end_date = $dateend_etl_js;
			
		$graph_data_x .= "'".$datefirst_etl_js."','".$dateend_etl_js."'";
		$graph_data_y .= "'ETL','ETL'" ;

		// Apply a different legend colour for each point
		$legend_pts[] = "'#9B7EBD'"; // Couleur pour datetime_first

		$nb_etl++;

		$data_graph .= "
						var trace_etl_".$nb_etl." = 
						{ 
							x: [".$graph_data_x."],
							y: [".$graph_data_y."],   

							name: '',

							mode: 'line', // type de trace (scatter plot)
							type: 'scatter', // chart type
							
							hovermode: 'closest',
							hovertemplate: '<b>".TEXT_ETL."</b>: %{text}  <br><b>".TEXT_LOADDATA_HOVER_DATE."</b>: %{x|%d-%m-%Y}',
							text: ['".TEXT_LOADDATA_LABEL_START."','".TEXT_LOADDATA_LABEL_END."'],

							line: {width: 4,color: '#9B7EBD'}, 
							marker: {
										size: 10, // marker size
										symbol: 'line-ns-open', // marker shape
										line: {width: 2}										
									}, 
						}; 
						"; 
		
		$load_data_etl .= "trace_etl_".$nb_etl.",";// . $load_data_etl; // reverse the display order on the graph
	}
	
	$load_data_etl = rtrim($load_data_etl, ',');
}



// META DATA details including RA - general time-series information

$nb_type_meta = 0;
$nb_data = 0;

$nb_ra = 0;
$nb_ra_valide = 0;
$nb_ra_Avalider = 0;

$mindate_all_ra = '';
$maxdate_all_ra = '';

$ra_array = [];
$ra_valide_array = [];
$ra_Avalider_array = [];

$graph_data_x = '';
$graph_data_y = '';

$sql_ra = "SELECT DISTINCT id_ra, id_agent_user, date_heure_ra, id_eq_type, etat_ra
		   FROM ".TABLE_DATA_RA." 
		   WHERE id_station=".$id_station."
		   ORDER BY date_heure_ra DESC";
$ra_query = tep_db_query($sql_link,$sql_ra);
while($ra_tab = tep_db_fetch_array($ra_query))
{
	$tab_date_heure_ra =  explode(" ",$ra_tab['date_heure_ra']);
	$date_ra =  dateus_fr($tab_date_heure_ra[0]);	

	$timestamp = strtotime($ra_tab['date_heure_ra']); 
	$date_ra_js = date("Y-m-d", $timestamp);

	if($nb_ra < 1){$mindate_all_ra = $date_ra_js;}
	$maxdate_all_ra = $date_ra_js;
	
	$ra_array[$ra_tab['id_ra']] = array('id_agent' => $ra_tab['id_agent_user'],
										'date_ra' => $date_ra, 
										'id_eq_type' => $ra_tab['id_eq_type'], 
										'etat_ra' => $ra_tab['etat_ra']); 

	if($ra_tab['etat_ra'] == 1)
	{
		$ra_valide_array[$ra_tab['id_ra']] = array('id_agent' => $ra_tab['id_agent_user'],
													'date_ra' => $date_ra, 
													'id_eq_type' => $ra_tab['id_eq_type'], 
													'etat_ra' => $ra_tab['etat_ra']); 
	}

	if($ra_tab['etat_ra'] == 0 || $ra_tab['etat_ra'] = null)
	{
		$ra_Avalider_array[$ra_tab['id_ra']] = array('id_agent' => $ra_tab['id_agent_user'],
													'date_ra' => $date_ra, 
													'id_eq_type' => $ra_tab['id_eq_type'], 
													'etat_ra' => $ra_tab['etat_ra']); 
	}

	// All RA points
	$graph_data_x .= "'".$date_ra_js."',";
	$graph_data_y .= "'".TEXT_RA."',";

	$nb_ra++;
}
$nb_ra_valide = sizeof($ra_valide_array);
$nb_ra_Avalider = sizeof($ra_Avalider_array);

// Build the table that lists the station time series
if($nb_ra > 0)
{
	$ra_data_array = array('init_type_data' => TEXT_RA,
						'nom_type_data' => TEXT_RA_DESC,
						'nb_data' => $nb_ra,
						'min_date' => $mindate_all_ra,
						'max_date' => $maxdate_all_ra
						); 	
}
$nb_data_general += $nb_ra;


// After the loop, rtrim() removes the trailing comma
$graph_data_x = rtrim($graph_data_x, ',');
$graph_data_y = rtrim($graph_data_y, ',');

$data_graph .= "
					var trace_ra = 
					{ 
						hovermode: 'closest',
						x: [".$graph_data_x."],
						y: [".$graph_data_y."],   

						name:'',

						hovertemplate: '<b>Date</b>: %{x|%d-%m-%Y}',

						mode: 'markers', // type de trace (scatter plot)
						type: 'scatter', // chart type
						marker: { 
									size: 6, // marker size
									color: '#FFE100',         // yellow
									symbol: 'square',                        // square marker
									line: {                                  // contour
										width: 1, 
										color: 'black'
									}
								}, 
					}; 
					"; 
	
					



// META DATA details - general time-series information
$graph_data_x = '';
$graph_data_y = '';

$sql_meta_data = "SELECT COUNT(*) as nb_data_chron, MIN(da.dateheure) as min_date, MAX(da.dateheure) as max_date, da.id_meta, dm.id_codequal, dm.id_typedata 
					FROM ".TABLE_DATA_ALL." da
					JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id	
					JOIN ".TABLE_TYPE_DATA." td ON dm.id_typedata=td.id_data_type 
					WHERE dm.id_station = ".$id_station."				
					GROUP BY da.id_meta, dm.id_codequal
					ORDER BY td.init_type_data ASC, min_date DESC, nb_data_chron ASC";

$chron_encours = 0;
$nb_data_chron = 0;

$periods = [];

$meta_data_query = tep_db_query($sql_link,$sql_meta_data);
while($meta_data_tab = tep_db_fetch_array($meta_data_query))
{
	$timestamp = strtotime($meta_data_tab['min_date']); 
	$min_date = date("Y-m-d", $timestamp);
	$timestamp = strtotime($meta_data_tab['max_date']); 
	$max_date = date("Y-m-d", $timestamp);
	
	$meta_data_array[$meta_data_tab['id_meta']] = array('init_type_data' => $type_chron_array[$meta_data_tab['id_typedata']]['init_type_data'],
														'nom_type_data' => $type_chron_array[$meta_data_tab['id_typedata']]['nom_type_data'],
														'nb_data' => $meta_data_tab['nb_data_chron'],
														'min_date' => $min_date,
														'max_date' => $max_date
														); 
	$init_chron_encours= '';
	if(!empty($type_chron_array[$meta_data_tab['id_typedata']]['init_type_data']))
	{
		$init_chron_encours = $type_chron_array[$meta_data_tab['id_typedata']]['init_type_data'];
	}

	if(!empty($code_qual_array[$meta_data_tab['id_codequal']]['init_qualite']))// && isset($code_qual_array[$meta_data_tab['id_codequal']]))
	{
		$code_qual_encours = $code_qual_array[$meta_data_tab['id_codequal']]['init_qualite'];
		$nom_qual_encours = $code_qual_array[$meta_data_tab['id_codequal']]['nom_qualite'];
		$color_qual_encours = $code_qual_array[$meta_data_tab['id_codequal']]['color'];
	}
	else
	{
		$code_qual_encours = '(nc)';
		$nom_qual_encours = 'inconnu';
		$color_qual_encours = $colorNC;
	}

	
	if(!isset($initQualiteLookup[$code_qual_encours]) && ($code_qual_encours <> 'L')) // Skip records flagged as gaps
	{
		$select_CodeQual[$meta_data_tab['id_codequal']] = array('init_qualite' => $code_qual_encours,
																'nom_qualite' => $nom_qual_encours,
																'color' => $color_qual_encours
																);
		
		// Ajouter le code au tableau de correspondance
        $initQualiteLookup[$code_qual_encours] = true;
	}

	if($code_qual_encours <> 'L') // Skip records flagged as gaps
	{
		// Graph variables
		$graph_data_x = $min_date."','".$max_date;
		$graph_data_y = $init_chron_encours."','".$init_chron_encours;													

		// Build the graph
		$data_graph .=
								"
								var trace_".$meta_data_tab['id_meta']." = 
								{ 
									x: ['".$graph_data_x."'],
									y: ['".$graph_data_y."'],

									mode: 'line',
									type: 'scatter',

									name:'',

									hovermode: 'closest',
									hovertemplate: '<b>".TEXT_LOADDATA_HOVER_DATE."</b>: %{x|%d-%m-%Y}<br><b>".TEXT_LOADDATA_HOVER_CODEQUAL."</b>: %{text}',
									text: ['".$code_qual_encours."','".$nom_qual_encours."'],

									line: {
											width: 12,
											color: '".$color_qual_encours."'
										}, 
									marker: 
											{
												size: 10, // marker size
												symbol: 'line-ns-open',
												line: {width: 2}
											},
								}; // Fin de trace
								";
		
		$load_data = "trace_".$meta_data_tab['id_meta'].", " . $load_data; // reverse the display order on the graph
		
		// Regroupement par Chronique (CI,PI, CIE, ...)														
		if($meta_data_tab['id_typedata'] == $chron_encours)
		{
			$nb_data_chron += $meta_data_tab['nb_data_chron'];
			
			if($min_date < $min_date_chron){$min_date_chron = $min_date;}
			if($max_date > $max_date_chron){$max_date_chron = $max_date;}	
		}		
		else
		{
			$chron_encours = $meta_data_tab['id_typedata'];

			$nb_data_chron = $meta_data_tab['nb_data_chron'];
			$min_date_chron = $min_date;
			$max_date_chron = $max_date;
		}

		$min_datefr_chron = dateus_fr($min_date_chron);
		$max_datefr_chron = dateus_fr($max_date_chron);

		$nb_data_all += $nb_data_chron;

		// Build the table that lists the station time series
		$chron_data_array[$chron_encours] = array('init_type_data' => $type_chron_array[$meta_data_tab['id_typedata']]['init_type_data'],
													'nom_type_data' => $type_chron_array[$meta_data_tab['id_typedata']]['nom_type_data'],
													'unite' => $type_chron_array[$meta_data_tab['id_typedata']]['unite'],
													'nb_data' => $nb_data_chron,
													'min_date' => $min_datefr_chron,
													'max_date' => $max_datefr_chron
													); 
													
		$nb_type_meta++;
	}
}
$nb_data_general += $nb_data_all;

if($id_eq_type == 1 && $nb_lab > 0){$load_data = $load_data_lab.", " . $load_data;} // Prepend LAB data to the graph series
if($id_eq_type == 1 && $nb_tot > 0){$load_data = $load_data_tot.", " . $load_data;} // Prepend LAB data to the graph series

if($id_eq_type == 11) // hydro stations only
{
	if($nb_jge > 0){$load_data = "trace_jge, " . $load_data;} // Prepend JGE data to the graph series
	if($nb_etl > 0){$load_data = $load_data_etl.", " . $load_data;} // Prepend ETL data to the graph series
}

if($nb_ra > 0){$load_data = "trace_ra, " . $load_data;} // Prepend RA data to the graph series (last line)




// HTML — data summary table for the station
$html_tab_data_station = '';

$html_tab_data_station .= "<table id='table_tri' cellspacing='0'>";

    $html_tab_data_station .= 
                        "
                            <thead>
                                <tr>
                                    <th style='width:90px;font-size:12px;'>" . TEXT_LOADDATA_COL_CHRON . "</th>					
                                    <th style='width:60px;font-size:12px;'>" . TEXT_LOADDATA_COL_NBDATA . "</th>
                                    <th style='width:75px;font-size:12px;'>" . TEXT_LOADDATA_COL_DATESTART . "</th>
                                    <th style='width:75px;font-size:12px;'>" . TEXT_LOADDATA_COL_DATEEND . "</th>
                                </tr>
                            </thead>
                        ";
                
                $row=1;
				if(isset($chron_data_array))
				{                    
					foreach($chron_data_array as $id_chron => $chron_tab) 
					{		
						if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                        else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
						
						$html_tab_data_station .= 
                            "
                                <tr ".$row_l." >
						
							        <td style='height:20px;padding-left:5px;' title='".$chron_tab['nom_type_data']."'>".$chron_tab['init_type_data']."</td>
							        <td style='height:20px;padding-left:2px;'>".number_format($chron_tab['nb_data'], 0, '.', ' ')."</td>
							        <td style='height:20px;'>".$chron_tab['min_date']."</td>		
							        <td style='height:20px;'>".$chron_tab['max_date']."</td>										
						
						        </tr>
                            ";
						
						$row++;
					}		
				}

				if(isset($jge_data_array) && $jge_data_array['nb_data'] >0)
				{					
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
					
                        $html_tab_data_station .= 
                            "
                                <tr ".$row_l." >
					
						            <td style='height:20px;padding-left:5px;' title='".$jge_data_array['nom_type_data']."'>".$jge_data_array['init_type_data']."</td>
						            <td style='height:20px;padding-left:2px;'>".number_format($jge_data_array['nb_data'], 0, '.', ' ')."</td>	
						            <td style='height:20px;'>".$jge_data_array['min_date']."</td>
						            <td style='height:20px;'>".$jge_data_array['max_date']."</td>									
						
                                </tr>
                            ";
                            
						
					$row++;					
				}

				if(isset($lab_data_array) && $lab_data_array['nb_data'] >0)
				{
					
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
					
                        $html_tab_data_station .= 
                            "
                                <tr ".$row_l." >
					
                                    <td style='height:20px;padding-left:5px;' title='".$lab_data_array['nom_type_data']."'>".$lab_data_array['init_type_data']."</td>
                                    <td style='height:20px;padding-left:2px;'>".number_format($lab_data_array['nb_data'], 0, '.', ' ')."</td>
                                    <td style='height:20px;'>".$lab_data_array['min_date']."</td>
                                    <td style='height:20px;'>".$lab_data_array['max_date']."</td>										
					
					            </tr>
                            ";
						
					$row++;					
				}

				if(isset($tot_data_array) && $tot_data_array['nb_data'] >0)
				{
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
					
					$html_tab_data_station .= 
                            "
                                <tr ".$row_l." >
					
						            <td style='height:20px;padding-left:5px;' title='".$tot_data_array['nom_type_data']."'>".$tot_data_array['init_type_data']."</td>
                                    <td style='height:20px;padding-left:2px;'>".number_format($tot_data_array['nb_data'], 0, '.', ' ')."</td>
						            <td style='height:20px;'>".$tot_data_array['min_date']."</td>
						            <td style='height:20px;'>".$tot_data_array['max_date']."</td>										
					
					            </tr>
                            ";
						
					$row++;					
				}

				if(isset($ra_data_array) && $ra_data_array['nb_data'] >0)
				{
					if(fmod($row,2)==0){$row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";} 
                    else{$row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";} 
					
					$html_tab_data_station .= 
                            "
                                <tr ".$row_l." >
					
						            <td style='height:20px;padding-left:5px;' title='".$ra_data_array['nom_type_data']."'>".$ra_data_array['init_type_data']."</td>
						            <td style='height:20px;padding-left:2px;'>".number_format($ra_data_array['nb_data'], 0, '.', ' ')."</td>
						            <td style='height:20px;'>".$ra_data_array['min_date']."</td>
						            <td style='height:20px;'>".$ra_data_array['max_date']."</td>
					
					            </tr>
                            ";
						
					$row++;					
				}
			
$html_tab_data_station .= "</table>";



// HTML — quality code legend table
$html_code_cal = '';

$html_code_cal .= "<p class='titre_box' style='font-size:13px;'>" . TEXT_LOADDATA_CODEQUAL_TITLE . "</p>\n";	
						
	$html_code_cal .= "<table style='width:100%;border-collapse:collapse;border:none;margin-top:4px;'>"; // Tableau principal

		if(isset($select_CodeQual))		
		{	
			$cle_CodeQual = array_keys($select_CodeQual);
			foreach($cle_CodeQual as $cle) 
			{
				$init_qualite = $select_CodeQual[$cle]['init_qualite'];
				$nom_qualite = $select_CodeQual[$cle]['nom_qualite'];
				$color = $select_CodeQual[$cle]['color'];

				// Choisir un texte noir ou blanc selon la luminosité du fond,
				// afin que le code reste lisible quelle que soit sa couleur.
				$text_color = '#000';
				$hex = ltrim(trim($color), '#');
				if (strlen($hex) == 3)
				{
					$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
				}
				if (strlen($hex) == 6 && ctype_xdigit($hex))
				{
					$r = hexdec(substr($hex, 0, 2));
					$g = hexdec(substr($hex, 2, 2));
					$b = hexdec(substr($hex, 4, 2));
					// Luminance perçue (0-255) ; seuil 150 -> texte noir/blanc
					$lum = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
					if ($lum < 150) { $text_color = '#fff'; }
				}

				// Une ligne = code surligné | nom
				$html_code_cal .=
					"
						<tr>
							<td style='border:none;padding:2px 8px 2px 0;width:48px;vertical-align:middle;'>
								<span style='display:inline-block;padding:1px 6px;font-size:10px;font-weight:bold;white-space:nowrap;background-color: $color;color: $text_color;'>".$init_qualite."</span>
							</td>
							<td style='border:none;padding:2px 0;font-size:10px;color:#000;vertical-align:middle;'>".$nom_qualite."</td>
						</tr>
					";
			}
		}

	// Fin du tableau
	$html_code_cal .= "</table>";


// Graph display variable initialisation

$config_graph = 
"
	var config = 
    {
        responsive: true,
        doubleClickDelay: 1000, //Delay du zoom
                
        scrollZoom: true, // Zoom avec la roulette de la souris

        displaylogo: false,
        modeBarOrientation: 'v',
        displayModeBar: true,    // Affichage constant du menu de la figure
        
        // Custom button layout
        modeBarButtons: [
            [
                {
                    name: 'Export SVG',
                    icon: Plotly.Icons.disk,
                    click: function(gd) {
                        Plotly.downloadImage(gd, {format: 'svg', filename: 'mon_grap'});
                    }
                },           
                {
                    name: 'Export PNG',
                    icon: Plotly.Icons.camera,
                    click: function(gd) {
                        Plotly.downloadImage(gd, {format: 'png', filename: 'mon_grap'});
                    }
                },
                'zoom2d',
                'pan2d',
                'resetScale2d'
            ]
        ],

        modeBarButtonsToRemove: ['select2d', 'lasso2d', 'autoScale2d', 'zoomIn2d', 'zoomOut2d']
    };
";

$layout_graph = 
"
	var layout = 
		{
			xaxis: 
				{
					title: {
						standoff: 20 // Ajuster la distance entre le titre et l'axe
					},
					type: 'date',
					automargin: true,
					autosize: true,
					fixedrange: false // allow panning on the Y axis   
				},
			
			yaxis: 
				{ 
					title: {
							//text: '' + TEXT_LOADDATA_YAXIS_LABEL + '
							standoff: 20, // Ajuster la distance entre le titre et l'axe
							font: {size: 13}
					},
					tickfont: {size: 11},
					automargin: true,
					autosize: true,
					fixedrange: false // allow panning on the Y axis   
				},

			
			autosize: true, // Permet de remplir le div parent
			
			//hovermode: 'x',
			hoverlabel: {
				bgcolor: '#fff', 
				font: { size: 14, color: '#000' } ,
			},	
			
			margin: {l: 50, r: 0, t: 30, b: 30},

			showlegend: false
		};
";


$editGraph = "
				Plotly.newPlot('plot',[".$load_data."],layout,config);
				setTimeout(function() {
					Plotly.Plots.resize('plot');
				}, 300);
			";


//print_r($select_CodeQual);

$responseData = array(
	'nb_chron' => $row,
    'js_tab_data' => $html_tab_data_station,
    'js_graph' => $config_graph.$data_graph.$layout_graph.$editGraph,
	'js_tab_code_cal' => $html_code_cal
);


// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;

?>
<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Importation des données Chroniques, 
Ce script il est appelé après avoir sélectionné et vérifier les fichiers à importer (load_file.php) 
Script coté serveur appelé par procédure AJAX depuis import.php
----------------------------------------
*/

// ----------------------------------------------
// nécessaire pour la configuration du script

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/sql_function.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');


// Récupération des données JSON envoyées depuis la requête AJAX
$jsonIdImport = file_get_contents('php://input');

// Décodage des données JSON 
$idImport = json_decode($jsonIdImport, true);

// ----------------------------------------------
// Récupération de données dans la base

// TABLE IMPORT FILES (Caractéristiques des fichiers importables)
$sql_import_files = "SELECT DISTINCT id, name_ext, multi_feuil, separateur, description, algo 
                    FROM ".TABLE_IMPORT_FILES." 
                    ORDER BY id ASC";
$import_files_query = tep_db_query($sql_link,$sql_import_files);									
while ($import_files_tab = tep_db_fetch_array($import_files_query))
{
    $name_ext = mb_convert_encoding($import_files_tab['name_ext'], 'ISO-8859-1', 'UTF-8');

	$import_files[$name_ext] = array('id' => $import_files_tab['id'],
                                    'multi_feuil' => $import_files_tab['multi_feuil'],
                                    'separateur' => $import_files_tab['separateur'],                                                    
                                    'description' => mb_convert_encoding($import_files_tab['description'], 'ISO-8859-1', 'UTF-8'),
                                    'algo' => $import_files_tab['algo'] // ce champs peu contenir l'algo de lecture du type de fichier !!! Attention potentiellement dangereux pour la sécurité
                                    );
}

// TABLE STATION
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
					FROM ".TABLE_STATION;
$station_all_query = tep_db_query($sql_link,$sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{	
	$station_all_array[$station_all['id_station']] = array('code_station' => $station_all['code_station'],
															'nom_station' => $station_all['nom_station'],
															'station_type' => $station_all['station_type'],
															);
}

// TABLE DATA_CHRON (TYPE CHRON - CI,PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
				  FROM ".TABLE_TYPE_DATA." 
				  ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link,$sql_type_chron);									
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
	$type_chron_array[$type_chron_tab['id_data_type']] = array('init_type_data' => $type_chron_tab['init_type_data'],
															'nom_type_data' => $type_chron_tab['nom_type_data'],
															'unite' => $type_chron_tab['unite'],															
															'id_eq_type_data' => $type_chron_tab['id_eq_type_data']
															);
}

// TABLE QUALITY_DATA 
$sql_quality_data = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data
				  	FROM ".TABLE_DATA_QUALITE;
$quality_data_query = tep_db_query($sql_link,$sql_quality_data);									
while ($quality_data_tab = tep_db_fetch_array($quality_data_query))
{
	$quality_data_array[$quality_data_tab['init_qualite_data']] = array('id_data_qualite' => $quality_data_tab['id_data_qualite'],
																		'nom_qualite_data' => mb_convert_encoding($quality_data_tab['nom_qualite_data'] ?? '', 'ISO-8859-1', 'UTF-8'),												
																		'info_qualite_data' => mb_convert_encoding($quality_data_tab['info_qualite_data'] ?? '', 'ISO-8859-1', 'UTF-8')
																		);
}


// ----------------------------------------------
// Initialisation de variables
$folder =  '../../../data/uploads/'; // Chemin de destination

$import_warning_ligne = '';
$import_result_error = "";
$import_result = '';

$num_ligne = 0;
$nb_data_import = 0;

$date_debut = ''; // date de début de la chronique à chargé
$date_fin = ''; // date de fin de la chronique à chargé

$db_load = true;
$rows_deleted = 0;

$id_station = 0;
$id_chron = 0;
$id_ext_file = 0;


$meta_id_encours = 0;
$sql_meta_id = "SELECT MAX(id) as last_id FROM ".TABLE_DATA_META; // récupérer le id du dernier meta
$meta_id_query = tep_db_query($sql_link,$sql_meta_id);
$meta_id_tab = tep_db_fetch_array($meta_id_query);        
if($meta_id_tab['last_id'] > 0){$meta_id_encours = $meta_id_tab['last_id'];}

$data_tab = array();
$meta_tab = array();
$list_quality_encours = array();


$startTime = microtime(true); // Temps au début du script

    // Récupération des données du fichier à importer 
    $sql_import = "SELECT DISTINCT id_import, file_import, file_ext, dateheure, id_station, id_chron, id_user
                        FROM ".TABLE_IMPORT_SUIVI." 
                        WHERE id=".$idImport;
    $import_query = tep_db_query($sql_link,$sql_import);		
    $import_data = tep_db_fetch_array($import_query);

    // Lecture et Vérification des données avant chargement dans la base 
    if($import_data['file_ext'] = 'csv')
    {
        $chemin_file = $folder.'files/'.$import_data['file_import'];

        $nb_error_date = 0;
        $nb_error_valeur = 0;        
        $nb_warning_qualite = 0;

        // Ouvrir le fichier CSV en mode lecture
        if (($handle = fopen($chemin_file, 'r')) !== false) 
        {
            // On créer un fichier temporaire rapide à écrire
            //$tempFile = $folder.'files/temp.csv';
            //$fp = fopen($tempFile, 'w');
            
            // Lire chaque ligne du fichier CSV
            while (($data = fgetcsv($handle, 10000, ';')) !== false) 
            {
                $import_valid = true;

                $num_ligne++;
                
                // Colonne 1 (date)
                $data_date = isValidDateImport($data[0]);
                if($data_date == 'Invalid')
                {
                    $import_valid = false;  
                    $nb_error_date++;   
                }

                // Colonne 2 (Mesure)
                if($import_valid)
                {
                    if(isDecimal($data[1])) // la fonction isDecimal (dans general.php) permet de changer une , en . et de vérifier si la valeur est bien numéric
                    {
                        $data_mesure = str_replace(',', '.', $data[1]);
                        // Formater le nombre avec 4 décimales et sans séparateur de milliers
                        $data_mesure = number_format((float)$data_mesure, 4, '.', '');
                    }
                    else
                    {
                        $import_valid = false;     
                        $nb_error_valeur++;  
                    }
                }   

                // Colonne 3 (Code Qualité)
                if($import_valid)
                {
                    $quality_ok = false;

                    if(isset($data[2]))
                    {
                        if(isset($quality_data_array[$data[2]])) // si l'intitulé du code qualité est enregistrée dans la base
                        {
                            $id_quality_cell = $quality_data_array[$data[2]]['id_data_qualite'];
                            $quality_ok = true;
                        }
                    }

                    if(!$quality_ok)
                    {
                        $id_quality_cell = 0;
                        $nb_warning_qualite++;
                    }

                    // On regarde ici s'il faut créer une nouvelle meta donnée                    
                    if(isset($list_quality_encours[$id_quality_cell])) 
                    {
                        $new_meta = false;
                    }
                    else
                    {
                        $meta_id_encours++;
                        $list_quality_encours[$id_quality_cell] = $meta_id_encours;
                        $new_meta = true;
                    }
                }

                // Enregistrement de la ligne si elle est valide dans les tables avant import dans la base
                if($import_valid)
                {
                    $nb_data_import++;

                    if($num_ligne == 1){$date_debut = $data_date;} // si on lit la première ligne on note la date
                    $date_fin = $data_date; // on met à jour la date de fin si la ligne est valide 

                    if($new_meta)
                    {
                        $meta_tab[] = array('id' => $list_quality_encours[$id_quality_cell],
                                            'station' => $import_data['id_station'],
                                            'chron' => $import_data['id_chron'],
                                            'quality' => $id_quality_cell,
                                            'user' => $import_data['id_user'],
                                            'source' => 'Import',
                                            'file' => $import_data['file_import'],
                                            'obs' => '',
                                        );
                    }
                    
                    // Les données à importer sont mises dans un tableau
                    $data_tab[] = array('date' => $data_date,
                                    'valeur' => $data_mesure,												
                                    'meta_id' => $list_quality_encours[$id_quality_cell]
                                    );   
                    
                    
                }
            }
            
            fclose($handle); // Fermer le fichier CSV à importer


        }
    }    

    // Enregistrement dans les bases
    if($import_valid)
    {
        // Information Load File
        $import_result .= "\n----\n";
        $import_result .= "Fichier : ".$import_data['file_import']."\n";
        $import_result .= "Station : ".$station_all_array[$import_data['id_station']]['nom_station']."\n";
        $import_result .= "Chronique : ".$type_chron_array[$import_data['id_chron']]['init_type_data']." - ".$type_chron_array[$import_data['id_chron']]['nom_type_data']."\n";
        $import_result .= "\n";
        
        // On efface les données entre Date_Debut et Date_Fin chronique, qu'elles existent ou pas
        $rows_deleted = deleteDataAndMeta($sql_link,$import_data['id_station'], $import_data['id_chron'], $date_debut, $date_fin);

        // Préparation de la requête d'insertion en bloc DATA_ALL  
        // Stratégie par bloc

        // Démarrer la transaction - Enregistrement des données TABLE_DATA_ALL
       
        mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

        try {
            $batchSize = 600; // Nombre de lignes par batch
            $query_insert_bloc_data = "INSERT INTO ".TABLE_DATA_ALL." (dateheure, valeur, id_meta) VALUES ";

            $rows = [];
            foreach ($data_tab as $row) 
            {
                $rows[] = "('".$row['date']."', ".$row['valeur'].", ".$row['meta_id'].")";

                if (count($rows) >= $batchSize) 
                {
                    // Exécution du batch
                    $query = $query_insert_bloc_data . implode(', ', $rows);
                    if (!mysqli_query($sql_link, $query)) {
                        //$import_result_error .= "Erreur lors de l'enregistrement dans base : " . mysqli_error($sql_link) . "\n";
                        $db_load = false;
                        throw new Exception(mysqli_error($sql_link));
                        //break;
                    }
                    $rows = []; // Réinitialiser pour le prochain batch
                }
            }

            // Exécuter le dernier batch s'il reste des lignes
            if (count($rows) > 0) 
            {
                $query = $query_insert_bloc_data . implode(', ', $rows);
                if (!mysqli_query($sql_link, $query)) {
                    //$import_result_error .= "Erreur lors de l'enregistrement dans base : " . mysqli_error($sql_link) . "\n";
                    $db_load = false;
                    throw new Exception(mysqli_error($sql_link));
                }
            }

            // Valider la transaction
            mysqli_commit($sql_link);
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db_load = false;
            mysqli_rollback($sql_link);
            $import_result_error .= "Erreur lors de l'enregistrement dans base : " . $e->getMessage();
        }
        
        if($db_load)
        {
            // Préparation de la requête d'insertion en bloc DATA_META                
            $query_insert_bloc_meta = "INSERT INTO ".TABLE_DATA_META." (id, id_station, id_typedata, id_codequal, id_user, source, file, obs) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = mysqli_prepare($sql_link, $query_insert_bloc_meta)) 
            {
                // Exécution de la requête en boucle
                foreach ($meta_tab as $row)
                {
                    mysqli_stmt_bind_param($stmt, 'iiiiisss', $row['id'], $row['station'], $row['chron'], $row['quality'], $row['user'], $row['source'], $row['file'], $row['obs']);
                    if (!mysqli_stmt_execute($stmt)) {
                        $import_result_error .= "Erreur lors de l'enregistrement dans base : " . mysqli_stmt_error($stmt) . "\n";
                        $db_load = false;
                    }
                }
                // Fermeture de la requête
                mysqli_stmt_close($stmt);
            } else
            {
                $import_result_error .= "Erreur lors de l'enregistrement dans base : " . mysqli_error($sql_link) . "\n";
                $db_load = false;
            }
            
        }
    }

$endTime = microtime(true); // Temps à la fin du script
$executionTime = number_format($endTime - $startTime,1);
        
    if($db_load)
    {        
        $import_result .= "Le traitement du fichier est terminé.\n";
        $import_result .= "\n";
        $import_result .= "Durée du traitement : ".$executionTime." sec.\n";
        $import_result .= "Nombre de données importées : ".$nb_data_import."\n";
        $import_result .= "Nombre d'erreurs : ".($num_ligne - $nb_data_import)."\n";     
        if($rows_deleted > 0)
        {
            $import_result .= "Nombre de données supprimées : ".$rows_deleted."\n";
        }                   
        $date_debut_tab = explode(" ", $date_debut);
        $import_result .= "Date de Début de la chronique : ".dateus_fr($date_debut_tab[0])."\n";
        $date_fin_tab = explode(" ", $date_fin);
        $import_result .= "Date de Fin de la chronique : ".dateus_fr($date_fin_tab[0])."\n";

        //if(file_exists($chemin_file)){unlink($chemin_file);} // On supprime le fichier 

        // Mises à jour de l'import dans la table de suivi
        
        $query = "UPDATE ".TABLE_IMPORT_SUIVI." SET nb_data='".$nb_data_import."', 
                                                    datetime_first='".$date_debut."', 
                                                    datetime_end='".$date_fin."',
                                                    import=1
                                                WHERE id=".$idImport;

        tep_db_query($sql_link, $query); 
        
    }
    else
    {
        $import_result .= "Les données n'ont pas pu être importées.\n";
        $import_result .= "\n";
        $import_result .= $import_result_error;
    }

    $import_result .= "\n";



// -------------------------------------
// Création d'un fichier txt contenant les infos détaillés sur l'import
if($nb_error_date>0){$import_warning_ligne .= "Erreurs sur le format de date (colonne 1) qui n'est pas reconnu ou vide : ".$nb_error_date." ligne(s) affectée(s).\n";}
if($nb_error_valeur>0){$import_warning_ligne .= "Erreurs sur le format de nombre (colonne 2) qui n'est pas valide ou vide : ".$nb_error_valeur." ligne(s) affectée(s).\n";}
if($nb_warning_qualite>0){$import_warning_ligne .= "Warning sur le code qualité (colonne 3) qui n'est pas référencé ou n'est pas renseigné : ".$nb_warning_qualite." ligne(s) concernée(s).\n";}

$text_import_result = $import_result.$import_result_error.$import_warning_ligne;
$text_import_result .= "----\n";

$resultFilename = $folder.'/'.$import_data['id_import'].'_'.$type_chron_array[$import_data['id_chron']]['init_type_data'].'.txt';
if (file_exists($resultFilename)){unlink($resultFilename);} // Supprimer le fichier existant s'il existe
file_put_contents($resultFilename, mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8'), FILE_APPEND); // Ecrire le résultat de l'import dans un fichier texte

// Enregistrement de l'action Export dans la base action
$type_action = 37;
$info_action = "Importation des données - Fichier : ".$import_data['file_import']." - Station : ".$station_all_array[$import_data['id_station']]['nom_station'];


$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure,id_import) 
                                VALUES (".$import_data['id_user'].",'".$type_action."','".$info_action."','".$import_data['dateheure']."','".$idImport."')";
tep_db_query($sql_link,$query);


echo json_encode($import_result);
					

?>

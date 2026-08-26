<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Compression d'un dossier - Format zip pour téléchargement
----------------------------------------
*/

// Récupération des données JSON envoyées depuis la requête AJAX
$jsonDataCompress = file_get_contents('php://input');

// Décodage des données JSON en tableau associatif
$dataCompress = json_decode($jsonDataCompress, true);

// Accès aux données individuelles
$chemin_folder = $dataCompress['chemin_folder'];
$folder = $dataCompress['folder_download'];
$chemin_folder_compress = '../../../' . $chemin_folder;




if(is_dir($chemin_folder_compress)) // Si le dossier existe
{
    // -------------------------------------
    // Zip Archive

    // créer un objet ZipArchive
    $zip = new ZipArchive();

    // nom du fichier ZIP à créer
    $zipFilename =  '../../../data/export/' . $folder.'.zip';
    /*
    if (!file_exists($zipFilename)) {
        // create an empty file with the specified name
        file_put_contents($zipFilename, '');
    }
    */

    // ouvrir le fichier ZIP en mode création
    //OVERWRITE permet d'éviter que d'anciens fichiers EXCEL générés soit tjrs dans le fichier zip en sortie.
    if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) 
    { 
        die('Erreur : impossible d\'ouvrir le fichier ZIP');
    }
    // Obtenir la liste des fichiers dans le dossier
    $fichiers = scandir($chemin_folder_compress);

    $total_files = sizeof($fichiers);
    $nb_files = 0;
    $total_time = 0;
    $resultZip_text = "";
    $json_resultZip_filename = $chemin_folder_compress.'/resultsZip.json';

    // Parcourir la liste des fichiers
    $startTime = microtime(true);

    foreach ($fichiers as $fichier) 
    {
        // Ignorer les entrées '.' et '..'
        if ($fichier != '.' && $fichier != '..' && $fichier != 'results.json' && $fichier != 'resultsZip.json') 
        {    
            // Ajouter le fichier à l'archive ZIP
            $zip->addFile($chemin_folder_compress.'/'.$fichier, $fichier);

            $nb_files++;

            $resultZip_text .= "Ajout du fichier : ".$fichier." \n";

            // Création d'un tableau associatif pour envoyer les résultats
            $data_resultZip = array(
                'result_text' => $resultZip_text,
                'total_time' => 0,
                'pourcentage' => ($nb_files / $total_files)*100
            );

            // Conversion du tableau en JSON
            $json_resultZip = json_encode($data_resultZip, JSON_UNESCAPED_UNICODE);

            // Écriture du JSON dans un fichier                
            file_put_contents($json_resultZip_filename, $json_resultZip);   
        }
    }   

    // fermer le fichier ZIP
    $zip->close();

     // Temps à la fin du script
     $endTime = microtime(true);

     // Calcul de la durée d'exécution en secondes
     $total_time = number_format($endTime - $startTime,1);

    // Obtenir la taille du fichier
    $fileSize = filesize($zipFilename);
    $fileSizeMb = round($fileSize / (1024 * 1024), 2); // Conversion en mégaoctets (Mo) avec arrondi à 2 décimales

    $resultZip_text .= "\nLes fichiers sont compressés et disponibles au téléchargement";
    $resultZip_text .= "\nDurée de traitement : ".$total_time." sec. - Nombre de fichiers traités : ".$nb_files;
    $resultZip_text .= "\nFichier : ".$folder.".zip - Taille : ".$fileSizeMb." Mo";

    echo json_encode($resultZip_text, JSON_UNESCAPED_UNICODE);


}



?>
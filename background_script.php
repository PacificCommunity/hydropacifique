<?php


// Ajoutez des journaux de débogage
file_put_contents('background_script.log', 'Script en arrière-plan démarré.' . PHP_EOL, FILE_APPEND);

// Faites quelque chose ici, par exemple, attendre pendant 5 secondes
sleep(5);

// Créez un fichier de test
file_put_contents('test_file.txt', 'Ceci est un test.');

?>
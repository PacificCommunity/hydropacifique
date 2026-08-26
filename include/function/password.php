<?php
/*  
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
*/



// verification de la validité du password 
// Permet aussi de modifier l'encryptage des mdp passer de md5 à password_hash()
function tep_validate_password($plain, $encrypted) 
{
    if (tep_not_null($plain) && tep_not_null($encrypted)) 
    {
        // CAS 1 : Nouveau format sécurisé (commence par $)
        if (strpos($encrypted, '$') === 0) {
            return password_verify($plain, $encrypted);
        }

        // CAS 2 : Ancien format (contient :)
        $stack = explode(':', $encrypted);
        if (sizeof($stack) == 2) {
            if (md5($stack[1] . $plain) == $stack[0]) {
                return true;
            }
        }
    }

    return false;
}


/* codage du password - Version sécurisée (PHP 8+) */
function tep_encrypt_password($plain) 
{
    if (!tep_not_null($plain)) return false;

    // Utilisation du standard PHP moderne
    // PASSWORD_DEFAULT choisit actuellement l'algorithme BCRYPT
    return password_hash($plain, PASSWORD_DEFAULT);
}


//pass alea
function pass_alea() 
{
    $char = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';	
    $pass = '';
    
    $max = strlen($char)-1;
    $taille = rand(6, 8);
    
    for ($i=1;$i<=$taille;$i++) 
    {
        $pass .= $char[rand(0, $max)];
    }
    
    return $pass;
} 



?>

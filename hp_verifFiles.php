<?php
    $projectDir = __DIR__;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectDir));
    $phpFiles = [];

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }

    $usedFiles = [];
    // Parcourir ton code pour trouver les "require", "include", etc.
    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        if (preg_match_all('/(require|include)(_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            foreach ($matches[3] as $includedFile) {
                $usedFiles[] = $projectDir . '/' . ltrim($includedFile, './');
            }
        }
    }

    $unusedFiles = array_diff($phpFiles, $usedFiles);
    print_r($unusedFiles);

?>
<?php
$csvFile = 'login.csv'; // chemin vers ton CSV
$outputFile = 'login_hashed.csv'; // CSV final avec Password_hash rempli

if (!file_exists($csvFile)) {
    die("Fichier $csvFile introuvable.");
}

if (($handle = fopen($csvFile, "r")) === false) {
    die("Impossible d'ouvrir le fichier CSV.");
}

// Lire l'en-tête
$header = fgetcsv($handle, 0, ';');
if (!$header) die("Impossible de lire l'en-tête CSV.");

// Préparer sortie
$output = [];
$output[] = implode(';', $header);

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    $row = array_combine($header, $data);

    // Remplir Password_hash si vide et Password présent
    if (empty($row['Password_hash']) && !empty($row['Password'])) {
        $row['Password_hash'] = password_hash($row['Password'], PASSWORD_DEFAULT);
    }

    // Reconstruire la ligne CSV
    $lineOut = [];
    foreach ($header as $col) {
        $lineOut[] = $row[$col] ?? '';
    }
    $output[] = implode(';', $lineOut);
}

fclose($handle);

// Écrire CSV final
file_put_contents($outputFile, implode("\n", $output));

echo "✅ CSV traité : $outputFile";
?>

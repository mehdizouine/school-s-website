<?php
// fusionner-exports.php
include('db.php');
require_once 'authorisation.php';
require_login();
// Pas besoin de CSRF ici

$EXPORT_DIR = 'export/';

if (!is_dir($EXPORT_DIR)) {
    die("❌ Dossier '$EXPORT_DIR' non trouvé. Créez-le et mettez-y vos fichiers CSV.");
}

// 🔑 ORDRE D'IMPORT SÉCURISÉ
$ordre = [
    'classes',
    'matiere',
    'examen',
    'semestre',
    'login',
    'profil',
    'login_attempts',
    'prof_classes',
    'note',
    'devoirs',
    'emploi_du_temps',
    'news',
    'message_us'
];

$csvFiles = [];
foreach ($ordre as $table) {
    $file = $EXPORT_DIR . $table . '.csv';
    if (file_exists($file)) {
        $csvFiles[] = $file;
    }
}

if (empty($csvFiles)) {
    die("❌ Aucun fichier CSV trouvé dans '$EXPORT_DIR'.");
}

$fichierFusion = 'import_complet_' . date('Y-m-d_H-i-s') . '.csv';
$output = fopen($fichierFusion, 'w');

foreach ($csvFiles as $file) {
    $tableName = basename($file, '.csv');
    fwrite($output, "### TABLE: $tableName\n");
    
    $input = fopen($file, 'r');
    if ($input) {
        while (($line = fgets($input)) !== false) {
            fwrite($output, $line);
        }
        fclose($input);
    }
    fwrite($output, "\n");
}

fclose($output);

// Télécharger
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $fichierFusion . '"');
readfile($fichierFusion);
unlink($fichierFusion);
exit;
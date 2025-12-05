<?php
include('db.php'); // ta connexion existante

// Désactiver les contraintes FK pour éviter erreurs
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Liste des tables à vider (ordre sûr si FK)
$tables = [
    'note',
    'devoirs',
    'emploi_du_temps',
    'prof_classes',
    'login_attempts',
    'login',
    'profil',
    'examen',
    'matiere',
    'semestre',
    'classes',
    'news',
    'message_us'
];

foreach ($tables as $t) {
    $conn->query("TRUNCATE TABLE `$t`");
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "✅ Toutes les tables ont été vidées. Tu peux relancer l'import.";
?>

<?php
// init.php

session_start();
require_once __DIR__ . '/database.php'; // ta connexion $conn

// Helper pour vérifier rôle
function require_role($role) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        die("Accès refusé");
    }
}

// Helpers CSRF simples
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function check_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur CSRF");
    }
}
?>

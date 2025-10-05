<?php
// auth.php
// Vérifie si la session est déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1️⃣ Vérifier si l’utilisateur est connecté
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        die("⛔ Accès refusé : veuillez vous connecter.");
    }
}

// 2️⃣ Vérifier un rôle précis
function require_role($role) {
    require_login(); // Assure que l’utilisateur est connecté
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die("⛔ Accès refusé : rôle requis ($role).");
    }
}

// 3️⃣ Vérifier plusieurs rôles autorisés
function require_roles(array $roles) {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die("⛔ Accès refusé : rôle non autorisé.");
    }
}
// dans authorisation.php (ou init.php) — appelé avant tout
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helper
function csrf_field() {
    $t = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="'.$t.'">';
}

function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            die("Requête invalide (CSRF).");
        }
    }
}


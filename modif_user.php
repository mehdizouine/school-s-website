<?php
session_start();
include('db.php');

// Ajouter ou modifier un utilisateur
if (isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($id) {
        // Update user
        $sql = "UPDATE login SET Username=?, Password=?, role=? WHERE ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $password, $role, $id);
    } else {
        // Add user
        $sql = "INSERT INTO login (Username, Password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $role);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: modif_user.php");
    exit;
}

// Supprimer un utilisateur
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM login WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: modif_user.php");
    exit;
}

// Récupérer tous les utilisateurs
$result = $conn->query("SELECT * FROM login ORDER BY ID ASC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Utilisateurs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-lg p-4" style="border-radius:20px;">
        <h3 class="mb-4 text-center text-primary"><i class="bi bi-people-fill"></i> Gestion des Utilisateurs</h3>

        <!-- Formulaire -->
        <form method="POST" class="row g-3">
            <input type="hidden" name="id" id="id">

            <div class="col-md-4">
                <input type="text" name="username" id="username" class="form-control" placeholder="Nom d’utilisateur" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="password" id="password" class="form-control" placeholder="Mot de passe" required>
            </div>
            <div class="col-md-3">
                <select name="role" id="role" class="form-select" required>
                    <option value="">Choisir rôle</option>
                    <option value="eleve">Élève</option>
                    <option value="admin">Admin</option>
                    <option value="prof">Prof</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" name="save_user" class="btn btn-success"><i class="bi bi-save"></i></button>
            </div>
        </form>
    </div>

    <hr class="my-4">

    <!-- Tableau -->
    <div class="card shadow-lg p-4" style="border-radius:20px;">
        <h4 class="mb-3"><i class="bi bi-list-check"></i> Liste des utilisateurs</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Nom d’utilisateur</th>
                        <th>Mot de passe</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['ID'] ?></td>
                        <td><?= htmlspecialchars($row['Username']) ?></td>
                        <td><?= htmlspecialchars($row['Password']) ?></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($row['role']) ?></span></td>
                        <td>
                            <button class="btn btn-warning btn-sm" 
                                    onclick="editUser(<?= $row['ID'] ?>,'<?= addslashes($row['Username']) ?>','<?= addslashes($row['Password']) ?>','<?= addslashes($row['role']) ?>')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="modif_user.php?delete=<?= $row['ID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet utilisateur ?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editUser(id, username, password, role) {
    document.getElementById('id').value = id;
    document.getElementById('username').value = username;
    document.getElementById('password').value = password;
    document.getElementById('role').value = role;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</body>
</html>
<style>
/* Sophisticated School Management System CSS - Teal Theme */

/* Advanced CSS Variables for Teal Design System */
:root {
    /* Palette Teal (ton vert-bleu foncé) */
    --primary-color: rgba(14, 119, 112, 0.8);       /* teal semi-transparente */
    --primary-dark: rgba(14, 119, 112, 1);          /* teal pur */
    --primary-light: rgba(14, 119, 112, 0.3);       /* teal clair */
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    --success-gradient: linear-gradient(135deg, rgba(129, 199, 132, 0.9) 0%, rgba(200, 230, 201, 0.8) 100%);
    --warning-gradient: linear-gradient(135deg, #ffe082 0%, #ffcc80 100%);
    --danger-gradient: linear-gradient(135deg, #ef9a9a 0%, #ffcdd2 100%);
    
    --glass-bg: rgba(255, 255, 255, 0.65);
    --glass-border: rgba(255, 255, 255, 0.3);
    --backdrop-blur: blur(12px);

    --shadow-light: 0 6px 18px rgba(14, 119, 112, 0.25);
    --shadow-medium: 0 12px 28px rgba(14, 119, 112, 0.2);
    --shadow-heavy: 0 16px 32px rgba(14, 119, 112, 0.3);

    --border-radius-sm: 12px;
    --border-radius-md: 20px;
    --border-radius-lg: 28px;

    --transition-smooth: all 0.4s ease;
    --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Sophisticated Body and Background */
body {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);;
    background-attachment: fixed;
    color: #2d3748;
    line-height: 1.6;
    min-height: 100vh;
    font-weight: 400;
    overflow-x: hidden;
    position: relative;
}

/* Animated Background Elements */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 25% 25%, rgba(27, 209, 194, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(27, 209, 194, 0.1) 0%, transparent 50%);
    animation: backgroundShift 20s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes backgroundShift {
    0%, 100% { opacity: 0.3; transform: translateY(0px); }
    50% { opacity: 0.6; transform: translateY(-20px); }
}

/* Container with Advanced Glassmorphism */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
    position: relative;
    z-index: 1;
}

/* Premium Card Design with Glassmorphism */
.card {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    padding: 40px;
    margin-bottom: 40px;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

/* Card Hover Effects */
.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(27, 209, 194, 0.1), transparent);
    transform: rotate(45deg);
    transition: var(--transition-smooth);
    opacity: 0;
}

.card:hover::before {
    animation: shimmer 1.5s ease-in-out;
}

.card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(27, 209, 194, 0.3);
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

/* Sophisticated Typography */
h3, h4 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin-bottom: 35px;
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    letter-spacing: -0.5px;
    position: relative;
}

h3::after, h4::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 0.8s ease-out;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 60px; }
}

/* ===== IMPROVED INPUT STYLING ===== */

/* Form Layout */
.row.g-3 {
    display: grid;
    grid-template-columns: 2fr 2fr 1.5fr auto;
    gap: 25px;
    align-items: end;
    margin-bottom: 0;
}

/* Column Positioning for Icons and Labels */
.col-md-4, .col-md-3 {
    position: relative;
    width: 100%;
    
}

/* Premium Input and Select Base Styling */
form .form-control, 
form .form-select {
    padding: 18px 20px 18px 55px !important;
    border: 2px solid rgba(27, 209, 194, 0.3) !important;
    border-radius: var(--border-radius-md) !important;
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(15px);
    color: rgba(2, 145, 133, 0.6) !important;
    font-size: 17px;
    font-weight: 500;
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 20px rgba(27, 209, 194, 0.1) !important;
    height: 58px;
    min-height: 58px;
    text-align: center;
}

/* Input Icons - Using Bootstrap Icons */
.col-md-4:nth-child(2)::before,
.col-md-4:nth-child(3)::before,
.col-md-3:nth-child(4)::before {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-family: 'bootstrap-icons';
    font-size: 20px;
    color: var(--primary-color);
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-smooth);
}

/* Username Icon */
.col-md-4:nth-child(2)::before {
    content: '\F4DA'; /* bi-person */
}

/* Password Icon */
.col-md-4:nth-child(3)::before {
    content: '\F512'; /* bi-lock */
}

/* Role Icon */
.col-md-3:nth-child(4)::before {
    content: '\F586'; /* bi-star */
}

/* Placeholder Styling */
form .form-control::placeholder,
form .form-select option:first-child {
    color: rgba(2, 145, 133, 0.6) !important;
    font-weight: 500;
    font-size: 19px;
}

/* Focus States */
form .form-control:focus,
form .form-select:focus {
    outline: none !important;
    border-color: var(--primary-dark) !important;
    background: rgba(255, 255, 255, 0.15) !important;
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.08),
        0 8px 30px rgba(27, 209, 194, 0.2) !important;
    transform: translateY(-3px);
}

/* Icon Animation on Focus */
.col-md-4:focus-within::before,
.col-md-3:focus-within::before {
    color: var(--primary-dark);
    transform: translateY(-50%) scale(1.2);
    filter: drop-shadow(0 0 8px rgba(27, 209, 194, 0.3));
}

/* Select Dropdown Styling */
form .form-select {
    cursor: pointer;
    appearance: none;
    background-image: none !important;
    background-repeat: no-repeat;
    background-position: right 18px center;
    background-size: 16px;
    padding-right: 50px !important;
}

form .form-select:focus {
    background-image: none !important;
}

/* Hover Effects */
.col-md-4:hover .form-control,
.col-md-3:hover .form-select {
    border-color: var(--primary-color) !important;
    background: rgba(255, 255, 255, 0.12) !important;
    transform: translateY(-1px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.08),
        0 6px 25px rgba(27, 209, 194, 0.15) !important;
}

/* Valid State */
form .form-control:valid {
    border-color: rgba(34, 197, 94, 0.6) !important;
}

/* Invalid State */
form .form-control:invalid:not(:placeholder-shown) {
    border-color: rgba(239, 68, 68, 0.6) !important;
}

/* Premium Button Design */
form button {
    background: var(--primary-gradient) !important;
    border: none !important;
    border-radius: 50px !important;
    padding: 16px 20px !important;
    color: white !important;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    min-width: 60px;
    height: 56px;
}

form button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: var(--transition-smooth);
}

form button:hover::before {
    width: 300px;
    height: 300px;
}

form button:hover {
    transform: translateY(-3px) scale(1.05) !important;
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4) !important;
    background: var(--primary-gradient) !important;
}

form button:active {
    transform: translateY(0) scale(0.98) !important;
}

/* ===== TABLE AND OTHER STYLES REMAIN UNCHANGED ===== */
/* Container du tableau avec glass effect et léger gradient teal */
/* Enhanced Table Styling - Light Colors Added */

/* Container du tableau avec glass effect et léger gradient teal */
.table-responsive {
    border-radius: var(--border-radius-md);
    overflow: hidden;
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(255, 255, 255, 0.6)); /* Light teal gradient */
    backdrop-filter: blur(12px);
    padding: 15px;
    margin-top: 20px;
    box-shadow: 0 10px 25px rgba(14, 119, 112, 0.15);
    border: 1px solid rgba(14, 119, 112, 0.1);
}

/* Table principale */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
    color: #2d3748;
    transition: all 0.4s ease;
    background: transparent;
}

/* Header - Keep your original styling but add icons */
thead th {
    background: var(--primary-gradient);
    color: #fff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 18px 15px;
    font-size: 13px;
    border: none;
    position: relative;
    box-shadow: inset 0 -3px 5px rgba(0,0,0,0.1);
    border-right: 1px solid rgba(255,255,255,0.2);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

thead th:last-child {
    border-right: none;
}

/* Add icons to headers */
thead th:nth-child(1)::before { content: '\F292 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* ID */
thead th:nth-child(2)::before { content: '\F4DA '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Username */
thead th:nth-child(3)::before { content: '\F512 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Password */
thead th:nth-child(4)::before { content: '\F2A4 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Role */
thead th:nth-child(5)::before { content: '\F3E5 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Actions */

/* Enhanced Body Rows with Light Colors */
tbody tr {
    background: rgba(255, 255, 255, 0.7); /* Light white background */
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(14, 119, 112, 0.08);
    position: relative;
}

tbody tr:nth-child(even) {
    background: rgba(226, 250, 248, 0.3); /* Very light teal for alternating rows */
}

/* Beautiful Hover Effect with Light Colors */
tbody tr:hover {
    background: rgba(178, 245, 234, 0.4) !important; /* Light teal on hover */
    transform: translateX(5px) scale(1.01);
    box-shadow: 
        5px 0 20px rgba(27, 209, 194, 0.15),
        0 4px 15px rgba(14, 119, 112, 0.1);
    border-left: 4px solid var(--primary-color);
}

/* Cellules with enhanced styling */
tbody td {
    padding: 16px 12px;
    vertical-align: middle;
    font-weight: 500;
    border: none;
    color: #2d3748;
    position: relative;
    transition: all 0.3s ease;
}

/* ID Cell Styling */
tbody td:first-child {
    font-weight: 700;
    color: var(--primary-dark);
    text-align: center;
    background: rgba(14, 119, 112, 0.08);
    border-radius: 8px;
    margin: 4px;
    width: 50px;
    position: relative;
}

tbody td:first-child::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.1), rgba(27, 209, 194, 0.1));
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: -1;
    transition: all 0.3s ease;
}

tbody tr:hover td:first-child::before {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.2), rgba(27, 209, 194, 0.2));
}

/* Username Cell Enhancement */
tbody td:nth-child(2) {
    font-weight: 600;
    color: var(--primary-dark);
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.2), rgba(255, 255, 255, 0.1));
    border-radius: 12px;
    margin: 2px;
}

/* Password Cell Enhancement */
tbody td:nth-child(3) {
    background: rgba(128, 128, 128, 0.08);
    border-radius: 10px;
    margin: 2px;
    font-family: 'Courier New', monospace;
    color: #666;
}

/* Enhanced Badge System with Different Colors for Each Role */
.badge {
    font-size: 0.85em;
    padding: 10px 16px;
    border-radius: 25px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    position: relative;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.4s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Role-specific badge colors */
.badge[data-role="admin"], .badge:contains("admin"), 
tbody td:nth-child(4) .badge:nth-of-type(1) {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3) !important;
}

.badge[data-role="prof"], .badge:contains("prof") {
    background: linear-gradient(135deg, #3182ce 0%, #63b3ed 100%) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3) !important;
}

.badge[data-role="eleve"], .badge:contains("eleve") {
    background: linear-gradient(135deg, #38a169 0%, #68d391 100%) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(56, 161, 105, 0.3) !important;
}

/* Default badge styling (fallback) */
.badge.bg-info {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.8), rgba(27, 209, 194, 0.8)) !important;
    color: #fff;
}

.badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: var(--transition-smooth);
}

.badge:hover::before {
    left: 100%;
}

.badge:hover {
    transform: scale(1.1) rotate(2deg);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
}

/* Enhanced Action Buttons */
tbody td:last-child {
    background: rgba(240, 248, 255, 0.3);
    border-radius: 15px;
    margin: 2px;
    text-align: center;
}

/* Boutons actions with enhanced styling */
.btn-sm {
    padding: 10px 14px;
    font-size: 13px;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 4px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-warning {
    background: linear-gradient(135deg, #ed8936 0%, #fbb040 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
}

.btn-warning:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 10px 30px rgba(237, 137, 54, 0.4);
    background: linear-gradient(135deg, #dd7824 0%, #ea9f28 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

.btn-danger:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 10px 30px rgba(229, 62, 62, 0.4);
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
}

.btn-sm:active {
    transform: translateY(0) scale(0.95);
}

/* Subtle Animation Effects */
@keyframes rowSlideIn {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

tbody tr {
    animation: rowSlideIn 0.5s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.1s; }
tbody tr:nth-child(2) { animation-delay: 0.2s; }
tbody tr:nth-child(3) { animation-delay: 0.3s; }
tbody tr:nth-child(4) { animation-delay: 0.4s; }
tbody tr:nth-child(5) { animation-delay: 0.5s; }

/* Enhanced responsive behavior */
@media (max-width: 768px) {
    .table-responsive {
        padding: 10px;
    }
    
    table {
        font-size: 12px;
    }
    
    tbody td, thead th {
        padding: 12px 8px;
    }
    
    tbody tr:hover {
        transform: none;
        border-left: none;
    }
    
    .badge {
        font-size: 0.75em;
        padding: 6px 10px;
    }
    
    .btn-sm {
        padding: 6px 10px;
        font-size: 11px;
        margin: 0 2px;
    }
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: rgba(14, 119, 112, 0.1);
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.6), rgba(27, 209, 194, 0.6));
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.8), rgba(27, 209, 194, 0.8));
}

.badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: var(--transition-smooth);
}

.badge:hover::before {
    left: 100%;
}

.badge.bg-info {
    background: var(--success-gradient) !important;
    color: white;
}

/* Responsive Design for Mobile */
@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .card {
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .row.g-3 {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    h3, h4 {
        font-size: 1.5rem;
    }
    
    table {
        font-size: 12px;
    }
    
    tbody td, thead th {
        padding: 12px 8px;
    }
    
    form .form-control, 
    form .form-select {
        padding: 16px 16px 16px 50px !important;
        height: 50px;
    }
    
    .col-md-4:nth-child(2)::before,
    .col-md-4:nth-child(3)::before,
    .col-md-3:nth-child(4)::before {
        left: 15px;
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .card {
        padding: 20px;
        border-radius: var(--border-radius-md);
    }
    
    .btn-sm {
        padding: 8px 12px;
        font-size: 12px;
    }
}

/* Accessibility Enhancements */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Focus Management for Keyboard Navigation */
button:focus, input:focus, select:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    body {
        background: white !important;
    }
    
    .card {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
    
    .btn-sm {
        display: none !important;
    }
}

/* Optionnel : aligner le texte au centre */
select#role option {
    text-align: center;
}
</style>

</body>
</html>
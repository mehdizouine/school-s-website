<?php
session_start();
include("db.php");
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');
// Dossier d'uploads et image par défaut
$uploadDir = "uploads/";
$defaultPhoto = $uploadDir . "default.png";
if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Traitement du formulaire
if (isset($_POST['save_profile'])) {
    $id = $_POST['id'];
    $prenom = htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8');
    $nom = htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $dob = $_POST['dob'] ?? null;
    $annee = $_POST['annee'] ?? null;
    $classe = $_POST['classe'] ?? null; // ID de la classe

    // Upload photo si fournie
    $photo = '';
    if (!empty($_FILES['photo']['name'])) {
        $fileName = uniqid() . "_" . basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $photo = $targetFile;
        }
    }

    // Vérifier si le profil existe
    $check = $conn->prepare("SELECT * FROM profil WHERE ID=?");
    $check->bind_param("s", $id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // UPDATE
        if ($photo) {
            $stmt = $conn->prepare("UPDATE profil SET Photo=?, Prénom=?, Nom=?, Email=?, Date_de_naissance=?, Année_scolaire=?, Classe=? WHERE ID=?");
            $stmt->bind_param("ssssssss", $photo, $prenom, $nom, $email, $dob, $annee, $classe, $id);
        } else {
            $stmt = $conn->prepare("UPDATE profil SET Prénom=?, Nom=?, Email=?, Date_de_naissance=?, Année_scolaire=?, Classe=? WHERE ID=?");
            $stmt->bind_param("sssssss", $prenom, $nom, $email, $dob, $annee, $classe, $id);
        }
    } else {
        // INSERT
        if (!$photo) $photo = $defaultPhoto;
        $stmt = $conn->prepare("INSERT INTO profil (ID, Photo, Prénom, Nom, Email, Date_de_naissance, Année_scolaire, Classe) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $id, $photo, $prenom, $nom, $email, $dob, $annee, $classe);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: modif-profil.php");
    exit;
}

// Récupération des profils
$profils = $conn->query("
    SELECT l.ID, l.Username, p.Photo, p.Prénom, p.Nom, p.Email, 
           p.Date_de_naissance, p.Année_scolaire, p.Classe
    FROM login l
    LEFT JOIN profil p ON l.ID = p.ID
    ORDER BY l.Username
");

// Récupération de toutes les classes pour le select
$allClasses = $conn->query("SELECT ID, nom_de_classe FROM classes ORDER BY nom_de_classe ASC");
$classesArray = [];
while($c = $allClasses->fetch_assoc()) {
    $classesArray[$c['ID']] = $c['nom_de_classe'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Profils</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
<h2>Modifier / Ajouter Profils</h2>
<div class="profile-grid">
<?php while($row = $profils->fetch_assoc()): 
    $initiales = strtoupper(substr($row['Prénom'] ?? '',0,1) . substr($row['Nom'] ?? '',0,1));
?>
<div class="profile-card">
<form method="POST" enctype="multipart/form-data">
     <?= csrf_field() ?>
    <div class="profile-photo">
        <?php if (!empty($row['Photo'])): ?>
            <img src="<?= htmlspecialchars($row['Photo']) ?>" alt="Photo de profil">
        <?php else: ?>
            <?= $initiales ?: '👤' ?>
        <?php endif; ?>
    </div>

    <label>Utilisateur</label>
    <input type="text" value="<?= htmlspecialchars($row['Username']) ?>" disabled>

    <label>Photo</label>
    <input type="file" name="photo" accept="image/*">

    <label>Prénom</label>
    <input type="text" name="prenom" value="<?= htmlspecialchars($row['Prénom'] ?? '') ?>">

    <label>Nom</label>
    <input type="text" name="nom" value="<?= htmlspecialchars($row['Nom'] ?? '') ?>">

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($row['Email'] ?? '') ?>">

    <label>Date de naissance</label>
    <input type="date" name="dob" value="<?= htmlspecialchars($row['Date_de_naissance'] ?? '') ?>">

    <label>Année scolaire</label>
    <input type="date" name="annee" value="<?= htmlspecialchars($row['Année_scolaire'] ?? '') ?>">

    <label>Classe</label>
    <select name="classe" class="form-select profile-input">
        <?php foreach($classesArray as $idClasse => $nomClasse): ?>
            <option value="<?= $idClasse ?>" <?= ($row['Classe'] == $idClasse) ? 'selected' : '' ?>>
                <?= htmlspecialchars($nomClasse) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="hidden" name="id" value="<?= $row['ID'] ?>">
    <button type="submit" name="save_profile">💾 Enregistrer</button>
</form>
</div>
<?php endwhile; ?>
</div>
</div>
</body>
</html>

<style>
/* Enhanced Profile Management System CSS - Teal Theme with Light Colors */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Light color palette */
    --card-bg: rgba(255, 255, 255, 0.85);
    --card-hover-bg: rgba(255, 255, 255, 0.95);
    --input-bg: rgba(255, 255, 255, 0.9);
    --input-focus-bg: rgba(255, 255, 255, 1);
    --light-teal: rgba(226, 250, 248, 0.4);
    --very-light-teal: rgba(226, 250, 248, 0.2);
    
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

/* Enhanced Body and Background */
body {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
    color: #2d3748;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
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

/* Enhanced Typography */
h2 {
    background: rgba(27, 209, 194);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin: 40px 0;
    font-size: clamp(2rem, 5vw, 3rem);
    letter-spacing: -0.5px;
    position: relative;
}

h2::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 1s ease-out;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 100px; }
}

/* Container */
.container {
    max-width: 1400px;
    margin: auto;
    padding: 20px;
    position: relative;
    z-index: 1;
}

/* Enhanced Profile Grid */
.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    animation: gridFadeIn 0.8s ease-out;
}

@keyframes gridFadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Profile Cards */
.profile-card {
    background: var(--card-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    padding: 30px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    border: 1px solid var(--glass-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
    animation: cardSlideIn 0.6s ease-out;
    animation-fill-mode: both;
}

/* Staggered animation for cards */
.profile-card:nth-child(1) { animation-delay: 0.1s; }
.profile-card:nth-child(2) { animation-delay: 0.2s; }
.profile-card:nth-child(3) { animation-delay: 0.3s; }
.profile-card:nth-child(4) { animation-delay: 0.4s; }
.profile-card:nth-child(5) { animation-delay: 0.5s; }
.profile-card:nth-child(6) { animation-delay: 0.6s; }

@keyframes cardSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Card shimmer effect */
.profile-card::before {
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

.profile-card:hover::before {
    animation: shimmer 1.5s ease-in-out;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

.profile-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-heavy);
    background: var(--card-hover-bg);
    border-color: rgba(27, 209, 194, 0.4);
}

/* Enhanced Profile Photo */
.profile-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(27, 209, 194, 0.3);
    margin-bottom: 20px;
    background: linear-gradient(135deg, var(--light-teal), var(--very-light-teal));
    color: var(--primary-dark);
    font-weight: 700;
    font-size: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: var(--transition-smooth);
    position: relative;
    box-shadow: 0 8px 25px rgba(14, 119, 112, 0.2);
}

.profile-photo::after {
    content: '';
    position: absolute;
    inset: -2px;
    background: var(--primary-gradient);
    border-radius: 50%;
    z-index: -1;
    opacity: 0;
    transition: var(--transition-smooth);
}

.profile-card:hover .profile-photo::after {
    opacity: 1;
}

.profile-card:hover .profile-photo {
    transform: scale(1.05) rotate(2deg);
    border-color: rgba(27, 209, 194, 0.6);
}

.profile-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: var(--transition-smooth);
}

.profile-card:hover .profile-photo img {
    transform: scale(1.1);
}

/* Enhanced Labels */
label {
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-dark);
    margin-top: 12px;
    margin-bottom: 6px;
    align-self: flex-start;
    position: relative;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

label::before {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: var(--transition-smooth);
}

.profile-card input:focus + label::before,
label:hover::before {
    width: 100%;
}

/* Enhanced Input Fields */
.profile-card input {
    padding: 15px 18px;
    border-radius: var(--border-radius-sm);
    border: 2px solid rgba(27, 209, 194, 0.3);
    width: 100%;
    margin-bottom: 12px;
    transition: var(--transition-smooth);
    background: var(--input-bg);
    backdrop-filter: blur(5px);
    color: #2d3748;
    font-weight: 500;
    font-size: 15px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 2px 10px rgba(14, 119, 112, 0.08);
}

.profile-card input:focus {
    border-color: var(--primary-dark);
    outline: none;
    background: var(--input-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 4px 20px rgba(14, 119, 112, 0.15);
    transform: translateY(-2px);
}

.profile-card input:hover {
    border-color: var(--primary-color);
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 3px 15px rgba(14, 119, 112, 0.12);
}

/* Disabled input styling */
.profile-card input:disabled {
    background: rgba(226, 250, 248, 0.5);
    color: var(--primary-dark);
    font-weight: 600;
    border-color: rgba(14, 119, 112, 0.2);
    cursor: not-allowed;
}

/* File input enhancement */
.profile-card input[type="file"] {
    padding: 12px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(226, 250, 248, 0.3));
    border: 2px dashed rgba(27, 209, 194, 0.4);
    cursor: pointer;
    position: relative;
}

.profile-card input[type="file"]:hover {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(226, 250, 248, 0.4));
}

.profile-card input[type="file"]:focus {
    border-style: solid;
}

/* Enhanced Button */
.profile-card button {
    background: var(--primary-gradient);
    color: #fff;
    font-weight: 700;
    border: none;
    padding: 16px 24px;
    width: 100%;
    border-radius: 50px;
    margin-top: 15px;
    cursor: pointer;
    transition: var(--transition-elastic);
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

.profile-card button::before {
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

.profile-card button:hover::before {
    width: 300px;
    height: 300px;
}

.profile-card button:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

.profile-card button:active {
    transform: translateY(0) scale(0.98);
}

/* Input type specific styling */
.profile-card input[type="email"] {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(240, 248, 255, 0.3));
}

.profile-card input[type="date"] {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 248, 220, 0.3));
}

.profile-card input[type="text"]:not(:disabled) {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.3));
}

/* Enhanced validation states */
.profile-card input:valid {
    border-color: rgba(34, 197, 94, 0.5);
}

.profile-card input:invalid:not(:placeholder-shown):not(:focus) {
    border-color: rgba(239, 68, 68, 0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .profile-card {
        padding: 25px;
    }
    
    .profile-photo {
        width: 100px;
        height: 100px;
        font-size: 32px;
    }
    
    h2 {
        font-size: 2rem;
        margin: 30px 0;
    }
}

@media (max-width: 480px) {
    .profile-card {
        padding: 20px;
    }
    
    .profile-photo {
        width: 90px;
        height: 90px;
        font-size: 28px;
    }
    
    .profile-card input {
        padding: 12px 15px;
        font-size: 14px;
    }
    
    .profile-card button {
        padding: 14px 20px;
        font-size: 14px;
    }
    
    h2 {
        font-size: 1.8rem;
    }
}

/* Loading animation for form */
form {
    width: 100%;
    animation: formFadeIn 0.6s ease-out;
}

@keyframes formFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom scrollbar */
body::-webkit-scrollbar {
    width: 12px;
}

body::-webkit-scrollbar-track {
    background: rgba(14, 119, 112, 0.1);
}

body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.6), rgba(27, 209, 194, 0.6));
    border-radius: 6px;
}

body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.8), rgba(27, 209, 194, 0.8));
}

/* Accessibility enhancements */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .profile-card:hover {
        transform: none;
    }
    
    .profile-photo:hover {
        transform: none;
    }
}

/* Focus management */
button:focus, input:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .profile-card {
        background: #ffffff;
        border: 2px solid #000000;
    }
    
    .profile-card input {
        border: 2px solid #000000;
        background: #ffffff;
    }
    
    .profile-photo {
        border: 3px solid #000000;
    }
}

/* Print styles */
@media print {
    body {
        background: white !important;
    }
    
    .profile-card {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .profile-card button {
        display: none !important;
    }
    
    .profile-grid {
        display: block;
    }
}
/* Style select pour qu'il corresponde aux inputs */
.profile-card select.profile-input {
    padding: 15px 18px;
    border-radius: var(--border-radius-sm);
    border: 2px solid rgba(27, 209, 194, 0.3);
    width: 100%;
    margin-bottom: 12px;
    transition: var(--transition-smooth);
    background: var(--input-bg);
    backdrop-filter: blur(5px);
    color: #2d3748;
    font-weight: 500;
    font-size: 15px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 2px 10px rgba(14, 119, 112, 0.08);
}

.profile-card select.profile-input:focus {
    border-color: var(--primary-dark);
    outline: none;
    background: var(--input-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 4px 20px rgba(14, 119, 112, 0.15);
    transform: translateY(-2px);
}

.profile-card select.profile-input:hover {
    border-color: var(--primary-color);
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 3px 15px rgba(14, 119, 112, 0.12);
}

</style>
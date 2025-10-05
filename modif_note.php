<?php
session_start();
include('db.php');
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');
// Message de succès ou d'erreur
$success = "";

// Ajouter ou mettre à jour une note
if(isset($_POST['add_note'])){
    $student = (int)$_POST['student'];
    $matiere = (int)$_POST['matiere'];
    $examen = (int)$_POST['examen'];
    $semestre = (int)$_POST['semestre'];
    $note = (float)$_POST['note'];

    // Correction : ON DUPLICATE KEY UPDATE compatible avec mysqli préparé
    $stmt = $conn->prepare("
        INSERT INTO note (ID_eleve, ID_matiere, ID_exam, ID_semestre, note)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            ID_semestre = ?,
            note = ?
    ");
    $stmt->bind_param("iiiiddd", $student, $matiere, $examen, $semestre, $note, $semestre, $note);

    if($stmt->execute()){
        $success = "✅ Note ajoutée / modifiée avec succès !";
    } else {
        $success = "❌ Erreur : " . $stmt->error;
    }
    $stmt->close();
}

// Supprimer une note
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM note WHERE ID_note='$id'");
    $success = "✅ Note supprimée avec succès !";
}

// Récupérer toutes les notes
$result = $conn->query("
    SELECT n.ID_note, s.Username AS student, m.matiere, e.nom_examen, sem.nom_semestre, n.note
    FROM note n
    JOIN login s ON n.ID_eleve = s.ID
    JOIN matiere m ON n.ID_matiere = m.ID_matiere
    JOIN examen e ON n.ID_exam = e.ID_examen
    JOIN semestre sem ON n.ID_semestre = sem.ID_semestre
    ORDER BY s.Username, sem.ID_semestre, m.matiere, e.ID_examen
");
?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des Notes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>

<div class="container">
    <h2>📌 Gestion des Notes</h2>

    <div class="card">
        <h4>Ajouter / Modifier une note</h4>
        <?php if($success): ?>
            <div class="alert alert-info"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST" class="row g-2">
             <?= csrf_field() ?>
            <div class="col-md-2">
                <select name="semestre" id="semestre" onchange="filterExamens()" required>
                    <option value=''>Semestre</option>
                    <?php 
                    $semestres = $conn->query("SELECT ID_semestre, nom_semestre FROM semestre");
                    while($s = $semestres->fetch_assoc()){
                        echo "<option value='{$s['ID_semestre']}'>{$s['nom_semestre']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="student" required>
                    <option value="">Élève</option>
                    <?php 
                    $students = $conn->query("SELECT ID, Username FROM login WHERE role='eleve'");
                    while($s = $students->fetch_assoc()){
                        echo "<option value='{$s['ID']}'>{$s['Username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="matiere" required>
                    <option value="">Matière</option>
                    <?php 
                    $matieres = $conn->query("SELECT ID_matiere, matiere FROM matiere");
                    while($m = $matieres->fetch_assoc()){
                        echo "<option value='{$m['ID_matiere']}'>{$m['matiere']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="examen" id="examen" required>
                    <option value="">Examen</option>
                    <?php 
                    $examens = $conn->query("SELECT ID_examen, nom_examen, semestre FROM examen");
                    while($e = $examens->fetch_assoc()){
                        echo "<option value='{$e['ID_examen']}' data-sem='{$e['semestre']}'>{$e['nom_examen']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-1">
                <input type="number" name="note" placeholder="Note" min="0" max="20" required>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" name="add_note">💾 Ajouter / Modifier</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h4>📑 Liste des notes</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Élève</th>
                        <th>Matière</th>
                        <th>Examen</th>
                        <th>Semestre</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['ID_note'] ?></td>
                        <td><?= htmlspecialchars($row['student']) ?></td>
                        <td><?= htmlspecialchars($row['matiere']) ?></td>
                        <td><?= htmlspecialchars($row['nom_examen']) ?></td>
                        <td><?= htmlspecialchars($row['nom_semestre']) ?></td>
                        <td><?= $row['note'] ?></td>
                        <td>
                            <a href="?delete=<?= $row['ID_note'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette note ?')">
                                Supprimer
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
function filterExamens() {
    var sem = document.getElementById('semestre').value;
    var exams = document.getElementById('examen').options;
    for(var i=0;i<exams.length;i++){
        exams[i].style.display = exams[i].getAttribute('data-sem') == sem ? 'block' : 'none';
    }
    document.getElementById('examen').value = '';
}
</script>

</body>
</html>

<style>

/* Enhanced Notes Management System CSS - Light Teal Theme */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Table-specific light colors */
    --table-header-bg: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.85) 100%);
    --table-row-even: rgba(226, 250, 248, 0.4);     /* Very light teal */
    --table-row-odd: rgba(255, 255, 255, 0.7);      /* Soft white */
    --table-hover: rgba(178, 245, 234, 0.4);        /* Light teal on hover */
    --table-border: rgba(14, 119, 112, 0.15);       /* Subtle teal borders */
    
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
    padding: 20px;
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

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Enhanced Typography */
h2 {
    background: rgba(27, 209, 194);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin-bottom: 35px;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    letter-spacing: -0.5px;
    position: relative;
}

h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 0.8s ease-out;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 80px; }
}

h4 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin: 25px 0;
    font-size: clamp(1.2rem, 3vw, 1.8rem);
    letter-spacing: -0.3px;
}

/* Enhanced Card Design */
.card {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    padding: 35px;
    margin-bottom: 35px;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

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
    transform: translateY(-5px) scale(1.01);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(27, 209, 194, 0.3);
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

/* Enhanced Form Styling */
form {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius-md);
    padding: 30px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
}

form .row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    align-items: end;
}

/* Individual form field containers with icons */
form .col-md-2, form .col-md-3, form .col-md-1 {
    position: relative;
    flex: 1;
    min-width: 160px;
}

/* Base styling for all form controls */
form select, form input[type="number"] {
    width: 100%;
    padding: 18px 20px 18px 55px !important;
    border-radius: var(--border-radius-md) !important;
    border: 2px solid rgba(27, 209, 194, 0.3) !important;
    font-size: 15px;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    color: #2d3748 !important;
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 20px rgba(14, 119, 112, 0.1) !important;
    height: 58px;
    appearance: none;
    cursor: pointer;
}

/* Input field specific styling */
form input[type="number"] {
    padding: 18px 20px !important;
    text-align: center;
    font-weight: 700;
    font-size: 16px;
}

/* Icons for form fields */
form .col-md-2:nth-child(1)::before {
    content: '\F123';  /* bi-calendar */
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

form .col-md-2:nth-child(2)::before {
    content: '\F4DA';  /* bi-person */
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

form .col-md-2:nth-child(3)::before {
    content: '\F2A4';  /* bi-book */
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

form .col-md-3::before {
    content: '\F2F5';  /* bi-clipboard-check */
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

/* Custom dropdown arrows for selects */
form select {
    background-image: linear-gradient(45deg, transparent 50%, var(--primary-color) 50%), 
                      linear-gradient(135deg, var(--primary-color) 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 2px), 
                         calc(100% - 15px) calc(1em + 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    padding-right: 50px !important;
}

/* Enhanced select options styling */
form select option {
    background: rgba(255, 255, 255, 0.95);
    color: #2d3748;
    padding: 12px 15px;
    font-weight: 500;
    border-bottom: 1px solid rgba(14, 119, 112, 0.1);
}

form select option:hover {
    background: rgba(226, 250, 248, 0.3);
}

form select option:checked {
    background: var(--primary-color);
    color: white;
}

/* Placeholder styling for selects */
form select option[value=""] {
    color: rgba(27, 209, 194, 0.6);
    font-style: italic;
    font-weight: 600;
}

/* Focus states with icon animation */
form select:focus, form input[type="number"]:focus {
    outline: none !important;
    border-color: var(--primary-dark) !important;
    background: rgba(255, 255, 255, 1) !important;
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 8px 30px rgba(27, 209, 194, 0.2) !important;
    transform: translateY(-3px);
}

/* Icon animation on focus */
form .col-md-2:focus-within::before,
form .col-md-3:focus-within::before {
    color: var(--primary-dark);
    transform: translateY(-50%) scale(1.2);
    filter: drop-shadow(0 0 8px rgba(27, 209, 194, 0.3));
}

/* Hover effects */
form select:hover, form input[type="number"]:hover {
    border-color: var(--primary-color) !important;
    background: rgba(255, 255, 255, 0.98) !important;
    transform: translateY(-2px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 25px rgba(27, 209, 194, 0.15) !important;
}

/* Icon hover animation */
form .col-md-2:hover::before,
form .col-md-3:hover::before {
    color: var(--primary-dark);
    transform: translateY(-50%) scale(1.1);
}

/* Enhanced number input styling */
form input[type="number"] {
    position: relative;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(226, 250, 248, 0.2)) !important;
}

form input[type="number"]::-webkit-outer-spin-button,
form input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

form input[type="number"] {
    -moz-appearance: textfield;
}

/* Valid state styling */
form select:valid, form input[type="number"]:valid {
    border-color: rgba(34, 197, 94, 0.5) !important;
}

/* Invalid state styling */
form select:invalid:not([value=""]), form input[type="number"]:invalid:not(:placeholder-shown) {
    border-color: rgba(239, 68, 68, 0.5) !important;
}

/* Enhanced styling for different select types */
form select[name="semestre"] {
    background-color: rgba(255, 248, 220, 0.9) !important;
}

form select[name="student"] {
    background-color: rgba(240, 248, 255, 0.9) !important;
}

form select[name="matiere"] {
    background-color: rgba(248, 250, 252, 0.9) !important;
}

form select[name="examen"] {
    background-color: rgba(255, 245, 238, 0.9) !important;
}

/* Enhanced Button */
form button {
    background: var(--primary-gradient);
    border: none;
    border-radius: 50px;
    padding: 15px 30px;
    color: white;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

form button:active {
    transform: translateY(0) scale(0.98);
}

/* Enhanced Alert */
.alert {
    background: linear-gradient(135deg, rgba(178, 245, 234, 0.2), rgba(226, 250, 248, 0.3));
    border: 1px solid rgba(14, 119, 112, 0.3);
    color: var(--primary-dark);
    border-radius: var(--border-radius-md);
    padding: 15px 25px;
    margin: 20px 0;
    font-weight: 600;
    text-align: center;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(14, 119, 112, 0.1);
}

/* Enhanced Table Container */
.table-responsive {
    border-radius: var(--border-radius-md);
    overflow: hidden;
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(255, 255, 255, 0.6));
    backdrop-filter: blur(15px);
    padding: 15px;
    margin-top: 25px;
    box-shadow: 
        0 15px 35px rgba(14, 119, 112, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    border: 1px solid rgba(14, 119, 112, 0.1);
    position: relative;
}

/* Enhanced Table */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 15px;
    color: #2d3748;
    background: transparent;
    margin: 0;
    border: none;
}

/* Enhanced Header */
thead th {
    background: var(--table-header-bg);
    color: #ffffff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    padding: 20px 16px;
    font-size: 13px;
    border: none;
    position: relative;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    border-right: 1px solid rgba(255, 255, 255, 0.15);
    transition: var(--transition-smooth);
}

thead th:last-child {
    border-right: none;
}

/* Add icons to headers */
thead th:nth-child(1)::before { content: '\F292 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* ID */
thead th:nth-child(2)::before { content: '\F4DA '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Student */
thead th:nth-child(3)::before { content: '\F2A4 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Subject */
thead th:nth-child(4)::before { content: '\F2F5 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Exam */
thead th:nth-child(5)::before { content: '\F123 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Semester */
thead th:nth-child(6)::before { content: '\F586 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Note */
thead th:nth-child(7)::before { content: '\F3E5 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Actions */

/* Enhanced Body Rows with Light Colors */
tbody tr {
    background: var(--table-row-odd);
    transition: var(--transition-smooth);
    border: none;
    position: relative;
}

tbody tr:nth-child(even) {
    background: var(--table-row-even);
}

/* Beautiful Hover Effect */
tbody tr:hover {
    background: var(--table-hover) !important;
    transform: translateX(8px) scale(1.02);
    box-shadow: 
        8px 0 25px rgba(27, 209, 194, 0.15),
        0 4px 20px rgba(14, 119, 112, 0.1);
    border-left: 4px solid var(--primary-color);
}

/* Enhanced Cells */
tbody td {
    padding: 18px 16px;
    vertical-align: middle;
    font-weight: 500;
    border: none;
    border-bottom: 1px solid var(--table-border);
    color: #2d3748;
    transition: var(--transition-smooth);
}

/* ID Cell Styling */
tbody td:first-child {
    font-weight: 700;
    color: var(--primary-dark);
    text-align: center;
    background: rgba(14, 119, 112, 0.08);
    border-radius: 10px;
    margin: 4px;
    position: relative;
}

tbody td:first-child::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.1), rgba(27, 209, 194, 0.1));
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: -1;
    transition: all 0.3s ease;
}

tbody tr:hover td:first-child::before {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.2), rgba(27, 209, 194, 0.2));
}

/* Student Name Cell Enhancement */
tbody td:nth-child(2) {
    font-weight: 600;
    color: var(--primary-dark);
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.2), rgba(255, 255, 255, 0.1));
    border-radius: 12px;
    margin: 2px;
}

/* Subject Cell Enhancement */
tbody td:nth-child(3) {
    background: rgba(135, 206, 250, 0.1);
    border-radius: 10px;
    margin: 2px;
    font-weight: 600;
    color: #2563eb;
}

/* Exam Cell Enhancement */
tbody td:nth-child(4) {
    background: rgba(255, 165, 0, 0.1);
    border-radius: 10px;
    margin: 2px;
    font-weight: 600;
    color: #d97706;
}

/* Semester Cell Enhancement */
tbody td:nth-child(5) {
    background: rgba(139, 69, 19, 0.1);
    border-radius: 10px;
    margin: 2px;
    font-weight: 600;
    color: #92400e;
}

/* Note Cell Enhancement with Color Coding */
tbody td:nth-child(6) {
    font-weight: 700;
    font-size: 16px;
    text-align: center;
    border-radius: 12px;
    margin: 2px;
    position: relative;
}

/* Note color coding based on value */
tbody td:nth-child(6)[data-note] {
    color: #dc2626; /* Default red for low scores */
    background: rgba(220, 38, 38, 0.1);
}

/* You can enhance this with JavaScript to add data-note attributes based on score */

/* Actions Cell Enhancement */
tbody td:last-child {
    background: rgba(248, 113, 113, 0.1);
    border-radius: 15px;
    margin: 2px;
    text-align: center;
}

/* Enhanced Delete Button */
.btn-danger {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: var(--transition-elastic);
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
    text-decoration: none;
    display: inline-block;
}

.btn-danger:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 10px 30px rgba(229, 62, 62, 0.4);
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
    color: #fff;
    text-decoration: none;
}

.btn-danger:active {
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

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .card {
        padding: 25px;
        margin-bottom: 25px;
    }
    
    form .row {
        flex-direction: column;
        gap: 15px;
    }
    
    form select, form input[type="number"] {
        min-width: 100%;
        padding: 12px 15px;
    }
    
    .table-responsive {
        padding: 10px;
    }
    
    table {
        font-size: 13px;
    }
    
    tbody td, thead th {
        padding: 12px 10px;
    }
    
    tbody tr:hover {
        transform: none;
        border-left: none;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    h4 {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .card {
        padding: 20px;
    }
    
    form {
        padding: 20px;
    }
    
    tbody td, thead th {
        padding: 10px 8px;
        font-size: 12px;
    }
    
    .btn-danger {
        padding: 8px 12px;
        font-size: 12px;
    }
}

/* Custom Scrollbar */
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

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    tbody tr:hover {
        transform: none;
    }
}

/* Focus Management */
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
    
    .table-responsive {
        background: white !important;
        box-shadow: none !important;
    }
    
    thead th {
        background: #f0f0f0 !important;
        color: #000000 !important;
    }
    
    .btn-danger {
        display: none !important;
    }
}
</style>


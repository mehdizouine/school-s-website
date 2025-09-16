<?php 
session_start();
include("db.php");

// Fonction pour récupérer toutes les notes d'un élève par semestre
function getGrades($conn, $semestreId, $studentId) {
    $sql = "SELECT m.matiere, e.nom_examen, n.note
            FROM note n
            JOIN matiere m ON n.ID_matiere = m.ID_matiere
            JOIN examen e ON n.ID_exam = e.ID_examen
            WHERE n.ID_semestre = ? AND n.ID_eleve = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $semestreId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $subject = $row["matiere"];
        $exam = $row["nom_examen"];
        $grades[$subject][$exam] = $row["note"];
    }

    $stmt->close();
    return $grades;
}

// Fonction pour récupérer tous les examens d'un semestre
function getExams($conn, $semestreId) {
    $stmt = $conn->prepare("SELECT nom_examen FROM examen WHERE semestre = ? ORDER BY ID_examen");
    $stmt->bind_param("i", $semestreId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = [];
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row['nom_examen'];
    }
    $stmt->close();
    return $exams;
}

// Récupérer l'ID élève depuis la session
$studentId = $_SESSION['user_id'] ?? 1; // Remplacer par la variable de session correcte

// Récupérer notes et examens pour les semestres 1 et 2
$gradesSem1 = getGrades($conn, 1, $studentId);
$gradesSem2 = getGrades($conn, 2, $studentId);
$examsSem1 = getExams($conn, 1);
$examsSem2 = getExams($conn, 2);

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/alwah logo.png">
    <title>Mes Notes</title>
    <style>
/* Enhanced Grades Display System CSS - Teal Theme with Light Colors */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Light color palette for grades display */
    --container-bg: rgba(255, 255, 255, 0.9);
    --table-header-bg: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.85) 100%);
    --table-row-even: rgba(226, 250, 248, 0.4);
    --table-row-odd: rgba(255, 255, 255, 0.7);
    --table-hover: rgba(178, 245, 234, 0.4);
    --select-bg: rgba(226, 250, 248, 0.3);
    --select-focus-bg: rgba(226, 250, 248, 0.5);
    
    --glass-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.4);
    --backdrop-blur: blur(15px);
    
    --shadow-light: 0 8px 25px rgba(14, 119, 112, 0.15);
    --shadow-medium: 0 15px 35px rgba(14, 119, 112, 0.2);
    --shadow-heavy: 0 20px 40px rgba(14, 119, 112, 0.25);
    
    --border-radius-sm: 12px;
    --border-radius-md: 20px;
    --border-radius-lg: 28px;
    
    --transition-smooth: all 0.4s ease;
    --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Enhanced Universal Reset */
* {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Lucida Sans', Geneva, Verdana, sans-serif;
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Enhanced Body with Teal Background */
body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
    position: relative;
    overflow-x: hidden;
    padding: 20px;
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
        radial-gradient(circle at 20% 30%, rgba(27, 209, 194, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(27, 209, 194, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 50% 10%, rgba(14, 119, 112, 0.08) 0%, transparent 50%);
    animation: backgroundShift 25s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes backgroundShift {
    0%, 100% { 
        opacity: 0.4; 
        transform: translateY(0px) rotate(0deg); 
    }
    33% { 
        opacity: 0.7; 
        transform: translateY(-10px) rotate(0.5deg); 
    }
    66% { 
        opacity: 0.5; 
        transform: translateY(5px) rotate(-0.5deg); 
    }
}

/* Enhanced Marks Container */
.Marks-container {
    width: 100%;
    max-width: 1200px;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    padding: 50px;
    border-radius: var(--border-radius-lg);
    gap: 25px;
    text-align: center;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-medium);
    border: 1px solid var(--glass-border);
    position: relative;
    overflow: hidden;
    animation: containerSlideIn 0.8s ease-out;
}

@keyframes containerSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Container shimmer effect */
.Marks-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(27, 209, 194, 0.08), transparent);
    transform: rotate(45deg);
    animation: shimmer 4s ease-in-out infinite;
    opacity: 0.6;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

/* Enhanced Typography */
h2 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 35px;
    font-weight: 700;
    font-size: clamp(2.5rem, 5vw, 3.8rem);
    letter-spacing: -0.8px;
    position: relative;
    z-index: 1;
    animation: titleSlideIn 0.8s ease-out 0.2s both;
}

@keyframes titleSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

h2::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 1.2s ease-out 0.5s both;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 120px; }
}

/* Enhanced Select Dropdown */
select {
    padding: 18px 25px;
    border-radius: var(--border-radius-md);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 30px;
    border: 2px solid rgba(27, 209, 194, 0.3);
    background: var(--select-bg);
    backdrop-filter: blur(10px);
    color: var(--primary-dark);
    cursor: pointer;
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 20px rgba(14, 119, 112, 0.1);
    min-width: 300px;
    appearance: none;
    position: relative;
    z-index: 1;
    animation: selectSlideIn 0.6s ease-out 0.4s both;
    
    /* Custom dropdown arrow */
    background-image: linear-gradient(45deg, transparent 50%, var(--primary-color) 50%), 
                      linear-gradient(135deg, var(--primary-color) 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 2px), 
                         calc(100% - 15px) calc(1em + 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    padding-right: 50px;
}

@keyframes selectSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

select:focus {
    outline: none;
    border-color: var(--primary-dark);
    background: var(--select-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 8px 30px rgba(14, 119, 112, 0.2);
    transform: translateY(-2px);
}

select:hover {
    border-color: var(--primary-color);
    background: rgba(226, 250, 248, 0.4);
    transform: translateY(-1px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 25px rgba(14, 119, 112, 0.15);
}

/* Enhanced Select Options */
select option {
    background: rgba(255, 255, 255, 0.95);
    color: var(--primary-dark);
    padding: 12px 15px;
    font-weight: 500;
}

select option:checked {
    background: var(--primary-color);
    color: white;
}

select option[value=""] {
    color: rgba(27, 209, 194, 0.6);
    font-style: italic;
}

/* Enhanced Table Container */
.table-container {
    margin-top: 30px;
    width: 100%;
    display: none;
    overflow-x: auto;
    border-radius: var(--border-radius-md);
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.3), rgba(255, 255, 255, 0.5));
    backdrop-filter: blur(10px);
    padding: 20px;
    box-shadow: var(--shadow-medium);
    border: 1px solid rgba(14, 119, 112, 0.1);
    animation: tableSlideIn 0.6s ease-out;
}

@keyframes tableSlideIn {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        max-height: 1000px;
    }
}

/* Enhanced Table */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: var(--border-radius-sm);
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(14, 119, 112, 0.1);
    background: transparent;
}

/* Enhanced Table Headers */
th {
    padding: 20px 15px;
    background: var(--table-header-bg);
    color: #ffffff;
    text-align: center;
    font-weight: 700;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    position: relative;
}

th:last-child {
    border-right: none;
}

/* Add icons to specific headers */
th:first-child::before {
    content: '\F2A4 '; /* bi-book (subject icon) */
    font-family: 'bootstrap-icons';
    margin-right: 8px;
}

/* Enhanced Table Cells */
td {
    padding: 18px 15px;
    text-align: center;
    border: none;
    border-bottom: 1px solid rgba(14, 119, 112, 0.1);
    font-weight: 500;
    font-size: 15px;
    color: #2d3748;
    transition: var(--transition-smooth);
    position: relative;
}

/* Enhanced Table Rows */
tbody tr {
    background: var(--table-row-odd);
    transition: var(--transition-smooth);
    animation: rowSlideIn 0.5s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(even) {
    background: var(--table-row-even);
}

/* Staggered animation for rows */
tbody tr:nth-child(1) { animation-delay: 0.1s; }
tbody tr:nth-child(2) { animation-delay: 0.2s; }
tbody tr:nth-child(3) { animation-delay: 0.3s; }
tbody tr:nth-child(4) { animation-delay: 0.4s; }
tbody tr:nth-child(5) { animation-delay: 0.5s; }

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

tbody tr:hover {
    background: var(--table-hover) !important;
    transform: translateX(8px) scale(1.01);
    box-shadow: 
        8px 0 25px rgba(27, 209, 194, 0.15),
        0 4px 20px rgba(14, 119, 112, 0.1);
    border-left: 4px solid var(--primary-color);
}

/* Subject Cell Enhancement */
tbody td:first-child {
    font-weight: 700;
    color: var(--primary-dark);
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.2), rgba(255, 255, 255, 0.1));
    border-radius: 8px 0 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 14px;
}

/* Grade Cell Enhancement with Color Coding */
tbody td:not(:first-child) {
    font-weight: 700;
    font-size: 16px;
    position: relative;
}

/* Color coding for grades */
tbody td:not(:first-child):not(:empty) {
    background: rgba(34, 197, 94, 0.1); /* Default green for good grades */
    border-radius: 8px;
    margin: 2px;
}

/* Empty cells styling */
tbody td:empty {
    background: rgba(156, 163, 175, 0.1);
    border-radius: 8px;
    margin: 2px;
    position: relative;
}

tbody td:empty::after {
    content: '—';
    color: rgba(156, 163, 175, 0.6);
    font-weight: 400;
}

/* Enhanced Custom Scrollbar for table container */
.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: rgba(14, 119, 112, 0.1);
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.6), rgba(27, 209, 194, 0.6));
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.8), rgba(27, 209, 194, 0.8));
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .Marks-container {
        padding: 30px 25px;
        gap: 20px;
    }
    
    h2 {
        font-size: 2.2rem;
        margin-bottom: 25px;
    }
    
    select {
        min-width: 100%;
        padding: 15px 20px;
        font-size: 15px;
        margin-bottom: 25px;
    }
    
    .table-container {
        padding: 15px;
        margin-top: 20px;
    }
    
    th, td {
        padding: 12px 10px;
        font-size: 13px;
    }
    
    tbody tr:hover {
        transform: none;
        border-left: none;
    }
    
    tbody td:first-child {
        font-size: 12px;
    }
    
    tbody td:not(:first-child) {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .Marks-container {
        padding: 25px 20px;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    select {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .table-container {
        padding: 12px;
    }
    
    th, td {
        padding: 10px 8px;
        font-size: 12px;
    }
    
    tbody td:first-child {
        font-size: 11px;
    }
    
    tbody td:not(:first-child) {
        font-size: 13px;
    }
}

/* Enhanced Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    tbody tr:hover {
        transform: none;
    }
    
    select:hover, select:focus {
        transform: none;
    }
}

/* Focus management for accessibility */
select:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .Marks-container, .table-container {
        background: #ffffff;
        border: 3px solid #000000;
    }
    
    select {
        background: #ffffff;
        border: 2px solid #000000;
        color: #000000;
    }
    
    th {
        background: #000000 !important;
        color: #ffffff !important;
    }
    
    tbody tr {
        background: #ffffff !important;
    }
    
    tbody tr:nth-child(even) {
        background: #f0f0f0 !important;
    }
    
    tbody tr:hover {
        background: #e0e0e0 !important;
        border-left: 4px solid #000000;
    }
}

/* Print styles */
@media print {
    body {
        background: white !important;
    }
    
    .Marks-container, .table-container {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        backdrop-filter: none !important;
    }
    
    th {
        background: #f0f0f0 !important;
        color: #000000 !important;
        text-shadow: none !important;
    }
    
    tbody tr {
        background: white !important;
    }
    
    tbody tr:nth-child(even) {
        background: #f9f9f9 !important;
    }
    
    select {
        background: white !important;
        border: 1px solid #ccc !important;
    }
}
    </style>
</head>
<body>
    <div class="Marks-container">
        <h2>Mes Notes</h2>

        <select id="semesterSelect">
            <option value="">-- Choisir un semestre --</option>
            <option value="1">Semestre 1</option>
            <option value="2">Semestre 2</option>
        </select>

        <!-- Semestre 1 -->
        <div class="table-container" id="table-sem1">
            <table>
                <thead>
                    <tr>
                        <th>Matière</th>
                        <?php foreach($examsSem1 as $examName): ?>
                            <th><?= htmlspecialchars($examName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gradesSem1 as $subject => $examNotes): ?>
                        <tr>
                            <td><?= htmlspecialchars($subject) ?></td>
                            <?php foreach($examsSem1 as $examName): ?>
                                <td><?= $examNotes[$examName] ?? "" ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Semestre 2 -->
        <div class="table-container" id="table-sem2">
            <table>
                <thead>
                    <tr>
                        <th>Matière</th>
                        <?php foreach($examsSem2 as $examName): ?>
                            <th><?= htmlspecialchars($examName) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gradesSem2 as $subject => $examNotes): ?>
                        <tr>
                            <td><?= htmlspecialchars($subject) ?></td>
                            <?php foreach($examsSem2 as $examName): ?>
                                <td><?= $examNotes[$examName] ?? "" ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const semesterSelect = document.getElementById('semesterSelect');
        const tableSem1 = document.getElementById('table-sem1');
        const tableSem2 = document.getElementById('table-sem2');

        semesterSelect.addEventListener('change', function() {
            tableSem1.style.display = "none";
            tableSem2.style.display = "none";

            if (this.value === "1") tableSem1.style.display = "block";
            else if (this.value === "2") tableSem2.style.display = "block";
        });
    </script>
</body>
</html>

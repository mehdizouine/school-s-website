<?php
include('db.php'); // connexion DB
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');
$message = '';

// Si le formulaire est soumis
if (isset($_POST['table']) && isset($_POST['action'])) {

    // Sécuriser le nom de la table
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table']);
    $action = $_POST['action'];

    // Vérifier que la table existe
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $message = "Table inexistante !";
    } else {
        if ($action === 'export') {
            // --- EXPORT CSV ---
            $filename = $table . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

            $output = fopen("php://output", "w");

            // BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Colonnes
            $res = $conn->query("SELECT * FROM `$table` LIMIT 1");
            $columns = $res->fetch_fields();
            $headers = [];
            foreach ($columns as $col) $headers[] = $col->name;
            fputcsv($output, $headers, ';');

            // Lignes
            $rows = $conn->query("SELECT * FROM `$table`");
            while ($row = $rows->fetch_assoc()) fputcsv($output, $row, ';');

            fclose($output);
            exit;

        } elseif ($action === 'delete') {
            // --- SUPPRESSION AVEC CONFIRMATION ---
            if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
                if ($conn->query("DROP TABLE `$table`") === TRUE) {
                    $message = "La table '$table' a été supprimée.";
                } else {
                    $message = "Erreur : " . $conn->error;
                }
            } else {
                // Demande de confirmation avec style
                ?>
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <link rel="icon" href="assets/img/alwah logo.png">
                    <title>Confirmation de suppression</title>
                    <style><?php include 'export-styles.css'; ?></style>
                </head>
                <body>
                    <div class="export-container">
                        <div class="card">
                            <h2>⚠️ Confirmation de suppression</h2>
                            <div class="alert alert-warning">
                                <p>Voulez-vous vraiment supprimer la table '<strong><?= htmlspecialchars($table) ?></strong>' ?</p>
                                <p class="warning-text">Cette action est irréversible !</p>
                            </div>
                            <form method="POST" class="confirmation-form">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                                <input type="hidden" name="action" value="delete">
                                <div class="button-group">
                                    <button type="submit" name="confirm" value="yes" class="btn-danger">
                                        🗑️ Oui, supprimer
                                    </button>
                                    <button type="submit" class="btn-cancel">
                                        ❌ Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
        }
    }
}

// Menu des tables
$tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/alwah logo.png">
    <title>Gestion des Tables - Export & Suppression</title>
    <style>
/* Enhanced Table Management System CSS - Teal Theme */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Light color palette */
    --container-bg: rgba(255, 255, 255, 0.85);
    --card-bg: rgba(255, 255, 255, 0.9);
    --form-bg: rgba(255, 255, 255, 0.7);
    --select-bg: rgba(226, 250, 248, 0.3);
    --select-focus-bg: rgba(226, 250, 248, 0.6);
    
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

/* Enhanced Export Container */
.export-container {
    width: 100%;
    max-width: 800px;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    padding: 50px;
    border-radius: var(--border-radius-lg);
    text-align: center;
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
.export-container::before {
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
    font-size: clamp(2.2rem, 5vw, 3.2rem);
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

/* Enhanced Card Design */
.card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-light);
    padding: 35px;
    margin-bottom: 30px;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
}

/* Enhanced Alert Messages */
.alert {
    background: linear-gradient(135deg, rgba(178, 245, 234, 0.3), rgba(226, 250, 248, 0.4));
    border: 2px solid rgba(14, 119, 112, 0.3);
    color: var(--primary-dark);
    border-radius: var(--border-radius-md);
    padding: 20px 25px;
    margin: 25px 0;
    font-weight: 600;
    text-align: center;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(14, 119, 112, 0.1);
    position: relative;
    overflow: hidden;
    animation: alertSlideIn 0.6s ease-out;
}

.alert-warning {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 235, 59, 0.2));
    border-color: rgba(255, 193, 7, 0.5);
    color: #d97706;
}

.warning-text {
    font-size: 14px;
    margin-top: 10px;
    font-style: italic;
    opacity: 0.8;
}

@keyframes alertSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Form Styling */
form {
    background: var(--form-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius-md);
    padding: 35px;
    border: 1px solid rgba(14, 119, 112, 0.1);
    position: relative;
    margin-top: 20px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* Label Styling */
label {
    display: block;
    color: var(--primary-dark);
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 16px;
    text-align: left;
    position: relative;
}

/* Main label with icon */
label[for="table"] {
    text-align: center;
    font-size: 18px;
    margin-bottom: 25px;
}

label[for="table"]::before {
    content: '\F2B2 '; /* database icon */
    font-family: 'bootstrap-icons';
    margin-right: 8px;
    color: var(--primary-color);
}

/* Enhanced Select Dropdown */
select {
    width: 100%;
    padding: 18px 25px;
    border-radius: var(--border-radius-md);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 35px;
    border: 2px solid rgba(27, 209, 194, 0.3);
    background: var(--select-bg);
    backdrop-filter: blur(10px);
    color: var(--primary-dark);
    cursor: pointer;
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 20px rgba(14, 119, 112, 0.1);
    appearance: none;
    position: relative;
    
    /* Custom dropdown arrow */
    background-image: linear-gradient(45deg, transparent 50%, var(--primary-color) 50%), 
                      linear-gradient(135deg, var(--primary-color) 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 2px), 
                         calc(100% - 15px) calc(1em + 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    padding-right: 50px;
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
}

select option {
    background: rgba(255, 255, 255, 0.95);
    color: var(--primary-dark);
    padding: 12px 15px;
    font-weight: 500;
}

/* Enhanced Radio Button Group */
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin: 30px 0;
    background: rgba(255, 255, 255, 0.3);
    padding: 25px;
    border-radius: var(--border-radius-md);
    border: 1px solid rgba(14, 119, 112, 0.1);
}

/* Custom Radio Buttons */
input[type="radio"] {
    appearance: none;
    width: 22px;
    height: 22px;
    border: 2px solid var(--primary-color);
    border-radius: 50%;
    margin-right: 12px;
    position: relative;
    cursor: pointer;
    transition: var(--transition-smooth);
    vertical-align: middle;
}

input[type="radio"]:checked {
    background: var(--primary-color);
    border-color: var(--primary-dark);
    box-shadow: 0 0 0 4px rgba(27, 209, 194, 0.2);
}

input[type="radio"]:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

input[type="radio"]:hover {
    transform: scale(1.1);
    box-shadow: 0 0 0 3px rgba(27, 209, 194, 0.15);
}

/* Radio Labels */
label[for="export"], label[for="delete"] {
    display: flex;
    align-items: center;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    padding: 15px;
    border-radius: var(--border-radius-sm);
    transition: var(--transition-smooth);
    background: rgba(255, 255, 255, 0.3);
    border: 1px solid transparent;
}

label[for="export"]:hover, label[for="delete"]:hover {
    background: rgba(178, 245, 234, 0.3);
    border-color: rgba(27, 209, 194, 0.3);
    transform: translateX(5px);
}

/* Export label styling */
label[for="export"] {
    color: #059669;
}

label[for="export"]::before {
    content: '\F2EE '; /* download icon */
    font-family: 'bootstrap-icons';
    margin-right: 8px;
    font-size: 18px;
}

/* Delete label styling */
label[for="delete"] {
    color: #dc2626;
}

label[for="delete"]::before {
    content: '\F5DE '; /* trash icon */
    font-family: 'bootstrap-icons';
    margin-right: 8px;
    font-size: 18px;
}

/* Enhanced Submit Button */
button[type="submit"] {
    background: var(--primary-gradient);
    border: none;
    border-radius: 50px;
    padding: 18px 40px;
    color: white;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 25px;
}

button[type="submit"]::before {
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

button[type="submit"]:hover::before {
    width: 300px;
    height: 300px;
}

button[type="submit"]:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

button[type="submit"]:active {
    transform: translateY(0) scale(0.98);
}

/* Confirmation Form Specific Styling */
.confirmation-form {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid rgba(255, 193, 7, 0.3);
}

.button-group {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
}

.btn-danger {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: var(--border-radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-elastic);
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

.btn-danger:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 30px rgba(229, 62, 62, 0.4);
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
}

.btn-cancel {
    background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: var(--border-radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-elastic);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.btn-cancel:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 30px rgba(107, 114, 128, 0.4);
    background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .export-container {
        padding: 30px 25px;
    }
    
    h2 {
        font-size: 2rem;
        margin-bottom: 25px;
    }
    
    form {
        padding: 25px;
    }
    
    select {
        padding: 15px 20px;
        margin-bottom: 25px;
    }
    
    .radio-group {
        padding: 20px;
        gap: 15px;
    }
    
    .button-group {
        flex-direction: column;
        gap: 15px;
    }
    
    button[type="submit"], .btn-danger, .btn-cancel {
        padding: 15px 25px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .export-container {
        padding: 25px 20px;
    }
    
    h2 {
        font-size: 1.6rem;
    }
    
    form {
        padding: 20px;
    }
    
    select {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .radio-group {
        padding: 15px;
    }
    
    label[for="export"], label[for="delete"] {
        padding: 12px;
        font-size: 14px;
    }
}

/* Enhanced Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    button:hover, select:hover {
        transform: none;
    }
}

/* Focus management for accessibility */
select:focus, input[type="radio"]:focus, button:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .export-container, form {
        background: #ffffff;
        border: 3px solid #000000;
    }
    
    select {
        background: #ffffff;
        border: 2px solid #000000;
        color: #000000;
    }
    
    button[type="submit"] {
        background: #000000 !important;
        color: #ffffff !important;
    }
}

/* Print styles */
@media print {
    body {
        background: white !important;
    }
    
    .export-container, form {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        backdrop-filter: none !important;
    }
    
    button[type="submit"], .btn-danger, .btn-cancel {
        background: #f0f0f0 !important;
        color: #000000 !important;
        border: 1px solid #ccc !important;
    }
}
    </style>
</head>
<body>
    <div class="export-container">
        <h2>📋 Gestion des Tables de la Base</h2>

        <?php if($message): ?>
            <div class="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                 <?= csrf_field() ?>
                <label for="table">Choisir la table à traiter :</label>
                <select name="table" id="table" required>
                    <option value="">-- Sélectionner une table --</option>
                    <?php
                    foreach ($tables as $t) {
                        echo '<option value="'.htmlspecialchars($t[0]).'">'.htmlspecialchars($t[0]).'</option>';
                    }
                    ?>
                </select>

                <div class="radio-group">
                    <label for="export">
                        <input type="radio" name="action" value="export" id="export" required>
                        Exporter la table en CSV
                    </label>

                    <label for="delete">
                        <input type="radio" name="action" value="delete" id="delete">
                        Supprimer définitivement la table
                    </label>
                </div>

                <button type="submit">✨ Valider l'action</button>
            </form>
        </div>
    </div>
</body>
</html>
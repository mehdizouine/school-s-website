<?php
include('db.php'); // connexion DB
$message = '';
// Si le formulaire est soumis
if (isset($_POST['table']) && isset($_FILES['csv_file'])) {
    // Sécuriser le nom de la table
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table']);
    // Vérifier si la table existe
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $message = "Table inexistante !";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Sauvegarde automatique de la table avant import
            $backupTable = $table . '_backup_' . date('Ymd_His');
            $conn->query("CREATE TABLE `$backupTable` AS SELECT * FROM `$table`");
            // Vider la table
            $conn->query("TRUNCATE TABLE `$table`");
            // Lire l'entête et nettoyer le BOM UTF-8
            $headers = fgetcsv($handle, 1000, ';');
            if (substr($headers[0], 0,3) === "\xEF\xBB\xBF") {
                $headers[0] = substr($headers[0], 3);
            }
            // Récupérer les colonnes de la table
            $resCols = $conn->query("SELECT * FROM `$table` LIMIT 1");
            $tableCols = [];
            foreach ($resCols->fetch_fields() as $col) {
                $tableCols[] = $col->name;
            }
            // Correspondance des colonnes (ignore les colonnes supplémentaires dans le CSV)
            $validCols = array_intersect($headers, $tableCols);
            if (empty($validCols)) {
                $message = "Aucune colonne valide trouvée pour l'import.";
            } else {
                $rowCount = 0;
                while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
                    $pairs = [];
                    foreach ($validCols as $i => $colName) {
                        if(isset($data[$i])) {
                            $pairs[$colName] = $conn->real_escape_string($data[$i]);
                        }
                    }
                    if(!empty($pairs)) {
                        // Construire INSERT
                        $columnsStr = [];
                        foreach($pairs as $col => $val){
                            $columnsStr[] = "`$col`='$val'";
                        }
                        $sql = "INSERT INTO `$table` SET ".implode(',', $columnsStr);
                        $conn->query($sql);
                        $rowCount++;
                    }
                }
                $message = "$rowCount lignes importées dans la table '$table'.<br>Sauvegarde automatique : $backupTable";
            }
            fclose($handle);
        } else {
            $message = "Impossible de lire le fichier CSV.";
        }
    }
}
// Récupérer toutes les tables pour le menu
$tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/alwah logo.png">
    <title>Importer CSV dans une table</title>
    <style>
/* === COPIE DU CSS DE export.txt === */
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
* {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Lucida Sans', Geneva, Verdana, sans-serif;
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
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
    0%, 100% { opacity: 0.4; transform: translateY(0px) rotate(0deg); }
    33% { opacity: 0.7; transform: translateY(-10px) rotate(0.5deg); }
    66% { opacity: 0.5; transform: translateY(5px) rotate(-0.5deg); }
}
.import-container {
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
    from { opacity: 0; transform: translateY(50px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.import-container::before {
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
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
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
@keyframes alertSlideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
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
label {
    display: block;
    color: var(--primary-dark);
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 16px;
    text-align: left;
}
label[for="table"], label[for="csv_file"] {
    text-align: center;
    font-size: 18px;
    margin-bottom: 25px;
}
label[for="table"]::before {
    content: '\F2B2 ';
    font-family: 'bootstrap-icons';
    margin-right: 8px;
    color: var(--primary-color);
}
label[for="csv_file"]::before {
    content: '\F1C9 ';
    font-family: 'bootstrap-icons';
    margin-right: 8px;
    color: var(--primary-color);
}
select, input[type="file"] {
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
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 20px rgba(14, 119, 112, 0.1);
}
select:focus, input[type="file"]:focus {
    outline: none;
    border-color: var(--primary-dark);
    background: var(--select-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 8px 30px rgba(14, 119, 112, 0.2);
    transform: translateY(-2px);
}
select:hover, input[type="file"]:hover {
    border-color: var(--primary-color);
    background: rgba(226, 250, 248, 0.4);
    transform: translateY(-1px);
}
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
/* Responsive */
@media (max-width: 768px) {
    body { padding: 15px; }
    .import-container { padding: 30px 25px; }
    h2 { font-size: 2rem; margin-bottom: 25px; }
    form { padding: 25px; }
    select, input[type="file"] { padding: 15px 20px; margin-bottom: 25px; }
}
@media (max-width: 480px) {
    .import-container { padding: 25px 20px; }
    h2 { font-size: 1.6rem; }
    form { padding: 20px; }
    select, input[type="file"] { padding: 12px 16px; font-size: 14px; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
select:focus, input[type="file"]:focus, button:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}
@media (prefers-contrast: high) {
    .import-container, form {
        background: #ffffff;
        border: 3px solid #000000;
    }
    select, input[type="file"] {
        background: #ffffff;
        border: 2px solid #000000;
        color: #000000;
    }
    button[type="submit"] {
        background: #000000 !important;
        color: #ffffff !important;
    }
}
@media print {
    body { background: white !important; }
    .import-container, form {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        backdrop-filter: none !important;
    }
    button[type="submit"] {
        background: #f0f0f0 !important;
        color: #000000 !important;
        border: 1px solid #ccc !important;
    }
}
    </style>
</head>
<body>
    <div class="import-container">
        <h2>📥 Importer un CSV dans une table</h2>
        <?php if($message): ?>
            <div class="alert">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <label for="table">Choisir la table :</label>
                <select name="table" id="table" required>
                    <option value="">-- Sélectionner une table --</option>
                    <?php
                    foreach ($tables as $t) {
                        echo '<option value="'.htmlspecialchars($t[0], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($t[0], ENT_QUOTES, 'UTF-8').'</option>';
                    }
                    ?>
                </select>

                <label for="csv_file">Choisir le fichier CSV :</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>

                <button type="submit">✨ Importer le fichier</button>
            </form>
        </div>
    </div>
</body>
</html>
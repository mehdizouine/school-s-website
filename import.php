<?php
include('db.php');
require_once 'authorisation.php';

// Vérifications de sécurité
require_login();
validate_csrf();
require_role('admin');

function processTableImport($table, $csvLines, $conn) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        return "⚠️ Table '$table' inexistante.";
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'import_');
    file_put_contents($tmpFile, implode("\n", $csvLines));

    // Détecter le séparateur
    $firstLine = $csvLines[0] ?? '';
    $delimiter = ',';
    if (strpos($firstLine, ';') !== false) $delimiter = ';';
    elseif (strpos($firstLine, "\t") !== false) $delimiter = "\t";

    if (($handle = fopen($tmpFile, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle); unlink($tmpFile);
            return "⚠️ Table '$table' : fichier vide.";
        }

        // Nettoyer BOM
        if (substr($headers[0], 0, 3) === "\xEF\xBB\xBF") {
            $headers[0] = substr($headers[0], 3);
        }

        $headers = array_map('trim', $headers);

        $resCols = $conn->query("SELECT * FROM `$table` LIMIT 1");
        $tableCols = [];
        foreach ($resCols->fetch_fields() as $col) $tableCols[] = $col->name;

        $headerIndex = [];
        foreach ($headers as $idx => $h) $headerIndex[$h] = $idx;

        $validCols = array_values(array_intersect($headers, $tableCols));
        if (empty($validCols)) {
            fclose($handle); unlink($tmpFile);
            return "⚠️ Table '$table' : colonnes non correspondantes.";
        }

        // Définir les colonnes uniques pour éviter les duplications
        $uniqueColsMap = [
            'login' => ['ID'],
            'classes' => ['ID'],
            'matiere' => ['ID_matiere'],
            'examen' => ['ID_examen'],
            'semestre' => ['ID_semestre'],
            'profil' => ['ID'],
            'login_attempts' => ['id'],
            'prof_classes' => ['id'],
            'note' => ['ID_note'],
            'devoirs' => ['id'],
            'emploi_du_temps' => ['id'],
            'news' => ['ID'],
            'message_us' => [] // pas de colonne unique
        ];

        $uniqueCols = $uniqueColsMap[strtolower($table)] ?? [];

        $rowCount = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $rowData = [];
            $skipLine = false;

            foreach ($validCols as $colName) {
                $i = $headerIndex[$colName] ?? null;
                $value = ($i !== null && isset($data[$i])) ? trim($data[$i]) : '';

                if ($value === '' || strtolower($value) === 'null') {
                    $colInfoRes = $conn->query("SHOW COLUMNS FROM `$table` LIKE '{$colName}'");
                    $colInfo = $colInfoRes ? $colInfoRes->fetch_assoc() : null;
                    $isNotNull = ($colInfo && isset($colInfo['Null']) && $colInfo['Null'] === 'NO');

                    if ($isNotNull) {
                        if ($colName === 'nom_de_classe') $value = 'INCONNU';
                        elseif (in_array($colName, ['Classe', 'classe_id'])) $value = '1';
                        else $skipLine = true;
                    } else {
                        $value = null;
                    }
                }

                if ($skipLine) break;

                $rowData[$colName] = ($value === null) ? null : $value;
            }
            if ($skipLine || empty($rowData)) continue;

            // Bloc spécifique pour login : générer password_hash si vide
            if (strtolower($table) === 'login') {
                $colPasswordHash = array_key_exists('Password_hash', $rowData) ? 'Password_hash' : null;
                $colPassword = array_key_exists('Password', $rowData) ? 'Password' : null;

                if ($colPasswordHash !== null && (!isset($rowData[$colPasswordHash]) || $rowData[$colPasswordHash] === '')) {
                    $plain = ($colPassword !== null && isset($rowData[$colPassword]) && $rowData[$colPassword] !== '') ? $rowData[$colPassword] : '123456';
                    $rowData[$colPasswordHash] = password_hash($plain, PASSWORD_DEFAULT);
                }

                // Supprimer password en clair
                if ($colPassword !== null) unset($rowData[$colPassword]);
            }

            // Vérifier si la ligne existe déjà
            $exists = false;
            if (!empty($uniqueCols)) {
                $whereParts = [];
                foreach ($uniqueCols as $col) {
                    if (!isset($rowData[$col])) continue 2; // ignore si la colonne unique n'est pas fournie
                    $val = $conn->real_escape_string($rowData[$col]);
                    $whereParts[] = "`$col` = '$val'";
                }
                if (!empty($whereParts)) {
                    $whereSql = implode(' AND ', $whereParts);
                    $res = $conn->query("SELECT 1 FROM `$table` WHERE $whereSql LIMIT 1");
                    if ($res && $res->num_rows > 0) $exists = true;
                }
            }
            if ($exists) continue; // ignorer la ligne si elle existe déjà

            // Insérer
            $cols = array_keys($rowData);
            $vals = array_map(function($v) use($conn){ return ($v===null) ? 'NULL' : "'".$conn->real_escape_string($v)."'"; }, array_values($rowData));
            $sql = "INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".implode(',', $vals).")";
            $conn->query($sql);

            $rowCount++;
        }

        fclose($handle);
        unlink($tmpFile);
        return "✅ '$table' : $rowCount lignes traitées.";
    }

    return "❌ '$table' : erreur lecture.";
}

$message = '';

if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (!is_file($file) || !is_readable($file)) {
        $message = "Fichier CSV invalide.";
    } else {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) $message = "Fichier vide.";
        else {
            $ordreTables = [
                'classes','matiere','examen','semestre','login','profil',
                'login_attempts','prof_classes','note','devoirs','emploi_du_temps',
                'news','message_us'
            ];

            $tablesData = [];
            $currentTable = null;
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (strpos($trimmed, '### TABLE:') === 0) {
                    $currentTable = trim(substr($trimmed, 11));
                    $tablesData[$currentTable] = [];
                } elseif ($currentTable !== null) {
                    $tablesData[$currentTable][] = $line;
                }
            }

            $allMessages = [];
            foreach ($ordreTables as $table) {
                if (isset($tablesData[$table]) && !empty($tablesData[$table])) {
                    $allMessages[] = processTableImport($table, $tablesData[$table], $conn);
                }
            }

            foreach ($tablesData as $table => $data) {
                if (!in_array($table, $ordreTables)) {
                    $allMessages[] = processTableImport($table, $data, $conn);
                }
            }

            $message = implode('<br>', $allMessages);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/img/alwah logo.png">
    <title>Importer CSV multi-table</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="csv_file" style="display:block; font-weight:bold; margin-bottom:15px; text-align:center; font-size:18px;">
            Choisir le fichier CSV multi-table :
        </label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required 
               style="width:100%; padding:12px; margin-bottom:25px; border:1px solid #ccc; border-radius:8px;">
        <button type="submit" 
                style="background:#0E7770; color:white; border:none; padding:12px 30px; 
                       border-radius:20px; font-weight:bold; cursor:pointer;">
            ✨ Importer toutes les tables
        </button>
    </form>
    <div style="margin-top:20px; text-align:center; font-weight:bold; color:green;">
        <?= $message ?>
    </div>
</body>
</html>
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
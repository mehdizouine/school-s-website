<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) die("Accès refusé");
$prof_id = $_SESSION['user_id'];

// Récupérer les classes du prof
$classes_res = $conn->prepare("
    SELECT c.ID, c.nom_de_classe
    FROM classes c
    JOIN prof_classes pc ON c.ID = pc.classe_id
    WHERE pc.prof_id = ?
");
$classes_res->bind_param("i", $prof_id);
$classes_res->execute();
$classes_result = $classes_res->get_result();

// Récupérer toutes les matières
$matieres_res = $conn->query("SELECT ID_matiere, matiere FROM matiere");

$success_message = "";
$error_message = "";

// --- Création du devoir ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $classe_id = $_POST['classe_id'];
    $matiere_id = $_POST['matiere_id'];
    $date_limite = $_POST['date_limite'];

    $fichier = null;
    if (!empty($_FILES['fichier']['name'])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_extension = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
        $fichier = time() . "_" . uniqid() . "." . $file_extension;
        if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_dir . $fichier)) {
            $error_message = "Erreur lors du téléchargement du fichier.";
        }
    }

    if (empty($error_message)) {
        $stmt = $conn->prepare("
            INSERT INTO devoirs (titre, description, classe_id, matiere_id, date_limite, fichier, date_creation)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssiiss", $titre, $description, $classe_id, $matiere_id, $date_limite, $fichier);
        if ($stmt->execute()) $success_message = "Devoir créé avec succès !";
        else $error_message = "Erreur lors de la création du devoir.";
        $stmt->close();
    }
}

// --- Modification du devoir ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $edit_id = intval($_POST['edit_id']);
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $classe_id = $_POST['classe_id'];
    $matiere_id = $_POST['matiere_id'];
    $date_limite = $_POST['date_limite'];

    // Vérifier que le devoir appartient à une classe du prof
    $check = $conn->prepare("SELECT id, fichier FROM devoirs d JOIN prof_classes pc ON d.classe_id=pc.classe_id WHERE d.id=? AND pc.prof_id=?");
    $check->bind_param("ii", $edit_id, $prof_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) { $error_message = "Modification impossible : accès refusé."; }
    else {
        $devoir = $res->fetch_assoc();
        $fichier = $devoir['fichier'];
        if (!empty($_FILES['fichier']['name'])) {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_extension = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
            $fichier = time() . "_" . uniqid() . "." . $file_extension;
            move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_dir . $fichier);
        }
        $stmt = $conn->prepare("UPDATE devoirs SET titre=?, description=?, classe_id=?, matiere_id=?, date_limite=?, fichier=? WHERE id=?");
        $stmt->bind_param("ssiissi", $titre, $description, $classe_id, $matiere_id, $date_limite, $fichier, $edit_id);
        if ($stmt->execute()) $success_message = "Devoir modifié avec succès !";
        else $error_message = "Erreur lors de la modification.";
        $stmt->close();
    }
    $check->close();
}

// --- Suppression d'un devoir ---
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $check = $conn->prepare("SELECT d.id FROM devoirs d JOIN prof_classes pc ON d.classe_id = pc.classe_id WHERE d.id=? AND pc.prof_id=?");
    $check->bind_param("ii", $delete_id, $prof_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows>0) {
        $conn->query("DELETE FROM devoirs WHERE id=$delete_id");
        $success_message = "Devoir supprimé avec succès !";
    } else { $error_message = "Suppression impossible : accès refusé."; }
    $check->close();
}

// --- Historique des devoirs ---
$homework_history_res = $conn->prepare("
    SELECT d.id, d.titre, d.description, d.date_limite, d.fichier, d.date_creation,
           c.nom_de_classe, m.matiere
    FROM devoirs d
    JOIN classes c ON d.classe_id=c.ID
    JOIN matiere m ON d.matiere_id=m.ID_matiere
    JOIN prof_classes pc ON d.classe_id = pc.classe_id
    WHERE pc.prof_id=?
    ORDER BY d.date_creation DESC
");
$homework_history_res->bind_param("i", $prof_id);
$homework_history_res->execute();
$homework_history_result = $homework_history_res->get_result();

// --- Edition inline ---
$edit_id = isset($_GET['edit'])?intval($_GET['edit']):0;
$edit_devoir = null;
if ($edit_id>0) {
    $stmt = $conn->prepare("SELECT * FROM devoirs d JOIN prof_classes pc ON d.classe_id=pc.classe_id WHERE d.id=? AND pc.prof_id=?");
    $stmt->bind_param("ii",$edit_id,$prof_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0) $edit_devoir = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Devoirs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">

<!-- Messages -->
<?php if($success_message): ?>
<div class="alert alert-success"><?= $success_message ?></div>
<?php endif; ?>
<?php if($error_message): ?>
<div class="alert alert-danger"><?= $error_message ?></div>
<?php endif; ?>

<!-- Formulaire Création / Edition -->
<div class="card mb-5">
    <h2 class="card-header"><i class="fas fa-plus-circle"></i> <?= $edit_devoir?'Modifier':'Créer' ?> un Devoir</h2>
    <div class="card-body">
    <form method="post" enctype="multipart/form-data">
        <?php if($edit_devoir): ?>
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="edit_id" value="<?= $edit_devoir['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="create" value="1">
        <?php endif; ?>

        <div class="mb-3"><label>Titre</label>
            <input type="text" name="titre" class="form-control" value="<?= $edit_devoir?htmlspecialchars($edit_devoir['titre']):'' ?>" required>
        </div>
        <div class="mb-3"><label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?= $edit_devoir?htmlspecialchars($edit_devoir['description']):'' ?></textarea>
        </div>
        <div class="mb-3"><label>Classe</label>
            <select name="classe_id" class="form-select" required>
                <option value="">-- Sélectionnez --</option>
                <?php $classes_result->data_seek(0); while($c=$classes_result->fetch_assoc()): ?>
                    <option value="<?= $c['ID'] ?>" <?= $edit_devoir&&$c['ID']==$edit_devoir['classe_id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['nom_de_classe']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3"><label>Matière</label>
            <select name="matiere_id" class="form-select" required>
                <option value="">-- Sélectionnez --</option>
                <?php $matieres_res->data_seek(0); while($m=$matieres_res->fetch_assoc()): ?>
                    <option value="<?= $m['ID_matiere'] ?>" <?= $edit_devoir&&$m['ID_matiere']==$edit_devoir['matiere_id']?'selected':'' ?>>
                        <?= htmlspecialchars($m['matiere']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3"><label>Date limite</label>
            <input type="date" name="date_limite" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= $edit_devoir?$edit_devoir['date_limite']:'' ?>" required>
        </div>
        <div class="mb-3"><label>Fichier</label>
            <?php if($edit_devoir && $edit_devoir['fichier']): ?>
                <div><a href="uploads/<?= $edit_devoir['fichier'] ?>" target="_blank"><?= $edit_devoir['fichier'] ?></a></div>
            <?php endif; ?>
            <input type="file" name="fichier" class="form-control mt-2">
        </div>
        <button type="submit" class="btn btn-<?= $edit_devoir?'warning':'primary' ?>">
            <i class="bi <?= $edit_devoir?'bi-pencil':'bi-plus-circle' ?>"></i> <?= $edit_devoir?'Modifier':'Créer' ?>
        </button>
        <?php if($edit_devoir): ?><a href="?" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Annuler</a><?php endif; ?>
    </form>
    </div>
</div>

<!-- Historique -->
<div class="card">
<h2 class="card-header"><i class="fas fa-history"></i> Historique</h2>
<div class="card-body">
<?php if($homework_history_result->num_rows==0): ?>
<p>Aucun devoir pour le moment.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered text-center">
<thead class="table-light">
<tr>
<th>Classe</th><th>Matière</th><th>Titre</th><th>Description</th><th>Date limite</th><th>Date création</th><th>Fichier</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($d=$homework_history_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['nom_de_classe']) ?></td>
<td><?= htmlspecialchars($d['matiere']) ?></td>
<td><?= htmlspecialchars($d['titre']) ?></td>
<td><?= nl2br(htmlspecialchars($d['description'])) ?></td>
<td><?= $d['date_limite'] ?></td>
<td><?= $d['date_creation'] ?></td>
<td><?= $d['fichier']?'<a href="uploads/'.$d['fichier'].'" target="_blank"><i class="bi bi-download"></i></a>':'—' ?></td>
<td>
<a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
<a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce devoir ?')"><i class="bi bi-trash"></i></a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
        /* Sophisticated School Management System CSS - Teal Theme */
        :root {
            --primary-color: rgba(14, 119, 112, 0.8);
            --primary-dark: rgba(14, 119, 112, 1);
            --primary-light: rgba(14, 119, 112, 0.3);
            --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
            --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
            --success-gradient: linear-gradient(135deg, rgba(129, 199, 132, 0.9) 0%, rgba(200, 230, 201, 0.8) 100%);
            --warning-gradient: linear-gradient(135deg, #ffe082 0%, #ffcc80 100%);
            --danger-gradient: linear-gradient(135deg, #ef9a9a 0%, #ffcdd2 100%);
            --info-gradient: linear-gradient(135deg, rgba(33, 150, 243, 0.9) 0%, rgba(144, 202, 249, 0.8) 100%);
            
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
            background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
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

        /* Form container optimized width */
        .form-card {
            max-width: 800px;
            margin: 0 auto 40px auto;
        }

        /* Premium Card Design with Glassmorphism */
        .card {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            margin-bottom: 40px;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
            border: none;
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

        /* Enhanced Card Headers */
        .card-header {
            background: var(--primary-gradient) !important;
            color: white !important;
            border: none !important;
            padding: 25px 30px !important;
            font-weight: 700;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0 !important;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition-smooth);
        }

        .card:hover .card-header::before {
            left: 100%;
        }

        .card-header i {
            margin-right: 10px;
            font-size: 1.3rem;
        }

        /* Enhanced Card Body */
        .card-body {
            padding: 40px 30px;
        }

        /* Enhanced Alert Styling */
        .alert {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-light);
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            font-weight: 500;
            text-align: center;
            animation: slideDown 0.5s ease-out;
        }

        .alert-success {
            color: #2e7d32;
            border-left: 4px solid #4caf50;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(200, 230, 201, 0.1));
        }

        .alert-danger {
            color: #c62828;
            border-left: 4px solid #f44336;
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(255, 205, 210, 0.1));
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced Form Group with better spacing */
        .form-group, .mb-3 {
            margin-bottom: 25px;
            position: relative;
        }

        .form-row {
            display: flex;
            gap: 20px;
            align-items: stretch;
        }

        .form-col {
            flex: 1;
        }

        .form-col-2 {
            flex: 2;
        }

        /* Premium Label Styling */
        label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            display: block;
        }

        /* Enhanced Input Styling */
        .form-control, .form-select {
            padding: 16px 20px !important;
            border: 2px solid rgba(27, 209, 194, 0.3) !important;
            border-radius: var(--border-radius-md) !important;
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(15px);
            color: var(--primary-dark) !important;
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition-smooth);
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.06), 0 4px 15px rgba(27, 209, 194, 0.08) !important;
            position: relative;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            outline: none !important;
            border-color: var(--primary-dark) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            box-shadow: 
                0 0 0 3px rgba(27, 209, 194, 0.2),
                inset 0 2px 6px rgba(0, 0, 0, 0.06),
                0 6px 20px rgba(27, 209, 194, 0.15) !important;
            transform: translateY(-2px);
            color: var(--primary-dark) !important;
        }

        .form-control::placeholder {
            color: rgba(14, 119, 112, 0.5) !important;
            font-style: italic;
        }

        /* Enhanced Textarea */
        .form-control[rows] {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        /* Enhanced Select Options */
        .form-select option {
            background: rgba(14, 119, 112, 0.9);
            color: white;
            padding: 12px;
            font-weight: 500;
        }

        /* Enhanced File Input */
        .form-control[type="file"] {
            padding: 15px !important;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        /* File link styling */
        .file-link {
            background: var(--info-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition-smooth);
            display: inline-block;
            margin-bottom: 10px;
        }

        .file-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
            color: white;
            text-decoration: none;
        }

        /* Premium Button Design */
        .btn {
            border-radius: var(--border-radius-md) !important;
            padding: 15px 25px !important;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition-elastic);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none !important;
        }

        .btn-primary {
            background: var(--primary-gradient) !important;
            color: white !important;
            box-shadow: var(--shadow-light);
        }

        .btn-warning {
            background: var(--warning-gradient) !important;
            color: #8a6d03 !important;
            box-shadow: 0 6px 18px rgba(255, 193, 7, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.9), rgba(173, 181, 189, 0.8)) !important;
            color: white !important;
            box-shadow: 0 6px 18px rgba(108, 117, 125, 0.3);
        }

        .btn-danger {
            background: var(--danger-gradient) !important;
            color: #c62828 !important;
            box-shadow: 0 6px 18px rgba(244, 67, 54, 0.3);
        }

        .btn::before {
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

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-3px) scale(1.05) !important;
        }

        .btn:active {
            transform: translateY(0) scale(0.98) !important;
        }

        /* Small buttons */
        .btn-sm {
            padding: 8px 16px !important;
            font-size: 14px !important;
        }

        /* Enhanced Table Container */
        .table-container {
            background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(255, 255, 255, 0.6));
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius-md);
            padding: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
            margin-top: 20px;
        }

        /* Enhanced Table Styling */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            color: #2d3748;
            transition: all 0.4s ease;
            background: transparent;
            margin-bottom: 0;
        }

        /* Header styling with icons */
        .table thead th {
            background: var(--primary-gradient);
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px 15px;
            font-size: 13px;
            border: none;
            position: relative;
            box-shadow: inset 0 -3px 5px rgba(0,0,0,0.1);
            border-right: 1px solid rgba(255,255,255,0.2);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            vertical-align: middle;
            text-align: center;
        }

        .table thead th:first-child {
            border-radius: var(--border-radius-sm) 0 0 0;
        }

        .table thead th:last-child {
            border-radius: 0 var(--border-radius-sm) 0 0;
            border-right: none;
        }

        /* Enhanced Body Rows */
        .table tbody tr {
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(14, 119, 112, 0.08);
            position: relative;
        }

        .table tbody tr:nth-child(even) {
            background: rgba(226, 250, 248, 0.3);
        }

        .table tbody tr:hover {
            background: rgba(178, 245, 234, 0.4) !important;
            transform: translateX(5px) scale(1.01);
            box-shadow: 
                5px 0 20px rgba(27, 209, 194, 0.15),
                0 4px 15px rgba(14, 119, 112, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        /* Cellules styling */
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            font-weight: 500;
            border: none;
            color: #2d3748;
            position: relative;
            transition: all 0.3s ease;
            text-align: center;
        }

        /* Enhanced Class and Subject Cells */
        .table tbody td:nth-child(1) {
            background: rgba(14, 119, 112, 0.08);
            color: var(--primary-dark);
            font-weight: 700;
            border-radius: var(--border-radius-sm);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .table tbody td:nth-child(2) {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(144, 202, 249, 0.1));
            color: #1976d2;
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        /* Title cell styling */
        .table tbody td:nth-child(3) {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1rem;
            text-align: left;
        }

        /* Description cell styling */
        .table tbody td:nth-child(4) {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
            font-size: 0.9rem;
        }

        /* Date cells styling */
        .table tbody td:nth-child(5), 
        .table tbody td:nth-child(6) {
            font-weight: 600;
            color: #e65100;
            background: rgba(255, 152, 0, 0.1);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
        }

        /* File cell styling */
        .table tbody td:nth-child(7) {
            font-size: 1.2rem;
        }

        /* Empty cell styling */
        .empty-cell {
            color: rgba(14, 119, 112, 0.3);
            font-size: 1.2rem;
            font-weight: 300;
        }

        /* Action buttons in table */
        .table tbody td:last-child .btn {
            margin: 0 3px;
            padding: 6px 12px !important;
            font-size: 12px !important;
        }

        /* Animation for page load */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        /* Row animation */
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

        .table tbody tr {
            animation: rowSlideIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        .table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .table tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .table tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .table tbody tr:nth-child(5) { animation-delay: 0.5s; }
        .table tbody tr:nth-child(6) { animation-delay: 0.6s; }
        .table tbody tr:nth-child(7) { animation-delay: 0.7s; }
        .table tbody tr:nth-child(8) { animation-delay: 0.8s; }

        /* No homework message */
        .no-homework {
            text-align: center;
            padding: 50px;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Responsive adjustments for form */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .form-card {
                max-width: 100%;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table tbody td, .table thead th {
                padding: 12px 8px;
            }
            
            .form-control, .form-select {
                padding: 15px !important;
                font-size: 14px;
            }
            
            .btn {
                padding: 12px 20px !important;
                font-size: 14px;
            }
            
            .table tbody tr:hover {
                transform: none;
                border-left: none;
            }
            
            .table tbody td:nth-child(4) {
                max-width: 150px;
            }
        }

        @media (max-width: 480px) {
            .card-body {
                padding: 20px 15px;
            }
            
            .table {
                font-size: 10px;
            }
            
            .form-control, .form-select {
                padding: 12px !important;
                font-size: 14px;
            }
            
            .btn {
                padding: 10px 15px !important;
                font-size: 12px;
            }
            
            .table tbody td:nth-child(4) {
                max-width: 100px;
            }
        }

        /* Custom scrollbar */
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

        /* Accessibility Enhancements */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus Management */
        .form-control:focus, .form-select:focus, .btn:focus {
            outline: 2px solid var(--primary-color) !important;
            outline-offset: 2px;
        }
    </style>
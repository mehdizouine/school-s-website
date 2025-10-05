<?php
session_start();
include('db.php');
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');
// Ajouter une classe
if (isset($_POST['add_class'])) {
    $nom = trim($_POST['nom_de_classe']);
    if (!empty($nom)) {
        $stmt = $conn->prepare("INSERT INTO classes (nom_de_classe) VALUES (?)");
        $stmt->bind_param("s", $nom);
        $stmt->execute();
        $stmt->close();
        header("Location: gestion_classes.php");
        exit;
    }
}

// Modifier une classe
if (isset($_POST['edit_class'])) {
    $id = $_POST['id'];
    $nom = trim($_POST['nom_de_classe']);
    if (!empty($nom)) {
        $stmt = $conn->prepare("UPDATE classes SET nom_de_classe=? WHERE ID=?");
        $stmt->bind_param("si", $nom, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: gestion_classes.php");
        exit;
    }
}

// Supprimer une classe
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM classes WHERE ID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gestion_classes.php");
    exit;
}

// Récupérer toutes les classes
$classes = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* === Copie fidèle du style edt.txt === */
        :root {
            --primary-color: rgba(14, 119, 112, 0.8);
            --primary-dark: rgba(14, 119, 112, 1);
            --primary-light: rgba(14, 119, 112, 0.3);
            --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
            --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
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
            --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
            background-attachment: fixed;
            color: #2d3748;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

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

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-heavy);
            border-color: rgba(27, 209, 194, 0.3);
        }

        h2, h3 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            font-size: clamp(1.5rem, 4vw, 2.2rem);
        }

        .form-control {
            padding: 16px !important;
            border: 2px solid rgba(27, 209, 194, 0.3) !important;
            border-radius: var(--border-radius-md) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(15px);
            color: rgba(2, 145, 133, 0.8) !important;
            font-weight: 500;
            transition: var(--transition-smooth);
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.08), 0 4px 20px rgba(27,209,194,0.1) !important;
        }

        .form-control:focus {
            outline: none !important;
            border-color: var(--primary-dark) !important;
            background: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 
                0 0 0 4px rgba(27, 209, 194, 0.25),
                inset 0 2px 8px rgba(0,0,0,0.08),
                0 8px 30px rgba(27, 209, 194, 0.2) !important;
            color: rgba(2, 145, 133, 1) !important;
        }

        .btn-success, .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: var(--border-radius-md) !important;
            padding: 14px 24px !important;
            color: white !important;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition-elastic);
            box-shadow: var(--shadow-light);
            position: relative;
            overflow: hidden;
        }

        .btn-success:hover, .btn-primary:hover {
            transform: translateY(-3px) scale(1.05) !important;
            background: var(--secondary-gradient) !important;
            box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4) !important;
        }

        .btn-warning, .btn-danger {
            border-radius: var(--border-radius-sm) !important;
            padding: 6px 12px !important;
            margin: 0 4px;
            font-size: 0.9rem;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border: none;
        }

        .table tbody tr {
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(14,119,112,0.08);
        }

        .table tbody tr:nth-child(even) {
            background: rgba(226, 250, 248, 0.3);
        }

        .table tbody tr:hover {
            background: rgba(178, 245, 234, 0.4) !important;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(14,119,112,0.1);
        }

        .class-name {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .card { padding: 25px 20px; }
            .form-control { margin-bottom: 1rem; }
            h2 { font-size: 1.6rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
            .action-cell { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Formulaire d'ajout -->
        <div class="card fade-in">
            <h2><i class="bi bi-building me-2"></i>Ajouter une Classe</h2>
            <form method="POST" class="row g-3">
                 <?= csrf_field() ?>
                <div class="col-md-8">
                    <input type="text" name="nom_de_classe" class="form-control" placeholder="Nom de la classe" required>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" name="add_class" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des classes -->
        <div class="card fade-in">
            <h2><i class="bi bi-list-ul me-2"></i>Liste des Classes</h2>
            <div class="table-responsive">
                <table class="table table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom de la Classe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $classes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['ID'] ?></td>
                                <td class="class-name"><?= htmlspecialchars($row['nom_de_classe']) ?></td>
                                <td class="action-cell">
                                    <!-- Formulaire de modification inline -->
                                    <form method="POST" style="display:inline;">
                                         <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $row['ID'] ?>">
                                        <input type="text" name="nom_de_classe" 
                                               value="<?= htmlspecialchars($row['nom_de_classe']) ?>" 
                                               class="form-control form-control-sm d-inline-block w-auto"
                                               style="width: 140px; display: inline-block;" required>
                                        <button type="submit" name="edit_class" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </form>
                                    <a href="?delete=<?= $row['ID'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Voulez-vous vraiment supprimer cette classe ?')">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
include('db.php');

// Ajouter / Modifier un créneau
if (isset($_POST['save_creneau'])) {
    $id = $_POST['id'] ?? '';
    $classe_id = $_POST['classe_id'];
    $matiere_id = $_POST['matiere_id'];
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];

    // Vérifier que les IDs existent
    $classe_check = $conn->prepare("SELECT COUNT(*) FROM classes WHERE ID=?");
    $classe_check->bind_param("i", $classe_id);
    $classe_check->execute();
    $classe_check->bind_result($classe_exists);
    $classe_check->fetch();
    $classe_check->close();

    $matiere_check = $conn->prepare("SELECT COUNT(*) FROM matiere WHERE ID_matiere=?");
    $matiere_check->bind_param("i", $matiere_id);
    $matiere_check->execute();
    $matiere_check->bind_result($matiere_exists);
    $matiere_check->fetch();
    $matiere_check->close();

    if (!$classe_exists || !$matiere_exists) {
        $error = "Erreur : Classe ou matière invalide.";
    } elseif ($heure_debut >= $heure_fin) {
        $error = "Erreur : l’heure de début doit être avant l’heure de fin.";
    } else {
        if ($id) { // Modifier
            $stmt = $conn->prepare("UPDATE emploi_du_temps SET classe_id=?, matiere_id=?, jour=?, heure_debut=?, heure_fin=? WHERE id=?");
            $stmt->bind_param("iisssi", $classe_id, $matiere_id, $jour, $heure_debut, $heure_fin, $id);
        } else { // Ajouter
            $stmt = $conn->prepare("INSERT INTO emploi_du_temps (classe_id, matiere_id, jour, heure_debut, heure_fin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $classe_id, $matiere_id, $jour, $heure_debut, $heure_fin);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: modif_edt.php");
        exit;
    }
}

// Supprimer un créneau
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM emploi_du_temps WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: modif_edt.php");
    exit;
}

// Récupérer toutes les classes et matières
$classes = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
$matieres = $conn->query("SELECT * FROM matiere ORDER BY matiere ASC");

// Récupérer tous les créneaux
$edt_result = $conn->query("
    SELECT e.*, c.nom_de_classe, m.matiere 
    FROM emploi_du_temps e
    JOIN classes c ON e.classe_id=c.ID
    JOIN matiere m ON e.matiere_id=m.ID_matiere
    ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), e.heure_debut
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Emploi du Temps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* === Identique au style de edt.txt === */
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
            max-width: 1400px;
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

        h3, h4 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            text-align: center;
            margin-bottom: 35px;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
        }

        .form-control, .form-select {
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

        .form-control:focus, .form-select:focus {
            outline: none !important;
            border-color: var(--primary-dark) !important;
            background: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 
                0 0 0 4px rgba(27, 209, 194, 0.25),
                inset 0 2px 8px rgba(0,0,0,0.08),
                0 8px 30px rgba(27, 209, 194, 0.2) !important;
            color: rgba(2, 145, 133, 1) !important;
        }

        .btn-success {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: var(--border-radius-md) !important;
            padding: 16px 24px !important;
            color: white !important;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition-elastic);
            box-shadow: var(--shadow-light);
            position: relative;
            overflow: hidden;
        }

        .btn-success:hover {
            transform: translateY(-3px) scale(1.05) !important;
            background: var(--secondary-gradient) !important;
            box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4) !important;
        }

        .btn-warning, .btn-danger {
            border-radius: var(--border-radius-sm) !important;
            padding: 6px 10px !important;
            margin: 0 4px;
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

        .course {
            background: linear-gradient(135deg, rgba(14,119,112,0.9), rgba(27,209,194,0.8));
            color: white;
            border-radius: var(--border-radius-sm);
            padding: 6px 10px;
            font-weight: 600;
            text-align: center;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .alert {
            border-radius: var(--border-radius-md);
            backdrop-filter: blur(10px);
            background: rgba(255, 235, 235, 0.85);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        @media (max-width: 768px) {
            .card { padding: 25px 20px; }
            .form-control, .form-select { margin-bottom: 1rem; }
            h3 { font-size: 1.6rem; }
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
        <div class="card fade-in">
            <h3><i class="bi bi-calendar-week me-3"></i> Gestion Emploi du Temps</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulaire d'ajout/modification -->
            <form method="POST" class="row g-3 mb-4">
                <input type="hidden" name="id" id="id">
                <div class="col-md-3">
                    <select name="classe_id" id="classe_id" class="form-select" required>
                        <option value="">-- Choisir classe --</option>
                        <?php 
                        $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['nom_de_classe']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="matiere_id" id="matiere_id" class="form-select" required>
                        <option value="">-- Choisir matière --</option>
                        <?php 
                        $matieres->data_seek(0);
                        while ($m = $matieres->fetch_assoc()): ?>
                            <option value="<?= $m['ID_matiere'] ?>"><?= htmlspecialchars($m['matiere']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jour" id="jour" class="form-select" required>
                        <option value="">-- Jour --</option>
                        <option value="Lundi">Lundi</option>
                        <option value="Mardi">Mardi</option>
                        <option value="Mercredi">Mercredi</option>
                        <option value="Jeudi">Jeudi</option>
                        <option value="Vendredi">Vendredi</option>
                        <option value="Samedi">Samedi</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="time" name="heure_debut" id="heure_debut" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <input type="time" name="heure_fin" id="heure_fin" class="form-control" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="save_creneau" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>

            <!-- Liste des créneaux -->
            <div class="card" style="padding: 25px;">
                <h4 class="mb-3"><i class="bi bi-list-check me-2"></i>Liste des Créneaux</h4>
                <div class="table-responsive">
                    <table class="table table-hover text-center align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Jour</th>
                                <th>Heure Début</th>
                                <th>Heure Fin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $edt_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['nom_de_classe']) ?></td>
                                    <td><span class="course"><?= htmlspecialchars($row['matiere']) ?></span></td>
                                    <td><?= $row['jour'] ?></td>
                                    <td><?= $row['heure_debut'] ?></td>
                                    <td><?= $row['heure_fin'] ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="editCreneau(<?= $row['id'] ?>,<?= $row['classe_id'] ?>,<?= $row['matiere_id'] ?>,'<?= addslashes($row['jour']) ?>','<?= $row['heure_debut'] ?>','<?= $row['heure_fin'] ?>')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="modif_edt.php?delete=<?= $row['id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Supprimer ce créneau ?')">
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
    </div>

    <script>
        function editCreneau(id, classe_id, matiere_id, jour, heure_debut, heure_fin) {
            document.getElementById('id').value = id;
            document.getElementById('classe_id').value = classe_id;
            document.getElementById('matiere_id').value = matiere_id;
            document.getElementById('jour').value = jour;
            document.getElementById('heure_debut').value = heure_debut;
            document.getElementById('heure_fin').value = heure_fin;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
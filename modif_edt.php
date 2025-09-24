<?php
session_start();
include('db.php');

// Ajouter / Modifier un créneau
if(isset($_POST['save_creneau'])){
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

    if(!$classe_exists || !$matiere_exists){
        $error = "Erreur : Classe ou matière invalide.";
    } elseif($heure_debut >= $heure_fin){
        $error = "Erreur : heure début doit être avant heure fin.";
    } else {
        if($id){ // Modifier
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
if(isset($_GET['delete'])){
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
<title>Gestion Emploi du Temps</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .course { background-color:#e3f2fd; border-radius:6px; padding:3px; font-weight:bold; color:#0d47a1; }
</style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h3 class="mb-4 text-center text-primary"><i class="bi bi-calendar-week"></i> Gestion Emploi du Temps</h3>

    <!-- Formulaire -->
    <?php if(!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST" class="row g-3 mb-4">
        <input type="hidden" name="id" id="id">

        <div class="col-md-3">
            <select name="classe_id" id="classe_id" class="form-select" required>
                <option value="">-- Choisir classe --</option>
                <?php 
                $classes->data_seek(0); // Repositionne le curseur
                while($c=$classes->fetch_assoc()): ?>
                    <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['nom_de_classe']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <select name="matiere_id" id="matiere_id" class="form-select" required>
                <option value="">-- Choisir matière --</option>
                <?php 
                $matieres->data_seek(0);
                while($m=$matieres->fetch_assoc()): ?>
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

        <div class="col-md-12 text-end">
            <button type="submit" name="save_creneau" class="btn btn-success"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </form>

    <!-- Tableau des créneaux -->
    <div class="card shadow-lg p-4" style="border-radius:20px;">
        <h4 class="mb-3"><i class="bi bi-list-check"></i> Liste des Créneaux</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-primary">
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
                <?php while($row=$edt_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nom_de_classe']) ?></td>
                        <td><?= htmlspecialchars($row['matiere']) ?></td>
                        <td><?= $row['jour'] ?></td>
                        <td><?= $row['heure_debut'] ?></td>
                        <td><?= $row['heure_fin'] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" 
                                    onclick="editCreneau(<?= $row['id'] ?>,<?= $row['classe_id'] ?>,<?= $row['matiere_id'] ?>,'<?= $row['jour'] ?>','<?= $row['heure_debut'] ?>','<?= $row['heure_fin'] ?>')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="modif_edt.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce créneau ?')">
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
function editCreneau(id, classe_id, matiere_id, jour, heure_debut, heure_fin){
    document.getElementById('id').value = id;
    document.getElementById('classe_id').value = classe_id;
    document.getElementById('matiere_id').value = matiere_id;
    document.getElementById('jour').value = jour;
    document.getElementById('heure_debut').value = heure_debut;
    document.getElementById('heure_fin').value = heure_fin;
}
</script>
</body>
</html>

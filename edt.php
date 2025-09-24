<?php
session_start();
include('db.php');

// Récupérer toutes les classes
$classes_result = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
$classes = [];
while($c = $classes_result->fetch_assoc()){
    $classes[$c['ID']] = $c['nom_de_classe'];
}

// Récupérer la classe sélectionnée
$classe_ID = $_GET['classe_ID'] ?? 0;
$classe_ID = (int)$classe_ID;

// Récupérer les créneaux si une classe est sélectionnée
$edt_par_classe = [];
if($classe_ID > 0){
    $stmt = $conn->prepare("
        SELECT e.*, m.matiere 
        FROM emploi_du_temps e
        JOIN matiere m ON e.matiere_id = m.ID_matiere
        WHERE e.classe_ID=?
        ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), e.heure_debut
    ");
    $stmt->bind_param("i", $classe_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $edt_par_classe[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Emploi du Temps</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.course { background-color:#e3f2fd; border-radius:6px; padding:3px; font-weight:bold; color:#0d47a1; margin-bottom:3px;}
</style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h3 class="mb-4 text-center text-primary">Emploi du Temps</h3>

    <!-- Formulaire de sélection -->
    <form method="GET" class="mb-4 text-center">
        <select name="classe_ID" class="form-select w-25 d-inline-block">
            <option value="0">-- Choisir une classe --</option>
            <?php foreach($classes as $ID => $nom): ?>
                <option value="<?= $ID ?>" <?= ($ID == $classe_ID) ? 'selected' : '' ?>><?= htmlspecialchars($nom) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Afficher</button>
    </form>

    <?php if($classe_ID > 0): ?>
        <h4 class="text-center"><?= htmlspecialchars($classes[$classe_ID]) ?></h4>
        <div class="table-responsive mb-5">
            <table class="table table-bordered text-center">
                <thead class="table-primary">
                    <tr>
                        <th>Jour</th>
                        <th>08:30-10:30</th>
                        <th>10:30-12:30</th>
                        <th>13:30-15:30</th>
                        <th>15:30-17:30</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
                    $slots = ['08:30-10:30','10:30-12:30','13:30-15:30','15:30-17:30'];
                    
                    foreach($jours as $jour){
                        echo "<tr><td>$jour</td>";
                        foreach($slots as $slot){
                            $found = false;
                            foreach($edt_par_classe as $c){
                                // On ignore les secondes dans la comparaison
                                $h = substr($c['heure_debut'],0,5) . '-' . substr($c['heure_fin'],0,5);
                                if($c['jour'] == $jour && $h == $slot){
                                    echo "<td class='course'>".htmlspecialchars($c['matiere'])."</td>";
                                    $found = true;
                                    break;
                                }
                            }
                            if(!$found) echo "<td></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

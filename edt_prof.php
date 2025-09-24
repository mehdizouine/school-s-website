<?php
session_start();
include('db.php');

$prof_id = $_SESSION['user_id'];

// Récupérer toutes les classes associées au prof
$prof_classes_res = $conn->prepare("SELECT classe_id FROM prof_classe WHERE prof_id = ?");
$prof_classes_res->bind_param("i", $prof_id);
$prof_classes_res->execute();
$prof_classes_result = $prof_classes_res->get_result();

$classes_ids = [];
while($c = $prof_classes_result->fetch_assoc()){
    $classes_ids[] = $c['classe_id'];
}
$prof_classes_res->close();

$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$heures = ['08:30-10:30','10:30-12:30','13:30-15:30','15:30-17:30'];

$edt = [];

if(count($classes_ids) > 0){
    $ids = implode(',', $classes_ids);

    $edt_result = $conn->query("
        SELECT e.*, c.nom_de_classe, m.matiere
        FROM emploi_du_temps e
        JOIN classes c ON e.classe_id = c.ID
        JOIN matiere m ON e.matiere_id = m.ID_matiere
        WHERE e.classe_id IN ($ids)
        ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), e.heure_debut
    ");

    while($row = $edt_result->fetch_assoc()){
        // Trouver la plage horaire correspondante
        $plage = '';
        foreach($heures as $h){
            list($debut,$fin) = explode('-',$h);
            if($row['heure_debut'] <= $fin && $row['heure_fin'] >= $debut){
                $plage = $h;
                break;
            }
        }
        if($plage){
            $edt[$row['jour']][$plage][] = $row['matiere'] . " (" . $row['nom_de_classe'] . ")";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Emploi du Temps Prof</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: Arial, sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; vertical-align: middle; }
    th { background: linear-gradient(90deg,#3ac1bf,#329d9c); color: white; }
    .course-card { background: linear-gradient(90deg,#3ac1bf,#329d9c); color: #fff; border-radius: 6px; padding: 5px; margin: 2px 0; display: block; }
</style>
</head>
<body>
<div class="container py-5">
<h2 class="mb-4">Mon Emploi du Temps</h2>

<?php if(count($classes_ids) === 0): ?>
    <div class="alert alert-warning">Vous n'êtes associé à aucune classe pour le moment.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Jour / Heure</th>
                <?php foreach($heures as $h): ?>
                    <th><?= $h ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($jours as $jour): ?>
                <tr>
                    <td><?= $jour ?></td>
                    <?php foreach($heures as $h): ?>
                        <td>
                            <?php
                            if(!empty($edt[$jour][$h])){
                                foreach($edt[$jour][$h] as $c){
                                    echo "<span class='course-card'>$c</span>";
                                }
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
include('db.php'); // Connexion à la base

// Ajouter une classe
if(isset($_POST['add_class'])){
    $nom = $_POST['nom_de_classe'];
    if(!empty($nom)){
        $stmt = $conn->prepare("INSERT INTO classes (nom_de_classe) VALUES (?)");
        $stmt->bind_param("s", $nom);
        $stmt->execute();
        $stmt->close();
    }
}

// Modifier une classe
if(isset($_POST['edit_class'])){
    $id = $_POST['id'];
    $nom = $_POST['nom_de_classe'];
    if(!empty($nom)){
        $stmt = $conn->prepare("UPDATE classes SET nom_de_classe=? WHERE ID=?");
        $stmt->bind_param("si", $nom, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Supprimer une classe
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM classes WHERE ID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Récupérer toutes les classes
$classes = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des classes</title>
</head>
<body>
    <h2>Ajouter une classe</h2>
    <form method="POST">
        <input type="text" name="nom_de_classe" placeholder="Nom de la classe" required>
        <button type="submit" name="add_class">Ajouter</button>
    </form>

    <h2>Liste des classes</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nom de la classe</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $classes->fetch_assoc()): ?>
        <tr>
            <td><?= $row['ID'] ?></td>
            <td><?= $row['nom_de_classe'] ?></td>
            <td>
                <!-- Modifier -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $row['ID'] ?>">
                    <input type="text" name="nom_de_classe" value="<?= $row['nom_de_classe'] ?>" required>
                    <button type="submit" name="edit_class">Modifier</button>
                </form>
                <!-- Supprimer -->
                <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Voulez-vous supprimer cette classe ?')">Supprimer</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

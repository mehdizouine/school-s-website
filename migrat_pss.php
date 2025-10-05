<?php
// migrate_passwords.php
include('db.php'); // adapte le chemin si nécessaire

$res = $conn->query("SELECT ID, Password FROM login");
if (!$res) {
    die("Erreur: " . $conn->error);
}

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $plain = $row['Password'];

    // Vérifier si déjà migré
    $chk = $conn->prepare("SELECT Password_hash FROM login WHERE ID = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $r = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!empty($r['Password_hash'])) {
        echo "ID $id déjà migré — skip<br>";
        continue;
    }

    // Générer le hash
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    if ($hash === false) {
        echo "Erreur hash ID $id<br>";
        continue;
    }

    // Mettre à jour
    $upd = $conn->prepare("UPDATE login SET Password_hash = ? WHERE ID = ?");
    $upd->bind_param("si", $hash, $id);
    if ($upd->execute()) {
        echo "Migré ID $id<br>";
    } else {
        echo "Erreur update ID $id : " . $upd->error . "<br>";
    }
    $upd->close();
}

$conn->close();
echo "Migration terminée";

<?php
// ==========================
// CONFIGURATION DE BASE
// ==========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/authorisation.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
require_role('admin');

// ==========================
// UTILITAIRE DE VALIDATION
// ==========================
function requireInt($value, $min = 1) {
    $v = filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min]]);
    return $v !== false ? (int)$v : null;
}

// Fonction utilitaire : obtenir le nom d'une classe
function getClasseNom($conn, $id) {
    $stmt = $conn->prepare("SELECT nom_de_classe FROM classes WHERE ID = ?");
    if (!$stmt) return "Classe $id";
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ? $r['nom_de_classe'] : "Classe $id";
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {

  case 'stats':
      $totUsers = (int)$conn->query("SELECT COUNT(*) as c FROM login WHERE role='eleve'")->fetch_assoc()['c'];
      $totClasses = (int)$conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];
      $totNotes = (int)$conn->query("SELECT COUNT(*) as c FROM note")->fetch_assoc()['c'];
      $totDevoirs = (int)$conn->query("SELECT COUNT(*) as c FROM devoirs")->fetch_assoc()['c'];
      echo json_encode([
          'users' => $totUsers,
          'classes' => $totClasses,
          'notes' => $totNotes,
          'devoirs' => $totDevoirs
      ]);
      exit;

  case 'class_counts':
      $res = $conn->query("
          SELECT c.nom_de_classe, COUNT(l.ID) as cnt 
          FROM classes c 
          LEFT JOIN login l ON l.classe_id = c.ID AND l.role = 'eleve' 
          GROUP BY c.ID 
          ORDER BY c.nom_de_classe
      ");
      $labels = []; $data = [];
      while($r = $res->fetch_assoc()){
          $labels[] = $r['nom_de_classe'];
          $data[] = (int)$r['cnt'];
      }
      echo json_encode(['labels' => $labels, 'data' => $data]);
      exit;

  case 'matiere_counts':
    $res = $conn->query("
      SELECT 
        m.ID_matiere,
        m.matiere,
        AVG(n.note) as moyenne,
        COUNT(n.note) as total_notes,
        SUM(CASE WHEN n.note >= 15 THEN 1 ELSE 0 END) as notes_hautes,
        SUM(CASE WHEN n.note < 10 THEN 1 ELSE 0 END) as notes_basses
      FROM matiere m
      LEFT JOIN note n ON n.ID_matiere = m.ID_matiere
      GROUP BY m.ID_matiere
      ORDER BY m.matiere
    ");
    $labels = []; 
    $data = [];
    $stats = [];
    while($r = $res->fetch_assoc()){
      $matiere = $r['matiere'] ?? 'Inconnu';
      $moyenne = round((float)($r['moyenne'] ?? 0), 2);
      $total = (int)($r['total_notes'] ?? 0);
      $hautes = (int)($r['notes_hautes'] ?? 0);
      $basses = (int)($r['notes_basses'] ?? 0);
      
      $emoji = $moyenne >= 15 ? '🟢' : ($moyenne >= 10 ? '🟡' : '🔴');
      $labels[] = "{$emoji} {$matiere}";
      $data[] = $moyenne;
      
      $stats[] = [
        'matiere' => $matiere,
        'total' => $total,
        'hautes' => $hautes,
        'basses' => $basses,
        'pct_hautes' => $total > 0 ? round(100 * $hautes / $total, 1) : 0,
        'pct_basses' => $total > 0 ? round(100 * $basses / $total, 1) : 0
      ];
    }
    echo json_encode(['labels' => $labels, 'data' => $data, 'stats' => $stats]);
    exit;

  case 'eleves_moyenne':
      $res = $conn->query("
          SELECT l.ID, l.Username, c.nom_de_classe,
                 ROUND(AVG(n.note), 2) as moyenne
          FROM login l
          LEFT JOIN classes c ON l.classe_id = c.ID
          LEFT JOIN note n ON n.ID_eleve = l.ID
          WHERE l.role = 'eleve'
          GROUP BY l.ID
          ORDER BY moyenne DESC
      ");
      $rows = [];
      while($r = $res->fetch_assoc()) $rows[] = $r;
      echo json_encode($rows);
      exit;

  case 'last_items':
      $classe = requireInt($_GET['classe'] ?? null, 0);
      $items = [];

      $noteQuery = "
          SELECT 
              l.Username, 
              'Note' as type, 
              CONCAT(COALESCE(m.matiere, '—'), ' - ', COALESCE(e.nom_examen, '—')) as detail,
              CONCAT(n.note, '/20') as value, 
              n.ID_note as id, 
              n.note,
              n.ID_note as sort_key
          FROM note n
          JOIN login l ON l.ID = n.ID_eleve
          LEFT JOIN matiere m ON m.ID_matiere = n.ID_matiere
          LEFT JOIN examen e ON e.ID_examen = n.ID_exam
      ";
      $devoirQuery = "
          SELECT 
              'Devoir' as type, 
              d.titre as detail, 
              d.titre,
              d.date_limite as value, 
              d.id,
              CONCAT('(Classe: ', COALESCE(c.nom_de_classe, '—'), ')') as Username,
              UNIX_TIMESTAMP(d.date_limite) as sort_key
          FROM devoirs d
          LEFT JOIN classes c ON d.classe_id = c.ID
      ";

      if ($classe !== null && $classe > 0) {
          $noteQuery .= " WHERE l.classe_id = ? ";
          $devoirQuery .= " WHERE d.classe_id = ? ";
      }

      $noteQuery .= " ORDER BY n.ID_note DESC LIMIT 20";
      $devoirQuery .= " ORDER BY d.date_limite DESC LIMIT 10";

      if ($classe && $classe > 0) {
          $stmt = $conn->prepare($noteQuery);
          $stmt->bind_param("i", $classe);
          $stmt->execute();
          $res = $stmt->get_result();
      } else {
          $res = $conn->query($noteQuery);
      }
      while($r = $res->fetch_assoc()) $items[] = $r;

      if ($classe && $classe > 0) {
          $stmt = $conn->prepare($devoirQuery);
          $stmt->bind_param("i", $classe);
          $stmt->execute();
          $res = $stmt->get_result();
      } else {
          $res = $conn->query($devoirQuery);
      }
      while($r = $res->fetch_assoc()) $items[] = $r;

      usort($items, fn($a, $b) => ($b['sort_key'] ?? 0) - ($a['sort_key'] ?? 0));
      echo json_encode(array_slice($items, 0, 20));
      exit;

  case 'notes_classe_matiere':
      $classe = requireInt($_GET['classe'] ?? null);
      $matiere = requireInt($_GET['matiere'] ?? null);
      $examen = requireInt($_GET['examen'] ?? null, 0);
      $semestre = requireInt($_GET['semestre'] ?? null, 0);
      
      if ($classe === null || $matiere === null) {
          http_response_code(400);
          echo json_encode(['error' => 'Classe et matière requises']);
          exit;
      }

      $sql = "
          SELECT 
              l.Username,
              m.matiere,
              e.nom_examen,
              n.note,
              s.nom_semestre,
              n.ID_note
          FROM note n
          JOIN login l ON l.ID = n.ID_eleve
          LEFT JOIN matiere m ON m.ID_matiere = n.ID_matiere
          LEFT JOIN examen e ON e.ID_examen = n.ID_exam
          LEFT JOIN semestre s ON s.ID_semestre = n.ID_semestre
          WHERE l.classe_id = ? AND n.ID_matiere = ?
      ";

      $params = [$classe, $matiere];
      $types = "ii";

      if ($examen !== null) {
          $sql .= " AND n.ID_exam = ?";
          $params[] = $examen;
          $types .= "i";
      }
      if ($semestre !== null) {
          $sql .= " AND n.ID_semestre = ?";
          $params[] = $semestre;
          $types .= "i";
      }

      $sql .= " ORDER BY l.Username";

      $stmt = $conn->prepare($sql);
      if (!$stmt) {
          http_response_code(500);
          echo json_encode(['error' => 'Erreur interne']);
          exit;
      }
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $res = $stmt->get_result();
      $rows = [];
      while($r = $res->fetch_assoc()) $rows[] = $r;
      echo json_encode($rows);
      exit;

  case 'add_note':
      $eleve = requireInt($_POST['eleve'] ?? null);
      $matiere = requireInt($_POST['matiere'] ?? null);
      $note = filter_var($_POST['note'] ?? null, FILTER_VALIDATE_FLOAT);
      
      if ($eleve === null || $matiere === null || $note === false || $note < 0 || $note > 20) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'Données invalides']);
          exit;
      }
      
      $stmt = $conn->prepare("INSERT INTO note (ID_eleve, ID_matiere, note) VALUES (?, ?, ?)");
      $stmt->bind_param("iid", $eleve, $matiere, $note);
      $ok = $stmt->execute();
      echo json_encode(['ok' => (bool)$ok, 'id' => $stmt->insert_id]);
      exit;

  case 'delete_note':
      $id = requireInt($_POST['id'] ?? null);
      if ($id === null) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'error' => 'ID invalide']);
          exit;
      }
      $stmt = $conn->prepare("DELETE FROM note WHERE ID_note = ?");
      $stmt->bind_param("i", $id);
      $ok = $stmt->execute();
      echo json_encode(['ok' => (bool)$ok]);
      exit;

  case 'autocomplete':
      $q = trim($_GET['q'] ?? '');
      if (strlen($q) < 2) {
          echo json_encode([]);
          exit;
      }
      $like = "%{$q}%";
      $results = [];

      $stmt = $conn->prepare("SELECT Username FROM login WHERE Username LIKE ? LIMIT 10");
      $stmt->bind_param("s", $like);
      $stmt->execute();
      $res = $stmt->get_result();
      while($r = $res->fetch_assoc()) $results[] = $r['Username'];
      $stmt->close();

      $stmt2 = $conn->prepare("SELECT nom_de_classe FROM classes WHERE nom_de_classe LIKE ? LIMIT 10");
      $stmt2->bind_param("s", $like);
      $stmt2->execute();
      $res2 = $stmt2->get_result();
      while($r = $res2->fetch_assoc()) $results[] = $r['nom_de_classe'];
      $stmt2->close();

      echo json_encode(array_values(array_unique($results)));
      exit;

  case 'alertes_pedagogiques':
      $stmt = $conn->prepare("
          SELECT 
              l.Username,
              c.nom_de_classe,
              m.matiere,
              n.note,
              e.nom_examen
          FROM note n
          JOIN login l ON l.ID = n.ID_eleve
          LEFT JOIN classes c ON c.ID = l.classe_id
          LEFT JOIN matiere m ON m.ID_matiere = n.ID_matiere
          LEFT JOIN examen e ON e.ID_examen = n.ID_exam
          WHERE n.note <= ?
          ORDER BY n.note ASC, l.Username
          LIMIT 20
      ");
      $seuil = 10.0;
      $stmt->bind_param("d", $seuil);
      $stmt->execute();
      $res = $stmt->get_result();
      $alertes = [];
      while ($row = $res->fetch_assoc()) {
          $alertes[] = $row;
      }
      echo json_encode($alertes);
      exit;

  case 'notes_par_examen':
    $classe = requireInt($_GET['classe'] ?? null);
    $matiere = requireInt($_GET['matiere'] ?? null);
    
    if ($classe === null || $matiere === null) {
      http_response_code(400);
      echo json_encode(['error' => 'Classe et matière requises']);
      exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            e.nom_examen,
            AVG(n.note) as moyenne,
            COUNT(n.ID_note) as nb_notes
        FROM note n
        JOIN login l ON l.ID = n.ID_eleve
        LEFT JOIN examen e ON e.ID_examen = n.ID_exam
        WHERE l.classe_id = ? AND n.ID_matiere = ?
        GROUP BY e.ID_examen, e.nom_examen
        ORDER BY e.ID_examen
    ");
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['error' => 'Erreur préparation SQL']);
      exit;
    }
    $stmt->bind_param("ii", $classe, $matiere);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()) {
      $rows[] = [
        'nom_examen' => $r['nom_examen'] ?? 'Examen non défini',
        'moyenne' => round((float)$r['moyenne'], 2),
        'nb_notes' => (int)$r['nb_notes']
      ];
    }
    echo json_encode($rows);
    exit;

  case 'resume_classe':
    $classeId = requireInt($_GET['classe'] ?? null);
    if ($classeId === null) {
      http_response_code(400);
      echo json_encode(['error' => 'Classe requise']);
      exit;
    }

    $stmt = $conn->prepare("SELECT nom_de_classe FROM classes WHERE ID = ?");
    $stmt->bind_param("i", $classeId);
    $stmt->execute();
    $nomClasse = $stmt->get_result()->fetch_assoc()['nom_de_classe'] ?? 'Inconnue';
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as nb FROM login WHERE classe_id = ? AND role = 'eleve'");
    $stmt->bind_param("i", $classeId);
    $stmt->execute();
    $nbEleves = (int)$stmt->get_result()->fetch_assoc()['nb'];
    $stmt->close();

    $stmt = $conn->prepare("
      SELECT AVG(n.note) as moyenne
      FROM note n
      JOIN login l ON l.ID = n.ID_eleve
      WHERE l.classe_id = ?
    ");
    $stmt->bind_param("i", $classeId);
    $stmt->execute();
    $moyenne = round((float)($stmt->get_result()->fetch_assoc()['moyenne'] ?? 0), 2);
    $stmt->close();

    $stmt = $conn->prepare("
      SELECT COUNT(*) as nb_alertes
      FROM (
        SELECT l.ID
        FROM login l
        LEFT JOIN note n ON n.ID_eleve = l.ID
        WHERE l.classe_id = ? AND l.role = 'eleve'
        GROUP BY l.ID
        HAVING AVG(n.note) < 10
      ) sub
    ");
    $stmt->bind_param("i", $classeId);
    $stmt->execute();
    $nbAlertes = (int)$stmt->get_result()->fetch_assoc()['nb_alertes'];
    $stmt->close();

    $stmt = $conn->prepare("
      SELECT m.matiere, AVG(n.note) as moyenne
      FROM note n
      JOIN login l ON l.ID = n.ID_eleve
      JOIN matiere m ON m.ID_matiere = n.ID_matiere
      WHERE l.classe_id = ?
      GROUP BY m.ID_matiere
      ORDER BY moyenne DESC
      LIMIT 1
    ");
    $stmt->bind_param("i", $classeId);
    $stmt->execute();
    $meilleure = $stmt->get_result()->fetch_assoc();
    $meilleureMatiere = $meilleure ? $meilleure['matiere'] : '—';
    $meilleureMoyenne = $meilleure ? round((float)$meilleure['moyenne'], 2) : 0;
    $stmt->close();

    echo json_encode([
      'nom_classe' => $nomClasse,
      'nb_eleves' => $nbEleves,
      'moyenne_generale' => $moyenne,
      'nb_alertes' => $nbAlertes,
      'meilleure_matiere' => $meilleureMatiere,
      'meilleure_moyenne' => $meilleureMoyenne
    ]);
    exit;

  case 'distribution_notes':
    $classe = requireInt($_GET['classe'] ?? null);
    $matiere = requireInt($_GET['matiere'] ?? null);
    $examen = requireInt($_GET['examen'] ?? null, 0);
    $semestre = requireInt($_GET['semestre'] ?? null, 0);

    if ($classe === null || $matiere === null) {
      http_response_code(400);
      echo json_encode(['error' => 'Classe et matière requises']);
      exit;
    }

    $sql = "
      SELECT n.note
      FROM note n
      JOIN login l ON l.ID = n.ID_eleve
      WHERE l.classe_id = ? AND n.ID_matiere = ?
    ";
    $params = [$classe, $matiere];
    $types = "ii";

    if ($examen !== null) {
      $sql .= " AND n.ID_exam = ?";
      $params[] = $examen;
      $types .= "i";
    }
    if ($semestre !== null) {
      $sql .= " AND n.ID_semestre = ?";
      $params[] = $semestre;
      $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['error' => 'Erreur SQL']);
      exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $notes = [];
    while ($row = $res->fetch_assoc()) {
      $notes[] = (float)$row['note'];
    }
    $stmt->close();

    $tranches = [
      ['label' => '0–5', 'min' => 0, 'max' => 5, 'count' => 0],
      ['label' => '6–9', 'min' => 6, 'max' => 9.999, 'count' => 0],
      ['label' => '10–14', 'min' => 10, 'max' => 14.999, 'count' => 0],
      ['label' => '15–20', 'min' => 15, 'max' => 20, 'count' => 0]
    ];

    foreach ($notes as $note) {
      foreach ($tranches as &$t) {
        if ($note >= $t['min'] && $note <= $t['max']) {
          $t['count']++;
          break;
        }
      }
    }

    echo json_encode($tranches);
    exit;

  case 'comparaison_classes':
    $classe1 = requireInt($_GET['classe1'] ?? null);
    $classe2 = requireInt($_GET['classe2'] ?? null);
    if ($classe1 === null || $classe2 === null) {
      http_response_code(400);
      echo json_encode(['error' => 'Deux classes requises']);
      exit;
    }

    $matStmt = $conn->query("SELECT ID_matiere, matiere FROM matiere ORDER BY matiere");
    $matieres = [];
    while ($m = $matStmt->fetch_assoc()) {
      $matieres[$m['ID_matiere']] = $m['matiere'];
    }

    $stmt1 = $conn->prepare("
      SELECT m.ID_matiere, AVG(n.note) as moyenne
      FROM matiere m
      LEFT JOIN note n ON n.ID_matiere = m.ID_matiere
      LEFT JOIN login l ON l.ID = n.ID_eleve
      WHERE (l.classe_id = ? OR n.ID_eleve IS NULL)
      GROUP BY m.ID_matiere
    ");
    $stmt1->bind_param("i", $classe1);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    $moyennes1 = [];
    while ($r = $res1->fetch_assoc()) {
      $moyennes1[$r['ID_matiere']] = round((float)$r['moyenne'], 2);
    }
    $stmt1->close();

    $stmt2 = $conn->prepare("
      SELECT m.ID_matiere, AVG(n.note) as moyenne
      FROM matiere m
      LEFT JOIN note n ON n.ID_matiere = m.ID_matiere
      LEFT JOIN login l ON l.ID = n.ID_eleve
      WHERE (l.classe_id = ? OR n.ID_eleve IS NULL)
      GROUP BY m.ID_matiere
    ");
    $stmt2->bind_param("i", $classe2);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $moyennes2 = [];
    while ($r = $res2->fetch_assoc()) {
      $moyennes2[$r['ID_matiere']] = round((float)$r['moyenne'], 2);
    }
    $stmt2->close();

    $labels = [];
    $data1 = [];
    $data2 = [];
    foreach ($matieres as $id => $nom) {
      $labels[] = $nom;
      $data1[] = $moyennes1[$id] ?? 0;
      $data2[] = $moyennes2[$id] ?? 0;
    }

    echo json_encode([
      'labels' => $labels,
      'classe1' => ['nom' => getClasseNom($conn, $classe1), 'data' => $data1],
      'classe2' => ['nom' => getClasseNom($conn, $classe2), 'data' => $data2]
    ]);
    exit;

  case 'eleve_moyennes_matiere':
    $eleveId = requireInt($_GET['eleve_id'] ?? null);
    if ($eleveId === null) {
      http_response_code(400);
      echo json_encode(['error' => 'ID élève requis']);
      exit;
    }
    $stmt = $conn->prepare("
      SELECT m.ID_matiere, m.matiere, AVG(n.note) as moyenne
      FROM note n
      JOIN matiere m ON m.ID_matiere = n.ID_matiere
      WHERE n.ID_eleve = ?
      GROUP BY m.ID_matiere
      ORDER BY m.matiere
    ");
    $stmt->bind_param("i", $eleveId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()) {
      $rows[] = [
        'ID_matiere' => (int)$r['ID_matiere'],
        'matiere' => $r['matiere'],
        'moyenne' => (float)$r['moyenne']
      ];
    }
    echo json_encode($rows);
    exit;

  case 'eleve_dernieres_notes':
    $eleveId = requireInt($_GET['eleve_id'] ?? null);
    if ($eleveId === null) {
      http_response_code(400);
      echo json_encode(['error' => 'ID élève requis']);
      exit;
    }
    $stmt = $conn->prepare("
      SELECT m.matiere, n.note, e.nom_examen
      FROM note n
      LEFT JOIN matiere m ON m.ID_matiere = n.ID_matiere
      LEFT JOIN examen e ON e.ID_examen = n.ID_exam
      WHERE n.ID_eleve = ?
      ORDER BY n.ID_note DESC
      LIMIT 10
    ");
    $stmt->bind_param("i", $eleveId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()) {
      $rows[] = $r;
    }
    echo json_encode($rows);
    exit;
case 'eleve_statut':
    header('Content-Type: application/json; charset=utf-8');

    $eleveId = requireInt($_GET['eleve_id'] ?? null);
    if ($eleveId === null) {
        http_response_code(400);
        echo json_encode(['error' => 'ID élève requis']);
        exit;
    }

    try {
        // On récupère l'ID de la classe de l'élève
        $stmt = $conn->prepare("SELECT classe_id FROM login WHERE ID = ?");
        $stmt->bind_param("i", $eleveId);
        $stmt->execute();
        $res = $stmt->get_result();
        $classeId = ($res && $row = $res->fetch_assoc()) ? (int)$row['classe_id'] : null;
        $stmt->close();

        if ($classeId === null) {
            echo json_encode(['error'=>'Élève ou classe introuvable']);
            exit;
        }

        // Moyenne générale
        $stmt = $conn->prepare("SELECT AVG(note) AS moy FROM note WHERE ID_eleve = ?");
        $stmt->bind_param("i", $eleveId);
        $stmt->execute();
        $res = $stmt->get_result();
        $moyGen = $res ? (float)($res->fetch_assoc()['moy'] ?? 0) : 0;
        $stmt->close();

        // Alertes (<10) et excellentes (>=15) par matière
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN avg_moy < 10 THEN 1 ELSE 0 END) AS alertes,
                SUM(CASE WHEN avg_moy >= 15 THEN 1 ELSE 0 END) AS excellentes
            FROM (
                SELECT AVG(note) AS avg_moy
                FROM note
                WHERE ID_eleve = ?
                GROUP BY ID_matiere
            ) AS sub
        ");
        $stmt->bind_param("i", $eleveId);
        $stmt->execute();
        $res = $stmt->get_result();
        $counts = $res ? $res->fetch_assoc() : ['alertes'=>0,'excellentes'=>0];
        $alertes = (int)$counts['alertes'];
        $excellentes = (int)$counts['excellentes'];
        $stmt->close();

        // Matière la plus forte
        $stmt = $conn->prepare("
            SELECT m.matiere, AVG(n.note) AS moyenne
            FROM note n
            JOIN matiere m ON n.ID_matiere = m.ID_matiere
            WHERE n.ID_eleve = ?
            GROUP BY m.ID_matiere
            ORDER BY moyenne DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $eleveId);
        $stmt->execute();
        $res = $stmt->get_result();
        $top = $res ? $res->fetch_assoc() : null;
        $matiere_top = $top['matiere'] ?? null;
        $moyenne_top = isset($top['moyenne']) ? (float)$top['moyenne'] : null;
        $stmt->close();

        // Matière la plus faible
        $stmt = $conn->prepare("
            SELECT m.matiere, AVG(n.note) AS moyenne
            FROM note n
            JOIN matiere m ON n.ID_matiere = m.ID_matiere
            WHERE n.ID_eleve = ?
            GROUP BY m.ID_matiere
            ORDER BY moyenne ASC
            LIMIT 1
        ");
        $stmt->bind_param("i", $eleveId);
        $stmt->execute();
        $res = $stmt->get_result();
        $low = $res ? $res->fetch_assoc() : null;
        $matiere_faible = $low['matiere'] ?? null;
        $moyenne_faible = isset($low['moyenne']) ? (float)$low['moyenne'] : null;
        $stmt->close();

        // Classement dans la classe
        $stmt = $conn->prepare("
            SELECT COUNT(*) + 1 AS classement
            FROM login l
            WHERE l.classe_id = ? AND l.role='eleve' 
            AND (SELECT AVG(n.note) FROM note n WHERE n.ID_eleve = l.ID) > ?
        ");
        $stmt->bind_param("id", $classeId, $moyGen);
        $stmt->execute();
        $res = $stmt->get_result();
        $classement = ($res && $row = $res->fetch_assoc()) ? (int)$row['classement'] : 1;
        $stmt->close();

        // Total élèves dans la classe
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_eleves FROM login WHERE role='eleve' AND classe_id = ?");
        $stmt->bind_param("i", $classeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $total_eleves = ($res && $row = $res->fetch_assoc()) ? (int)$row['total_eleves'] : 0;
        $stmt->close();

        // Devoirs dans la classe (pas de colonne 'rendu')
        $stmt = $conn->prepare("SELECT COUNT(*) AS nb_devoirs FROM devoirs WHERE classe_id = ?");
        $stmt->bind_param("i", $classeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $devoirs = $res ? $res->fetch_assoc() : ['nb_devoirs'=>0];
        $stmt->close();
        $nb_devoirs_rendus = (int)$devoirs['nb_devoirs'];
        $nb_devoirs_non_rendus = 0; // on ne peut pas savoir sans colonne 'rendu'

        echo json_encode([
            'moyenne_generale' => $moyGen,
            'nb_alertes' => $alertes,
            'nb_excellentes' => $excellentes,
            'matiere_top' => $matiere_top,
            'moyenne_top' => $moyenne_top,
            'matiere_faible' => $matiere_faible,
            'moyenne_faible' => $moyenne_faible,
            'classement' => $classement,
            'total_eleves' => $total_eleves,
            'nb_devoirs_rendus' => $nb_devoirs_rendus,
            'nb_devoirs_non_rendus' => $nb_devoirs_non_rendus
        ]);

    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error'=>'Erreur serveur','message'=>$e->getMessage()]);
    }
    exit;




  case 'eleve_evolution_matiere':
    $eleveId = requireInt($_GET['eleve_id'] ?? null);
    $matiereId = requireInt($_GET['matiere_id'] ?? null);
    if ($eleveId === null || $matiereId === null) {
      http_response_code(400);
      echo json_encode(['error' => 'ID élève et matière requis']);
      exit;
    }

    $stmt = $conn->prepare("
      SELECT 
        n.note, 
        e.nom_examen,
        COALESCE(e.nom_examen, CONCAT('Note ', n.ID_note)) as label
      FROM note n
      LEFT JOIN examen e ON e.ID_examen = n.ID_exam
      WHERE n.ID_eleve = ? AND n.ID_matiere = ?
      ORDER BY n.ID_exam, n.ID_note
    ");
    $stmt->bind_param("ii", $eleveId, $matiereId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()) {
      $rows[] = $r;
    }
    echo json_encode($rows);
    exit;

  // ===================== GET SUBJECTS =====================
  case 'getSubjects':
    $result = $conn->query("SELECT ID_matiere as id, matiere as nom FROM matiere ORDER BY matiere ASC");
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
      $subjects[] = $row;
    }
    echo json_encode(["success" => true, "data" => $subjects]);
    exit;

  // ===================== GET EXAMS BY SUBJECT =====================
  case 'getExams':
    $id = requireInt($_GET['subject_id'] ?? null);
    if ($id === null) {
      http_response_code(400);
      echo json_encode(['error' => 'subject_id requis']);
      exit;
    }
    $stmt = $conn->prepare("SELECT ID_examen as id, nom_examen as titre FROM examen WHERE ID_matiere = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = [];
    while ($row = $result->fetch_assoc()) {
      $exams[] = $row;
    }
    echo json_encode(["success" => true, "data" => $exams]);
    exit;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Action invalide']);
    exit;
}
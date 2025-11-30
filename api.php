<?php
// ==========================
// CONFIGURATION DE BASE
// ==========================
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
          SELECT m.matiere, AVG(n.note) as moyenne
          FROM matiere m
          LEFT JOIN note n ON n.ID_matiere = m.ID_matiere
          GROUP BY m.ID_matiere
          ORDER BY m.matiere
      ");
      $labels = []; $data = [];
      while($r = $res->fetch_assoc()){
          $labels[] = $r['matiere'] ?? 'Inconnu';
          $data[] = round((float)($r['moyenne'] ?? 0), 2);
      }
      echo json_encode(['labels' => $labels, 'data' => $data]);
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
      $classe = requireInt($_GET['classe'] ?? null, 0); // 0 autorisé ici
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

      // Notes
      if ($classe && $classe > 0) {
          $stmt = $conn->prepare($noteQuery);
          $stmt->bind_param("i", $classe);
          $stmt->execute();
          $res = $stmt->get_result();
      } else {
          $res = $conn->query($noteQuery);
      }
      while($r = $res->fetch_assoc()) $items[] = $r;

      // Devoirs
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

  default:
      http_response_code(400);
      echo json_encode(['error' => 'Action invalide']);
      exit;
// NOTES PAR EXAMEN (POUR PROGRESSION)
case 'notes_par_examen':
    $classe = (int)($_GET['classe'] ?? 0);
    $matiere = (int)($_GET['matiere'] ?? 0);
    
    if(!$classe || !$matiere) {
        echo json_encode(['error' => 'classe et matiere requis']);
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
        GROUP BY e.ID_examen
        ORDER BY e.ID_examen
    ");
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
         
}
?>
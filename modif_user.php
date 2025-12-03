<?php
session_start();
include('db.php');
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');

// Récupérer toutes les classes
$classesList = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
$classes = [];
while($c = $classesList->fetch_assoc()){
    $classes[$c['ID']] = $c['nom_de_classe'];
}

// Ajouter ou modifier un utilisateur
if(isset($_POST['save_user'])) {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $classe_ids = $_POST['classe_id'] ?? [];

    // Hasher le mot de passe uniquement si rempli
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    if($id) {
        // Si on passe d'un rôle prof, supprimer d'abord ses anciennes liaisons
        if($role == 'prof') {
            $stmt = $conn->prepare("DELETE FROM prof_classes WHERE prof_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // Si le mot de passe est rempli → on met à jour le hash aussi
        if(!empty($password)) {
            $sql = "UPDATE login SET Username = ?, Password_hash = ?, role = ?, classe_id = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            // Pour les rôles 'prof' on va mettre NULL dans la colonne classe_id (les classes sont dans prof_classes)
            $classe_id_single = ($role != 'prof') ? (int)($classe_ids[0] ?? 0) : null;
            // Bind: s = username, s = password hash, s = role, i = classe_id (nullable), i = ID
            // Pour les valeurs NULL, bind_param attend une variable — on gère avec null casté en null_value
            if ($classe_id_single === null) {
                // MySQLi ne prend pas le type 'n' pour null; on envoie 0 et ensuite on peut UPDATE ... OR NULL logic is simpler:
                // Ici on enverra 0 et puis dans la requête on pourrait setter NULL si 0 — mais pour simplicité, on accepte 0 = pas de classe.
                $classe_id_single = 0;
            }
            $stmt->bind_param("sssii", $username, $hashedPassword, $role, $classe_id_single, $id);
        } else {
            // Ne pas modifier le mot de passe
            $sql = "UPDATE login SET Username = ?, role = ?, classe_id = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $classe_id_single = ($role != 'prof') ? (int)($classe_ids[0] ?? 0) : 0;
            $stmt->bind_param("ssii", $username, $role, $classe_id_single, $id);
        }

        $stmt->execute();
        $stmt->close();

    } else {
        // Add user - pour création, on exige idéalement un mot de passe
        if(empty($password)) {
            // Optionnel : tu peux forcer la création d'un mot de passe
            // Ici je renvoie simplement une erreur simple (tu peux remplacer par gestion d'erreur UI)
            die("Erreur : mot de passe requis pour créer un utilisateur.");
        }

        $classe_id_single = ($role != 'prof') ? (int)($classe_ids[0] ?? 0) : 0;
        $sql = "INSERT INTO login (Username, Password_hash, role, classe_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $hashedPassword, $role, $classe_id_single);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
    }

    // Enregistrer classes multiples pour prof
    if($role == 'prof' && !empty($classe_ids) && $id) {
        $stmt = $conn->prepare("INSERT INTO prof_classes (prof_id, classe_id) VALUES (?, ?)");
        foreach($classe_ids as $cid) {
            $cid_i = (int)$cid;
            $stmt->bind_param("ii", $id, $cid_i);
            $stmt->execute();
        }
        $stmt->close();
    }

    header("Location: modif_user.php");
    exit;
}

// Supprimer utilisateur
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM login WHERE ID=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: modif_user.php");
    exit;
}

// Récupérer utilisateurs
$result = $conn->query("SELECT * FROM login ORDER BY ID ASC");

// Fonction helper pour récupérer les classes d'un prof
function getProfClasses($conn, $prof_id){
    $ids = [];
    $q = $conn->prepare("SELECT classe_id FROM prof_classes WHERE prof_id=?");
    $q->bind_param("i",$prof_id);
    $q->execute();
    $r = $q->get_result();
    while($c = $r->fetch_assoc()) $ids[] = $c['classe_id'];
    $q->close();
    return $ids;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des Utilisateurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-lg p-4" style="border-radius:20px;">
        <h3 class="mb-4 text-center text-primary"><i class="bi bi-people-fill"></i> Gestion des Utilisateurs</h3>

        <!-- Formulaire -->
        <form method="POST" class="row g-3">
             <?= csrf_field() ?>
            <input type="hidden" name="id" id="id">

            <div class="col-md-3">
                <input type="text" name="username" id="username" class="form-control" placeholder="Nom d’utilisateur" required>
            </div>
            <div class="col-md-3">
                <input type="password" name="password" id="password" class="form-control" placeholder="Mot de passe">
            </div>
            <div class="col-md-2">
                <select name="role" id="role" class="form-select" required>
                    <option value="">Choisir rôle</option>
                    <option value="eleve">Élève</option>
                    <option value="admin">Admin</option>
                    <option value="prof">Prof</option>
                </select>
            </div>
            <div class="col-md-4">
                <!-- on laisse multiple mais on l'activera via JS si besoin -->
                <select name="classe_id[]" id="classe_id" class="form-select" multiple style="display:none;">
                    <?php foreach($classes as $idc => $nom): ?>
                        <option value="<?= $idc ?>"><?= htmlspecialchars($nom) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" name="save_user" class="btn btn-success"><i class="bi bi-save"></i></button>
            </div>
        </form>
    </div>

    <hr class="my-4">

    <!-- Tableau -->
    <div class="card shadow-lg p-4" style="border-radius:20px;">
        <h4 class="mb-3"><i class="bi bi-list-check"></i> Liste des utilisateurs</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Nom d’utilisateur</th>
                        <th>Mot de passe</th>
                        <th>Rôle</th>
                        <th>Classes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['ID'] ?></td>
                        <td><?= htmlspecialchars($row['Username']) ?></td>
                        <td>********</td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($row['role']) ?></span></td>
                        <td>
                        <?php
                            if($row['role'] == 'prof') {
                                $classIds = getProfClasses($conn,$row['ID']);
                                $classNames = [];
                                foreach($classIds as $cid) $classNames[] = $classes[$cid] ?? '';
                                echo htmlspecialchars(implode(", ", $classNames));
                            } else {
                                echo htmlspecialchars($classes[$row['classe_id']] ?? '-');
                            }
                        ?>
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                onclick='editUser(
                                    <?= (int)$row['ID'] ?>,
                                    <?= json_encode($row['Username']) ?>,
                                    "",
                                    <?= json_encode($row['role']) ?>,
                                    <?= ($row['role']=='prof' ? json_encode(getProfClasses($conn,$row['ID'])) : json_encode((int)($row['classe_id'] ?? 0))) ?>
                                )'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="modif-user.php?delete=<?= (int)$row['ID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet utilisateur ?')">
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
const roleSelect = document.getElementById('role');
const classeSelect = document.getElementById('classe_id');

function updateClasseUI() {
    const isProf = roleSelect.value === 'prof';
    if(isProf){
        classeSelect.style.display = 'block';
        classeSelect.setAttribute('multiple','multiple');
    } else {
        // si élève ou admin, on affiche aussi le select (single) — si tu veux le cacher pour admin, change ici
        classeSelect.style.display = 'block';
        classeSelect.removeAttribute('multiple');
    }
}

// initial UI
updateClasseUI();

roleSelect.addEventListener('change', updateClasseUI);

function editUser(id, username, password, role, classe_ids){
    document.getElementById('id').value = id;
    document.getElementById('username').value = username;
    document.getElementById('password').value = ''; // toujours vide pour sécurité
    document.getElementById('role').value = role;

    // reset options
    Array.from(classeSelect.options).forEach(opt => opt.selected = false);

    if(role === 'prof') {
        // classe_ids est un tableau
        if(Array.isArray(classe_ids)) {
            classe_ids.forEach(cid => {
                const opt = Array.from(classeSelect.options).find(o => o.value==cid);
                if(opt) opt.selected = true;
            });
        }
    } else {
        // classe_ids est un entier (0 si none)
        if(classe_ids && classe_ids != 0) {
            const opt = Array.from(classeSelect.options).find(o => o.value==classe_ids);
            if(opt) opt.selected = true;
        }
    }

    updateClasseUI();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>


<style>
/* Enhanced User Management System CSS - Light Teal Theme */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Table-specific light colors */
    --table-header-bg: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.85) 100%);
    --table-row-even: rgba(226, 250, 248, 0.4);
    --table-row-odd: rgba(255, 255, 255, 0.7);
    --table-hover: rgba(178, 245, 234, 0.4);
    --table-border: rgba(14, 119, 112, 0.15);
    
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

/* Enhanced Body and Background */
body {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
    color: #2d3748;
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
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

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Enhanced Typography */
h2 {
    background: rgba(27, 209, 194);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin-bottom: 35px;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    letter-spacing: -0.5px;
    position: relative;
}

h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 0.8s ease-out;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 80px; }
}

h4 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-align: center;
    margin: 25px 0;
    font-size: clamp(1.2rem, 3vw, 1.8rem);
    letter-spacing: -0.3px;
}

/* Enhanced Card Design */
.card {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    padding: 35px;
    margin-bottom: 35px;
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

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
    transform: translateY(-5px) scale(1.01);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(27, 209, 194, 0.3);
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

/* Enhanced Form Styling */
form {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius-md);
    padding: 30px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
}

form .row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    align-items: end;
}

/* Individual form field containers with icons */
form .col-md-3, form .col-md-2 {
    position: relative;
    flex: 1;
    min-width: 160px;
}

/* Base styling for all form controls */
form input[type="text"], form select {
    width: 100%;
    padding: 18px 20px 18px 55px !important;
    border-radius: var(--border-radius-md) !important;
    border: 2px solid rgba(27, 209, 194, 0.3) !important;
    font-size: 15px;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    color: #2d3748 !important;
    transition: var(--transition-smooth);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 20px rgba(14, 119, 112, 0.1) !important;
    height: 58px;
    appearance: none;
    cursor: pointer;
}

/* Icons for form fields */
form .col-md-3:nth-child(2)::before {
    content: '\F4DA';  /* bi-person */
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-family: 'bootstrap-icons';
    font-size: 20px;
    color: var(--primary-color);
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-smooth);
}

form .col-md-3:nth-child(3)::before {
    content: '\F512';  /* bi-lock */
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-family: 'bootstrap-icons';
    font-size: 20px;
    color: var(--primary-color);
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-smooth);
}

form .col-md-2:nth-child(4)::before {
    content: '\F586';  /* bi-star */
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-family: 'bootstrap-icons';
    font-size: 20px;
    color: var(--primary-color);
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-smooth);
}

form .col-md-2:nth-child(5)::before {
    content: '\F2A4';  /* bi-book */
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-family: 'bootstrap-icons';
    font-size: 20px;
    color: var(--primary-color);
    z-index: 5;
    pointer-events: none;
    transition: var(--transition-smooth);
}

/* Custom dropdown arrows for selects */
form select {
    background-image: linear-gradient(45deg, transparent 50%, var(--primary-color) 50%), 
                      linear-gradient(135deg, var(--primary-color) 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 2px), 
                         calc(100% - 15px) calc(1em + 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    padding-right: 50px !important;
}

/* Focus states with icon animation */
form input[type="text"]:focus, form select:focus {
    outline: none !important;
    border-color: var(--primary-dark) !important;
    background: rgba(255, 255, 255, 1) !important;
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 8px 30px rgba(27, 209, 194, 0.2) !important;
    transform: translateY(-3px);
}

/* Icon animation on focus */
form .col-md-3:focus-within::before,
form .col-md-2:focus-within::before {
    color: var(--primary-dark);
    transform: translateY(-50%) scale(1.2);
    filter: drop-shadow(0 0 8px rgba(27, 209, 194, 0.3));
}

/* Hover effects */
form input[type="text"]:hover, form select:hover {
    border-color: var(--primary-color) !important;
    background: rgba(255, 255, 255, 0.98) !important;
    transform: translateY(-2px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 25px rgba(27, 209, 194, 0.15) !important;
}

/* Enhanced Button */
form button {
    background: var(--primary-gradient);
    border: none;
    border-radius: 50px;
    padding: 15px 30px;
    color: white;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

form button::before {
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

form button:hover::before {
    width: 300px;
    height: 300px;
}

form button:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

form button:active {
    transform: translateY(0) scale(0.98);
}

/* Enhanced Alert */
.alert {
    background: linear-gradient(135deg, rgba(178, 245, 234, 0.2), rgba(226, 250, 248, 0.3));
    border: 1px solid rgba(14, 119, 112, 0.3);
    color: var(--primary-dark);
    border-radius: var(--border-radius-md);
    padding: 15px 25px;
    margin: 20px 0;
    font-weight: 600;
    text-align: center;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(14, 119, 112, 0.1);
}

/* Enhanced Table Container */
.table-responsive {
    border-radius: var(--border-radius-md);
    overflow: hidden;
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(255, 255, 255, 0.6));
    backdrop-filter: blur(15px);
    padding: 15px;
    margin-top: 25px;
    box-shadow: 
        0 15px 35px rgba(14, 119, 112, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    border: 1px solid rgba(14, 119, 112, 0.1);
    position: relative;
}

/* Enhanced Table */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 15px;
    color: #2d3748;
    background: transparent;
    margin: 0;
    border: none;
}

/* Enhanced Header */
thead th {
    background: var(--table-header-bg);
    color: #ffffff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    padding: 20px 16px;
    font-size: 13px;
    border: none;
    position: relative;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    border-right: 1px solid rgba(255, 255, 255, 0.15);
    transition: var(--transition-smooth);
}

thead th:last-child {
    border-right: none;
}

/* Add icons to headers */
thead th:nth-child(1)::before { content: '\F292 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* ID */
thead th:nth-child(2)::before { content: '\F4DA '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Username */
thead th:nth-child(3)::before { content: '\F512 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Password */
thead th:nth-child(4)::before { content: '\F586 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Role */
thead th:nth-child(5)::before { content: '\F2A4 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Classes */
thead th:nth-child(6)::before { content: '\F3E5 '; font-family: 'bootstrap-icons'; margin-right: 8px; }  /* Actions */

/* Enhanced Body Rows with Light Colors */
tbody tr {
    background: var(--table-row-odd);
    transition: var(--transition-smooth);
    border: none;
    position: relative;
}

tbody tr:nth-child(even) {
    background: var(--table-row-even);
}

/* Beautiful Hover Effect */
tbody tr:hover {
    background: var(--table-hover) !important;
    transform: translateX(8px) scale(1.02);
    box-shadow: 
        8px 0 25px rgba(27, 209, 194, 0.15),
        0 4px 20px rgba(14, 119, 112, 0.1);
    border-left: 4px solid var(--primary-color);
}

/* Enhanced Cells */
tbody td {
    padding: 18px 16px;
    vertical-align: middle;
    font-weight: 500;
    border: none;
    border-bottom: 1px solid var(--table-border);
    color: #2d3748;
    transition: var(--transition-smooth);
}

/* ID Cell Styling */
tbody td:first-child {
    font-weight: 700;
    color: var(--primary-dark);
    text-align: center;
    background: rgba(14, 119, 112, 0.08);
    border-radius: 10px;
    margin: 4px;
    position: relative;
}

tbody td:first-child::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.1), rgba(27, 209, 194, 0.1));
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: -1;
    transition: all 0.3s ease;
}

tbody tr:hover td:first-child::before {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.2), rgba(27, 209, 194, 0.2));
}

/* Username Cell Enhancement */
tbody td:nth-child(2) {
    font-weight: 600;
    color: var(--primary-dark);
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.2), rgba(255, 255, 255, 0.1));
    border-radius: 12px;
    margin: 2px;
}

/* Password Cell Enhancement */
tbody td:nth-child(3) {
    background: rgba(128, 128, 128, 0.1);
    border-radius: 10px;
    margin: 2px;
    font-family: 'Courier New', monospace;
    color: #666;
    letter-spacing: 2px;
}

/* Role Badge Styling */
.badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: none;
}

.badge.role-admin {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: white;
}

.badge.role-prof {
    background: linear-gradient(135deg, #3182ce 0%, #63b3ed 100%);
    color: white;
}

.badge.role-eleve {
    background: linear-gradient(135deg, #38a169 0%, #68d391 100%);
    color: white;
}

/* Classes Cell Enhancement */
tbody td:nth-child(5) {
    background: rgba(255, 165, 0, 0.1);
    border-radius: 10px;
    margin: 2px;
    font-weight: 600;
    color: #d97706;
}

/* Actions Cell Enhancement */
tbody td:last-child {
    background: rgba(248, 113, 113, 0.1);
    border-radius: 15px;
    margin: 2px;
    text-align: center;
}

/* Enhanced Action Buttons */
.btn-sm {
    padding: 10px 16px;
    border-radius: var(--border-radius-sm);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: var(--transition-elastic);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    display: inline-block;
    border: none;
    margin: 0 4px;
    position: relative;
    overflow: hidden;
}

.btn-warning {
    background: linear-gradient(135deg, #ed8936 0%, #fbb040 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
}

.btn-warning:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 10px 30px rgba(237, 137, 54, 0.4);
    background: linear-gradient(135deg, #dd7824 0%, #ea9f28 100%);
    color: #fff;
    text-decoration: none;
}

.btn-danger {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

.btn-danger:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 10px 30px rgba(229, 62, 62, 0.4);
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
    color: #fff;
    text-decoration: none;
}

.btn-sm:active {
    transform: translateY(0) scale(0.95);
}

/* Enhanced styling for different input types */
form input[name="username"] {
    background-color: rgba(240, 248, 255, 0.9) !important;
}

form input[name="password"] {
    background-color: rgba(255, 248, 220, 0.9) !important;
}

form select[name="role"] {
    background-color: rgba(248, 250, 252, 0.9) !important;
}

form select[name="classe_id[]"] {
    background-color: rgba(255, 245, 238, 0.9) !important;
}

/* Placeholder styling for inputs */
form input::placeholder {
    color: rgba(27, 209, 194, 0.6);
    font-style: italic;
    font-weight: 500;
}

/* Enhanced select options styling */
form select option {
    background: rgba(255, 255, 255, 0.95);
    color: #2d3748;
    padding: 12px 15px;
    font-weight: 500;
    border-bottom: 1px solid rgba(14, 119, 112, 0.1);
}

form select option:hover {
    background: rgba(226, 250, 248, 0.3);
}

form select option:checked {
    background: var(--primary-color);
    color: white;
}

/* Placeholder styling for selects */
form select option[value=""] {
    color: rgba(27, 209, 194, 0.6);
    font-style: italic;
    font-weight: 600;
}

/* Valid state styling */
form input[type="text"]:valid, form select:valid {
    border-color: rgba(34, 197, 94, 0.5) !important;
}

/* Invalid state styling */
form input[type="text"]:invalid:not(:placeholder-shown), form select:invalid:not([value=""]) {
    border-color: rgba(239, 68, 68, 0.5) !important;
}

/* Subtle Animation Effects */
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

tbody tr {
    animation: rowSlideIn 0.5s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.1s; }
tbody tr:nth-child(2) { animation-delay: 0.2s; }
tbody tr:nth-child(3) { animation-delay: 0.3s; }
tbody tr:nth-child(4) { animation-delay: 0.4s; }
tbody tr:nth-child(5) { animation-delay: 0.5s; }

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .card {
        padding: 25px;
        margin-bottom: 25px;
    }
    
    form .row {
        flex-direction: column;
        gap: 15px;
    }
    
    form input[type="text"], form select {
        min-width: 100%;
        padding: 12px 15px 12px 50px;
        height: 50px;
    }
    
    .table-responsive {
        padding: 10px;
    }
    
    table {
        font-size: 13px;
    }
    
    tbody td, thead th {
        padding: 12px 10px;
    }
    
    tbody tr:hover {
        transform: none;
        border-left: none;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    h4 {
        font-size: 1.3rem;
    }
    
    .badge {
        font-size: 11px;
        padding: 6px 12px;
    }
    
    .btn-sm {
        padding: 8px 12px;
        font-size: 12px;
        margin: 2px;
    }
}

@media (max-width: 480px) {
    .card {
        padding: 20px;
    }
    
    form {
        padding: 20px;
    }
    
    tbody td, thead th {
        padding: 10px 8px;
        font-size: 12px;
    }
    
    .btn-sm {
        padding: 6px 10px;
        font-size: 11px;
    }
    
    .badge {
        font-size: 10px;
        padding: 4px 8px;
    }
}

/* Custom Scrollbar */
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

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    tbody tr:hover {
        transform: none;
    }
}

/* Focus Management */
button:focus, input:focus, select:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    body {
        background: white !important;
    }
    
    .card {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
    
    .table-responsive {
        background: white !important;
        box-shadow: none !important;
    }
    
    thead th {
        background: #f0f0f0 !important;
        color: #000000 !important;
    }
    
    .btn-sm {
        display: none !important;
    }
    
    form {
        display: none !important;
    }
}

/* Hide classe select by default */
#classe_id {
    display: none !important;
}

/* Show classe select when needed */
#classe_id[style*="block"] {
    display: block !important;
}

/* Multi-select styling for prof classes */
#classe_id[multiple] {
    height: auto !important;
    min-height: 120px !important;
    padding: 10px 20px 10px 55px !important;
}

#classe_id[multiple] option {
    padding: 8px 12px;
    margin: 2px 0;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(14, 119, 112, 0.2);
}

#classe_id[multiple] option:checked {
    background: var(--primary-color) !important;
    color: white !important;
}
</style>

</body>
</html>
<?php
session_start();
include('db.php');

// === Exemple de session ===
// $_SESSION['role'] = 'admin' ou 'eleve'
// $_SESSION['user_id'] = ID de l'utilisateur connecté
$role = $_SESSION['role'] ?? 'eleve';
$user_id = $_SESSION['user_id'] ?? 0;

// Récupérer toutes les classes (pour Admin uniquement)
$classes = [];
if($role == 'admin'){
    $classes_result = $conn->query("SELECT * FROM classes ORDER BY nom_de_classe ASC");
    while($c = $classes_result->fetch_assoc()){
        $classes[$c['ID']] = $c['nom_de_classe'];
    }
}

// Déterminer la classe à afficher
if($role == 'admin'){
    $classe_ID = $_GET['classe_ID'] ?? 0; 
    $classe_ID = (int)$classe_ID;
} else {
    // Pour élève, récupérer sa classe depuis login
    $res = $conn->query("SELECT classe_ID FROM login WHERE ID = $user_id");
    $row = $res->fetch_assoc();
    $classe_ID = $row['classe_ID'] ?? 0;
}

// Récupérer le nom de la classe
if($classe_ID > 0){
    if($role != 'admin'){
        $res = $conn->query("SELECT nom_de_classe FROM classes WHERE ID = $classe_ID");
        $row = $res->fetch_assoc();
        $classe_name = $row['nom_de_classe'] ?? 'Votre classe';
    } else {
        $classe_name = $classes[$classe_ID] ?? 'Votre classe';
    }
}

// Récupérer les créneaux pour la classe
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sophisticated School Management System CSS - Teal Theme */
        :root {
            --primary-color: rgba(14, 119, 112, 0.8);
            --primary-dark: rgba(14, 119, 112, 1);
            --primary-light: rgba(14, 119, 112, 0.3);
            --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
            --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
            --success-gradient: linear-gradient(135deg, rgba(129, 199, 132, 0.9) 0%, rgba(200, 230, 201, 0.8) 100%);
            --warning-gradient: linear-gradient(135deg, #ffe082 0%, #ffcc80 100%);
            --danger-gradient: linear-gradient(135deg, #ef9a9a 0%, #ffcdd2 100%);
            
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

        /* Sophisticated Body and Background */
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
            background-attachment: fixed;
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
            font-weight: 400;
            overflow-x: hidden;
            position: relative;
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

        /* Container with Advanced Glassmorphism */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        /* Premium Card Design with Glassmorphism */
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

        /* Card Hover Effects */
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
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-heavy);
            border-color: rgba(27, 209, 194, 0.3);
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
        }

        /* Sophisticated Typography */
        h3, h4 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            text-align: center;
            margin-bottom: 35px;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            letter-spacing: -0.5px;
            position: relative;
        }

        h3::after, h4::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
            animation: lineGrow 0.8s ease-out;
        }

        @keyframes lineGrow {
            from { width: 0; }
            to { width: 60px; }
        }

        /* Form styling for class selector */
        .class-selector {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            padding: 30px;
            margin-bottom: 30px;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .form-select {
            padding: 18px 20px !important;
            border: 2px solid rgba(27, 209, 194, 0.3) !important;
            border-radius: var(--border-radius-md) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(15px);
            color: rgba(2, 145, 133, 0.8) !important;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition-smooth);
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 20px rgba(27, 209, 194, 0.1) !important;
            height: 58px;
            min-height: 58px;
        }

        .form-select:focus {
            outline: none !important;
            border-color: var(--primary-dark) !important;
            background: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 
                0 0 0 4px rgba(27, 209, 194, 0.25),
                inset 0 2px 8px rgba(0, 0, 0, 0.08),
                0 8px 30px rgba(27, 209, 194, 0.2) !important;
            transform: translateY(-3px);
            color: rgba(2, 145, 133, 1) !important;
        }

        .form-select option {
            background: rgba(14, 119, 112, 0.9);
            color: white;
            padding: 0.5rem;
        }

        /* Premium Button Design */
        .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: var(--border-radius-md) !important;
            padding: 18px 30px !important;
            color: white !important;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition-elastic);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary::before {
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

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05) !important;
            box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4) !important;
            background: var(--secondary-gradient) !important;
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.98) !important;
        }

        /* Class name styling */
        .class-name-card {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-md);
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition-smooth);
        }

        .class-name-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .class-name {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
        }

        /* Enhanced Table Styling */
        .schedule-container {
            background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(255, 255, 255, 0.6));
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius-md);
            padding: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
            margin-top: 20px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            color: #2d3748;
            transition: all 0.4s ease;
            background: transparent;
            margin-bottom: 0;
        }

        /* Header styling with icons */
        .table thead th {
            background: var(--primary-gradient);
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px 15px;
            font-size: 13px;
            border: none;
            position: relative;
            box-shadow: inset 0 -3px 5px rgba(0,0,0,0.1);
            border-right: 1px solid rgba(255,255,255,0.2);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            vertical-align: middle;
        }

        .table thead th:first-child {
            border-radius: var(--border-radius-sm) 0 0 0;
        }

        .table thead th:last-child {
            border-radius: 0 var(--border-radius-sm) 0 0;
            border-right: none;
        }

        /* Enhanced Body Rows */
        .table tbody tr {
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(14, 119, 112, 0.08);
            position: relative;
        }

        .table tbody tr:nth-child(even) {
            background: rgba(226, 250, 248, 0.3);
        }

        .table tbody tr:hover {
            background: rgba(178, 245, 234, 0.4) !important;
            transform: translateX(5px) scale(1.01);
            box-shadow: 
                5px 0 20px rgba(27, 209, 194, 0.15),
                0 4px 15px rgba(14, 119, 112, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        /* Cellules styling */
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            font-weight: 500;
            border: none;
            color: #2d3748;
            position: relative;
            transition: all 0.3s ease;
        }

        /* Day column styling */
        .table tbody td:first-child {
            font-weight: 700;
            color: var(--primary-dark);
            text-align: center;
            background: rgba(14, 119, 112, 0.08);
            border-radius: 10px;
            margin: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .table tbody td:first-child::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(14, 119, 112, 0.1), rgba(27, 209, 194, 0.1));
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;
            transition: all 0.3s ease;
        }

        .table tbody tr:hover td:first-child::before {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, rgba(14, 119, 112, 0.2), rgba(27, 209, 194, 0.2));
        }

        /* Course styling with enhanced effects */
        .course {
            background: linear-gradient(135deg, rgba(14, 119, 112, 0.9), rgba(27, 209, 194, 0.8));
            color: white;
            border-radius: var(--border-radius-sm);
            padding: 12px 8px;
            font-weight: 600;
            text-align: center;
            margin: 4px;
            box-shadow: 0 4px 15px rgba(14, 119, 112, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .course::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition-smooth);
        }

        .course:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(14, 119, 112, 0.5);
            background: linear-gradient(135deg, rgba(14, 119, 112, 1), rgba(27, 209, 194, 0.9));
        }

        .course:hover::before {
            left: 100%;
        }

        /* Empty cell styling */
        .table tbody td:empty {
            background: rgba(255, 255, 255, 0.05);
            position: relative;
        }

        .table tbody td:empty::after {
            content: '—';
            color: rgba(14, 119, 112, 0.3);
            font-size: 1.5rem;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 300;
        }

        /* Animation for page load */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        /* Row animation */
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

        .table tbody tr {
            animation: rowSlideIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        .table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .table tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .table tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .table tbody tr:nth-child(5) { animation-delay: 0.5s; }
        .table tbody tr:nth-child(6) { animation-delay: 0.6s; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .card, .class-selector, .schedule-container {
                padding: 25px 20px;
                margin-bottom: 25px;
            }
            
            h3, h4 {
                font-size: 1.5rem;
            }
            
            .class-name {
                font-size: 1.4rem;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table tbody td, .table thead th {
                padding: 12px 8px;
            }
            
            .course {
                font-size: 0.75rem;
                padding: 8px 6px;
            }
            
            .form-select, .btn-primary {
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .table tbody tr:hover {
                transform: none;
                border-left: none;
            }
        }

        @media (max-width: 480px) {
            .card, .class-selector, .schedule-container {
                padding: 20px 15px;
                border-radius: var(--border-radius-md);
            }
            
            .table {
                font-size: 10px;
            }
            
            .course {
                font-size: 0.65rem;
                padding: 6px 4px;
            }
        }

        /* Custom scrollbar */
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

        /* Accessibility Enhancements */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus Management */
        button:focus, select:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container py-5 fade-in">
        <div class="card">
            <h3>
                <i class="bi bi-calendar-week me-3"></i>
                Emploi du Temps
            </h3>

            <!-- Formulaire de sélection (Admin uniquement) -->
            <?php if($role == 'admin'): ?>
                <div class="class-selector">
                    <form method="GET" class="text-center">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-6">
                                <select name="classe_ID" class="form-select">
                                    <option value="0">-- Choisir une classe --</option>
                                    <?php foreach($classes as $ID => $nom): ?>
                                        <option value="<?= $ID ?>" <?= ($ID == $classe_ID) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nom) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Afficher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($classe_ID > 0): ?>
                <div class="class-name-card">
                    <h4 class="class-name">
                        <i class="bi bi-people-fill me-2"></i>
                        <?= htmlspecialchars($classe_name) ?>
                    </h4>
                </div>
                
                <div class="schedule-container">
                    <div class="table-responsive">
                        <table class="table text-center">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar-day me-2"></i>Jour</th>
                                    <th><i class="bi bi-clock me-2"></i>08:30-10:30</th>
                                    <th><i class="bi bi-clock me-2"></i>10:30-12:30</th>
                                    <th><i class="bi bi-clock me-2"></i>13:30-15:30</th>
                                    <th><i class="bi bi-clock me-2"></i>15:30-17:30</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
                                $slots = ['08:30-10:30','10:30-12:30','13:30-15:30','15:30-17:30'];

                                foreach($jours as $jour){
                                    echo "<tr><td><i class='bi bi-chevron-right me-2'></i>$jour</td>";
                                    foreach($slots as $slot){
                                        $cell_content = "";
                                        list($slot_debut, $slot_fin) = explode('-', $slot);

                                        foreach($edt_par_classe as $c){
                                            if($c['jour'] == $jour){
                                                $cours_debut = substr($c['heure_debut'],0,5);
                                                $cours_fin = substr($c['heure_fin'],0,5);

                                                // Vérifier si le cours chevauche le slot
                                                if($cours_debut < $slot_fin && $cours_fin > $slot_debut){
                                                    $prof = $c['prof_id'] ?? ''; // ajoute cette colonne si tu veux afficher le prof
                                                    $cell_content .= "<div class='course'>
                                                        <i class='bi bi-book me-1'></i>"
                                                        .htmlspecialchars($c['matiere'])."<br>
                                                        <small>$cours_debut-$cours_fin</small>";
                                                    if($prof) $cell_content .= "<br><small>Prof: ".htmlspecialchars($prof)."</small>";
                                                    $cell_content .= "</div>";
                                                }
                                            }
                                        }

                                        echo "<td>".($cell_content ?: "")."</td>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                                </tbody>

                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
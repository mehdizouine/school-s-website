<?php
session_start();
include('db.php');
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('prof');

$prof_id = $_SESSION['user_id'];

// Récupérer les classes associées au prof
$prof_classes_res = $conn->prepare("SELECT classe_id FROM prof_classes WHERE prof_id = ?");
$prof_classes_res->bind_param("i", $prof_id);
$prof_classes_res->execute();
$prof_classes_result = $prof_classes_res->get_result();

$classes_ids = [];
while($c = $prof_classes_result->fetch_assoc()){
    $classes_ids[] = $c['classe_id'];
}
$prof_classes_res->close();

// Jours et horaires fixes
$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$heures = ['08:30-10:30','10:30-12:30','13:30-15:30','15:30-17:30'];

// EDT du prof
$edt = [];

if(count($classes_ids) > 0){
    $ids = implode(',', $classes_ids);

    $sql = "
        SELECT e.*, c.nom_de_classe, m.matiere
        FROM emploi_du_temps e
        JOIN classes c ON e.classe_id = c.ID
        JOIN matiere m ON e.matiere_id = m.ID_matiere
        WHERE e.classe_id IN ($ids) AND e.prof_id = $prof_id
        ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), e.heure_debut
    ";
    $edt_result = $conn->query($sql);

    while($row = $edt_result->fetch_assoc()){
        // Parcourir toutes les plages horaires et ajouter le cours à toutes celles concernées
        foreach($heures as $h){
            list($debut,$fin) = explode('-',$h);
            if(strtotime($row['heure_debut']) < strtotime($fin) && strtotime($row['heure_fin']) > strtotime($debut)){
                $edt[$row['jour']][$h][] = $row['matiere'] . " (" . $row['nom_de_classe'] . ")";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Emploi du Temps - Professeur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5 fade-in">
        <div class="card">
            <h2>
                <i class="fas fa-chalkboard-teacher professor-icon"></i>
                Mon Emploi du Temps
            </h2>

            <?php if(count($classes_ids) === 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Vous n'êtes associé à aucune classe pour le moment.
                </div>
            <?php else: ?>
                <div class="schedule-container">
                    <div class="table-responsive">
                        <table class="table text-center">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar-day me-2"></i>Jour / Heure</th>
                                    <?php foreach($heures as $h): ?>
                                        <th><i class="bi bi-clock me-2"></i><?= $h ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($jours as $jour): ?>
                                    <tr>
                                        <td><i class="bi bi-chevron-right me-2"></i><b><?= $jour ?></b></td>
                                        <?php foreach($heures as $h): ?>
                                            <td>
                                                <?php
                                                if(!empty($edt[$jour][$h])){
                                                    foreach($edt[$jour][$h] as $c){
                                                        echo "<span class='course-card'>
                                                            <i class='bi bi-book me-1'></i>" . htmlspecialchars($c) . "
                                                        </span>";
                                                    }
                                                } else {
                                                    echo "<span class='empty-cell'>—</span>";
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
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

</html>
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
        h2 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            text-align: center;
            margin-bottom: 35px;
            font-size: clamp(1.8rem, 5vw, 3rem);
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

        /* Enhanced Alert Styling */
        .alert {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-light);
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            color: #856404;
            font-weight: 500;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--warning-gradient);
        }

        .alert-warning {
            border-left: 4px solid #ffc107;
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

        /* Course card styling avec effets avancés */
        .course-card {
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
            display: block;
            border: none;
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition-smooth);
        }

        .course-card:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(14, 119, 112, 0.5);
            background: linear-gradient(135deg, rgba(14, 119, 112, 1), rgba(27, 209, 194, 0.9));
        }

        .course-card:hover::before {
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

        /* Style pour les cellules avec tiret */
        .empty-cell {
            color: rgba(14, 119, 112, 0.3);
            font-size: 1.5rem;
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

        /* Icon pour la fiche professeur */
        .professor-icon {
            display: inline-block;
            margin-right: 10px;
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .card, .schedule-container {
                padding: 25px 20px;
                margin-bottom: 25px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table tbody td, .table thead th {
                padding: 12px 8px;
            }
            
            .course-card {
                font-size: 0.75rem;
                padding: 8px 6px;
            }
            
            .table tbody tr:hover {
                transform: none;
                border-left: none;
            }
        }

        @media (max-width: 480px) {
            .card, .schedule-container {
                padding: 20px 15px;
                border-radius: var(--border-radius-md);
            }
            
            .table {
                font-size: 10px;
            }
            
            .course-card {
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
        .course-card:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
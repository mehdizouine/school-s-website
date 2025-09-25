<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé : vous devez vous connecter.");
}

$eleve_id = $_SESSION['user_id'];

// Récupérer la classe de l'élève depuis login
$res = $conn->prepare("SELECT classe_id FROM login WHERE ID = ?");
$res->bind_param("i", $eleve_id);
$res->execute();
$result = $res->get_result();
$row = $result->fetch_assoc();
$classe_id = $row['classe_id'];

// Récupérer les devoirs pour cette classe
$homework_res = $conn->prepare("
    SELECT d.id, d.titre, d.description, d.date_limite, d.fichier, c.nom_de_classe, m.matiere
    FROM devoirs d
    JOIN classes c ON d.classe_id = c.ID
    JOIN matiere m ON d.matiere_id = m.ID_matiere
    WHERE d.classe_id = ?
    ORDER BY d.date_limite ASC
");
$homework_res->bind_param("i", $classe_id);
$homework_res->execute();
$homework_result = $homework_res->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Devoirs</title>
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
            --info-gradient: linear-gradient(135deg, rgba(33, 150, 243, 0.9) 0%, rgba(144, 202, 249, 0.8) 100%);
            
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
            text-align: center;
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

        /* Enhanced Table Container */
        .homework-container {
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

        /* Enhanced Table Styling */
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

        /* Enhanced Class Cell */
        .class-cell {
            background: rgba(14, 119, 112, 0.08);
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
            border-radius: var(--border-radius-sm);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        /* Enhanced Subject Cell */
        .subject-cell {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(144, 202, 249, 0.1));
            color: #1976d2;
            font-weight: 600;
            text-align: center;
            border-radius: var(--border-radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        /* Enhanced Title Cell */
        .title-cell {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1rem;
        }

        /* Enhanced Description Cell */
        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }

        /* Enhanced Date Cell */
        .date-cell {
            font-weight: 600;
            text-align: center;
            color: #e65100;
            background: rgba(255, 152, 0, 0.1);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
        }

        /* Date urgence indicators */
        .date-urgent {
            background: rgba(244, 67, 54, 0.15) !important;
            color: #c62828 !important;
            animation: pulse 2s infinite;
        }

        .date-soon {
            background: rgba(255, 152, 0, 0.15) !important;
            color: #f57c00 !important;
        }

        .date-normal {
            background: rgba(76, 175, 80, 0.15) !important;
            color: #388e3c !important;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(244, 67, 54, 0); }
            100% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
        }

        /* Enhanced Download Link */
        .download-link {
            background: var(--success-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition-smooth);
            display: inline-block;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .download-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition-smooth);
        }

        .download-link:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
            text-decoration: none;
            color: white;
        }

        .download-link:hover::before {
            left: 100%;
        }

        /* Empty cell styling */
        .empty-cell {
            color: rgba(14, 119, 112, 0.3);
            font-size: 1.2rem;
            font-weight: 300;
            text-align: center;
        }

        /* Icon pour la fiche élève */
        .student-icon {
            display: inline-block;
            margin-right: 10px;
            color: var(--primary-color);
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
        .table tbody tr:nth-child(7) { animation-delay: 0.7s; }
        .table tbody tr:nth-child(8) { animation-delay: 0.8s; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .card, .homework-container {
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
            
            .download-link {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            
            .description-cell {
                max-width: 150px;
            }
            
            .table tbody tr:hover {
                transform: none;
                border-left: none;
            }
        }

        @media (max-width: 480px) {
            .card, .homework-container {
                padding: 20px 15px;
                border-radius: var(--border-radius-md);
            }
            
            .table {
                font-size: 10px;
            }
            
            .download-link {
                font-size: 0.65rem;
                padding: 4px 8px;
            }
            
            .description-cell {
                max-width: 100px;
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
        .download-link:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Stats badges */
        .stats-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-badge {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-md);
            padding: 15px 25px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition-smooth);
            min-width: 120px;
        }

        .stat-badge:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-5 fade-in">
        <div class="card">
            <h2>
                <i class="fas fa-graduation-cap student-icon"></i>
                Mes Devoirs
            </h2>

            <?php if($homework_result->num_rows == 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Aucun devoir disponible pour le moment.
                </div>
            <?php else: ?>
                <!-- Stats badges -->
                <div class="stats-container">
                    <div class="stat-badge">
                        <span class="stat-number"><?= $homework_result->num_rows ?></span>
                        <span class="stat-label">Devoirs</span>
                    </div>
                </div>

                <div class="homework-container">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-people-fill me-2"></i>Classe</th>
                                    <th><i class="bi bi-book me-2"></i>Matière</th>
                                    <th><i class="bi bi-card-text me-2"></i>Titre</th>
                                    <th><i class="bi bi-file-text me-2"></i>Description</th>
                                    <th><i class="bi bi-calendar-event me-2"></i>Date limite</th>
                                    <th><i class="bi bi-download me-2"></i>Fichier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset result pointer for counting
                                $homework_result->data_seek(0);
                                while($d = $homework_result->fetch_assoc()): 
                                    // Calculer l'urgence de la date
                                    $date_limite = strtotime($d['date_limite']);
                                    $today = strtotime(date('Y-m-d'));
                                    $days_diff = ($date_limite - $today) / (60 * 60 * 24);
                                    
                                    $date_class = 'date-normal';
                                    if($days_diff <= 1) {
                                        $date_class = 'date-urgent';
                                    } elseif($days_diff <= 3) {
                                        $date_class = 'date-soon';
                                    }
                                ?>
                                    <tr>
                                        <td class="class-cell">
                                            <i class="bi bi-mortarboard me-1"></i>
                                            <?= htmlspecialchars($d['nom_de_classe']) ?>
                                        </td>
                                        <td class="subject-cell">
                                            <i class="bi bi-journal-bookmark me-1"></i>
                                            <?= htmlspecialchars($d['matiere']) ?>
                                        </td>
                                        <td class="title-cell">
                                            <i class="bi bi-star-fill me-1"></i>
                                            <?= htmlspecialchars($d['titre']) ?>
                                        </td>
                                        <td class="description-cell">
                                            <?= nl2br(htmlspecialchars($d['description'])) ?>
                                        </td>
                                        <td class="date-cell <?= $date_class ?>">
                                            <i class="bi bi-alarm me-1"></i>
                                            <?= date('d/m/Y', strtotime($d['date_limite'])) ?>
                                            <?php if($days_diff <= 1): ?>
                                                <br><small><i class="bi bi-exclamation-triangle"></i> Urgent!</small>
                                            <?php elseif($days_diff <= 3): ?>
                                                <br><small><i class="bi bi-clock"></i> Bientôt</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($d['fichier']): ?>
                                                <a href="uploads/<?= htmlspecialchars($d['fichier']) ?>" 
                                                   target="_blank" 
                                                   class="download-link">
                                                    <i class="bi bi-cloud-download me-1"></i>
                                                    Télécharger
                                                </a>
                                            <?php else: ?>
                                                <span class="empty-cell">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
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
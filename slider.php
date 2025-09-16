<?php
include("db.php");

// Récupérer toutes les news
$result = $conn->query("SELECT * FROM news ORDER BY ID DESC");
$all_news = [];
if($result && $result->num_rows>0){
    while($row = $result->fetch_assoc()) $all_news[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>News Carousel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* Enhanced News Slider CSS - Teal Theme with Glassmorphism */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Glassmorphism effects */
    --glass-bg: rgba(255, 255, 255, 0.15);
    --glass-bg-strong: rgba(255, 255, 255, 0.25);
    --glass-border: rgba(255, 255, 255, 0.3);
    --backdrop-blur: blur(20px);
    --backdrop-blur-strong: blur(25px);
    
    /* Enhanced shadows */
    --shadow-light: 0 8px 32px rgba(14, 119, 112, 0.15);
    --shadow-medium: 0 15px 35px rgba(14, 119, 112, 0.2);
    --shadow-heavy: 0 20px 40px rgba(14, 119, 112, 0.3);
    
    --border-radius-sm: 12px;
    --border-radius-md: 20px;
    --border-radius-lg: 28px;
    
    --transition-smooth: all 0.4s ease;
    --transition-bounce: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Enhanced Universal Reset */
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

/* Enhanced Carousel Item */
.carousel-item {
    height: 100vh;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    overflow: hidden;
}

.carousel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: var(--transition-smooth);
    filter: brightness(0.8) contrast(1.1);
}

.carousel-item:hover img {
    transform: scale(1.05);
    filter: brightness(0.85) contrast(1.15);
}

/* Enhanced Overlay with Teal Gradient */
.overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        135deg,
        rgba(14, 119, 112, 0.4) 0%,
        rgba(27, 209, 194, 0.3) 50%,
        rgba(0, 0, 0, 0.5) 100%
    );
    backdrop-filter: blur(2px);
    z-index: 1;
}

/* Animated Background Elements */
.overlay::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 20%, rgba(27, 209, 194, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(27, 209, 194, 0.1) 0%, transparent 50%);
    animation: backgroundShift 15s ease-in-out infinite;
    pointer-events: none;
}

@keyframes backgroundShift {
    0%, 100% { opacity: 0.3; transform: translateY(0px); }
    50% { opacity: 0.6; transform: translateY(-10px); }
}

/* Enhanced News Content with Advanced Glassmorphism */
.news-content {
    position: absolute;
    top: 50%;
    left: 8%;
    transform: translateY(-50%);
    background: var(--glass-bg-strong);
    backdrop-filter: var(--backdrop-blur-strong);
    -webkit-backdrop-filter: var(--backdrop-blur-strong);
    color: #fff;
    border-radius: var(--border-radius-lg);
    padding: 30px 35px;
    max-width: 550px;
    max-height: 70vh;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-heavy);
    z-index: 2;
    animation: contentSlideIn 0.8s ease-out;
    overflow: hidden;
    box-sizing: border-box;
}

@keyframes contentSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0) translateX(0);
    }
}

/* Content shimmer effect */
.news-content::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: rotate(45deg);
    animation: shimmer 3s ease-in-out infinite;
    pointer-events: none;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

/* Enhanced Typography */
.news-content h2 {
    font-family: 'Inter', sans-serif;
    font-weight: 800;
    font-size: clamp(1.8rem, 3.5vw, 2.8rem);
    margin-bottom: 15px;
    line-height: 1.2;
    background: linear-gradient(135deg, #ffffff 0%, rgba(226, 250, 248, 0.9) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.news-content p {
    font-size: 1.1rem;
    margin-bottom: 20px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Enhanced Button with Teal Theme */
.btn-read {
    background: var(--primary-gradient);
    color: #fff;
    text-decoration: none;
    padding: 16px 32px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: var(--transition-elastic);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow-light);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.btn-read::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: var(--transition-smooth);
}

.btn-read:hover::before {
    width: 300px;
    height: 300px;
}

.btn-read:hover {
    color: #fff;
    text-decoration: none;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

.btn-read:active {
    transform: translateY(0) scale(0.98);
}

.btn-read::after {
    content: '\F138'; /* bi-arrow-right */
    font-family: 'bootstrap-icons';
    margin-left: 8px;
    transition: var(--transition-smooth);
}

.btn-read:hover::after {
    transform: translateX(3px);
}

/* Enhanced Carousel Controls */
.carousel-control-prev, .carousel-control-next {
    width: 80px;
    height: 80px;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border-radius: 50%;
    border: 1px solid var(--glass-border);
    top: 50%;
    transform: translateY(-50%);
    transition: var(--transition-smooth);
    opacity: 0.8;
    box-shadow: var(--shadow-medium);
}

.carousel-control-prev {
    left: 40px;
}

.carousel-control-next {
    right: 40px;
}

.carousel-control-prev:hover, .carousel-control-next:hover {
    background: var(--glass-bg-strong);
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
    box-shadow: var(--shadow-heavy);
}

.carousel-control-prev-icon, .carousel-control-next-icon {
    width: 24px;
    height: 24px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

/* Enhanced Carousel Indicators (if needed) */
.carousel-indicators {
    bottom: 30px;
    margin-bottom: 0;
}

.carousel-indicators [data-bs-target] {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    border: 2px solid rgba(255, 255, 255, 0.5);
    transition: var(--transition-smooth);
}

.carousel-indicators .active {
    background: var(--primary-color);
    border-color: rgba(255, 255, 255, 0.8);
    transform: scale(1.2);
}

/* Enhanced Loading State */
.carousel-item.loading {
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-item.loading::after {
    content: '';
    width: 50px;
    height: 50px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    .news-content {
        top: 50%;
        left: 5%;
        right: 5%;
        transform: translateY(-50%);
        padding: 25px;
        max-width: none;
    }
    
    .news-content h2 {
        font-size: clamp(1.5rem, 6vw, 2.2rem);
        margin-bottom: 15px;
    }
    
    .news-content p {
        font-size: 1rem;
        margin-bottom: 20px;
    }
    
    .btn-read {
        padding: 12px 24px;
        font-size: 14px;
    }
    
    .carousel-control-prev, .carousel-control-next {
        width: 60px;
        height: 60px;
    }
    
    .carousel-control-prev {
        left: 20px;
    }
    
    .carousel-control-next {
        right: 20px;
    }
}

@media (max-width: 480px) {
    .news-content {
        top: 50%;
        transform: translateY(-50%);
        padding: 20px;
    }
    
    .news-content h2 {
        font-size: 1.8rem;
    }
    
    .news-content p {
        font-size: 0.95rem;
    }
    
    .btn-read {
        padding: 10px 20px;
        font-size: 13px;
    }
    
    .carousel-control-prev, .carousel-control-next {
        width: 50px;
        height: 50px;
    }
    
    .carousel-control-prev {
        left: 15px;
    }
    
    .carousel-control-next {
        right: 15px;
    }
}

/* Enhanced Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .carousel-item:hover img {
        transform: none;
    }
    
    .btn-read:hover {
        transform: none;
    }
}

/* Focus management for accessibility */
.carousel-control-prev:focus, .carousel-control-next:focus, .btn-read:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .news-content {
        background: rgba(0, 0, 0, 0.9);
        border: 2px solid #ffffff;
    }
    
    .news-content h2 {
        color: #ffffff;
        -webkit-text-fill-color: #ffffff;
    }
    
    .news-content p {
        color: #ffffff;
    }
    
    .btn-read {
        background: #000000;
        border: 2px solid #ffffff;
    }
    
    .carousel-control-prev, .carousel-control-next {
        background: rgba(0, 0, 0, 0.8);
        border: 2px solid #ffffff;
    }
}

/* Print styles */
@media print {
    .carousel-control-prev, .carousel-control-next {
        display: none !important;
    }
    
    .news-content {
        position: static;
        background: white !important;
        color: black !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        border: 1px solid #ccc !important;
    }
    
    .news-content h2 {
        color: black !important;
        -webkit-text-fill-color: black !important;
    }
    
    .news-content p {
        color: black !important;
    }
}

/* Enhanced Animation for Carousel Transitions */
.carousel-fade .carousel-item {
    opacity: 0;
    transition: opacity 0.8s ease-in-out;
}

.carousel-fade .carousel-item.active {
    opacity: 1;
}

/* Add smooth transitions for content */
.carousel-item .news-content {
    transition: var(--transition-smooth);
}

.carousel-item:not(.active) .news-content {
    opacity: 0;
    transform: translateY(30px);
}

.carousel-item.active .news-content {
    opacity: 1;
    transform: translateY(0);
}
</style>
</head>
<body>

<div id="newsCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
  <div class="carousel-inner">
    <?php
    $active = "active";
    if(count($all_news)>0){
        foreach($all_news as $n){
            $img = !empty($n['photo']) ? 'assets/img/'.htmlspecialchars($n['photo']) : 'assets/img/default.png';
            echo '<div class="carousel-item '.$active.'">';
            echo '<img src="'.$img.'" alt="News">';
            echo '<div class="overlay"></div>';
            echo '<div class="news-content">';
            echo '<h2>'.htmlspecialchars($n['titre']).'</h2>';
            echo '<p>'.htmlspecialchars($n['sous_titre']).'</p>';
            echo '<a href="'.htmlspecialchars($n['lien']).'" class="btn-read">Lire Plus</a>';
            echo '</div></div>';
            $active = "";
        }
    } else {
        echo '<div class="carousel-item active"><div class="news-content"><h2>Aucune Actualité</h2></div></div>';
    }
    ?>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
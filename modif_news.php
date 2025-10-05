<?php
include("db.php");
require_once 'authorisation.php';
require_login();
validate_csrf();
require_role('admin');
// Ajouter / Editer news
if(isset($_POST['save'])){
    // Vérifier si l'ID existe et est valide
    $id = isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0 ? (int)$_POST['id'] : 0;
    $titre = strip_tags($_POST['titre']);
    $sous_titre = strip_tags($_POST['sous_titre']);
    $lien = strip_tags($_POST['lien']);

    // Upload photo si un fichier est choisi
    $photo = '';
    if(!empty($_FILES['photo']['name'])){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = uniqid('news_').'.'.$ext;
        $uploadDir = 'assets/img/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $targetFile = $uploadDir.$photo_name;
        if(move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)){
            $photo = $photo_name;
        }
    }

    if($id > 0){ // UPDATE
        if(!$photo){ // garder l'ancienne photo si pas de nouvelle
            $res = $conn->query("SELECT photo FROM news WHERE ID=$id");
            if($res && $row = $res->fetch_assoc()){
                $photo = $row['photo'];
            }
        }
        $stmt = $conn->prepare("UPDATE news SET titre=?, sous_titre=?, lien=?, photo=? WHERE ID=?");
        $stmt->bind_param("ssssi", $titre, $sous_titre, $lien, $photo, $id);
        $stmt->execute();
        $stmt->close();
    } else { // INSERT
        $stmt = $conn->prepare("INSERT INTO news (photo,titre,sous_titre,lien) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $photo, $titre, $sous_titre, $lien);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: modif_news.php");
    exit;
}

// Supprimer news
if(isset($_GET['delete'])){
    $id = isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] > 0 ? (int)$_GET['delete'] : 0;
    if($id > 0){ // supprimer uniquement si ID valide
        $stmt = $conn->prepare("DELETE FROM news WHERE ID=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: modif_news.php");
    exit;
}

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
<title>Dashboard News Pro - Gestion</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* Enhanced News Management System CSS - Teal Theme with Light Colors */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: rgba(27, 209, 194);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Light color palette for news management */
    --news-item-bg: rgba(255, 255, 255, 0.9);
    --news-item-hover-bg: rgba(255, 255, 255, 0.95);
    --input-bg: rgba(226, 250, 248, 0.3);
    --input-focus-bg: rgba(226, 250, 248, 0.5);
    --add-news-bg: rgba(14, 119, 112, 0.9);
    --add-news-hover-bg: rgba(27, 209, 194, 0.9);
    
    --glass-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.4);
    --backdrop-blur: blur(15px);
    
    --shadow-light: 0 8px 25px rgba(14, 119, 112, 0.15);
    --shadow-medium: 0 15px 35px rgba(14, 119, 112, 0.2);
    --shadow-heavy: 0 20px 40px rgba(14, 119, 112, 0.25);
    
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
    padding: 30px 20px;
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
        radial-gradient(circle at 20% 20%, rgba(27, 209, 194, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(27, 209, 194, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 50% 10%, rgba(14, 119, 112, 0.08) 0%, transparent 50%);
    animation: backgroundShift 20s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes backgroundShift {
    0%, 100% { 
        opacity: 0.4; 
        transform: translateY(0px); 
    }
    50% { 
        opacity: 0.7; 
        transform: translateY(-15px); 
    }
}

/* Enhanced Typography */
h2 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
    margin-bottom: 50px;
    color: #2d3748;
    font-weight: 700;
    font-size: clamp(2.5rem, 5vw, 3.5rem);
    letter-spacing: -0.8px;
    position: relative;
    animation: titleSlideIn 0.8s ease-out;
}

h2::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 150px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 1.2s ease-out 0.3s both;
}

@keyframes titleSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 150px; }
}

/* Enhanced Container */
.container {
    max-width: 1200px;
    margin: auto;
    position: relative;
    z-index: 1;
}

/* Enhanced Add News Button */
.add-news {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    color: var(--primary-dark);
    padding: 25px;
    border-radius: var(--border-radius-lg);
    text-align: center;
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 35px;
    cursor: pointer;
    transition: var(--transition-smooth);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-medium);
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: addNewsSlideIn 0.6s ease-out 0.4s both;
}

@keyframes addNewsSlideIn {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.add-news::before {
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

.add-news:hover::before {
    animation: shimmer 1.5s ease-in-out;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
}

.add-news:hover {
    background: var(--add-news-hover-bg);
    color: white;
    transform: translateY(-5px) scale(1.02);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(27, 209, 194, 0.4);
}

/* Enhanced New Form */
#newForm {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border-radius: var(--border-radius-lg);
    padding: 30px;
    margin-bottom: 40px;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-medium);
    animation: formSlideIn 0.5s ease-out;
}

@keyframes formSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 500px;
    }
}

/* Enhanced News Items */
.news-item {
    background: var(--news-item-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    padding: 30px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    transition: var(--transition-smooth);
    overflow: hidden;
    border: 1px solid var(--glass-border);
    position: relative;
    animation: newsItemSlideIn 0.6s ease-out;
    animation-fill-mode: both;
}

/* Staggered animation for news items */
.news-item:nth-child(3) { animation-delay: 0.1s; }
.news-item:nth-child(4) { animation-delay: 0.2s; }
.news-item:nth-child(5) { animation-delay: 0.3s; }
.news-item:nth-child(6) { animation-delay: 0.4s; }

@keyframes newsItemSlideIn {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.news-item::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(27, 209, 194, 0.08), transparent);
    transform: rotate(45deg);
    transition: var(--transition-smooth);
    opacity: 0;
}

.news-item:hover::before {
    animation: shimmer 2s ease-in-out;
}

.news-item:hover {
    transform: translateY(-8px) scale(1.01);
    background: var(--news-item-hover-bg);
    box-shadow: var(--shadow-heavy);
    border-color: rgba(27, 209, 194, 0.3);
}

/* Enhanced Images */
.news-item img {
    width: 180px;
    height: 110px;
    object-fit: cover;
    border-radius: var(--border-radius-sm);
    margin-right: 30px;
    transition: var(--transition-smooth);
    border: 3px solid rgba(27, 209, 194, 0.3);
    box-shadow: 0 8px 25px rgba(14, 119, 112, 0.15);
    position: relative;
}

.news-item img:hover {
    transform: scale(1.05) rotate(1deg);
    border-color: rgba(27, 209, 194, 0.6);
    box-shadow: 0 15px 35px rgba(14, 119, 112, 0.25);
}

/* Enhanced Form Layout */
.news-form {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Enhanced Input Fields */
input[type="text"], input[type="file"] {
    width: 100%;
    padding: 16px 20px;
    margin-bottom: 15px;
    border-radius: var(--border-radius-sm);
    border: 2px solid rgba(27, 209, 194, 0.3);
    transition: var(--transition-smooth);
    background: var(--input-bg);
    backdrop-filter: blur(10px);
    color: #2d3748;
    font-weight: 500;
    font-size: 15px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 15px rgba(14, 119, 112, 0.08);
}

input[type="text"]:focus, input[type="file"]:focus {
    border-color: var(--primary-dark);
    outline: none;
    background: var(--input-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 20px rgba(14, 119, 112, 0.15);
    transform: translateY(-2px);
}

input[type="text"]:hover, input[type="file"]:hover {
    border-color: var(--primary-color);
    background: rgba(226, 250, 248, 0.4);
    transform: translateY(-1px);
}

/* Different background colors for different input types */
input[type="text"]:nth-of-type(1) { /* Titre */
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.3), rgba(240, 248, 255, 0.2));
}

input[type="text"]:nth-of-type(2) { /* Sous-titre */
    background: linear-gradient(135deg, rgba(255, 248, 220, 0.3), rgba(226, 250, 248, 0.2));
}

input[type="text"]:nth-of-type(3) { /* Lien */
    background: linear-gradient(135deg, rgba(245, 243, 255, 0.3), rgba(226, 250, 248, 0.2));
}

/* Enhanced File Input */
input[type="file"] {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(226, 250, 248, 0.4));
    border: 2px dashed rgba(27, 209, 194, 0.4);
    cursor: pointer;
    position: relative;
}

input[type="file"]:hover {
    border-color: var(--primary-color);
    border-style: solid;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(226, 250, 248, 0.5));
}

input[type="file"]:focus {
    border-style: solid;
}

/* Enhanced Placeholder Styling */
input[type="text"]::placeholder {
    color: rgba(27, 209, 194, 0.6);
    font-weight: 500;
}

/* Enhanced Buttons */
button {
    border: none;
    padding: 14px 25px;
    border-radius: 50px;
    cursor: pointer;
    margin-top: 10px;
    font-weight: 700;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
}

button::before {
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

button:hover::before {
    width: 200px;
    height: 200px;
}

/* Save Button */
button.save {
    background: var(--primary-gradient);
    color: #fff;
    box-shadow: var(--shadow-light);
    margin-right: 10px;
}

button.save:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
}

/* Delete Button */
button.delete {
    background: linear-gradient(135deg, #e53e3e 0%, #fc8181 100%);
    color: #fff;
    margin-left: 10px;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

button.delete:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 35px rgba(229, 62, 62, 0.4);
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
}

button:active {
    transform: translateY(0) scale(0.95);
}

/* Button Container */
.news-form > div {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: flex-start;
    align-items: center;
}

.news-form > div a {
    text-decoration: none;
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 20px 15px;
    }
    
    .container {
        max-width: 100%;
    }
    
    .news-item {
        flex-direction: column;
        text-align: center;
        padding: 25px;
    }
    
    .news-item img {
        width: 100%;
        max-width: 300px;
        height: 180px;
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    .news-form {
        width: 100%;
    }
    
    input[type="text"], input[type="file"] {
        padding: 14px 18px;
    }
    
    button {
        padding: 12px 20px;
        font-size: 13px;
    }
    
    h2 {
        font-size: 2.2rem;
        margin-bottom: 40px;
    }
    
    .add-news {
        padding: 20px;
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .news-item {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .news-item img {
        height: 150px;
    }
    
    input[type="text"], input[type="file"] {
        padding: 12px 15px;
        font-size: 14px;
    }
    
    button {
        padding: 10px 16px;
        font-size: 12px;
    }
    
    .news-form > div {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    button.save, button.delete {
        width: 100%;
        margin: 5px 0;
    }
    
    h2 {
        font-size: 1.8rem;
    }
}

/* Custom scrollbar */
body::-webkit-scrollbar {
    width: 12px;
}

body::-webkit-scrollbar-track {
    background: rgba(14, 119, 112, 0.1);
}

body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.6), rgba(27, 209, 194, 0.6));
    border-radius: 6px;
}

body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, rgba(14, 119, 112, 0.8), rgba(27, 209, 194, 0.8));
}

/* Accessibility enhancements */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .news-item:hover, .add-news:hover {
        transform: none;
    }
}

/* Focus management */
button:focus, input:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .news-item, .add-news, #newForm {
        background: #ffffff;
        border: 2px solid #000000;
    }
    
    input[type="text"], input[type="file"] {
        background: #ffffff;
        border: 2px solid #000000;
    }
}

/* Print styles */
@media print {
    body {
        background: white !important;
    }
    
    .news-item, .add-news, #newForm {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
    
    button {
        display: none !important;
    }
    
    .add-news {
        display: none !important;
    }
}
</style>
</head>
<body>

<h2>Dashboard Gestion News</h2>
<div class="container">

<!-- Ajouter une nouvelle news -->
<div class="add-news" onclick="document.getElementById('newForm').style.display='block';">➕ Ajouter une News</div>
<div id="newForm" style="display:none; margin-bottom:30px;">
<form method="POST" enctype="multipart/form-data" class="news-form">
     <?= csrf_field() ?>
    <input type="file" name="photo" accept="image/*">
    <input type="text" name="titre" placeholder="Titre" required>
    <input type="text" name="sous_titre" placeholder="Sous-titre" required>
    <input type="text" name="lien" placeholder="Lien" required>
    <button type="submit" name="save" class="save">💾 Enregistrer</button>
</form>
</div>

<!-- News existantes -->
<?php foreach($all_news as $n): ?>
<div class="news-item">
    <img src="assets/img/<?php echo htmlspecialchars($n['photo'] ?: 'default.png'); ?>" alt="Preview" class="preview" id="preview-<?php echo $n['ID']; ?>">
    <form method="POST" enctype="multipart/form-data" class="news-form">
         <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?php echo $n['ID']; ?>">
        <input type="file" name="photo" accept="image/*" onchange="document.getElementById('preview-<?php echo $n['ID']; ?>').src=window.URL.createObjectURL(this.files[0])">
        <input type="text" name="titre" value="<?php echo htmlspecialchars($n['titre']); ?>" placeholder="Titre" required>
        <input type="text" name="sous_titre" value="<?php echo htmlspecialchars($n['sous_titre']); ?>" placeholder="Sous-titre" required>
        <input type="text" name="lien" value="<?php echo htmlspecialchars($n['lien']); ?>" placeholder="Lien" required>
        <div>
            <button type="submit" name="save" class="save">💾 Enregistrer</button>
            <a href="?delete=<?php echo $n['ID']; ?>" class="delete-link" onclick="return confirm('Supprimer cette news ?')">
                <button type="button" class="delete">❌ Supprimer</button>
            </a>
        </div>
    </form>
</div>
<?php endforeach; ?>

</div>
</body>
</html>


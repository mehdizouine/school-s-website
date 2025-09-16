<?php 
session_start();
include('db.php');

// Fetch user profile information
$user_id = $_SESSION['user_id']; // Retrieve user ID from session
$sql = "SELECT p.ID , p.Photo, p.Prénom, p.Nom, p.Email, p.Date_de_naissance, p.Année_scolaire, p.Classe 
        FROM profil p
        JOIN login l ON p.ID = l.ID
        WHERE l.ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the profile exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $photo = $row['Photo'];
    // Décoder les entités HTML pour afficher correctement les apostrophes
    $prenom = html_entity_decode($row['Prénom'], ENT_QUOTES);
    $nom = html_entity_decode($row['Nom'], ENT_QUOTES);
    $email = html_entity_decode($row['Email'], ENT_QUOTES);
    $dob = html_entity_decode($row['Date_de_naissance'], ENT_QUOTES);
    $schoolYear = html_entity_decode($row['Année_scolaire'], ENT_QUOTES);
    $class = html_entity_decode($row['Classe'], ENT_QUOTES);
} else {
    echo "No profile data found!";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="icon" href="assets/img/alwah logo.png">
    <title>Profil</title>
    <style>
/* Enhanced Profile Display System CSS - Teal Theme with Light Colors */

/* Advanced CSS Variables for Teal Design System */
:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    /* Light color palette for profile display */
    --container-bg: rgba(255, 255, 255, 0.9);
    --input-bg: rgba(226, 250, 248, 0.4);
    --input-hover-bg: rgba(226, 250, 248, 0.6);
    --light-teal: rgba(226, 250, 248, 0.3);
    --very-light-teal: rgba(226, 250, 248, 0.15);
    
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

/* Enhanced Universal Reset */
* {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Lucida Sans', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    outline: none;
    color: #2d3748;
}

/* Enhanced Body with Teal Background */
body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
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
        radial-gradient(circle at 20% 30%, rgba(27, 209, 194, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(27, 209, 194, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(14, 119, 112, 0.1) 0%, transparent 50%);
    animation: backgroundShift 25s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes backgroundShift {
    0%, 100% { 
        opacity: 0.4; 
        transform: translateY(0px) rotate(0deg); 
    }
    33% { 
        opacity: 0.7; 
        transform: translateY(-15px) rotate(1deg); 
    }
    66% { 
        opacity: 0.5; 
        transform: translateY(10px) rotate(-1deg); 
    }
}

/* Enhanced Profile Container */
.profile-container {
    width: 100%;
    max-width: 1100px;
    margin: 0;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;
    padding: 60px;
    border-radius: var(--border-radius-lg);
    gap: 30px;
    line-height: 1.6;
    box-shadow: var(--shadow-medium);
    border: 1px solid var(--glass-border);
    position: relative;
    overflow: hidden;
    animation: containerSlideIn 0.8s ease-out;
}

/* Container entrance animation */
@keyframes containerSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Container shimmer effect */
.profile-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(27, 209, 194, 0.08), transparent);
    transform: rotate(45deg);
    animation: shimmer 3s ease-in-out infinite;
    opacity: 0.7;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

/* Enhanced Typography */
h2 {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
    grid-column: 1 / 3;
    margin-bottom: 25px;
    font-weight: 700;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    letter-spacing: -0.8px;
    position: relative;
    z-index: 1;
}

h2::after {
    content: '';
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 4px;
    background: var(--primary-color);
    border-radius: 2px;
    animation: lineGrow 1.2s ease-out;
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 120px; }
}

/* Enhanced Profile Picture */
.profile-pic {
    grid-column: 1 / 3;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 35px;
    position: relative;
    z-index: 1;
}

.profile-pic img {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    border: 6px solid rgba(27, 209, 194, 0.4);
    box-shadow: 
        0 0 0 3px rgba(255, 255, 255, 0.8),
        0 15px 35px rgba(14, 119, 112, 0.25);
    transition: var(--transition-smooth);
    position: relative;
    animation: profilePicSlideIn 1s ease-out 0.3s both;
}

@keyframes profilePicSlideIn {
    from {
        opacity: 0;
        transform: scale(0.5) rotate(-10deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

/* Profile picture hover effect */
.profile-pic img:hover {
    transform: scale(1.05) rotate(2deg);
    border-color: rgba(27, 209, 194, 0.7);
    box-shadow: 
        0 0 0 3px rgba(255, 255, 255, 0.9),
        0 20px 45px rgba(14, 119, 112, 0.35);
}

/* Animated ring around profile picture */
.profile-pic::before {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    border: 3px solid transparent;
    border-top-color: rgba(27, 209, 194, 0.6);
    border-right-color: rgba(27, 209, 194, 0.3);
    border-radius: 50%;
    animation: rotate 4s linear infinite;
    z-index: -1;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Enhanced Form Layout */
#profileForm {
    grid-column: 1 / 3;
    width: 100%;
    display: contents;
}

.Part1, .Part2 {
    display: flex;
    flex-direction: column;
    gap: 25px;
    text-align: left;
    animation: partSlideIn 0.8s ease-out;
    animation-fill-mode: both;
}

.Part1 {
    animation-delay: 0.4s;
}

.Part2 {
    animation-delay: 0.6s;
}

@keyframes partSlideIn {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Enhanced Input Groups */
.input-group {
    margin-bottom: 25px;
    width: 100%;
    max-width: 400px;
    position: relative;
    animation: inputGroupFadeIn 0.6s ease-out;
    animation-fill-mode: both;
}

.input-group:nth-child(1) { animation-delay: 0.5s; }
.input-group:nth-child(2) { animation-delay: 0.6s; }
.input-group:nth-child(3) { animation-delay: 0.7s; }

@keyframes inputGroupFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Labels */
label {
    display: block;
    font-weight: 600;
    font-size: 15px;
    color: var(--primary-dark);
    margin-bottom: 8px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    position: relative;
}

label::before {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 30px;
    height: 2px;
    background: var(--primary-color);
    border-radius: 1px;
    transition: var(--transition-smooth);
}

.input-group:hover label::before {
    width: 60px;
}

/* Enhanced Input Fields */
input[type="text"] {
    width: 100%;
    padding: 18px 24px;
    border: 2px solid rgba(27, 209, 194, 0.3);
    background: var(--input-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius-md);
    font-size: 16px;
    font-weight: 500;
    color: #2d3748;
    text-align: left;
    transition: var(--transition-smooth);
    cursor: default;
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 4px 20px rgba(14, 119, 112, 0.08);
    position: relative;
}

/* Different background colors for different input types */
.input-group:nth-child(1) input { /* Prénom */
    background: linear-gradient(135deg, rgba(226, 250, 248, 0.4), rgba(240, 248, 255, 0.3));
}

.input-group:nth-child(2) input { /* Nom */
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.4), rgba(226, 250, 248, 0.3));
}

.input-group:nth-child(3) input { /* Classe */
    background: linear-gradient(135deg, rgba(255, 248, 220, 0.4), rgba(226, 250, 248, 0.3));
}

.Part2 .input-group:nth-child(1) input { /* Date de naissance */
    background: linear-gradient(135deg, rgba(255, 245, 238, 0.4), rgba(226, 250, 248, 0.3));
}

.Part2 .input-group:nth-child(2) input { /* Année scolaire */
    background: linear-gradient(135deg, rgba(240, 255, 244, 0.4), rgba(226, 250, 248, 0.3));
}

.Part2 .input-group:nth-child(3) input { /* Email */
    background: linear-gradient(135deg, rgba(245, 243, 255, 0.4), rgba(226, 250, 248, 0.3));
}

/* Enhanced Focus States */
input[type="text"]:focus {
    border-color: var(--primary-dark);
    background: var(--input-hover-bg);
    transform: translateY(-2px);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 8px 30px rgba(14, 119, 112, 0.15);
}

/* Hover Effects */
input[type="text"]:hover {
    border-color: var(--primary-color);
    background: var(--input-hover-bg);
    transform: translateY(-1px);
    box-shadow: 
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 25px rgba(14, 119, 112, 0.12);
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
        padding: 40px 30px;
        gap: 25px;
        max-width: 95%;
    }
    
    .profile-pic img {
        width: 140px;
        height: 140px;
    }
    
    .profile-pic::before {
        width: 160px;
        height: 160px;
    }
    
    .Part1, .Part2 {
        gap: 20px;
    }
    
    .input-group {
        max-width: 100%;
    }
    
    input[type="text"] {
        padding: 15px 20px;
        font-size: 15px;
    }
    
    h2 {
        font-size: 2.2rem;
        margin-bottom: 20px;
    }
    
    label {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .profile-container {
        padding: 30px 20px;
        gap: 20px;
    }
    
    .profile-pic img {
        width: 120px;
        height: 120px;
    }
    
    .profile-pic::before {
        width: 140px;
        height: 140px;
    }
    
    input[type="text"] {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    .input-group {
        margin-bottom: 20px;
    }
}

@media (max-width: 1024px) {
    .profile-container {
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        padding: 50px 40px;
    }
    
    .profile-pic img {
        width: 150px;
        height: 150px;
    }
    
    .profile-pic::before {
        width: 170px;
        height: 170px;
    }
}

/* Enhanced Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .profile-pic::before {
        animation: none;
    }
    
    input[type="text"]:hover,
    input[type="text"]:focus {
        transform: none;
    }
}

/* Focus management for accessibility */
input:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .profile-container {
        background: #ffffff;
        border: 3px solid #000000;
    }
    
    input[type="text"] {
        background: #ffffff;
        border: 2px solid #000000;
        color: #000000;
    }
    
    label {
        color: #000000;
    }
}

/* Print styles */
@media print {
    body {
        background: white !important;
    }
    
    .profile-container {
        background: white !important;
        box-shadow: none !important;
        border: 2px solid #ccc !important;
        backdrop-filter: none !important;
    }
    
    .profile-pic::before {
        display: none !important;
    }
    
    input[type="text"] {
        background: #f9f9f9 !important;
        border: 1px solid #ccc !important;
    }
}

/* Loading state animation */
.profile-container {
    opacity: 0;
    animation: fadeInProfile 1s ease-out 0.2s forwards;
}

@keyframes fadeInProfile {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Mon Profil</h2>
        <div class="profile-pic">
            <?php
                if (!empty($photo)) {
                    echo '<img src="' . htmlspecialchars($photo) . '" alt="Profil Image">';
                } else {
                    echo '<img src="assets/img/default-profile.png" alt="Default Profile Image">';
                }
            ?>
        </div>
        <form id="profileForm" action="Profil.php" method="post">
            <div class="Part1">
                <div class="input-group">
                    <label for="firstName">Prénom</label>
                    <input type="text" id="firstName" disabled value="<?php echo $prenom; ?>">
                </div>
                <div class="input-group">
                    <label for="lastName">Nom</label>
                    <input type="text" id="lastName" disabled value="<?php echo $nom; ?>">
                </div>
                <div class="input-group">
                    <label for="class">Classe</label>
                    <input type="text" id="class" disabled value="<?php echo $class; ?>">
                </div>
            </div>
            <div class="Part2">
                <div class="input-group">
                    <label for="dob">Date de naissance</label>
                    <input type="text" id="dob" disabled value="<?php echo $dob; ?>">
                </div>
                <div class="input-group">
                    <label for="schoolYear">Année scolaire</label>
                    <input type="text" id="schoolYear" disabled value="<?php echo $schoolYear; ?>">
                </div>
                <div class="input-group">
                    <label for="Email">Email</label>
                    <input type="text" id="Email" disabled value="<?php echo $email; ?>">
                </div>
            </div>
        </form>
    </div>
</body>
</html>

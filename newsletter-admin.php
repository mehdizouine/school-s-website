<?php
session_start();
include("db.php");
require_once 'authorisation.php';
require_login();
require_role('admin');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// --- Partie Autocomplétion AJAX ---
if(isset($_GET['q'])) {
    $q = "%{$_GET['q']}%";
    $results = [];

    // Emails
    $stmt = $conn->prepare("SELECT Email FROM profil WHERE Email LIKE ? LIMIT 10");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $results[] = $row['Email'];
    }

    // Classes
    $stmt = $conn->prepare("SELECT nom_de_classe FROM classes WHERE nom_de_classe LIKE ? LIMIT 10");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $results[] = $row['nom_de_classe'];
    }

    echo json_encode($results);
    exit;
}

// --- Partie envoi de newsletter ---
if(isset($_POST['send_newsletter'])) {
    $toList = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if(!$toList) { echo "Aucun destinataire."; exit; }

    $recipients = explode(',', $toList);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mehdizouine2007@gmail.com';
        $mail->Password = 'jhvdrzlomcidvfwo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ton.email@gmail.com', 'Admin Ecole');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($message);

        foreach($recipients as $to) {
            $to = trim($to);
            if(filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($to);
            } else {
                $stmt = $conn->prepare("SELECT Email FROM profil p JOIN classes c ON p.Classe=c.ID WHERE c.nom_de_classe=?");
                $stmt->bind_param("s", $to);
                $stmt->execute();
                $res = $stmt->get_result();
                while($row = $res->fetch_assoc()) {
                    $mail->addAddress($row['Email']);
                }
            }
        }

        $mail->send();
        echo "<div class='alert alert-success'>Newsletter envoyée avec succès !</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Erreur : {$mail->ErrorInfo}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Newsletter Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* Enhanced Newsletter Admin CSS - Teal Theme */

:root {
    --primary-color: rgba(14, 119, 112, 0.8);
    --primary-dark: rgba(14, 119, 112, 1);
    --primary-light: rgba(14, 119, 112, 0.3);
    --primary-gradient: rgba(27, 209, 194);
    --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    
    --glass-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.4);
    --backdrop-blur: blur(15px);
    --input-bg: rgba(226, 250, 248, 0.3);
    --input-focus-bg: rgba(226, 250, 248, 0.5);
    
    --shadow-light: 0 8px 25px rgba(14, 119, 112, 0.15);
    --shadow-medium: 0 15px 35px rgba(14, 119, 112, 0.2);
    --shadow-heavy: 0 20px 40px rgba(14, 119, 112, 0.25);
    
    --border-radius-sm: 12px;
    --border-radius-md: 20px;
    --border-radius-lg: 28px;
    
    --transition-smooth: all 0.4s ease;
    --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

body {
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
    color: #2d3748;
    padding: 40px 20px;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

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
    0%, 100% { opacity: 0.4; transform: translateY(0px); }
    50% { opacity: 0.7; transform: translateY(-15px); }
}

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
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes lineGrow {
    from { width: 0; }
    to { width: 150px; }
}

.container {
    max-width: 900px;
    margin: auto;
    position: relative;
    z-index: 1;
}

form {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    -webkit-backdrop-filter: var(--backdrop-blur);
    border-radius: var(--border-radius-lg);
    padding: 40px;
    padding-bottom: 60px;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-medium);
    animation: formSlideIn 0.6s ease-out;
    overflow: visible;
    position: relative;
}

@keyframes formSlideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mb-3 {
    margin-bottom: 25px;
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.mb-3:nth-child(1) { animation-delay: 0.1s; }
.mb-3:nth-child(2) { animation-delay: 0.2s; }
.mb-3:nth-child(3) { animation-delay: 0.3s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

label {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 12px;
    display: block;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

input[type="text"],
textarea {
    width: 100%;
    padding: 16px 20px;
    border-radius: var(--border-radius-sm);
    border: 2px solid rgba(27, 209, 194, 0.3);
    transition: var(--transition-smooth);
    background: var(--input-bg);
    backdrop-filter: blur(10px);
    color: #2d3748;
    font-weight: 500;
    font-size: 15px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 15px rgba(14, 119, 112, 0.08);
    font-family: 'Inter', 'Segoe UI', sans-serif;
}

input[type="text"]:focus,
textarea:focus {
    border-color: var(--primary-dark);
    outline: none;
    background: var(--input-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 20px rgba(14, 119, 112, 0.15);
    transform: translateY(-2px);
}

input[type="text"]:hover,
textarea:hover {
    border-color: var(--primary-color);
    background: rgba(226, 250, 248, 0.4);
    transform: translateY(-1px);
}

input[type="text"]::placeholder,
textarea::placeholder {
    color: rgba(27, 209, 194, 0.6);
    font-weight: 500;
}

textarea {
    resize: vertical;
    min-height: 150px;
    line-height: 1.6;
}

/* To Input Container */
.to-input-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
    background: var(--input-bg);
    border: 2px solid rgba(27, 209, 194, 0.3);
    border-radius: var(--border-radius-sm);
    min-height: 50px;
    align-items: center;
    transition: var(--transition-smooth);
    backdrop-filter: blur(10px);
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06);
}

.to-input-container:focus-within {
    border-color: var(--primary-dark);
    background: var(--input-focus-bg);
    box-shadow: 
        0 0 0 4px rgba(27, 209, 194, 0.25),
        inset 0 2px 8px rgba(0, 0, 0, 0.06),
        0 6px 20px rgba(14, 119, 112, 0.15);
}

#to-input {
    flex: 1;
    min-width: 150px;
    border: none;
    background: transparent;
    padding: 0;
    font-size: 15px;
    font-weight: 500;
    color: #2d3748;
    outline: none;
}

#to-input::placeholder {
    color: rgba(27, 209, 194, 0.6);
}

/* Chips */
.to-chip {
    background: linear-gradient(135deg, rgba(27, 209, 194, 0.2), rgba(14, 119, 112, 0.15));
    border: 1.5px solid rgba(27, 209, 194, 0.4);
    padding: 8px 16px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--primary-dark);
    font-size: 14px;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 12px rgba(14, 119, 112, 0.1);
    animation: chipSlideIn 0.3s ease-out;
}

@keyframes chipSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.to-chip:hover {
    background: linear-gradient(135deg, rgba(27, 209, 194, 0.3), rgba(14, 119, 112, 0.25));
    border-color: rgba(27, 209, 194, 0.6);
    box-shadow: 0 8px 20px rgba(14, 119, 112, 0.15);
}

.to-chip span {
    cursor: pointer;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
    transition: var(--transition-smooth);
}

.to-chip span:hover {
    transform: scale(1.3) rotate(90deg);
    color: #e53e3e;
}

/* Suggestions Box */
.suggestions-box {
    display: none;
    position: absolute;
    top: calc(100% - 2px);
    left: -2px;
    right: -2px;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-blur);
    border: 2px solid rgba(27, 209, 194, 0.3);
    border-top: none;
    border-radius: 0 0 var(--border-radius-sm) var(--border-radius-sm);
    max-height: 300px;
    overflow-y: auto;
    z-index: 10000;
    box-shadow: 0 10px 30px rgba(14, 119, 112, 0.2);
    animation: suggestionsSlideIn 0.3s ease-out;
}

@keyframes suggestionsSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.suggestions-box div {
    padding: 14px 20px;
    cursor: pointer;
    transition: var(--transition-smooth);
    border-bottom: 1px solid rgba(27, 209, 194, 0.1);
    color: #2d3748;
    font-weight: 500;
}

.suggestions-box div:last-child {
    border-bottom: none;
}

.suggestions-box div:hover {
    background: rgba(27, 209, 194, 0.2);
    padding-left: 25px;
}

/* Buttons */
button {
    border: none;
    padding: 16px 35px;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition-elastic);
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);
    color: #fff;
    box-shadow: var(--shadow-light);
    width: 100%;
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

button:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: var(--shadow-heavy);
}

button:active {
    transform: translateY(0) scale(0.98);
}

/* Alert Messages */
.alert {
    border-radius: var(--border-radius-sm);
    border: none;
    padding: 18px 25px;
    font-weight: 600;
    margin-bottom: 30px;
    animation: alertSlideIn 0.5s ease-out;
}

@keyframes alertSlideIn {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, rgba(48, 211, 111, 0.15), rgba(76, 175, 80, 0.1));
    border-left: 4px solid #30d36f;
    color: #22543d;
}

.alert-danger {
    background: linear-gradient(135deg, rgba(229, 62, 62, 0.15), rgba(244, 67, 54, 0.1));
    border-left: 4px solid #e53e3e;
    color: #742a2a;
}

/* Position relative for suggestions */
.mb-3.position-relative {
    position: relative;
}

.to-input-container {
    position: relative;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 25px 15px;
    }
    
    form {
        padding: 25px;
    }
    
    h2 {
        font-size: 2.2rem;
        margin-bottom: 35px;
    }
    
    input[type="text"],
    textarea {
        padding: 14px 16px;
        font-size: 14px;
    }
    
    button {
        padding: 14px 25px;
        font-size: 14px;
    }
    
    label {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    form {
        padding: 20px;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    input[type="text"],
    textarea {
        padding: 12px 14px;
        font-size: 13px;
    }
    
    button {
        padding: 12px 20px;
        font-size: 13px;
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

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

button:focus, input:focus, textarea:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    form {
        background: #ffffff;
        border: 2px solid #000000;
    }
    
    input[type="text"], textarea {
        background: #ffffff;
        border: 2px solid #000000;
    }
}
</style>
</head>
<body>

<h2>📧 Envoyer Newsletter</h2>

<div class="container">
    <form method="POST" id="newsletter-form">
        <div class="mb-3">
            <label>Destinataires (emails ou classes)</label>
        </div>
        <div style="position: relative; margin-bottom: 25px;">
            <div class="to-input-container" id="to-container">
                <input type="text" id="to-input" autocomplete="off" placeholder="Tapez un email ou une classe...">
            </div>
            <div id="suggestions" class="suggestions-box"></div>
            <input type="hidden" name="to" id="to-hidden">
        </div>

        <div class="mb-3">
            <label>Sujet</label>
            <input type="text" name="subject" placeholder="Entrez le sujet de la newsletter" required>
        </div>

        <div class="mb-3">
            <label>Message</label>
            <textarea name="message" placeholder="Rédigez votre message ici..." required></textarea>
        </div>

        <button type="submit" name="send_newsletter">✉️ Envoyer Newsletter</button>
    </form>
</div>

<script>
const container = document.getElementById('to-container');
const input = document.getElementById('to-input');
const suggestionsBox = document.getElementById('suggestions');
const hiddenInput = document.getElementById('to-hidden');
let recipients = [];

function updateHiddenInput() {
    hiddenInput.value = recipients.join(',');
}

function addRecipient(value) {
    if (!value || recipients.includes(value)) return;
    recipients.push(value);
    const chip = document.createElement('div');
    chip.className = 'to-chip';
    chip.textContent = value;
    const remove = document.createElement('span');
    remove.textContent = '×';
    remove.onclick = () => {
        container.removeChild(chip);
        recipients = recipients.filter(r => r !== value);
        updateHiddenInput();
    };
    chip.appendChild(remove);
    container.insertBefore(chip, input);
    input.value = '';
    updateHiddenInput();
}

input.addEventListener('input', function() {
    const query = this.value;
    if(query.length < 1) {
        suggestionsBox.style.display = 'none';
        return;
    }
    
    fetch('newsletter-admin.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            suggestionsBox.innerHTML = '';
            if(data.length > 0) {
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item;
                    div.addEventListener('click', () => addRecipient(item));
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            } else suggestionsBox.style.display = 'none';
        });
});

input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addRecipient(this.value.trim());
        suggestionsBox.style.display = 'none';
    } else if (e.key === 'Backspace' && this.value === '') {
        const chips = container.querySelectorAll('.to-chip');
        if(chips.length > 0){
            const last = chips[chips.length - 1];
            last.remove();
            recipients.pop();
            updateHiddenInput();
        }
    }
});

document.addEventListener('click', e => {
    if(!container.contains(e.target)){
        suggestionsBox.style.display = 'none';
    }
});
</script>

</body>
</html>
<?php
session_start();
include("db.php");
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

    // Prepare and insert into 'message_us' table
    $stmt = $conn->prepare("INSERT INTO message_us (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        $success = "✅ Message saved successfully!";
    } else {
        $success = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<head>
  <title>Accueil</title>
  <link href="assets/img/alwah logo.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css  " rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css  " rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <style>
  /* ✅ Force la transparence sur le formulaire de contact */
  #contact .box-shadow-full {
    background: transparent !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
  }

  /* ✅ S'assure que la section contact n'a pas de fond */
  section#contact {
    background-color: transparent !important;
  }

  /* ✅ Style minimal pour les inputs */
  #contact input.form-control,
  #contact textarea.form-control {
    background: rgba(255, 255, 255, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
  }

  /* ✅ Bouton */
  #contact .button-a {
    background: rgba(27, 209, 194, 0.8) !important;
    border: none !important;
    color: white !important;
  }
</style>
<style>
/* ✨ CONTAINERA - STYLE LIGHT GLASS IDENTIQUE AU MENU */
.containera {
    background: linear-gradient(135deg, 
        rgba(226, 250, 248, 0.4), 
        rgba(255, 255, 255, 0.6)
    );
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: 25px;
    padding: 30px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    margin: 0 auto;
    max-width: 1200px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

/* Effet de survol subtil pour tout le conteneur */
.containera:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 15px 40px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.6),
        0 0 25px rgba(27, 209, 194, 0.1);
    border-color: rgba(27, 209, 194, 0.2);
}

/* Léger effet de brillance au survol */
.containera::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.03), transparent);
    transition: 0.5s;
    opacity: 0;
    z-index: 1;
    border-radius: 25px;
}

.containera:hover::before {
    animation: containerShimmer 2s ease-in-out;
    opacity: 1;
}

@keyframes containerShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Style de l'iframe container (.man) */
.man {
    background: transparent;
    border-radius: 20px;
    padding: 20px;
    box-shadow: none;
    margin-top: 25px;
    border: 1px solid rgba(14, 119, 112, 0.05);
    transition: all 0.3s ease;
}

.man:hover {
    box-shadow: 0 8px 25px rgba(27, 209, 194, 0.08);
    border-color: rgba(27, 209, 194, 0.1);
}

/* Style de l'iframe */
#iframeSite {
    width: 100%;
    height: 550px;
    border: none;
    border-radius: 16px;
    background: white;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

#iframeSite:hover {
    transform: scale(1.01);
    box-shadow: 0 8px 25px rgba(27, 209, 194, 0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .containera {
        padding: 20px;
        border-radius: 20px;
    }
    #iframeSite {
        height: 400px;
    }
}
</style>
<style>
  /* ✅ Rétablir l'effet glass morphism sur le formulaire de contact */
  #contact .box-shadow-full {
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(20px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(200%) !important;
    border: 1px solid rgba(27, 209, 194, 0.3) !important;
    border-radius: 25px !important;
    padding: 3rem !important;
    box-shadow: 
      0 15px 35px rgba(27, 209, 194, 0.25),
      inset 0 1px 0 rgba(27, 209, 194, 0.4),
      inset 0 -1px 0 rgba(27, 209, 194, 0.2) !important;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  }

  /* ✅ Inputs transparents avec effet glass */
  #contact input.form-control,
  #contact textarea.form-control {
    background: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    border: 1.5px solid rgba(27, 209, 194, 0.4) !important;
    border-radius: 18px !important;
    color: rgba(27, 209, 194, 1) !important;
    padding: 18px 24px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    box-shadow: 
      0 8px 25px rgba(27, 209, 194, 0.12),
      inset 0 1px 0 rgba(27, 209, 194, 0.3) !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  }

  /* ✅ Bouton avec gradient teal */
  #contact .button-a {
    background: linear-gradient(135deg, 
      rgba(27, 209, 194, 0.9) 0%,
      rgba(27, 209, 194, 1) 50%,
      rgba(27, 209, 194, 0.8) 100%
    ) !important;
    backdrop-filter: blur(15px) !important;
    -webkit-backdrop-filter: blur(15px) !important;
    border: 2px solid rgba(27, 209, 194, 0.4) !important;
    border-radius: 50px !important;
    color: white !important;
    padding: 18px 40px !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    text-transform: uppercase !important;
    letter-spacing: 1.5px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    box-shadow: 
      0 15px 35px rgba(27, 209, 194, 0.4),
      inset 0 1px 0 rgba(255, 255, 255, 0.3),
      0 0 20px rgba(27, 209, 194, 0.3) !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
  }
</style>
<style>
  section#contact {
    backdrop-filter: blur(800px) saturate(2000%);
    -webkit-backdrop-filter: blur(800px) saturate(2000%);
    background-color: rgba(0, 0, 0, 0.9);
  }
</style>
<style>
  body{
      margin: 0;
    padding: 0;
    font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    background-attachment: fixed;
    min-height: 100vh;
    color: #2d3748;
    overflow-x: hidden;
    position: relative;
  }
/* ✨ ANIMATED TEAL BACKGROUND FROM MODIFNOTE.TXT */
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

/* Optional: Add a subtle grid texture for depth */
body::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        linear-gradient(rgba(14, 119, 112, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(14, 119, 112, 0.03) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none;
    z-index: -1;
}
#hero .hero-title {
    color: #ffffff; /* Keep white */
    text-shadow: 0 4px 20px rgba(0,0,0,0.5), 0 0 30px rgba(27, 209, 194, 0.6); /* Boost shadow */
}

#hero .hero-subtitle {
    color: #ffffff; /* Keep white */
    text-shadow: 0 2px 15px rgba(0,0,0,0.4); /* Boost shadow */
}

/* ✨ MENU GLASS CONTAINER - STYLE LIGHT GLASS COMME DANS MODIFNOTE.TXT */
.menu-glass-container {
    background: linear-gradient(135deg, 
        rgba(226, 250, 248, 0.4), 
        rgba(255, 255, 255, 0.6)
    );
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    margin-bottom: 25px;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

/* Effet de survol subtil */
.menu-glass-container:hover {
    transform: translateY(-3px);
    box-shadow: 
        0 12px 40px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.6),
        0 0 20px rgba(27, 209, 194, 0.1);
    border-color: rgba(27, 209, 194, 0.2);
}

/* Léger effet de brillance au survol */
.menu-glass-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.05), transparent);
    transition: 0.5s;
    opacity: 0;
    z-index: 1;
    border-radius: 20px;
}

.menu-glass-container:hover::before {
    animation: menuShimmer 1.5s ease-in-out;
    opacity: 1;
}

@keyframes menuShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Style de la liste */
.menu-glass {
    display: flex;
    gap: 20px;
    list-style: none;
    margin: 0;
    padding: 0;
    flex-wrap: wrap;
    justify-content: center;
    position: relative;
    z-index: 2;
}

/* Boutons — on garde ton style actuel, mais on l’ajuste légèrement pour harmoniser */
.menu-glass button {
    background: linear-gradient(135deg, 
        rgba(14, 119, 112, 0.95), 
        rgba(27, 209, 194, 0.7)
    );
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    color: white;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(14, 119, 112, 0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.menu-glass button:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 8px 25px rgba(27, 209, 194, 0.3);
}

.menu-glass button:active {
    transform: translateY(0) scale(0.98);
}

/* Responsive */
@media (max-width: 768px) {
    .menu-glass-container {
        padding: 15px;
        border-radius: 16px;
    }
    .menu-glass {
        gap: 12px;
    }
    .menu-glass button {
        padding: 10px 20px;
        font-size: 13px;
    }
}

</style>
<style>
/* ✨ PANNEAU CENTRAL DU HERO - STYLE LIGHT GLASS */
#hero {
    background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: 30px;
    padding: 80px 40px;
    margin: 60px auto 40px;
    max-width: 90%;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: 300px;
}
#hero .hero-content .container {
    background: linear-gradient(135deg, 
        rgba(0, 0, 0, 0.4), 
        rgba(255, 255, 255, 0.6)
    );
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: 25px;
    padding: 40px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    max-width: 80%;
    text-align: center;
    margin: 0 auto;
}

/* Effet de brillance au survol */
#hero .hero-content .container:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 15px 40px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.6),
        0 0 25px rgba(27, 209, 194, 0.1);
    border-color: rgba(27, 209, 194, 0.2);
}

/* Léger effet de lumière qui glisse */
#hero .hero-content .container::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.03), transparent);
    transition: 0.5s;
    opacity: 0;
    z-index: 1;
    border-radius: 25px;
}

#hero .hero-content .container:hover::before {
    animation: heroShimmer 2s ease-in-out;
    opacity: 1;
}

@keyframes heroShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>
</head>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const style = document.createElement("style");
  style.innerHTML = `
/* ✨ HERO SECTION - STYLE LIGHT GLASS COMME DANS MODIFNOTE.TXT */
#hero {
    background :linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%)
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    border: 1px solid rgba(14, 119, 112, 0.1);
    border-radius: 30px;
    padding: 80px 40px;
    margin: 60px auto 40px;
    max-width: 90%;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: 300px;
}

/* Effet de survol subtil */
#hero:hover {
    transform: translateY(-5px);
    box-shadow: 
        0 15px 40px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.6),
        0 0 25px rgba(27, 209, 194, 0.1);
    border-color: rgba(27, 209, 194, 0.2);
}

/* Léger effet de brillance au survol */
#hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.03), transparent);
    transition: 0.5s;
    opacity: 0;
    z-index: 1;
    border-radius: 30px;
}

#hero:hover::before {
    animation: heroShimmer 2s ease-in-out;
    opacity: 1;
}

@keyframes heroShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Contenu du hero — on garde ton style de texte */
#hero .hero-title {
    font-size: 3.5rem;
    font-weight: 900;
    color: #0E7770; /* On passe en teal foncé pour contraster sur fond clair */
    text-shadow: 0 4px 15px rgba(0,0,0,0.2);
    margin-bottom: 20px;
    letter-spacing: 1px;
    animation: glowText 2s ease-in-out infinite alternate;
}

@keyframes glowText {
    from { text-shadow: 0 0 10px rgba(27, 209, 194, 0.3); }
    to { text-shadow: 0 0 20px rgba(27, 209, 194, 0.5); }
}

#hero .hero-subtitle {
    font-size: 2rem;
    font-weight: 700;
    color: #0E7770; /* Teal foncé */
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 10px;
    opacity: 0;
    animation: fadeInUp 1s ease-out 0.5s forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    #hero {
        padding: 60px 20px;
        margin: 40px 15px;
        border-radius: 25px;
    }
    #hero .hero-title {
        font-size: 2.5rem;
    }
    #hero .hero-subtitle {
        font-size: 1.5rem;
    }
}
`});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const headerStyle = document.createElement("style");
  headerStyle.innerHTML = `
    /* ✨ HEADER - FLOATING TEAL GLASS NAVBAR */
    #header {
      background: rgba(255, 255, 255, 0.12) !important;;
      backdrop-filter: blur(20px) saturate(180%) !important;
      -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
      border-bottom: 1px solid rgba(27, 209, 194, 0.3) !important;
      box-shadow: 
        0 8px 32px rgba(27, 209, 194, 0.1),
        0 0 15px rgba(27, 209, 194, 0.2) !important;
      padding: 15px 0 !important;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      position: fixed !important;
      top: 0;
      width: 100%;
      z-index: 1000;
      animation: headerFloat 4s ease-in-out infinite;
    }

    @keyframes headerFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-3px); }
    }

    /* Logo styling */
    #header .logo img {
      height: 50px;
      transition: all 0.3s ease;
      filter: drop-shadow(0 4px 8px rgba(27, 209, 194, 0.4));
    }

    #header .logo img:hover {
      transform: scale(1.05);
      filter: drop-shadow(0 6px 12px rgba(27, 209, 194, 0.6));
    }

    /* Navbar links */
    #header .navbar ul {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
      gap: 30px;
    }

    #header .navbar a {
      color: #ffffff !important;
      font-weight: 600;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 50px;
      transition: all 0.3s ease;
      position: relative;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    #header .navbar a::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: linear-gradient(to right, transparent, rgba(27, 209, 194, 1), transparent);
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }

    #header .navbar a:hover {
      color: #1BD1C2 !important;
      transform: translateY(-3px);
      text-shadow: 0 4px 8px rgba(27, 209, 194, 0.4);
    }

    #header .navbar a:hover::before {
      width: 80%;
    }

    /* Language switcher */
    #languageSwitcher {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(27, 209, 194, 0.4);
      color: white;
      padding: 8px 16px;
      border-radius: 50px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    #languageSwitcher:hover {
      background: rgba(27, 209, 194, 0.3);
      border-color: rgba(27, 209, 194, 0.8);
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 992px) {
      #header .container {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      #header .navbar ul {
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
      }
    }
  `;
  document.head.appendChild(headerStyle);
});
</script>
</script>


<body>
  <header id="header" class="fixed-top">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="Accueil.php">
        <div class="about-img"> <img src="assets/img/alwah logo.png" class="img-fluid rounded b-shadow-a" alt="" style="border-color: black; border-radius:10px;"></div></a></h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto" href="#hero">Home</a></li>
          <li><a class="nav-link scrollto" href="#about">About me</a></li>
          <li><a class="nav-link scrollto" href="#contact">Contact</a></li>
          <li><a class="nav-link scrollto" href="Login.php">Sign Out</a></li>
        </ul>
      </nav>
      <select id="languageSwitcher">
           <option value="en">English</option>
           <option value="fr">Français</option>
           <option value="ar">العربية</option>
      </select>
    </div>
  </header>

  <div id="hero" class="hero route bg-image">
    <div class="overlay-itro"></div>
    <div class="hero-content display-table">
      <div class="table-cell">
        <div class="container">
          <h1 class="hero-title mb-4">[school's name]</h1>
          <p class="hero-subtitle"><span class="typed" data-typed-items="Welcome <?php echo htmlspecialchars($_SESSION['username']); ?> !"></span></p>
        </div>
      </div>
    </div>
  </div>

  <main id="main">

    <section id="about" class="about-mf sect-pt4 route">
      <div class="container">
        <div class="row">
          <div class="col-sm-12">
            <div class="box-shadow-full" style="border-radius: 25px; max-height: 124vh;">
              <div class="row">
                <div class="containera">
                    <div class="menu-glass-container">
                      <ul class="menu-glass">
                        <li><button onclick="changerSite('Profil.php')">PROFIL</button></li>
                        <li><button onclick="changerSite('Marks.php')">MARKS</button></li>
                        <li><button onclick="changerSite('Homeworks.html')">HOMEWORKS</button></li>
                        <li><button onclick="changerSite('slider.php')">NEWS</button></li>
                        <li><button onclick="changerSite('edt.php')">EDT</button></li>
                      </ul>
                    </div>
                   
                  <iframe id="iframeSite" src="" width="100%" height="550px" style="border: none; border-radius: 20px; margin-top: 20px;"></iframe>
                  
                </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <form action="Accueil.php" method="post">
      <section id="contact" class="paralax-mf footer-paralax bg-image sect-mt4 route" style="background-image: url(assets/img/alwah_out.jpg)">
        <div class="overlay-mf"></div>
        <div class="container">
          <div id="contact" class="box-shadow-full " style="border-radius: 25px;">
            <div class="row">
            
            <?php if ($success): ?>
                <div class="alert alert-info"><?php echo $success; ?></div>
              <?php endif; ?>

              <div class="col-md-6">
                <div class="title-box-2">
                  <h5 class="title-left">Send Message Us</h5>
                </div>
                <div class="col-md-12 mb-3">
                  <div class="form-group">
                    <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required>
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <div class="form-group">
                    <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required>
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <div class="form-group">
                    <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <textarea class="form-control" name="message" rows="5" placeholder="Message" required></textarea>
                  </div>
                  <br>
                </div>
                <div class="col-md-12 text-center">
                  <button type="submit" class="button button-a button-big button-rouded">Send Message</button>
                </div>
              </div>
              <div class="col-md-6">
                <div class="title-box-2 pt-4 pt-md-0">
                  <h5 class="title-left">Get in Touch</h5>
                </div>
                <div class="more-info">
                  <p class="lead">
                    We would love to hear from you! If you have any questions, inquiries,
                    or would like to discuss a potential collaboration, please don't hesitate
                    to get in touch with us. Fill out the form below and we'll get back to you as
                    soon as possible. Thank you for reaching out!
                  </p>
                  <ul class="list-ico">
                    <li><span class="bi bi-phone"></span> [number]</li>
                    <li><span class="bi bi-envelope"></span> [school's email]@gmail.com</li>
                  </ul>
                </div>
                <div class="socials">
                  <ul>
                    <li><a href="https://www.facebook.com/etablissementAlwah/  " target="_blank"><span class="ico-circle"><i class="bi bi-facebook"></i></span></a></li>
                    <li><a href="https://www.instagram.com/etablissementalwah/  " target="_blank"><span class="ico-circle"><i class="bi bi-instagram"></i></span></a></li>
                    <li><a href="https://alwah.ma/  " target="_blank"><span class="ico-circle"><i class="bi bi-google"></i></span></a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </form>  

  </main>

  <footer>
    <div class="container">
      <div class="copyright-box">
        <p class="copyright">&copy; Copyright <strong>[school's name]</strong> 2025-2026</p>
        <div class="credits">All Rights Reserved</div>
      </div>
    </div>      
  </footer>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12  "></script>
  <script src="assets/main.js"></script>





 
</body>
</html> 
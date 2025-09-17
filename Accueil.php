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
   .containera {
    background: rgba(27, 209, 194, 0.1); /* Teal très clair */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(27, 209, 194, 0.3);
    border-radius: 25px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
    max-width: 1200px;
  }
  .menu-glass-container {
  background: transparent;
  backdrop-filter: none;
  border: none;
  padding: 15px 20px;
  margin-bottom: 20px;
}
.man {
  background: white;
  border-radius: 16px;
  padding: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

#iframeSite {
  width: 100%;
  height: 480px;
  border: none;
  border-radius: 16px;
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
</head>
<script>
/*document.addEventListener("DOMContentLoaded", function() {
  const style = document.createElement("style");
  style.innerHTML = `
    /* Whole hero section as a big glassy blue box 
    #hero {
      position: relative;
      background: linear-gradient(135deg, #0E7770 50%, #1BD1C2 50%)
      backdrop-filter: blur(16px) saturate(160%);
      -webkit-backdrop-filter: blur(16px) saturate(160%);
      border: 1px solid rgba(255, 255, 255, 0.25);
      border-radius: 20px;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
      padding: 80px 20px;
      margin: 40px auto;
      max-width: 100%;
    }

    /* Hero content centered nicely 
    #hero .hero-content {
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      height: 100%;
    }

    /* School name styling 
    #hero .hero-title {
      font-size: 3rem;
      font-weight: 800;
      color: #ffffff;
      text-shadow: 0 3px 10px rgba(0,0,0,0.4);
      margin-bottom: 20px;
    }

    /* Welcome text styling 

  `;
  document.head.appendChild(style);
});*/
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
          <h1 class="hero-title mb-4">[school's nam</h1>
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
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Nettoyer l'ancien style si existe
    const oldStyle = document.getElementById("menu-glass-style");
    if (oldStyle) oldStyle.remove();

    // Créer le nouveau style
    const style = document.createElement("style");
    style.id = "menu-glass-style";
    style.innerHTML = `
        @keyframes floatMenu {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25%     { transform: translateY(2px) rotate(0.5deg); }
            50%     { transform: translateY(-2px) rotate(0deg); }
            75%     { transform: translateY(2px) rotate(-0.5deg); }
        }

        @keyframes shimmerTeal {
            0% { transform: translateX(-100%); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }

        .menu-glass-container {
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 0.2) 0%,
                rgba(27, 209, 194, 0.2) 25%,
                rgba(27, 209, 194, 0.3) 50%,
                rgba(27, 209, 194, 0.4) 75%,
                rgba(27, 209, 194, 0.5) 100%
            );
            backdrop-filter: blur(20px) saturate(200%);
            -webkit-backdrop-filter: blur(20px) saturate(200%);
            border: 1px solid rgba(27, 209, 194, 0.3);
            border-radius: 20px;
            padding: 15px 20px;
            box-shadow: 
                0 8px 32px rgba(27, 209, 194, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            animation: floatMenu 4s ease-in-out infinite;
        }

        .menu-glass-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.1), transparent);
            animation: shimmerTeal 3s infinite;
            z-index: 1;
        }

        .menu-glass {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: nowrap;
            position: relative;
            z-index: 2;
        }

        .menu-glass button {
            background: linear-gradient(135deg, 
                rgba(14, 119, 112, 0.95) 0%, 
                rgba(27, 209, 194, 0.7) 100%
            );
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(14, 119, 112, 0.25);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .menu-glass button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.4s ease;
        }

        .menu-glass button:hover::before {
            width: 300px;
            height: 300px;
        }

        .menu-glass button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(27, 209, 194, 0.4);
        }

        .menu-glass button:active {
            transform: translateY(0) scale(0.98);
        }

        @media (max-width: 768px) {
            .menu-glass-container {
                padding: 12px 15px;
                border-radius: 16px;
            }
            .menu-glass button {
                padding: 10px 18px;
                font-size: 13px;
            }
        }
    `;
    document.head.appendChild(style);
});
</script>



 
</body>
</html> 
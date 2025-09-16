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
  <link href="assets/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>

<body>
  <header id="header" class="fixed-top">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="Accueil.php">
        <div class="about-img"> <img src="assets/img/alwah logo.png" class="img-fluid rounded b-shadow-a" alt=""></div></a></h1>
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
                  <div class="diha">
                  <ul>
                    <li><button onclick="changerSite('Profil.php')">Profil</button></li>
                    <li><button onclick="changerSite('modif_note.php')">modif_note</button></li>
                    <li><button onclick="changerSite('slider.php')">News</button></li>
                    <li><button onclick="changerSite('modif_profiel.php')">modif.prof</button></li>
                    <li><button onclick="changerSite('modif_user.php')">Users</button></li>
                    <li><button onclick="changerSite('modif_news.php')">modif.news</button></li>

                  </ul>
                  </div>
                   <div class="man">
                  <iframe id="iframeSite" src="" width="100%" height="550px" style="border: none; border-radius: 20px;"></iframe>
                  </div>
                </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <form action="Accueil.php" method="post">
      <section id="contact" class="paralax-mf footer-paralax bg-image sect-mt4 route" style="background-image: url(assets/img/alwah\ out.jpg)">
        <div class="overlay-mf"></div>
        <div class="container">
          <div id="contact" class="box-shadow-full" style="border-radius: 25px;">
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
                    <li><span class="bi bi-phone"></span> [numero]</li>
                    <li><span class="bi bi-envelope"></span> [school's email]@gmail.com</li>
                  </ul>
                </div>
                <div class="socials">
                  <ul>
                    <li><a href="https://www.facebook.com/etablissementAlwah/" target="_blank"><span class="ico-circle"><i class="bi bi-facebook"></i></span></a></li>
                    <li><a href="https://www.instagram.com/etablissementalwah/" target="_blank"><span class="ico-circle"><i class="bi bi-instagram"></i></span></a></li>
                    <li><a href="https://alwah.ma/" target="_blank"><span class="ico-circle"><i class="bi bi-google"></i></span></a></li>
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
        <div class="credits">All Rights Resd</div>
      </div>
    </div>      
  </footer>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
  <script src="assets/main.js"></script>


<script>
function changerSite(url){
    document.getElementById('iframeSite').src = url;
}
</script>
 <script>
document.addEventListener("DOMContentLoaded", function() {
  const style = document.createElement("style");
  style.innerHTML = `
:root {
  --primary-color: rgba(14, 119, 112, 0.8);
  --primary-dark: rgba(14, 119, 112, 1);
  --primary-light: rgba(14, 119, 112, 0.3);
  --primary-gradient: linear-gradient(135deg, rgba(14,119,112,0.95) 0%, rgba(27,209,194,0.7) 100%);
  --secondary-gradient: linear-gradient(135deg, #0e7770 0%, #1bd1c2 100%);

  /* Glass-specific variables */
  --glass-bg: rgba(255, 255, 255, 0.65);
  --glass-border: rgba(255, 255, 255, 0.3);
  --backdrop-blur: blur(14px);
  --glass-box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);

  --border-radius-md: 20px;
  --transition-smooth: all 0.4s ease;
  --transition-elastic: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.diha {
  /* Applying glassmorphism using variables for consistency */
  background: var(--glass-bg);
  backdrop-filter: var(--backdrop-blur) saturate(160%);
  -webkit-backdrop-filter: var(--backdrop-blur) saturate(160%);
  border: 1px solid var(--glass-border);
  box-shadow: var(--glass-box-shadow);

  /* Preserving original layout and spacing */
  padding: 12px 20px;
  border-radius: 16px;
  margin: 20px auto;
  max-width: 100%;
}

.diha ul {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: nowrap;
}

.diha li {
  flex: 0 0 auto;
}

.diha button {
  padding: 10px 20px;
  border: none !important;
  border-radius: 12px !important;

  /* Glassy button background using original colors */
  background: linear-gradient(
    135deg,
    rgba(224, 247, 250, 0.4),
    rgba(178, 235, 242, 0.35)
  ) !important;
  color: #004d40 !important; /* dark teal text for readability */
  font-size: 15px !important;
  font-weight: 600 !important;
  cursor: pointer;
  transition: var(--transition-smooth);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.diha button:hover {
  background: linear-gradient(
    135deg,
    rgba(224, 247, 250, 0.6),
    rgba(178, 235, 242, 0.5)
  ) !important;
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

.diha button:active {
  transform: translateY(0);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}
  `;
  document.head.appendChild(style);
});
</script>



 <script>
// Advanced Teal Glass Morphism Contact Form with Signature Colors
document.addEventListener('DOMContentLoaded', function() {
    const contactSection = document.querySelector('#contact');
    const formInputs = document.querySelectorAll('#contact input.form-control, #contact textarea.form-control');
    const contactBox = document.querySelector('#contact .box-shadow-full');
    const submitButton = document.querySelector('#contact .button-a');
    const titles = document.querySelectorAll('#contact .title-left');
    const moreInfoBox = document.querySelector('#contact .more-info');
    const socialIcons = document.querySelectorAll('#contact .socials .ico-circle');
    const textElements = document.querySelectorAll('#contact .more-info p, #contact .list-ico li');

    // Add floating animation keyframes with teal glows
    const style = document.createElement('style');
    style.textContent = `
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-8px) rotate(0deg); }
            50% { transform: translateY(0px) rotate(0deg); }
            75% { transform: translateY(-4px) rotate(0deg); }
        }
        
        @keyframes tealGlow {
            0%, 100% { 
                box-shadow: 
                    0 4px 15px rgba(27, 209, 194, 0.2), 
                    0 0 20px rgba(27, 209, 194, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }
            50% { 
                box-shadow: 
                    0 8px 25px rgba(27, 209, 194, 0.4), 
                    0 0 30px rgba(27, 209, 194, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.4);
            }
        }
        
        @keyframes shimmerTeal {
            0% { background-position: -200px 0; }
            100% { background-position: 200px 0; }
        }
        
        @keyframes rippleTeal {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        
        .glass-shimmer-teal::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(27, 209, 194, 0.3), transparent);
            animation: shimmerTeal 4s infinite;
            border-radius: 30px;
        }
        
        .teal-gradient-bg {
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 0.15) 0%,
                rgba(27, 209, 194, 0.08) 25%,
                rgba(27, 209, 194, 0.12) 50%,
                rgba(27, 209, 194, 0.06) 75%,
                rgba(27, 209, 194, 0.1) 100%
            ) !important;
        }
        
        .teal-text-gradient {
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 1) 0%,
                rgba(27, 209, 194, 0.8) 50%,
                rgba(27, 209, 194, 1) 100%
            ) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }
    `;
    document.head.appendChild(style);

    // Enhanced glass effect for main container with teal gradient
    if (contactBox) {
        contactBox.style.cssText = `
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 0.12) 0%,
                rgba(27, 209, 194, 0.06) 25%,
                rgba(27, 209, 194, 0.1) 50%,
                rgba(27, 209, 194, 0.04) 75%,
                rgba(27, 209, 194, 0.08) 100%
            ) !important;
            backdrop-filter: blur(25px) saturate(200%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(200%) !important;
            border-radius: 30px !important;
            border: 2px solid rgba(27, 209, 194, 0.3) !important;
            box-shadow: 
                0 15px 35px rgba(27, 209, 194, 0.25),
                0 5px 15px rgba(27, 209, 194, 0.15),
                inset 0 1px 0 rgba(27, 209, 194, 0.4),
                inset 0 -1px 0 rgba(27, 209, 194, 0.2) !important;
            padding: 3rem !important;
            position: relative !important;
            overflow: hidden !important;
            animation: float 6s ease-in-out infinite !important;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        `;
        
        // Add teal shimmer effect
        contactBox.classList.add('glass-shimmer-teal');
        contactBox.style.position = 'relative';
        
        // Enhanced hover effect with teal glow
        contactBox.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02) rotateX(2deg)';
            this.style.boxShadow = `
                0 25px 50px rgba(27, 209, 194, 0.35),
                0 10px 25px rgba(27, 209, 194, 0.2),
                inset 0 1px 0 rgba(27, 209, 194, 0.5),
                inset 0 -1px 0 rgba(27, 209, 194, 0.3),
                0 0 40px rgba(27, 209, 194, 0.4)
            `;
            this.style.borderColor = 'rgba(27, 209, 194, 0.6)';
        });
        
        contactBox.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotateX(0deg)';
            this.style.boxShadow = `
                0 15px 35px rgba(27, 209, 194, 0.25),
                0 5px 15px rgba(27, 209, 194, 0.15),
                inset 0 1px 0 rgba(27, 209, 194, 0.4),
                inset 0 -1px 0 rgba(27, 209, 194, 0.2)
            `;
            this.style.borderColor = 'rgba(27, 209, 194, 0.3)';
        });
    }

    // Enhanced glass effect for form inputs with teal accents
    formInputs.forEach((input, index) => {
        input.style.cssText = `
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
            position: relative !important;
            overflow: hidden !important;
        `;
        
        // Style placeholders with teal
        input.style.setProperty('--placeholder-color', 'rgba(27, 209, 194, 0.7)');
        
        // Add staggered entrance animation
        setTimeout(() => {
            input.style.opacity = '1';
            input.style.transform = 'translateY(0)';
        }, index * 100);
        
        input.style.opacity = '0';
        input.style.transform = 'translateY(20px)';
        
        // Enhanced focus effects with teal glow
        input.addEventListener('focus', function() {
            this.style.cssText += `
                background: rgba(255, 255, 255, 0.2) !important;
                border-color: rgba(27, 209, 194, 0.9) !important;
                box-shadow: 
                    0 0 0 4px rgba(27, 209, 194, 0.2),
                    0 15px 35px rgba(27, 209, 194, 0.3),
                    inset 0 1px 0 rgba(27, 209, 194, 0.4),
                    0 0 30px rgba(27, 209, 194, 0.25) !important;
                transform: translateY(-2px) scale(1.02) !important;
                color: rgba(27, 209, 194, 1) !important;
            `;
            
            // Add teal ripple effect
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(27, 209, 194, 0.4);
                width: 100px;
                height: 100px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0);
                animation: rippleTeal 0.6s linear;
                pointer-events: none;
                z-index: 1;
            `;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
        
        input.addEventListener('blur', function() {
            this.style.cssText = `
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
                transform: translateY(0) scale(1) !important;
            `;
        });
        
        // Typing animation effect with teal glow
        input.addEventListener('input', function() {
            this.style.animation = 'tealGlow 0.5s ease-in-out';
            setTimeout(() => this.style.animation = '', 500);
        });
    });

    // Enhanced button with teal gradient
    if (submitButton) {
        submitButton.style.cssText = `
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
            position: relative !important;
            overflow: hidden !important;
            cursor: pointer !important;
        `;
        
        // Add glowing teal border animation
        submitButton.style.animation = 'tealGlow 2s ease-in-out infinite';
        
        submitButton.addEventListener('mouseenter', function() {
            this.style.cssText += `
                background: linear-gradient(135deg, 
                    rgba(27, 209, 194, 1) 0%,
                    rgba(32, 230, 210, 1) 50%,
                    rgba(27, 209, 194, 1) 100%
                ) !important;
                transform: translateY(-4px) scale(1.05) !important;
                box-shadow: 
                    0 25px 50px rgba(27, 209, 194, 0.6),
                    inset 0 1px 0 rgba(255, 255, 255, 0.4),
                    0 0 40px rgba(27, 209, 194, 0.6) !important;
                border-color: rgba(27, 209, 194, 0.8) !important;
            `;
        });
        
        submitButton.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.background = `linear-gradient(135deg, 
                rgba(27, 209, 194, 0.9) 0%,
                rgba(27, 209, 194, 1) 50%,
                rgba(27, 209, 194, 0.8) 100%
            )`;
            this.style.boxShadow = `
                0 15px 35px rgba(27, 209, 194, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3),
                0 0 20px rgba(27, 209, 194, 0.3)
            `;
            this.style.borderColor = 'rgba(27, 209, 194, 0.4)';
        });
        
        submitButton.addEventListener('click', function(e) {
            // Add teal click ripple effect
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.8);
                width: 10px;
                height: 10px;
                left: ${x - 5}px;
                top: ${y - 5}px;
                animation: rippleTeal 0.8s linear;
                pointer-events: none;
            `;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 800);
        });
    }

    // Enhanced titles with teal gradient text
    titles.forEach(title => {
        title.classList.add('teal-text-gradient');
        title.style.cssText += `
            font-weight: 800 !important;
            text-shadow: 0 2px 8px rgba(27, 209, 194, 0.3) !important;
            position: relative !important;
            font-size: 2.2rem !important;
        `;
    });

    // Enhanced more info box with teal gradient
    if (moreInfoBox) {
        moreInfoBox.style.cssText = `
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 0.1) 0%,
                rgba(27, 209, 194, 0.05) 50%,
                rgba(27, 209, 194, 0.08) 100%
            ) !important;
            backdrop-filter: blur(20px) saturate(200%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(200%) !important;
            border-radius: 25px !important;
            padding: 30px !important;
            border: 1.5px solid rgba(27, 209, 194, 0.3) !important;
            box-shadow: 
                0 10px 25px rgba(27, 209, 194, 0.2),
                inset 0 1px 0 rgba(27, 209, 194, 0.3) !important;
            margin-bottom: 25px !important;
            transition: all 0.3s ease !important;
        `;
        
        moreInfoBox.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = `
                0 20px 40px rgba(27, 209, 194, 0.3),
                inset 0 1px 0 rgba(27, 209, 194, 0.4),
                0 0 30px rgba(27, 209, 194, 0.2)
            `;
            this.style.borderColor = 'rgba(27, 209, 194, 0.5)';
        });
        
        moreInfoBox.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = `
                0 10px 25px rgba(27, 209, 194, 0.2),
                inset 0 1px 0 rgba(27, 209, 194, 0.3)
            `;
            this.style.borderColor = 'rgba(27, 209, 194, 0.3)';
        });
    }

    // Style text elements with teal color for visibility
    textElements.forEach(element => {
        element.style.cssText += `
            color: rgba(27, 209, 194, 0.9) !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
        `;
    });

    // Style list icons with teal
    const listIcons = document.querySelectorAll('#contact .list-ico li span');
    listIcons.forEach(icon => {
        icon.style.cssText += `
            color: rgba(27, 209, 194, 1) !important;
            filter: drop-shadow(0 2px 4px rgba(27, 209, 194, 0.3)) !important;
        `;
    });

    // Enhanced social icons with teal theme
    socialIcons.forEach((icon, index) => {
        icon.style.cssText = `
            background: linear-gradient(135deg, 
                rgba(27, 209, 194, 0.15) 0%,
                rgba(27, 209, 194, 0.1) 100%
            ) !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            border: 1.5px solid rgba(27, 209, 194, 0.4) !important;
            color: rgba(27, 209, 194, 1) !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            box-shadow: 0 8px 20px rgba(27, 209, 194, 0.15) !important;
        `;
        
        // Staggered entrance animation
        setTimeout(() => {
            icon.style.opacity = '1';
            icon.style.transform = 'translateY(0) scale(1)';
        }, index * 150);
        
        icon.style.opacity = '0';
        icon.style.transform = 'translateY(20px) scale(0.8)';
        
        icon.addEventListener('mouseenter', function() {
            this.style.cssText += `
                background: linear-gradient(135deg, 
                    rgba(27, 209, 194, 0.9) 0%,
                    rgba(27, 209, 194, 1) 100%
                ) !important;
                color: white !important;
                transform: translateY(-8px) scale(1.15) rotateY(10deg) !important;
                box-shadow: 0 20px 40px rgba(27, 209, 194, 0.5) !important;
                border-color: rgba(27, 209, 194, 0.8) !important;
            `;
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1) rotateY(0deg)';
            this.style.background = `linear-gradient(135deg, 
                rgba(27, 209, 194, 0.15) 0%,
                rgba(27, 209, 194, 0.1) 100%
            )`;
            this.style.color = 'rgba(27, 209, 194, 1)';
            this.style.boxShadow = '0 8px 20px rgba(27, 209, 194, 0.15)';
            this.style.borderColor = 'rgba(27, 209, 194, 0.4)';
        });
    });

    // Add mouse movement parallax effect with teal glow
    if (contactSection) {
        contactSection.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width;
            const y = (e.clientY - rect.top) / rect.height;
            
            const tiltX = (y - 0.5) * 5;
            const tiltY = (x - 0.5) * -5;
            
            if (contactBox) {
                contactBox.style.transform = `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
                // Add dynamic teal glow based on mouse position
                const glowIntensity = Math.abs(tiltX) + Math.abs(tiltY);
                contactBox.style.filter = `drop-shadow(0 0 ${20 + glowIntensity * 2}px rgba(27, 209, 194, ${0.3 + glowIntensity * 0.02}))`;
            }
        });
        
        contactSection.addEventListener('mouseleave', function() {
            if (contactBox) {
                contactBox.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
                contactBox.style.filter = 'drop-shadow(0 0 20px rgba(27, 209, 194, 0.3))';
            }
        });
    }

    console.log('🌊 Advanced Teal Glass Morphism Contact Form loaded successfully!');
});
</script>
</body>
</html>


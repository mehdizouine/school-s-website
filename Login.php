<?php
session_start();
include('db.php');
require_once 'authorisation.php'; // CSRF + fonctions auth

$message = "";
$message_ip = "";
$message_iu = "";

// 🔒 Protection brute-force
$max_attempts = 2;
$lockout_time = 30; // 5 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

$diff = time() - ($_SESSION['last_attempt'] ?? 0);
if ($_SESSION['login_attempts'] >= $max_attempts && $diff < $lockout_time) {
    die("<div class='alert alert-danger'>⛔ Trop de tentatives. Réessayez dans " . ($lockout_time - $diff) . " secondes.</div>");
}

// Vérification du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    // ✅ Vérification CSRF
    validate_csrf();

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = "<div class='alert alert-warning'>⚠ Username and Password are required!</div>";
    } else {
        $sql = "SELECT * FROM login WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (!empty($user['Password_hash']) && password_verify($password, $user['Password_hash'])) {
                // ✅ Reset brute-force
                $_SESSION['login_attempts'] = 0;

                // Set session variables
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['role'];

                // Redirection selon rôle
                switch($user['role']) {
                    case 'eleve':
                        header("Location: Accueil.php");
                        exit();
                    case 'prof':
                        header("Location: prof.php");
                        exit();
                    case 'admin':
                        header("Location: admin.php");
                        exit();
                    default:
                        $message = "<div class='alert alert-danger'>❌ Rôle inconnu !</div>";
                }

            } else {
                // Mauvais mot de passe
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                $message_ip = "<div class='alert alert-danger'>❌ Identifiants incorrects</div>";
            }
        } else {
            // Nom d'utilisateur introuvable
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $message_iu = "<div class='alert alert-danger'>❌ Identifiants incorrects</div>";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!-- Affichage des messages -->
<?php
if (!empty($message)) echo $message;
if (!empty($message_ip)) echo $message_ip;
if (!empty($message_iu)) echo $message_iu;
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="assets/img/alwah logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoginPage</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
     * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: url(assets/img/alwah_out.jpg) no-repeat;
      background-size: cover;
      background-position: center;
    }
    body:before {
      content: "";
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #00000085;
      z-index: -1;
    }
    .wrapper {
      width: 420px;
      background: transparent;
      border: 2px solid rgba(255, 255, 255, .2);
      backdrop-filter: blur(3px);
      color: #fff;
      border-radius: 12px;
      padding: 30px 40px;
    }
    .wrapper h1 {
      font-size: 36px;
      text-align: center;
    }
    .wrapper .input-box {
      position: relative;
      width: 100%;
      height: 50px;
      margin: 30px 0;
    }
    .input-box input {
      width: 100%;
      height: 100%;
      background: transparent;
      border: none;
      outline: none;
      border: 2px solid rgba(255, 255, 255, .2);
      border-radius: 40px;
      font-size: 16px;
      color: #fff;
      padding: 20px 45px 20px 20px;
      animation: borderGlow 2s infinite alternate;
    }
    @keyframes borderGlow {
      0% {
        border-color: #3498db;
        box-shadow: 0 0 5px #3498db;
      }
      100% {
        border-color: #00cc99;
        box-shadow: 0 0 10px #00cc99;
      }
    }
    .input-box input::placeholder {
      color: #fff;
    }
    .input-box i {
      position: absolute;
      right: 20px;
      top: 30%;
      transform: translate(-50%);
      font-size: 20px;
      cursor: pointer;
      transition: transform 0.3s ease-in-out;
    }
    .wrapper .btn {
      width: 100%;
      height: 45px;
      background: #fff;
      border: none;
      outline: none;
      border-radius: 40px;
      box-shadow: 0 0 10px rgba(0, 0, 0, .1);
      cursor: pointer;
      font-size: 16px;
      color: #333;
      font-weight: 600;
      transition: 0.3s ease;
    }
    .wrapper .btn:hover {
      filter: brightness(105%);
      transform: translateX(2px);
    }
    input:hover{
      transform: scale(1.01);
    }
    .aa{
      text-align: center;
      background-color: transparent;
    }
    .bb{
      text-align: center;
      background-color: transparent;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <form action="Login.php" method="post">
      <?= csrf_field() ?>
      <h1>Login</h1>
      <div class="input-box">
        <input type="text" name="username" placeholder="Username" required>
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="password" id="password" name="password" placeholder="Password" required>
        <i class='bx bx-hide' id="togglePassword"></i>
      </div>
      <input type="submit" name="login" class="btn">
      <?php if ($message_ip): ?>
        <div class="aa">
          <?php echo $message_ip; ?>
        </div>
      <?php endif; ?>

      <?php if ($message_iu): ?>
        <div class="bb">
          <?php echo $message_iu; ?>
        </div>
      <?php endif; ?>

    </form>
    <?php if ($message): ?>
        <div>
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
  </div>
  <script>
    const togglePassword = document.querySelector("#togglePassword");
    const passwordInput = document.querySelector("#password");
    togglePassword.addEventListener("click", function () {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("bx-hide");
      this.classList.toggle("bx-show");
    });
  </script>
</body>
</html>
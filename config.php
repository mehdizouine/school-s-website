<?php
// config.php

// Paramètres du site
define('SITE_NAME', 'Mon Ecole');
define('ADMIN_EMAIL', 'mehdizouine2007@gmail.com'); // email utilisé pour envoyer la newsletter
define('FROM_NAME', 'Admin Ecole');

// Paramètres PHPMailer (SMTP Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'mehdizouine2007@gmail.com'); 
define('SMTP_PASS', 'jhvdrzlomcidvfwo'); // ton code 16 chiffres (ne pas mettre sur GitHub)
define('SMTP_SECURE', 'tls'); // tls ou ssl
?>

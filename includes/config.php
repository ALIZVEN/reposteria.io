<?php
// includes/config.php
// Configuración principal del sistema

// Base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ingccs_reposrurik');

// Configuración de correo para pedidos
define('EMAIL_PEDIDOS', 'Danieldavidrivasizquierdo11@gmail.com');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'Danieldavidrivasizquierdo11@gmail.com');
define('SMTP_PASS', 'kpri hfun tnqw posu'); 
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Configuración de la tienda
define('SITE_NAME', 'Dulce Repostería');
define('SITE_URL', 'http://localhost/reposteria/');

// Zona horaria
date_default_timezone_set('America/Caracas'); // Ajusta según tu ubicación
?>
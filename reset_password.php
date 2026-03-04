<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h2>🔄 Restableciendo contraseña de admin</h2>";

$db = Database::getInstance()->getConnection();

// Contraseña nueva: admin123
$nueva_password = password_hash('admin123', PASSWORD_DEFAULT);

// Actualizar o insertar admin
$check = $db->query("SELECT id FROM administradores WHERE usuario = 'admin'");

if ($check->num_rows > 0) {
    // Actualizar existente
    $sql = "UPDATE administradores SET password = '$nueva_password' WHERE usuario = 'admin'";
    if ($db->query($sql)) {
        echo "<p style='color:green'>✅ Contraseña actualizada correctamente para usuario 'admin'</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . $db->error . "</p>";
    }
} else {
    // Crear nuevo
    $sql = "INSERT INTO administradores (usuario, password, nombre, email, rol) 
            VALUES ('admin', '$nueva_password', 'Administrador', 'admin@reposteria.com', 'admin')";
    if ($db->query($sql)) {
        echo "<p style='color:green'>✅ Admin creado correctamente</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . $db->error . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Usuario:</strong> admin</p>";
echo "<p><strong>Contraseña:</strong> admin123</p>";
echo "<p><a href='admin/login.php'>➡️ Ir al login</a></p>";
?>
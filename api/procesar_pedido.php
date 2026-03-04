<?php
header('Content-Type: application/json');

// Capturar TODOS los errores
function errorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'success' => false,
        'error_type' => 'PHP Error',
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    echo json_encode($error);
    exit;
}
set_error_handler('errorHandler');

function exceptionHandler($exception) {
    $error = [
        'success' => false,
        'error_type' => 'Exception',
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ];
    echo json_encode($error);
    exit;
}
set_exception_handler('exceptionHandler');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error_type' => 'Fatal Error',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// BUSCAR PHPMailer
// ============================================
$phpmailer_found = false;
$debug_phpmailer = "";

$current_dir = __DIR__;
$root_dir = dirname($current_dir);

$possible_paths = [
    $root_dir . '/PHPMailer/src/Exception.php',
    $current_dir . '/../PHPMailer/src/Exception.php',
    $current_dir . '/PHPMailer/src/Exception.php',
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        require_once str_replace('Exception.php', 'PHPMailer.php', $path);
        require_once str_replace('Exception.php', 'SMTP.php', $path);
        $phpmailer_found = true;
        $debug_phpmailer = "✓ PHPMailer encontrado en: $path";
        break;
    }
}

if (!$phpmailer_found) {
    $debug_phpmailer = "✗ PHPMailer NO encontrado";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../includes/functions.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }
    
    // Obtener datos POST
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    // Validar campos
    if (empty($nombre) || empty($telefono) || empty($email) || empty($direccion)) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos los campos son obligatorios'
        ]);
        exit;
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email no válido'
        ]);
        exit;
    }
    
    // Obtener items del carrito
    $items = getCarritoItems();
    
    if (empty($items['items'])) {
        echo json_encode([
            'success' => false,
            'message' => 'El carrito está vacío'
        ]);
        exit;
    }
    
    // Obtener el carrito actual
    $carrito_id = getCarritoId();
    
    if (!$carrito_id) {
        $session_id = session_id();
        $stmt = $db->prepare("INSERT INTO carritos (session_id, fecha_creacion, activo) VALUES (?, NOW(), 1)");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $carrito_id = $db->insert_id;
    }
    
    // Guardar el pedido
    $db->begin_transaction();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO pedidos 
            (carrito_id, nombre_cliente, telefono, email, direccion, notas, total, estado, fecha_pedido) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
        ");
        
        $total = $items['total'];
        $stmt->bind_param("isssssd", $carrito_id, $nombre, $telefono, $email, $direccion, $notas, $total);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el pedido: " . $stmt->error);
        }
        
        $pedido_id = $db->insert_id;
        
        // Limpiar carrito
        $stmt = $db->prepare("DELETE FROM items_carrito WHERE carrito_id = ?");
        $stmt->bind_param("i", $carrito_id);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE carritos SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $carrito_id);
        $stmt->execute();
        
        $db->commit();
        unset($_SESSION['carrito_id']);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
    // ENVIAR EMAIL
    $email_enviado = false;
    $debug_email = "";
    
    if ($phpmailer_found && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            
            // Configuración adicional
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Remitente y destinatario
            $mail->setFrom(SMTP_USER, 'Dulce Repostería');
            $mail->addAddress(EMAIL_PEDIDOS);
            $mail->addReplyTo($email, $nombre);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = "🍰 NUEVO PEDIDO #{$pedido_id} - Dulce Repostería";

// ============================================
// MENSAJE CORREO
// ============================================
$mensaje = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background: #ff8ba7; color: white; padding: 20px; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #ff8ba7; color: white; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎂 NUEVO PEDIDO #{$pedido_id}</h1>
        </div>
        
        <h2>Datos del Cliente</h2>
        <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
        <p><strong>Teléfono:</strong> " . htmlspecialchars($telefono) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Dirección:</strong> " . htmlspecialchars($direccion) . "</p>
        
        <h2>Productos</h2>
        <table>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                
                <th>Subtotal</th>
            </tr>";


error_log("Estructura de items en procesar_pedido: " . print_r($items, true));


if (isset($items['items']) && is_array($items['items'])) {
    foreach ($items['items'] as $item) {
        $nombre_producto = isset($item['nombre']) ? $item['nombre'] : 'Producto';
        $cantidad = isset($item['cantidad']) ? $item['cantidad'] : 1;
        
        $subtotal = isset($item['subtotal']) ? $item['subtotal'] : ($cantidad * $precio);
        
        $mensaje .= "
            <tr>
                <td>" . htmlspecialchars($nombre_producto) . "</td>
                <td style='text-align: center;'>" . (int)$cantidad . "</td>
                
                <td>$" . number_format($subtotal, 0, ',', '.') . "</td>
            </tr>";
    }
}

$total_pedido = isset($items['total']) ? $items['total'] : 0;
$mensaje .= "
        </table>
        
        <h3 style='text-align: right;'>TOTAL: $" . number_format($total_pedido, 0, ',', '.') . "</h3>";

if (!empty($notas)) {
    $mensaje .= "<p><strong>Notas:</strong> " . htmlspecialchars($notas) . "</p>";
}

$mensaje .= "
        <p style='text-align: center; color: #666; margin-top: 30px;'>
            Dulce Repostería - Endulzando tus momentos especiales
        </p>
    </div>
</body>
</html>";

// texto plano
$texto_plano = "NUEVO PEDIDO #{$pedido_id}\n";
$texto_plano .= "==================\n\n";
$texto_plano .= "DATOS DEL CLIENTE:\n";
$texto_plano .= "Nombre: {$nombre}\n";
$texto_plano .= "Teléfono: {$telefono}\n";
$texto_plano .= "Email: {$email}\n";
$texto_plano .= "Dirección: {$direccion}\n\n";
$texto_plano .= "PRODUCTOS:\n";

if (isset($items['items']) && is_array($items['items'])) {
    foreach ($items['items'] as $item) {
        $nombre_producto = isset($item['nombre']) ? $item['nombre'] : 'Producto';
        $cantidad = isset($item['cantidad']) ? $item['cantidad'] : 1;
        $precio = isset($item['precio']) ? $item['precio'] : 0;
        $subtotal = isset($item['subtotal']) ? $item['subtotal'] : ($cantidad * $precio);
        
        $texto_plano .= "- {$nombre_producto} x {$cantidad} = $" . number_format($subtotal, 0, ',', '.') . "\n";
    }
}

$texto_plano .= "\nTOTAL: $" . number_format($total_pedido, 0, ',', '.') . "\n";

if (!empty($notas)) {
    $texto_plano .= "\nNOTAS: {$notas}\n";
}

$mail->Body = $mensaje;
$mail->AltBody = $texto_plano;
          
            
            $mail->send();
            $email_enviado = true;
            $debug_email = "✅ Email enviado correctamente";
            
        } catch (Exception $e) {
            $debug_email = "❌ Error PHPMailer: " . $mail->ErrorInfo;
        }
    } else {
        $debug_email = $debug_phpmailer;
    }
    
    echo json_encode([
        'success' => true,
        'message' => '¡Pedido recibido! Te contactaremos pronto.',
        'pedido_id' => $pedido_id,
        'email_enviado' => $email_enviado,
        'debug' => $debug_email
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
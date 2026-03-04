<?php
// Verificar si la sesión ya está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Funciones para carrito
function getCarritoId() {
    // Asegurar que la sesión está iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $db = Database::getInstance()->getConnection();
    
    if (!isset($_SESSION['carrito_id'])) {
        $sesion_id = session_id();
        
        $stmt = $db->prepare("INSERT INTO carritos (sesion_id) VALUES (?)");
        $stmt->bind_param("s", $sesion_id);
        $stmt->execute();
        
        $carrito_id = $db->insert_id;
        $_SESSION['carrito_id'] = $carrito_id;
        
        return $carrito_id;
    }
    return $_SESSION['carrito_id'];
}

// ... resto de las funciones igual ...

function getCarritoItems() {
    $db = Database::getInstance()->getConnection();
    $carrito_id = getCarritoId();
    
    if (!$carrito_id) {
        return ['items' => [], 'total' => 0];
    }
    
    // Usar items_carrito en lugar de carrito_items
    $stmt = $db->prepare("
        SELECT ic.*, p.nombre, p.precio_base, p.imagen 
        FROM items_carrito ic
        JOIN productos p ON ic.producto_id = p.id
        WHERE ic.carrito_id = ?
    ");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Calcular precio con extras
        $precio = $row['precio_base'];
        if ($row['extra_chocolate']) $precio += 2.00;
        if ($row['extra_relleno']) $precio += 3.00;
        if ($row['extra_decoracion']) $precio += 2.50;
        
        $subtotal = $precio * $row['cantidad'];
        
        $items[] = [
            'id' => $row['id'],
            'producto_id' => $row['producto_id'],
            'nombre' => $row['nombre'],
            'cantidad' => $row['cantidad'],
            'precio' => $precio,
            'subtotal' => $subtotal,
            'imagen' => $row['imagen'],
            'personalizacion' => $row['personalizacion']
        ];
        
        $total += $subtotal;
    }
    
    return ['items' => $items, 'total' => $total];
}

function getCategorias() {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre, tipo_evento");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductosDestacados($limite = 8) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, c.nombre as categoria_nombre, c.tipo_evento
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.destacado = 1 AND p.activo = 1
        LIMIT ?
    ");
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function enviarEmailPedido($pedido_id, $datos_cliente) {
    // Verificar que PHPMailer existe
    $phpmailer_path = __DIR__ . '/PHPMailer/PHPMailer.php';
    
    if (!file_exists($phpmailer_path)) {
        error_log("PHPMailer no encontrado en: " . $phpmailer_path);
        return false;
    }
    
    require_once $phpmailer_path;
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    require_once __DIR__ . '/PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Remitente y destinatarios
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($datos_cliente['email'], $datos_cliente['nombre']);
        $mail->addAddress(SMTP_FROM, 'Administrador'); // Copia al administrador
        
        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Confirmación de Pedido #' . $pedido_id;
        
        // Obtener items del carrito
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT ic.*, p.nombre, p.precio_base 
            FROM items_carrito ic
            JOIN productos p ON ic.producto_id = p.id
            WHERE ic.carrito_id = (SELECT carrito_id FROM pedidos WHERE id = ?)
        ");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $items_email = $stmt->get_result();
        
        // Construir el cuerpo del email
        $cuerpo = "<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: auto; }
                .header { background: #ff8ba7; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
                .total { font-size: 18px; font-weight: bold; color: #ff8ba7; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>¡Gracias por tu pedido!</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$datos_cliente['nombre']}</strong>,</p>
                    <p>Hemos recibido tu pedido #<strong>{$pedido_id}</strong> correctamente.</p>
                    
                    <h3>Detalles del pedido:</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>";
        
        $total_email = 0;
        while ($item = $items_email->fetch_assoc()) {
            $subtotal = $item['precio_base'] * $item['cantidad'];
            $total_email += $subtotal;
            
            $cuerpo .= "<tr>
                <td>{$item['nombre']}</td>
                <td>{$item['cantidad']}</td>
                <td>$" . number_format($item['precio_base'], 1) . "</td>
                <td>$" . number_format($subtotal, 2) . "</td>
            </tr>";
            
            if (!empty($item['personalizacion'])) {
                $cuerpo .= "<tr><td colspan='4'><small><em>📝 Personalización: {$item['personalizacion']}</em></small></td></tr>";
            }
            if (!empty($item['alergias'])) {
                $cuerpo .= "<tr><td colspan='4'><small><em>⚠️ Alergias: {$item['alergias']}</em></small></td></tr>";
            }
        }
        
        $cuerpo .= "</tbody>
                    </table>
                    
                    <p class='total'>Total: $" . number_format($total_email, 2) . "</p>
                    
                    <p><strong>Método de pago:</strong> " . ucfirst($datos_cliente['metodo_pago']) . "</p>
                    
                    <p>Te contactaremos pronto para confirmar la entrega.</p>
                    
                    <hr>
                    
                    <p style='color: #666; font-size: 12px;'>
                        Dulce Repostería<br>
                        Tel: " . (defined('TIENDA_TELEFONO') ? TIENDA_TELEFONO : 'Contactar') . "<br>
                        Email: " . (defined('TIENDA_EMAIL') ? TIENDA_EMAIL : SMTP_FROM) . "
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $cuerpo;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $cuerpo));
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        return false;
    }
}
// Función para depuración (opcional)
function debug_log($mensaje) {
    $log_file = __DIR__ . '/../debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensaje\n", FILE_APPEND);
}
?>
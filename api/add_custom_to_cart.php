<?php
// api/add_custom_to_cart.php
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Obtener datos POST
    $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    
    if (!$producto_id) {
        throw new Exception('Producto no válido');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Obtener o crear carrito
    $carrito_id = getCarritoId();
    
    if (!$carrito_id) {
        $session_id = session_id();
        $stmt = $db->prepare("INSERT INTO carritos (sesion_id, fecha_creacion, activo) VALUES (?, NOW(), 1)");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $carrito_id = $db->insert_id;
        $_SESSION['carrito_id'] = $carrito_id;
    }
    
    // Obtener datos de personalización
    $personalizacion = isset($_POST['personalizacion']) ? trim($_POST['personalizacion']) : '';
    $alergias = isset($_POST['alergias']) ? trim($_POST['alergias']) : '';
    $extra_chocolate = isset($_POST['extra_chocolate']) ? 1 : 0;
    $extra_relleno = isset($_POST['extra_relleno']) ? 1 : 0;
    $extra_decoracion = isset($_POST['extra_decoracion']) ? 1 : 0;
    
    // Procesar opciones adicionales si existen
    $opciones_adicionales = isset($_POST['opciones_adicionales']) ? json_encode($_POST['opciones_adicionales']) : null;
    
    // Verificar si el producto ya está en el carrito
    $stmt = $db->prepare("SELECT id, cantidad FROM items_carrito WHERE carrito_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $carrito_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Actualizar cantidad
        $item = $result->fetch_assoc();
        $nueva_cantidad = $item['cantidad'] + $cantidad;
        
        $stmt = $db->prepare("UPDATE items_carrito SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $item['id']);
        $stmt->execute();
        
        $message = 'Cantidad actualizada';
    } else {
        // Insertar nuevo item con personalización
        $stmt = $db->prepare("INSERT INTO items_carrito 
            (carrito_id, producto_id, cantidad, personalizacion, alergias, extra_chocolate, extra_relleno, extra_decoracion, opciones_adicionales) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssiis", 
            $carrito_id, 
            $producto_id, 
            $cantidad, 
            $personalizacion, 
            $alergias,
            $extra_chocolate,
            $extra_relleno,
            $extra_decoracion,
            $opciones_adicionales
        );
        $stmt->execute();
        
        $message = 'Producto agregado al carrito';
    }
    
    // Obtener el total de items para actualizar el contador
    $stmt = $db->prepare("SELECT SUM(cantidad) as total FROM items_carrito WHERE carrito_id = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc();
    $cart_count = $total['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => (int)$cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$carrito_id = getCarritoId();
$db = Database::getInstance()->getConnection();

$producto_id = $_POST['producto_id'] ?? 0;
$cantidad = $_POST['cantidad'] ?? 1;

if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'Producto no válido']);
    exit;
}

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
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cantidad actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
} else {
    // Insertar nuevo item
    $stmt = $db->prepare("INSERT INTO items_carrito (carrito_id, producto_id, cantidad) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $carrito_id, $producto_id, $cantidad);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar']);
    }
}
?>
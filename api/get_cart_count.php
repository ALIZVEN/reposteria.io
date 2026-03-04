<?php
// api/get_cart_count.php
header('Content-Type: application/json');
session_start();
require_once '../includes/functions.php';

try {
    $carrito_id = getCarritoId();
    
    if (!$carrito_id) {
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT SUM(cantidad) as total FROM items_carrito WHERE carrito_id = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'count' => (int)($result['total'] ?? 0)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>
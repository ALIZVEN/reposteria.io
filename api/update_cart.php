<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

$db = Database::getInstance()->getConnection();
$item_id = $_POST['item_id'];
$cantidad = $_POST['cantidad'];

$stmt = $db->prepare("UPDATE items_carrito SET cantidad = ? WHERE id = ?");
$stmt->bind_param("ii", $cantidad, $item_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
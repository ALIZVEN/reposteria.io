<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

$db = Database::getInstance()->getConnection();
$item_id = $_POST['item_id'];

$stmt = $db->prepare("DELETE FROM items_carrito WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
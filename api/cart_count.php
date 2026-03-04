<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$items = getCarritoItems();
echo json_encode(['count' => count($items['items'])]);
?>
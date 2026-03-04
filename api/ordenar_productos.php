<?php
require_once '../includes/functions.php';

$categoria = $_GET['categoria'] ?? '';
$tipo_evento = $_GET['tipo'] ?? '';
$orden = $_GET['orden'] ?? 'nombre_asc';

$db = Database::getInstance()->getConnection();

$query = "SELECT p.*, c.nombre as categoria_nombre, c.tipo_evento 
          FROM productos p
          JOIN categorias c ON p.categoria_id = c.id
          WHERE p.activo = 1";

$params = [];
$types = "";

if ($categoria) {
    $query .= " AND c.nombre = ?";
    $params[] = $categoria;
    $types .= "s";
}

if ($tipo_evento) {
    $query .= " AND c.tipo_evento = ?";
    $params[] = $tipo_evento;
    $types .= "s";
}

// Aplicar ordenamiento
switch ($orden) {
    case 'nombre_asc':
        $query .= " ORDER BY p.nombre ASC";
        break;
    case 'nombre_desc':
        $query .= " ORDER BY p.nombre DESC";
        break;
    case 'precio_asc':
        $query .= " ORDER BY p.precio_base ASC";
        break;
    case 'precio_desc':
        $query .= " ORDER BY p.precio_base DESC";
        break;
    default:
        $query .= " ORDER BY p.destacado DESC, p.nombre ASC";
}

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($productos as $producto) {
    ?>
    <div class="col-md-3 col-6 producto-item">
        <div class="card producto-card h-100">
            <img src="../uploads/productos/<?php echo $producto['imagen'] ?: 'default.jpg'; ?>" 
                 class="card-img-top" alt="<?php echo $producto['nombre']; ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                <p class="card-text text-muted small"><?php echo $producto['descripcion_corta']; ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h5 mb-0 text-primary">
                        $<?php echo number_format($producto['precio_base'], 2); ?>
                    </span>
                    <div>
                        <a href="personalizar.php?id=<?php echo $producto['id']; ?>" 
                           class="btn btn-sm btn-warning" title="Personalizar">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <button class="btn btn-sm btn-primary add-to-cart" 
                                data-id="<?php echo $producto['id']; ?>"
                                title="Agregar al carrito">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
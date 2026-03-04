<?php
require_once 'includes/functions.php';

$categoria = $_GET['categoria'] ?? '';
$tipo_evento = $_GET['tipo'] ?? '';

$db = Database::getInstance()->getConnection();

// Construir query
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

$query .= " ORDER BY p.destacado DESC, p.nombre ASC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Título de la página
if ($categoria && $tipo_evento) {
    $titulo = ucfirst($categoria) . " para " . ucfirst($tipo_evento);
} elseif ($categoria) {
    $titulo = ucfirst($categoria);
} else {
    $titulo = "Todos los Productos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - Dulce Repostería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <!-- Migas de pan -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <?php if ($categoria): ?>
                <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
                <li class="breadcrumb-item active"><?php echo ucfirst($categoria); ?></li>
                <?php if ($tipo_evento): ?>
                    <li class="breadcrumb-item active"><?php echo ucfirst($tipo_evento); ?></li>
                <?php endif; ?>
            <?php else: ?>
                <li class="breadcrumb-item active">Productos</li>
            <?php endif; ?>
        </ol>
    </nav>
    
    <h1 class="mb-4"><?php echo $titulo; ?></h1>
    
    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-3">
            <select class="form-select" id="filtro-categoria">
                <option value="">Todas las categorías</option>
                <option value="tortas" <?php echo $categoria == 'tortas' ? 'selected' : ''; ?>>Tortas</option>
                <option value="cupcakes" <?php echo $categoria == 'cupcakes' ? 'selected' : ''; ?>>Cupcakes</option>
                <option value="gelatinas" <?php echo $categoria == 'gelatinas' ? 'selected' : ''; ?>>Gelatinas</option>
                <option value="quesillos" <?php echo $categoria == 'quesillos' ? 'selected' : ''; ?>>Quesillos</option>
                <option value="snacks" <?php echo $categoria == 'snacks' ? 'selected' : ''; ?>>Snacks</option>
                <option value="dulces" <?php echo $categoria == 'dulces' ? 'selected' : ''; ?>>Dulces</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filtro-tipo" <?php echo !$categoria ? 'disabled' : ''; ?>>
                <option value="">Todos los tipos</option>
                <option value="niños" <?php echo $tipo_evento == 'niños' ? 'selected' : ''; ?>>Niños</option>
                <option value="adultos" <?php echo $tipo_evento == 'adultos' ? 'selected' : ''; ?>>Adultos</option>
                <option value="bautizo" <?php echo $tipo_evento == 'bautizo' ? 'selected' : ''; ?>>Bautizos/Comunión</option>
                <option value="clasica" <?php echo $tipo_evento == 'clasica' ? 'selected' : ''; ?>>Clásicas</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filtro-orden">
                <option value="nombre_asc">Nombre (A-Z)</option>
                <option value="nombre_desc">Nombre (Z-A)</option>
                <option value="precio_asc">Precio (menor a mayor)</option>
                <option value="precio_desc">Precio (mayor a menor)</option>
            </select>
        </div>
    </div>
    
    <!-- Grid de productos -->
    <div class="row g-4" id="productos-container">
        <?php if (empty($productos)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-cake fa-3x text-muted mb-3"></i>
                <h3>No hay productos disponibles</h3>
                <p>Pronto tendremos nuevas delicias para ti</p>
                <a href="index.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        <?php else: ?>
            <?php foreach ($productos as $producto): ?>
            <div class="col-md-3 col-6 producto-item">
                <div class="card producto-card h-100">
                    <img src="uploads/productos/<?php echo $producto['imagen'] ?: 'default.jpg'; ?>" 
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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Offcanvas Carrito -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="carritoOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-shopping-cart"></i> Tu Carrito
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="carrito-contenido">
            <p class="text-center text-muted">Cargando carrito...</p>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <script src="assets/js/config.js"></script>
<script src="assets/js/carrito.js"></script>

<script>
$(document).ready(function() {
    // Filtros
    $('#filtro-categoria').change(function() {
        var categoria = $(this).val();
        var tipo = $('#filtro-tipo').val();
        
        if (categoria) {
            $('#filtro-tipo').prop('disabled', false);
            window.location.href = 'productos.php?categoria=' + categoria + (tipo ? '&tipo=' + tipo : '');
        } else {
            $('#filtro-tipo').prop('disabled', true);
            window.location.href = 'productos.php';
        }
    });
    
    $('#filtro-tipo').change(function() {
        var categoria = $('#filtro-categoria').val();
        var tipo = $(this).val();
        window.location.href = 'productos.php?categoria=' + categoria + (tipo ? '&tipo=' + tipo : '');
    });
    
    $('#filtro-orden').change(function() {
        // Implementar ordenamiento vía AJAX
        ordenarProductos($(this).val());
    });
});

function ordenarProductos(orden) {
    var categoria = $('#filtro-categoria').val();
    var tipo = $('#filtro-tipo').val();
    
    $.ajax({
        url: 'api/ordenar_productos.php',
        method: 'GET',
        data: {
            categoria: categoria,
            tipo: tipo,
            orden: orden
        },
        success: function(response) {
            $('#productos-container').html(response);
        }
    });
}
</script>
<!-- MODAL PARA VER IMÁGENES COMPLETAS -->
<div id="imageModal" class="image-modal">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="modalImage">
    <div id="modalCaption" class="modal-caption"></div>
    
    <!-- Botones de navegación (opcional) -->
    <a class="modal-nav prev" id="modalPrev">&#10094;</a>
    <a class="modal-nav next" id="modalNext">&#10095;</a>
</div>
</body>
</html>
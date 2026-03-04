<?php
require_once 'includes/functions.php';
$productos_destacados = getProductosDestacados(8);
$categorias = getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dulce Repostería - Tienda de Tortas y Postres</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cake-candles"></i> Dulce Repostería
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Tortas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=niños">Niños</a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=adultos">Adultos</a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=bautizo">Bautizos/Comunión</a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=clasica">Clásicas</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php?categoria=cupcakes">Cupcakes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php?categoria=gelatinas">Gelatinas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php?categoria=quesillos">Quesillos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php?categoria=snacks">Snacks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php?categoria=dulces">Dulces</a>
                </li>
            </ul>
            <button class="btn btn-outline-primary ms-2 position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#carritoOffcanvas">
                <i class="fas fa-shopping-cart"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="carrito-contador">
                    0
                </span>
            </button>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section text-center text-white d-flex align-items-center">
    <div class="container">
        <h1 class="display-3 fw-bold">Reposteria Caracas</h1>
        <p class="lead mb-4">Los mejores postres artesanales hechos con amor</p>
        
    </div>
</section>

<!-- Categorías -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Nuestras Categorías</h2>
        <div class="row g-4">
            <?php
            $categorias_unicas = [];
            foreach ($categorias as $cat) {
                if (!in_array($cat['nombre'], $categorias_unicas)) {
                    $categorias_unicas[] = $cat['nombre'];
            ?>
            <div class="col-md-3 col-6">
                <div class="card categoria-card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-cake fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title"><?php echo ucfirst($cat['nombre']); ?></h5>
                        <a href="productos.php?categoria=<?php echo $cat['nombre']; ?>" class="btn btn-sm btn-outline-primary">Ver más</a>
                    </div>
                </div>
            </div>
            <?php 
                }
            } 
            ?>
        </div>
    </div>
</section>

<!-- Productos Destacados -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Productos Destacados</h2>
        <div class="row g-4" id="productos-destacados">
            <?php foreach ($productos_destacados as $producto): ?>
            <div class="col-md-3 col-6">
                <div class="card producto-card h-100">
                    <img src="uploads/productos/<?php echo $producto['imagen'] ?: 'default.jpg'; ?>" 
                         class="card-img-top" alt="<?php echo $producto['nombre']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                        <p class="card-text text-muted small"><?php echo $producto['descripcion_corta']; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">$<?php echo number_format($producto['precio_base'], 2); ?></span>
                            <div>
                                <a href="personalizar.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $producto['id']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Tortas por tipo de evento -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Tortas por ocasión</h2>
        
        <!-- Nav tabs -->
        <ul class="nav nav-tabs justify-content-center mb-4" id="tortasTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ninios-tab" data-bs-toggle="tab" data-bs-target="#ninios" type="button" role="tab">🎈 Niños</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adultos-tab" data-bs-toggle="tab" data-bs-target="#adultos" type="button" role="tab">🎉 Adultos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bautizos-tab" data-bs-toggle="tab" data-bs-target="#bautizos" type="button" role="tab">👼 Bautizos/Comunión</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clasicas-tab" data-bs-toggle="tab" data-bs-target="#clasicas" type="button" role="tab">🍰 Clásicas</button>
            </li>
        </ul>
        
        <!-- Tab panes -->
        <div class="tab-content">
            <?php
            // Obtener instancia de la base de datos usando tu sistema
            $db = Database::getInstance()->getConnection();
            
            // Definir los tipos de evento (según base de datos)
            $tipos_evento = [
                'ninios' => 'niños',
                'adultos' => 'adultos',
                'bautizos' => 'bautizo',
                'clasicas' => 'clasica'
            ];
            
            $active = true;
            
            foreach ($tipos_evento as $tab_id => $tipo):
                // Consulta adaptada a tu estructura
                $query = "
                    SELECT p.*, c.nombre as categoria_nombre, c.tipo_evento
                    FROM productos p
                    JOIN categorias c ON p.categoria_id = c.id
                    WHERE c.nombre = 'tortas' 
                    AND c.tipo_evento = '" . $db->real_escape_string($tipo) . "'
                    AND p.activo = 1
                    ORDER BY p.destacado DESC
                    LIMIT 4
                ";
                
                $result = $db->query($query);
            ?>
            <div class="tab-pane fade <?php echo $active ? 'show active' : ''; ?>" id="<?php echo $tab_id; ?>" role="tabpanel">
                <div class="row g-4">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($torta = $result->fetch_assoc()): ?>
                        <div class="col-md-3 col-6">
                            <div class="card producto-card h-100">
                                <img src="uploads/productos/<?php echo $torta['imagen'] ?: 'default.jpg'; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo $torta['nombre']; ?>"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=<?php echo urlencode($torta['nombre']); ?>'">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo $torta['nombre']; ?></h6>
                                    <p class="card-text text-primary fw-bold">$<?php echo number_format($torta['precio_base'], 2); ?></p>
                                    <button class="btn btn-sm btn-primary w-100 add-to-cart" data-id="<?php echo $torta['id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted">No hay productos disponibles en esta categoría</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                $active = false;
            endforeach; 
            ?>
        </div>
    </div>
</section>

<!-- Offcanvas del Carrito -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="carritoOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Tu Carrito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="carrito-contenido">
            <!-- Contenido cargado vía AJAX -->
            <p class="text-center text-muted">Cargando carrito...</p>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Dulce Repostería</h5>
                <p>Los mejores postres artesanales hechos con amor y los mejores ingredientes.</p>
            </div>
            <div class="col-md-4">
                <h5>Enlaces rápidos</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white-50">Sobre nosotros</a></li>
                    <li><a href="#" class="text-white-50">Contacto</a></li>
                    <li><a href="#" class="text-white-50">Términos y condiciones</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contacto</h5>
                <p><i class="fas fa-phone"></i> +58 412 123 4567</p>
                <p><i class="fas fa-envelope"></i> info@dulcereposteria.com</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Scripts personalizados -->
<script src="assets/js/config.js"></script>
<script src="assets/js/carrito.js"></script>
<script>
$(document).ready(function() {
    actualizarCarrito();
    
    // Agregar al carrito
    $('.add-to-cart').click(function() {
        var productoId = $(this).data('id');
        
        $.ajax({
            url: 'api/add_to_cart.php',
            method: 'POST',
            data: {
                producto_id: productoId,
                cantidad: 0
            },
            success: function(response) {
                if (response.success) {
                    actualizarCarrito();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Agregado!',
                        text: 'Producto agregado al carrito',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            }
        });
    });
});

function actualizarCarrito() {
    $.ajax({
        url: 'api/get_cart.php',
        method: 'GET',
        success: function(response) {
            $('#carrito-contenido').html(response);
            actualizarContador();
        }
    });
}

function actualizarContador() {
    $.ajax({
        url: 'api/cart_count.php',
        method: 'GET',
        success: function(response) {
            $('#carrito-contador').text(response.count);
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
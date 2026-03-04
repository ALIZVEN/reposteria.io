<?php
// Obtener contador del carrito
$items_carrito = getCarritoItems();
$cantidad_items = count($items_carrito['items']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-cake-candles" style="color: #ff8ba7;"></i> 
            Dulce Repostería
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                       href="index.php">Inicio</a>
                </li>
                
                <!-- Dropdown Tortas -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Tortas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=niños">
                            <i class="fas fa-child"></i> Niños
                        </a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=adultos">
                            <i class="fas fa-glass-cheers"></i> Adultos
                        </a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=bautizo">
                            <i class="fas fa-baby"></i> Bautizos/Comunión
                        </a></li>
                        <li><a class="dropdown-item" href="productos.php?categoria=tortas&tipo=clasica">
                            <i class="fas fa-birthday-cake"></i> Clásicas
                        </a></li>
                    </ul>
                </li>
                
                <!-- Otros productos -->
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
            
         
            <!-- Carrito -->
            <button class="btn btn-outline-primary position-relative" type="button" 
                    data-bs-toggle="offcanvas" data-bs-target="#carritoOffcanvas">
                <i class="fas fa-shopping-cart"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                      id="carrito-contador">
                    <?php echo $cantidad_items; ?>
                </span>
            </button>
        </div>
    </div>
</nav>

<!-- Resultados de búsqueda -->
<div id="resultados-busqueda" class="container mt-2" style="display: none;"></div>
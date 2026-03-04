<?php
// Obtener configuraciones si existen
$db = Database::getInstance()->getConnection();
$config = [];
$config_result = $db->query("SELECT clave, valor FROM configuraciones WHERE clave IN ('tienda_telefono', 'tienda_email', 'tienda_direccion', 'instagram', 'facebook', 'whatsapp')");
while($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}
?>

<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-cake-candles text-primary me-2"></i>
                    <?php echo defined('SITE_NAME') ? SITE_NAME : 'Dulce Repostería'; ?>
                </h5>
                <p class="text-white-50">
                    Los mejores postres artesanales hechos con amor y los mejores ingredientes para endulzar tus momentos especiales.
                </p>
                <div class="social-links mt-3">
                    <?php if (isset($config['facebook'])): ?>
                    <a href="https://facebook.com/<?php echo $config['facebook']; ?>" class="text-white me-3" target="_blank">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isset($config['instagram'])): ?>
                    <a href="https://instagram.com/<?php echo $config['instagram']; ?>" class="text-white me-3" target="_blank">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isset($config['whatsapp'])): ?>
                    <a href="https://wa.me/<?php echo $config['whatsapp']; ?>" class="text-white me-3" target="_blank">
                        <i class="fab fa-whatsapp fa-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-2 mb-4">
                <h5 class="mb-3">Enlaces</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Inicio</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/productos.php" class="text-white-50 text-decoration-none">Productos</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/productos.php?categoria=tortas" class="text-white-50 text-decoration-none">Tortas</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/productos.php?categoria=cupcakes" class="text-white-50 text-decoration-none">Cupcakes</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/productos.php?categoria=gelatinas" class="text-white-50 text-decoration-none">Gelatinas</a></li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5 class="mb-3">Horario</h5>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2">Lunes a Viernes: 9am - 7pm</li>
                    <li class="mb-2">Sábados: 9am - 5pm</li>
                    <li class="mb-2">Domingos: 10am - 2pm</li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5 class="mb-3">Contacto</h5>
                <ul class="list-unstyled text-white-50">
                    <?php if (isset($config['tienda_telefono'])): ?>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2"></i> <?php echo $config['tienda_telefono']; ?>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isset($config['tienda_email'])): ?>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i> <?php echo $config['tienda_email']; ?>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isset($config['tienda_direccion'])): ?>
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i> <?php echo $config['tienda_direccion']; ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <hr class="bg-secondary">
        
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0 text-white-50">
                    &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Dulce Repostería'; ?>. 
                    Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</footer>
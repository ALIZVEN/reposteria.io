<?php
require_once 'includes/functions.php';

$producto_id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Obtener producto
$stmt = $db->prepare("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.id = ? AND p.activo = 1
");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto) {
    header('Location: index.php');
    exit;
}

// Obtener opciones de personalización
$stmt = $db->prepare("SELECT * FROM opciones_personalizacion WHERE producto_id = ? AND activo = 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$opciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizar - <?php echo $producto['nombre']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <div id="productoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="uploads/productos/<?php echo $producto['imagen'] ?: 'default.jpg'; ?>" 
                             class="d-block w-100 rounded" alt="">
                    </div>
                    <?php if ($producto['imagen2']): ?>
                    <div class="carousel-item">
                        <img src="uploads/productos/<?php echo $producto['imagen2']; ?>" class="d-block w-100 rounded" alt="">
                    </div>
                    <?php endif; ?>
                    <?php if ($producto['imagen3']): ?>
                    <div class="carousel-item">
                        <img src="uploads/productos/<?php echo $producto['imagen3']; ?>" class="d-block w-100 rounded" alt="">
                    </div>
                    <?php endif; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#productoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
        
        <div class="col-md-6">
            <h2><?php echo $producto['nombre']; ?></h2>
            <p class="text-muted"><?php echo $producto['descripcion']; ?></p>
            
            <form id="form-personalizar">
                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Cantidad:</label>
                    <input type="number" name="cantidad" class="form-control" value="1" min="1" style="width: 100px;">
                </div>
                
                <?php if ($producto['tiene_extra_chocolate']): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="extra_chocolate" id="extra_chocolate" value="1">
                    <label class="form-check-label" for="extra_chocolate">
                        Extra Chocolate (+$<?php echo number_format($producto['precio_extra_chocolate'], 2); ?>)
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($producto['tiene_extra_relleno']): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="extra_relleno" id="extra_relleno" value="1">
                    <label class="form-check-label" for="extra_relleno">
                        Extra Relleno (+$<?php echo number_format($producto['precio_extra_relleno'], 2); ?>)
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($producto['tiene_extra_decoracion']): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="extra_decoracion" id="extra_decoracion" value="1">
                    <label class="form-check-label" for="extra_decoracion">
                        Decoración Especial (+$<?php echo number_format($producto['precio_extra_decoracion'], 2); ?>)
                    </label>
                </div>
                <?php endif; ?>
                
                <?php foreach ($opciones as $opcion): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="opciones_adicionales[]" 
                           value="<?php echo $opcion['id']; ?>" id="opcion_<?php echo $opcion['id']; ?>">
                    <label class="form-check-label" for="opcion_<?php echo $opcion['id']; ?>">
                        <?php echo $opcion['nombre']; ?> (+$<?php echo number_format($opcion['precio_adicional'], 2); ?>)
                    </label>
                </div>
                <?php endforeach; ?>
                
                <div class="mb-3">
                    <label class="form-label">Personalización (escribe lo que quieres):</label>
                    <textarea name="personalizacion" class="form-control" rows="2" 
                              placeholder="Ej: 'Feliz Cumpleaños María', 'Con corazones rojos', etc."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">¿Tienes alguna alergia?</label>
                    <textarea name="alergias" class="form-control" rows="2" 
                              placeholder="Ej: Alérgico a nueces, lácteos, etc."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Imagen de referencia (opcional):</label>
                    <input type="file" name="imagen_referencia" class="form-control" accept="image/*">
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Precio base: $<?php echo number_format($producto['precio_base'], 2); ?></h4>
                    <h4 id="precio-total">$<?php echo number_format($producto['precio_base'], 2); ?></h4>
                </div>
                
                <button type="button" class="btn btn-primary btn-lg w-100" id="btn-agregar-personalizado">
                    <i class="fas fa-cart-plus"></i> Agregar al Carrito
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/config.js"></script>
<script src="assets/js/carrito.js"></script>

<script>
$(document).ready(function() {
    // Calcular precio total
    function calcularPrecio() {
        var precioBase = <?php echo $producto['precio_base']; ?>;
        var total = precioBase;
        
        if ($('#extra_chocolate').is(':checked')) {
            total += <?php echo $producto['precio_extra_chocolate']; ?>;
        }
        if ($('#extra_relleno').is(':checked')) {
            total += <?php echo $producto['precio_extra_relleno']; ?>;
        }
        if ($('#extra_decoracion').is(':checked')) {
            total += <?php echo $producto['precio_extra_decoracion']; ?>;
        }
        
        var cantidad = parseInt($('input[name="cantidad"]').val()) || 1;
        $('#precio-total').text('$' + (total * cantidad).toFixed(2));
    }
    
    $('input[type="checkbox"], input[name="cantidad"]').on('change keyup', calcularPrecio);
    
    // Agregar al carrito con personalización
    $('#btn-agregar-personalizado').click(function() {
        var formData = new FormData(document.getElementById('form-personalizar'));
        
        $.ajax({
            url: 'api/add_custom_to_cart.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Producto personalizado agregado!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location.href = 'productos.php';
                    });
                }
            }
        });
    });
});
</script>
</body>
</html>
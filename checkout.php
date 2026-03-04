<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/functions.php';
require_once 'includes/functions.php';
$items = getCarritoItems();

if (empty($items['items'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Dulce Repostería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Finalizar Pedido</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Datos de Contacto</h5>
                    
                    <form id="form-checkout">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Teléfono *</label>
                                <input type="tel" name="telefono" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Dirección de Entrega *</label>
                                <input type="text" name="direccion" class="form-control" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Notas adicionales (opcional)</label>
                                <textarea name="notas" class="form-control" rows="3" placeholder="Ej: Portón negro, tocar timbre, etc."></textarea>
                            </div>
                            
                            <!-- Mensaje informativo -->
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    Una vez confirmes tu pedido, te contactaremos para coordinar la entrega y el método de pago.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumen del Pedido</h5>
                    
                    <?php foreach ($items['items'] as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo $item['cantidad']; ?>x <?php echo $item['nombre']; ?></span>
                        <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary">$<?php echo number_format($items['total'], 2); ?></strong>
                    </div>
                    
                    <button class="btn btn-success btn-lg w-100" id="btn-procesar-pedido">
                        <i class="fas fa-check-circle"></i> Confirmar Pedido
                    </button>
                    
                    <p class="text-muted small text-center mt-3">
                        <i class="fas fa-clock"></i> Te contactaremos en menos de 30 minutos
                    </p>
                </div>
            </div>
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
    // Procesar pedido
    $('#btn-procesar-pedido').click(function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!$('#form-checkout')[0].checkValidity()) {
            $('#form-checkout')[0].reportValidity();
            return;
        }
        
        // Confirmar antes de enviar
        Swal.fire({
            title: '¿Confirmar pedido?',
            text: 'Revisa que tus datos sean correctos',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarPedido();
            }
        });
    });
    
    function procesarPedido() {
        var formData = $('#form-checkout').serialize();
        
        // Mostrar loading
        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'api/procesar_pedido.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido Recibido!',
                        html: `
                            <p>Tu pedido #${response.pedido_id} ha sido registrado</p>
                            <p class="small">Te contactaremos pronto a tu teléfono o email</p>
                        `,
                        confirmButtonText: 'Entendido'
                    }).then(function() {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Hubo un problema al procesar tu pedido'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo procesar el pedido. Intenta nuevamente.'
                });
            }
        });
    }
});
</script>
</body>
</html>
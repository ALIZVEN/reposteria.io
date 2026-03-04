<?php
require_once 'includes/functions.php';

$pedido_id = $_GET['pedido'] ?? 0;

if (!$pedido_id) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por tu compra!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            position: relative;
        }
        
        .success-checkmark .check-icon {
            width: 80px;
            height: 80px;
            position: relative;
            border-radius: 50%;
            box-sizing: content-box;
            border: 4px solid #4CAF50;
        }
        
        .success-checkmark .check-icon::before {
            top: 50%;
            left: 50%;
            height: 80px;
            width: 80px;
            position: absolute;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            content: '';
            box-sizing: content-box;
        }
        
        .success-checkmark .check-icon .icon-line {
            height: 5px;
            background-color: #4CAF50;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 10;
        }
        
        .success-checkmark .check-icon .icon-line.line-tip {
            top: 42px;
            left: 14px;
            width: 25px;
            transform: rotate(45deg);
            animation: icon-line-tip 0.75s;
        }
        
        .success-checkmark .check-icon .icon-line.line-long {
            top: 34px;
            right: 8px;
            width: 47px;
            transform: rotate(-45deg);
            animation: icon-line-long 0.75s;
        }
        
        @keyframes icon-line-tip {
            0% { width: 0; left: 1px; top: 19px; }
            54% { width: 0; left: 1px; top: 19px; }
            70% { width: 50px; left: -8px; top: 37px; }
            84% { width: 17px; left: 21px; top: 48px; }
            100% { width: 25px; left: 14px; top: 42px; }
        }
        
        @keyframes icon-line-long {
            0% { width: 0; right: 46px; top: 54px; }
            65% { width: 0; right: 46px; top: 54px; }
            84% { width: 55px; right: 0px; top: 35px; }
            100% { width: 47px; right: 8px; top: 34px; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="success-checkmark">
                <div class="check-icon">
                    <span class="icon-line line-tip"></span>
                    <span class="icon-line line-long"></span>
                </div>
            </div>
            
            <h1 class="display-4 mt-4">¡Gracias por tu compra!</h1>
            <p class="lead">Tu pedido #<?php echo $pedido_id; ?> ha sido confirmado</p>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Detalles del pedido</h5>
                    <p><strong>Nombre:</strong> <?php echo $pedido['nombre_cliente']; ?></p>
                    <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></p>
                    <p><strong>Método de pago:</strong> 
                        <?php echo $pedido['metodo_pago'] == 'efectivo' ? 'Efectivo' : 'Pago Móvil'; ?>
                    </p>
                    <p><strong>Estado:</strong> 
                        <span class="badge bg-warning">Pendiente de confirmación</span>
                    </p>
                </div>
            </div>
            
            <div class="mt-4">
                <p>Hemos enviado los detalles a tu correo electrónico.</p>
                <p>Te contactaremos pronto para confirmar la entrega.</p>
                
                <a href="index.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-home"></i> Volver al inicio
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <script src="assets/js/config.js"></script>
<script src="assets/js/carrito.js"></script>
</body>
</html>
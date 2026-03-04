<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->checkAuth();

$db = Database::getInstance()->getConnection();
$pedido_id = $_GET['id'] ?? 0;

// Obtener pedido
$stmt = $db->prepare("
    SELECT p.*, 
           (SELECT SUM(total) FROM items_carrito ic WHERE ic.carrito_id = p.carrito_id) as subtotal
    FROM pedidos p 
    WHERE p.id = ?
");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header('Location: index.php');
    exit;
}

// Obtener items del pedido
$stmt = $db->prepare("
    SELECT ic.*, pr.nombre, pr.imagen, pr.precio_base,
           pr.precio_extra_chocolate, pr.precio_extra_relleno, pr.precio_extra_decoracion
    FROM items_carrito ic
    JOIN productos pr ON ic.producto_id = pr.id
    WHERE ic.carrito_id = ?
");
$stmt->bind_param("i", $pedido['carrito_id']);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $pedido_id; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../includes/menu.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-file-invoice"></i> 
            Pedido #<?php echo str_pad($pedido_id, 5, '0', STR_PAD_LEFT); ?>
        </h2>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="imprimir.php?id=<?php echo $pedido_id; ?>" class="btn btn-info text-white" target="_blank">
                <i class="fas fa-print"></i> Imprimir
            </a>
            <a href="actualizar.php?id=<?php echo $pedido_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Actualizar Estado
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Información del cliente -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Información del Cliente</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Nombre:</th>
                            <td><?php echo $pedido['nombre_cliente']; ?></td>
                        </tr>
                        <tr>
                            <th>Teléfono:</th>
                            <td>
                                <?php echo $pedido['telefono']; ?>
                                <a href="https://wa.me/<?php echo $pedido['telefono']; ?>" 
                                   class="btn btn-sm btn-success ms-2" target="_blank">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo $pedido['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Dirección:</th>
                            <td><?php echo nl2br($pedido['direccion']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Información del pedido -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Detalles del Pedido</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Fecha del pedido:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                        </tr>
                        <tr>
                            <th>Estado actual:</th>
                            <td>
                                <?php
                                $badge_color = [
                                    'pendiente' => 'warning',
                                    'confirmado' => 'info',
                                    'preparando' => 'primary',
                                    'listo' => 'success',
                                    'entregado' => 'success',
                                    'cancelado' => 'danger'
                                ];
                                $color = $badge_color[$pedido['estado']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?> p-2">
                                    <?php echo ucfirst($pedido['estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Método de pago:</th>
                            <td>
                                <?php if ($pedido['metodo_pago'] == 'efectivo'): ?>
                                    <span class="badge bg-success">Efectivo</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Pago Móvil</span>
                                    <?php if ($pedido['referencia_pago']): ?>
                                        <br><small>Ref: <?php echo $pedido['referencia_pago']; ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Notas adicionales:</th>
                            <td><?php echo nl2br($pedido['notas'] ?: 'Sin notas'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Items del pedido -->
    <div class="card">
        <div class="card-header">
            <h5>Productos solicitados</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Imagen</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Extras</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        while ($item = $items->fetch_assoc()): 
                            $precio_unitario = $item['precio_base'];
                            $extras = [];
                            
                            if ($item['extra_chocolate']) {
                                $precio_unitario += $item['precio_extra_chocolate'];
                                $extras[] = "Chocolate (+$" . $item['precio_extra_chocolate'] . ")";
                            }
                            if ($item['extra_relleno']) {
                                $precio_unitario += $item['precio_extra_relleno'];
                                $extras[] = "Relleno (+$" . $item['precio_extra_relleno'] . ")";
                            }
                            if ($item['extra_decoracion']) {
                                $precio_unitario += $item['precio_extra_decoracion'];
                                $extras[] = "Decoración (+$" . $item['precio_extra_decoracion'] . ")";
                            }
                            
                            $subtotal = $precio_unitario * $item['cantidad'];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo $item['nombre']; ?></td>
                            <td>
                                <?php if ($item['imagen']): ?>
                                <img src="../../uploads/productos/<?php echo $item['imagen']; ?>" 
                                     width="50" height="50" style="object-fit: cover;" class="rounded">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td>$<?php echo number_format($precio_unitario, 2); ?></td>
                            <td>
                                <?php if (!empty($extras)): ?>
                                <small><?php echo implode('<br>', $extras); ?></small>
                                <?php endif; ?>
                                <?php if ($item['personalizacion']): ?>
                                <br><small class="text-primary">📝 <?php echo $item['personalizacion']; ?></small>
                                <?php endif; ?>
                                <?php if ($item['alergias']): ?>
                                <br><small class="text-danger">⚠️ <?php echo $item['alergias']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total:</th>
                            <th>$<?php echo number_format($total, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Historial de cambios -->
    <?php
    $auditoria = $db->query("
        SELECT * FROM auditoria 
        WHERE tabla = 'pedidos' AND registro_id = $pedido_id 
        ORDER BY fecha DESC
    ");
    
    if ($auditoria->num_rows > 0):
    ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5>Historial de cambios</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php while ($log = $auditoria->fetch_assoc()): ?>
                <div class="mb-3">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-circle text-primary"></i>
                        </div>
                        <div>
                            <p class="mb-0">
                                <strong><?php echo ucfirst($log['accion']); ?></strong> - 
                                <?php echo $log['detalles']; ?>
                            </p>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?> - 
                                IP: <?php echo $log['ip_address']; ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->checkAuth();

$db = Database::getInstance()->getConnection();

// Filtros
$estado_filter = $_GET['estado'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Construir query
$where = ["DATE(p.fecha_pedido) BETWEEN ? AND ?"];
$params = [$fecha_desde, $fecha_hasta];
$types = "ss";

if ($estado_filter) {
    $where[] = "p.estado = ?";
    $params[] = $estado_filter;
    $types .= "s";
}

$where_clause = "WHERE " . implode(" AND ", $where);

// Obtener pedidos
$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM items_carrito ic WHERE ic.carrito_id = p.carrito_id) as total_items
        FROM pedidos p 
        $where_clause 
        ORDER BY p.fecha_pedido DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$pedidos = $stmt->get_result();

// Estadísticas rápidas
$stats = [];
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
    SUM(CASE WHEN estado = 'preparando' THEN 1 ELSE 0 END) as preparando,
    SUM(CASE WHEN estado = 'listo' THEN 1 ELSE 0 END) as listos,
    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
    SUM(total) as total_ventas
FROM pedidos 
WHERE DATE(fecha_pedido) BETWEEN ? AND ?";

$stmt_stats = $db->prepare($stats_sql);
$stmt_stats->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/menu.php'; ?>

<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-clipboard-list"></i> 
        Gestión de Pedidos
    </h2>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Pedidos</h5>
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pendientes</h5>
                    <h3><?php echo $stats['pendientes'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Entregados</h5>
                    <h3><?php echo $stats['entregados'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Ventas</h5>
                    <h3>$<?php echo number_format($stats['total_ventas'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="<?php echo $fecha_desde; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="<?php echo $fecha_hasta; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $estado_filter == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="confirmado" <?php echo $estado_filter == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                        <option value="preparando" <?php echo $estado_filter == 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                        <option value="listo" <?php echo $estado_filter == 'listo' ? 'selected' : ''; ?>>Listo</option>
                        <option value="entregado" <?php echo $estado_filter == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelado" <?php echo $estado_filter == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de pedidos -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="tablaPedidos">
                    <thead>
                        <tr>
                            <th>#Pedido</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Método Pago</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($pedido['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                            <td><?php echo $pedido['nombre_cliente']; ?></td>
                            <td><?php echo $pedido['telefono']; ?></td>
                            <td><?php echo $pedido['total_items']; ?></td>
                            <td><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></td>
                            <td>
                                <?php if ($pedido['metodo_pago'] == 'efectivo'): ?>
                                <span class="badge bg-success">Efectivo</span>
                                <?php else: ?>
                                <span class="badge bg-info">Pago Móvil</span>
                                <?php endif; ?>
                            </td>
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
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo ucfirst($pedido['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver.php?id=<?php echo $pedido['id']; ?>" 
                                   class="btn btn-sm btn-info text-white" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="actualizar.php?id=<?php echo $pedido['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Actualizar estado">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="imprimir.php?id=<?php echo $pedido['id']; ?>" 
                                   class="btn btn-sm btn-secondary" title="Imprimir factura" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($pedido['estado'] != 'entregado' && $pedido['estado'] != 'cancelado'): ?>
                                <button type="button" class="btn btn-sm btn-success btn-confirmar-whatsapp"
                                        data-telefono="<?php echo $pedido['telefono']; ?>"
                                        data-pedido="<?php echo $pedido['id']; ?>"
                                        title="Enviar WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaPedidos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });
    
    // Botón WhatsApp
    $('.btn-confirmar-whatsapp').click(function() {
        var telefono = $(this).data('telefono');
        var pedido = $(this).data('pedido');
        
        var mensaje = encodeURIComponent(
            '¡Hola! Tu pedido #' + pedido + ' está siendo preparado. ' +
            'Pronto estará listo para entregar. Gracias por preferirnos 🍰'
        );
        
        window.open('https://wa.me/' + telefono + '?text=' + mensaje, '_blank');
    });
});
</script>
</body>
</html>
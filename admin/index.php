<?php
require_once 'includes/auth.php';
$auth = new Auth();
$auth->checkAuth();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas
$stats = [];

// Total de pedidos hoy
$result = $db->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()");
$stats['pedidos_hoy'] = $result->fetch_assoc()['total'];

// Ventas hoy
$result = $db->query("SELECT SUM(total) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado != 'cancelado'");
$stats['ventas_hoy'] = $result->fetch_assoc()['total'] ?? 0;

// Pedidos pendientes
$result = $db->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
$stats['pedidos_pendientes'] = $result->fetch_assoc()['total'];

// Productos bajos en stock (simulado)
$stats['productos_bajos'] = 5;

// Últimos pedidos
$ultimos_pedidos = $db->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM items_carrito ic WHERE ic.carrito_id = p.carrito_id) as total_items
    FROM pedidos p 
    ORDER BY p.fecha_pedido DESC 
    LIMIT 10
");

// Ventas por mes (para gráfico)
$ventas_mes = $db->query("
    SELECT DATE_FORMAT(fecha_pedido, '%Y-%m') as mes, 
           COUNT(*) as total_pedidos,
           SUM(total) as total_ventas
    FROM pedidos 
    WHERE fecha_pedido >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_pedido, '%Y-%m')
    ORDER BY mes DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>



<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-tachometer-alt"></i> 
                Dashboard
                <small class="text-muted">Bienvenido, <?php echo $_SESSION['admin_nombre']; ?></small>
            </h2>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['pedidos_hoy']; ?></h3>
                    <p class="text-muted mb-0">Pedidos Hoy</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 class="mb-0">$<?php echo number_format($stats['ventas_hoy'], 2); ?></h3>
                    <p class="text-muted mb-0">Ventas Hoy</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['pedidos_pendientes']; ?></h3>
                    <p class="text-muted mb-0">Pendientes</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['productos_bajos']; ?></h3>
                    <p class="text-muted mb-0">Stock Bajo</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico y últimos pedidos -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Ventas últimos 6 meses</h5>
                </div>
                <div class="card-body">
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="pedidos/index.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Ver Pedidos
                        </a>
                        <a href="productos/crear.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                        <a href="reportes/ventas.php" class="btn btn-info text-white">
                            <i class="fas fa-chart-line"></i> Reportes
                        </a>
                        <a href="configuracion.php" class="btn btn-warning">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimos Pedidos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Últimos Pedidos</h5>
                    <a href="pedidos/index.php" class="btn btn-sm btn-primary">Ver todos</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pedido = $ultimos_pedidos->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $pedido['id']; ?></td>
                                    <td><?php echo $pedido['nombre_cliente']; ?></td>
                                    <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                    <td><?php echo $pedido['total_items']; ?></td>
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
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>
                                        <a href="pedidos/ver.php?id=<?php echo $pedido['id']; ?>" 
                                           class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Gráfico de ventas
var ctx = document.getElementById('ventasChart').getContext('2d');
var ventasChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php 
            $labels = [];
            $ventas = [];
            while($row = $ventas_mes->fetch_assoc()) {
                $labels[] = "'" . date('M Y', strtotime($row['mes'] . '-01')) . "'";
                $ventas[] = $row['total_ventas'];
            }
            echo implode(',', array_reverse($labels));
        ?>],
        datasets: [{
            label: 'Ventas ($)',
            data: [<?php echo implode(',', array_reverse($ventas)); ?>],
            borderColor: '#ff8ba7',
            backgroundColor: 'rgba(255, 139, 167, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
</body>
</html>
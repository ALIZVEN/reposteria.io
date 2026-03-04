<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->checkAuth();

$db = Database::getInstance()->getConnection();

// Paginación
$pagina = $_GET['pagina'] ?? 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$categoria_filter = $_GET['categoria'] ?? '';
$activo_filter = $_GET['activo'] ?? '';

// Construir query
$where = [];
$params = [];
$types = "";

if ($categoria_filter) {
    $where[] = "c.id = ?";
    $params[] = $categoria_filter;
    $types .= "i";
}

if ($activo_filter !== '') {
    $where[] = "p.activo = ?";
    $params[] = $activo_filter;
    $types .= "i";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Obtener total de registros
$count_sql = "SELECT COUNT(*) as total FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id $where_clause";
$count_stmt = $db->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_registros = $count_stmt->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener productos
$sql = "SELECT p.*, c.nombre as categoria_nombre, c.tipo_evento 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        $where_clause 
        ORDER BY p.fecha_creacion DESC 
        LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$productos = $stmt->get_result();

// Obtener categorías para el filtro
$categorias = $db->query("SELECT * FROM categorias ORDER BY nombre, tipo_evento");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/menu.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-box"></i> 
            Gestionar Productos
        </h2>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php while($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $categoria_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cat['nombre']) . ' - ' . ucfirst($cat['tipo_evento']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="activo" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $activo_filter === '1' ? 'selected' : ''; ?>>Activos</option>
                        <option value="0" <?php echo $activo_filter === '0' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de productos -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Destacado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($producto = $productos->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $producto['id']; ?></td>
                            <td>
                                <?php if ($producto['imagen']): ?>
                                <img src="../../uploads/productos/<?php echo $producto['imagen']; ?>" 
                                     width="50" height="50" class="rounded" style="object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                     style="width:50px; height:50px;">
                                    <i class="fas fa-image"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $producto['nombre']; ?></td>
                            <td>
                                <?php echo ucfirst($producto['categoria_nombre']); ?>
                                <?php if ($producto['tipo_evento'] != 'general'): ?>
                                <br><small class="text-muted"><?php echo ucfirst($producto['tipo_evento']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($producto['precio_base'], 2); ?></td>
                            <td>
                                <?php if ($producto['destacado']): ?>
                                <span class="badge bg-success">Sí</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($producto['activo']): ?>
                                <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar.php?id=<?php echo $producto['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="ver.php?id=<?php echo $producto['id']; ?>" 
                                   class="btn btn-sm btn-info text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-eliminar" 
                                        data-id="<?php echo $producto['id']; ?>" 
                                        data-nombre="<?php echo $producto['nombre']; ?>"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina-1; ?>&categoria=<?php echo $categoria_filter; ?>&activo=<?php echo $activo_filter; ?>">
                            Anterior
                        </a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&categoria=<?php echo $categoria_filter; ?>&activo=<?php echo $activo_filter; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina+1; ?>&categoria=<?php echo $categoria_filter; ?>&activo=<?php echo $activo_filter; ?>">
                            Siguiente
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de eliminar el producto <strong id="productoNombre"></strong>?</p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // DataTable
    $('#tablaProductos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 15,
        ordering: true
    });
    
    // Eliminar producto
    $('.btn-eliminar').click(function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        $('#productoNombre').text(nombre);
        $('#btnConfirmarEliminar').attr('href', 'eliminar.php?id=' + id);
        $('#modalEliminar').modal('show');
    });
});

// Mostrar notificaciones
<?php if (isset($_GET['success'])): ?>
Swal.fire({
    icon: 'success',
    title: '¡Éxito!',
    text: 'Operación realizada correctamente',
    timer: 2000
});
<?php endif; ?>
</script>
</body>
</html>
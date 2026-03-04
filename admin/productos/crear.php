<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->checkAuth();
$auth->checkRol(['admin', 'editor']);

$db = Database::getInstance()->getConnection();

// Obtener categorías
$categorias = $db->query("SELECT * FROM categorias ORDER BY nombre, tipo_evento");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Procesar formulario
    $nombre = $_POST['nombre'];
    $categoria_id = $_POST['categoria_id'];
    $descripcion = $_POST['descripcion'];
    $descripcion_corta = $_POST['descripcion_corta'];
    $precio_base = $_POST['precio_base'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    // Opciones extras
    $tiene_extra_chocolate = isset($_POST['tiene_extra_chocolate']) ? 1 : 0;
    $precio_extra_chocolate = $_POST['precio_extra_chocolate'] ?? 2.00;
    $tiene_extra_relleno = isset($_POST['tiene_extra_relleno']) ? 1 : 0;
    $precio_extra_relleno = $_POST['precio_extra_relleno'] ?? 3.00;
    $tiene_extra_decoracion = isset($_POST['tiene_extra_decoracion']) ? 1 : 0;
    $precio_extra_decoracion = $_POST['precio_extra_decoracion'] ?? 2.50;
    
    // Manejar imagen principal
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $target_dir = "../../uploads/productos/";
        $extension = pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $extension;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
            $imagen = $filename;
        }
    }
    
    // Insertar producto
    $stmt = $db->prepare("
        INSERT INTO productos 
        (categoria_id, nombre, descripcion, descripcion_corta, precio_base, imagen, 
         destacado, tiene_extra_chocolate, precio_extra_chocolate, 
         tiene_extra_relleno, precio_extra_relleno, 
         tiene_extra_decoracion, precio_extra_decoracion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "isssdsiiddidd",
        $categoria_id, $nombre, $descripcion, $descripcion_corta, $precio_base, $imagen,
        $destacado, $tiene_extra_chocolate, $precio_extra_chocolate,
        $tiene_extra_relleno, $precio_extra_relleno,
        $tiene_extra_decoracion, $precio_extra_decoracion
    );
    
    if ($stmt->execute()) {
        $producto_id = $db->insert_id;
        
        // Registrar auditoría
        $auth->registrarAuditoria(
            $_SESSION['admin_id'],
            'crear',
            'productos',
            $producto_id,
            "Producto creado: $nombre"
        );
        
        header('Location: index.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Producto - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../includes/menu.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-plus-circle"></i> 
            Crear Nuevo Producto
        </h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Información básica -->
                        <div class="mb-3">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" class="form-select" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php while($cat = $categorias->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo ucfirst($cat['nombre']) . ' - ' . ucfirst($cat['tipo_evento']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Base *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="precio_base" class="form-control" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción Corta</label>
                            <input type="text" name="descripcion_corta" class="form-control" 
                                   maxlength="200" placeholder="Breve descripción del producto">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción Completa</label>
                            <textarea name="descripcion" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <!-- Opciones adicionales -->
                        <h5 class="mt-4">Opciones de Personalización</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="tiene_extra_chocolate" id="extra_chocolate" checked>
                                            <label class="form-check-label" for="extra_chocolate">
                                                Extra Chocolate
                                            </label>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="precio_extra_chocolate" 
                                                   class="form-control" value="2.00" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="tiene_extra_relleno" id="extra_relleno" checked>
                                            <label class="form-check-label" for="extra_relleno">
                                                Extra Relleno
                                            </label>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="precio_extra_relleno" 
                                                   class="form-control" value="3.00" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="tiene_extra_decoracion" id="extra_decoracion" checked>
                                            <label class="form-check-label" for="extra_decoracion">
                                                Decoración Especial
                                            </label>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="precio_extra_decoracion" 
                                                   class="form-control" value="2.50" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Imagen principal -->
                        <div class="mb-3">
                            <label class="form-label">Imagen Principal</label>
                            <input type="file" name="imagen" class="form-control" accept="image/*">
                            <div class="form-text">Tamaño recomendado: 500x500px</div>
                        </div>
                        
                        <!-- Imagen 2 -->
                        <div class="mb-3">
                            <label class="form-label">Imagen 2 (opcional)</label>
                            <input type="file" name="imagen2" class="form-control" accept="image/*">
                        </div>
                        
                        <!-- Imagen 3 -->
                        <div class="mb-3">
                            <label class="form-label">Imagen 3 (opcional)</label>
                            <input type="file" name="imagen3" class="form-control" accept="image/*">
                        </div>
                        
                        <!-- Opciones adicionales -->
                        <div class="card">
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="destacado" id="destacado">
                                    <label class="form-check-label" for="destacado">
                                        Marcar como producto destacado
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
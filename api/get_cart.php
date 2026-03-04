<?php
session_start();
require_once '../includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

$items = getCarritoItems();

if (empty($items['items'])) {
    echo '<div class="text-center py-5">';
    echo '<i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>';
    echo '<p class="text-muted">Tu carrito está vacío</p>';
    echo '<a href="productos.php" class="btn btn-primary btn-sm">Ver productos</a>';
    echo '</div>';
} else {
    echo '<div class="cart-items">';
    foreach ($items['items'] as $item) {
        ?>
        <div class="card mb-2 cart-item" data-id="<?php echo $item['id']; ?>">
            <div class="row g-0">
                <div class="col-4">
                    <img src="uploads/productos/<?php echo $item['imagen'] ?: 'default.jpg'; ?>" 
                         class="img-fluid rounded-start" 
                         style="height: 80px; width: 100%; object-fit: cover;"
                         alt="<?php echo $item['nombre']; ?>">
                </div>
                <div class="col-8">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1"><?php echo $item['nombre']; ?></h6>
                        <p class="card-text small mb-1">
                            $<?php echo number_format($item['precio'], 2); ?> x 
                            <input type="number" class="cart-quantity form-control form-control-sm d-inline-block" 
                                   value="<?php echo $item['cantidad']; ?>" 
                                   min="1" style="width: 60px;" data-id="<?php echo $item['id']; ?>">
                        </p>
                        <p class="card-text mb-1">
                            <strong>$<?php echo number_format($item['subtotal'], 2); ?></strong>
                        </p>
                        <?php if (!empty($item['personalizacion'])): ?>
                        <small class="text-muted d-block">📝 <?php echo substr($item['personalizacion'], 0, 30); ?>...</small>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-danger remove-item mt-1" data-id="<?php echo $item['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    
    // Calcular total general
    $total_general = 0;
    foreach ($items['items'] as $item) {
        $total_general += $item['subtotal'];
    }
    ?>
    <div class="text-end mt-3">
        <h6>Total: $<?php echo number_format($total_general, 2); ?></h6>
        <a href="checkout.php" class="btn btn-success btn-sm w-100">
            <i class="fas fa-check"></i> Finalizar Compra
        </a>
    </div>
    <?php
}
?>
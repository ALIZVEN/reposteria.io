// ========================================
// CARRITO DE COMPRAS - FUNCIONES PRINCIPALES
// VERSIÓN CON CONFIG.JS
// ========================================

// Verificar que CONFIG existe
if (typeof CONFIG === 'undefined') {
    console.error('❌ ERROR: config.js no está cargado. Usando valor por defecto.');
    var CONFIG = { API_URL: '/demo/rurik/api' };
}

console.log('🔧 Usando API_URL:', CONFIG.API_URL);
console.log('🌍 Hostname:', window.location.hostname);
console.log('📁 Pathname:', window.location.pathname);

$(document).ready(function() {
    // Inicializar carrito
    actualizarCarrito();
    actualizarContador();
    
    // ========================================
    // VISOR DE IMÁGENES COMPLETAS (LIGHTBOX)
    // ========================================
    
    // Variable para guardar todas las imágenes
    var imagenesProductos = [];
    
    // Recopilar todas las imágenes de productos
    function recopilarImagenes() {
        imagenesProductos = [];
        $('.producto-card img').each(function(index) {
            var img = $(this);
            var card = img.closest('.producto-card');
            var nombre = card.find('.card-title').first().text() || 'Producto';
            var src = img.attr('src');
            
            if (src) {
                imagenesProductos.push({
                    src: src,
                    nombre: nombre,
                    index: index
                });
            }
        });
        console.log('📸 Imágenes cargadas:', imagenesProductos.length);
    }
    
    recopilarImagenes();
    
    var imagenActualIndex = 0;
    
    // Abrir modal al hacer clic en cualquier imagen de producto
    $(document).on('click', '.producto-card img', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var imgSrc = $(this).attr('src');
        var card = $(this).closest('.producto-card');
        var productName = card.find('.card-title').first().text() || 'Producto';
        
        // Encontrar el índice
        imagenActualIndex = imagenesProductos.findIndex(img => img.src === imgSrc);
        if (imagenActualIndex === -1) imagenActualIndex = 0;
        
        $('#modalImage').attr('src', imgSrc);
        $('#modalCaption').text(productName);
        $('#imageModal').css('display', 'block');
        $('body').css('overflow', 'hidden');
        
        return false;
    });
    
    // Cerrar modal
    $('.close-modal, #imageModal').click(function(e) {
        if (e.target === this || $(e.target).hasClass('close-modal')) {
            $('#imageModal').css('display', 'none');
            $('body').css('overflow', 'auto');
        }
    });
    
    // Navegación con teclas
    $(document).keyup(function(e) {
        if ($('#imageModal').is(':visible')) {
            if (e.key === 'Escape') {
                $('#imageModal').css('display', 'none');
                $('body').css('overflow', 'auto');
            } else if (e.key === 'ArrowLeft') {
                navegarImagen('prev');
            } else if (e.key === 'ArrowRight') {
                navegarImagen('next');
            }
        }
    });
    
    // Botones de navegación
    $('#modalPrev').click(function() {
        navegarImagen('prev');
    });
    
    $('#modalNext').click(function() {
        navegarImagen('next');
    });
    
    function navegarImagen(direccion) {
        if (imagenesProductos.length === 0) return;
        
        if (direccion === 'next') {
            imagenActualIndex = (imagenActualIndex + 1) % imagenesProductos.length;
        } else {
            imagenActualIndex = (imagenActualIndex - 1 + imagenesProductos.length) % imagenesProductos.length;
        }
        
        var nuevaImagen = imagenesProductos[imagenActualIndex];
        $('#modalImage').attr('src', nuevaImagen.src);
        $('#modalCaption').text(nuevaImagen.nombre);
    }
    
    // ========================================
    // AGREGAR AL CARRITO - AHORA USA CONFIG
    // ========================================
    $(document).on('click', '.add-to-cart', function() {
        var productoId = $(this).data('id');
        var boton = $(this);
        var textoOriginal = boton.html();
        
        boton.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        // ✅ Usando CONFIG.API_URL
        var url = CONFIG.API_URL + '/add_to_cart.php';
        console.log('📤 Enviando a:', url);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                producto_id: productoId,
                cantidad: 1
            },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('✅ Respuesta:', response);
                if (response && response.success) {
                    actualizarCarrito();
                    actualizarContador();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Agregado!',
                            text: 'Producto agregado al carrito',
                            showConfirmButton: false,
                            timer: 1500,
                            position: 'top-end',
                            toast: true
                        });
                    } else {
                        alert('Producto agregado al carrito');
                    }
                    
                    if (typeof bootstrap !== 'undefined') {
                        var offcanvasElement = document.getElementById('carritoOffcanvas');
                        if (offcanvasElement) {
                            var offcanvas = new bootstrap.Offcanvas(offcanvasElement);
                            offcanvas.show();
                        }
                    }
                } else {
                    console.error('Respuesta sin éxito:', response);
                    mostrarError('Error al agregar el producto');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error:', error);
                console.error('URL que falló:', url);
                console.error('Status:', status);
                console.error('Respuesta:', xhr.responseText);
                
                var mensaje = 'Error al conectar con el servidor';
                if (status === 'timeout') {
                    mensaje = 'Tiempo de espera agotado';
                } else if (xhr.status === 404) {
                    mensaje = 'Archivo no encontrado (404)';
                } else if (xhr.status === 500) {
                    mensaje = 'Error interno del servidor (500)';
                }
                
                mostrarError(mensaje);
            },
            complete: function() {
                boton.html(textoOriginal).prop('disabled', false);
            }
        });
    });
    
    function mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });
        } else {
            alert('Error: ' + mensaje);
        }
    }
    
    // ========================================
    // ACTUALIZAR CANTIDAD EN CARRITO - USA CONFIG
    // ========================================
    $(document).on('change', '.cart-quantity', function() {
        var itemId = $(this).data('id');
        var cantidad = $(this).val();
        
        $.ajax({
            url: CONFIG.API_URL + '/update_cart.php',
            method: 'POST',
            data: {
                item_id: itemId,
                cantidad: cantidad
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    actualizarCarrito();
                    actualizarContador();
                }
            },
            error: function() {
                console.error('Error actualizando cantidad');
            }
        });
    });
    
    // ========================================
    // ELIMINAR ITEM DEL CARRITO - USA CONFIG
    // ========================================
    $(document).on('click', '.remove-item', function() {
        var itemId = $(this).data('id');
        var boton = $(this);
        var textoOriginal = boton.html();
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar producto?',
                text: '¿Estás seguro de eliminar este producto del carrito?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarItem(itemId, boton, textoOriginal);
                }
            });
        } else {
            if (confirm('¿Eliminar este producto del carrito?')) {
                eliminarItem(itemId, boton, textoOriginal);
            }
        }
    });
    
    function eliminarItem(itemId, boton, textoOriginal) {
        boton.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: CONFIG.API_URL + '/remove_from_cart.php',
            method: 'POST',
            data: {
                item_id: itemId
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    actualizarCarrito();
                    actualizarContador();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(
                            '¡Eliminado!',
                            'El producto ha sido eliminado del carrito.',
                            'success'
                        );
                    }
                }
            },
            error: function() {
                console.error('Error eliminando item');
            },
            complete: function() {
                boton.html(textoOriginal).prop('disabled', false);
            }
        });
    }
});

// ========================================
// FUNCIONES GLOBALES - USA CONFIG
// ========================================

function actualizarCarrito() {
    var url = CONFIG.API_URL + '/get_cart.php';
    console.log('📥 Cargando carrito desde:', url);
    
    $.ajax({
        url: url,
        method: 'GET',
        timeout: 10000,
        success: function(response) {
            $('#carrito-contenido').html(response);
        },
        error: function(xhr, status, error) {
            console.error('Error cargando carrito:', error);
            $('#carrito-contenido').html('<p class="text-center text-danger">Error al cargar el carrito</p>');
        }
    });
}

function actualizarContador() {
    var url = CONFIG.API_URL + '/cart_count.php';
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(response) {
            if (response && response.count !== undefined) {
                $('#carrito-contador').text(response.count);
                if (response.count == 0) {
                    $('#carrito-contador').hide();
                } else {
                    $('#carrito-contador').show();
                }
            }
        },
        error: function() {
            console.error('Error actualizando contador');
        }
    });
}

// ========================================
// VISOR DE IMÁGENES
// ========================================
function asegurarModal() {
    if ($('#imageModal').length === 0) {
        $('body').append(`
            <div id="imageModal" class="image-modal">
                <span class="close-modal">&times;</span>
                <img class="modal-content" id="modalImage">
                <div id="modalCaption" class="modal-caption"></div>
                <a class="modal-nav prev" id="modalPrev">&#10094;</a>
                <a class="modal-nav next" id="modalNext">&#10095;</a>
            </div>
        `);
    }
}

function actualizarImagenes() {
    window.imagenesProductos = [];
    $('.producto-card img').each(function(index) {
        var img = $(this);
        var card = img.closest('.producto-card');
        var nombre = card.find('.card-title').first().text() || 'Producto';
        var src = img.attr('src');
        
        if (src) {
            window.imagenesProductos.push({
                src: src,
                nombre: nombre,
                index: index
            });
        }
    });
    console.log('📸 Imágenes cargadas:', window.imagenesProductos.length);
}

function initVisorImagenes() {
    asegurarModal();
    actualizarImagenes();
    
    $(document).off('click', '.producto-card img');
    
    $(document).on('click', '.producto-card img', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var imgSrc = $(this).attr('src');
        var card = $(this).closest('.producto-card');
        var productName = card.find('.card-title').first().text() || 'Producto';
        
        console.log('👆 Click en imagen:', imgSrc);
        
        actualizarImagenes();
        
        window.imagenActualIndex = window.imagenesProductos.findIndex(img => img.src === imgSrc);
        if (window.imagenActualIndex === -1) window.imagenActualIndex = 0;
        
        $('#modalImage').attr('src', imgSrc);
        $('#modalCaption').text(productName);
        $('#imageModal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        return false;
    });
}

function navegarImagen(direccion) {
    if (!window.imagenesProductos || window.imagenesProductos.length === 0) return;
    
    if (direccion === 'next') {
        window.imagenActualIndex = (window.imagenActualIndex + 1) % window.imagenesProductos.length;
    } else {
        window.imagenActualIndex = (window.imagenActualIndex - 1 + window.imagenesProductos.length) % window.imagenesProductos.length;
    }
    
    var nuevaImagen = window.imagenesProductos[window.imagenActualIndex];
    $('#modalImage').attr('src', nuevaImagen.src);
    $('#modalCaption').text(nuevaImagen.nombre);
}

$(document).ready(function() {
    initVisorImagenes();
    
    $(document).on('click', '.close-modal, #imageModal', function(e) {
        if (e.target === this || $(e.target).hasClass('close-modal')) {
            $('#imageModal').fadeOut(300);
            $('body').css('overflow', 'auto');
        }
    });
    
    $(document).on('keyup', function(e) {
        if ($('#imageModal').is(':visible')) {
            if (e.key === 'Escape') {
                $('#imageModal').fadeOut(300);
                $('body').css('overflow', 'auto');
            } else if (e.key === 'ArrowLeft') {
                navegarImagen('prev');
            } else if (e.key === 'ArrowRight') {
                navegarImagen('next');
            }
        }
    });
    
    $(document).on('click', '#modalPrev', function() {
        navegarImagen('prev');
    });
    
    $(document).on('click', '#modalNext', function() {
        navegarImagen('next');
    });
    
    $(document).ajaxComplete(function() {
        setTimeout(actualizarImagenes, 100);
    });
});

window.addEventListener('load', function() {
    setTimeout(actualizarImagenes, 200);
});
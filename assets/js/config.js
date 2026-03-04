// ========================================
// CONFIGURACIÓN GLOBAL DEL SITIO
// ========================================

const CONFIG = {
    // URL base para las peticiones AJAX
    API_URL: '/demo/rurik/api',
    
    // Versión de la aplicación
    VERSION: '1.0.0',
    
    // Nombre del sitio
    SITE_NAME: 'Dulce reposteria',
    
    // Moneda
    CURRENCY: 'C$'
};

// Congelar para evitar modificaciones accidentales
Object.freeze(CONFIG);

console.log('✅ Configuración cargada:', CONFIG);
console.log('📡 API URL:', CONFIG.API_URL);
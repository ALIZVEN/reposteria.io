<?php
session_start();

class Auth {
    private $db;
    
    public function __construct() {
        require_once '../includes/db.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($usuario, $password) {
        $stmt = $this->db->prepare("SELECT * FROM administradores WHERE usuario = ? AND activo = 1");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['admin_rol'] = $admin['rol'];
                
                // Actualizar último acceso
                $stmt = $this->db->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt->bind_param("i", $admin['id']);
                $stmt->execute();
                
                // Registrar auditoría
                $this->registrarAuditoria($admin['id'], 'login', 'administradores', $admin['id'], 'Inicio de sesión');
                
                return true;
            }
        }
        return false;
    }
    
    public function checkAuth() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: login.php');
            exit;
        }
        return true;
    }
    
    public function checkRol($roles_permitidos = ['admin']) {
        if (!in_array($_SESSION['admin_rol'], $roles_permitidos)) {
            header('Location: index.php?error=permiso');
            exit;
        }
        return true;
    }
    
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->registrarAuditoria($_SESSION['admin_id'], 'logout', 'administradores', $_SESSION['admin_id'], 'Cierre de sesión');
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // ✅ FUNCIÓN CORREGIDA
    public function registrarAuditoria($admin_id, $accion, $tabla, $registro_id, $detalles) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->db->prepare("INSERT INTO auditoria (admin_id, accion, tabla, registro_id, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        
        // CORRECCIÓN: "isssss" = integer, string, string, integer, string, string
        $stmt->bind_param("isssss", $admin_id, $accion, $tabla, $registro_id, $detalles, $ip);
        
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Error en auditoría: " . $stmt->error);
            return false;
        }
    }
    
    public function getCurrentAdmin() {
        return [
            'id' => $_SESSION['admin_id'],
            'usuario' => $_SESSION['admin_usuario'],
            'nombre' => $_SESSION['admin_nombre'],
            'rol' => $_SESSION['admin_rol']
        ];
    }
}
?>
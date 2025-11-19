<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Middleware de Autenticación y Autorización
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function verificarRol($rolesPermitidos) {
    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = array($rolesPermitidos);
    }

    if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], $rolesPermitidos)) {
        header("Location: ../acceso_denegado.php");
        exit();
    }
}

/**
 * Verificar si el usuario es administrador
 */
function esAdministrador() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Administrador';
}

/**
 * Verificar si el usuario es supervisor o administrador
 */
function esSupervisorOAdmin() {
    return isset($_SESSION['usuario_rol']) &&
           ($_SESSION['usuario_rol'] === 'Administrador' || $_SESSION['usuario_rol'] === 'Supervisor');
}

/**
 * Obtener el ID del usuario actual
 */
function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener el nombre del usuario actual
 */
function obtenerUsuarioNombre() {
    return $_SESSION['usuario_nombre_completo'] ?? 'Usuario';
}

/**
 * Obtener el rol del usuario actual
 */
function obtenerUsuarioRol() {
    return $_SESSION['usuario_rol'] ?? null;
}

/**
 * Verificar permiso de acceso según el rol
 *
 * @param string $recurso El recurso que se quiere acceder
 * @param string $accion La acción que se quiere realizar (ver, crear, editar, eliminar)
 * @return bool
 */
function tienePermiso($recurso, $accion) {
    $rol = obtenerUsuarioRol();

    // Administrador tiene acceso total
    if ($rol === 'Administrador') {
        return true;
    }

    // Supervisor tiene acceso de lectura a todo y puede gestionar préstamos/devoluciones
    if ($rol === 'Supervisor') {
        if ($accion === 'ver') {
            return true;
        }

        if (in_array($recurso, ['prestamos', 'devoluciones', 'reportes', 'auditoria'])) {
            return in_array($accion, ['ver', 'crear']);
        }

        return false;
    }

    // Técnico solo puede ver sus propias herramientas y gestionar sus préstamos
    if ($rol === 'Tecnico') {
        if ($recurso === 'prestamos' && in_array($accion, ['ver', 'crear'])) {
            return true;
        }

        if ($recurso === 'devoluciones' && in_array($accion, ['ver', 'crear'])) {
            return true;
        }

        if (in_array($recurso, ['herramientas', 'cajas']) && $accion === 'ver') {
            return true;
        }

        return false;
    }

    return false;
}

/**
 * Mostrar mensaje de error de permisos
 */
function mensajePermisosDenegados() {
    return '<div class="alert alert-danger">No tiene permisos para realizar esta acción.</div>';
}

// Auto-verificar autenticación en archivos que incluyan este archivo
// (opcional, comentar si se quiere llamar manualmente)
// verificarAutenticacion();
?>

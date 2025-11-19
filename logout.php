<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Cerrar Sesión
 */

session_start();

// Registrar logout en auditoría si hay sesión activa
if (isset($_SESSION['usuario_id'])) {
    require_once 'conexion.php';

    $sql = "INSERT INTO AuditoriaHerramientas
            (TipoOperacion, TablaAfectada, IdUsuario, NombreUsuario, Observaciones)
            VALUES (?, ?, ?, ?, ?)";

    $params = array(
        'Logout',
        'Usuarios',
        $_SESSION['usuario_id'],
        $_SESSION['usuario_nombre'],
        'Cierre de sesión'
    );

    sqlsrv_query($conn, $sql, $params);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: login.php?logout=1");
exit();
?>

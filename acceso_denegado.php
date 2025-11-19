<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Página de Acceso Denegado
 */

session_start();
require_once 'auth.php';

$nombreUsuario = obtenerUsuarioNombre();
$rolUsuario = obtenerUsuarioRol();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Control de Herramientas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
        }

        .error-container h1 {
            color: #dc3545;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .error-container p {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .btn-group-center {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">🚫</div>
            <h1>Acceso Denegado</h1>
            <p>
                Lo sentimos, <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong>,
                no tienes permisos suficientes para acceder a este recurso.
            </p>
            <p>Tu rol actual es: <span class="badge badge-warning"><?php echo htmlspecialchars($rolUsuario); ?></span></p>

            <div class="btn-group-center">
                <a href="javascript:history.back()" class="btn btn-secondary">Volver Atrás</a>
                <a href="dashboard.php" class="btn btn-primary">Ir al Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

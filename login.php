<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Página de Login
 */

session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'conexion.php';

$error = '';
$mensaje = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreUsuario = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if (empty($nombreUsuario) || empty($contrasena)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        // Buscar el usuario en la base de datos (contraseña en texto plano)
        $sql = "SELECT IdUsuario, NombreUsuario, NombreCompleto, Email, Rol, Estado
                FROM Usuarios
                WHERE NombreUsuario = ? AND Contrasena = ? AND Estado = 'Activo'";

        $params = array($nombreUsuario, $contrasena);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $error = 'Error al conectar con la base de datos';
            error_log("Error en login: " . print_r(sqlsrv_errors(), true));
        } else {
            $usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($usuario) {
                // Login exitoso - Crear sesión
                $_SESSION['usuario_id'] = $usuario['IdUsuario'];
                $_SESSION['usuario_nombre'] = $usuario['NombreUsuario'];
                $_SESSION['usuario_nombre_completo'] = $usuario['NombreCompleto'];
                $_SESSION['usuario_email'] = $usuario['Email'];
                $_SESSION['usuario_rol'] = $usuario['Rol'];

                // Actualizar fecha de último acceso
                $sqlUpdate = "UPDATE Usuarios SET FechaUltimoAcceso = GETDATE() WHERE IdUsuario = ?";
                $paramsUpdate = array($usuario['IdUsuario']);
                sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

                // Registrar en auditoría
                $sqlAudit = "INSERT INTO AuditoriaHerramientas
                            (TipoOperacion, TablaAfectada, IdUsuario, NombreUsuario, Observaciones)
                            VALUES (?, ?, ?, ?, ?)";
                $paramsAudit = array('Login', 'Usuarios', $usuario['IdUsuario'], $usuario['NombreUsuario'], 'Inicio de sesión exitoso');
                sqlsrv_query($conn, $sqlAudit, $paramsAudit);

                // Redirigir al dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        }
    }
}

// Mostrar mensaje si viene de logout
if (isset($_GET['logout'])) {
    $mensaje = 'Sesión cerrada correctamente';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Control de Herramientas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .info-box p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔧 Control de Herramientas</h1>
            <p>Sistema de Gestión de Taller</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="info-box">
            <strong>Usuarios de prueba:</strong>
            <p><strong>Administrador:</strong> admin / admin123</p>
            <p><strong>Supervisor:</strong> supervisor / supervisor123</p>
            <p><strong>Técnico:</strong> tecnico / tecnico123</p>
        </div>
    </div>
</body>
</html>

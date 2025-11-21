<?php
/**
 * Sistema de Control de Herramientas de Taller
 * CRUD de Usuarios
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth.php';
require_once 'funciones.php';

// Verificar autenticación y permisos de administrador
verificarAutenticacion();
verificarRol('Administrador');

$accion = $_GET['accion'] ?? 'listar';
$id = $_GET['id'] ?? null;
$mensaje = '';
$tipoMensaje = 'success';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accionPost = $_POST['accion'] ?? '';

    if ($accionPost === 'crear') {
        $nombreUsuario = limpiarEntrada($_POST['nombre_usuario']);
        $contrasena = $_POST['contrasena'];
        $nombreCompleto = limpiarEntrada($_POST['nombre_completo']);
        $email = limpiarEntrada($_POST['email']);
        $rol = $_POST['rol'];
        $estado = $_POST['estado'];

        // Validar que el usuario no exista
        if (existeRegistro($conn, 'Usuarios', 'NombreUsuario', $nombreUsuario)) {
            $mensaje = 'El nombre de usuario ya existe';
            $tipoMensaje = 'error';
        } else {
            // Contraseña en texto plano (entorno local)
            $sql = "INSERT INTO Usuarios (NombreUsuario, Contrasena, NombreCompleto, Email, Rol, Estado)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $params = array($nombreUsuario, $contrasena, $nombreCompleto, $email, $rol, $estado);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt) {
                // Registrar en auditoría
                $sqlAudit = "INSERT INTO AuditoriaHerramientas
                            (TipoOperacion, TablaAfectada, IdUsuario, NombreUsuario, Observaciones)
                            VALUES (?, ?, ?, ?, ?)";
                $paramsAudit = array('Alta', 'Usuarios', obtenerUsuarioId(), obtenerUsuarioNombre(),
                                   "Usuario creado: $nombreUsuario ($rol)");
                sqlsrv_query($conn, $sqlAudit, $paramsAudit);

                redirigirConMensaje('usuarios.php', 'Usuario creado exitosamente', 'success');
            } else {
                $mensaje = 'Error al crear el usuario';
                $tipoMensaje = 'error';
            }
        }
    }

    if ($accionPost === 'editar') {
        $idUsuario = $_POST['id_usuario'];
        $nombreUsuario = limpiarEntrada($_POST['nombre_usuario']);
        $nombreCompleto = limpiarEntrada($_POST['nombre_completo']);
        $email = limpiarEntrada($_POST['email']);
        $rol = $_POST['rol'];
        $estado = $_POST['estado'];
        $cambiarContrasena = isset($_POST['cambiar_contrasena']) && $_POST['cambiar_contrasena'] === '1';

        if ($cambiarContrasena && !empty($_POST['contrasena'])) {
            // Contraseña en texto plano (entorno local)
            $sql = "UPDATE Usuarios
                    SET NombreUsuario = ?, Contrasena = ?, NombreCompleto = ?, Email = ?, Rol = ?, Estado = ?,
                        FechaActualizacion = GETDATE()
                    WHERE IdUsuario = ?";
            $params = array($nombreUsuario, $_POST['contrasena'], $nombreCompleto, $email, $rol, $estado, $idUsuario);
        } else {
            $sql = "UPDATE Usuarios
                    SET NombreUsuario = ?, NombreCompleto = ?, Email = ?, Rol = ?, Estado = ?,
                        FechaActualizacion = GETDATE()
                    WHERE IdUsuario = ?";
            $params = array($nombreUsuario, $nombreCompleto, $email, $rol, $estado, $idUsuario);
        }

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // Registrar en auditoría
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Edicion', 'Usuarios', $idUsuario, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               "Usuario actualizado: $nombreUsuario");
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('usuarios.php', 'Usuario actualizado exitosamente', 'success');
        } else {
            $mensaje = 'Error al actualizar el usuario';
            $tipoMensaje = 'error';
        }
    }

    if ($accionPost === 'eliminar') {
        $idUsuario = $_POST['id_usuario'];

        // No permitir eliminar el propio usuario
        if ($idUsuario == obtenerUsuarioId()) {
            redirigirConMensaje('usuarios.php', 'No puedes eliminar tu propio usuario', 'error');
        }

        // Cambiar estado a Inactivo en lugar de eliminar
        $sql = "UPDATE Usuarios SET Estado = 'Inactivo', FechaActualizacion = GETDATE() WHERE IdUsuario = ?";
        $params = array($idUsuario);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // Registrar en auditoría
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Baja', 'Usuarios', $idUsuario, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               'Usuario desactivado');
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('usuarios.php', 'Usuario desactivado exitosamente', 'success');
        } else {
            $mensaje = 'Error al desactivar el usuario';
            $tipoMensaje = 'error';
        }
    }
}

// Obtener datos según la acción
if ($accion === 'editar' && $id) {
    $sql = "SELECT * FROM Usuarios WHERE IdUsuario = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    $usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$usuario) {
        redirigirConMensaje('usuarios.php', 'Usuario no encontrado', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Control de Herramientas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>🔧 Sistema de Control de Herramientas de Taller</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars(obtenerUsuarioNombre()); ?></span>
                <a href="../logout.php" class="btn btn-sm btn-danger">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="../dashboard.php">Dashboard</a></li>
            <li><a href="herramientas.php">Herramientas</a></li>
            <li><a href="tecnicos.php">Técnicos</a></li>
            <li><a href="prestamos.php">Préstamos</a></li>
            <li><a href="cajas.php">Cajas</a></li>
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
            <li class="dropdown">
                <a href="#" class="active">Administración ▼</a>
                <div class="dropdown-content">
                    <a href="usuarios.php">Usuarios</a>
                    <a href="ubicaciones.php">Ubicaciones</a>
                    <a href="tipos_herramientas.php">Tipos de Herramientas</a>
                    <a href="auditoria.php">Auditoría</a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($accion === 'listar'): ?>
        <div class="card">
            <div class="card-header">
                <h2>Gestión de Usuarios</h2>
                <a href="?accion=nuevo" class="btn btn-primary">Nuevo Usuario</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM Usuarios ORDER BY FechaCreacion DESC";
                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['IdUsuario']; ?></td>
                            <td><?php echo htmlspecialchars($row['NombreUsuario']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreCompleto']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['Rol'] === 'Administrador' ? 'success' : ($row['Rol'] === 'Supervisor' ? 'warning' : 'info'); ?>">
                                    <?php echo htmlspecialchars($row['Rol']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $row['Estado'] === 'Activo' ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($row['Estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['FechaUltimoAcceso'] ? formatearFechaMostrar($row['FechaUltimoAcceso']) : 'Nunca'; ?></td>
                            <td>
                                <a href="?accion=editar&id=<?php echo $row['IdUsuario']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php if ($row['IdUsuario'] != obtenerUsuarioId() && $row['Estado'] === 'Activo'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar este usuario?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?php echo $row['IdUsuario']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Desactivar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay usuarios registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($accion === 'nuevo'): ?>
        <div class="card">
            <div class="card-header">
                <h2>Nuevo Usuario</h2>
                <a href="usuarios.php" class="btn btn-secondary">Volver al Listado</a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear">

                <div class="form-row">
                    <div class="form-group col-2">
                        <label for="nombre_usuario">Nombre de Usuario *</label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" required>
                    </div>

                    <div class="form-group col-2">
                        <label for="contrasena">Contraseña *</label>
                        <input type="password" id="contrasena" name="contrasena" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo *</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>

                <div class="form-row">
                    <div class="form-group col-2">
                        <label for="rol">Rol *</label>
                        <select id="rol" name="rol" required>
                            <option value="">Seleccione...</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Tecnico">Técnico</option>
                        </select>
                    </div>

                    <div class="form-group col-2">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <?php elseif ($accion === 'editar' && isset($usuario)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Editar Usuario</h2>
                <a href="usuarios.php" class="btn btn-secondary">Volver al Listado</a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario['IdUsuario']; ?>">

                <div class="form-row">
                    <div class="form-group col-2">
                        <label for="nombre_usuario">Nombre de Usuario *</label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['NombreUsuario']); ?>" required>
                    </div>

                    <div class="form-group col-2">
                        <label>
                            <input type="checkbox" id="cambiar_contrasena" name="cambiar_contrasena" value="1" onclick="togglePassword()">
                            Cambiar Contraseña
                        </label>
                        <input type="password" id="contrasena" name="contrasena" minlength="6" disabled style="margin-top: 10px;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo *</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($usuario['NombreCompleto']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['Email']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group col-2">
                        <label for="rol">Rol *</label>
                        <select id="rol" name="rol" required>
                            <option value="Administrador" <?php echo $usuario['Rol'] === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Supervisor" <?php echo $usuario['Rol'] === 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="Tecnico" <?php echo $usuario['Rol'] === 'Tecnico' ? 'selected' : ''; ?>>Técnico</option>
                        </select>
                    </div>

                    <div class="form-group col-2">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" required>
                            <option value="Activo" <?php echo $usuario['Estado'] === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo $usuario['Estado'] === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <script>
        function togglePassword() {
            var checkbox = document.getElementById('cambiar_contrasena');
            var password = document.getElementById('contrasena');
            password.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                password.value = '';
            }
        }
        </script>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

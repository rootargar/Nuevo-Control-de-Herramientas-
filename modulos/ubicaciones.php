<?php
/**
 * Sistema de Control de Herramientas de Taller
 * CRUD de Ubicaciones
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth.php';
require_once 'funciones.php';

// Verificar autenticación y permisos
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
        $nombre = limpiarEntrada($_POST['nombre']);
        $descripcion = limpiarEntrada($_POST['descripcion']);
        $estado = $_POST['estado'];

        $sql = "INSERT INTO Ubicaciones (Nombre, Descripcion, Estado)
                VALUES (?, ?, ?)";

        $params = array($nombre, $descripcion, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?)";
            $paramsAudit = array('Alta', 'Ubicaciones', obtenerUsuarioId(), obtenerUsuarioNombre(),
                               "Ubicación creada: $nombre");
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('ubicaciones.php', 'Ubicación creada exitosamente', 'success');
        } else {
            $mensaje = 'Error al crear la ubicación';
            $tipoMensaje = 'error';
        }
    }

    if ($accionPost === 'editar') {
        $idUbicacion = $_POST['id_ubicacion'];
        $nombre = limpiarEntrada($_POST['nombre']);
        $descripcion = limpiarEntrada($_POST['descripcion']);
        $estado = $_POST['estado'];

        $sql = "UPDATE Ubicaciones
                SET Nombre = ?, Descripcion = ?, Estado = ?, FechaActualizacion = GETDATE()
                WHERE IdUbicacion = ?";

        $params = array($nombre, $descripcion, $estado, $idUbicacion);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Edicion', 'Ubicaciones', $idUbicacion, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               "Ubicación actualizada: $nombre");
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('ubicaciones.php', 'Ubicación actualizada exitosamente', 'success');
        } else {
            $mensaje = 'Error al actualizar la ubicación';
            $tipoMensaje = 'error';
        }
    }

    if ($accionPost === 'eliminar') {
        $idUbicacion = $_POST['id_ubicacion'];

        // Verificar si hay herramientas con esta ubicación
        $sqlCheck = "SELECT COUNT(*) as total FROM Herramientas WHERE IdUbicacion = ?";
        $paramsCheck = array($idUbicacion);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($rowCheck['total'] > 0) {
            redirigirConMensaje('ubicaciones.php', 'No se puede eliminar: hay herramientas asignadas a esta ubicación', 'error');
        }

        $sql = "UPDATE Ubicaciones SET Estado = 'Inactiva', FechaActualizacion = GETDATE() WHERE IdUbicacion = ?";
        $params = array($idUbicacion);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Baja', 'Ubicaciones', $idUbicacion, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               'Ubicación desactivada');
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('ubicaciones.php', 'Ubicación desactivada exitosamente', 'success');
        } else {
            $mensaje = 'Error al desactivar la ubicación';
            $tipoMensaje = 'error';
        }
    }
}

// Obtener datos según la acción
if ($accion === 'editar' && $id) {
    $sql = "SELECT * FROM Ubicaciones WHERE IdUbicacion = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    $ubicacion = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$ubicacion) {
        redirigirConMensaje('ubicaciones.php', 'Ubicación no encontrada', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubicaciones - Control de Herramientas</title>
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
                <h2>Gestión de Ubicaciones</h2>
                <a href="?accion=nuevo" class="btn btn-primary">Nueva Ubicación</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Herramientas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT u.*, COUNT(h.IdHerramienta) as TotalHerramientas
                                FROM Ubicaciones u
                                LEFT JOIN Herramientas h ON u.IdUbicacion = h.IdUbicacion AND h.Estado = 'Activa'
                                GROUP BY u.IdUbicacion, u.Nombre, u.Descripcion, u.Estado, u.FechaCreacion, u.FechaActualizacion
                                ORDER BY u.Nombre";
                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['IdUbicacion']; ?></td>
                            <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['Estado'] === 'Activa' ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($row['Estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['TotalHerramientas']; ?></td>
                            <td>
                                <a href="?accion=editar&id=<?php echo $row['IdUbicacion']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php if ($row['TotalHerramientas'] == 0 && $row['Estado'] === 'Activa'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar esta ubicación?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_ubicacion" value="<?php echo $row['IdUbicacion']; ?>">
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
                            <td colspan="6" class="text-center">No hay ubicaciones registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($accion === 'nuevo'): ?>
        <div class="card">
            <div class="card-header">
                <h2>Nueva Ubicación</h2>
                <a href="ubicaciones.php" class="btn btn-secondary">Volver al Listado</a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear">

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="Activa">Activa</option>
                        <option value="Inactiva">Inactiva</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Guardar Ubicación</button>
                    <a href="ubicaciones.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <?php elseif ($accion === 'editar' && isset($ubicacion)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Editar Ubicación</h2>
                <a href="ubicaciones.php" class="btn btn-secondary">Volver al Listado</a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_ubicacion" value="<?php echo $ubicacion['IdUbicacion']; ?>">

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($ubicacion['Nombre']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($ubicacion['Descripcion']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="Activa" <?php echo $ubicacion['Estado'] === 'Activa' ? 'selected' : ''; ?>>Activa</option>
                        <option value="Inactiva" <?php echo $ubicacion['Estado'] === 'Inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Actualizar Ubicación</button>
                    <a href="ubicaciones.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

<?php
/**
 * Sistema de Control de Herramientas de Taller
 * CRUD de Tipos de Herramientas
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

        $sql = "INSERT INTO TiposHerramienta (Nombre, Descripcion, Estado)
                VALUES (?, ?, ?)";

        $params = array($nombre, $descripcion, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?)";
            $paramsAudit = array('Alta', 'TiposHerramienta', obtenerUsuarioId(), obtenerUsuarioNombre(),
                               "Tipo de herramienta creado: $nombre");
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('tipos_herramientas.php', 'Tipo de herramienta creado exitosamente', 'success');
        } else {
            $mensaje = 'Error al crear el tipo de herramienta';
            $tipoMensaje = 'error';
        }
    }

    if ($accionPost === 'editar') {
        $idTipo = $_POST['id_tipo'];
        $nombre = limpiarEntrada($_POST['nombre']);
        $descripcion = limpiarEntrada($_POST['descripcion']);
        $estado = $_POST['estado'];

        $sql = "UPDATE TiposHerramienta
                SET Nombre = ?, Descripcion = ?, Estado = ?, FechaActualizacion = GETDATE()
                WHERE IdTipo = ?";

        $params = array($nombre, $descripcion, $estado, $idTipo);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Edicion', 'TiposHerramienta', $idTipo, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               "Tipo de herramienta actualizado: $nombre");
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('tipos_herramientas.php', 'Tipo de herramienta actualizado exitosamente', 'success');
        } else {
            $mensaje = 'Error al actualizar el tipo de herramienta';
            $tipoMensaje = 'error';
        }
    }

    if ($accionPost === 'eliminar') {
        $idTipo = $_POST['id_tipo'];

        // Verificar si hay herramientas con este tipo
        $sqlCheck = "SELECT COUNT(*) as total FROM Herramientas WHERE IdTipo = ?";
        $paramsCheck = array($idTipo);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

        if ($rowCheck['total'] > 0) {
            redirigirConMensaje('tipos_herramientas.php', 'No se puede eliminar: hay herramientas con este tipo asignado', 'error');
        }

        $sql = "UPDATE TiposHerramienta SET Estado = 'Inactivo', FechaActualizacion = GETDATE() WHERE IdTipo = ?";
        $params = array($idTipo);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $sqlAudit = "INSERT INTO AuditoriaHerramientas
                        (TipoOperacion, TablaAfectada, IdRegistro, IdUsuario, NombreUsuario, Observaciones)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $paramsAudit = array('Baja', 'TiposHerramienta', $idTipo, obtenerUsuarioId(), obtenerUsuarioNombre(),
                               'Tipo de herramienta desactivado');
            sqlsrv_query($conn, $sqlAudit, $paramsAudit);

            redirigirConMensaje('tipos_herramientas.php', 'Tipo de herramienta desactivado exitosamente', 'success');
        } else {
            $mensaje = 'Error al desactivar el tipo de herramienta';
            $tipoMensaje = 'error';
        }
    }
}

// Obtener datos según la acción
if ($accion === 'editar' && $id) {
    $sql = "SELECT * FROM TiposHerramienta WHERE IdTipo = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    $tipo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$tipo) {
        redirigirConMensaje('tipos_herramientas.php', 'Tipo de herramienta no encontrado', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Herramientas - Control de Herramientas</title>
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
                <h2>Gestión de Tipos de Herramientas</h2>
                <a href="?accion=nuevo" class="btn btn-primary">Nuevo Tipo</a>
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
                        $sql = "SELECT t.*, COUNT(h.IdHerramienta) as TotalHerramientas
                                FROM TiposHerramienta t
                                LEFT JOIN Herramientas h ON t.IdTipo = h.IdTipo AND h.Estado = 'Activa'
                                GROUP BY t.IdTipo, t.Nombre, t.Descripcion, t.Estado, t.FechaCreacion, t.FechaActualizacion
                                ORDER BY t.Nombre";
                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['IdTipo']; ?></td>
                            <td><?php echo htmlspecialchars($row['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['Estado'] === 'Activo' ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($row['Estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['TotalHerramientas']; ?></td>
                            <td>
                                <a href="?accion=editar&id=<?php echo $row['IdTipo']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php if ($row['TotalHerramientas'] == 0 && $row['Estado'] === 'Activo'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de desactivar este tipo?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_tipo" value="<?php echo $row['IdTipo']; ?>">
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
                            <td colspan="6" class="text-center">No hay tipos de herramientas registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($accion === 'nuevo'): ?>
        <div class="card">
            <div class="card-header">
                <h2>Nuevo Tipo de Herramienta</h2>
                <a href="tipos_herramientas.php" class="btn btn-secondary">Volver al Listado</a>
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
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Guardar Tipo</button>
                    <a href="tipos_herramientas.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <?php elseif ($accion === 'editar' && isset($tipo)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Editar Tipo de Herramienta</h2>
                <a href="tipos_herramientas.php" class="btn btn-secondary">Volver al Listado</a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_tipo" value="<?php echo $tipo['IdTipo']; ?>">

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($tipo['Nombre']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($tipo['Descripcion']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <option value="Activo" <?php echo $tipo['Estado'] === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $tipo['Estado'] === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Actualizar Tipo</button>
                    <a href="tipos_herramientas.php" class="btn btn-secondary">Cancelar</a>
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

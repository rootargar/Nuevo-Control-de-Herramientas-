<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Préstamos de Herramientas
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/funciones.php';

$accion = $_GET['accion'] ?? 'listar';
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_post = $_POST['accion'] ?? '';

    switch ($accion_post) {
        case 'crear':
            $id_herramienta = intval($_POST['id_herramienta']);
            $id_tecnico = intval($_POST['id_tecnico']);
            $cantidad = intval($_POST['cantidad']);
            $fecha_devolucion_prevista = formatearFechaSQL($_POST['fecha_devolucion_prevista']);
            $observaciones = limpiarEntrada($_POST['observaciones']);

            // Validaciones
            if (empty($id_herramienta)) {
                $mensaje = 'Debe seleccionar una herramienta';
                $tipo_mensaje = 'error';
            } elseif (empty($id_tecnico)) {
                $mensaje = 'Debe seleccionar un técnico';
                $tipo_mensaje = 'error';
            } elseif (!esNumeroPositivo($cantidad) || $cantidad == 0) {
                $mensaje = 'La cantidad debe ser mayor a 0';
                $tipo_mensaje = 'error';
            } elseif (!verificarStockDisponible($conn, $id_herramienta, $cantidad)) {
                $mensaje = 'No hay stock disponible suficiente para realizar el préstamo';
                $tipo_mensaje = 'error';
            } else {
                // Crear préstamo
                $sql = "INSERT INTO Prestamos (IdHerramienta, IdTecnico, CantidadPrestada, FechaDevolucionPrevista, Observaciones)
                        VALUES (?, ?, ?, ?, ?)";
                $params = array($id_herramienta, $id_tecnico, $cantidad, $fecha_devolucion_prevista, $observaciones);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    // Actualizar stock disponible
                    if (actualizarExistenciaDisponible($conn, $id_herramienta, $cantidad, 'restar')) {
                        // Registrar auditoría
                        $herramienta = obtenerHerramienta($conn, $id_herramienta);
                        $tecnico = obtenerTecnico($conn, $id_tecnico);
                        $obs_auditoria = "Préstamo a {$tecnico['Nombre']} {$tecnico['Apellido']} - Cantidad: $cantidad";

                        registrarAuditoria($conn, $id_herramienta, 'Préstamo', $cantidad, $obs_auditoria);

                        $mensaje = 'Préstamo registrado exitosamente';
                        $tipo_mensaje = 'success';
                        $accion = 'listar';
                    } else {
                        $mensaje = 'Error al actualizar el stock disponible';
                        $tipo_mensaje = 'error';
                    }
                } else {
                    $mensaje = 'Error al registrar el préstamo: ' . print_r(sqlsrv_errors(), true);
                    $tipo_mensaje = 'error';
                }
            }
            break;

        case 'devolver':
            $id_prestamo = intval($_POST['id_prestamo']);
            $cantidad_devolver = intval($_POST['cantidad_devolver']);
            $observaciones = limpiarEntrada($_POST['observaciones']);

            // Obtener datos del préstamo
            $sql = "SELECT * FROM Prestamos WHERE IdPrestamo = ?";
            $params = array($id_prestamo);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $prestamo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$prestamo) {
                $mensaje = 'Préstamo no encontrado';
                $tipo_mensaje = 'error';
            } else {
                $cantidad_pendiente = $prestamo['CantidadPrestada'] - $prestamo['CantidadDevuelta'];

                if ($cantidad_devolver > $cantidad_pendiente) {
                    $mensaje = 'La cantidad a devolver excede la cantidad pendiente';
                    $tipo_mensaje = 'error';
                } else {
                    $nueva_cantidad_devuelta = $prestamo['CantidadDevuelta'] + $cantidad_devolver;
                    $nuevo_estado = ($nueva_cantidad_devuelta >= $prestamo['CantidadPrestada']) ? 'Devuelto' : 'Parcial';

                    // Actualizar préstamo
                    $sql = "UPDATE Prestamos
                            SET CantidadDevuelta = ?,
                                EstadoPrestamo = ?,
                                FechaDevolucionReal = CASE WHEN ? = 'Devuelto' THEN GETDATE() ELSE FechaDevolucionReal END,
                                Observaciones = CASE
                                    WHEN ? IS NOT NULL AND ? <> '' THEN Observaciones + CHAR(13) + CHAR(10) + 'Devolución: ' + ?
                                    ELSE Observaciones
                                END
                            WHERE IdPrestamo = ?";
                    $params = array($nueva_cantidad_devuelta, $nuevo_estado, $nuevo_estado, $observaciones, $observaciones, $observaciones, $id_prestamo);
                    $stmt = sqlsrv_query($conn, $sql, $params);

                    if ($stmt !== false) {
                        // Actualizar stock disponible
                        if (actualizarExistenciaDisponible($conn, $prestamo['IdHerramienta'], $cantidad_devolver, 'sumar')) {
                            // Registrar en tabla de devoluciones
                            $sql_dev = "INSERT INTO Devoluciones (IdPrestamo, IdHerramienta, IdTecnico, CantidadDevuelta, TipoDevolucion, MotivoObservaciones)
                                        VALUES (?, ?, ?, ?, 'Prestamo', ?)";
                            $params_dev = array($id_prestamo, $prestamo['IdHerramienta'], $prestamo['IdTecnico'], $cantidad_devolver, $observaciones);
                            sqlsrv_query($conn, $sql_dev, $params_dev);

                            // Registrar auditoría
                            registrarAuditoria($conn, $prestamo['IdHerramienta'], 'Devolución de préstamo', $cantidad_devolver,
                                             "Devolución de préstamo - Cantidad: $cantidad_devolver");

                            $mensaje = 'Devolución registrada exitosamente';
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje = 'Error al actualizar el stock disponible';
                            $tipo_mensaje = 'error';
                        }
                    } else {
                        $mensaje = 'Error al registrar la devolución: ' . print_r(sqlsrv_errors(), true);
                        $tipo_mensaje = 'error';
                    }
                }
            }
            $accion = 'listar';
            break;
    }
}

// Almacenar mensaje en sesión si existe
if ($mensaje) {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = $tipo_mensaje;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos - Control de Herramientas</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Sistema de Control de Herramientas de Taller</h1>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="herramientas.php">Herramientas</a></li>
            <li><a href="tecnicos.php">Técnicos</a></li>
            <li><a href="prestamos.php" class="active">Préstamos</a></li>
            <li><a href="cajas.php">Cajas</a></li>
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <?php if ($accion === 'listar'): ?>
            <!-- Listado de Préstamos -->
            <div class="card">
                <div class="card-header">
                    <h2>Préstamos de Herramientas</h2>
                    <p>Gestión de préstamos activos y devoluciones</p>
                </div>

                <div class="btn-group mb-3">
                    <a href="?accion=nuevo" class="btn btn-primary">Nuevo Préstamo</a>
                    <a href="?accion=historial" class="btn btn-secondary">Ver Historial</a>
                </div>

                <h3>Préstamos Activos</h3>
                <div class="table-responsive">
                    <table id="tablaPrestamos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Técnico</th>
                                <th>Herramienta</th>
                                <th>Cantidad</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Prevista</th>
                                <th>Días Activo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*,
                                    t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                    h.Nombre as NombreHerramienta,
                                    DATEDIFF(day, p.FechaPrestamo, GETDATE()) as DiasActivo,
                                    (p.CantidadPrestada - p.CantidadDevuelta) as CantidadPendiente
                                    FROM Prestamos p
                                    INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                                    WHERE p.EstadoPrestamo IN ('Activo', 'Parcial')
                                    ORDER BY p.FechaPrestamo DESC";
                            $stmt = sqlsrv_query($conn, $sql);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdPrestamo']; ?></td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['NombreHerramienta']); ?></strong></td>
                                <td>
                                    <?php if ($row['EstadoPrestamo'] === 'Parcial'): ?>
                                        <?php echo $row['CantidadPendiente']; ?> de <?php echo $row['CantidadPrestada']; ?>
                                    <?php else: ?>
                                        <?php echo $row['CantidadPendiente']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatearFechaMostrar($row['FechaPrestamo']); ?></td>
                                <td><?php echo $row['FechaDevolucionPrevista'] ? formatearFechaMostrar($row['FechaDevolucionPrevista']) : 'No definida'; ?></td>
                                <td>
                                    <?php
                                    $dias = $row['DiasActivo'];
                                    $clase = $dias > 30 ? 'badge-danger' : ($dias > 15 ? 'badge-warning' : 'badge-success');
                                    ?>
                                    <span class="badge <?php echo $clase; ?>"><?php echo $dias; ?> días</span>
                                </td>
                                <td>
                                    <?php
                                    $estado_clase = $row['EstadoPrestamo'] === 'Parcial' ? 'badge-warning' : 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['EstadoPrestamo']; ?></span>
                                </td>
                                <td>
                                    <a href="?accion=devolver&id=<?php echo $row['IdPrestamo']; ?>"
                                       class="btn btn-success btn-sm">Devolver</a>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay préstamos activos</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($accion === 'nuevo'): ?>
            <!-- Formulario Nuevo Préstamo -->
            <div class="card">
                <div class="card-header">
                    <h2>Nuevo Préstamo</h2>
                    <p>Registrar préstamo de herramienta a un técnico</p>
                </div>

                <form method="POST" onsubmit="return validarFormularioPrestamo(this) && prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="crear">

                    <div class="form-group">
                        <label for="id_herramienta">Herramienta *</label>
                        <select id="id_herramienta" name="id_herramienta" required>
                            <option value="">Seleccione una herramienta...</option>
                            <?php
                            $herramientas = obtenerHerramientasActivas($conn);
                            foreach ($herramientas as $h):
                                if ($h['ExistenciaDisponible'] > 0):
                            ?>
                            <option value="<?php echo $h['IdHerramienta']; ?>"
                                    data-stock="<?php echo $h['ExistenciaDisponible']; ?>">
                                <?php echo htmlspecialchars($h['Nombre']); ?> (Disponible: <?php echo $h['ExistenciaDisponible']; ?>)
                            </option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                        <span id="stockInfo" style="color: #27ae60; font-size: 0.9rem; margin-top: 0.5rem; display: block;"></span>
                    </div>

                    <div class="form-group">
                        <label for="id_tecnico">Técnico *</label>
                        <select id="id_tecnico" name="id_tecnico" required>
                            <option value="">Seleccione un técnico...</option>
                            <?php
                            $tecnicos = obtenerTecnicosActivos($conn);
                            foreach ($tecnicos as $t):
                            ?>
                            <option value="<?php echo $t['IdTecnico']; ?>">
                                <?php echo htmlspecialchars($t['Apellido'] . ', ' . $t['Nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="cantidad">Cantidad *</label>
                                <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="fecha_devolucion_prevista">Fecha Devolución Prevista</label>
                                <input type="datetime-local" id="fecha_devolucion_prevista" name="fecha_devolucion_prevista">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Registrar Préstamo</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

            <script>
                actualizarStockDisponible('id_herramienta', 'stockInfo');
            </script>

        <?php elseif ($accion === 'devolver'): ?>
            <?php
            $id_prestamo = intval($_GET['id']);
            $sql = "SELECT p.*,
                    t.Nombre + ' ' + t.Apellido as NombreTecnico,
                    h.Nombre as NombreHerramienta,
                    (p.CantidadPrestada - p.CantidadDevuelta) as CantidadPendiente
                    FROM Prestamos p
                    INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                    WHERE p.IdPrestamo = ?";
            $params = array($id_prestamo);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $prestamo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($prestamo):
            ?>
            <!-- Formulario Devolución -->
            <div class="card">
                <div class="card-header">
                    <h2>Devolver Herramienta</h2>
                    <p>Registrar devolución de préstamo</p>
                </div>

                <div class="alert alert-info">
                    <strong>Préstamo ID:</strong> <?php echo $prestamo['IdPrestamo']; ?><br>
                    <strong>Técnico:</strong> <?php echo htmlspecialchars($prestamo['NombreTecnico']); ?><br>
                    <strong>Herramienta:</strong> <?php echo htmlspecialchars($prestamo['NombreHerramienta']); ?><br>
                    <strong>Cantidad prestada:</strong> <?php echo $prestamo['CantidadPrestada']; ?><br>
                    <strong>Cantidad devuelta:</strong> <?php echo $prestamo['CantidadDevuelta']; ?><br>
                    <strong>Cantidad pendiente:</strong> <span class="badge badge-warning"><?php echo $prestamo['CantidadPendiente']; ?></span>
                </div>

                <form method="POST" onsubmit="return prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="devolver">
                    <input type="hidden" name="id_prestamo" value="<?php echo $prestamo['IdPrestamo']; ?>">

                    <div class="form-group">
                        <label for="cantidad_devolver">Cantidad a Devolver *</label>
                        <input type="number" id="cantidad_devolver" name="cantidad_devolver"
                               min="1" max="<?php echo $prestamo['CantidadPendiente']; ?>"
                               value="<?php echo $prestamo['CantidadPendiente']; ?>" required>
                        <small>Máximo: <?php echo $prestamo['CantidadPendiente']; ?></small>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones de Devolución</label>
                        <textarea id="observaciones" name="observaciones" rows="3"
                                  placeholder="Opcional: estado de la herramienta, daños, etc."></textarea>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Registrar Devolución</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Préstamo no encontrado.
            </div>
            <a href="?accion=listar" class="btn btn-secondary">Volver al listado</a>
            <?php endif; ?>

        <?php elseif ($accion === 'historial'): ?>
            <!-- Historial de Préstamos -->
            <div class="card">
                <div class="card-header">
                    <h2>Historial de Préstamos</h2>
                    <p>Todos los préstamos del sistema</p>
                </div>

                <div class="btn-group mb-3">
                    <a href="?accion=listar" class="btn btn-secondary">Ver Solo Activos</a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Técnico</th>
                                <th>Herramienta</th>
                                <th>Cantidad</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT TOP 100 p.*,
                                    t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                    h.Nombre as NombreHerramienta
                                    FROM Prestamos p
                                    INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                                    ORDER BY p.FechaPrestamo DESC";
                            $stmt = sqlsrv_query($conn, $sql);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdPrestamo']; ?></td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                                <td><?php echo $row['CantidadPrestada']; ?></td>
                                <td><?php echo formatearFechaMostrar($row['FechaPrestamo']); ?></td>
                                <td><?php echo $row['FechaDevolucionReal'] ? formatearFechaMostrar($row['FechaDevolucionReal']) : '-'; ?></td>
                                <td>
                                    <?php
                                    $estado_clase = $row['EstadoPrestamo'] === 'Devuelto' ? 'badge-success' :
                                                   ($row['EstadoPrestamo'] === 'Parcial' ? 'badge-warning' : 'badge-info');
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['EstadoPrestamo']; ?></span>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay préstamos registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

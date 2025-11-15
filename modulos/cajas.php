<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Cajas de Herramientas
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
            $nombre_caja = limpiarEntrada($_POST['nombre_caja']);
            $id_tecnico = !empty($_POST['id_tecnico']) ? intval($_POST['id_tecnico']) : null;
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $herramientas = $_POST['herramientas'] ?? array();
            $cantidades = $_POST['cantidades'] ?? array();

            // Validaciones
            if (empty($nombre_caja)) {
                $mensaje = 'El nombre de la caja es obligatorio';
                $tipo_mensaje = 'error';
            } elseif (empty($herramientas)) {
                $mensaje = 'Debe agregar al menos una herramienta a la caja';
                $tipo_mensaje = 'error';
            } else {
                // Verificar stock disponible para todas las herramientas
                $stock_insuficiente = false;
                foreach ($herramientas as $id_herramienta) {
                    $cantidad = intval($cantidades[$id_herramienta]);
                    if (!verificarStockDisponible($conn, $id_herramienta, $cantidad)) {
                        $herramienta = obtenerHerramienta($conn, $id_herramienta);
                        $mensaje = "Stock insuficiente para: {$herramienta['Nombre']}";
                        $tipo_mensaje = 'error';
                        $stock_insuficiente = true;
                        break;
                    }
                }

                if (!$stock_insuficiente) {
                    // Crear caja
                    $sql = "INSERT INTO Cajas (NombreCaja, IdTecnicoAsignado, Descripcion)
                            VALUES (?, ?, ?)";
                    $params = array($nombre_caja, $id_tecnico, $descripcion);
                    $stmt = sqlsrv_query($conn, $sql, $params);

                    if ($stmt !== false) {
                        // Obtener ID de la caja recién creada
                        $sql_id = "SELECT SCOPE_IDENTITY() as IdCaja";
                        $stmt_id = sqlsrv_query($conn, $sql_id);
                        $row_id = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
                        $id_caja = $row_id['IdCaja'];

                        // Agregar herramientas a la caja y actualizar stock
                        $todo_ok = true;
                        foreach ($herramientas as $id_herramienta) {
                            $cantidad = intval($cantidades[$id_herramienta]);

                            // Insertar en CajasDetalle
                            $sql_det = "INSERT INTO CajasDetalle (IdCaja, IdHerramienta, Cantidad)
                                        VALUES (?, ?, ?)";
                            $params_det = array($id_caja, $id_herramienta, $cantidad);
                            $stmt_det = sqlsrv_query($conn, $sql_det, $params_det);

                            if ($stmt_det !== false) {
                                // Actualizar stock disponible
                                if (!actualizarExistenciaDisponible($conn, $id_herramienta, $cantidad, 'restar')) {
                                    $todo_ok = false;
                                    break;
                                }

                                // Registrar auditoría
                                registrarAuditoria($conn, $id_herramienta, 'Asignación a caja', $cantidad,
                                                 "Asignado a caja: $nombre_caja");
                            } else {
                                $todo_ok = false;
                                break;
                            }
                        }

                        if ($todo_ok) {
                            $mensaje = 'Caja creada exitosamente';
                            $tipo_mensaje = 'success';
                            $accion = 'listar';
                        } else {
                            $mensaje = 'Error al agregar herramientas a la caja';
                            $tipo_mensaje = 'error';
                        }
                    } else {
                        $mensaje = 'Error al crear la caja: ' . print_r(sqlsrv_errors(), true);
                        $tipo_mensaje = 'error';
                    }
                }
            }
            break;

        case 'reasignar':
            $id_caja = intval($_POST['id_caja']);
            $nuevo_tecnico = !empty($_POST['nuevo_tecnico']) ? intval($_POST['nuevo_tecnico']) : null;

            $sql = "UPDATE Cajas SET IdTecnicoAsignado = ?, FechaActualizacion = GETDATE()
                    WHERE IdCaja = ?";
            $params = array($nuevo_tecnico, $id_caja);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt !== false) {
                $mensaje = 'Caja reasignada exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al reasignar la caja: ' . print_r(sqlsrv_errors(), true);
                $tipo_mensaje = 'error';
            }
            $accion = 'listar';
            break;

        case 'devolver_caja':
            $id_caja = intval($_POST['id_caja']);
            $tipo_devolucion = $_POST['tipo_devolucion']; // 'total' o 'parcial'
            $herramientas_devolver = $_POST['herramientas_devolver'] ?? array();
            $cantidades_devolver = $_POST['cantidades_devolver'] ?? array();
            $observaciones = limpiarEntrada($_POST['observaciones']);

            // Obtener datos de la caja
            $sql = "SELECT * FROM Cajas WHERE IdCaja = ?";
            $params = array($id_caja);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $caja = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$caja) {
                $mensaje = 'Caja no encontrada';
                $tipo_mensaje = 'error';
            } else {
                $todo_ok = true;

                if ($tipo_devolucion === 'total') {
                    // Devolver todas las herramientas de la caja
                    $sql_det = "SELECT * FROM CajasDetalle WHERE IdCaja = ?";
                    $params_det = array($id_caja);
                    $stmt_det = sqlsrv_query($conn, $sql_det, $params_det);

                    while ($row_det = sqlsrv_fetch_array($stmt_det, SQLSRV_FETCH_ASSOC)) {
                        // Actualizar stock
                        if (!actualizarExistenciaDisponible($conn, $row_det['IdHerramienta'], $row_det['Cantidad'], 'sumar')) {
                            $todo_ok = false;
                            break;
                        }

                        // Registrar devolución
                        $sql_dev = "INSERT INTO Devoluciones (IdCaja, IdHerramienta, IdTecnico, CantidadDevuelta, TipoDevolucion, MotivoObservaciones)
                                    VALUES (?, ?, ?, ?, 'Caja', ?)";
                        $params_dev = array($id_caja, $row_det['IdHerramienta'], $caja['IdTecnicoAsignado'], $row_det['Cantidad'], $observaciones);
                        sqlsrv_query($conn, $sql_dev, $params_dev);

                        // Registrar auditoría
                        registrarAuditoria($conn, $row_det['IdHerramienta'], 'Devolución de caja', $row_det['Cantidad'],
                                         "Devolución total de caja: {$caja['NombreCaja']}");
                    }

                    if ($todo_ok) {
                        // Eliminar detalles de la caja
                        $sql_del = "DELETE FROM CajasDetalle WHERE IdCaja = ?";
                        sqlsrv_query($conn, $sql_del, array($id_caja));

                        // Inactivar caja
                        $sql_upd = "UPDATE Cajas SET Estado = 'Inactiva', FechaActualizacion = GETDATE() WHERE IdCaja = ?";
                        sqlsrv_query($conn, $sql_upd, array($id_caja));

                        $mensaje = 'Devolución total registrada exitosamente. Caja inactivada.';
                        $tipo_mensaje = 'success';
                    }
                } else {
                    // Devolución parcial
                    foreach ($herramientas_devolver as $id_herramienta) {
                        $cantidad_devolver = intval($cantidades_devolver[$id_herramienta]);

                        // Obtener cantidad actual en caja
                        $sql_actual = "SELECT Cantidad FROM CajasDetalle WHERE IdCaja = ? AND IdHerramienta = ?";
                        $params_actual = array($id_caja, $id_herramienta);
                        $stmt_actual = sqlsrv_query($conn, $sql_actual, $params_actual);
                        $row_actual = sqlsrv_fetch_array($stmt_actual, SQLSRV_FETCH_ASSOC);

                        if ($row_actual && $cantidad_devolver <= $row_actual['Cantidad']) {
                            // Actualizar stock
                            if (actualizarExistenciaDisponible($conn, $id_herramienta, $cantidad_devolver, 'sumar')) {
                                // Actualizar o eliminar de CajasDetalle
                                $nueva_cantidad = $row_actual['Cantidad'] - $cantidad_devolver;

                                if ($nueva_cantidad > 0) {
                                    $sql_upd_det = "UPDATE CajasDetalle SET Cantidad = ? WHERE IdCaja = ? AND IdHerramienta = ?";
                                    $params_upd_det = array($nueva_cantidad, $id_caja, $id_herramienta);
                                    sqlsrv_query($conn, $sql_upd_det, $params_upd_det);
                                } else {
                                    $sql_del_det = "DELETE FROM CajasDetalle WHERE IdCaja = ? AND IdHerramienta = ?";
                                    $params_del_det = array($id_caja, $id_herramienta);
                                    sqlsrv_query($conn, $sql_del_det, $params_del_det);
                                }

                                // Registrar devolución
                                $sql_dev = "INSERT INTO Devoluciones (IdCaja, IdHerramienta, IdTecnico, CantidadDevuelta, TipoDevolucion, MotivoObservaciones)
                                            VALUES (?, ?, ?, ?, 'Caja', ?)";
                                $params_dev = array($id_caja, $id_herramienta, $caja['IdTecnicoAsignado'], $cantidad_devolver, $observaciones);
                                sqlsrv_query($conn, $sql_dev, $params_dev);

                                // Registrar auditoría
                                registrarAuditoria($conn, $id_herramienta, 'Devolución parcial de caja', $cantidad_devolver,
                                                 "Devolución parcial de caja: {$caja['NombreCaja']}");
                            } else {
                                $todo_ok = false;
                                break;
                            }
                        }
                    }

                    if ($todo_ok) {
                        $mensaje = 'Devolución parcial registrada exitosamente';
                        $tipo_mensaje = 'success';
                    }
                }

                if (!$todo_ok) {
                    $mensaje = 'Error al procesar la devolución';
                    $tipo_mensaje = 'error';
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
    <title>Cajas de Herramientas - Control de Herramientas</title>
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
            <li><a href="prestamos.php">Préstamos</a></li>
            <li><a href="cajas.php" class="active">Cajas</a></li>
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <?php if ($accion === 'listar'): ?>
            <!-- Listado de Cajas -->
            <div class="card">
                <div class="card-header">
                    <h2>Cajas de Herramientas</h2>
                    <p>Gestión de cajas y herramientas asignadas</p>
                </div>

                <div class="btn-group mb-3">
                    <a href="?accion=nuevo" class="btn btn-primary">Nueva Caja</a>
                </div>

                <div class="table-responsive">
                    <table id="tablaCajas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Caja</th>
                                <th>Técnico Asignado</th>
                                <th>Herramientas</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT c.*,
                                    CASE WHEN c.IdTecnicoAsignado IS NOT NULL
                                         THEN t.Nombre + ' ' + t.Apellido
                                         ELSE 'Sin asignar'
                                    END as NombreTecnico,
                                    (SELECT COUNT(*) FROM CajasDetalle cd WHERE cd.IdCaja = c.IdCaja) as NumHerramientas
                                    FROM Cajas c
                                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                                    ORDER BY c.Estado DESC, c.NombreCaja";
                            $stmt = sqlsrv_query($conn, $sql);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdCaja']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['NombreCaja']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $row['NumHerramientas']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                                <td>
                                    <?php
                                    $estado_clase = $row['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['Estado']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?accion=ver&id=<?php echo $row['IdCaja']; ?>"
                                           class="btn btn-primary btn-sm">Ver Contenido</a>
                                        <?php if ($row['Estado'] === 'Activa'): ?>
                                        <a href="?accion=reasignar&id=<?php echo $row['IdCaja']; ?>"
                                           class="btn btn-warning btn-sm">Reasignar</a>
                                        <a href="?accion=devolver&id=<?php echo $row['IdCaja']; ?>"
                                           class="btn btn-success btn-sm">Devolver</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay cajas registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($accion === 'nuevo'): ?>
            <!-- Formulario Nueva Caja -->
            <div class="card">
                <div class="card-header">
                    <h2>Nueva Caja de Herramientas</h2>
                    <p>Crear una nueva caja y asignar herramientas</p>
                </div>

                <form method="POST" onsubmit="return prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="crear">

                    <div class="form-group">
                        <label for="nombre_caja">Nombre de la Caja *</label>
                        <input type="text" id="nombre_caja" name="nombre_caja" required>
                    </div>

                    <div class="form-group">
                        <label for="id_tecnico">Técnico Asignado</label>
                        <select id="id_tecnico" name="id_tecnico">
                            <option value="">Sin asignar</option>
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

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="2"></textarea>
                    </div>

                    <hr>

                    <h3>Herramientas en la Caja</h3>

                    <div class="form-group">
                        <label for="select_herramienta">Agregar Herramienta</label>
                        <select id="select_herramienta">
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
                        <button type="button" class="btn btn-secondary btn-sm mt-1"
                                onclick="agregarHerramientaCaja('select_herramienta')">
                            Agregar a la Caja
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="tablaHerramientasCaja">
                            <thead>
                                <tr>
                                    <th>Herramienta</th>
                                    <th>Cantidad</th>
                                    <th>Stock Disponible</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center">No hay herramientas agregadas</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-2"><strong>Total de herramientas:</strong> <span id="totalHerramientas">0</span></p>

                    <div class="btn-group mt-3">
                        <button type="submit" class="btn btn-success">Crear Caja</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

        <?php elseif ($accion === 'ver'): ?>
            <?php
            $id_caja = intval($_GET['id']);
            $sql = "SELECT c.*,
                    CASE WHEN c.IdTecnicoAsignado IS NOT NULL
                         THEN t.Nombre + ' ' + t.Apellido
                         ELSE 'Sin asignar'
                    END as NombreTecnico
                    FROM Cajas c
                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                    WHERE c.IdCaja = ?";
            $params = array($id_caja);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $caja = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($caja):
            ?>
            <!-- Detalle de la Caja -->
            <div class="card">
                <div class="card-header">
                    <h2>Contenido de la Caja</h2>
                    <p><?php echo htmlspecialchars($caja['NombreCaja']); ?></p>
                </div>

                <div class="row">
                    <div class="col-2">
                        <p><strong>Técnico Asignado:</strong> <?php echo htmlspecialchars($caja['NombreTecnico']); ?></p>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($caja['Descripcion']); ?></p>
                    </div>
                    <div class="col-2">
                        <p><strong>Estado:</strong>
                            <span class="badge <?php echo $caja['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $caja['Estado']; ?>
                            </span>
                        </p>
                        <p><strong>Fecha Creación:</strong> <?php echo formatearFechaMostrar($caja['FechaCreacion']); ?></p>
                    </div>
                </div>

                <h3 class="mt-3">Herramientas en la Caja</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Herramienta</th>
                                <th>Cantidad en Caja</th>
                                <th>Ubicación</th>
                                <th>Fecha Asignación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_det = "SELECT cd.*, h.Nombre as NombreHerramienta, h.Ubicacion
                                        FROM CajasDetalle cd
                                        INNER JOIN Herramientas h ON cd.IdHerramienta = h.IdHerramienta
                                        WHERE cd.IdCaja = ?
                                        ORDER BY h.Nombre";
                            $params_det = array($caja['IdCaja']);
                            $stmt_det = sqlsrv_query($conn, $sql_det, $params_det);

                            if ($stmt_det !== false && sqlsrv_has_rows($stmt_det)):
                                while ($row_det = sqlsrv_fetch_array($stmt_det, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row_det['NombreHerramienta']); ?></strong></td>
                                <td><?php echo $row_det['Cantidad']; ?></td>
                                <td><?php echo htmlspecialchars($row_det['Ubicacion']); ?></td>
                                <td><?php echo formatearFechaMostrar($row_det['FechaAsignacion']); ?></td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay herramientas en esta caja</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="btn-group mt-3">
                    <?php if ($caja['Estado'] === 'Activa'): ?>
                    <a href="?accion=reasignar&id=<?php echo $caja['IdCaja']; ?>" class="btn btn-warning">Reasignar Técnico</a>
                    <a href="?accion=devolver&id=<?php echo $caja['IdCaja']; ?>" class="btn btn-success">Devolver Herramientas</a>
                    <?php endif; ?>
                    <a href="?accion=listar" class="btn btn-secondary">Volver</a>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Caja no encontrada.
            </div>
            <a href="?accion=listar" class="btn btn-secondary">Volver al listado</a>
            <?php endif; ?>

        <?php elseif ($accion === 'reasignar'): ?>
            <?php
            $id_caja = intval($_GET['id']);
            $sql = "SELECT c.*, t.Nombre + ' ' + t.Apellido as NombreTecnicoActual
                    FROM Cajas c
                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                    WHERE c.IdCaja = ?";
            $params = array($id_caja);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $caja = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($caja):
            ?>
            <!-- Formulario Reasignar Caja -->
            <div class="card">
                <div class="card-header">
                    <h2>Reasignar Caja de Herramientas</h2>
                    <p><?php echo htmlspecialchars($caja['NombreCaja']); ?></p>
                </div>

                <div class="alert alert-info">
                    <strong>Técnico actual:</strong> <?php echo htmlspecialchars($caja['NombreTecnicoActual'] ?? 'Sin asignar'); ?>
                </div>

                <form method="POST" onsubmit="return prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="reasignar">
                    <input type="hidden" name="id_caja" value="<?php echo $caja['IdCaja']; ?>">

                    <div class="form-group">
                        <label for="nuevo_tecnico">Nuevo Técnico Asignado</label>
                        <select id="nuevo_tecnico" name="nuevo_tecnico">
                            <option value="">Sin asignar</option>
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

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Reasignar Caja</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Caja no encontrada.
            </div>
            <a href="?accion=listar" class="btn btn-secondary">Volver al listado</a>
            <?php endif; ?>

        <?php elseif ($accion === 'devolver'): ?>
            <?php
            $id_caja = intval($_GET['id']);
            $sql = "SELECT c.*, t.Nombre + ' ' + t.Apellido as NombreTecnico
                    FROM Cajas c
                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                    WHERE c.IdCaja = ?";
            $params = array($id_caja);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $caja = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($caja):
            ?>
            <!-- Formulario Devolver Herramientas de Caja -->
            <div class="card">
                <div class="card-header">
                    <h2>Devolver Herramientas de Caja</h2>
                    <p><?php echo htmlspecialchars($caja['NombreCaja']); ?></p>
                </div>

                <form method="POST" onsubmit="return prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="devolver_caja">
                    <input type="hidden" name="id_caja" value="<?php echo $caja['IdCaja']; ?>">

                    <div class="form-group">
                        <label>Tipo de Devolución</label>
                        <select name="tipo_devolucion" id="tipo_devolucion" required onchange="toggleDevolucionParcial()">
                            <option value="total">Devolución Total (todas las herramientas)</option>
                            <option value="parcial">Devolución Parcial (seleccionar herramientas)</option>
                        </select>
                    </div>

                    <div id="seccion_parcial" style="display: none;">
                        <h3>Seleccionar Herramientas a Devolver</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Devolver</th>
                                        <th>Herramienta</th>
                                        <th>Cantidad en Caja</th>
                                        <th>Cantidad a Devolver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_det = "SELECT cd.*, h.Nombre as NombreHerramienta
                                                FROM CajasDetalle cd
                                                INNER JOIN Herramientas h ON cd.IdHerramienta = h.IdHerramienta
                                                WHERE cd.IdCaja = ?";
                                    $params_det = array($caja['IdCaja']);
                                    $stmt_det = sqlsrv_query($conn, $sql_det, $params_det);

                                    while ($row_det = sqlsrv_fetch_array($stmt_det, SQLSRV_FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="herramientas_devolver[]"
                                                   value="<?php echo $row_det['IdHerramienta']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row_det['NombreHerramienta']); ?></td>
                                        <td><?php echo $row_det['Cantidad']; ?></td>
                                        <td>
                                            <input type="number" name="cantidades_devolver[<?php echo $row_det['IdHerramienta']; ?>]"
                                                   min="1" max="<?php echo $row_det['Cantidad']; ?>"
                                                   value="<?php echo $row_det['Cantidad']; ?>"
                                                   style="width: 80px;">
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Registrar Devolución</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

            <script>
                function toggleDevolucionParcial() {
                    const tipo = document.getElementById('tipo_devolucion').value;
                    const seccion = document.getElementById('seccion_parcial');
                    seccion.style.display = tipo === 'parcial' ? 'block' : 'none';
                }
            </script>
            <?php else: ?>
            <div class="alert alert-error">
                Caja no encontrada.
            </div>
            <a href="?accion=listar" class="btn btn-secondary">Volver al listado</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

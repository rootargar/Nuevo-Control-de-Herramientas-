<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Catálogo de Herramientas (CRUD)
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
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $existencia_total = intval($_POST['existencia_total']);
            $existencia_disponible = intval($_POST['existencia_disponible']);
            $ubicacion = limpiarEntrada($_POST['ubicacion']);
            $estado = limpiarEntrada($_POST['estado']);

            // Validaciones
            if (empty($nombre)) {
                $mensaje = 'El nombre de la herramienta es obligatorio';
                $tipo_mensaje = 'error';
            } elseif (!esNumeroPositivo($existencia_total)) {
                $mensaje = 'La existencia total debe ser un número positivo';
                $tipo_mensaje = 'error';
            } elseif (!esNumeroPositivo($existencia_disponible)) {
                $mensaje = 'La existencia disponible debe ser un número positivo';
                $tipo_mensaje = 'error';
            } elseif ($existencia_disponible > $existencia_total) {
                $mensaje = 'La existencia disponible no puede ser mayor que la existencia total';
                $tipo_mensaje = 'error';
            } else {
                // Insertar herramienta
                $sql = "INSERT INTO Herramientas (Nombre, Descripcion, ExistenciaTotal, ExistenciaDisponible, Ubicacion, Estado)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $params = array($nombre, $descripcion, $existencia_total, $existencia_disponible, $ubicacion, $estado);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    // Obtener ID de la herramienta recién creada
                    $sql_id = "SELECT SCOPE_IDENTITY() as IdHerramienta";
                    $stmt_id = sqlsrv_query($conn, $sql_id);
                    $row_id = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC);
                    $idHerramienta = $row_id['IdHerramienta'];

                    // Registrar auditoría
                    registrarAuditoria($conn, $idHerramienta, 'Alta de herramienta', $existencia_total,
                                     "Nueva herramienta: $nombre - Stock inicial: $existencia_total");

                    $mensaje = 'Herramienta creada exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al crear la herramienta: ' . print_r(sqlsrv_errors(), true);
                    $tipo_mensaje = 'error';
                }
            }
            break;

        case 'editar':
            $id = intval($_POST['id']);
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $existencia_total = intval($_POST['existencia_total']);
            $existencia_disponible = intval($_POST['existencia_disponible']);
            $ubicacion = limpiarEntrada($_POST['ubicacion']);
            $estado = limpiarEntrada($_POST['estado']);

            // Validaciones
            if (empty($nombre)) {
                $mensaje = 'El nombre de la herramienta es obligatorio';
                $tipo_mensaje = 'error';
            } elseif (!esNumeroPositivo($existencia_total)) {
                $mensaje = 'La existencia total debe ser un número positivo';
                $tipo_mensaje = 'error';
            } elseif (!esNumeroPositivo($existencia_disponible)) {
                $mensaje = 'La existencia disponible debe ser un número positivo';
                $tipo_mensaje = 'error';
            } elseif ($existencia_disponible > $existencia_total) {
                $mensaje = 'La existencia disponible no puede ser mayor que la existencia total';
                $tipo_mensaje = 'error';
            } else {
                // Obtener datos anteriores para auditoría
                $herramienta_anterior = obtenerHerramienta($conn, $id);

                // Actualizar herramienta
                $sql = "UPDATE Herramientas
                        SET Nombre = ?, Descripcion = ?, ExistenciaTotal = ?, ExistenciaDisponible = ?,
                            Ubicacion = ?, Estado = ?, FechaActualizacion = GETDATE()
                        WHERE IdHerramienta = ?";
                $params = array($nombre, $descripcion, $existencia_total, $existencia_disponible, $ubicacion, $estado, $id);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    // Registrar auditoría
                    $cambios = "Modificación - ";
                    if ($herramienta_anterior['ExistenciaTotal'] != $existencia_total) {
                        $cambios .= "ExistenciaTotal: {$herramienta_anterior['ExistenciaTotal']} -> $existencia_total. ";
                    }
                    if ($herramienta_anterior['Estado'] != $estado) {
                        $cambios .= "Estado: {$herramienta_anterior['Estado']} -> $estado. ";
                    }

                    registrarAuditoria($conn, $id, 'Modificación de herramienta', null, $cambios);

                    $mensaje = 'Herramienta actualizada exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al actualizar la herramienta: ' . print_r(sqlsrv_errors(), true);
                    $tipo_mensaje = 'error';
                }
            }
            break;

        case 'eliminar':
            $id = intval($_POST['id']);

            // Verificar si la herramienta está en uso
            if (herramientaEnUso($conn, $id)) {
                $mensaje = 'No se puede eliminar la herramienta porque está en préstamo o asignada a una caja';
                $tipo_mensaje = 'error';
            } else {
                $herramienta = obtenerHerramienta($conn, $id);

                // Cambiar estado a Inactiva en lugar de eliminar
                $sql = "UPDATE Herramientas SET Estado = 'Inactiva', FechaActualizacion = GETDATE()
                        WHERE IdHerramienta = ?";
                $params = array($id);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    registrarAuditoria($conn, $id, 'Baja de herramienta', null,
                                     "Herramienta dada de baja: {$herramienta['Nombre']}");

                    $mensaje = 'Herramienta dada de baja exitosamente';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al dar de baja la herramienta: ' . print_r(sqlsrv_errors(), true);
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
    <title>Catálogo de Herramientas - Control de Herramientas</title>
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
            <li><a href="herramientas.php" class="active">Herramientas</a></li>
            <li><a href="tecnicos.php">Técnicos</a></li>
            <li><a href="prestamos.php">Préstamos</a></li>
            <li><a href="cajas.php">Cajas</a></li>
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <?php if ($accion === 'listar'): ?>
            <!-- Listado de Herramientas -->
            <div class="card">
                <div class="card-header">
                    <h2>Catálogo de Herramientas</h2>
                    <p>Gestión completa del inventario de herramientas</p>
                </div>

                <div class="btn-group mb-3">
                    <a href="?accion=nuevo" class="btn btn-primary">Nueva Herramienta</a>
                </div>

                <div class="table-responsive">
                    <table id="tablaHerramientas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Stock Total</th>
                                <th>Stock Disponible</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM Herramientas ORDER BY Nombre";
                            $stmt = sqlsrv_query($conn, $sql);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdHerramienta']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['Nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                                <td><?php echo $row['ExistenciaTotal']; ?></td>
                                <td>
                                    <?php
                                    $disponible = $row['ExistenciaDisponible'];
                                    $clase = $disponible == 0 ? 'badge-danger' : ($disponible < 5 ? 'badge-warning' : 'badge-success');
                                    ?>
                                    <span class="badge <?php echo $clase; ?>"><?php echo $disponible; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['Ubicacion']); ?></td>
                                <td>
                                    <?php
                                    $estado_clase = $row['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['Estado']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?accion=editar&id=<?php echo $row['IdHerramienta']; ?>"
                                           class="btn btn-warning btn-sm">Editar</a>
                                        <?php if ($row['Estado'] === 'Activa'): ?>
                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirmarEliminacion('¿Está seguro de dar de baja esta herramienta?')">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $row['IdHerramienta']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Dar de Baja</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay herramientas registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($accion === 'nuevo'): ?>
            <!-- Formulario Nueva Herramienta -->
            <div class="card">
                <div class="card-header">
                    <h2>Nueva Herramienta</h2>
                    <p>Registrar una nueva herramienta en el inventario</p>
                </div>

                <form method="POST" onsubmit="return validarFormularioHerramienta(this) && prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="crear">

                    <div class="form-group">
                        <label for="nombre">Nombre de la Herramienta *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="existencia_total">Existencia Total *</label>
                                <input type="number" id="existencia_total" name="existencia_total"
                                       min="0" value="0" required>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="existencia_disponible">Existencia Disponible *</label>
                                <input type="number" id="existencia_disponible" name="existencia_disponible"
                                       min="0" value="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Guardar Herramienta</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

        <?php elseif ($accion === 'editar'): ?>
            <?php
            $id = intval($_GET['id']);
            $herramienta = obtenerHerramienta($conn, $id);

            if ($herramienta):
            ?>
            <!-- Formulario Editar Herramienta -->
            <div class="card">
                <div class="card-header">
                    <h2>Editar Herramienta</h2>
                    <p>Modificar información de la herramienta</p>
                </div>

                <form method="POST" onsubmit="return validarFormularioHerramienta(this) && prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?php echo $herramienta['IdHerramienta']; ?>">

                    <div class="form-group">
                        <label for="nombre">Nombre de la Herramienta *</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?php echo htmlspecialchars($herramienta['Nombre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($herramienta['Descripcion']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="existencia_total">Existencia Total *</label>
                                <input type="number" id="existencia_total" name="existencia_total"
                                       min="0" value="<?php echo $herramienta['ExistenciaTotal']; ?>" required>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="existencia_disponible">Existencia Disponible *</label>
                                <input type="number" id="existencia_disponible" name="existencia_disponible"
                                       min="0" value="<?php echo $herramienta['ExistenciaDisponible']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion"
                               value="<?php echo htmlspecialchars($herramienta['Ubicacion']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="Activa" <?php echo $herramienta['Estado'] === 'Activa' ? 'selected' : ''; ?>>Activa</option>
                            <option value="Inactiva" <?php echo $herramienta['Estado'] === 'Inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Actualizar Herramienta</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Herramienta no encontrada.
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

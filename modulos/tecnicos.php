<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Técnicos (CRUD)
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
            $apellido = limpiarEntrada($_POST['apellido']);
            $telefono = limpiarEntrada($_POST['telefono']);
            $email = limpiarEntrada($_POST['email']);
            $estado = limpiarEntrada($_POST['estado']);

            // Validaciones
            if (empty($nombre)) {
                $mensaje = 'El nombre del técnico es obligatorio';
                $tipo_mensaje = 'error';
            } elseif (empty($apellido)) {
                $mensaje = 'El apellido del técnico es obligatorio';
                $tipo_mensaje = 'error';
            } else {
                // Insertar técnico
                $sql = "INSERT INTO Tecnicos (Nombre, Apellido, Telefono, Email, Estado)
                        VALUES (?, ?, ?, ?, ?)";
                $params = array($nombre, $apellido, $telefono, $email, $estado);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    $mensaje = 'Técnico creado exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al crear el técnico: ' . print_r(sqlsrv_errors(), true);
                    $tipo_mensaje = 'error';
                }
            }
            break;

        case 'editar':
            $id = intval($_POST['id']);
            $nombre = limpiarEntrada($_POST['nombre']);
            $apellido = limpiarEntrada($_POST['apellido']);
            $telefono = limpiarEntrada($_POST['telefono']);
            $email = limpiarEntrada($_POST['email']);
            $estado = limpiarEntrada($_POST['estado']);

            // Validaciones
            if (empty($nombre)) {
                $mensaje = 'El nombre del técnico es obligatorio';
                $tipo_mensaje = 'error';
            } elseif (empty($apellido)) {
                $mensaje = 'El apellido del técnico es obligatorio';
                $tipo_mensaje = 'error';
            } else {
                // Actualizar técnico
                $sql = "UPDATE Tecnicos
                        SET Nombre = ?, Apellido = ?, Telefono = ?, Email = ?, Estado = ?,
                            FechaActualizacion = GETDATE()
                        WHERE IdTecnico = ?";
                $params = array($nombre, $apellido, $telefono, $email, $estado, $id);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt !== false) {
                    $mensaje = 'Técnico actualizado exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al actualizar el técnico: ' . print_r(sqlsrv_errors(), true);
                    $tipo_mensaje = 'error';
                }
            }
            break;

        case 'eliminar':
            $id = intval($_POST['id']);

            // Verificar si el técnico tiene préstamos activos
            if (tecnicoTienePrestamos($conn, $id)) {
                $mensaje = 'No se puede dar de baja al técnico porque tiene préstamos activos';
                $tipo_mensaje = 'error';
            } else {
                // Verificar si tiene cajas asignadas
                $sql_cajas = "SELECT COUNT(*) as total FROM Cajas WHERE IdTecnicoAsignado = ? AND Estado = 'Activa'";
                $params_cajas = array($id);
                $stmt_cajas = sqlsrv_query($conn, $sql_cajas, $params_cajas);
                $row_cajas = sqlsrv_fetch_array($stmt_cajas, SQLSRV_FETCH_ASSOC);

                if ($row_cajas['total'] > 0) {
                    $mensaje = 'No se puede dar de baja al técnico porque tiene cajas asignadas';
                    $tipo_mensaje = 'error';
                } else {
                    // Cambiar estado a Inactivo
                    $sql = "UPDATE Tecnicos SET Estado = 'Inactivo', FechaActualizacion = GETDATE()
                            WHERE IdTecnico = ?";
                    $params = array($id);
                    $stmt = sqlsrv_query($conn, $sql, $params);

                    if ($stmt !== false) {
                        $mensaje = 'Técnico dado de baja exitosamente';
                        $tipo_mensaje = 'success';
                    } else {
                        $mensaje = 'Error al dar de baja el técnico: ' . print_r(sqlsrv_errors(), true);
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
    <title>Técnicos - Control de Herramientas</title>
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
            <li><a href="tecnicos.php" class="active">Técnicos</a></li>
            <li><a href="prestamos.php">Préstamos</a></li>
            <li><a href="cajas.php">Cajas</a></li>
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <?php if ($accion === 'listar'): ?>
            <!-- Listado de Técnicos -->
            <div class="card">
                <div class="card-header">
                    <h2>Técnicos del Taller</h2>
                    <p>Gestión de técnicos y sus herramientas asignadas</p>
                </div>

                <div class="btn-group mb-3">
                    <a href="?accion=nuevo" class="btn btn-primary">Nuevo Técnico</a>
                </div>

                <div class="table-responsive">
                    <table id="tablaTecnicos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Préstamos Activos</th>
                                <th>Cajas Asignadas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT t.*,
                                    (SELECT COUNT(*) FROM Prestamos p
                                     WHERE p.IdTecnico = t.IdTecnico AND p.EstadoPrestamo = 'Activo') as PrestamosActivos,
                                    (SELECT COUNT(*) FROM Cajas c
                                     WHERE c.IdTecnicoAsignado = t.IdTecnico AND c.Estado = 'Activa') as CajasAsignadas
                                    FROM Tecnicos t
                                    ORDER BY t.Apellido, t.Nombre";
                            $stmt = sqlsrv_query($conn, $sql);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdTecnico']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['Apellido'] . ', ' . $row['Nombre']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td>
                                    <?php if ($row['PrestamosActivos'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $row['PrestamosActivos']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['CajasAsignadas'] > 0): ?>
                                        <span class="badge badge-info"><?php echo $row['CajasAsignadas']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $estado_clase = $row['Estado'] === 'Activo' ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['Estado']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?accion=ver&id=<?php echo $row['IdTecnico']; ?>"
                                           class="btn btn-primary btn-sm">Ver Detalle</a>
                                        <a href="?accion=editar&id=<?php echo $row['IdTecnico']; ?>"
                                           class="btn btn-warning btn-sm">Editar</a>
                                        <?php if ($row['Estado'] === 'Activo' && $row['PrestamosActivos'] == 0 && $row['CajasAsignadas'] == 0): ?>
                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirmarEliminacion('¿Está seguro de dar de baja este técnico?')">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $row['IdTecnico']; ?>">
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
                                <td colspan="8" class="text-center">No hay técnicos registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($accion === 'nuevo'): ?>
            <!-- Formulario Nuevo Técnico -->
            <div class="card">
                <div class="card-header">
                    <h2>Nuevo Técnico</h2>
                    <p>Registrar un nuevo técnico en el sistema</p>
                </div>

                <form method="POST" onsubmit="return validarFormularioTecnico(this) && prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="crear">

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="apellido">Apellido *</label>
                                <input type="text" id="apellido" name="apellido" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono">
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Guardar Técnico</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

        <?php elseif ($accion === 'editar'): ?>
            <?php
            $id = intval($_GET['id']);
            $tecnico = obtenerTecnico($conn, $id);

            if ($tecnico):
            ?>
            <!-- Formulario Editar Técnico -->
            <div class="card">
                <div class="card-header">
                    <h2>Editar Técnico</h2>
                    <p>Modificar información del técnico</p>
                </div>

                <form method="POST" onsubmit="return validarFormularioTecnico(this) && prevenirEnvioDoble(this)">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?php echo $tecnico['IdTecnico']; ?>">

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
                                <input type="text" id="nombre" name="nombre"
                                       value="<?php echo htmlspecialchars($tecnico['Nombre']); ?>" required>
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="apellido">Apellido *</label>
                                <input type="text" id="apellido" name="apellido"
                                       value="<?php echo htmlspecialchars($tecnico['Apellido']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                       value="<?php echo htmlspecialchars($tecnico['Telefono']); ?>">
                            </div>
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($tecnico['Email']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="Activo" <?php echo $tecnico['Estado'] === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo $tecnico['Estado'] === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Actualizar Técnico</button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Técnico no encontrado.
            </div>
            <a href="?accion=listar" class="btn btn-secondary">Volver al listado</a>
            <?php endif; ?>

        <?php elseif ($accion === 'ver'): ?>
            <?php
            $id = intval($_GET['id']);
            $tecnico = obtenerTecnico($conn, $id);

            if ($tecnico):
            ?>
            <!-- Detalle del Técnico -->
            <div class="card">
                <div class="card-header">
                    <h2>Detalle del Técnico</h2>
                    <p><?php echo htmlspecialchars($tecnico['Nombre'] . ' ' . $tecnico['Apellido']); ?></p>
                </div>

                <div class="row">
                    <div class="col-2">
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($tecnico['Telefono']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($tecnico['Email']); ?></p>
                    </div>
                    <div class="col-2">
                        <p><strong>Estado:</strong>
                            <span class="badge <?php echo $tecnico['Estado'] === 'Activo' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $tecnico['Estado']; ?>
                            </span>
                        </p>
                        <p><strong>Fecha Registro:</strong> <?php echo formatearFechaMostrar($tecnico['FechaRegistro']); ?></p>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="?accion=editar&id=<?php echo $tecnico['IdTecnico']; ?>" class="btn btn-warning">Editar</a>
                    <a href="?accion=listar" class="btn btn-secondary">Volver</a>
                </div>
            </div>

            <!-- Préstamos Activos -->
            <div class="card">
                <div class="card-header">
                    <h2>Préstamos Activos</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Herramienta</th>
                                <th>Cantidad</th>
                                <th>Fecha Préstamo</th>
                                <th>Días Transcurridos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*, h.Nombre as NombreHerramienta,
                                    DATEDIFF(day, p.FechaPrestamo, GETDATE()) as DiasTranscurridos
                                    FROM Prestamos p
                                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                                    WHERE p.IdTecnico = ? AND p.EstadoPrestamo = 'Activo'
                                    ORDER BY p.FechaPrestamo DESC";
                            $params = array($tecnico['IdTecnico']);
                            $stmt = sqlsrv_query($conn, $sql, $params);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                                <td><?php echo $row['CantidadPrestada'] - $row['CantidadDevuelta']; ?></td>
                                <td><?php echo formatearFechaMostrar($row['FechaPrestamo']); ?></td>
                                <td><?php echo $row['DiasTranscurridos']; ?> días</td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center">No tiene préstamos activos</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cajas Asignadas -->
            <div class="card">
                <div class="card-header">
                    <h2>Cajas Asignadas</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Caja</th>
                                <th>Descripción</th>
                                <th>Herramientas en Caja</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT c.*,
                                    (SELECT COUNT(*) FROM CajasDetalle cd WHERE cd.IdCaja = c.IdCaja) as NumHerramientas
                                    FROM Cajas c
                                    WHERE c.IdTecnicoAsignado = ?
                                    ORDER BY c.NombreCaja";
                            $params = array($tecnico['IdTecnico']);
                            $stmt = sqlsrv_query($conn, $sql, $params);

                            if ($stmt !== false && sqlsrv_has_rows($stmt)):
                                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['NombreCaja']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                                <td><span class="badge badge-info"><?php echo $row['NumHerramientas']; ?></span></td>
                                <td>
                                    <span class="badge <?php echo $row['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['Estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center">No tiene cajas asignadas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-error">
                Técnico no encontrado.
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

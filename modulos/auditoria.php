<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo de Consulta de Auditoría
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth.php';
require_once 'funciones.php';

// Verificar autenticación y permisos
verificarAutenticacion();

// Solo Administradores y Supervisores pueden ver auditoría
if (!esSupervisorOAdmin()) {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Filtros
$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipoOperacion = $_GET['tipo_operacion'] ?? '';
$usuario = $_GET['usuario'] ?? '';

// Obtener usuarios para el filtro
$sqlUsuarios = "SELECT IdUsuario, NombreUsuario FROM Usuarios ORDER BY NombreUsuario";
$stmtUsuarios = sqlsrv_query($conn, $sqlUsuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - Control de Herramientas</title>
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
            <?php if (esAdministrador()): ?>
            <li class="dropdown">
                <a href="#">Administración ▼</a>
                <div class="dropdown-content">
                    <a href="usuarios.php">Usuarios</a>
                    <a href="ubicaciones.php">Ubicaciones</a>
                    <a href="tipos_herramientas.php">Tipos de Herramientas</a>
                    <a href="auditoria.php">Auditoría</a>
                </div>
            </li>
            <?php endif; ?>
            <li><a href="auditoria.php" class="active">Auditoría</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <div class="card">
            <div class="card-header">
                <h2>🔍 Auditoría del Sistema</h2>
                <p>Registro de todas las operaciones realizadas en el sistema</p>
            </div>

            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="fecha_desde">Fecha Desde</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $fechaDesde; ?>">
                    </div>

                    <div class="form-group col-4">
                        <label for="fecha_hasta">Fecha Hasta</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fechaHasta; ?>">
                    </div>

                    <div class="form-group col-4">
                        <label for="tipo_operacion">Tipo de Operación</label>
                        <select id="tipo_operacion" name="tipo_operacion">
                            <option value="">Todas</option>
                            <option value="Alta" <?php echo $tipoOperacion === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="Baja" <?php echo $tipoOperacion === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                            <option value="Edicion" <?php echo $tipoOperacion === 'Edicion' ? 'selected' : ''; ?>>Edición</option>
                            <option value="Prestamo" <?php echo $tipoOperacion === 'Prestamo' ? 'selected' : ''; ?>>Préstamo</option>
                            <option value="Devolucion" <?php echo $tipoOperacion === 'Devolucion' ? 'selected' : ''; ?>>Devolución</option>
                            <option value="AsignacionCaja" <?php echo $tipoOperacion === 'AsignacionCaja' ? 'selected' : ''; ?>>Asignación Caja</option>
                            <option value="RetiroCaja" <?php echo $tipoOperacion === 'RetiroCaja' ? 'selected' : ''; ?>>Retiro Caja</option>
                            <option value="Login" <?php echo $tipoOperacion === 'Login' ? 'selected' : ''; ?>>Login</option>
                            <option value="Logout" <?php echo $tipoOperacion === 'Logout' ? 'selected' : ''; ?>>Logout</option>
                        </select>
                    </div>

                    <div class="form-group col-4">
                        <label for="usuario">Usuario</label>
                        <select id="usuario" name="usuario">
                            <option value="">Todos</option>
                            <?php
                            if ($stmtUsuarios):
                                while ($rowUsuario = sqlsrv_fetch_array($stmtUsuarios, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <option value="<?php echo $rowUsuario['IdUsuario']; ?>" <?php echo $usuario == $rowUsuario['IdUsuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rowUsuario['NombreUsuario']); ?>
                            </option>
                            <?php
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="auditoria.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo Operación</th>
                            <th>Tabla</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Construir query con filtros
                        $sql = "SELECT
                                    a.IdAuditoria,
                                    a.TipoOperacion,
                                    a.TablaAfectada,
                                    a.FechaMovimiento,
                                    a.NombreUsuario,
                                    a.Observaciones
                                FROM AuditoriaHerramientas a
                                WHERE 1=1";

                        $params = array();

                        if ($fechaDesde) {
                            $sql .= " AND CAST(a.FechaMovimiento AS DATE) >= ?";
                            $params[] = $fechaDesde;
                        }

                        if ($fechaHasta) {
                            $sql .= " AND CAST(a.FechaMovimiento AS DATE) <= ?";
                            $params[] = $fechaHasta;
                        }

                        if ($tipoOperacion) {
                            $sql .= " AND a.TipoOperacion = ?";
                            $params[] = $tipoOperacion;
                        }

                        if ($usuario) {
                            $sql .= " AND a.IdUsuario = ?";
                            $params[] = $usuario;
                        }

                        $sql .= " ORDER BY a.FechaMovimiento DESC";

                        $stmt = sqlsrv_query($conn, $sql, $params);

                        if ($stmt && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                                $badgeClass = '';
                                switch($row['TipoOperacion']) {
                                    case 'Alta':
                                        $badgeClass = 'badge-success';
                                        break;
                                    case 'Baja':
                                        $badgeClass = 'badge-danger';
                                        break;
                                    case 'Edicion':
                                        $badgeClass = 'badge-warning';
                                        break;
                                    case 'Prestamo':
                                        $badgeClass = 'badge-info';
                                        break;
                                    case 'Devolucion':
                                        $badgeClass = 'badge-primary';
                                        break;
                                    default:
                                        $badgeClass = 'badge-secondary';
                                }
                        ?>
                        <tr>
                            <td><?php echo formatearFechaMostrar($row['FechaMovimiento']); ?></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($row['TipoOperacion']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['TablaAfectada']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreUsuario']); ?></td>
                            <td><?php echo htmlspecialchars($row['Observaciones']); ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron registros de auditoría</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Estadísticas de Auditoría</h3>
            </div>

            <?php
            // Obtener estadísticas
            $sqlStats = "SELECT
                            TipoOperacion,
                            COUNT(*) as Total
                        FROM AuditoriaHerramientas
                        WHERE CAST(FechaMovimiento AS DATE) BETWEEN ? AND ?
                        GROUP BY TipoOperacion
                        ORDER BY Total DESC";

            $paramsStats = array($fechaDesde, $fechaHasta);
            $stmtStats = sqlsrv_query($conn, $sqlStats, $paramsStats);
            ?>

            <div class="stats-grid">
                <?php if ($stmtStats && sqlsrv_has_rows($stmtStats)): ?>
                    <?php while ($rowStat = sqlsrv_fetch_array($stmtStats, SQLSRV_FETCH_ASSOC)): ?>
                    <div class="stat-card">
                        <h3><?php echo htmlspecialchars($rowStat['TipoOperacion']); ?></h3>
                        <div class="stat-value"><?php echo $rowStat['Total']; ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No hay estadísticas disponibles para el período seleccionado</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

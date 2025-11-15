<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Devoluciones (Historial)
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/funciones.php';

$accion = $_GET['accion'] ?? 'listar';
$filtro_tipo = $_GET['tipo'] ?? 'todos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluciones - Control de Herramientas</title>
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
            <li><a href="cajas.php">Cajas</a></li>
            <li><a href="devoluciones.php" class="active">Devoluciones</a></li>
            <li><a href="reportes.php">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <div class="card">
            <div class="card-header">
                <h2>Historial de Devoluciones</h2>
                <p>Registro completo de todas las devoluciones realizadas</p>
            </div>

            <div class="btn-group mb-3">
                <a href="?tipo=todos" class="btn <?php echo $filtro_tipo === 'todos' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Todas
                </a>
                <a href="?tipo=prestamo" class="btn <?php echo $filtro_tipo === 'prestamo' ? 'btn-primary' : 'btn-secondary'; ?>">
                    De Préstamos
                </a>
                <a href="?tipo=caja" class="btn <?php echo $filtro_tipo === 'caja' ? 'btn-primary' : 'btn-secondary'; ?>">
                    De Cajas
                </a>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="stats-grid mb-3">
                <?php
                // Total devoluciones
                $sql_total = "SELECT COUNT(*) as total FROM Devoluciones";
                $stmt_total = sqlsrv_query($conn, $sql_total);
                $row_total = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);

                // Devoluciones de préstamos
                $sql_prestamos = "SELECT COUNT(*) as total FROM Devoluciones WHERE TipoDevolucion = 'Prestamo'";
                $stmt_prestamos = sqlsrv_query($conn, $sql_prestamos);
                $row_prestamos = sqlsrv_fetch_array($stmt_prestamos, SQLSRV_FETCH_ASSOC);

                // Devoluciones de cajas
                $sql_cajas = "SELECT COUNT(*) as total FROM Devoluciones WHERE TipoDevolucion = 'Caja'";
                $stmt_cajas = sqlsrv_query($conn, $sql_cajas);
                $row_cajas = sqlsrv_fetch_array($stmt_cajas, SQLSRV_FETCH_ASSOC);

                // Cantidad total devuelta
                $sql_cantidad = "SELECT SUM(CantidadDevuelta) as total FROM Devoluciones";
                $stmt_cantidad = sqlsrv_query($conn, $sql_cantidad);
                $row_cantidad = sqlsrv_fetch_array($stmt_cantidad, SQLSRV_FETCH_ASSOC);
                ?>
                <div class="stat-card">
                    <h3>Total Devoluciones</h3>
                    <div class="stat-value"><?php echo $row_total['total']; ?></div>
                </div>
                <div class="stat-card success">
                    <h3>De Préstamos</h3>
                    <div class="stat-value"><?php echo $row_prestamos['total']; ?></div>
                </div>
                <div class="stat-card warning">
                    <h3>De Cajas</h3>
                    <div class="stat-value"><?php echo $row_cajas['total']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Unidades Devueltas</h3>
                    <div class="stat-value"><?php echo $row_cantidad['total'] ?? 0; ?></div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaDevoluciones">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Técnico</th>
                            <th>Herramienta</th>
                            <th>Cantidad</th>
                            <th>Referencia</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Construir consulta según el filtro
                        $sql = "SELECT d.*,
                                t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                h.Nombre as NombreHerramienta,
                                CASE
                                    WHEN d.TipoDevolucion = 'Prestamo' THEN 'Préstamo #' + CAST(d.IdPrestamo as VARCHAR)
                                    WHEN d.TipoDevolucion = 'Caja' THEN 'Caja #' + CAST(d.IdCaja as VARCHAR)
                                    ELSE 'N/A'
                                END as Referencia
                                FROM Devoluciones d
                                INNER JOIN Tecnicos t ON d.IdTecnico = t.IdTecnico
                                INNER JOIN Herramientas h ON d.IdHerramienta = h.IdHerramienta
                                WHERE 1=1";

                        if ($filtro_tipo === 'prestamo') {
                            $sql .= " AND d.TipoDevolucion = 'Prestamo'";
                        } elseif ($filtro_tipo === 'caja') {
                            $sql .= " AND d.TipoDevolucion = 'Caja'";
                        }

                        $sql .= " ORDER BY d.FechaDevolucion DESC";

                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt !== false && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['IdDevolucion']; ?></td>
                            <td><?php echo formatearFechaMostrar($row['FechaDevolucion']); ?></td>
                            <td>
                                <?php
                                $tipo_clase = $row['TipoDevolucion'] === 'Prestamo' ? 'badge-info' : 'badge-warning';
                                ?>
                                <span class="badge <?php echo $tipo_clase; ?>"><?php echo $row['TipoDevolucion']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['NombreHerramienta']); ?></strong></td>
                            <td><span class="badge badge-success"><?php echo $row['CantidadDevuelta']; ?></span></td>
                            <td><?php echo htmlspecialchars($row['Referencia']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['MotivoObservaciones'], 0, 50)); ?><?php echo strlen($row['MotivoObservaciones']) > 50 ? '...' : ''; ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay devoluciones registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Devoluciones Recientes por Técnico -->
        <div class="card">
            <div class="card-header">
                <h2>Devoluciones Recientes por Técnico</h2>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <th>Total Devoluciones</th>
                            <th>De Préstamos</th>
                            <th>De Cajas</th>
                            <th>Última Devolución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT
                                t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                COUNT(*) as TotalDevoluciones,
                                SUM(CASE WHEN d.TipoDevolucion = 'Prestamo' THEN 1 ELSE 0 END) as DevolucionesPrestamos,
                                SUM(CASE WHEN d.TipoDevolucion = 'Caja' THEN 1 ELSE 0 END) as DevolucionesCajas,
                                MAX(d.FechaDevolucion) as UltimaDevolucion
                                FROM Devoluciones d
                                INNER JOIN Tecnicos t ON d.IdTecnico = t.IdTecnico
                                GROUP BY t.IdTecnico, t.Nombre, t.Apellido
                                ORDER BY MAX(d.FechaDevolucion) DESC";

                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt !== false && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['NombreTecnico']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo $row['TotalDevoluciones']; ?></span></td>
                            <td><?php echo $row['DevolucionesPrestamos']; ?></td>
                            <td><?php echo $row['DevolucionesCajas']; ?></td>
                            <td><?php echo formatearFechaMostrar($row['UltimaDevolucion']); ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay datos disponibles</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Herramientas Más Devueltas -->
        <div class="card">
            <div class="card-header">
                <h2>Herramientas Más Devueltas</h2>
                <p>Top 10 herramientas con más devoluciones</p>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Herramienta</th>
                            <th>Total Devoluciones</th>
                            <th>Cantidad Total Devuelta</th>
                            <th>De Préstamos</th>
                            <th>De Cajas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT TOP 10
                                h.Nombre as NombreHerramienta,
                                COUNT(*) as TotalDevoluciones,
                                SUM(d.CantidadDevuelta) as CantidadTotal,
                                SUM(CASE WHEN d.TipoDevolucion = 'Prestamo' THEN 1 ELSE 0 END) as DevolucionesPrestamos,
                                SUM(CASE WHEN d.TipoDevolucion = 'Caja' THEN 1 ELSE 0 END) as DevolucionesCajas
                                FROM Devoluciones d
                                INNER JOIN Herramientas h ON d.IdHerramienta = h.IdHerramienta
                                GROUP BY h.IdHerramienta, h.Nombre
                                ORDER BY COUNT(*) DESC";

                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt !== false && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['NombreHerramienta']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo $row['TotalDevoluciones']; ?></span></td>
                            <td><span class="badge badge-success"><?php echo $row['CantidadTotal']; ?></span></td>
                            <td><?php echo $row['DevolucionesPrestamos']; ?></td>
                            <td><?php echo $row['DevolucionesCajas']; ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay datos disponibles</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="btn-group">
            <a href="prestamos.php?accion=devolver" class="btn btn-primary">Registrar Nueva Devolución</a>
            <a href="reportes.php?tipo=devoluciones" class="btn btn-secondary">Ver Reporte Completo</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

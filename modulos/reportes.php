<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Módulo: Reportes con Exportación CSV
 */

session_start();
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/funciones.php';

$tipo_reporte = $_GET['tipo'] ?? 'herramientas';
$exportar = $_GET['exportar'] ?? false;

// Procesar exportación CSV
if ($exportar === 'csv') {
    switch ($tipo_reporte) {
        case 'herramientas':
            $sql = "SELECT
                    IdHerramienta as 'ID',
                    Nombre as 'Nombre',
                    Descripcion as 'Descripción',
                    ExistenciaTotal as 'Stock Total',
                    ExistenciaDisponible as 'Stock Disponible',
                    Ubicacion as 'Ubicación',
                    Estado as 'Estado'
                    FROM Herramientas
                    ORDER BY Nombre";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Nombre', 'Descripción', 'Stock Total', 'Stock Disponible', 'Ubicación', 'Estado');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_herramientas', $encabezados, $datos);
            break;

        case 'prestamos':
            $sql = "SELECT
                    p.IdPrestamo as 'ID',
                    h.Nombre as 'Herramienta',
                    t.Nombre + ' ' + t.Apellido as 'Técnico',
                    p.CantidadPrestada as 'Cantidad',
                    CONVERT(VARCHAR, p.FechaPrestamo, 120) as 'Fecha Préstamo',
                    CASE WHEN p.FechaDevolucionReal IS NOT NULL
                         THEN CONVERT(VARCHAR, p.FechaDevolucionReal, 120)
                         ELSE 'Pendiente'
                    END as 'Fecha Devolución',
                    p.EstadoPrestamo as 'Estado'
                    FROM Prestamos p
                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                    INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                    ORDER BY p.FechaPrestamo DESC";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Herramienta', 'Técnico', 'Cantidad', 'Fecha Préstamo', 'Fecha Devolución', 'Estado');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_prestamos', $encabezados, $datos);
            break;

        case 'tecnicos':
            $sql = "SELECT
                    t.IdTecnico as 'ID',
                    t.Nombre + ' ' + t.Apellido as 'Técnico',
                    COUNT(DISTINCT p.IdPrestamo) as 'Préstamos Activos',
                    COUNT(DISTINCT c.IdCaja) as 'Cajas Asignadas',
                    t.Estado as 'Estado'
                    FROM Tecnicos t
                    LEFT JOIN Prestamos p ON t.IdTecnico = p.IdTecnico AND p.EstadoPrestamo = 'Activo'
                    LEFT JOIN Cajas c ON t.IdTecnico = c.IdTecnicoAsignado AND c.Estado = 'Activa'
                    GROUP BY t.IdTecnico, t.Nombre, t.Apellido, t.Estado
                    ORDER BY t.Apellido, t.Nombre";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Técnico', 'Préstamos Activos', 'Cajas Asignadas', 'Estado');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_tecnicos', $encabezados, $datos);
            break;

        case 'cajas':
            $sql = "SELECT
                    c.IdCaja as 'ID',
                    c.NombreCaja as 'Nombre Caja',
                    CASE WHEN c.IdTecnicoAsignado IS NOT NULL
                         THEN t.Nombre + ' ' + t.Apellido
                         ELSE 'Sin asignar'
                    END as 'Técnico',
                    COUNT(cd.IdHerramienta) as 'Número Herramientas',
                    c.Estado as 'Estado'
                    FROM Cajas c
                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                    LEFT JOIN CajasDetalle cd ON c.IdCaja = cd.IdCaja
                    GROUP BY c.IdCaja, c.NombreCaja, c.IdTecnicoAsignado, t.Nombre, t.Apellido, c.Estado
                    ORDER BY c.NombreCaja";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Nombre Caja', 'Técnico', 'Número Herramientas', 'Estado');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_cajas', $encabezados, $datos);
            break;

        case 'devoluciones':
            $sql = "SELECT
                    d.IdDevolucion as 'ID',
                    CONVERT(VARCHAR, d.FechaDevolucion, 120) as 'Fecha',
                    d.TipoDevolucion as 'Tipo',
                    t.Nombre + ' ' + t.Apellido as 'Técnico',
                    h.Nombre as 'Herramienta',
                    d.CantidadDevuelta as 'Cantidad',
                    d.MotivoObservaciones as 'Observaciones'
                    FROM Devoluciones d
                    INNER JOIN Tecnicos t ON d.IdTecnico = t.IdTecnico
                    INNER JOIN Herramientas h ON d.IdHerramienta = h.IdHerramienta
                    ORDER BY d.FechaDevolucion DESC";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Fecha', 'Tipo', 'Técnico', 'Herramienta', 'Cantidad', 'Observaciones');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_devoluciones', $encabezados, $datos);
            break;

        case 'auditoria':
            $sql = "SELECT
                    a.IdAuditoria as 'ID',
                    CONVERT(VARCHAR, a.FechaMovimiento, 120) as 'Fecha',
                    a.TipoMovimiento as 'Tipo Movimiento',
                    CASE WHEN a.IdHerramienta IS NOT NULL
                         THEN h.Nombre
                         ELSE 'N/A'
                    END as 'Herramienta',
                    a.Cantidad as 'Cantidad',
                    a.IdUsuario as 'Usuario',
                    a.Observaciones as 'Observaciones'
                    FROM AuditoriaHerramientas a
                    LEFT JOIN Herramientas h ON a.IdHerramienta = h.IdHerramienta
                    ORDER BY a.FechaMovimiento DESC";
            $stmt = sqlsrv_query($conn, $sql);
            $encabezados = array('ID', 'Fecha', 'Tipo Movimiento', 'Herramienta', 'Cantidad', 'Usuario', 'Observaciones');
            $datos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos[] = array_values($row);
            }
            exportarCSV('reporte_auditoria', $encabezados, $datos);
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Control de Herramientas</title>
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
            <li><a href="devoluciones.php">Devoluciones</a></li>
            <li><a href="reportes.php" class="active">Reportes</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Reportes del Sistema</h2>
                <p>Generar reportes con filtros y exportar a CSV</p>
            </div>

            <!-- Selector de Tipo de Reporte -->
            <div class="btn-group mb-3">
                <a href="?tipo=herramientas" class="btn <?php echo $tipo_reporte === 'herramientas' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Herramientas
                </a>
                <a href="?tipo=prestamos" class="btn <?php echo $tipo_reporte === 'prestamos' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Préstamos
                </a>
                <a href="?tipo=tecnicos" class="btn <?php echo $tipo_reporte === 'tecnicos' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Técnicos
                </a>
                <a href="?tipo=cajas" class="btn <?php echo $tipo_reporte === 'cajas' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Cajas
                </a>
                <a href="?tipo=devoluciones" class="btn <?php echo $tipo_reporte === 'devoluciones' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Devoluciones
                </a>
                <a href="?tipo=auditoria" class="btn <?php echo $tipo_reporte === 'auditoria' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Auditoría
                </a>
            </div>

            <?php if ($tipo_reporte === 'herramientas'): ?>
                <!-- Reporte de Herramientas -->
                <h3>Reporte de Inventario de Herramientas</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=herramientas&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Stock Total</th>
                                <th>Stock Disponible</th>
                                <th>En Préstamo</th>
                                <th>En Cajas</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT h.*,
                                    (SELECT ISNULL(SUM(p.CantidadPrestada - p.CantidadDevuelta), 0)
                                     FROM Prestamos p
                                     WHERE p.IdHerramienta = h.IdHerramienta AND p.EstadoPrestamo = 'Activo') as EnPrestamo,
                                    (SELECT ISNULL(SUM(cd.Cantidad), 0)
                                     FROM CajasDetalle cd
                                     INNER JOIN Cajas c ON cd.IdCaja = c.IdCaja
                                     WHERE cd.IdHerramienta = h.IdHerramienta AND c.Estado = 'Activa') as EnCajas
                                    FROM Herramientas h
                                    ORDER BY h.Nombre";
                            $stmt = sqlsrv_query($conn, $sql);

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
                                <td><?php echo $row['EnPrestamo']; ?></td>
                                <td><?php echo $row['EnCajas']; ?></td>
                                <td><?php echo htmlspecialchars($row['Ubicacion']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['Estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tipo_reporte === 'prestamos'): ?>
                <!-- Reporte de Préstamos -->
                <h3>Reporte de Préstamos</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=prestamos&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Técnico</th>
                                <th>Herramienta</th>
                                <th>Cantidad</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Prevista</th>
                                <th>Fecha Devolución</th>
                                <th>Días Transcurridos</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*,
                                    t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                    h.Nombre as NombreHerramienta,
                                    DATEDIFF(day, p.FechaPrestamo,
                                        CASE WHEN p.FechaDevolucionReal IS NOT NULL
                                             THEN p.FechaDevolucionReal
                                             ELSE GETDATE()
                                        END) as DiasTranscurridos
                                    FROM Prestamos p
                                    INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                                    INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                                    ORDER BY p.FechaPrestamo DESC";
                            $stmt = sqlsrv_query($conn, $sql);

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdPrestamo']; ?></td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                                <td><?php echo $row['CantidadPrestada']; ?></td>
                                <td><?php echo formatearFechaMostrar($row['FechaPrestamo']); ?></td>
                                <td><?php echo $row['FechaDevolucionPrevista'] ? formatearFechaMostrar($row['FechaDevolucionPrevista']) : 'N/A'; ?></td>
                                <td><?php echo $row['FechaDevolucionReal'] ? formatearFechaMostrar($row['FechaDevolucionReal']) : 'Pendiente'; ?></td>
                                <td><?php echo $row['DiasTranscurridos']; ?> días</td>
                                <td>
                                    <?php
                                    $estado_clase = $row['EstadoPrestamo'] === 'Devuelto' ? 'badge-success' :
                                                   ($row['EstadoPrestamo'] === 'Parcial' ? 'badge-warning' : 'badge-info');
                                    ?>
                                    <span class="badge <?php echo $estado_clase; ?>"><?php echo $row['EstadoPrestamo']; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tipo_reporte === 'tecnicos'): ?>
                <!-- Reporte de Técnicos -->
                <h3>Reporte de Técnicos y Herramientas Asignadas</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=tecnicos&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Técnico</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Préstamos Activos</th>
                                <th>Cajas Asignadas</th>
                                <th>Total Herramientas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT t.*,
                                    (SELECT COUNT(*) FROM Prestamos p
                                     WHERE p.IdTecnico = t.IdTecnico AND p.EstadoPrestamo = 'Activo') as PrestamosActivos,
                                    (SELECT COUNT(*) FROM Cajas c
                                     WHERE c.IdTecnicoAsignado = t.IdTecnico AND c.Estado = 'Activa') as CajasAsignadas,
                                    (SELECT ISNULL(SUM(p.CantidadPrestada - p.CantidadDevuelta), 0)
                                     FROM Prestamos p
                                     WHERE p.IdTecnico = t.IdTecnico AND p.EstadoPrestamo = 'Activo') as TotalPrestadas
                                    FROM Tecnicos t
                                    ORDER BY t.Apellido, t.Nombre";
                            $stmt = sqlsrv_query($conn, $sql);

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdTecnico']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['Apellido'] . ', ' . $row['Nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['Telefono']); ?></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td><span class="badge badge-info"><?php echo $row['PrestamosActivos']; ?></span></td>
                                <td><span class="badge badge-warning"><?php echo $row['CajasAsignadas']; ?></span></td>
                                <td><?php echo $row['TotalPrestadas']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['Estado'] === 'Activo' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['Estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tipo_reporte === 'cajas'): ?>
                <!-- Reporte de Cajas -->
                <h3>Reporte de Cajas de Herramientas</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=cajas&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Caja</th>
                                <th>Técnico Asignado</th>
                                <th>Herramientas</th>
                                <th>Total Unidades</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT c.*,
                                    CASE WHEN c.IdTecnicoAsignado IS NOT NULL
                                         THEN t.Nombre + ' ' + t.Apellido
                                         ELSE 'Sin asignar'
                                    END as NombreTecnico,
                                    (SELECT COUNT(*) FROM CajasDetalle cd WHERE cd.IdCaja = c.IdCaja) as NumHerramientas,
                                    (SELECT ISNULL(SUM(cd.Cantidad), 0) FROM CajasDetalle cd WHERE cd.IdCaja = c.IdCaja) as TotalUnidades
                                    FROM Cajas c
                                    LEFT JOIN Tecnicos t ON c.IdTecnicoAsignado = t.IdTecnico
                                    ORDER BY c.NombreCaja";
                            $stmt = sqlsrv_query($conn, $sql);

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdCaja']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['NombreCaja']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td><span class="badge badge-info"><?php echo $row['NumHerramientas']; ?></span></td>
                                <td><?php echo $row['TotalUnidades']; ?></td>
                                <td><?php echo htmlspecialchars($row['Descripcion']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['Estado'] === 'Activa' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['Estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tipo_reporte === 'devoluciones'): ?>
                <!-- Reporte de Devoluciones -->
                <h3>Reporte de Devoluciones</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=devoluciones&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
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
                                    ORDER BY d.FechaDevolucion DESC";
                            $stmt = sqlsrv_query($conn, $sql);

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdDevolucion']; ?></td>
                                <td><?php echo formatearFechaMostrar($row['FechaDevolucion']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['TipoDevolucion'] === 'Prestamo' ? 'badge-info' : 'badge-warning'; ?>">
                                        <?php echo $row['TipoDevolucion']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                                <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                                <td><?php echo $row['CantidadDevuelta']; ?></td>
                                <td><?php echo htmlspecialchars($row['Referencia']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['MotivoObservaciones'], 0, 50)); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($tipo_reporte === 'auditoria'): ?>
                <!-- Reporte de Auditoría -->
                <h3>Reporte de Auditoría de Movimientos</h3>
                <div class="btn-group mb-2 no-print">
                    <a href="?tipo=auditoria&exportar=csv" class="btn btn-success btn-sm">Exportar a CSV</a>
                    <button onclick="imprimirReporte()" class="btn btn-secondary btn-sm">Imprimir</button>
                </div>

                <div class="table-responsive">
                    <table id="tablaReporte">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo Movimiento</th>
                                <th>Herramienta</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT TOP 500 a.*,
                                    CASE WHEN a.IdHerramienta IS NOT NULL
                                         THEN h.Nombre
                                         ELSE 'N/A'
                                    END as NombreHerramienta
                                    FROM AuditoriaHerramientas a
                                    LEFT JOIN Herramientas h ON a.IdHerramienta = h.IdHerramienta
                                    ORDER BY a.FechaMovimiento DESC";
                            $stmt = sqlsrv_query($conn, $sql);

                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['IdAuditoria']; ?></td>
                                <td><?php echo formatearFechaMostrar($row['FechaMovimiento']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['TipoMovimiento']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                                <td><?php echo $row['Cantidad'] ?? 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($row['IdUsuario']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['Observaciones'], 0, 50)); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <p><small>Mostrando los últimos 500 registros</small></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>

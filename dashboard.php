<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Dashboard Principal (Requiere Autenticación)
 */

session_start();
require_once 'conexion.php';
require_once 'auth.php';
require_once 'modulos/funciones.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener estadísticas del sistema
$estadisticas = obtenerEstadisticas($conn);

// Obtener el nombre del usuario
$nombreUsuario = obtenerUsuarioNombre();
$rolUsuario = obtenerUsuarioRol();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Control de Herramientas</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>🔧 Sistema de Control de Herramientas de Taller</h1>
            <div class="user-info">
                <span>Bienvenido, <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong></span>
                <span class="badge badge-<?php echo $rolUsuario === 'Administrador' ? 'success' : ($rolUsuario === 'Supervisor' ? 'warning' : 'info'); ?>">
                    <?php echo htmlspecialchars($rolUsuario); ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-danger">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>

            <?php if (tienePermiso('herramientas', 'ver')): ?>
            <li><a href="modulos/herramientas.php">Herramientas</a></li>
            <?php endif; ?>

            <?php if (tienePermiso('tecnicos', 'ver')): ?>
            <li><a href="modulos/tecnicos.php">Técnicos</a></li>
            <?php endif; ?>

            <?php if (tienePermiso('prestamos', 'ver')): ?>
            <li><a href="modulos/prestamos.php">Préstamos</a></li>
            <?php endif; ?>

            <?php if (tienePermiso('cajas', 'ver')): ?>
            <li><a href="modulos/cajas.php">Cajas</a></li>
            <?php endif; ?>

            <?php if (tienePermiso('devoluciones', 'ver')): ?>
            <li><a href="modulos/devoluciones.php">Devoluciones</a></li>
            <?php endif; ?>

            <?php if (tienePermiso('reportes', 'ver')): ?>
            <li><a href="modulos/reportes.php">Reportes</a></li>
            <?php endif; ?>

            <?php if (esAdministrador()): ?>
            <li class="dropdown">
                <a href="#">Administración ▼</a>
                <div class="dropdown-content">
                    <a href="modulos/usuarios.php">Usuarios</a>
                    <a href="modulos/ubicaciones.php">Ubicaciones</a>
                    <a href="modulos/tipos_herramientas.php">Tipos de Herramientas</a>
                    <a href="modulos/auditoria.php">Auditoría</a>
                </div>
            </li>
            <?php endif; ?>

            <?php if (esSupervisorOAdmin()): ?>
            <li><a href="modulos/auditoria.php">Auditoría</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <?php mostrarMensajeSesion(); ?>

        <div class="welcome-section">
            <h2>Panel de Control - <?php echo htmlspecialchars($rolUsuario); ?></h2>
            <p>Gestiona eficientemente el inventario de herramientas, préstamos, cajas y técnicos de tu taller.</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Resumen del Sistema</h2>
                <p>Estado actual del inventario y operaciones</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Herramientas</h3>
                    <div class="stat-value"><?php echo $estadisticas['total_herramientas']; ?></div>
                </div>

                <div class="stat-card success">
                    <h3>Técnicos Activos</h3>
                    <div class="stat-value"><?php echo $estadisticas['total_tecnicos']; ?></div>
                </div>

                <div class="stat-card warning">
                    <h3>Préstamos Activos</h3>
                    <div class="stat-value"><?php echo $estadisticas['prestamos_activos']; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Cajas Activas</h3>
                    <div class="stat-value"><?php echo $estadisticas['cajas_activas']; ?></div>
                </div>
            </div>

            <?php if ($estadisticas['stock_bajo'] > 0 || $estadisticas['sin_stock'] > 0): ?>
            <div class="alert alert-warning">
                <strong>⚠ Atención:</strong>
                <?php if ($estadisticas['sin_stock'] > 0): ?>
                    Hay <?php echo $estadisticas['sin_stock']; ?> herramienta(s) sin stock disponible.
                <?php endif; ?>
                <?php if ($estadisticas['stock_bajo'] > 0): ?>
                    Hay <?php echo $estadisticas['stock_bajo']; ?> herramienta(s) con stock bajo (menos de 5 unidades).
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Accesos Rápidos</h2>
                    </div>
                    <div class="btn-group" style="flex-direction: column;">
                        <?php if (tienePermiso('herramientas', 'crear')): ?>
                        <a href="modulos/herramientas.php?accion=nuevo" class="btn btn-primary">Nueva Herramienta</a>
                        <?php endif; ?>

                        <?php if (tienePermiso('tecnicos', 'crear')): ?>
                        <a href="modulos/tecnicos.php?accion=nuevo" class="btn btn-primary">Nuevo Técnico</a>
                        <?php endif; ?>

                        <?php if (tienePermiso('prestamos', 'crear')): ?>
                        <a href="modulos/prestamos.php?accion=nuevo" class="btn btn-success">Registrar Préstamo</a>
                        <?php endif; ?>

                        <?php if (tienePermiso('cajas', 'crear')): ?>
                        <a href="modulos/cajas.php?accion=nuevo" class="btn btn-success">Crear Caja</a>
                        <?php endif; ?>

                        <?php if (tienePermiso('devoluciones', 'crear')): ?>
                        <a href="modulos/devoluciones.php?accion=nuevo" class="btn btn-warning">Registrar Devolución</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Reportes Rápidos</h2>
                    </div>
                    <div class="btn-group" style="flex-direction: column;">
                        <a href="modulos/reportes.php?tipo=herramientas" class="btn btn-secondary">Inventario de Herramientas</a>
                        <a href="modulos/reportes.php?tipo=prestamos" class="btn btn-secondary">Préstamos Activos</a>
                        <a href="modulos/reportes.php?tipo=tecnicos" class="btn btn-secondary">Herramientas por Técnico</a>
                        <a href="modulos/reportes.php?tipo=cajas" class="btn btn-secondary">Estado de Cajas</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (tienePermiso('prestamos', 'ver')): ?>
        <div class="card">
            <div class="card-header">
                <h2>Últimos Préstamos Activos</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <th>Herramienta</th>
                            <th>Cantidad</th>
                            <th>Fecha Préstamo</th>
                            <th>Días Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT TOP 5
                                    p.IdPrestamo,
                                    t.Nombre + ' ' + t.Apellido as NombreTecnico,
                                    h.Nombre as NombreHerramienta,
                                    p.CantidadPrestada - p.CantidadDevuelta as CantidadActual,
                                    p.FechaPrestamo,
                                    DATEDIFF(day, p.FechaPrestamo, GETDATE()) as DiasActivo
                                FROM Prestamos p
                                INNER JOIN Tecnicos t ON p.IdTecnico = t.IdTecnico
                                INNER JOIN Herramientas h ON p.IdHerramienta = h.IdHerramienta
                                WHERE p.EstadoPrestamo = 'Activo'
                                ORDER BY p.FechaPrestamo DESC";

                        $stmt = sqlsrv_query($conn, $sql);

                        if ($stmt !== false && sqlsrv_has_rows($stmt)):
                            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['NombreTecnico']); ?></td>
                            <td><?php echo htmlspecialchars($row['NombreHerramienta']); ?></td>
                            <td><?php echo $row['CantidadActual']; ?></td>
                            <td><?php echo formatearFechaMostrar($row['FechaPrestamo']); ?></td>
                            <td>
                                <?php
                                $dias = $row['DiasActivo'];
                                $clase = $dias > 30 ? 'badge-danger' : ($dias > 15 ? 'badge-warning' : 'badge-success');
                                ?>
                                <span class="badge <?php echo $clase; ?>"><?php echo $dias; ?> días</span>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay préstamos activos en este momento</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Sistema de Control de Herramientas de Taller. Todos los derechos reservados.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>

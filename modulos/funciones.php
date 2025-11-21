<?php
/**
 * Sistema de Control de Herramientas de Taller
 * Funciones Auxiliares y Auditoría
 */

require_once __DIR__ . '/../conexion.php';

/**
 * Registrar auditoría de movimientos
 */
function registrarAuditoria($conn, $idHerramienta, $tipoMovimiento, $cantidad = null, $observaciones = '') {
    // Obtener ID de usuario (nombre del equipo o IP)
    $idUsuario = obtenerIdentificadorUsuario();

    $sql = "INSERT INTO AuditoriaHerramientas
            (IdHerramienta, TipoMovimiento, Cantidad, IdUsuario, Observaciones)
            VALUES (?, ?, ?, ?, ?)";

    $params = array($idHerramienta, $tipoMovimiento, $cantidad, $idUsuario, $observaciones);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        error_log("Error al registrar auditoría: " . print_r(sqlsrv_errors(), true));
        return false;
    }

    return true;
}

/**
 * Obtener identificador de usuario (nombre del equipo o IP)
 */
function obtenerIdentificadorUsuario() {
    // Intentar obtener el nombre del equipo
    $nombreEquipo = gethostname();

    if ($nombreEquipo && $nombreEquipo !== false) {
        return $nombreEquipo;
    }

    // Si no se puede obtener el nombre, usar la IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDO';
    return $ip;
}

/**
 * Limpiar entrada de datos
 */
function limpiarEntrada($dato) {
    return htmlspecialchars(strip_tags(trim($dato)));
}

/**
 * Verificar stock disponible de una herramienta
 */
function verificarStockDisponible($conn, $idHerramienta, $cantidadRequerida) {
    $sql = "SELECT ExistenciaDisponible FROM Herramientas WHERE IdHerramienta = ?";
    $params = array($idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return false;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    return $row['ExistenciaDisponible'] >= $cantidadRequerida;
}

/**
 * Actualizar existencia disponible de herramienta
 */
function actualizarExistenciaDisponible($conn, $idHerramienta, $cantidad, $operacion = 'restar') {
    if ($operacion === 'restar') {
        $sql = "UPDATE Herramientas
                SET ExistenciaDisponible = ExistenciaDisponible - ?,
                    FechaActualizacion = GETDATE()
                WHERE IdHerramienta = ?";
    } else {
        $sql = "UPDATE Herramientas
                SET ExistenciaDisponible = ExistenciaDisponible + ?,
                    FechaActualizacion = GETDATE()
                WHERE IdHerramienta = ?";
    }

    $params = array($cantidad, $idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    return $stmt !== false;
}

/**
 * Obtener información de herramienta
 */
function obtenerHerramienta($conn, $idHerramienta) {
    $sql = "SELECT * FROM Herramientas WHERE IdHerramienta = ?";
    $params = array($idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return null;
    }

    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

/**
 * Obtener información de técnico
 */
function obtenerTecnico($conn, $idTecnico) {
    $sql = "SELECT * FROM Tecnicos WHERE IdTecnico = ?";
    $params = array($idTecnico);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return null;
    }

    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

/**
 * Obtener todas las herramientas activas
 */
function obtenerHerramientasActivas($conn) {
    $sql = "SELECT * FROM Herramientas WHERE Estado = 'Activa' ORDER BY Nombre";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return array();
    }

    $herramientas = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $herramientas[] = $row;
    }

    return $herramientas;
}

/**
 * Obtener todos los técnicos activos
 */
function obtenerTecnicosActivos($conn) {
    $sql = "SELECT * FROM Tecnicos WHERE Estado = 'Activo' ORDER BY Apellido, Nombre";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return array();
    }

    $tecnicos = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $tecnicos[] = $row;
    }

    return $tecnicos;
}

/**
 * Formatear fecha para SQL Server
 */
function formatearFechaSQL($fecha) {
    if (empty($fecha)) {
        return null;
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Formatear fecha para mostrar
 */
function formatearFechaMostrar($fecha) {
    if (!$fecha || !is_object($fecha)) {
        return '';
    }

    return $fecha->format('d/m/Y H:i');
}

/**
 * Validar número positivo
 */
function esNumeroPositivo($valor) {
    return is_numeric($valor) && $valor >= 0;
}

/**
 * Redirigir con mensaje
 */
function redirigirConMensaje($url, $mensaje, $tipo = 'success') {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = $tipo;
    header("Location: $url");
    exit();
}

/**
 * Mostrar mensaje de sesión
 */
function mostrarMensajeSesion() {
    if (isset($_SESSION['mensaje'])) {
        $tipo = $_SESSION['tipo_mensaje'] ?? 'info';
        $mensaje = $_SESSION['mensaje'];

        echo "<div class='alert alert-{$tipo}'>" . htmlspecialchars($mensaje) . "</div>";

        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
    }
}

/**
 * Obtener estadísticas del sistema
 */
function obtenerEstadisticas($conn) {
    $estadisticas = array(
        'total_herramientas' => 0,
        'total_tecnicos' => 0,
        'prestamos_activos' => 0,
        'cajas_activas' => 0,
        'stock_bajo' => 0,
        'sin_stock' => 0
    );

    // Total de herramientas
    $sql = "SELECT COUNT(*) as total FROM Herramientas WHERE Estado = 'Activa'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['total_herramientas'] = $row['total'];
        }
    }

    // Total de técnicos activos
    $sql = "SELECT COUNT(*) as total FROM Tecnicos WHERE Estado = 'Activo'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['total_tecnicos'] = $row['total'];
        }
    }

    // Préstamos activos
    $sql = "SELECT COUNT(*) as total FROM Prestamos WHERE EstadoPrestamo = 'Activo'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['prestamos_activos'] = $row['total'];
        }
    }

    // Cajas activas
    $sql = "SELECT COUNT(*) as total FROM Cajas WHERE Estado = 'Activa'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['cajas_activas'] = $row['total'];
        }
    }

    // Herramientas con stock bajo (menos de 5)
    $sql = "SELECT COUNT(*) as total FROM Herramientas
            WHERE Estado = 'Activa' AND ExistenciaDisponible < 5";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['stock_bajo'] = $row['total'];
        }
    }

    // Herramientas sin stock
    $sql = "SELECT COUNT(*) as total FROM Herramientas
            WHERE Estado = 'Activa' AND ExistenciaDisponible = 0";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $estadisticas['sin_stock'] = $row['total'];
        }
    }

    return $estadisticas;
}

/**
 * Exportar datos a CSV
 */
function exportarCSV($nombreArchivo, $encabezados, $datos) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Escribir encabezados
    fputcsv($output, $encabezados);

    // Escribir datos
    foreach ($datos as $fila) {
        fputcsv($output, $fila);
    }

    fclose($output);
    exit();
}

/**
 * Validar existencia de registro
 */
function existeRegistro($conn, $tabla, $campo, $valor) {
    $sql = "SELECT COUNT(*) as total FROM $tabla WHERE $campo = ?";
    $params = array($valor);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return false;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] > 0;
}

/**
 * Obtener nombre completo de técnico
 */
function obtenerNombreTecnico($conn, $idTecnico) {
    $tecnico = obtenerTecnico($conn, $idTecnico);
    if ($tecnico) {
        return $tecnico['Nombre'] . ' ' . $tecnico['Apellido'];
    }
    return 'Desconocido';
}

/**
 * Obtener nombre de herramienta
 */
function obtenerNombreHerramienta($conn, $idHerramienta) {
    $herramienta = obtenerHerramienta($conn, $idHerramienta);
    if ($herramienta) {
        return $herramienta['Nombre'];
    }
    return 'Desconocido';
}

/**
 * Verificar si un técnico tiene herramientas prestadas
 */
function tecnicoTienePrestamos($conn, $idTecnico) {
    $sql = "SELECT COUNT(*) as total FROM Prestamos
            WHERE IdTecnico = ? AND EstadoPrestamo = 'Activo'";
    $params = array($idTecnico);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return false;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] > 0;
}

/**
 * Verificar si una herramienta está en uso
 */
function herramientaEnUso($conn, $idHerramienta) {
    // Verificar préstamos activos
    $sql = "SELECT COUNT(*) as total FROM Prestamos
            WHERE IdHerramienta = ? AND EstadoPrestamo = 'Activo'";
    $params = array($idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row['total'] > 0) {
            return true;
        }
    }

    // Verificar si está en cajas
    $sql = "SELECT COUNT(*) as total FROM CajasDetalle
            WHERE IdHerramienta = ?";
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row['total'] > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Calcular cantidad total en préstamos activos
 */
function cantidadEnPrestamos($conn, $idHerramienta) {
    $sql = "SELECT SUM(CantidadPrestada - CantidadDevuelta) as total
            FROM Prestamos
            WHERE IdHerramienta = ? AND EstadoPrestamo = 'Activo'";
    $params = array($idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return 0;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] ?? 0;
}

/**
 * Calcular cantidad total en cajas
 */
function cantidadEnCajas($conn, $idHerramienta) {
    $sql = "SELECT SUM(cd.Cantidad) as total
            FROM CajasDetalle cd
            INNER JOIN Cajas c ON cd.IdCaja = c.IdCaja
            WHERE cd.IdHerramienta = ? AND c.Estado = 'Activa'";
    $params = array($idHerramienta);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return 0;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] ?? 0;
}

/**
 * Sanitizar nombre de archivo para descarga
 */
function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre);
    return $nombre . '_' . date('Y-m-d_His');
}

/**
 * Registrar error en log
 */
function registrarError($mensaje, $contexto = '') {
    $log = date('Y-m-d H:i:s') . " - ERROR: $mensaje";
    if ($contexto) {
        $log .= " - Contexto: $contexto";
    }
    error_log($log);
}
?>

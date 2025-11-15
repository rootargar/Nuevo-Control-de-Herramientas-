# Sistema de Control de Herramientas de Taller

Sistema completo de gestión de herramientas de taller desarrollado en PHP y SQL Server, diseñado para administrar inventarios, préstamos, cajas de herramientas, técnicos y devoluciones.

## Características Principales

### 1. Catálogo de Herramientas
- Alta, baja y modificación de herramientas
- Control de existencia total y disponible
- Validación de stock en tiempo real
- Estados activo/inactivo
- Ubicaciones personalizadas

### 2. Gestión de Técnicos
- Registro completo de técnicos
- Asignación de herramientas por préstamo o caja
- Visualización de herramientas asignadas
- Control de préstamos activos
- Historial de actividades

### 3. Préstamos de Herramientas
- Registro de préstamos individuales
- Validación automática de stock
- Control de devoluciones (total y parcial)
- Seguimiento de fechas previstas
- Alertas de préstamos prolongados
- Impide préstamos sin stock

### 4. Cajas de Herramientas
- Creación y gestión de cajas
- Asignación de técnico responsable
- Agregar múltiples herramientas
- Reasignación de cajas entre técnicos
- Devolución total o parcial
- Visualización del contenido

### 5. Devoluciones
- Registro de devoluciones de préstamos
- Devoluciones de cajas (total/parcial)
- Incremento automático de stock
- Historial completo
- Observaciones y motivos

### 6. Reportes
- Reporte de inventario de herramientas
- Préstamos activos e históricos
- Herramientas por técnico
- Estado de cajas
- Historial de devoluciones
- Auditoría de movimientos
- Exportación a CSV
- Función de impresión

### 7. Auditoría
- Registro automático de todos los movimientos
- Alta, baja y modificación
- Préstamos y devoluciones
- Asignaciones a cajas
- Identificación por nombre de equipo o IP
- Observaciones detalladas

## Tecnologías Utilizadas

- **Backend**: PHP (sin frameworks)
- **Base de datos**: Microsoft SQL Server
- **Frontend**: HTML5 + CSS3 + JavaScript vanilla
- **Diseño**: Minimalista y responsive

## Estructura del Proyecto

```
Nuevo-Control-de-Herramientas-/
├── conexion.php                    # Archivo de conexión a SQL Server
├── index.php                       # Página principal con dashboard
├── README.md                       # Este archivo
├── css/
│   └── estilos.css                # Estilos del sistema
├── js/
│   └── main.js                    # JavaScript para validaciones
├── modulos/
│   ├── funciones.php              # Funciones auxiliares y auditoría
│   ├── herramientas.php           # CRUD de herramientas
│   ├── tecnicos.php               # CRUD de técnicos
│   ├── prestamos.php              # Gestión de préstamos
│   ├── cajas.php                  # Gestión de cajas
│   ├── devoluciones.php           # Historial de devoluciones
│   └── reportes.php               # Reportes con exportación CSV
└── sql/
    └── crear_tablas.sql           # Script de creación de tablas
```

## Requisitos del Sistema

### Servidor Web
- PHP 7.4 o superior
- Extensión `php_sqlsrv` habilitada
- Extensión `php_pdo_sqlsrv` habilitada (opcional)

### Base de Datos
- Microsoft SQL Server 2012 o superior
- SQL Server Express compatible

### Cliente
- Navegador web moderno (Chrome, Firefox, Edge, Safari)
- JavaScript habilitado

## Instalación

### Paso 1: Configurar PHP con SQL Server

#### En Windows:

1. Descargar los drivers de Microsoft para PHP:
   - Visitar: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
   - Descargar la versión correspondiente a tu PHP

2. Copiar los archivos DLL al directorio de extensiones de PHP:
   ```
   php_sqlsrv_*.dll
   php_pdo_sqlsrv_*.dll
   ```

3. Habilitar las extensiones en `php.ini`:
   ```ini
   extension=php_sqlsrv_*.dll
   extension=php_pdo_sqlsrv_*.dll
   ```

4. Reiniciar el servidor web (Apache, IIS, etc.)

### Paso 2: Crear la Base de Datos

1. Abrir SQL Server Management Studio (SSMS)

2. Conectarse al servidor SQL Server

3. Verificar que existe la base de datos `CotizaKW` o crearla:
   ```sql
   CREATE DATABASE CotizaKW;
   GO
   ```

4. Ejecutar el script de creación de tablas:
   - Abrir el archivo `sql/crear_tablas.sql`
   - Ejecutar todo el script
   - Verificar que todas las tablas se crearon correctamente

### Paso 3: Configurar la Conexión

1. Editar el archivo `conexion.php` si es necesario:
   ```php
   $serverName = "KWSERVIFACT"; // Nombre del servidor
   $connectionOptions = array(
       "Database" => "CotizaKW",
       "Uid" => "sa",
       "PWD" => "tu_contraseña"
   );
   ```

2. Asegurarse de que el servidor SQL Server permita conexiones TCP/IP

### Paso 4: Desplegar el Sistema

#### Opción A: Servidor Local (XAMPP/WAMP)
1. Copiar la carpeta del proyecto a `htdocs` (XAMPP) o `www` (WAMP)
2. Acceder a: `http://localhost/Nuevo-Control-de-Herramientas-/`

#### Opción B: Servidor IIS
1. Crear un nuevo sitio web en IIS
2. Apuntar la ruta física a la carpeta del proyecto
3. Configurar el pool de aplicaciones para usar PHP
4. Acceder al sitio según la configuración de IIS

#### Opción C: Servidor Apache
1. Configurar el VirtualHost en Apache
2. Apuntar DocumentRoot a la carpeta del proyecto
3. Reiniciar Apache
4. Acceder según el ServerName configurado

### Paso 5: Verificar la Instalación

1. Abrir el navegador y acceder al sistema
2. Debería mostrar la página principal con el dashboard
3. Verificar que no hay errores de conexión
4. Intentar navegar por los diferentes módulos

## Uso del Sistema

### Primer Uso

1. **Registrar Herramientas**:
   - Ir a "Herramientas" → "Nueva Herramienta"
   - Completar todos los campos obligatorios
   - Definir stock total y disponible

2. **Registrar Técnicos**:
   - Ir a "Técnicos" → "Nuevo Técnico"
   - Ingresar nombre, apellido y datos de contacto

3. **Realizar Préstamos**:
   - Ir a "Préstamos" → "Nuevo Préstamo"
   - Seleccionar herramienta y técnico
   - Definir cantidad (valida stock automáticamente)

4. **Crear Cajas**:
   - Ir a "Cajas" → "Nueva Caja"
   - Asignar técnico (opcional)
   - Agregar herramientas a la caja

5. **Registrar Devoluciones**:
   - Desde "Préstamos", hacer clic en "Devolver"
   - O desde "Cajas", hacer clic en "Devolver Herramientas"

6. **Generar Reportes**:
   - Ir a "Reportes"
   - Seleccionar el tipo de reporte deseado
   - Opcionalmente exportar a CSV o imprimir

## Características de Seguridad

- **Validación de Stock**: Impide préstamos sin existencias disponibles
- **Auditoría Completa**: Todos los movimientos quedan registrados
- **Validación de Datos**: Previene inyección SQL con parámetros preparados
- **Sanitización**: Limpieza de entradas para prevenir XSS
- **Control de Integridad**: Validaciones antes de eliminar registros

## Validaciones Implementadas

### Herramientas
- Nombre obligatorio
- Stock disponible no puede exceder stock total
- No se puede eliminar si está en uso (préstamos o cajas)

### Técnicos
- Nombre y apellido obligatorios
- Validación de formato de email
- No se puede eliminar si tiene préstamos activos o cajas asignadas

### Préstamos
- Validación de stock disponible
- Cantidad mayor a cero
- Técnico y herramienta obligatorios
- Cantidad a devolver no puede exceder cantidad pendiente

### Cajas
- Debe contener al menos una herramienta
- Validación de stock para cada herramienta
- Control de cantidades al devolver

## Mantenimiento

### Respaldo de Base de Datos

Realizar respaldos periódicos de la base de datos:

```sql
BACKUP DATABASE CotizaKW
TO DISK = 'C:\Backups\CotizaKW_backup.bak'
WITH FORMAT, MEDIANAME = 'CotizaKW_Backup';
```

### Limpieza de Auditoría

Si la tabla de auditoría crece demasiado, puede limpiar registros antiguos:

```sql
-- Eliminar registros de auditoría mayores a 6 meses
DELETE FROM AuditoriaHerramientas
WHERE FechaMovimiento < DATEADD(month, -6, GETDATE());
```

## Solución de Problemas

### Error de Conexión a SQL Server

1. Verificar que SQL Server esté ejecutándose
2. Verificar que TCP/IP esté habilitado en SQL Server Configuration Manager
3. Verificar el nombre del servidor en `conexion.php`
4. Verificar credenciales de usuario

### Extensión sqlsrv no encontrada

1. Verificar que las DLL estén en la carpeta de extensiones
2. Verificar que `php.ini` tenga las extensiones habilitadas
3. Reiniciar el servidor web
4. Ejecutar `php -m` para ver extensiones cargadas

### Problemas de Permisos

1. Verificar permisos de escritura en la carpeta del proyecto
2. Verificar permisos del usuario SQL en la base de datos
3. Asegurarse de que el usuario tenga permisos de INSERT, UPDATE, DELETE

## Archivos del Sistema

### Tablas de la Base de Datos

1. **Herramientas**: Catálogo de herramientas
2. **Tecnicos**: Registro de técnicos
3. **Prestamos**: Préstamos de herramientas
4. **Cajas**: Cajas de herramientas
5. **CajasDetalle**: Contenido de las cajas
6. **Devoluciones**: Historial de devoluciones
7. **AuditoriaHerramientas**: Registro de auditoría

### Módulos PHP

- `conexion.php`: Conexión a base de datos
- `funciones.php`: Funciones auxiliares y auditoría
- `herramientas.php`: Gestión de herramientas
- `tecnicos.php`: Gestión de técnicos
- `prestamos.php`: Gestión de préstamos
- `cajas.php`: Gestión de cajas
- `devoluciones.php`: Historial de devoluciones
- `reportes.php`: Generación de reportes

## Personalización

### Modificar Colores

Editar el archivo `css/estilos.css` y cambiar las variables CSS:

```css
:root {
    --color-primario: #2c3e50;
    --color-secundario: #3498db;
    --color-exito: #27ae60;
    --color-peligro: #e74c3c;
    /* ... más colores ... */
}
```

### Agregar Campos Personalizados

1. Agregar columna en SQL Server:
   ```sql
   ALTER TABLE Herramientas
   ADD CampoPersonalizado NVARCHAR(100);
   ```

2. Modificar formularios en el módulo correspondiente
3. Actualizar consultas SQL para incluir el nuevo campo

## Exportación de Datos

El sistema permite exportar reportes a CSV:

1. Ir al módulo "Reportes"
2. Seleccionar el tipo de reporte
3. Hacer clic en "Exportar a CSV"
4. El archivo se descarga automáticamente

## Soporte

Para reportar problemas o solicitar mejoras:
- Documentar el error con capturas de pantalla
- Incluir mensaje de error completo
- Especificar versión de PHP y SQL Server

## Notas Importantes

- El sistema registra el nombre del equipo o IP como usuario en la auditoría
- Todos los movimientos quedan permanentemente registrados
- Las eliminaciones son lógicas (cambio de estado), no físicas
- Las fechas se manejan en formato SQL Server (DATETIME)
- El sistema valida automáticamente el stock antes de cualquier operación

## Licencia

Sistema desarrollado para uso interno de talleres y gestión de herramientas.

---

**Desarrollado con**: PHP + SQL Server + HTML + CSS + JavaScript

**Última actualización**: 2025

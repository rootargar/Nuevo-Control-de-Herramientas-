# 📘 Manual de Instalación - Sistema de Control de Herramientas de Taller

## 📋 Tabla de Contenidos
1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalación](#instalación)
3. [Configuración de la Base de Datos](#configuración-de-la-base-de-datos)
4. [Configuración del Sistema](#configuración-del-sistema)
5. [Primer Acceso](#primer-acceso)
6. [Estructura del Proyecto](#estructura-del-proyecto)
7. [Usuarios por Defecto](#usuarios-por-defecto)
8. [Solución de Problemas](#solución-de-problemas)
9. [Seguridad y Recomendaciones](#seguridad-y-recomendaciones)

---

## 🔧 Requisitos del Sistema

### Software Requerido
- **Servidor Web**: Apache 2.4+ o IIS 7.0+
- **PHP**: Versión 7.4 o superior
- **Base de Datos**: Microsoft SQL Server 2014 o superior
- **Extensiones PHP Requeridas**:
  - `php_sqlsrv` - Driver de SQL Server
  - `php_pdo_sqlsrv` - PDO para SQL Server
  - `php_mbstring` - Soporte multibyte
  - `php_openssl` - Encriptación

### Requisitos de Hardware (Mínimos)
- **Procesador**: 2 GHz o superior
- **RAM**: 4 GB mínimo (8 GB recomendado)
- **Disco Duro**: 500 MB de espacio libre
- **Red**: Conexión de red para acceso a SQL Server

---

## 📦 Instalación

### Paso 1: Descargar el Sistema
```bash
git clone https://github.com/rootargar/Nuevo-Control-de-Herramientas-.git
cd Nuevo-Control-de-Herramientas-
```

### Paso 2: Copiar al Servidor Web

#### Para Apache (XAMPP, WAMP, etc.)
```bash
# Copiar al directorio htdocs
cp -r Nuevo-Control-de-Herramientas- C:/xampp/htdocs/herramientas
```

#### Para IIS
1. Abrir IIS Manager
2. Crear un nuevo sitio web
3. Apuntar la ruta física a la carpeta del proyecto
4. Asignar un nombre y puerto (ej: `herramientas.local:8080`)

### Paso 3: Verificar Extensiones PHP

Editar `php.ini` y habilitar:
```ini
extension=php_sqlsrv_81_ts.dll
extension=php_pdo_sqlsrv_81_ts.dll
extension=php_mbstring.dll
extension=php_openssl.dll
```

**Reiniciar el servidor web después de modificar php.ini**

---

## 🗄️ Configuración de la Base de Datos

### Paso 1: Crear la Base de Datos

1. Abrir **SQL Server Management Studio (SSMS)**
2. Conectar al servidor SQL Server
3. Si la base de datos `CotizaKW` no existe, crearla:

```sql
CREATE DATABASE CotizaKW;
GO
```

### Paso 2: Ejecutar el Script de Creación de Tablas

1. Abrir el archivo `sql/crear_tablas.sql`
2. Conectar a la base de datos `CotizaKW`
3. Ejecutar el script completo (F5)

El script creará automáticamente:
- ✅ Todas las tablas necesarias
- ✅ Índices para optimización
- ✅ Restricciones y relaciones
- ✅ Usuarios de prueba
- ✅ Datos iniciales (ubicaciones, tipos de herramientas)

### Paso 3: Verificar Creación de Tablas

Ejecutar el siguiente query para verificar:
```sql
USE CotizaKW;
GO

SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;
GO
```

**Tablas Esperadas:**
- Usuarios
- Ubicaciones
- TiposHerramienta
- Herramientas
- Tecnicos
- Prestamos
- Cajas
- CajasDetalle
- Devoluciones
- AuditoriaHerramientas

---

## ⚙️ Configuración del Sistema

### Configurar Conexión a la Base de Datos

Editar el archivo `conexion.php`:

```php
<?php
$serverName = "NOMBRE_DEL_SERVIDOR"; // Cambiar por tu servidor
$connectionOptions = array(
    "Database" => "CotizaKW", // Nombre de tu base de datos
    "Uid" => "sa", // Usuario SQL Server
    "PWD" => "tu_contraseña" // Contraseña del usuario
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
```

### Ejemplos de Configuración

#### Servidor Local
```php
$serverName = "localhost"; // o "127.0.0.1"
```

#### Servidor Remoto
```php
$serverName = "192.168.1.100"; // IP del servidor
```

#### Servidor con Puerto Específico
```php
$serverName = "servidor.dominio.com,1433";
```

#### Autenticación de Windows
```php
$connectionOptions = array(
    "Database" => "CotizaKW"
    // No incluir Uid ni PWD para usar autenticación de Windows
);
```

---

## 🚀 Primer Acceso

### Paso 1: Acceder al Sistema

Abrir el navegador y navegar a:
```
http://localhost/herramientas/
```

El sistema redirigirá automáticamente a `login.php`

### Paso 2: Iniciar Sesión

Utilizar uno de los usuarios por defecto:

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin | admin123 | Administrador |
| supervisor | supervisor123 | Supervisor |
| tecnico | tecnico123 | Técnico |

### Paso 3: Cambiar Contraseñas

**⚠️ IMPORTANTE**: Por seguridad, cambiar las contraseñas inmediatamente después del primer acceso.

1. Iniciar sesión como `admin`
2. Ir a **Administración > Usuarios**
3. Editar cada usuario y cambiar su contraseña

---

## 📁 Estructura del Proyecto

```
Nuevo-Control-de-Herramientas-/
│
├── css/                         # Hojas de estilo
│   └── estilos.css             # Estilos principales del sistema
│
├── js/                          # JavaScript
│   └── main.js                 # Funciones y validaciones
│
├── modulos/                     # Módulos del sistema
│   ├── funciones.php           # Funciones auxiliares y auditoría
│   ├── herramientas.php        # CRUD de Herramientas
│   ├── tecnicos.php            # CRUD de Técnicos
│   ├── cajas.php               # CRUD de Cajas
│   ├── prestamos.php           # Módulo de Préstamos
│   ├── devoluciones.php        # Módulo de Devoluciones
│   ├── reportes.php            # Módulo de Reportes
│   ├── usuarios.php            # CRUD de Usuarios (Admin)
│   ├── ubicaciones.php         # CRUD de Ubicaciones (Admin)
│   ├── tipos_herramientas.php  # CRUD de Tipos (Admin)
│   └── auditoria.php           # Consulta de Auditoría
│
├── sql/                         # Scripts de base de datos
│   └── crear_tablas.sql        # Script completo de creación
│
├── conexion.php                 # Configuración de BD
├── auth.php                     # Autenticación y permisos
├── login.php                    # Página de login
├── logout.php                   # Cerrar sesión
├── dashboard.php                # Dashboard principal
├── index.php                    # Página de entrada
├── acceso_denegado.php         # Página de error de permisos
│
├── README.md                    # Documentación del sistema
├── MANUAL_INSTALACION.md        # Este archivo
└── MEJORAS_APLICADAS.md         # Lista de mejoras
```

---

## 👥 Usuarios por Defecto

### Administrador
- **Usuario**: `admin`
- **Contraseña**: `admin123`
- **Permisos**:
  - ✅ Acceso total al sistema
  - ✅ CRUD completo de todos los módulos
  - ✅ Gestión de usuarios
  - ✅ Configuración del sistema
  - ✅ Auditoría completa

### Supervisor
- **Usuario**: `supervisor`
- **Contraseña**: `supervisor123`
- **Permisos**:
  - ✅ Consultar inventario
  - ✅ Consultar préstamos y devoluciones
  - ✅ Registrar préstamos y devoluciones
  - ✅ Reportes por técnico o herramienta
  - ✅ Ver auditoría parcial
  - ❌ No puede modificar catálogos
  - ❌ No puede gestionar usuarios

### Técnico
- **Usuario**: `tecnico`
- **Contraseña**: `tecnico123`
- **Permisos**:
  - ✅ Ver herramientas asignadas a su caja
  - ✅ Solicitar préstamos
  - ✅ Registrar devoluciones
  - ✅ Ver su historial
  - ❌ No puede ver otros técnicos
  - ❌ No puede modificar inventario

---

## 🔍 Solución de Problemas

### Error: "Could not connect to SQL Server"

**Causas posibles:**
1. SQL Server no está ejecutándose
2. Credenciales incorrectas
3. Extensión `php_sqlsrv` no habilitada

**Soluciones:**
```bash
# Verificar estado de SQL Server
services.msc
# Buscar "SQL Server" y verificar que esté iniciado

# Verificar extensiones PHP
php -m | findstr sqlsrv

# Si no aparece, editar php.ini y habilitar:
extension=php_sqlsrv_81_ts.dll
```

### Error: "Invalid object name 'Usuarios'"

**Causa:** Las tablas no se crearon correctamente

**Solución:**
1. Abrir SSMS
2. Conectar a SQL Server
3. Ejecutar `sql/crear_tablas.sql` nuevamente

### Error de Permisos (403 Forbidden)

**Causa:** Permisos incorrectos en la carpeta

**Solución (Windows):**
```cmd
# Dar permisos de lectura/escritura a IIS_IUSRS
icacls "C:\inetpub\wwwroot\herramientas" /grant IIS_IUSRS:(OI)(CI)F /T
```

**Solución (Apache):**
- Verificar que el usuario de Apache tenga permisos de lectura

### Página en Blanco

**Causa:** Error de PHP no mostrado

**Solución:**
1. Editar `php.ini`:
```ini
display_errors = On
error_reporting = E_ALL
```
2. Reiniciar el servidor web
3. Verificar logs de errores en `php_error.log`

---

## 🔐 Seguridad y Recomendaciones

### Seguridad Básica

1. **Cambiar contraseñas por defecto** inmediatamente
2. **Usar contraseñas seguras**: mínimo 12 caracteres, letras, números y símbolos
3. **Actualizar regularmente** el sistema operativo y SQL Server
4. **Backup regular** de la base de datos

### Backups de Base de Datos

#### Backup Manual (SSMS)
1. Click derecho en `CotizaKW`
2. Tasks > Back Up...
3. Seleccionar ubicación y nombre
4. Click OK

#### Backup Automático (T-SQL)
```sql
BACKUP DATABASE [CotizaKW]
TO DISK = 'C:\Backups\CotizaKW_Full.bak'
WITH FORMAT,
     MEDIANAME = 'SQLServerBackups',
     NAME = 'Full Backup of CotizaKW';
GO
```

### Configuración de SQL Server

#### Habilitar Autenticación Mixta
1. Abrir SSMS
2. Click derecho en servidor > Properties
3. Security > Server authentication
4. Seleccionar "SQL Server and Windows Authentication mode"
5. Reiniciar SQL Server

### Permisos Recomendados

No usar la cuenta `sa` en producción. Crear un usuario específico:

```sql
USE master;
GO

CREATE LOGIN herramientas_app WITH PASSWORD = 'ContraseñaSegura123!';
GO

USE CotizaKW;
GO

CREATE USER herramientas_app FOR LOGIN herramientas_app;
GO

ALTER ROLE db_datareader ADD MEMBER herramientas_app;
ALTER ROLE db_datawriter ADD MEMBER herramientas_app;
GO
```

Luego actualizar `conexion.php`:
```php
"Uid" => "herramientas_app",
"PWD" => "ContraseñaSegura123!"
```

### Seguridad del Servidor Web

#### Apache (.htaccess)
Crear `.htaccess` en la raíz del proyecto:
```apache
# Proteger archivos sensibles
<FilesMatch "\.(md|sql|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevenir listado de directorios
Options -Indexes

# Seguridad adicional
<IfModule mod_headers.c>
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
</IfModule>
```

#### IIS (web.config)
Crear `web.config` en la raíz del proyecto:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <httpProtocol>
            <customHeaders>
                <add name="X-XSS-Protection" value="1; mode=block" />
                <add name="X-Frame-Options" value="SAMEORIGIN" />
                <add name="X-Content-Type-Options" value="nosniff" />
            </customHeaders>
        </httpProtocol>
    </system.webServer>
</configuration>
```

---

## 📞 Soporte y Contacto

Para reportar problemas o solicitar ayuda:
- **GitHub Issues**: https://github.com/rootargar/Nuevo-Control-de-Herramientas-/issues
- **Email**: admin@taller.com

---

## 📄 Licencia

Este sistema es de código abierto. Ver archivo LICENSE para más detalles.

---

**Desarrollado con 💙 para mejorar la gestión de herramientas de taller**

Versión 1.0.0 - 2025

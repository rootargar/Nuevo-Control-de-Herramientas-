-- =============================================
-- Sistema de Control de Herramientas de Taller
-- Script de Creación de Tablas COMPLETO
-- Base de datos: CotizaKW
-- Incluye: Usuarios, Roles, Ubicaciones, Tipos de Herramientas
-- =============================================

USE CotizaKW;
GO

-- =============================================
-- Tabla: Usuarios
-- Descripción: Usuarios del sistema con roles
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Usuarios' AND xtype='U')
BEGIN
    CREATE TABLE Usuarios (
        IdUsuario INT IDENTITY(1,1) PRIMARY KEY,
        NombreUsuario NVARCHAR(50) NOT NULL UNIQUE,
        Contrasena NVARCHAR(255) NOT NULL,
        NombreCompleto NVARCHAR(200) NOT NULL,
        Email NVARCHAR(100),
        Rol NVARCHAR(20) NOT NULL CHECK (Rol IN ('Administrador', 'Supervisor', 'Tecnico')),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activo' CHECK (Estado IN ('Activo', 'Inactivo')),
        FechaCreacion DATETIME DEFAULT GETDATE(),
        FechaUltimoAcceso DATETIME NULL,
        FechaActualizacion DATETIME DEFAULT GETDATE()
    );
    PRINT 'Tabla Usuarios creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Usuarios ya existe';
END
GO

-- =============================================
-- Tabla: Ubicaciones
-- Descripción: Ubicaciones físicas del taller
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Ubicaciones' AND xtype='U')
BEGIN
    CREATE TABLE Ubicaciones (
        IdUbicacion INT IDENTITY(1,1) PRIMARY KEY,
        Nombre NVARCHAR(100) NOT NULL,
        Descripcion NVARCHAR(500),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activa' CHECK (Estado IN ('Activa', 'Inactiva')),
        FechaCreacion DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE()
    );
    PRINT 'Tabla Ubicaciones creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Ubicaciones ya existe';
END
GO

-- =============================================
-- Tabla: TiposHerramienta
-- Descripción: Categorías/tipos de herramientas
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='TiposHerramienta' AND xtype='U')
BEGIN
    CREATE TABLE TiposHerramienta (
        IdTipo INT IDENTITY(1,1) PRIMARY KEY,
        Nombre NVARCHAR(100) NOT NULL,
        Descripcion NVARCHAR(500),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activo' CHECK (Estado IN ('Activo', 'Inactivo')),
        FechaCreacion DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE()
    );
    PRINT 'Tabla TiposHerramienta creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla TiposHerramienta ya existe';
END
GO

-- =============================================
-- Tabla: Herramientas
-- Descripción: Catálogo de herramientas del taller
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Herramientas' AND xtype='U')
BEGIN
    CREATE TABLE Herramientas (
        IdHerramienta INT IDENTITY(1,1) PRIMARY KEY,
        Codigo NVARCHAR(50) UNIQUE,
        Nombre NVARCHAR(100) NOT NULL,
        Descripcion NVARCHAR(500),
        IdTipo INT NULL,
        IdUbicacion INT NULL,
        ExistenciaTotal INT NOT NULL DEFAULT 0,
        ExistenciaDisponible INT NOT NULL DEFAULT 0,
        StockMinimo INT NOT NULL DEFAULT 5,
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activa' CHECK (Estado IN ('Activa', 'Inactiva', 'Mantenimiento', 'Dañada')),
        FechaRegistro DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE(),
        IdUsuarioRegistro INT NULL,
        FOREIGN KEY (IdTipo) REFERENCES TiposHerramienta(IdTipo),
        FOREIGN KEY (IdUbicacion) REFERENCES Ubicaciones(IdUbicacion),
        FOREIGN KEY (IdUsuarioRegistro) REFERENCES Usuarios(IdUsuario)
    );
    PRINT 'Tabla Herramientas creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Herramientas ya existe';
END
GO

-- =============================================
-- Tabla: Tecnicos
-- Descripción: Registro de técnicos del taller
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Tecnicos' AND xtype='U')
BEGIN
    CREATE TABLE Tecnicos (
        IdTecnico INT IDENTITY(1,1) PRIMARY KEY,
        Nombre NVARCHAR(100) NOT NULL,
        Apellido NVARCHAR(100) NOT NULL,
        Telefono NVARCHAR(20),
        Email NVARCHAR(100),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activo' CHECK (Estado IN ('Activo', 'Inactivo')),
        FechaRegistro DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE()
    );
    PRINT 'Tabla Tecnicos creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Tecnicos ya existe';
END
GO

-- =============================================
-- Tabla: Prestamos
-- Descripción: Registro de préstamos de herramientas
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Prestamos' AND xtype='U')
BEGIN
    CREATE TABLE Prestamos (
        IdPrestamo INT IDENTITY(1,1) PRIMARY KEY,
        IdHerramienta INT NOT NULL,
        IdTecnico INT NOT NULL,
        CantidadPrestada INT NOT NULL,
        FechaPrestamo DATETIME DEFAULT GETDATE(),
        FechaDevolucionPrevista DATETIME,
        FechaDevolucionReal DATETIME NULL,
        EstadoPrestamo NVARCHAR(20) NOT NULL DEFAULT 'Activo' CHECK (EstadoPrestamo IN ('Activo', 'Devuelto', 'Parcial')),
        CantidadDevuelta INT DEFAULT 0,
        Observaciones NVARCHAR(MAX),
        IdUsuarioRegistro INT NULL,
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta),
        FOREIGN KEY (IdTecnico) REFERENCES Tecnicos(IdTecnico),
        FOREIGN KEY (IdUsuarioRegistro) REFERENCES Usuarios(IdUsuario)
    );
    PRINT 'Tabla Prestamos creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Prestamos ya existe';
END
GO

-- =============================================
-- Tabla: Cajas
-- Descripción: Cajas de herramientas asignadas a técnicos
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Cajas' AND xtype='U')
BEGIN
    CREATE TABLE Cajas (
        IdCaja INT IDENTITY(1,1) PRIMARY KEY,
        NombreCaja NVARCHAR(100) NOT NULL,
        IdTecnicoAsignado INT NULL,
        Descripcion NVARCHAR(500),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activa' CHECK (Estado IN ('Activa', 'Inactiva')),
        FechaCreacion DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (IdTecnicoAsignado) REFERENCES Tecnicos(IdTecnico)
    );
    PRINT 'Tabla Cajas creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Cajas ya existe';
END
GO

-- =============================================
-- Tabla: CajasDetalle
-- Descripción: Detalle de herramientas contenidas en cada caja
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='CajasDetalle' AND xtype='U')
BEGIN
    CREATE TABLE CajasDetalle (
        IdCajaDetalle INT IDENTITY(1,1) PRIMARY KEY,
        IdCaja INT NOT NULL,
        IdHerramienta INT NOT NULL,
        Cantidad INT NOT NULL,
        FechaAsignacion DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (IdCaja) REFERENCES Cajas(IdCaja),
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta)
    );
    PRINT 'Tabla CajasDetalle creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla CajasDetalle ya existe';
END
GO

-- =============================================
-- Tabla: Devoluciones
-- Descripción: Registro de devoluciones de herramientas
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Devoluciones' AND xtype='U')
BEGIN
    CREATE TABLE Devoluciones (
        IdDevolucion INT IDENTITY(1,1) PRIMARY KEY,
        IdPrestamo INT NULL,
        IdCaja INT NULL,
        IdHerramienta INT NOT NULL,
        IdTecnico INT NOT NULL,
        CantidadDevuelta INT NOT NULL,
        FechaDevolucion DATETIME DEFAULT GETDATE(),
        TipoDevolucion NVARCHAR(50) NOT NULL CHECK (TipoDevolucion IN ('Prestamo', 'Caja')),
        EstadoHerramienta NVARCHAR(50) DEFAULT 'Bueno' CHECK (EstadoHerramienta IN ('Bueno', 'Regular', 'Malo', 'Dañado')),
        MotivoObservaciones NVARCHAR(MAX),
        IdUsuarioRegistro INT NULL,
        FOREIGN KEY (IdPrestamo) REFERENCES Prestamos(IdPrestamo),
        FOREIGN KEY (IdCaja) REFERENCES Cajas(IdCaja),
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta),
        FOREIGN KEY (IdTecnico) REFERENCES Tecnicos(IdTecnico),
        FOREIGN KEY (IdUsuarioRegistro) REFERENCES Usuarios(IdUsuario)
    );
    PRINT 'Tabla Devoluciones creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla Devoluciones ya existe';
END
GO

-- =============================================
-- Tabla: AuditoriaHerramientas
-- Descripción: Registro de auditoría de movimientos
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='AuditoriaHerramientas' AND xtype='U')
BEGIN
    CREATE TABLE AuditoriaHerramientas (
        IdAuditoria INT IDENTITY(1,1) PRIMARY KEY,
        TipoOperacion NVARCHAR(50) NOT NULL CHECK (TipoOperacion IN ('Alta', 'Baja', 'Edicion', 'Prestamo', 'Devolucion', 'AsignacionCaja', 'RetiroCaja')),
        TablaAfectada NVARCHAR(50) NOT NULL,
        IdRegistro INT NULL,
        IdHerramienta INT NULL,
        IdTecnico INT NULL,
        FechaMovimiento DATETIME DEFAULT GETDATE(),
        Cantidad INT NULL,
        IdUsuario INT NULL,
        NombreUsuario NVARCHAR(100),
        Observaciones NVARCHAR(MAX),
        DatosAnteriores NVARCHAR(MAX),
        DatosNuevos NVARCHAR(MAX),
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta),
        FOREIGN KEY (IdTecnico) REFERENCES Tecnicos(IdTecnico),
        FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario)
    );
    PRINT 'Tabla AuditoriaHerramientas creada exitosamente';
END
ELSE
BEGIN
    PRINT 'La tabla AuditoriaHerramientas ya existe';
END
GO

-- =============================================
-- Índices para mejorar el rendimiento
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Usuarios_NombreUsuario')
    CREATE INDEX IX_Usuarios_NombreUsuario ON Usuarios(NombreUsuario);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Usuarios_Rol')
    CREATE INDEX IX_Usuarios_Rol ON Usuarios(Rol);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Herramientas_Estado')
    CREATE INDEX IX_Herramientas_Estado ON Herramientas(Estado);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Herramientas_Codigo')
    CREATE INDEX IX_Herramientas_Codigo ON Herramientas(Codigo);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Tecnicos_Estado')
    CREATE INDEX IX_Tecnicos_Estado ON Tecnicos(Estado);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Prestamos_EstadoPrestamo')
    CREATE INDEX IX_Prestamos_EstadoPrestamo ON Prestamos(EstadoPrestamo);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_AuditoriaHerramientas_FechaMovimiento')
    CREATE INDEX IX_AuditoriaHerramientas_FechaMovimiento ON AuditoriaHerramientas(FechaMovimiento);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_AuditoriaHerramientas_TipoOperacion')
    CREATE INDEX IX_AuditoriaHerramientas_TipoOperacion ON AuditoriaHerramientas(TipoOperacion);
GO

-- =============================================
-- Datos Iniciales - Usuarios por defecto
-- =============================================
PRINT 'Insertando datos iniciales...';
GO

-- Insertar usuario Administrador por defecto (password: admin123)
-- NOTA: Contraseñas en texto plano para entorno local
IF NOT EXISTS (SELECT * FROM Usuarios WHERE NombreUsuario = 'admin')
BEGIN
    INSERT INTO Usuarios (NombreUsuario, Contrasena, NombreCompleto, Email, Rol, Estado)
    VALUES ('admin', 'admin123', 'Administrador del Sistema', 'admin@taller.com', 'Administrador', 'Activo');
    PRINT 'Usuario Administrador creado (usuario: admin, password: admin123)';
END
GO

-- Insertar usuario Supervisor por defecto (password: supervisor123)
-- NOTA: Contraseñas en texto plano para entorno local
IF NOT EXISTS (SELECT * FROM Usuarios WHERE NombreUsuario = 'supervisor')
BEGIN
    INSERT INTO Usuarios (NombreUsuario, Contrasena, NombreCompleto, Email, Rol, Estado)
    VALUES ('supervisor', 'supervisor123', 'Supervisor del Taller', 'supervisor@taller.com', 'Supervisor', 'Activo');
    PRINT 'Usuario Supervisor creado (usuario: supervisor, password: supervisor123)';
END
GO

-- Insertar usuario Técnico por defecto (password: tecnico123)
-- NOTA: Contraseñas en texto plano para entorno local
IF NOT EXISTS (SELECT * FROM Usuarios WHERE NombreUsuario = 'tecnico')
BEGIN
    INSERT INTO Usuarios (NombreUsuario, Contrasena, NombreCompleto, Email, Rol, Estado)
    VALUES ('tecnico', 'tecnico123', 'Técnico de Prueba', 'tecnico@taller.com', 'Tecnico', 'Activo');
    PRINT 'Usuario Técnico creado (usuario: tecnico, password: tecnico123)';
END
GO

-- =============================================
-- Datos Iniciales - Ubicaciones
-- =============================================
IF NOT EXISTS (SELECT * FROM Ubicaciones WHERE Nombre = 'Almacén Principal')
BEGIN
    INSERT INTO Ubicaciones (Nombre, Descripcion, Estado)
    VALUES
        ('Almacén Principal', 'Almacén central de herramientas', 'Activa'),
        ('Taller 1', 'Taller de mantenimiento área 1', 'Activa'),
        ('Taller 2', 'Taller de mantenimiento área 2', 'Activa'),
        ('Área de Servicio', 'Área de servicio y reparaciones', 'Activa'),
        ('Bodega Temporal', 'Almacenamiento temporal de herramientas', 'Activa');
    PRINT 'Ubicaciones iniciales creadas';
END
GO

-- =============================================
-- Datos Iniciales - Tipos de Herramientas
-- =============================================
IF NOT EXISTS (SELECT * FROM TiposHerramienta WHERE Nombre = 'Herramientas Manuales')
BEGIN
    INSERT INTO TiposHerramienta (Nombre, Descripcion, Estado)
    VALUES
        ('Herramientas Manuales', 'Herramientas de mano básicas', 'Activo'),
        ('Herramientas Eléctricas', 'Herramientas con motor eléctrico', 'Activo'),
        ('Herramientas de Medición', 'Instrumentos de medición y calibración', 'Activo'),
        ('Herramientas de Corte', 'Herramientas para cortar materiales', 'Activo'),
        ('Herramientas de Torque', 'Llaves dinamométricas y similares', 'Activo'),
        ('Herramientas Neumáticas', 'Herramientas de aire comprimido', 'Activo'),
        ('Herramientas de Soldadura', 'Equipos de soldadura y accesorios', 'Activo'),
        ('Equipo de Seguridad', 'Equipos de protección personal', 'Activo');
    PRINT 'Tipos de herramientas iniciales creados';
END
GO

PRINT '=============================================';
PRINT 'Script ejecutado exitosamente';
PRINT 'Todas las tablas, índices y datos iniciales han sido creados';
PRINT '=============================================';
PRINT '';
PRINT 'Usuarios creados:';
PRINT '  - admin / admin123 (Administrador)';
PRINT '  - supervisor / supervisor123 (Supervisor)';
PRINT '  - tecnico / tecnico123 (Técnico)';
PRINT '=============================================';
GO

-- =============================================
-- Sistema de Control de Herramientas de Taller
-- Script de Creación de Tablas
-- Base de datos: CotizaKW
-- =============================================

USE CotizaKW;
GO

-- =============================================
-- Tabla: Herramientas
-- Descripción: Catálogo de herramientas del taller
-- =============================================
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Herramientas' AND xtype='U')
BEGIN
    CREATE TABLE Herramientas (
        IdHerramienta INT IDENTITY(1,1) PRIMARY KEY,
        Nombre NVARCHAR(100) NOT NULL,
        Descripcion NVARCHAR(500),
        ExistenciaTotal INT NOT NULL DEFAULT 0,
        ExistenciaDisponible INT NOT NULL DEFAULT 0,
        Ubicacion NVARCHAR(200),
        Estado NVARCHAR(20) NOT NULL DEFAULT 'Activa' CHECK (Estado IN ('Activa', 'Inactiva')),
        FechaRegistro DATETIME DEFAULT GETDATE(),
        FechaActualizacion DATETIME DEFAULT GETDATE()
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
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta),
        FOREIGN KEY (IdTecnico) REFERENCES Tecnicos(IdTecnico)
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
        MotivoObservaciones NVARCHAR(MAX),
        FOREIGN KEY (IdPrestamo) REFERENCES Prestamos(IdPrestamo),
        FOREIGN KEY (IdCaja) REFERENCES Cajas(IdCaja),
        FOREIGN KEY (IdHerramienta) REFERENCES Herramientas(IdHerramienta),
        FOREIGN KEY (IdTecnico) REFERENCES Tecnicos(IdTecnico)
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
        IdHerramienta INT NULL,
        FechaMovimiento DATETIME DEFAULT GETDATE(),
        TipoMovimiento NVARCHAR(50),
        Cantidad INT NULL,
        IdUsuario NVARCHAR(100),
        Observaciones NVARCHAR(MAX)
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
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Herramientas_Estado')
    CREATE INDEX IX_Herramientas_Estado ON Herramientas(Estado);
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

PRINT '=============================================';
PRINT 'Script ejecutado exitosamente';
PRINT 'Todas las tablas e índices han sido creados';
PRINT '=============================================';
GO

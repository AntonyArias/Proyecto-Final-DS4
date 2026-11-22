SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
    id INT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO roles VALUES (1, 'Admin'), (2, 'Tecnico'), (3, 'Cliente');

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    rol_id INT NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT 1,
    tkRef VARCHAR(64),
    sesion_id VARCHAR(64),
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE,
    cedula VARCHAR(30) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    direccion VARCHAR(500),
    activo BOOLEAN NOT NULL DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT 1
);
CREATE TABLE solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    descripcion TEXT NOT NULL,
    estado ENUM('Recibida', 'Cotizada') NOT NULL DEFAULT 'Recibida',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id)
);
CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT,
    cliente_id INT NOT NULL,
    servicio_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda CHAR(3) NOT NULL DEFAULT 'CRC',
    estado ENUM('Cotizacion', 'Enviada', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Cotizacion',
    creado_por INT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aprobado_por INT,
    fecha_aprobacion DATETIME,
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id),
    CHECK (monto > 0),
    CHECK (moneda = 'CRC')
);
CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    estado ENUM('Aprobada', 'En campo', 'Informe entregado', 'Facturada') NOT NULL DEFAULT 'Aprobada',
    informe_referencia VARCHAR(500),
    factura_referencia VARCHAR(150),
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id)
);
CREATE TABLE proyecto_tecnicos (
    proyecto_id INT NOT NULL,
    tecnico_id INT NOT NULL,
    asignado_por INT NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (proyecto_id, tecnico_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id),
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id)
);
CREATE TABLE historial_estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado_anterior VARCHAR(30),
    estado_nuevo VARCHAR(30) NOT NULL,
    motivo VARCHAR(500),
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    tipo ENUM('Anticipo', 'Final') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda CHAR(3) NOT NULL DEFAULT 'CRC',
    estado ENUM('Pendiente', 'Recibido', 'Anulado') NOT NULL,
    fecha_pago DATETIME NOT NULL,
    referencia VARCHAR(150) NOT NULL,
    registrado_por INT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    CHECK (monto > 0),
    CHECK (moneda = 'CRC'),
    INDEX (proyecto_id, estado),
    INDEX (fecha_pago)
);
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pago_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado_anterior VARCHAR(30),
    estado_nuevo VARCHAR(30) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pago_id) REFERENCES pagos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
CREATE TABLE visitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    proyecto_id INT NOT NULL,
    tecnico_id INT NOT NULL,
    fecha_captura DATETIME NOT NULL,
    observaciones TEXT NOT NULL,
    latitud DECIMAL(10,7) NOT NULL,
    longitud DECIMAL(10,7) NOT NULL,
    huella CHAR(64) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id),
    CHECK (latitud BETWEEN -90 AND 90),
    CHECK (longitud BETWEEN -180 AND 180),
    INDEX (proyecto_id, fecha_captura)
);
CREATE TABLE evidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visita_id INT NOT NULL,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    mime VARCHAR(30) NOT NULL,
    bytes INT NOT NULL,
    sha256 CHAR(64) NOT NULL,
    ruta VARCHAR(200) NOT NULL UNIQUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visita_id) REFERENCES visitas(id),
    UNIQUE (visita_id, uuid),
    CHECK (bytes > 0 AND bytes <= 5242880)
);
INSERT INTO servicios (nombre, descripcion) VALUES
('Inventario forestal', 'Inventario de recursos forestales.'),
('Regencia ambiental', 'Seguimiento y regencia ambiental.'),
('Monitoreo de fincas', 'Visitas de monitoreo y observación.');

CREATE TABLE IF NOT EXISTS historial_cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado_anterior VARCHAR(30),
    estado_nuevo VARCHAR(30) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

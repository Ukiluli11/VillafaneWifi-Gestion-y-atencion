-- Modelo lógico relacional - Sistema de Gestión Villafañe Wifi
-- Destino: MySQL Workbench 8.x
-- El script puede importarse mediante:
-- File > Import > Reverse Engineer MySQL Create Script

SET NAMES utf8mb4;

CREATE SCHEMA IF NOT EXISTS villafane_wifi
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_0900_ai_ci;

USE villafane_wifi;

CREATE TABLE plan (
    id_plan             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(100) NOT NULL,
    velocidad_mbps      INT UNSIGNED NOT NULL,
    precio_vigente      DECIMAL(12,2) NOT NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_plan PRIMARY KEY (id_plan),
    CONSTRAINT uq_plan_nombre UNIQUE (nombre),
    CONSTRAINT ck_plan_velocidad CHECK (velocidad_mbps > 0),
    CONSTRAINT ck_plan_precio CHECK (precio_vigente >= 0)
) ENGINE=InnoDB;

CREATE TABLE cliente (
    id_cliente          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_documento      VARCHAR(20) NOT NULL DEFAULT 'DNI',
    numero_documento    VARCHAR(30) NOT NULL,
    nombre_razon_social VARCHAR(160) NOT NULL,
    tipo_cliente        VARCHAR(30) NOT NULL DEFAULT 'PERSONA',
    contacto_calle      VARCHAR(120) NULL,
    contacto_numero     VARCHAR(20) NULL,
    contacto_localidad  VARCHAR(100) NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_cliente PRIMARY KEY (id_cliente),
    CONSTRAINT uq_cliente_documento UNIQUE (tipo_documento, numero_documento)
) ENGINE=InnoDB;

-- Transformación del atributo multivaluado CLIENTE.Teléfono.
CREATE TABLE cliente_telefono (
    id_cliente          BIGINT UNSIGNED NOT NULL,
    telefono            VARCHAR(30) NOT NULL,
    CONSTRAINT pk_cliente_telefono PRIMARY KEY (id_cliente, telefono),
    CONSTRAINT uq_cliente_telefono UNIQUE (telefono),
    CONSTRAINT fk_cliente_telefono_cliente
        FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE usuario (
    id_usuario          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_usuario      VARCHAR(80) NOT NULL,
    credencial          VARCHAR(255) NOT NULL COMMENT 'Se almacenará el hash de la contraseña, nunca texto plano',
    estado              VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_usuario PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_nombre UNIQUE (nombre_usuario)
) ENGINE=InnoDB;

-- Estrategia de herencia: una tabla para el supertipo y una por subtipo.
-- La totalidad y disyunción se controlan en la lógica de negocio.
CREATE TABLE empleado (
    id_usuario          BIGINT UNSIGNED NOT NULL,
    area                VARCHAR(40) NOT NULL,
    CONSTRAINT pk_empleado PRIMARY KEY (id_usuario),
    CONSTRAINT fk_empleado_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE administrador (
    id_usuario          BIGINT UNSIGNED NOT NULL,
    CONSTRAINT pk_administrador PRIMARY KEY (id_usuario),
    CONSTRAINT fk_administrador_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE servicio (
    id_servicio         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente          BIGINT UNSIGNED NOT NULL,
    id_plan             BIGINT UNSIGNED NOT NULL,
    instalacion_calle   VARCHAR(120) NOT NULL,
    instalacion_numero  VARCHAR(20) NULL,
    instalacion_localidad VARCHAR(100) NOT NULL,
    dia_vencimiento     TINYINT UNSIGNED NOT NULL,
    fecha_alta          DATE NOT NULL,
    ip                  VARCHAR(45) NULL,
    mac                 VARCHAR(17) NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_servicio PRIMARY KEY (id_servicio),
    CONSTRAINT uq_servicio_ip UNIQUE (ip),
    CONSTRAINT uq_servicio_mac UNIQUE (mac),
    CONSTRAINT ck_servicio_dia_vencimiento CHECK (dia_vencimiento BETWEEN 1 AND 31),
    CONSTRAINT fk_servicio_cliente
        FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_servicio_plan
        FOREIGN KEY (id_plan) REFERENCES plan (id_plan)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_servicio_cliente ON servicio (id_cliente);
CREATE INDEX ix_servicio_plan ON servicio (id_plan);

CREATE TABLE conversacion (
    id_conversacion     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente          BIGINT UNSIGNED NULL,
    id_usuario_atencion BIGINT UNSIGNED NULL,
    numero_whatsapp     VARCHAR(30) NOT NULL,
    fecha_hora_inicio   DATETIME NOT NULL,
    fecha_hora_cierre   DATETIME NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'ABIERTA',
    modo_atencion       VARCHAR(20) NOT NULL DEFAULT 'BOT',
    inicio_atencion     DATETIME NULL,
    fin_atencion        DATETIME NULL,
    CONSTRAINT pk_conversacion PRIMARY KEY (id_conversacion),
    CONSTRAINT ck_conversacion_fechas CHECK (
        fecha_hora_cierre IS NULL OR fecha_hora_cierre >= fecha_hora_inicio
    ),
    CONSTRAINT ck_conversacion_atencion CHECK (
        fin_atencion IS NULL OR inicio_atencion IS NULL OR fin_atencion >= inicio_atencion
    ),
    CONSTRAINT fk_conversacion_cliente
        FOREIGN KEY (id_cliente) REFERENCES cliente (id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_conversacion_usuario
        FOREIGN KEY (id_usuario_atencion) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_conversacion_cliente ON conversacion (id_cliente);
CREATE INDEX ix_conversacion_usuario ON conversacion (id_usuario_atencion);
CREATE INDEX ix_conversacion_whatsapp ON conversacion (numero_whatsapp);

CREATE TABLE mensaje (
    id_mensaje              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_conversacion         BIGINT UNSIGNED NOT NULL,
    id_usuario_emisor       BIGINT UNSIGNED NULL,
    id_mensaje_externo      VARCHAR(120) NULL COMMENT 'Identificador de Meta para idempotencia del webhook',
    fecha_hora              DATETIME NOT NULL,
    tipo                    VARCHAR(30) NOT NULL,
    contenido               TEXT NULL,
    archivo_adjunto         VARCHAR(500) NULL,
    tipo_emisor             VARCHAR(30) NOT NULL,
    estado_envio            VARCHAR(20) NULL,
    CONSTRAINT pk_mensaje PRIMARY KEY (id_mensaje),
    CONSTRAINT uq_mensaje_externo UNIQUE (id_mensaje_externo),
    CONSTRAINT ck_mensaje_contenido CHECK (
        contenido IS NOT NULL OR archivo_adjunto IS NOT NULL
    ),
    CONSTRAINT ck_mensaje_usuario_emisor CHECK (
        (tipo_emisor = 'USUARIO_INTERNO' AND id_usuario_emisor IS NOT NULL)
        OR (tipo_emisor IN ('CLIENTE', 'BOT') AND id_usuario_emisor IS NULL)
    ),
    CONSTRAINT fk_mensaje_conversacion
        FOREIGN KEY (id_conversacion) REFERENCES conversacion (id_conversacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mensaje_usuario
        FOREIGN KEY (id_usuario_emisor) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_mensaje_conversacion_fecha ON mensaje (id_conversacion, fecha_hora);
CREATE INDEX ix_mensaje_usuario ON mensaje (id_usuario_emisor);

CREATE TABLE cuenta_receptora (
    id_cuenta           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(100) NOT NULL,
    tipo                VARCHAR(30) NOT NULL,
    identificador       VARCHAR(100) NOT NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
    CONSTRAINT pk_cuenta_receptora PRIMARY KEY (id_cuenta),
    CONSTRAINT uq_cuenta_identificador UNIQUE (tipo, identificador)
) ENGINE=InnoDB;

CREATE TABLE comprobante (
    id_comprobante          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_mensaje              BIGINT UNSIGNED NOT NULL,
    id_usuario_validador    BIGINT UNSIGNED NULL,
    hash_archivo            CHAR(64) NOT NULL,
    fecha_recepcion         DATETIME NOT NULL,
    numero_operacion        VARCHAR(100) NULL,
    monto_ocr               DECIMAL(12,2) NULL,
    fecha_ocr               DATE NULL,
    confianza_ocr           DECIMAL(5,2) NULL,
    estado_validacion       VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    motivo_rechazo          VARCHAR(500) NULL,
    fecha_hora_validacion   DATETIME NULL,
    CONSTRAINT pk_comprobante PRIMARY KEY (id_comprobante),
    CONSTRAINT uq_comprobante_mensaje UNIQUE (id_mensaje),
    CONSTRAINT uq_comprobante_hash UNIQUE (hash_archivo),
    CONSTRAINT uq_comprobante_operacion UNIQUE (numero_operacion),
    CONSTRAINT ck_comprobante_monto CHECK (monto_ocr IS NULL OR monto_ocr >= 0),
    CONSTRAINT ck_comprobante_confianza CHECK (
        confianza_ocr IS NULL OR confianza_ocr BETWEEN 0 AND 100
    ),
    CONSTRAINT ck_comprobante_rechazo CHECK (
        estado_validacion <> 'RECHAZADO' OR motivo_rechazo IS NOT NULL
    ),
    CONSTRAINT fk_comprobante_mensaje
        FOREIGN KEY (id_mensaje) REFERENCES mensaje (id_mensaje)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comprobante_usuario
        FOREIGN KEY (id_usuario_validador) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_comprobante_estado ON comprobante (estado_validacion, fecha_recepcion);
CREATE INDEX ix_comprobante_validador ON comprobante (id_usuario_validador);

CREATE TABLE pago (
    id_pago             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_comprobante      BIGINT UNSIGNED NOT NULL,
    id_cuenta           BIGINT UNSIGNED NOT NULL,
    fecha               DATETIME NOT NULL,
    monto_total         DECIMAL(12,2) NOT NULL,
    medio_pago          VARCHAR(30) NOT NULL,
    CONSTRAINT pk_pago PRIMARY KEY (id_pago),
    CONSTRAINT uq_pago_comprobante UNIQUE (id_comprobante),
    CONSTRAINT ck_pago_monto CHECK (monto_total > 0),
    CONSTRAINT fk_pago_comprobante
        FOREIGN KEY (id_comprobante) REFERENCES comprobante (id_comprobante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pago_cuenta
        FOREIGN KEY (id_cuenta) REFERENCES cuenta_receptora (id_cuenta)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_pago_cuenta_fecha ON pago (id_cuenta, fecha);

CREATE TABLE cuota (
    id_cuota            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_servicio         BIGINT UNSIGNED NOT NULL,
    id_pago             BIGINT UNSIGNED NULL,
    periodo             CHAR(7) NOT NULL COMMENT 'Formato AAAA-MM',
    monto               DECIMAL(12,2) NOT NULL,
    fecha_emision       DATE NOT NULL,
    fecha_vencimiento   DATE NOT NULL,
    CONSTRAINT pk_cuota PRIMARY KEY (id_cuota),
    CONSTRAINT uq_cuota_servicio_periodo UNIQUE (id_servicio, periodo),
    CONSTRAINT ck_cuota_monto CHECK (monto > 0),
    CONSTRAINT ck_cuota_fechas CHECK (fecha_vencimiento >= fecha_emision),
    CONSTRAINT fk_cuota_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicio (id_servicio)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cuota_pago
        FOREIGN KEY (id_pago) REFERENCES pago (id_pago)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_cuota_pago ON cuota (id_pago);
CREATE INDEX ix_cuota_vencimiento ON cuota (fecha_vencimiento);

CREATE TABLE ticket (
    id_ticket               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_conversacion         BIGINT UNSIGNED NOT NULL,
    id_servicio             BIGINT UNSIGNED NOT NULL,
    id_usuario_responsable  BIGINT UNSIGNED NULL,
    fecha_creacion          DATETIME NOT NULL,
    tipo                    VARCHAR(40) NOT NULL,
    descripcion             TEXT NOT NULL,
    estado                  VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    fecha_resolucion        DATETIME NULL,
    fecha_asignacion        DATETIME NULL,
    CONSTRAINT pk_ticket PRIMARY KEY (id_ticket),
    CONSTRAINT ck_ticket_resolucion CHECK (
        fecha_resolucion IS NULL OR fecha_resolucion >= fecha_creacion
    ),
    CONSTRAINT fk_ticket_conversacion
        FOREIGN KEY (id_conversacion) REFERENCES conversacion (id_conversacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ticket_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicio (id_servicio)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ticket_usuario
        FOREIGN KEY (id_usuario_responsable) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_ticket_conversacion ON ticket (id_conversacion);
CREATE INDEX ix_ticket_servicio ON ticket (id_servicio);
CREATE INDEX ix_ticket_responsable ON ticket (id_usuario_responsable);
CREATE INDEX ix_ticket_cola ON ticket (estado, fecha_creacion);

CREATE TABLE nota_interna (
    id_nota             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_ticket           BIGINT UNSIGNED NOT NULL,
    id_usuario          BIGINT UNSIGNED NOT NULL,
    fecha_hora          DATETIME NOT NULL,
    contenido           TEXT NOT NULL,
    CONSTRAINT pk_nota_interna PRIMARY KEY (id_nota),
    CONSTRAINT fk_nota_ticket
        FOREIGN KEY (id_ticket) REFERENCES ticket (id_ticket)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nota_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX ix_nota_ticket_fecha ON nota_interna (id_ticket, fecha_hora);
CREATE INDEX ix_nota_usuario ON nota_interna (id_usuario);

-- Reglas que se controlarán en la lógica de negocio porque no pueden
-- expresarse únicamente con claves foráneas y restricciones CHECK simples:
-- 1. Todo USUARIO debe pertenecer a un único subtipo: EMPLEADO o ADMINISTRADOR.
-- 2. Las CUOTAS canceladas por un mismo PAGO deben pertenecer al mismo CLIENTE.
-- 3. Todo PAGO debe cancelar al menos una CUOTA y no se admiten pagos parciales.
-- 4. Solo empleados o administradores autorizados pueden validar, atender o gestionar.
-- 5. Próximo vencimiento, estado de CUOTA y cuenta corriente son datos derivados.

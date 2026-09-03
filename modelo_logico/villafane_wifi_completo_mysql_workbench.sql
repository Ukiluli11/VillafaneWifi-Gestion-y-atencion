-- DER lógico completo - Villafañe Wifi
-- Destino: MySQL Workbench 8.x (solo para modelado y generación del EER).
-- Motor real del sistema: PostgreSQL. Los nombres de tablas y columnas respetan
-- los modelos y db_column de Django; los tipos se adaptan aquí a sintaxis MySQL.
-- Estados: [IMPLEMENTADO], [FUTURO] e [INFRAESTRUCTURA DJANGO].

SET NAMES utf8mb4;
CREATE SCHEMA IF NOT EXISTS villafane_wifi
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE villafane_wifi;

-- ============================================================================
-- MODELO DE NEGOCIO IMPLEMENTADO
-- ============================================================================

CREATE TABLE usuario (
    id              BIGINT NOT NULL AUTO_INCREMENT,
    password        VARCHAR(128) NOT NULL,
    last_login      DATETIME(6) NULL,
    nombre_usuario  VARCHAR(150) NOT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    is_staff        BOOLEAN NOT NULL DEFAULT FALSE,
    is_superuser    BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_alta      DATETIME(6) NOT NULL,
    CONSTRAINT pk_usuario PRIMARY KEY (id),
    CONSTRAINT uq_usuario_nombre_usuario UNIQUE (nombre_usuario)
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Usuario personalizado de Django';

CREATE TABLE empleado (
    id_usuario BIGINT NOT NULL,
    area       VARCHAR(30) NOT NULL,
    CONSTRAINT pk_empleado PRIMARY KEY (id_usuario),
    CONSTRAINT fk_empleado_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Subtipo de usuario';

CREATE TABLE administrador (
    id_usuario BIGINT NOT NULL,
    CONSTRAINT pk_administrador PRIMARY KEY (id_usuario),
    CONSTRAINT fk_administrador_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Subtipo de usuario';

CREATE TABLE cliente (
    id                    BIGINT NOT NULL AUTO_INCREMENT,
    tipo_documento        VARCHAR(20) NOT NULL DEFAULT 'DNI',
    numero_documento      VARCHAR(30) NOT NULL,
    nombre_razon_social   VARCHAR(160) NOT NULL,
    tipo_cliente          VARCHAR(30) NOT NULL DEFAULT 'PERSONA',
    contacto_calle        VARCHAR(120) NOT NULL DEFAULT '',
    contacto_numero       VARCHAR(20) NOT NULL DEFAULT '',
    contacto_localidad    VARCHAR(100) NOT NULL DEFAULT '',
    estado                VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_cliente PRIMARY KEY (id),
    CONSTRAINT uq_cliente_documento UNIQUE (tipo_documento, numero_documento)
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Cliente persona o empresa';

CREATE TABLE cliente_telefono (
    numero      VARCHAR(30) NOT NULL,
    id_cliente  BIGINT NOT NULL,
    CONSTRAINT pk_cliente_telefono PRIMARY KEY (numero),
    CONSTRAINT fk_cliente_telefono_cliente FOREIGN KEY (id_cliente)
        REFERENCES cliente (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Atributo multivaluado teléfono';
CREATE INDEX ix_cliente_telefono_cliente ON cliente_telefono (id_cliente);

CREATE TABLE plan (
    id                BIGINT NOT NULL AUTO_INCREMENT,
    nombre            VARCHAR(100) NOT NULL,
    velocidad_mbps    INT UNSIGNED NOT NULL,
    precio_vigente    DECIMAL(12,2) NOT NULL,
    estado            VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT pk_plan PRIMARY KEY (id),
    CONSTRAINT uq_plan_nombre UNIQUE (nombre),
    CONSTRAINT ck_plan_velocidad_positiva CHECK (velocidad_mbps > 0),
    CONSTRAINT ck_plan_precio_no_negativo CHECK (precio_vigente >= 0)
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Catálogo de planes';

CREATE TABLE servicio (
    id                      BIGINT NOT NULL AUTO_INCREMENT,
    instalacion_calle       VARCHAR(120) NOT NULL,
    instalacion_numero      VARCHAR(20) NOT NULL DEFAULT '',
    instalacion_localidad   VARCHAR(100) NOT NULL,
    dia_vencimiento         SMALLINT UNSIGNED NOT NULL,
    fecha_alta              DATE NOT NULL,
    ip                      VARCHAR(45) NULL,
    mac                     VARCHAR(17) NULL,
    estado                  VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    id_cliente              BIGINT NOT NULL,
    id_plan                 BIGINT NOT NULL,
    CONSTRAINT pk_servicio PRIMARY KEY (id),
    CONSTRAINT uq_servicio_ip UNIQUE (ip),
    CONSTRAINT uq_servicio_mac UNIQUE (mac),
    CONSTRAINT ck_servicio_dia CHECK (dia_vencimiento BETWEEN 1 AND 31),
    CONSTRAINT fk_servicio_cliente FOREIGN KEY (id_cliente)
        REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT fk_servicio_plan FOREIGN KEY (id_plan)
        REFERENCES plan (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Conexión contratada';
CREATE INDEX ix_servicio_cliente ON servicio (id_cliente);
CREATE INDEX ix_servicio_plan ON servicio (id_plan);

CREATE TABLE cuenta_receptora (
    id              BIGINT NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    tipo            VARCHAR(30) NOT NULL,
    identificador   VARCHAR(100) NOT NULL,
    estado          VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
    CONSTRAINT pk_cuenta_receptora PRIMARY KEY (id),
    CONSTRAINT uq_cuenta_identificador UNIQUE (tipo, identificador)
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Cuenta habilitada para recibir pagos';

-- ============================================================================
-- MODELO DE NEGOCIO FUTURO YA DEFINIDO EN REQUISITOS Y DOCUMENTACIÓN
-- ============================================================================

CREATE TABLE conversacion (
    id                    BIGINT NOT NULL AUTO_INCREMENT,
    numero_whatsapp       VARCHAR(30) NOT NULL,
    fecha_hora_inicio     DATETIME(6) NOT NULL,
    fecha_hora_cierre     DATETIME(6) NULL,
    estado                VARCHAR(20) NOT NULL DEFAULT 'ABIERTA',
    modo_atencion         VARCHAR(20) NOT NULL DEFAULT 'BOT',
    inicio_atencion       DATETIME(6) NULL,
    fin_atencion          DATETIME(6) NULL,
    id_cliente            BIGINT NULL,
    id_usuario_atencion   BIGINT NULL,
    CONSTRAINT pk_conversacion PRIMARY KEY (id),
    CONSTRAINT ck_conversacion_cierre CHECK (
        fecha_hora_cierre IS NULL OR fecha_hora_cierre >= fecha_hora_inicio
    ),
    CONSTRAINT ck_conversacion_atencion CHECK (
        fin_atencion IS NULL OR inicio_atencion IS NULL OR fin_atencion >= inicio_atencion
    ),
    CONSTRAINT fk_conversacion_cliente FOREIGN KEY (id_cliente)
        REFERENCES cliente (id) ON DELETE RESTRICT,
    CONSTRAINT fk_conversacion_usuario FOREIGN KEY (id_usuario_atencion)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[FUTURO] Conversación iniciada por WhatsApp';
CREATE INDEX ix_conversacion_cliente ON conversacion (id_cliente);
CREATE INDEX ix_conversacion_usuario ON conversacion (id_usuario_atencion);
CREATE INDEX ix_conversacion_whatsapp ON conversacion (numero_whatsapp);

CREATE TABLE mensaje (
    id                  BIGINT NOT NULL AUTO_INCREMENT,
    id_mensaje_externo  VARCHAR(120) NULL,
    fecha_hora          DATETIME(6) NOT NULL,
    tipo                VARCHAR(30) NOT NULL,
    contenido           TEXT NULL,
    archivo_adjunto     VARCHAR(500) NULL,
    tipo_emisor         VARCHAR(30) NOT NULL,
    estado_envio        VARCHAR(20) NULL,
    id_conversacion     BIGINT NOT NULL,
    id_usuario_emisor   BIGINT NULL,
    CONSTRAINT pk_mensaje PRIMARY KEY (id),
    CONSTRAINT uq_mensaje_externo UNIQUE (id_mensaje_externo),
    CONSTRAINT ck_mensaje_contenido CHECK (
        contenido IS NOT NULL OR archivo_adjunto IS NOT NULL
    ),
    CONSTRAINT fk_mensaje_conversacion FOREIGN KEY (id_conversacion)
        REFERENCES conversacion (id) ON DELETE RESTRICT,
    CONSTRAINT fk_mensaje_usuario FOREIGN KEY (id_usuario_emisor)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[FUTURO] Mensaje persistido de una conversación';
CREATE INDEX ix_mensaje_conversacion_fecha ON mensaje (id_conversacion, fecha_hora);

CREATE TABLE comprobante (
    id                      BIGINT NOT NULL AUTO_INCREMENT,
    hash_archivo            CHAR(64) NOT NULL,
    fecha_recepcion         DATETIME(6) NOT NULL,
    numero_operacion        VARCHAR(100) NULL,
    monto_ocr               DECIMAL(12,2) NULL,
    fecha_ocr               DATE NULL,
    confianza_ocr           DECIMAL(5,2) NULL,
    estado_validacion       VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    motivo_rechazo          VARCHAR(500) NULL,
    fecha_hora_validacion   DATETIME(6) NULL,
    id_mensaje              BIGINT NOT NULL,
    id_usuario_validador    BIGINT NULL,
    CONSTRAINT pk_comprobante PRIMARY KEY (id),
    CONSTRAINT uq_comprobante_mensaje UNIQUE (id_mensaje),
    CONSTRAINT uq_comprobante_hash UNIQUE (hash_archivo),
    CONSTRAINT uq_comprobante_operacion UNIQUE (numero_operacion),
    CONSTRAINT ck_comprobante_confianza CHECK (
        confianza_ocr IS NULL OR confianza_ocr BETWEEN 0 AND 100
    ),
    CONSTRAINT fk_comprobante_mensaje FOREIGN KEY (id_mensaje)
        REFERENCES mensaje (id) ON DELETE RESTRICT,
    CONSTRAINT fk_comprobante_validador FOREIGN KEY (id_usuario_validador)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[FUTURO] Comprobante recibido y procesado por OCR';

-- PAGO y CUOTA ya están implementadas. id_comprobante es una ampliación futura
-- y queda NULL para pagos manuales o en efectivo.
CREATE TABLE pago (
    id                  BIGINT NOT NULL AUTO_INCREMENT,
    fecha               DATETIME(6) NOT NULL,
    monto_total         DECIMAL(12,2) NOT NULL,
    medio_pago          VARCHAR(30) NOT NULL,
    id_cuenta           BIGINT NOT NULL,
    id_comprobante      BIGINT NULL,
    CONSTRAINT pk_pago PRIMARY KEY (id),
    CONSTRAINT uq_pago_comprobante UNIQUE (id_comprobante),
    CONSTRAINT ck_pago_monto_positivo CHECK (monto_total > 0),
    CONSTRAINT fk_pago_cuenta FOREIGN KEY (id_cuenta)
        REFERENCES cuenta_receptora (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pago_comprobante FOREIGN KEY (id_comprobante)
        REFERENCES comprobante (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO + AMPLIACIÓN FUTURA] Pago';
CREATE INDEX ix_pago_cuenta_fecha ON pago (id_cuenta, fecha);

CREATE TABLE cuota (
    id                  BIGINT NOT NULL AUTO_INCREMENT,
    periodo             VARCHAR(7) NOT NULL,
    monto               DECIMAL(12,2) NOT NULL,
    fecha_emision       DATE NOT NULL,
    fecha_vencimiento   DATE NOT NULL,
    id_servicio         BIGINT NOT NULL,
    id_pago             BIGINT NULL,
    CONSTRAINT pk_cuota PRIMARY KEY (id),
    CONSTRAINT uq_cuota_servicio_periodo UNIQUE (id_servicio, periodo),
    CONSTRAINT ck_cuota_monto_positivo CHECK (monto > 0),
    CONSTRAINT ck_cuota_fechas CHECK (fecha_vencimiento >= fecha_emision),
    CONSTRAINT fk_cuota_servicio FOREIGN KEY (id_servicio)
        REFERENCES servicio (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cuota_pago FOREIGN KEY (id_pago)
        REFERENCES pago (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[IMPLEMENTADO] Cuota; estado y saldo son derivados';
CREATE INDEX ix_cuota_pago ON cuota (id_pago);
CREATE INDEX ix_cuota_vencimiento ON cuota (fecha_vencimiento);

CREATE TABLE ticket (
    id                      BIGINT NOT NULL AUTO_INCREMENT,
    fecha_creacion          DATETIME(6) NOT NULL,
    tipo                    VARCHAR(40) NOT NULL,
    descripcion             TEXT NOT NULL,
    estado                  VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    fecha_resolucion        DATETIME(6) NULL,
    fecha_asignacion        DATETIME(6) NULL,
    id_conversacion         BIGINT NOT NULL,
    id_servicio             BIGINT NOT NULL,
    id_usuario_responsable  BIGINT NULL,
    CONSTRAINT pk_ticket PRIMARY KEY (id),
    CONSTRAINT ck_ticket_resolucion CHECK (
        fecha_resolucion IS NULL OR fecha_resolucion >= fecha_creacion
    ),
    CONSTRAINT fk_ticket_conversacion FOREIGN KEY (id_conversacion)
        REFERENCES conversacion (id) ON DELETE RESTRICT,
    CONSTRAINT fk_ticket_servicio FOREIGN KEY (id_servicio)
        REFERENCES servicio (id) ON DELETE RESTRICT,
    CONSTRAINT fk_ticket_responsable FOREIGN KEY (id_usuario_responsable)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[FUTURO] Ticket en cola hasta ser atendido';
CREATE INDEX ix_ticket_cola ON ticket (estado, fecha_creacion);

CREATE TABLE nota_interna (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    fecha_hora  DATETIME(6) NOT NULL,
    contenido   TEXT NOT NULL,
    id_ticket   BIGINT NOT NULL,
    id_usuario  BIGINT NOT NULL,
    CONSTRAINT pk_nota_interna PRIMARY KEY (id),
    CONSTRAINT fk_nota_ticket FOREIGN KEY (id_ticket)
        REFERENCES ticket (id) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[FUTURO] Nota visible solo para personal interno';

-- ============================================================================
-- INFRAESTRUCTURA GENERADA POR DJANGO (EXISTE EN POSTGRESQL)
-- ============================================================================

CREATE TABLE django_migrations (
    id       BIGINT NOT NULL AUTO_INCREMENT,
    app      VARCHAR(255) NOT NULL,
    name     VARCHAR(255) NOT NULL,
    applied  DATETIME(6) NOT NULL,
    CONSTRAINT pk_django_migrations PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Historial de migraciones';

CREATE TABLE django_content_type (
    id         BIGINT NOT NULL AUTO_INCREMENT,
    app_label  VARCHAR(100) NOT NULL,
    model      VARCHAR(100) NOT NULL,
    CONSTRAINT pk_django_content_type PRIMARY KEY (id),
    CONSTRAINT uq_django_content_type UNIQUE (app_label, model)
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Tipos de contenido';

CREATE TABLE auth_permission (
    id               BIGINT NOT NULL AUTO_INCREMENT,
    name             VARCHAR(255) NOT NULL,
    content_type_id  BIGINT NOT NULL,
    codename         VARCHAR(100) NOT NULL,
    CONSTRAINT pk_auth_permission PRIMARY KEY (id),
    CONSTRAINT uq_auth_permission UNIQUE (content_type_id, codename),
    CONSTRAINT fk_auth_permission_content_type FOREIGN KEY (content_type_id)
        REFERENCES django_content_type (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Permisos técnicos';

CREATE TABLE auth_group (
    id    BIGINT NOT NULL AUTO_INCREMENT,
    name  VARCHAR(150) NOT NULL,
    CONSTRAINT pk_auth_group PRIMARY KEY (id),
    CONSTRAINT uq_auth_group_name UNIQUE (name)
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Grupos técnicos';

CREATE TABLE auth_group_permissions (
    id             BIGINT NOT NULL AUTO_INCREMENT,
    group_id       BIGINT NOT NULL,
    permission_id  BIGINT NOT NULL,
    CONSTRAINT pk_auth_group_permissions PRIMARY KEY (id),
    CONSTRAINT uq_auth_group_permission UNIQUE (group_id, permission_id),
    CONSTRAINT fk_auth_group_permission_group FOREIGN KEY (group_id)
        REFERENCES auth_group (id) ON DELETE CASCADE,
    CONSTRAINT fk_auth_group_permission_permission FOREIGN KEY (permission_id)
        REFERENCES auth_permission (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Relación grupo-permiso';

CREATE TABLE django_session (
    session_key   VARCHAR(40) NOT NULL,
    session_data  LONGTEXT NOT NULL,
    expire_date   DATETIME(6) NOT NULL,
    CONSTRAINT pk_django_session PRIMARY KEY (session_key)
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Sesiones autenticadas';
CREATE INDEX ix_django_session_expire_date ON django_session (expire_date);

CREATE TABLE django_admin_log (
    id               BIGINT NOT NULL AUTO_INCREMENT,
    action_time      DATETIME(6) NOT NULL,
    object_id        LONGTEXT NULL,
    object_repr      VARCHAR(200) NOT NULL,
    action_flag      SMALLINT UNSIGNED NOT NULL,
    change_message   LONGTEXT NOT NULL,
    content_type_id  BIGINT NULL,
    user_id          BIGINT NOT NULL,
    CONSTRAINT pk_django_admin_log PRIMARY KEY (id),
    CONSTRAINT ck_django_admin_log_action_flag CHECK (action_flag > 0),
    CONSTRAINT fk_admin_log_content_type FOREIGN KEY (content_type_id)
        REFERENCES django_content_type (id) ON DELETE SET NULL,
    CONSTRAINT fk_admin_log_usuario FOREIGN KEY (user_id)
        REFERENCES usuario (id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='[INFRAESTRUCTURA DJANGO] Auditoría del admin';

-- Reglas aplicadas por servicios Django y no solo por la base:
-- 1. Usuario pertenece exactamente a Empleado o Administrador (total y disjunta).
-- 2. Un pago cancela una o varias cuotas completas del mismo cliente.
-- 3. Una cuota pendiente no tiene id_pago; no se utiliza el número 0 en el DER.
-- 4. Un ticket queda sin responsable hasta que lo toma el primer empleado disponible.
-- 5. Empleado y Administrador pueden validar comprobantes según permisos.
-- 6. Estado de cuota, saldo y próximo vencimiento son datos calculados.

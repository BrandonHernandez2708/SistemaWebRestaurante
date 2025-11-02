-- =========================================================
--  BASE DE DATOS: marea_roja_db (MySQL / XAMPP)
--  Estructura basada en el ERD proporcionado por el usuario
-- =========================================================

DROP DATABASE IF EXISTS marea_roja_db;
CREATE DATABASE marea_roja_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE marea_roja_db;

-- =========================================================
--  AJUSTES GENERALES
-- =========================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
--  CATÁLOGOS BÁSICOS: departamentos, puestos, sucursales, unidades
-- =========================================================
CREATE TABLE departamentos (
  id_departamento      INT AUTO_INCREMENT PRIMARY KEY,
  nombre               VARCHAR(100) NOT NULL,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE puestos (
  id_puesto            INT AUTO_INCREMENT PRIMARY KEY,
  descripcion          VARCHAR(120) NOT NULL,
  sueldo_base_q        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sucursales (
  id_sucursal          INT AUTO_INCREMENT PRIMARY KEY,
  direccion            VARCHAR(200) NOT NULL,
  horario_apertura     TIME NOT NULL,
  hora_cierre          TIME NOT NULL,
  capacidad_empleados  INT NOT NULL DEFAULT 0,
  telefono             VARCHAR(20),
  correo               VARCHAR(120),
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE unidades_medida (
  id_unidad            INT AUTO_INCREMENT PRIMARY KEY,
  unidad               VARCHAR(60) NOT NULL,
  abreviatura          VARCHAR(16) NOT NULL,
  UNIQUE KEY uk_unidad_abrev (unidad, abreviatura)
) ENGINE=InnoDB;

CREATE TABLE conversion_unidades (
  id_conversion        INT AUTO_INCREMENT PRIMARY KEY,
  id_unidad_origen     INT NOT NULL,
  id_unidad_destino    INT NOT NULL,
  descripcion          VARCHAR(120),
  factor_conversion    DECIMAL(18,6) NOT NULL,
  CONSTRAINT fk_conv_um_origen
    FOREIGN KEY (id_unidad_origen) REFERENCES unidades_medida(id_unidad)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_conv_um_destino
    FOREIGN KEY (id_unidad_destino) REFERENCES unidades_medida(id_unidad)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uk_conv (id_unidad_origen, id_unidad_destino)
) ENGINE=InnoDB;

-- =========================================================
--  PERSONAS: empleados, contactos, teléfonos, correos, asistencia
-- =========================================================
CREATE TABLE empleados (
  id_empleado          INT AUTO_INCREMENT PRIMARY KEY,
  dpi                  VARCHAR(20),
  nombre               VARCHAR(100) NOT NULL,
  apellido             VARCHAR(100) NOT NULL,
  id_departamento      INT,
  id_puesto            INT,
  id_sucursal          INT,
  fecha_ingreso        DATE,
  estado               ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_emp_depto   FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_emp_puesto  FOREIGN KEY (id_puesto) REFERENCES puestos(id_puesto)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_emp_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE telefonos_empleado (
  id_telefono          INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  numero_telefono      VARCHAR(20) NOT NULL,
  CONSTRAINT fk_tel_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE correos_empleado (
  id_correo            INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  direccion_correo     VARCHAR(150) NOT NULL,
  UNIQUE KEY uk_mail_emp (id_empleado, direccion_correo),
  CONSTRAINT fk_mail_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contactos_emergencia (
  id_contacto          INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  nombre_contacto      VARCHAR(120) NOT NULL,
  relacion             VARCHAR(60),
  telefono             VARCHAR(20),
  CONSTRAINT fk_contacto_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE asistencia_empleado (
  id_asistencia        INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  fecha                DATE NOT NULL,
  hora_entrada         TIME,
  hora_salida          TIME,
  CONSTRAINT fk_asistencia_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE,
  UNIQUE KEY uk_asistencia (id_empleado, fecha)
) ENGINE=InnoDB;

-- Penalizaciones a empleados
CREATE TABLE penalizaciones (
  id_penalizacion      INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  descripcion          VARCHAR(200) NOT NULL,
  fecha_penalizacion   DATE NOT NULL,
  descuento_q          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_penalizacion_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Planilla
CREATE TABLE planilla (
  id_planilla          INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT NOT NULL,
  bonificacion_total_q DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  penalizacion_total_q DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sueldo_total_q       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  periodo              VARCHAR(20) NOT NULL, -- p.ej. 2025-10
  CONSTRAINT fk_planilla_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE CASCADE,
  UNIQUE KEY uk_planilla_periodo (id_empleado, periodo)
) ENGINE=InnoDB;

-- =========================================================
--  SEGURIDAD Y AUDITORÍA: usuarios y bitácora
-- =========================================================
CREATE TABLE usuarios (
  id_usuario           INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado          INT,
  usuario              VARCHAR(60) NOT NULL UNIQUE,
  contrasenia_hash     VARCHAR(255) NOT NULL,
  activo               TINYINT(1) NOT NULL DEFAULT 1,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usr_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE bitacora (
  id_bitacora          BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_usuario           INT,
  ip                   VARCHAR(64),
  pc                   VARCHAR(100),
  operacion_realizada  VARCHAR(200) NOT NULL,
  fecha_hora_accion    DATETIME NOT NULL
) ENGINE=InnoDB;

-- =========================================================
--  PROVEEDORES, COMPRAS, INVENTARIO (ingredientes), PÉRDIDAS, CONTROL DE INGREDIENTES
-- =========================================================
CREATE TABLE proveedores (
  id_proveedor         INT AUTO_INCREMENT PRIMARY KEY,
  nombre_proveedor     VARCHAR(120) NOT NULL,
  correo_proveedor     VARCHAR(150),
  telefono_proveedor   VARCHAR(20),
  UNIQUE KEY uk_proveedor (nombre_proveedor)
) ENGINE=InnoDB;

CREATE TABLE ingredientes (
  id_ingrediente       INT AUTO_INCREMENT PRIMARY KEY,
  nombre_ingrediente   VARCHAR(120) NOT NULL,
  id_unidad            INT NOT NULL, -- unidad principal de stock
  cantidad_stock       DECIMAL(18,3) NOT NULL DEFAULT 0.000,
  CONSTRAINT fk_ing_unidad FOREIGN KEY (id_unidad) REFERENCES unidades_medida(id_unidad)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uk_ing (nombre_ingrediente)
) ENGINE=InnoDB;

CREATE TABLE compras_ingrediente (
  id_compra_ingrediente   INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor            INT NOT NULL,
  fecha_de_compra         DATE NOT NULL,
  monto_total_compra_q    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_ci_prov FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detalle_compra_ingrediente (
  id_detalle              INT AUTO_INCREMENT PRIMARY KEY,
  id_compra_ingrediente   INT NOT NULL,
  id_ingrediente          INT NOT NULL,
  cantidad_compra         DECIMAL(18,3) NOT NULL,
  costo_unitario_q        DECIMAL(12,4) NOT NULL,
  costo_total_q           DECIMAL(12,2) GENERATED ALWAYS AS (cantidad_compra * costo_unitario_q) STORED,
  CONSTRAINT fk_dci_ci FOREIGN KEY (id_compra_ingrediente) REFERENCES compras_ingrediente(id_compra_ingrediente)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dci_ing FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id_ingrediente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE perdidas_inventario (
  id_perdida            INT AUTO_INCREMENT PRIMARY KEY,
  id_ingrediente        INT NOT NULL,
  descripcion           VARCHAR(200),
  cantidad_unidades     DECIMAL(18,3) NOT NULL,
  costo_perdida_q       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  fecha_perdida         DATE NOT NULL,
  CONSTRAINT fk_perdida_ing FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id_ingrediente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE control_ingredientes (
  id_control            INT AUTO_INCREMENT PRIMARY KEY,
  id_ingrediente        INT NOT NULL,
  estado                ENUM('OK','POR_VENCER','VENCIDO') NOT NULL DEFAULT 'OK',
  fecha_entrada         DATE NOT NULL,
  fecha_caducidad       DATE,
  CONSTRAINT fk_control_ing FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id_ingrediente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  INSUMOS VARIOS (no ingredientes), COMPRAS DE INSUMOS
-- =========================================================
CREATE TABLE insumos (
  id_insumo             INT AUTO_INCREMENT PRIMARY KEY,
  descripcion           VARCHAR(120) NOT NULL,
  stock                 DECIMAL(18,3) NOT NULL DEFAULT 0.000,
  UNIQUE KEY uk_insumo_desc (descripcion)
) ENGINE=InnoDB;

CREATE TABLE compras_insumos (
  id_compra_insumo      INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor          INT NOT NULL,
  fecha_compra          DATE NOT NULL,
  monto_total_q         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_ci_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detalle_compra_insumo (
  id_detalle_ci         INT AUTO_INCREMENT PRIMARY KEY,
  id_compra_insumo      INT NOT NULL,
  id_insumo             INT NOT NULL,
  cantidad_compra       DECIMAL(18,3) NOT NULL,
  costo_unitario_q      DECIMAL(12,4) NOT NULL,
  costo_total_q         DECIMAL(12,2) GENERATED ALWAYS AS (cantidad_compra * costo_unitario_q) STORED,
  CONSTRAINT fk_dci_ci2 FOREIGN KEY (id_compra_insumo) REFERENCES compras_insumos(id_compra_insumo)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dci_ins FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  MOBILIARIO, TIPOS y COMPRAS
-- =========================================================
CREATE TABLE tipos_mobiliario (
  id_tipo_mobiliario    INT AUTO_INCREMENT PRIMARY KEY,
  descripcion           VARCHAR(120) NOT NULL,
  UNIQUE KEY uk_tipo_mob (descripcion)
) ENGINE=InnoDB;

CREATE TABLE mobiliario (
  id_mobiliario         INT AUTO_INCREMENT PRIMARY KEY,
  nombre_mobiliario     VARCHAR(120) NOT NULL,
  id_tipo_mobiliario    INT,
  descripcion           VARCHAR(200),
  cantidad_en_stock     INT NOT NULL DEFAULT 0,
  id_sucursal           INT,
  CONSTRAINT fk_mob_tipo FOREIGN KEY (id_tipo_mobiliario) REFERENCES tipos_mobiliario(id_tipo_mobiliario)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mob_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE compras_mobiliario (
  id_compra_mobiliario  INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor          INT NOT NULL,
  fecha_de_compra       DATE NOT NULL,
  monto_total_compra_q  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_cm_prov FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detalle_compra_mobiliario (
  id_detalle_cm         INT AUTO_INCREMENT PRIMARY KEY,
  id_compra_mobiliario  INT NOT NULL,
  id_mobiliario         INT NOT NULL,
  cantidad_compra       INT NOT NULL,
  costo_unitario_q      DECIMAL(12,2) NOT NULL,
  costo_total_q         DECIMAL(12,2) GENERATED ALWAYS AS (cantidad_compra * costo_unitario_q) STORED,
  CONSTRAINT fk_dcm_cm FOREIGN KEY (id_compra_mobiliario) REFERENCES compras_mobiliario(id_compra_mobiliario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dcm_mob FOREIGN KEY (id_mobiliario) REFERENCES mobiliario(id_mobiliario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  CLIENTES, FACTURAS, COBROS
-- =========================================================
CREATE TABLE clientes (
  id_cliente           INT AUTO_INCREMENT PRIMARY KEY,
  nombre               VARCHAR(100) NOT NULL,
  apellido             VARCHAR(100),
  nit                  VARCHAR(20) NOT NULL,
  telefono             VARCHAR(20),
  correo               VARCHAR(150),
  UNIQUE KEY uk_nit (nit)
) ENGINE=InnoDB;

CREATE TABLE facturas (
  id_factura           INT AUTO_INCREMENT PRIMARY KEY,
  codigo_serie         VARCHAR(30),
  fecha_emision        DATETIME NOT NULL,
  id_cliente           INT NOT NULL,
  nit_cliente          VARCHAR(20),
  monto_total_q        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_fact_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tipos_cobro (
  id_tipo_cobro        INT AUTO_INCREMENT PRIMARY KEY,
  tipo_cobro           VARCHAR(60) NOT NULL,
  UNIQUE KEY uk_tipo_cobro (tipo_cobro)
) ENGINE=InnoDB;

CREATE TABLE detalle_cobro (
  id_detalle_cobro     INT AUTO_INCREMENT PRIMARY KEY,
  id_factura           INT NOT NULL,
  id_tipo_cobro        INT NOT NULL,
  monto_detalle_q      DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_dc_fact FOREIGN KEY (id_factura) REFERENCES facturas(id_factura)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_dc_tipo FOREIGN KEY (id_tipo_cobro) REFERENCES tipos_cobro(id_tipo_cobro)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  MENÚ: platos, bebidas, recetas
-- =========================================================
CREATE TABLE platos (
  id_plato             INT AUTO_INCREMENT PRIMARY KEY,
  nombre_plato         VARCHAR(120) NOT NULL,
  descripcion          VARCHAR(200),
  precio_unitario_q    DECIMAL(12,2) NOT NULL,
  estado               ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  UNIQUE KEY uk_plato (nombre_plato)
) ENGINE=InnoDB;

CREATE TABLE bebidas (
  id_bebida            INT AUTO_INCREMENT PRIMARY KEY,
  descripcion          VARCHAR(120) NOT NULL,
  precio_unitario_q    DECIMAL(12,2) NOT NULL,
  estado               ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  UNIQUE KEY uk_bebida (descripcion)
) ENGINE=InnoDB;

CREATE TABLE receta (
  id_registro_receta   INT AUTO_INCREMENT PRIMARY KEY,
  id_plato             INT NOT NULL,
  id_ingrediente       INT NOT NULL,
  cantidad             DECIMAL(18,3) NOT NULL,
  id_unidad            INT NOT NULL,
  CONSTRAINT fk_rec_plato FOREIGN KEY (id_plato) REFERENCES platos(id_plato)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rec_ing FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id_ingrediente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_rec_um FOREIGN KEY (id_unidad) REFERENCES unidades_medida(id_unidad)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uk_receta (id_plato, id_ingrediente)
) ENGINE=InnoDB;

CREATE TABLE detalle_factura (
  id_detalle           INT AUTO_INCREMENT PRIMARY KEY,
  id_factura           INT NOT NULL,
  id_plato             INT,
  id_bebida            INT,
  cantidad             INT NOT NULL DEFAULT 1,
  precio_unitario_q    DECIMAL(12,2) NOT NULL,
  precio_total_q       DECIMAL(12,2) GENERATED ALWAYS AS (cantidad * precio_unitario_q) STORED,
  CONSTRAINT fk_df_fact FOREIGN KEY (id_factura) REFERENCES facturas(id_factura)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_df_plato FOREIGN KEY (id_plato) REFERENCES platos(id_plato)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_df_bebida FOREIGN KEY (id_bebida) REFERENCES bebidas(id_bebida)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- =========================================================
--  MESAS y RESERVACIONES
-- =========================================================
CREATE TABLE mesas (
  id_mesa              INT AUTO_INCREMENT PRIMARY KEY,
  descripcion          VARCHAR(120),
  capacidad_personas   INT NOT NULL,
  estado               ENUM('DISPONIBLE','OCUPADA','RESERVADA','FUERA_DE_SERVICIO') DEFAULT 'DISPONIBLE'
) ENGINE=InnoDB;

CREATE TABLE reservaciones (
  id_reservacion       INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente           INT NOT NULL,
  id_mesa              INT NOT NULL,
  cantidad_personas    INT NOT NULL,
  fecha_hora           DATETIME NOT NULL,
  estado               ENUM('PROGRAMADA','CANCELADA','CUMPLIDA') DEFAULT 'PROGRAMADA',
  CONSTRAINT fk_reserva_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  VEHÍCULOS, RUTAS, VIAJES, ACCIDENTES
-- =========================================================
CREATE TABLE vehiculos (
  id_placa             INT AUTO_INCREMENT PRIMARY KEY,
  no_placas            VARCHAR(20) NOT NULL UNIQUE,
  marca                VARCHAR(60) NOT NULL,
  modelo               VARCHAR(60),
  anio_vehiculo        INT,
  id_mobiliario        INT, 
  estado               ENUM('ACTIVO','EN_TALLER','BAJA') DEFAULT 'ACTIVO',
  CONSTRAINT fk_vehiculo_mobiliario FOREIGN KEY (id_mobiliario) 
    REFERENCES mobiliario(id_mobiliario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rutas (
  id_ruta              INT AUTO_INCREMENT PRIMARY KEY,
  descripcion_ruta     VARCHAR(200) NOT NULL,
  inicio_ruta          VARCHAR(120),
  fin_ruta             VARCHAR(120),
  gasolina_aproximada  DECIMAL(12,2)
) ENGINE=InnoDB;

CREATE TABLE viajes (
  id_viaje              INT AUTO_INCREMENT PRIMARY KEY,
  id_ruta               INT NOT NULL,
  id_vehiculo           INT NOT NULL,
  id_empleado_piloto    INT NOT NULL,
  id_empleado_acompanante INT,
  fecha_hora_salida     DATETIME NOT NULL,
  tiempo_aproximado_min INT,
  descripcion_viaje     VARCHAR(200),

  CONSTRAINT fk_viaje_ruta FOREIGN KEY (id_ruta)
    REFERENCES rutas(id_ruta)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT fk_viaje_veh FOREIGN KEY (id_vehiculo)
    REFERENCES vehiculos(id_placa)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT fk_viaje_piloto FOREIGN KEY (id_empleado_piloto)
    REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT fk_viaje_acompanante FOREIGN KEY (id_empleado_acompanante)
    REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE reportes_accidentes (
  id_accidente         INT AUTO_INCREMENT PRIMARY KEY,
  id_viaje             INT NOT NULL,
  id_empleado          INT NOT NULL,
  descripcion_accidente VARCHAR(300) NOT NULL,
  fecha_hora           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_acc_viaje FOREIGN KEY (id_viaje) REFERENCES viajes(id_viaje)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_acc_emp FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  TALLERES, ESPECIALIDADES, MANTENIMIENTOS
-- =========================================================
CREATE TABLE especialidades_reparacion (
  id_especialidad      INT AUTO_INCREMENT PRIMARY KEY,
  descripcion          VARCHAR(120) NOT NULL,
  UNIQUE KEY uk_esp (descripcion)
) ENGINE=InnoDB;

CREATE TABLE talleres (
  id_taller            INT AUTO_INCREMENT PRIMARY KEY,
  nombre_taller        VARCHAR(120) NOT NULL,
  correo               VARCHAR(150),
  telefono             VARCHAR(20),
  id_sucursal          INT,
  id_especialidad      INT,
  CONSTRAINT fk_taller_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_taller_esp FOREIGN KEY (id_especialidad) REFERENCES especialidades_reparacion(id_especialidad)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_vehiculo (
  id_mantenimiento     INT AUTO_INCREMENT PRIMARY KEY,
  id_placa             INT NOT NULL,
  id_taller            INT NOT NULL,
  descripcion_mantenimiento VARCHAR(200) NOT NULL,
  fecha_mantenimiento  DATE NOT NULL,
  costo_mantenimiento_q DECIMAL(12,2) NOT NULL,
  estado               ENUM('PROGRAMADO','EN_PROCESO','FINALIZADO') DEFAULT 'PROGRAMADO',
  CONSTRAINT fk_mantv_veh FOREIGN KEY (id_placa) REFERENCES vehiculos(id_placa)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mantv_taller FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_muebles (
  id_mantenimiento     INT AUTO_INCREMENT PRIMARY KEY,
  id_mobiliario        INT NOT NULL,
  id_taller            INT,
  descripcion_mantenimiento VARCHAR(200) NOT NULL,
  fecha_mantenimiento  DATE NOT NULL,
  costo_mantenimiento_q DECIMAL(12,2) NOT NULL,
  estado               ENUM('PROGRAMADO','EN_PROCESO','FINALIZADO') DEFAULT 'PROGRAMADO',
  CONSTRAINT fk_mantm_mob FOREIGN KEY (id_mobiliario) REFERENCES mobiliario(id_mobiliario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mantm_taller FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_electrodomesticos (
  id_mantenimiento     INT AUTO_INCREMENT PRIMARY KEY,
  id_mobiliario        INT NOT NULL,
  id_taller            INT,
  descripcion_mantenimiento VARCHAR(200) NOT NULL,
  fecha_mantenimiento  DATE NOT NULL,
  costo_mantenimiento_q DECIMAL(12,2) NOT NULL,
  estado               ENUM('PROGRAMADO','EN_PROCESO','FINALIZADO') DEFAULT 'PROGRAMADO',
  CONSTRAINT fk_mantel_mob FOREIGN KEY (id_mobiliario) REFERENCES mobiliario(id_mobiliario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mantel_taller FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
--  RELACIÓN FACTURA - MESERO (empleado)
-- =========================================================
CREATE TABLE factura_mesero (
  id_factura           INT PRIMARY KEY,
  id_mesero            INT NOT NULL,
  CONSTRAINT fk_fm_fact FOREIGN KEY (id_factura) REFERENCES facturas(id_factura)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_fm_mesero FOREIGN KEY (id_mesero) REFERENCES empleados(id_empleado)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  ÍNDICES ÚTILES
-- =========================================================
CREATE INDEX idx_emp_depto ON empleados(id_departamento);
CREATE INDEX idx_emp_puesto ON empleados(id_puesto);
CREATE INDEX idx_tel_emp ON telefonos_empleado(id_empleado);
CREATE INDEX idx_mail_emp ON correos_empleado(id_empleado);
CREATE INDEX idx_ci_fecha ON compras_insumos(fecha_compra);
CREATE INDEX idx_ci_prov ON compras_insumos(id_proveedor);
CREATE INDEX idx_ciing_fecha ON compras_ingrediente(fecha_de_compra);
CREATE INDEX idx_df_fact ON detalle_factura(id_factura);
CREATE INDEX idx_viaje_fecha ON viajes(fecha_hora_salida);
CREATE INDEX idx_reserva_fecha ON reservaciones(fecha_hora);

SET FOREIGN_KEY_CHECKS = 1;

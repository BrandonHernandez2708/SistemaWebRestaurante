use marea_roja_db;
ALTER TABLE ingredientes 
ADD COLUMN descripcion VARCHAR(200) NULL AFTER nombre_ingrediente;

DELIMITER //

CREATE TRIGGER after_insert_detalle_compra
AFTER INSERT ON detalle_compra_ingrediente
FOR EACH ROW
BEGIN
    UPDATE ingredientes 
    SET cantidad_stock = cantidad_stock + NEW.cantidad_compra
    WHERE id_ingrediente = NEW.id_ingrediente;
END//

DELIMITER 

DELIMITER $$

CREATE TRIGGER trg_actualizar_stock_perdida
AFTER INSERT ON perdidas_inventario
FOR EACH ROW
BEGIN
    -- Actualiza el stock del ingrediente afectado
    UPDATE ingredientes
    SET cantidad_stock = cantidad_stock - NEW.cantidad_unitaria_perdida
    WHERE id_ingrediente = NEW.id_ingrediente;
END$$

DELIMITER ;


INSERT INTO unidades_medida (unidad, abreviatura) VALUES
-- Unidades de peso
('Gramos', 'g'),
('Kilogramos', 'kg'),
('Libras', 'lb'),
('Onzas', 'oz'),

-- Unidades de volumen
('Mililitros', 'ml'),
('Litros', 'l'),
('Tazas', 'taza'),
('Cucharadas', 'cda'),
('Cucharaditas', 'cdta'),
('Pizca', 'pizca'),

-- Unidades de cantidad
('Unidades', 'ud'),
('Docenas', 'doc'),
('Paquetes', 'pqt'),
('Latas', 'lata'),
('Botellas', 'bot'),
('Sobres', 'sobre'),

-- Unidades de longitud
('Centímetros', 'cm'),
('Metros', 'm'),

-- Otras unidades
('Raciones', 'rac'),
('Porciones', 'por'),
('Filetes', 'filete'),
('Rodajas', 'rod'),
('Dientes', 'dte'),
('Manojos', 'manojo'),
('Ramos', 'ramo');
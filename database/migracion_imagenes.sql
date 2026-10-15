USE catalogo_ventas;

-- Agregar el campo de imagen principal si todavía no existe.
SET @existe_imagen = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'productos'
      AND COLUMN_NAME = 'imagen'
);

SET @sql_imagen = IF(
    @existe_imagen = 0,
    'ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL',
    'SELECT ''El campo imagen ya existe'' AS resultado'
);

PREPARE stmt_imagen FROM @sql_imagen;
EXECUTE stmt_imagen;
DEALLOCATE PREPARE stmt_imagen;

-- Tabla para guardar las rutas de las imágenes de cada producto.
CREATE TABLE IF NOT EXISTS imagenes_producto (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    es_principal BOOLEAN NOT NULL DEFAULT FALSE,
    orden INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_imagenes_producto
        FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON DELETE CASCADE,

    UNIQUE KEY uq_producto_archivo (id_producto, nombre_archivo),
    INDEX idx_imagenes_producto (id_producto, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

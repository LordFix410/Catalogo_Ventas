USE catalogo_ventas;

DROP PROCEDURE IF EXISTS sp_portal_productos;
DELIMITER //
CREATE PROCEDURE sp_portal_productos()
BEGIN
    SELECT
        p.id_producto,
        p.nombre,
        p.descripcion,
        p.precio,
        p.stock,
        p.imagen,
        c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria
    WHERE p.activo = TRUE
      AND c.activo = TRUE
    ORDER BY p.nombre;
END //
DELIMITER ;

USE catalogo_ventas;

ALTER TABLE pedidos
    ADD COLUMN direccion_entrega VARCHAR(255) NULL AFTER id_cliente;

DROP PROCEDURE IF EXISTS sp_pedido_insertar;
DELIMITER //
CREATE PROCEDURE sp_pedido_insertar(
    IN p_id_cliente INT,
    IN p_observaciones VARCHAR(500),
    IN p_direccion_entrega VARCHAR(255)
)
BEGIN
    INSERT INTO pedidos (
        id_cliente,
        direccion_entrega,
        total,
        observaciones
    )
    VALUES (
        p_id_cliente,
        p_direccion_entrega,
        0,
        p_observaciones
    );

    SELECT LAST_INSERT_ID() AS id_pedido;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_pedido_obtener;
DELIMITER //
CREATE PROCEDURE sp_pedido_obtener(
    IN p_id_pedido INT
)
BEGIN
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        p.total,
        p.observaciones,
        COALESCE(NULLIF(p.direccion_entrega, ''), c.direccion) AS direccion_entrega,
        c.id_cliente,
        CONCAT(c.nombre, ' ', c.apellido) AS cliente,
        c.telefono
    FROM pedidos p
    INNER JOIN clientes c
        ON p.id_cliente = c.id_cliente
    WHERE p.id_pedido = p_id_pedido;
END //
DELIMITER ;

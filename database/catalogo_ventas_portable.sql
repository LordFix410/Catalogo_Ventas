-- Instalación completa para una base de datos nueva y vacía.
-- Cuentas iniciales: admin@gmail.com y cliente@gmail.com | Contraseña: 123
-- Ejecutar este archivo una sola vez en MySQL/MariaDB. No lo importes sobre tablas existentes.
CREATE DATABASE IF NOT EXISTS catalogo_ventas
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE catalogo_ventas;


#TABLA DE ROLES:
CREATE TABLE roles(
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150)
);


#TABLA DE USUARIOS:
CREATE TABLE usuarios(
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuarios_roles FOREIGN KEY(id_rol) REFERENCES roles(id_rol)
);

#TABLA DE CLIENTES 
CREATE TABLE clientes(
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(255),
    tipo_cliente ENUM('Minorista', 'Mayorista') NOT NULL DEFAULT 'Minorista',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT uq_clientes_usuario UNIQUE(id_usuario),
    CONSTRAINT fk_clientes_usuarios FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario)
);


#TABLA DE CATEGORÍAS 
CREATE TABLE categorias(
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(200),
    activo BOOLEAN NOT NULL DEFAULT TRUE
);


#TABLA DE PRODUCTOS 
CREATE TABLE productos(
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_productos_precio CHECK(precio >= 0),
    CONSTRAINT chk_productos_stock CHECK(stock >= 0),
    CONSTRAINT fk_productos_categorias FOREIGN KEY(id_categoria) REFERENCES categorias(id_categoria)
);



#TABLA DE PEDIDOS 
CREATE TABLE pedidos(
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    direccion_entrega VARCHAR(255),
    fecha_pedido DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    cargo_cancelacion DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observaciones VARCHAR(500),

    CONSTRAINT chk_pedidos_total CHECK(total >= 0),

    CONSTRAINT fk_pedidos_clientes FOREIGN KEY(id_cliente) REFERENCES clientes(id_cliente)
);



#TABLA DE DETALLE DEL PEDIDO 
CREATE TABLE detalle_pedido(
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT chk_detalle_cantidad CHECK(cantidad > 0),
    CONSTRAINT chk_detalle_precio CHECK(precio_unitario >= 0),
    CONSTRAINT chk_detalle_subtotal CHECK(subtotal >= 0),
    CONSTRAINT fk_detalle_pedidos FOREIGN KEY(id_pedido) REFERENCES pedidos(id_pedido),
    CONSTRAINT fk_detalle_productos FOREIGN KEY(id_producto) REFERENCES productos(id_producto)
);

CREATE TABLE entradas_inventario(
    id_entrada INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    referencia_factura VARCHAR(80) NULL,
    fecha_compra DATE NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    observaciones VARCHAR(500) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_entradas_total CHECK(total >= 0),
    CONSTRAINT fk_entradas_usuarios FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE detalle_entrada_inventario(
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_entrada INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT chk_entrada_cantidad CHECK(cantidad > 0),
    CONSTRAINT chk_entrada_costo CHECK(costo_unitario >= 0),
    CONSTRAINT chk_entrada_subtotal CHECK(subtotal >= 0),
    CONSTRAINT fk_detalle_entradas FOREIGN KEY(id_entrada) REFERENCES entradas_inventario(id_entrada),
    CONSTRAINT fk_detalle_entrada_productos FOREIGN KEY(id_producto) REFERENCES productos(id_producto)
);



#TABLA DE ESTADOS DEL PEDIDO 
CREATE TABLE estados_pedido(
    id_estado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    orden INT NOT NULL UNIQUE
);

#TABLA DE SEGUIMIENTO 
CREATE TABLE seguimiento_pedido(
    id_seguimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_estado INT NOT NULL,
    id_usuario INT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(500),

    CONSTRAINT fk_seguimiento_pedidos FOREIGN KEY(id_pedido) REFERENCES pedidos(id_pedido),
    CONSTRAINT fk_seguimiento_estados FOREIGN KEY(id_estado) REFERENCES estados_pedido(id_estado),
    CONSTRAINT fk_seguimiento_usuarios FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario)
);

#------------INSERTS----------

INSERT INTO roles(nombre, descripcion) VALUES
('Administrador', 'Acceso completo al sistema'),
('Empleado', 'Acceso a las funciones administrativas autorizadas'),
('Cliente', 'Acceso al portal del cliente');

INSERT INTO estados_pedido(nombre, orden) VALUES
('Recibido', 1),
('En preparación', 2),
('Preparado', 3),
('Despachado', 4),
('En proceso de entrega', 5),
('Entregado', 6),
('Cancelado', 7);

INSERT INTO usuarios (id_rol, nombre, correo, password, activo) VALUES
(1, 'Administrador', 'admin@gmail.com', '$2y$10$LPQ3shuHwV6CyCi3QSfqKepIdtwtuqpZk7w8HLwtHMa.Uf1FmAorO', TRUE),
(3, 'Cliente Demo', 'cliente@gmail.com', '$2y$10$LPQ3shuHwV6CyCi3QSfqKepIdtwtuqpZk7w8HLwtHMa.Uf1FmAorO', TRUE);

INSERT INTO categorias(nombre, descripcion) VALUES
('Ropa', 'Prendas de vestir'),
('Accesorios', 'Accesorios y complementos'),
('Belleza', 'Productos de belleza y cuidado personal'),
('Hogar', 'Artículos para el hogar');

-- Clientes de ejemplo: Ana tiene acceso al portal con cliente@gmail.com / 123.
-- Los demás clientes no tienen usuario y sirven para pedidos creados por administración.
INSERT INTO clientes
    (id_cliente, id_usuario, nombre, apellido, telefono, direccion, tipo_cliente, activo)
VALUES
    (1, 2, 'Ana', 'Gómez', '5555-9012', 'Zona 1, Ciudad de Guatemala', 'Minorista', TRUE),
    (2, NULL, 'Carlos', 'Méndez', '5555-2381', 'Zona 7, Ciudad de Guatemala', 'Mayorista', TRUE),
    (3, NULL, 'Sofía', 'López', '5555-7440', 'Mixco, Guatemala', 'Minorista', TRUE),
    (4, NULL, 'María', 'Pérez', '5555-1836', 'Zona 12, Ciudad de Guatemala', 'Mayorista', TRUE),
    (5, NULL, 'Lucía', 'Ramírez', '5555-6621', 'Villa Nueva, Guatemala', 'Minorista', TRUE);

-- Productos de muestra. El stock representa existencias actuales después de los pedidos de ejemplo.
-- Las imágenes se encuentran en assets/productos/.
INSERT INTO productos
    (id_producto, id_categoria, nombre, descripcion, precio, stock, imagen, activo)
VALUES
    (1, 1, 'Blusa Rosa Chiquis', 'Blusa casual color rosa con manga ajustable.', 150.00, 14, 'assets/productos/761710cfcaae08ab697fc67df959e524.png', TRUE),
    (2, 1, 'Pantalón Lila Estampado', 'Pantalón de tiro alto con estampado en tonos lila.', 220.00, 8, 'assets/productos/633db2d5a0b0cd8b066758431f1a7a75.png', TRUE),
    (3, 3, 'Paleta de Sombras Colorida', 'Paleta de sombras con tonos mate y brillantes.', 95.00, 12, 'assets/productos/909f67c9214224da81b36bab865d355d.png', TRUE),
    (4, 2, 'Bolso Acolchado Morado', 'Bolso de mano acolchado con cadena decorativa.', 185.00, 6, 'assets/productos/6232da93bf49e14d90693cd400ea51a8.png', TRUE),
    (5, 1, 'Jeans Acampanados', 'Jeans azul de corte acampanado y cintura alta.', 210.00, 9, 'assets/productos/3c22735fca62f5b8a92f1869f40602bd.png', TRUE);

-- Pedidos de ejemplo con totales que coinciden con sus respectivos detalles.
INSERT INTO pedidos
    (id_pedido, id_cliente, direccion_entrega, fecha_pedido, total, observaciones)
VALUES
    (1, 1, 'Zona 1, Ciudad de Guatemala', CURRENT_TIMESTAMP - INTERVAL 6 DAY, 430.00, 'Entregar por la tarde.'),
    (2, 2, 'Zona 7, Ciudad de Guatemala', CURRENT_TIMESTAMP - INTERVAL 3 DAY, 650.00, 'Pedido mayorista.'),
    (3, 3, 'Mixco, Guatemala', CURRENT_TIMESTAMP - INTERVAL 1 DAY, 465.00, 'Avisar antes de llegar.'),
    (4, 4, 'Zona 12, Ciudad de Guatemala', CURRENT_TIMESTAMP - INTERVAL 2 HOUR, 150.00, NULL),
    (5, 5, 'Villa Nueva, Guatemala', CURRENT_TIMESTAMP - INTERVAL 2 DAY, 420.00, 'Entregar en recepción.');

INSERT INTO detalle_pedido
    (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
VALUES
    (1, 1, 1, 150.00, 150.00),
    (1, 3, 1, 95.00, 95.00),
    (1, 4, 1, 185.00, 185.00),
    (2, 2, 2, 220.00, 440.00),
    (2, 5, 1, 210.00, 210.00),
    (3, 4, 2, 185.00, 370.00),
    (3, 3, 1, 95.00, 95.00),
    (4, 1, 1, 150.00, 150.00),
    (5, 5, 2, 210.00, 420.00);

-- Historial de estados de ejemplo; el pedido 1 está entregado.
INSERT INTO seguimiento_pedido
    (id_pedido, id_estado, id_usuario, fecha_hora, observacion)
VALUES
    (1, 1, 1, CURRENT_TIMESTAMP - INTERVAL 6 DAY, 'Hemos recibido su pedido.'),
    (1, 2, 1, CURRENT_TIMESTAMP - INTERVAL 6 DAY + INTERVAL 2 HOUR, 'Pedido en preparación.'),
    (1, 3, 1, CURRENT_TIMESTAMP - INTERVAL 5 DAY, 'Pedido preparado.'),
    (1, 4, 1, CURRENT_TIMESTAMP - INTERVAL 5 DAY + INTERVAL 2 HOUR, 'Pedido despachado.'),
    (1, 5, 1, CURRENT_TIMESTAMP - INTERVAL 5 DAY + INTERVAL 4 HOUR, 'Pedido en camino.'),
    (1, 6, 1, CURRENT_TIMESTAMP - INTERVAL 5 DAY + INTERVAL 6 HOUR, 'Pedido entregado.'),
    (2, 1, 1, CURRENT_TIMESTAMP - INTERVAL 3 DAY, 'Hemos recibido su pedido.'),
    (2, 2, 1, CURRENT_TIMESTAMP - INTERVAL 2 DAY, 'Pedido en preparación.'),
    (2, 3, 1, CURRENT_TIMESTAMP - INTERVAL 1 DAY, 'Pedido preparado.'),
    (3, 1, 1, CURRENT_TIMESTAMP - INTERVAL 1 DAY, 'Hemos recibido su pedido.'),
    (3, 2, 1, CURRENT_TIMESTAMP - INTERVAL 20 HOUR, 'Pedido en preparación.'),
    (3, 3, 1, CURRENT_TIMESTAMP - INTERVAL 12 HOUR, 'Pedido preparado.'),
    (3, 4, 1, CURRENT_TIMESTAMP - INTERVAL 6 HOUR, 'Pedido despachado.'),
    (3, 5, 1, CURRENT_TIMESTAMP - INTERVAL 2 HOUR, 'Pedido en camino.'),
    (4, 1, 1, CURRENT_TIMESTAMP - INTERVAL 2 HOUR, 'Hemos recibido su pedido.'),
    (5, 1, 1, CURRENT_TIMESTAMP - INTERVAL 2 DAY, 'Hemos recibido su pedido.'),
    (5, 2, 1, CURRENT_TIMESTAMP - INTERVAL 1 DAY, 'Pedido en preparación.');


#---------------------------ÍNDICES 
CREATE INDEX idx_usuarios_rol ON usuarios(id_rol);
CREATE INDEX idx_productos_categoria ON productos(id_categoria);
CREATE INDEX idx_pedidos_cliente ON pedidos(id_cliente);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_pedido);
CREATE INDEX idx_detalle_pedido ON detalle_pedido(id_pedido);
CREATE INDEX idx_detalle_producto ON detalle_pedido(id_producto);
CREATE INDEX idx_seguimiento_pedido ON seguimiento_pedido(id_pedido);
CREATE INDEX idx_seguimiento_estado ON seguimiento_pedido(id_estado);
CREATE INDEX idx_seguimiento_fecha ON seguimiento_pedido(fecha_hora);

/***************** PROCEDIMIENTOS ALMACENADOS *******************/

DELIMITER //

/***** USUARIOS *****/

CREATE PROCEDURE sp_usuario_insertar(
    IN p_id_rol INT,
    IN p_nombre VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_password VARCHAR(255)
)
BEGIN
    INSERT INTO usuarios (
        id_rol,
        nombre,
        correo,
        password
    )
    VALUES (
        p_id_rol,
        p_nombre,
        p_correo,
        p_password
    );
END //

CREATE PROCEDURE sp_usuario_listar()
BEGIN
    SELECT
        u.id_usuario,
        u.nombre,
        u.correo,
        r.nombre AS rol,
        u.activo,
        u.fecha_registro
    FROM usuarios u
    INNER JOIN roles r
        ON u.id_rol = r.id_rol
    ORDER BY u.id_usuario DESC;
END //

CREATE PROCEDURE sp_usuario_buscar(
    IN p_correo VARCHAR(100)
)
BEGIN
    SELECT
        u.id_usuario,
        u.id_rol,
        u.nombre,
        u.correo,
        u.password,
        u.activo,
        r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r
        ON u.id_rol = r.id_rol
    WHERE u.correo = p_correo
    LIMIT 1;
END //

CREATE PROCEDURE sp_usuario_actualizar(
    IN p_id_usuario INT,
    IN p_id_rol INT,
    IN p_nombre VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_activo BOOLEAN
)
BEGIN
    UPDATE usuarios
    SET
        id_rol = p_id_rol,
        nombre = p_nombre,
        correo = p_correo,
        activo = p_activo
    WHERE id_usuario = p_id_usuario;
END //

/********* CLIENTES ***********/

CREATE PROCEDURE sp_cliente_insertar(
    IN p_id_usuario INT,
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_direccion VARCHAR(255),
    IN p_tipo_cliente VARCHAR(20)
)
BEGIN
    INSERT INTO clientes (
        id_usuario,
        nombre,
        apellido,
        telefono,
        direccion,
        tipo_cliente
    )
    VALUES (
        p_id_usuario,
        p_nombre,
        p_apellido,
        p_telefono,
        p_direccion,
        p_tipo_cliente
    );
END //

CREATE PROCEDURE sp_cliente_listar()
BEGIN
    SELECT
        c.id_cliente,
        c.nombre,
        c.apellido,
        c.telefono,
        c.direccion,
        c.tipo_cliente,
        c.fecha_registro,
        c.activo,
        u.correo
    FROM clientes c
    LEFT JOIN usuarios u
        ON c.id_usuario = u.id_usuario
    ORDER BY c.id_cliente DESC;
END //

CREATE PROCEDURE sp_cliente_obtener(
    IN p_id_cliente INT
)
BEGIN
    SELECT
        c.*,
        u.correo
    FROM clientes c
    LEFT JOIN usuarios u
        ON c.id_usuario = u.id_usuario
    WHERE c.id_cliente = p_id_cliente;
END //

CREATE PROCEDURE sp_cliente_actualizar(
    IN p_id_cliente INT,
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_direccion VARCHAR(255),
    IN p_tipo_cliente VARCHAR(20)
)
BEGIN
    UPDATE clientes
    SET
        nombre = p_nombre,
        apellido = p_apellido,
        telefono = p_telefono,
        direccion = p_direccion,
        tipo_cliente = p_tipo_cliente
    WHERE id_cliente = p_id_cliente;
END //

CREATE PROCEDURE sp_cliente_eliminar(
    IN p_id_cliente INT
)
BEGIN
    UPDATE clientes
    SET activo = FALSE
    WHERE id_cliente = p_id_cliente;
END //

/********** CATEGORÍAS **********/

CREATE PROCEDURE sp_categoria_insertar(
    IN p_nombre VARCHAR(80),
    IN p_descripcion VARCHAR(200)
)
BEGIN
    INSERT INTO categorias (
        nombre,
        descripcion
    )
    VALUES (
        p_nombre,
        p_descripcion
    );
END //

CREATE PROCEDURE sp_categoria_listar()
BEGIN
    SELECT *
    FROM categorias
    WHERE activo = TRUE
    ORDER BY nombre;
END //

CREATE PROCEDURE sp_categoria_actualizar(
    IN p_id_categoria INT,
    IN p_nombre VARCHAR(80),
    IN p_descripcion VARCHAR(200)
)
BEGIN
    UPDATE categorias
    SET
        nombre = p_nombre,
        descripcion = p_descripcion
    WHERE id_categoria = p_id_categoria;
END //

CREATE PROCEDURE sp_categoria_eliminar(
    IN p_id_categoria INT
)
BEGIN
    UPDATE categorias
    SET activo = FALSE
    WHERE id_categoria = p_id_categoria;
END //

/* PRODUCTOS */

CREATE PROCEDURE sp_producto_insertar(
    IN p_id_categoria INT,
    IN p_nombre VARCHAR(120),
    IN p_descripcion TEXT,
    IN p_precio DECIMAL(10,2),
    IN p_stock INT,
    IN p_imagen VARCHAR(255)
)
BEGIN
    INSERT INTO productos (
        id_categoria,
        nombre,
        descripcion,
        precio,
        stock,
        imagen
    )
    VALUES (
        p_id_categoria,
        p_nombre,
        p_descripcion,
        p_precio,
        p_stock,
        p_imagen
    );
END //

CREATE PROCEDURE sp_producto_listar()
BEGIN
    SELECT
        p.id_producto,
        p.nombre,
        p.descripcion,
        p.precio,
        p.stock,
        p.imagen,
        p.activo,
        p.fecha_registro,
        c.id_categoria,
        c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria
    ORDER BY p.id_producto DESC;
END //

CREATE PROCEDURE sp_producto_obtener(
    IN p_id_producto INT
)
BEGIN
    SELECT
        p.*,
        c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = p_id_producto;
END //

CREATE PROCEDURE sp_producto_actualizar(
    IN p_id_producto INT,
    IN p_id_categoria INT,
    IN p_nombre VARCHAR(120),
    IN p_descripcion TEXT,
    IN p_precio DECIMAL(10,2),
    IN p_stock INT,
    IN p_imagen VARCHAR(255)
)
BEGIN
    UPDATE productos
    SET
        id_categoria = p_id_categoria,
        nombre = p_nombre,
        descripcion = p_descripcion,
        precio = p_precio,
        stock = p_stock,
        imagen = p_imagen
    WHERE id_producto = p_id_producto;
END //

CREATE PROCEDURE sp_producto_eliminar(
    IN p_id_producto INT
)
BEGIN
    UPDATE productos
    SET activo = FALSE
    WHERE id_producto = p_id_producto;
END //

/******** PEDIDOS ***********/

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

CREATE PROCEDURE sp_pedido_listar()
BEGIN
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        p.total,
        p.cargo_cancelacion,
        p.observaciones,
        c.id_cliente,
        CONCAT(c.nombre, ' ', c.apellido) AS cliente
    FROM pedidos p
    INNER JOIN clientes c
        ON p.id_cliente = c.id_cliente
    ORDER BY p.fecha_pedido DESC;
END //

CREATE PROCEDURE sp_pedido_obtener(
    IN p_id_pedido INT
)
BEGIN
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        p.total,
        p.cargo_cancelacion,
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

/********** DETALLE DE PEDIDO *********/

CREATE PROCEDURE sp_detalle_insertar(
    IN p_id_pedido INT,
    IN p_id_producto INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_stock INT;

    SELECT precio, stock
    INTO v_precio, v_stock
    FROM productos
    WHERE id_producto = p_id_producto
      AND activo = TRUE;

    IF v_precio IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El producto no existe o está inactivo';
    END IF;

    IF p_cantidad <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La cantidad debe ser mayor que cero';
    END IF;

    IF v_stock < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente';
    END IF;

    INSERT INTO detalle_pedido (
        id_pedido,
        id_producto,
        cantidad,
        precio_unitario,
        subtotal
    )
    VALUES (
        p_id_pedido,
        p_id_producto,
        p_cantidad,
        v_precio,
        v_precio * p_cantidad
    );

    UPDATE productos
    SET stock = stock - p_cantidad
    WHERE id_producto = p_id_producto;

    UPDATE pedidos
    SET total = (
        SELECT COALESCE(SUM(subtotal), 0)
        FROM detalle_pedido
        WHERE id_pedido = p_id_pedido
    )
    WHERE id_pedido = p_id_pedido;
END //

CREATE PROCEDURE sp_detalle_listar(
    IN p_id_pedido INT
)
BEGIN
    SELECT
        d.id_detalle,
        d.id_producto,
        pr.nombre AS producto,
        d.cantidad,
        d.precio_unitario,
        d.subtotal
    FROM detalle_pedido d
    INNER JOIN productos pr
        ON d.id_producto = pr.id_producto
    WHERE d.id_pedido = p_id_pedido
    ORDER BY d.id_detalle;
END //

/*********** SEGUIMIENTO ************/

CREATE PROCEDURE sp_seguimiento_iniciar(
    IN p_id_pedido INT,
    IN p_id_usuario INT
)
BEGIN
    INSERT INTO seguimiento_pedido (
        id_pedido,
        id_estado,
        id_usuario,
        observacion
    )
    VALUES (
        p_id_pedido,
        1,
        p_id_usuario,
        'Pedido recibido'
    );
END //

CREATE PROCEDURE sp_seguimiento_actualizar(
    IN p_id_pedido INT,
    IN p_id_estado INT,
    IN p_id_usuario INT,
    IN p_observacion VARCHAR(500)
)
BEGIN
    INSERT INTO seguimiento_pedido (
        id_pedido,
        id_estado,
        id_usuario,
        observacion
    )
    VALUES (
        p_id_pedido,
        p_id_estado,
        p_id_usuario,
        p_observacion
    );
END //

CREATE PROCEDURE sp_seguimiento_historial(
    IN p_id_pedido INT
)
BEGIN
    SELECT
        s.id_seguimiento,
        s.id_pedido,
        e.id_estado,
        e.nombre AS estado,
        e.orden,
        s.fecha_hora,
        s.observacion,
        u.nombre AS actualizado_por
    FROM seguimiento_pedido s
    INNER JOIN estados_pedido e
        ON s.id_estado = e.id_estado
    LEFT JOIN usuarios u
        ON s.id_usuario = u.id_usuario
    WHERE s.id_pedido = p_id_pedido
    ORDER BY s.fecha_hora ASC,
             s.id_seguimiento ASC;
END //

CREATE PROCEDURE sp_seguimiento_listar()
BEGIN
    SELECT
        p.id_pedido,
        CONCAT(c.nombre, ' ', c.apellido) AS cliente,
        p.fecha_pedido,
        p.total,
        e.id_estado,
        e.nombre AS estado,
        COALESCE(e.orden, 1) AS orden,
        s.fecha_hora AS ultima_actualizacion
    FROM pedidos p
    INNER JOIN clientes c
        ON p.id_cliente = c.id_cliente
    LEFT JOIN seguimiento_pedido s
        ON s.id_seguimiento = (
            SELECT s2.id_seguimiento
            FROM seguimiento_pedido s2
            WHERE s2.id_pedido = p.id_pedido
            ORDER BY s2.fecha_hora DESC,
                     s2.id_seguimiento DESC
            LIMIT 1
        )
    LEFT JOIN estados_pedido e
        ON s.id_estado = e.id_estado
    ORDER BY p.fecha_pedido DESC;
END //

/*********** PORTAL DEL CLIENTE **************/

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

CREATE PROCEDURE sp_portal_pedidos_cliente(
    IN p_id_cliente INT
)
BEGIN
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        p.total,
        e.nombre AS estado
    FROM pedidos p
    LEFT JOIN seguimiento_pedido s
        ON s.id_seguimiento = (
            SELECT s2.id_seguimiento
            FROM seguimiento_pedido s2
            WHERE s2.id_pedido = p.id_pedido
            ORDER BY s2.fecha_hora DESC,
                     s2.id_seguimiento DESC
            LIMIT 1
        )
    LEFT JOIN estados_pedido e
        ON s.id_estado = e.id_estado
    WHERE p.id_cliente = p_id_cliente
    ORDER BY p.fecha_pedido DESC;
END //

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_dashboard_resumen;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_resumen()
BEGIN

    SELECT
        (SELECT COUNT(*)
         FROM clientes
         WHERE activo = 1) AS total_clientes,

        (SELECT COUNT(*)
         FROM productos
         WHERE activo = 1) AS total_productos,

        (SELECT COUNT(*)
         FROM pedidos) AS total_pedidos,

        (SELECT COUNT(*)
         FROM seguimiento_pedido) AS total_seguimiento;

END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_cliente_por_usuario;
DELIMITER //
CREATE PROCEDURE sp_cliente_por_usuario(
    IN p_id_usuario INT
)
BEGIN
    SELECT
        c.id_cliente,
        c.id_usuario,
        c.nombre,
        c.apellido,
        c.telefono,
        c.direccion,
        c.tipo_cliente,
        c.fecha_registro,
        c.activo
    FROM clientes c
    WHERE c.id_usuario = p_id_usuario
      AND c.activo = 1
    LIMIT 1;
END //

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_portal_seguimiento_cliente;

DELIMITER //

CREATE PROCEDURE sp_portal_seguimiento_cliente(
    IN p_id_usuario INT
)
BEGIN
    SELECT
        p.id_pedido,
        p.fecha_pedido,
        p.total,
        e.id_estado,
        e.nombre AS estado,
        s.fecha_hora AS ultima_actualizacion
    FROM pedidos p
    INNER JOIN clientes c
        ON p.id_cliente = c.id_cliente
    LEFT JOIN seguimiento_pedido s
        ON s.id_seguimiento = (
            SELECT s2.id_seguimiento
            FROM seguimiento_pedido s2
            WHERE s2.id_pedido = p.id_pedido
            ORDER BY s2.fecha_hora DESC,
                     s2.id_seguimiento DESC
            LIMIT 1
        )
    LEFT JOIN estados_pedido e
        ON s.id_estado = e.id_estado
    WHERE c.id_usuario = p_id_usuario
    ORDER BY p.fecha_pedido DESC;
END //

DELIMITER ;

SHOW PROCEDURE STATUS WHERE Db = 'catalogo_ventas';

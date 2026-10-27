USE catalogo_ventas;

-- Registra facturas de compra y su detalle sin modificar el precio de venta.
CREATE TABLE IF NOT EXISTS entradas_inventario(
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

CREATE TABLE IF NOT EXISTS detalle_entrada_inventario(
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

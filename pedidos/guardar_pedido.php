<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido.');
}


$id_cliente        = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$fecha_pedido      = $_POST['fecha_pedido'] ?? date('Y-m-d H:i:s');
$observaciones     = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT) ?? '';
$productos_enviados = $_POST['productos'] ?? [];

if (!$id_cliente || !$fecha_pedido || empty($productos_enviados)) {
    die('Faltan datos obligatorios para registrar el pedido.');
}

try {
    $pdo->beginTransaction();

    $stmtPedido = $pdo->prepare(
        "INSERT INTO pedidos (id_cliente, fecha_pedido, total, observaciones)
         VALUES (:id_cliente, :fecha_pedido, 0.00, :observaciones)"
    );
    $stmtPedido->execute([
        ':id_cliente'    => $id_cliente,
        ':fecha_pedido'  => $fecha_pedido,
        ':observaciones' => $observaciones
    ]);
    $id_pedido = $pdo->lastInsertId();

    $stmtPrecio  = $pdo->prepare("SELECT precio, stock FROM productos WHERE id_producto = :id");
    $stmtDetalle = $pdo->prepare(
        "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
         VALUES (:id_pedido, :id_producto, :cantidad, :precio, :subtotal)"
    );
    $stmtStock   = $pdo->prepare(
        "UPDATE productos SET stock = stock - :cantidad WHERE id_producto = :id_producto"
    );

    $total_pedido = 0;

    foreach ($productos_enviados as $item) {
        $id_producto = filter_var($item['id_producto'], FILTER_VALIDATE_INT);
        $cantidad    = filter_var($item['cantidad'], FILTER_VALIDATE_INT);

        if (!$id_producto || !$cantidad || $cantidad < 1) {
            continue; // Fila vacía o cantidad no válida
        }

        $stmtPrecio->execute([':id' => $id_producto]);
        $producto = $stmtPrecio->fetch();

        if (!$producto) {
            continue; // Producto no existe
        }

        if ($producto['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para el producto ID: {$id_producto}");
        }

        $precio_unitario = $producto['precio'];
        $subtotal        = $precio_unitario * $cantidad;
        $total_pedido   += $subtotal;

        $stmtDetalle->execute([
            ':id_pedido'   => $id_pedido,
            ':id_producto' => $id_producto,
            ':cantidad'    => $cantidad,
            ':precio'      => $precio_unitario,
            ':subtotal'    => $subtotal,
        ]);

        $stmtStock->execute([
            ':cantidad'    => $cantidad,
            ':id_producto' => $id_producto
        ]);
    }

    $stmtTotal = $pdo->prepare("UPDATE pedidos SET total = :total WHERE id_pedido = :id_pedido");
    $stmtTotal->execute([':total' => $total_pedido, ':id_pedido' => $id_pedido]);

    $stmtSeguimiento = $pdo->prepare(
        "INSERT INTO seguimiento_pedidos (id_pedido, id_estado, id_usuario, fecha_hora, observacion)
         VALUES (:id_pedido, 1, 1, NOW(), 'Pedido creado e ingresado al sistema')"
    );
    $stmtSeguimiento->execute([':id_pedido' => $id_pedido]);

    $pdo->commit();

    header('Location: ver_pedido.php?id=' . $id_pedido);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die('Error al guardar el pedido: ' . $e->getMessage());
}
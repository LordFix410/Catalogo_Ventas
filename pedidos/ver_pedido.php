<?php
require_once 'config.php';

$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_pedido) {
    die('Pedido no válido.');
}

$stmt = $pdo->prepare(
    "SELECT p.id_pedido, p.fecha_pedido, p.total, p.observaciones,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente, c.tipo_cliente, c.telefono, c.direccion,
            COALESCE(ep.nombre, 'Pendiente') AS estado
     FROM pedidos p
     JOIN clientes c ON c.id_cliente = p.id_cliente
     LEFT JOIN seguimiento_pedidos sp ON sp.id_seguimiento = (
         SELECT MAX(id_seguimiento) 
         FROM seguimiento_pedidos 
         WHERE id_pedido = p.id_pedido
     )
     LEFT JOIN estados_pedidos ep ON ep.id_estado = sp.id_estado
     WHERE p.id_pedido = :id"
);
$stmt->execute([':id' => $id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die('El pedido no existe.');
}

$stmtDetalle = $pdo->prepare(
    "SELECT pr.nombre AS producto, d.cantidad, d.precio_unitario, d.subtotal
     FROM detalle_pedido d
     JOIN productos pr ON pr.id_producto = d.id_producto
     WHERE d.id_pedido = :id"
);
$stmtDetalle->execute([':id' => $id_pedido]);
$detalle = $stmtDetalle->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido #<?= $pedido['id_pedido'] ?> - Variedades Chiquis</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 30px; background-color: #f4f6f9; color: #333; }
    .container { max-width: 850px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { font-size: 22px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; background-color: #e3f2fd; color: #0d47a1; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 6px; }
    .info-grid p { margin: 5px 0; font-size: 14px; }
    table { border-collapse: collapse; width: 100%; margin-top: 15px; }
    th, td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
    th { background-color: #f1f3f5; font-size: 13px; color: #495057; }
    .total-box { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; color: #2c3e50; }
    .acciones { margin-top: 25px; display: flex; gap: 10px; }
    .btn { padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; }
    .btn-primary { background-color: #3498db; color: white; }
    .btn-secondary { background-color: #95a5a6; color: white; }
    .btn:hover { opacity: 0.9; }
</style>
</head>
<body>

<div class="container">
    <h1>Pedido #<?= $pedido['id_pedido'] ?></h1>

    <div class="info-grid">
        <div>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente']) ?></p>
            <p><strong>Tipo de cliente:</strong> <?= htmlspecialchars($pedido['tipo_cliente']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono'] ?? 'N/A') ?></p>
        </div>
        <div>
            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></p>
            <p><strong>Estado:</strong> <span class="badge"><?= htmlspecialchars($pedido['estado']) ?></span></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion'] ?? 'N/A') ?></p>
        </div>
    </div>

    <?php if (!empty($pedido['observaciones'])): ?>
        <p><strong>Observaciones:</strong> <?= htmlspecialchars($pedido['observaciones']) ?></p>
    <?php endif; ?>

    <h3>Detalle de Productos</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalle as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['producto']) ?></td>
                <td><?= $d['cantidad'] ?></td>
                <td>Q <?= number_format($d['precio_unitario'], 2) ?></td>
                <td>Q <?= number_format($d['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        Total del Pedido: Q <?= number_format($pedido['total'], 2) ?>
    </div>

    <div class="acciones">
        <a href="crear_pedido.php" class="btn btn-primary">+ Registrar otro pedido</a>
    </div>
</div>

</body>
</html>
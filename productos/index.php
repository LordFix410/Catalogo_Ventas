<?php
require_once __DIR__ . '/funciones.php';
$conexion = Database::getConnection();
$productos = productosEjecutar($conexion, 'CALL sp_producto_listar()');
$activos = count(array_filter($productos, fn($p) => (bool)$p['activo']));
$inactivos = count($productos) - $activos;
$mensajes = ['creado'=>'Producto registrado correctamente.','actualizado'=>'Producto actualizado correctamente.','desactivado'=>'Producto desactivado.','restaurado'=>'Producto reactivado.'];
$mensaje = $mensajes[$_GET['msg'] ?? ''] ?? null;
productosEncabezado('Productos', 'productos');
?>
<?php if ($mensaje): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= productosEsc($mensaje) ?></div><?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div class="stat-content"><span class="stat-title">Productos registrados</span><strong class="stat-number"><?= count($productos) ?></strong><span class="stat-description">Incluye productos inactivos</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div class="stat-content"><span class="stat-title">Activos</span><strong class="stat-number"><?= $activos ?></strong><span class="stat-description">Visibles en el catálogo</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-archive"></i></div><div class="stat-content"><span class="stat-title">Inactivos</span><strong class="stat-number"><?= $inactivos ?></strong><span class="stat-description">Conservados en el sistema</span></div></div></div>
  <div class="col-sm-6 col-xl-3 d-flex align-items-center"><a href="crear.php" class="btn btn-chiquis w-100"><i class="bi bi-plus-lg me-2"></i>Registrar producto</a></div>
</div>
<div class="card"><div class="card-header"><h2 class="card-title mb-0">Inventario de productos</h2></div><div class="card-body"><div class="table-responsive">
<table class="table table-hover align-middle"><thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Existencias</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($productos as $p): ?><tr class="<?= $p['activo'] ? '' : 'text-muted' ?>">
<td><div class="d-flex align-items-center gap-3"><?php if (!empty($p['imagen'])): ?><img src="/<?= productosEsc($p['imagen']) ?>" alt="" style="width:54px;height:54px;object-fit:cover;border-radius:10px"><?php else: ?><span class="d-flex align-items-center justify-content-center bg-body-tertiary rounded" style="width:54px;height:54px"><i class="bi bi-image text-muted"></i></span><?php endif; ?><div><strong><?= productosEsc($p['nombre']) ?></strong><div class="small text-muted"><?= productosEsc(mb_strimwidth((string)($p['descripcion'] ?? ''), 0, 65, '…')) ?></div></div></div></td>
<td><?= productosEsc($p['categoria']) ?></td><td class="fw-bold">Q <?= number_format((float)$p['precio'],2) ?></td><td><?= (int)$p['stock'] ?></td><td><?= $p['activo'] ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>' ?></td>
<td><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?= (int)$p['id_producto'] ?>" title="Editar"><i class="bi bi-pencil"></i></a><?php if ($p['activo']): ?><form method="post" action="eliminar.php" onsubmit="return confirm('¿Desactivar este producto? Se conservará en el historial de pedidos.');"><input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>"><button class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="bi bi-archive"></i></button></form><?php endif; ?></div></td></tr>
<?php endforeach; ?>
<?php if (!$productos): ?><tr><td colspan="6" class="text-center text-muted py-5">Aún no hay productos registrados. Crea el primero para mostrarlo en el catálogo.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php productosPie(); ?>

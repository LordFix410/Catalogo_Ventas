<?php
require_once __DIR__ . '/funciones.php';
if (empty($_SESSION['token_producto_estado'])) $_SESSION['token_producto_estado'] = bin2hex(random_bytes(32));
$conexion = Database::getConnection();
$productos = productosEjecutar($conexion, 'CALL sp_producto_listar()');
$activos = count(array_filter($productos, fn($p) => (bool)$p['activo']));
$inactivos = count($productos) - $activos;
$mensajes = ['creado'=>'Producto registrado correctamente.','actualizado'=>'Producto actualizado correctamente.','desactivado'=>'Producto desactivado.','restaurado'=>'Producto reactivado.'];
$mensaje = $mensajes[$_GET['msg'] ?? ''] ?? null;
$errores = ['solicitud' => 'La sesión de seguridad expiró. Recarga la página e inténtalo de nuevo.'];
$error = $errores[$_GET['error'] ?? ''] ?? null;
productosEncabezado('Productos', 'productos');
?>
<?php if ($mensaje): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= productosEsc($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= productosEsc($error) ?></div><?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div class="stat-content"><span class="stat-title">Productos registrados</span><strong class="stat-number"><?= count($productos) ?></strong><span class="stat-description">Incluye productos inactivos</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div class="stat-content"><span class="stat-title">Activos</span><strong class="stat-number"><?= $activos ?></strong><span class="stat-description">Visibles en el catálogo</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="dashboard-stat stat-productos"><div class="stat-icon"><i class="bi bi-archive"></i></div><div class="stat-content"><span class="stat-title">Inactivos</span><strong class="stat-number"><?= $inactivos ?></strong><span class="stat-description">Conservados en el sistema</span></div></div></div>
  <div class="col-sm-6 col-xl-3 d-flex align-items-center"><a href="crear.php" class="btn btn-chiquis w-100"><i class="bi bi-plus-lg me-2"></i>Registrar producto</a></div>
</div>
<div class="card"><div class="card-header"><h2 class="card-title mb-0">Inventario de productos</h2></div><div class="card-body"><div class="table-responsive">
<table class="table table-hover align-middle tabla-productos"><thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Existencias</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($productos as $p): ?><tr class="<?= $p['activo'] ? '' : 'text-muted' ?>">
<td data-label="Producto"><div class="producto-inventario"><div class="producto-inventario-imagen"><?php if (!empty($p['imagen'])): ?><img src="/<?= productosEsc($p['imagen']) ?>" alt=""><?php else: ?><i class="bi bi-image text-muted"></i><?php endif; ?></div><div class="producto-inventario-texto"><strong><?= productosEsc($p['nombre']) ?></strong><div class="small text-muted"><?= productosEsc(mb_strimwidth((string)($p['descripcion'] ?? ''), 0, 65, '…')) ?></div></div></div></td>
<td data-label="Categoría"><?= productosEsc($p['categoria']) ?></td><td data-label="Precio"><span class="producto-inventario-valor">Q <?= number_format((float)$p['precio'],2) ?></span></td><td data-label="Existencias"><span class="producto-inventario-valor"><?= (int)$p['stock'] ?></span></td><td data-label="Estado"><?= $p['activo'] ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>' ?></td>
<td data-label="Acciones"><div class="d-flex gap-2 acciones-producto"><a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?= (int)$p['id_producto'] ?>" title="Editar"><i class="bi bi-pencil"></i></a><?php if ($p['activo']): ?><button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDesactivarProducto" data-producto-id="<?= (int)$p['id_producto'] ?>" data-producto-nombre="<?= productosEsc($p['nombre']) ?>" title="Desactivar"><i class="bi bi-archive"></i></button><?php endif; ?></div></td></tr>
<?php endforeach; ?>
<?php if (!$productos): ?><tr><td colspan="6" class="text-center text-muted py-5">Aún no hay productos registrados. Crea el primero para mostrarlo en el catálogo.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="modal fade" id="modalDesactivarProducto" tabindex="-1" aria-labelledby="modalDesactivarTitulo" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header"><h2 class="modal-title fs-5 fw-bold" id="modalDesactivarTitulo"><i class="bi bi-archive text-danger me-2"></i>Desactivar producto</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><p class="mb-2">¿Deseas desactivar <strong id="nombreProductoDesactivar"></strong>?</p><p class="small text-muted mb-0">Dejará de aparecer en el catálogo y se conservará en el historial de pedidos.</p></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><form method="post" action="eliminar.php" class="m-0"><input type="hidden" name="id" id="idProductoDesactivar"><input type="hidden" name="token" value="<?= productosEsc($_SESSION['token_producto_estado']) ?>"><button type="submit" class="btn btn-danger"><i class="bi bi-archive me-1"></i>Desactivar</button></form></div></div></div></div>
<script>document.getElementById('modalDesactivarProducto')?.addEventListener('show.bs.modal', event => { const trigger = event.relatedTarget; document.getElementById('nombreProductoDesactivar').textContent = trigger.dataset.productoNombre || 'este producto'; document.getElementById('idProductoDesactivar').value = trigger.dataset.productoId || ''; });</script>
<?php productosPie(); ?>

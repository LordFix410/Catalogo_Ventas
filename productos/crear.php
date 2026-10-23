<?php
require_once __DIR__ . '/funciones.php';
$conexion = Database::getConnection();
$categorias = productosCategorias($conexion);
$error = '';
$datos = ['nombre'=>'','descripcion'=>'','precio'=>'','stock'=>'0','id_categoria'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $_) $datos[$campo] = trim((string)($_POST[$campo] ?? ''));
    $precio = filter_var($datos['precio'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($datos['stock'], FILTER_VALIDATE_INT);
    $idCategoria = filter_var($datos['id_categoria'], FILTER_VALIDATE_INT);
    if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 120 || $precio === false || $precio < 0 || $stock === false || $stock < 0 || !$idCategoria || mb_strlen($datos['descripcion']) > 65535) $error = 'Revisa los campos: nombre obligatorio, precio y existencias no negativos y categoría válida.';
    elseif (!in_array($idCategoria, array_map(fn($c)=>(int)$c['id_categoria'], $categorias), true)) $error = 'La categoría seleccionada no está disponible.';
    else {
        $imagen = null;
        try {
            $imagen = productosGuardarImagen($_FILES['imagen'] ?? []);
            productosEjecutar($conexion, 'CALL sp_producto_insertar(?,?,?,?,?,?)', 'issdis', [$idCategoria,$datos['nombre'],$datos['descripcion'],$precio,$stock,$imagen]);
            header('Location: index.php?msg=creado'); exit;
        } catch (Throwable $e) { productosBorrarImagen($imagen); $error = $e->getMessage(); }
    }
}
productosEncabezado('Registrar producto', 'productos');
?>
<div class="row justify-content-center"><div class="col-xl-9"><div class="card"><div class="card-header"><h2 class="card-title mb-0">Información del producto</h2></div><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><?= productosEsc($error) ?></div><?php endif; ?>
<?php if (!$categorias): ?><div class="alert alert-warning">Primero registra una categoría para poder crear productos.</div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-8"><label class="form-label" for="nombre">Nombre <span class="text-danger">*</span></label><input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= productosEsc($datos['nombre']) ?>"></div>
<div class="col-md-4"><label class="form-label" for="id_categoria">Categoría <span class="text-danger">*</span></label><select class="form-select" id="id_categoria" name="id_categoria" required><option value="">Selecciona…</option><?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id_categoria'] ?>" <?= (string)$c['id_categoria']===$datos['id_categoria']?'selected':'' ?>><?= productosEsc($c['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= productosEsc($datos['descripcion']) ?></textarea></div>
<div class="col-md-4"><label class="form-label" for="precio">Precio (Q) <span class="text-danger">*</span></label><input class="form-control" type="number" id="precio" name="precio" min="0" step="0.01" required value="<?= productosEsc($datos['precio']) ?>"></div>
<div class="col-md-4"><label class="form-label" for="stock">Existencias <span class="text-danger">*</span></label><input class="form-control" type="number" id="stock" name="stock" min="0" step="1" required value="<?= productosEsc($datos['stock']) ?>"></div>
<div class="col-md-4"><label class="form-label" for="imagen">Imagen</label><input class="form-control" type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"><div class="form-text">JPG, PNG, WEBP o GIF; máximo 5 MB.</div></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a href="index.php" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary" <?= !$categorias?'disabled':'' ?>><i class="bi bi-check-lg me-1"></i>Guardar producto</button></div></form>
</div></div></div></div>
<?php productosPie(); ?>

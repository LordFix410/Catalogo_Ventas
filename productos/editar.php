<?php
require_once __DIR__ . '/funciones.php';
$conexion = Database::getConnection();
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { header('Location: index.php'); exit; }
$filas = productosEjecutar($conexion, 'CALL sp_producto_obtener(?)', 'i', [$id]);
if (!$filas) { header('Location: index.php?error=no_encontrado'); exit; }
$producto = $filas[0]; $categorias = productosCategorias($conexion); $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim((string)($_POST['nombre'] ?? '')); $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT); $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT); $cat = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
    if ($nombre === '' || mb_strlen($nombre)>120 || $precio===false || $precio<0 || $stock===false || $stock<0 || !$cat || mb_strlen($descripcion)>65535) $error='Revisa los campos: nombre obligatorio, precio y existencias no negativos y categoría válida.';
    elseif (!in_array($cat,array_map(fn($c)=>(int)$c['id_categoria'],$categorias),true)) $error='La categoría seleccionada no está disponible.';
    else {
        $imagenNueva = null;
        try {
            $imagenNueva = productosGuardarImagen($_FILES['imagen'] ?? [], $producto['imagen']);
            productosEjecutar($conexion,'CALL sp_producto_actualizar(?,?,?,?,?,?,?)','iissdis',[$id,$cat,$nombre,$descripcion,$precio,$stock,$imagenNueva]);
            if ($imagenNueva !== $producto['imagen']) productosBorrarImagen($producto['imagen']);
            header('Location: index.php?msg=actualizado'); exit;
        } catch (Throwable $e) { if ($imagenNueva !== $producto['imagen']) productosBorrarImagen($imagenNueva); $error=$e->getMessage(); }
    }
    $producto = array_merge($producto,['nombre'=>$nombre,'descripcion'=>$descripcion,'precio'=>$_POST['precio']??'','stock'=>$_POST['stock']??'','id_categoria'=>$cat?:'']);
}
productosEncabezado('Editar producto','productos');
?>
<div class="row justify-content-center"><div class="col-xl-9"><div class="card"><div class="card-header"><h2 class="card-title mb-0">Editar información</h2></div><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><?= productosEsc($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= (int)$id ?>"><div class="row g-3">
<div class="col-md-8"><label class="form-label" for="nombre">Nombre <span class="text-danger">*</span></label><input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= productosEsc((string)$producto['nombre']) ?>"></div>
<div class="col-md-4"><label class="form-label" for="id_categoria">Categoría <span class="text-danger">*</span></label><select class="form-select" id="id_categoria" name="id_categoria" required><option value="">Selecciona…</option><?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id_categoria'] ?>" <?= (string)$c['id_categoria']===(string)$producto['id_categoria']?'selected':'' ?>><?= productosEsc($c['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= productosEsc((string)($producto['descripcion']??'')) ?></textarea></div>
<div class="col-md-4"><label class="form-label" for="precio">Precio (Q) <span class="text-danger">*</span></label><input class="form-control" type="number" id="precio" name="precio" min="0" step="0.01" required value="<?= productosEsc((string)$producto['precio']) ?>"></div>
<div class="col-md-4"><label class="form-label" for="stock">Existencias <span class="text-danger">*</span></label><input class="form-control" type="number" id="stock" name="stock" min="0" step="1" required value="<?= (int)$producto['stock'] ?>"></div>
<div class="col-md-4"><label class="form-label" for="imagen">Cambiar imagen</label><input class="form-control" type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"><div class="form-text">Deja vacío para conservar la actual. Máximo 5 MB.</div></div>
<?php if (!empty($producto['imagen'])): ?><div class="col-12"><span class="form-label d-block">Imagen actual</span><img src="/<?= productosEsc($producto['imagen']) ?>" alt="<?= productosEsc($producto['nombre']) ?>" style="max-width:220px;max-height:180px;object-fit:contain;border-radius:12px"></div><?php endif; ?>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a href="index.php" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button></div></form>
</div></div></div></div>
<?php productosPie(); ?>

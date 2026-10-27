<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador']);
require_once __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion = Database::getConnection();
if (empty($_SESSION['token_entrada_inventario'])) {
    $_SESSION['token_entrada_inventario'] = bin2hex(random_bytes(32));
}

$error = '';
$lineasIniciales = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $referencia = trim((string) ($_POST['referencia_factura'] ?? ''));
    $fecha = trim((string) ($_POST['fecha_compra'] ?? ''));
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $ids = $_POST['producto'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $costos = $_POST['costo'] ?? [];
    $lineas = [];

    if (is_array($ids) && is_array($cantidades) && is_array($costos)) {
        foreach ($ids as $i => $idProducto) {
            $lineas[] = [
                'producto' => filter_var($idProducto, FILTER_VALIDATE_INT),
                'cantidad' => filter_var($cantidades[$i] ?? null, FILTER_VALIDATE_INT),
                'costo' => trim((string) ($costos[$i] ?? '')),
            ];
        }
    }
    $lineasIniciales = array_map(static fn($linea) => [
        'producto' => (string) ($linea['producto'] ?: ''),
        'cantidad' => (string) ($linea['cantidad'] ?: '1'),
        'costo' => $linea['costo'],
    ], $lineas);

    if (!is_string($token) || !hash_equals($_SESSION['token_entrada_inventario'], $token)) {
        $error = 'La sesión expiró. Recarga la página e inténtalo de nuevo.';
    } elseif (mb_strlen($referencia) > 80 || mb_strlen($observaciones) > 500) {
        $error = 'La referencia permite 80 caracteres y las observaciones hasta 500.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4))) {
        $error = 'Selecciona una fecha de factura válida.';
    } elseif (!$lineas) {
        $error = 'Agrega por lo menos un producto de la factura.';
    } else {
        foreach ($lineas as $linea) {
            if (!$linea['producto'] || $linea['producto'] < 1 || !$linea['cantidad'] || $linea['cantidad'] < 1 || $linea['cantidad'] > 2147483647 || $linea['costo'] === '' || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $linea['costo']) || (float) $linea['costo'] > 99999999.99) {
                $error = 'Revisa los productos, cantidades y costos de la factura.';
                break;
            }
        }
    }

    if ($error === '') {
        $activa = false;
        try {
            mysqli_begin_transaction($conexion);
            $activa = true;
            $productosBloqueados = [];
            $total = 0.0;

            foreach ($lineas as $linea) {
                $idProducto = (int) $linea['producto'];
                if (isset($productosBloqueados[$idProducto])) continue;
                $stmtProducto = mysqli_prepare($conexion, 'SELECT id_producto FROM productos WHERE id_producto = ? FOR UPDATE');
                mysqli_stmt_bind_param($stmtProducto, 'i', $idProducto);
                mysqli_stmt_execute($stmtProducto);
                $resultadoProducto = mysqli_stmt_get_result($stmtProducto);
                $existe = mysqli_fetch_assoc($resultadoProducto);
                mysqli_free_result($resultadoProducto);
                mysqli_stmt_close($stmtProducto);
                if (!$existe) throw new RuntimeException('Uno de los productos ya no existe. Recarga el formulario.');
                $productosBloqueados[$idProducto] = true;
                $subtotalLinea = round((int) $linea['cantidad'] * round((float) $linea['costo'], 2), 2);
                $total = round($total + $subtotalLinea, 2);
                if ($subtotalLinea > 9999999999.99 || $total > 9999999999.99) throw new RuntimeException('El total de la factura supera el límite permitido.');
            }

            $referenciaGuardar = $referencia !== '' ? $referencia : null;
            $observacionesGuardar = $observaciones !== '' ? $observaciones : null;
            $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
            $stmtEntrada = mysqli_prepare($conexion, 'INSERT INTO entradas_inventario (id_usuario, referencia_factura, fecha_compra, total, observaciones) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmtEntrada, 'issds', $idUsuario, $referenciaGuardar, $fecha, $total, $observacionesGuardar);
            mysqli_stmt_execute($stmtEntrada);
            $idEntrada = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmtEntrada);

            $stmtDetalle = mysqli_prepare($conexion, 'INSERT INTO detalle_entrada_inventario (id_entrada, id_producto, cantidad, costo_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
            $stmtStock = mysqli_prepare($conexion, 'UPDATE productos SET stock = stock + ? WHERE id_producto = ?');
            foreach ($lineas as $linea) {
                $idProducto = (int) $linea['producto'];
                $cantidad = (int) $linea['cantidad'];
                $costo = round((float) $linea['costo'], 2);
                $subtotal = round($cantidad * $costo, 2);
                mysqli_stmt_bind_param($stmtDetalle, 'iiidd', $idEntrada, $idProducto, $cantidad, $costo, $subtotal);
                mysqli_stmt_execute($stmtDetalle);
                mysqli_stmt_bind_param($stmtStock, 'ii', $cantidad, $idProducto);
                mysqli_stmt_execute($stmtStock);
            }
            mysqli_stmt_close($stmtDetalle);
            mysqli_stmt_close($stmtStock);

            mysqli_commit($conexion);
            $activa = false;
            $_SESSION['token_entrada_inventario'] = bin2hex(random_bytes(32));
            header('Location: /inventario/entradas.php?msg=guardada');
            exit;
        } catch (Throwable $e) {
            if ($activa) mysqli_rollback($conexion);
            error_log('Error al registrar entrada de inventario: ' . $e->getMessage());
            $error = 'No se pudo registrar la factura ni actualizar las existencias. Revisa los datos e inténtalo de nuevo.';
        }
    }
}

$resultadoProductos = mysqli_query($conexion, 'SELECT id_producto, nombre, stock, activo FROM productos ORDER BY activo DESC, nombre');
$productos = mysqli_fetch_all($resultadoProductos, MYSQLI_ASSOC);
mysqli_free_result($resultadoProductos);
$entradas = mysqli_query($conexion, 'SELECT e.id_entrada, e.referencia_factura, e.fecha_compra, e.total, u.nombre AS usuario, COUNT(d.id_detalle) AS lineas FROM entradas_inventario e INNER JOIN usuarios u ON u.id_usuario = e.id_usuario LEFT JOIN detalle_entrada_inventario d ON d.id_entrada = e.id_entrada GROUP BY e.id_entrada ORDER BY e.creado_en DESC LIMIT 10');
$historialEntradas = mysqli_fetch_all($entradas, MYSQLI_ASSOC);
mysqli_free_result($entradas);
$titulo = 'Cargar factura';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="app-main">
    <div class="app-content-header"><div class="container-fluid"><span class="page-eyebrow">INVENTARIO</span><h1 class="page-title">Carga de productos</h1><p class="page-description">Registra la factura de compra y suma las unidades recibidas al inventario.</p></div></div>
    <div class="app-content"><div class="container-fluid">
        <?php if (($_GET['msg'] ?? '') === 'guardada'): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Factura registrada y existencias actualizadas.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if (!$productos): ?><div class="alert alert-warning">Primero registra productos para poder cargar una factura.</div><?php else: ?>
        <form method="post" id="formEntradaInventario">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token_entrada_inventario'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="alert alert-warning d-none" id="alertaEntradaLineas">Agrega por lo menos un producto antes de revisar la factura.</div>
            <section class="dashboard-card mb-4">
                <h2 class="section-title"><i class="bi bi-receipt"></i> Datos de la factura</h2>
                <div class="row g-3">
                    <div class="col-12 col-md-4"><label class="form-label fw-bold" for="referencia_factura">Número de factura</label><input class="form-control" id="referencia_factura" name="referencia_factura" maxlength="80" value="<?= htmlspecialchars((string) ($_POST['referencia_factura'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. FAC-00125"></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-bold" for="fecha_compra">Fecha de compra</label><input class="form-control" type="date" id="fecha_compra" name="fecha_compra" required value="<?= htmlspecialchars((string) ($_POST['fecha_compra'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="col-12"><label class="form-label fw-bold" for="observaciones">Observaciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="2" maxlength="500" placeholder="Proveedor, forma de pago u otra nota"><?= htmlspecialchars((string) ($_POST['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
                </div>
            </section>
            <section class="dashboard-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="section-title mb-0"><i class="bi bi-box-seam"></i> Productos recibidos</h2><button type="button" id="agregarLineaEntrada" class="btn btn-chiquis"><i class="bi bi-plus-lg me-1"></i>Agregar producto</button></div>
                <p class="small text-muted">El costo unitario se guarda como referencia de la factura. El precio de venta del catálogo no cambia.</p>
                <div class="table-responsive"><table class="table align-middle" id="tablaEntrada"><thead><tr><th>Producto</th><th style="min-width:110px">Cantidad</th><th style="min-width:160px">Costo unitario</th><th style="min-width:140px">Subtotal</th><th></th></tr></thead><tbody></tbody></table></div>
                <div class="text-end fs-5 fw-bold">Total factura: <span id="totalEntrada" class="text-primary">Q 0.00</span></div>
            </section>
            <div class="d-flex flex-wrap justify-content-end gap-2"><a href="/productos/index.php" class="btn btn-outline-secondary">Cancelar</a><button type="button" class="btn btn-chiquis" id="abrirConfirmacionEntrada" data-bs-toggle="modal" data-bs-target="#modalConfirmarEntrada"><i class="bi bi-check2-circle me-1"></i>Revisar factura</button></div>
        </form>
        <?php endif; ?>

        <section class="dashboard-card mt-4"><h2 class="section-title"><i class="bi bi-clock-history"></i> Últimas facturas cargadas</h2><?php if ($historialEntradas): ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Factura</th><th>Fecha</th><th>Productos</th><th>Registró</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach ($historialEntradas as $entrada): ?><tr><td><?= htmlspecialchars($entrada['referencia_factura'] ?: 'Sin número', ENT_QUOTES, 'UTF-8') ?></td><td><?= date('d/m/Y', strtotime($entrada['fecha_compra'])) ?></td><td><?= (int) $entrada['lineas'] ?></td><td><?= htmlspecialchars($entrada['usuario'], ENT_QUOTES, 'UTF-8') ?></td><td class="text-end fw-bold">Q <?= number_format((float) $entrada['total'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="text-muted mb-0">Todavía no hay facturas registradas.</p><?php endif; ?></section>
    </div></div>
</main>
<template id="plantillaEntrada"><tr class="linea-entrada"><td><input type="search" class="form-control form-control-sm mb-2 buscar-producto-entrada" placeholder="Buscar producto..." autocomplete="off"><select class="form-select producto-entrada" name="producto[]" required><option value="">Selecciona producto...</option><?php foreach ($productos as $producto): ?><option value="<?= (int) $producto['id_producto'] ?>"><?= htmlspecialchars($producto['nombre'] . (!(bool) $producto['activo'] ? ' (inactivo)' : ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></td><td><input type="number" class="form-control cantidad-entrada" name="cantidad[]" min="1" step="1" value="1" required></td><td><div class="input-group"><span class="input-group-text">Q</span><input type="number" class="form-control costo-entrada" name="costo[]" min="0" max="99999999.99" step="0.01" placeholder="0.00" required></div></td><td class="subtotal-entrada text-nowrap fw-bold">Q 0.00</td><td><button type="button" class="btn btn-outline-danger quitar-entrada" aria-label="Quitar producto"><i class="bi bi-trash"></i></button></td></tr></template>
<div class="modal fade" id="modalConfirmarEntrada" tabindex="-1" aria-labelledby="tituloConfirmarEntrada" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header"><h2 class="modal-title fs-5 fw-bold" id="tituloConfirmarEntrada"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Confirmar carga de factura</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><p>Se sumarán estas cantidades a las existencias:</p><ul id="resumenEntrada" class="list-unstyled mb-3"></ul><div class="d-flex justify-content-between border-top pt-3 fw-bold"><span>Total de factura</span><span id="confirmarTotalEntrada" class="text-primary">Q 0.00</span></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Seguir editando</button><button type="submit" form="formEntradaInventario" class="btn btn-chiquis"><i class="bi bi-check-lg me-1"></i>Confirmar y cargar</button></div></div></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.querySelector('#tablaEntrada tbody');
    const template = document.getElementById('plantillaEntrada');
    if (!body || !template) return;
    const money = value => 'Q ' + value.toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const recalculate = () => {
        let total = 0;
        body.querySelectorAll('.linea-entrada').forEach(row => {
            const quantity = Number(row.querySelector('.cantidad-entrada').value) || 0;
            const cost = Number(row.querySelector('.costo-entrada').value) || 0;
            const subtotal = quantity * cost;
            total += subtotal;
            row.querySelector('.subtotal-entrada').textContent = money(subtotal);
        });
        document.getElementById('totalEntrada').textContent = money(total);
    };
    const addLine = () => {
        const row = template.content.firstElementChild.cloneNode(true);
        const select = row.querySelector('.producto-entrada');
        const search = row.querySelector('.buscar-producto-entrada');
        search.addEventListener('input', () => {
            const term = search.value.trim().toLocaleLowerCase('es');
            Array.from(select.options).forEach((option, index) => { option.hidden = index > 0 && !option.textContent.toLocaleLowerCase('es').includes(term); });
            if (select.selectedOptions[0]?.hidden) select.value = '';
        });
        row.querySelector('.cantidad-entrada').addEventListener('input', recalculate);
        row.querySelector('.costo-entrada').addEventListener('input', recalculate);
        row.querySelector('.quitar-entrada').addEventListener('click', () => { row.remove(); recalculate(); });
        body.appendChild(row);
    };
    document.getElementById('agregarLineaEntrada').addEventListener('click', addLine);
    document.getElementById('abrirConfirmacionEntrada').addEventListener('click', event => {
        const form = document.getElementById('formEntradaInventario');
        if (!form.reportValidity()) { event.preventDefault(); return; }
        const summary = document.getElementById('resumenEntrada');
        summary.replaceChildren();
        let count = 0;
        body.querySelectorAll('.linea-entrada').forEach(row => {
            const select = row.querySelector('.producto-entrada');
            if (!select.value) return;
            count++;
            const item = document.createElement('li');
            item.className = 'd-flex justify-content-between gap-3 py-2 border-bottom';
            const name = document.createElement('span');
            name.textContent = `${select.selectedOptions[0].textContent} × ${row.querySelector('.cantidad-entrada').value}`;
            const price = document.createElement('strong');
            price.textContent = money((Number(row.querySelector('.cantidad-entrada').value) || 0) * (Number(row.querySelector('.costo-entrada').value) || 0));
            item.append(name, price);
            summary.appendChild(item);
        });
        if (!count) { event.preventDefault(); document.getElementById('alertaEntradaLineas').classList.remove('d-none'); return; }
        document.getElementById('alertaEntradaLineas').classList.add('d-none');
        document.getElementById('confirmarTotalEntrada').textContent = document.getElementById('totalEntrada').textContent;
    });
    const initialLines = <?= json_encode($lineasIniciales, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (initialLines.length) {
        initialLines.forEach(line => {
            addLine();
            const row = body.lastElementChild;
            row.querySelector('.producto-entrada').value = line.producto;
            row.querySelector('.cantidad-entrada').value = line.cantidad;
            row.querySelector('.costo-entrada').value = line.costo;
        });
        recalculate();
    } else addLine();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

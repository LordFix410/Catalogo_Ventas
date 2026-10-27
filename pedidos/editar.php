<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Cliente', 'Administrador', 'Empleado']);
require_once __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion = Database::getConnection();
$esCliente = ($_SESSION['rol'] ?? '') === 'Cliente';
$idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
$idPedido = filter_var($_GET['id'] ?? $_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$volver = $esCliente ? '/portal-cliente/seguimiento.php' : '/pedidos/index.php';

if (!$idPedido || $idPedido < 1) {
    header('Location: ' . $volver);
    exit;
}

if (empty($_SESSION['token_editar_pedido'])) {
    $_SESSION['token_editar_pedido'] = bin2hex(random_bytes(32));
}

$filtroCliente = $esCliente ? ' AND c.id_usuario = ? AND c.activo = 1' : '';
$sqlPedido = "SELECT p.id_pedido, p.total, p.observaciones,
                     COALESCE(NULLIF(p.direccion_entrega, ''), c.direccion) AS direccion_entrega,
                     CONCAT(c.nombre, ' ', c.apellido) AS cliente,
                     COALESCE(e.nombre, 'Recibido') AS estado,
                     COALESCE(e.orden, 1) AS orden
              FROM pedidos p
              INNER JOIN clientes c ON c.id_cliente = p.id_cliente
              LEFT JOIN seguimiento_pedido s ON s.id_seguimiento = (
                  SELECT s2.id_seguimiento FROM seguimiento_pedido s2
                  WHERE s2.id_pedido = p.id_pedido
                  ORDER BY s2.fecha_hora DESC, s2.id_seguimiento DESC LIMIT 1
              )
              LEFT JOIN estados_pedido e ON e.id_estado = s.id_estado
              WHERE p.id_pedido = ?{$filtroCliente} LIMIT 1";
$stmtPedido = mysqli_prepare($conexion, $sqlPedido);
if ($esCliente) mysqli_stmt_bind_param($stmtPedido, 'ii', $idPedido, $idUsuario);
else mysqli_stmt_bind_param($stmtPedido, 'i', $idPedido);
mysqli_stmt_execute($stmtPedido);
$resultadoPedido = mysqli_stmt_get_result($stmtPedido);
$pedido = mysqli_fetch_assoc($resultadoPedido) ?: null;
mysqli_free_result($resultadoPedido);
mysqli_stmt_close($stmtPedido);

if (!$pedido) {
    header('Location: ' . $volver);
    exit;
}

$stmtItems = mysqli_prepare(
    $conexion,
    'SELECT d.id_producto, pr.nombre, d.cantidad, d.precio_unitario, d.subtotal
     FROM detalle_pedido d INNER JOIN productos pr ON pr.id_producto = d.id_producto
     WHERE d.id_pedido = ? ORDER BY d.id_detalle'
);
mysqli_stmt_bind_param($stmtItems, 'i', $idPedido);
mysqli_stmt_execute($stmtItems);
$resultadoItems = mysqli_stmt_get_result($stmtItems);
$itemsActuales = mysqli_fetch_all($resultadoItems, MYSQLI_ASSOC);
mysqli_free_result($resultadoItems);
mysqli_stmt_close($stmtItems);

$resultadoProductos = mysqli_query($conexion, 'SELECT id_producto, nombre, precio, stock FROM productos WHERE activo = 1 ORDER BY nombre');
$productos = mysqli_fetch_all($resultadoProductos, MYSQLI_ASSOC);
mysqli_free_result($resultadoProductos);

$error = '';
$mensaje = '';
$direccion = (string) ($pedido['direccion_entrega'] ?? '');
$observaciones = (string) ($pedido['observaciones'] ?? '');
$lineasFormulario = array_map(fn($item) => ['id_producto' => (string) $item['id_producto'], 'cantidad' => (string) $item['cantidad']], $itemsActuales);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $direccion = trim((string) ($_POST['direccion_entrega'] ?? ''));
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $idsEnviados = $_POST['producto'] ?? [];
    $cantidadesEnviadas = $_POST['cantidad'] ?? [];
    $lineasFormulario = [];

    if (is_array($idsEnviados) && is_array($cantidadesEnviadas)) {
        foreach ($idsEnviados as $indice => $idEnviado) {
            $lineasFormulario[] = [
                'id_producto' => trim((string) $idEnviado),
                'cantidad' => trim((string) ($cantidadesEnviadas[$indice] ?? '')),
            ];
        }
    }

    if (!is_string($token) || !hash_equals($_SESSION['token_editar_pedido'], $token)) {
        $error = 'La sesión de edición expiró. Recarga la página e inténtalo de nuevo.';
    } elseif ((int) $pedido['orden'] !== 1 || mb_strtolower((string) $pedido['estado']) !== 'recibido') {
        $error = 'Solo puedes editar un pedido mientras su estado sea Recibido.';
    } elseif ($direccion === '' || mb_strlen($direccion) > 255 || mb_strlen($observaciones) > 500) {
        $error = 'Ingresa una dirección de entrega válida y observaciones de hasta 500 caracteres.';
    } else {
        $nuevasCantidades = [];
        foreach ($lineasFormulario as $linea) {
            if ($linea['id_producto'] === '' && $linea['cantidad'] === '') continue;
            $idProductoLinea = filter_var($linea['id_producto'], FILTER_VALIDATE_INT);
            $cantidadLinea = filter_var($linea['cantidad'], FILTER_VALIDATE_INT);
            if (!$idProductoLinea || $idProductoLinea < 1 || !$cantidadLinea || $cantidadLinea < 1) {
                $error = 'Selecciona un producto y una cantidad válida para cada línea.';
                break;
            }
            $nuevasCantidades[$idProductoLinea] = ($nuevasCantidades[$idProductoLinea] ?? 0) + $cantidadLinea;
        }
        if (!$error && !$nuevasCantidades) $error = 'El pedido debe contener al menos un producto.';

        if (!$error) {
            $transaccionActiva = false;
            try {
                mysqli_begin_transaction($conexion);
                $transaccionActiva = true;

                $sqlBloqueo = 'SELECT p.id_pedido FROM pedidos p INNER JOIN clientes c ON c.id_cliente = p.id_cliente WHERE p.id_pedido = ?' . ($esCliente ? ' AND c.id_usuario = ? AND c.activo = 1' : '') . ' FOR UPDATE';
                $stmtBloqueo = mysqli_prepare($conexion, $sqlBloqueo);
                if ($esCliente) mysqli_stmt_bind_param($stmtBloqueo, 'ii', $idPedido, $idUsuario);
                else mysqli_stmt_bind_param($stmtBloqueo, 'i', $idPedido);
                mysqli_stmt_execute($stmtBloqueo);
                $resultadoBloqueo = mysqli_stmt_get_result($stmtBloqueo);
                $bloqueado = mysqli_fetch_assoc($resultadoBloqueo);
                mysqli_free_result($resultadoBloqueo);
                mysqli_stmt_close($stmtBloqueo);
                if (!$bloqueado) throw new RuntimeException('No se encontró el pedido.');

                $stmtEstado = mysqli_prepare(
                    $conexion,
                    "SELECT e.id_estado, e.orden, e.nombre FROM seguimiento_pedido s
                     INNER JOIN estados_pedido e ON e.id_estado = s.id_estado
                     WHERE s.id_pedido = ? ORDER BY s.fecha_hora DESC, s.id_seguimiento DESC LIMIT 1 FOR UPDATE"
                );
                mysqli_stmt_bind_param($stmtEstado, 'i', $idPedido);
                mysqli_stmt_execute($stmtEstado);
                $resultadoEstado = mysqli_stmt_get_result($stmtEstado);
                $estado = mysqli_fetch_assoc($resultadoEstado) ?: ['id_estado' => 1, 'orden' => 1, 'nombre' => 'Recibido'];
                mysqli_free_result($resultadoEstado);
                mysqli_stmt_close($stmtEstado);
                if ((int) $estado['orden'] !== 1 || mb_strtolower((string) $estado['nombre']) !== 'recibido') {
                    throw new RuntimeException('El pedido dejó de estar en estado Recibido y ya no se puede editar.');
                }

                $stmtAntiguos = mysqli_prepare($conexion, 'SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = ? FOR UPDATE');
                mysqli_stmt_bind_param($stmtAntiguos, 'i', $idPedido);
                mysqli_stmt_execute($stmtAntiguos);
                $resultadoAntiguos = mysqli_stmt_get_result($stmtAntiguos);
                $antiguasLineas = mysqli_fetch_all($resultadoAntiguos, MYSQLI_ASSOC);
                mysqli_free_result($resultadoAntiguos);
                mysqli_stmt_close($stmtAntiguos);
                $cantidadesAntiguas = [];
                foreach ($antiguasLineas as $lineaAntigua) {
                    $idProductoAntiguo = (int) $lineaAntigua['id_producto'];
                    $cantidadesAntiguas[$idProductoAntiguo] = ($cantidadesAntiguas[$idProductoAntiguo] ?? 0) + (int) $lineaAntigua['cantidad'];
                }

                $catalogoConfirmado = [];
                $idsAjustar = array_unique(array_merge(array_keys($cantidadesAntiguas), array_keys($nuevasCantidades)));
                foreach ($idsAjustar as $idProductoAjustar) {
                    $idProductoAjustar = (int) $idProductoAjustar;
                    $stmtProducto = mysqli_prepare($conexion, 'SELECT nombre, precio, stock, activo FROM productos WHERE id_producto = ? FOR UPDATE');
                    mysqli_stmt_bind_param($stmtProducto, 'i', $idProductoAjustar);
                    mysqli_stmt_execute($stmtProducto);
                    $resultadoProducto = mysqli_stmt_get_result($stmtProducto);
                    $producto = mysqli_fetch_assoc($resultadoProducto) ?: null;
                    if ($resultadoProducto) mysqli_free_result($resultadoProducto);
                    mysqli_stmt_close($stmtProducto);

                    $cantidadNueva = (int) ($nuevasCantidades[$idProductoAjustar] ?? 0);
                    $cantidadAntigua = (int) ($cantidadesAntiguas[$idProductoAjustar] ?? 0);
                    $cambio = $cantidadNueva - $cantidadAntigua;
                    if (!$producto) throw new RuntimeException('Uno de los productos ya no existe.');
                    if ($cantidadNueva > 0 && !(bool) $producto['activo']) throw new RuntimeException($producto['nombre'] . ' ya no está disponible.');

                    if ($cambio > 0) {
                        $stmtStock = mysqli_prepare($conexion, 'UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND activo = 1 AND stock >= ?');
                        mysqli_stmt_bind_param($stmtStock, 'iii', $cambio, $idProductoAjustar, $cambio);
                        mysqli_stmt_execute($stmtStock);
                        $actualizadas = mysqli_stmt_affected_rows($stmtStock);
                        mysqli_stmt_close($stmtStock);
                        if ($actualizadas !== 1) throw new RuntimeException('No hay existencias suficientes de ' . $producto['nombre'] . '.');
                    } elseif ($cambio < 0) {
                        $devolver = abs($cambio);
                        $stmtStock = mysqli_prepare($conexion, 'UPDATE productos SET stock = stock + ? WHERE id_producto = ?');
                        mysqli_stmt_bind_param($stmtStock, 'ii', $devolver, $idProductoAjustar);
                        mysqli_stmt_execute($stmtStock);
                        mysqli_stmt_close($stmtStock);
                    }

                    if ($cantidadNueva > 0) {
                        $catalogoConfirmado[$idProductoAjustar] = [
                            'nombre' => $producto['nombre'],
                            'cantidad' => $cantidadNueva,
                            'precio' => (float) $producto['precio'],
                        ];
                    }
                }

                $stmtEliminar = mysqli_prepare($conexion, 'DELETE FROM detalle_pedido WHERE id_pedido = ?');
                mysqli_stmt_bind_param($stmtEliminar, 'i', $idPedido);
                mysqli_stmt_execute($stmtEliminar);
                mysqli_stmt_close($stmtEliminar);

                $totalNuevo = 0.0;
                $stmtInsertar = mysqli_prepare($conexion, 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
                foreach ($catalogoConfirmado as $idProductoNuevo => $lineaNueva) {
                    $cantidadNueva = (int) $lineaNueva['cantidad'];
                    $precioNuevo = (float) $lineaNueva['precio'];
                    $subtotalNuevo = $cantidadNueva * $precioNuevo;
                    $totalNuevo += $subtotalNuevo;
                    mysqli_stmt_bind_param($stmtInsertar, 'iiidd', $idPedido, $idProductoNuevo, $cantidadNueva, $precioNuevo, $subtotalNuevo);
                    mysqli_stmt_execute($stmtInsertar);
                }
                mysqli_stmt_close($stmtInsertar);

                $direccionGuardar = $direccion !== '' ? $direccion : null;
                $observacionesGuardar = $observaciones !== '' ? $observaciones : null;
                $stmtActualizar = mysqli_prepare($conexion, 'UPDATE pedidos SET total = ?, direccion_entrega = ?, observaciones = ? WHERE id_pedido = ?');
                mysqli_stmt_bind_param($stmtActualizar, 'dssi', $totalNuevo, $direccionGuardar, $observacionesGuardar, $idPedido);
                mysqli_stmt_execute($stmtActualizar);
                mysqli_stmt_close($stmtActualizar);

                $idEstado = (int) $estado['id_estado'];
                $nota = 'Pedido editado antes de su preparación.';
                $stmtHistorial = mysqli_prepare($conexion, 'INSERT INTO seguimiento_pedido (id_pedido, id_estado, id_usuario, observacion) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmtHistorial, 'iiis', $idPedido, $idEstado, $idUsuario, $nota);
                mysqli_stmt_execute($stmtHistorial);
                mysqli_stmt_close($stmtHistorial);

                mysqli_commit($conexion);
                $transaccionActiva = false;
                $_SESSION['token_editar_pedido'] = bin2hex(random_bytes(32));
                if ($esCliente) header('Location: /seguimiento/detalle.php?id=' . $idPedido . '&editado=1');
                else header('Location: /pedidos/detalle.php?id=' . $idPedido . '&msg=actualizado');
                exit;
            } catch (Throwable $e) {
                if ($transaccionActiva) mysqli_rollback($conexion);
                error_log('Error al editar pedido: ' . $e->getMessage());
                $error = $e->getMessage();
                $pedido['direccion_entrega'] = $direccion;
                $pedido['observaciones'] = $observaciones;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $lineasFormulario = array_map(fn($item) => ['id_producto' => (string) $item['id_producto'], 'cantidad' => (string) $item['cantidad']], $itemsActuales);
}
if (!$lineasFormulario) $lineasFormulario = [['id_producto' => '', 'cantidad' => '1']];

$titulo = 'Editar pedido';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="app-main">
    <div class="app-content-header"><div class="container-fluid">
        <span class="page-eyebrow">GESTIÓN DE PEDIDOS</span>
        <h1 class="page-title">Editar pedido #<?= str_pad((string) $idPedido, 5, '0', STR_PAD_LEFT) ?></h1>
        <p class="page-description">Cliente: <?= htmlspecialchars($pedido['cliente'], ENT_QUOTES, 'UTF-8') ?> · Estado: <?= htmlspecialchars($pedido['estado'], ENT_QUOTES, 'UTF-8') ?></p>
    </div></div>
    <div class="app-content"><div class="container-fluid">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ((int) $pedido['orden'] !== 1 || mb_strtolower((string) $pedido['estado']) !== 'recibido'): ?>
            <div class="alert alert-warning">Solo los pedidos en estado <strong>Recibido</strong> se pueden editar. Puedes <a href="/pedidos/cancelar.php?id=<?= (int) $idPedido ?>">revisar la cancelación</a>; si está preparado o en ruta, se aplicará un cargo del 20%.</div>
            <a href="<?= htmlspecialchars($volver, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Regresar</a>
        <?php else: ?>
            <form method="post" id="formEditarPedido">
                <input type="hidden" name="id_pedido" value="<?= (int) $idPedido ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token_editar_pedido'], ENT_QUOTES, 'UTF-8') ?>">
                <section class="dashboard-card mb-4">
                    <h2 class="section-title"><i class="bi bi-geo-alt"></i> Entrega y observaciones</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><label for="direccion_entrega" class="form-label fw-bold">Dirección de entrega</label><input id="direccion_entrega" name="direccion_entrega" maxlength="255" class="form-control" value="<?= htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8') ?>" required></div>
                        <div class="col-12"><label for="observaciones" class="form-label fw-bold">Observaciones</label><textarea id="observaciones" name="observaciones" maxlength="500" rows="2" class="form-control"><?= htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8') ?></textarea></div>
                    </div>
                </section>
                <section class="dashboard-card mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="section-title mb-0"><i class="bi bi-box-seam"></i> Productos</h2><button type="button" id="agregarLineaEdicion" class="btn btn-chiquis"><i class="bi bi-plus-lg me-1"></i>Agregar producto</button></div>
                    <div class="table-responsive"><table class="table align-middle" id="tablaEdicionPedido"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr></thead><tbody>
                    <?php foreach ($lineasFormulario as $linea): ?>
                        <tr class="linea-edicion-pedido">
                            <td><input type="search" class="form-control form-control-sm mb-2 buscar-producto-edicion" placeholder="Buscar producto..." autocomplete="off"><select class="form-select producto-edicion" name="producto[]" required><option value="">Selecciona producto...</option><?php foreach ($productos as $producto): ?><option value="<?= (int) $producto['id_producto'] ?>" data-precio="<?= (float) $producto['precio'] ?>" data-stock="<?= (int) $producto['stock'] ?>" <?= (string) $linea['id_producto'] === (string) $producto['id_producto'] ? 'selected' : '' ?>><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?> (stock <?= (int) $producto['stock'] ?>)</option><?php endforeach; ?></select></td>
                            <td><input type="number" class="form-control cantidad-edicion" name="cantidad[]" min="1" value="<?= htmlspecialchars((string) $linea['cantidad'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                            <td class="precio-edicion text-nowrap">Q 0.00</td><td class="subtotal-edicion text-nowrap fw-bold">Q 0.00</td><td><button type="button" class="btn btn-outline-danger quitar-linea-edicion" aria-label="Quitar línea"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <div class="text-end fw-bold fs-5 mt-3">Total nuevo: <span id="totalEdicionPedido" class="text-primary">Q 0.00</span></div>
                    <p class="small text-muted mb-0">Al guardar se recalcularán precios y existencias. Solo se puede editar antes de que el pedido quede preparado.</p>
                </section>
                <div class="d-flex flex-wrap justify-content-end gap-2"><a href="<?= htmlspecialchars($volver, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-chiquis"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button></div>
            </form>
        <?php endif; ?>
    </div></div>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('#tablaEdicionPedido tbody');
    if (!tbody) return;
    const fmt = value => 'Q ' + value.toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    function calcRow(row) {
        const select = row.querySelector('.producto-edicion');
        const quantity = row.querySelector('.cantidad-edicion');
        const option = select.selectedOptions[0];
        const price = option && option.value ? Number(option.dataset.precio) : 0;
        const available = option && option.value ? Number(option.dataset.stock) + Number(quantity.dataset.reserved || 0) : 0;
        quantity.max = available || '';
        row.querySelector('.precio-edicion').textContent = fmt(price);
        row.querySelector('.subtotal-edicion').textContent = fmt(price * (Number(quantity.value) || 0));
        calcTotal();
    }
    function calcTotal() {
        let sum = 0;
        tbody.querySelectorAll('.linea-edicion-pedido').forEach(row => {
            const op = row.querySelector('.producto-edicion').selectedOptions[0];
            sum += (Number(op?.dataset.precio) || 0) * (Number(row.querySelector('.cantidad-edicion').value) || 0);
        });
        document.getElementById('totalEdicionPedido').textContent = fmt(sum);
    }
    function bind(row) {
        const select = row.querySelector('.producto-edicion');
        const quantity = row.querySelector('.cantidad-edicion');
        const search = row.querySelector('.buscar-producto-edicion');
        const selected = select.selectedOptions[0];
        quantity.dataset.reserved = selected?.value ? quantity.value : '0';
        search.addEventListener('input', () => {
            const q = search.value.trim().toLocaleLowerCase('es');
            Array.from(select.options).forEach((option, index) => { option.hidden = index > 0 && !option.textContent.toLocaleLowerCase('es').includes(q); });
            if (select.selectedOptions[0]?.hidden) select.value = '';
            calcRow(row);
        });
        select.addEventListener('change', () => { quantity.dataset.reserved = '0'; calcRow(row); });
        quantity.addEventListener('input', () => calcRow(row));
        row.querySelector('.quitar-linea-edicion').addEventListener('click', () => { row.remove(); calcTotal(); });
        calcRow(row);
    }
    tbody.querySelectorAll('.linea-edicion-pedido').forEach(bind);
    document.getElementById('agregarLineaEdicion').addEventListener('click', () => {
        const row = tbody.querySelector('.linea-edicion-pedido').cloneNode(true);
        row.querySelector('.buscar-producto-edicion').value = '';
        row.querySelector('.producto-edicion').value = '';
        row.querySelector('.cantidad-edicion').value = '1';
        row.querySelector('.cantidad-edicion').dataset.reserved = '0';
        row.querySelectorAll('.producto-edicion option').forEach(option => option.hidden = false);
        bind(row);
        tbody.appendChild(row);
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

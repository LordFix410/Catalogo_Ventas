<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Cliente', 'Administrador', 'Empleado']);
require_once __DIR__ . '/../config/database.php';

$esCliente = ($_SESSION['rol'] ?? '') === 'Cliente';
$rutaRegreso = $esCliente ? '/portal-cliente/seguimiento.php' : '/seguimiento/index.php';
$idPedido = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$idPedido || $idPedido < 1) {
    header('Location: ' . $rutaRegreso);
    exit;
}

$conexion = Database::getConnection();

if ($esCliente) {
    $stmtAcceso = mysqli_prepare(
        $conexion,
        'SELECT 1 FROM pedidos p INNER JOIN clientes c ON c.id_cliente = p.id_cliente WHERE p.id_pedido = ? AND c.id_usuario = ? AND c.activo = 1 LIMIT 1'
    );
    $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
    mysqli_stmt_bind_param($stmtAcceso, 'ii', $idPedido, $idUsuario);
    mysqli_stmt_execute($stmtAcceso);
    $resultadoAcceso = mysqli_stmt_get_result($stmtAcceso);
    $tieneAcceso = $resultadoAcceso && mysqli_fetch_row($resultadoAcceso);
    if ($resultadoAcceso) mysqli_free_result($resultadoAcceso);
    mysqli_stmt_close($stmtAcceso);

    if (!$tieneAcceso) {
        header('Location: ' . $rutaRegreso);
        exit;
    }
}

$pedidoInfo = null;
$resultadoPedido = mysqli_query($conexion, 'CALL sp_pedido_obtener(' . (int) $idPedido . ')');
if ($resultadoPedido) {
    $pedidoInfo = mysqli_fetch_assoc($resultadoPedido);
    mysqli_free_result($resultadoPedido);
}
Database::limpiarResultados($conexion);

if (!$pedidoInfo) {
    header('Location: ' . $rutaRegreso);
    exit;
}

$historial = [];
$resultadoHistorial = mysqli_query($conexion, 'CALL sp_seguimiento_historial(' . (int) $idPedido . ')');
if ($resultadoHistorial) {
    $historial = mysqli_fetch_all($resultadoHistorial, MYSQLI_ASSOC);
    mysqli_free_result($resultadoHistorial);
}
Database::limpiarResultados($conexion);
$ultimoEvento = $historial ? end($historial) : null;
$estadoPedidoActual = mb_strtolower((string) ($ultimoEvento['estado'] ?? 'recibido'));
$ordenPedidoActual = (int) ($ultimoEvento['orden'] ?? 1);

$productosPedido = [];
$resultadoProductos = mysqli_query($conexion, 'CALL sp_detalle_listar(' . (int) $idPedido . ')');
if ($resultadoProductos) {
    $productosPedido = mysqli_fetch_all($resultadoProductos, MYSQLI_ASSOC);
    mysqli_free_result($resultadoProductos);
}
Database::limpiarResultados($conexion);

$titulo = 'Historial del pedido';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="page-eyebrow">SEGUIMIENTO</span>
                    <h1 class="page-title">Pedido #<?= str_pad((string) $idPedido, 5, '0', STR_PAD_LEFT) ?></h1>
                    <p class="page-description mb-0">Historial de actualizaciones y estado de entrega.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($esCliente && !in_array($estadoPedidoActual, ['cancelado', 'entregado'], true)): ?>
                        <?php if ($ordenPedidoActual === 1 && $estadoPedidoActual === 'recibido'): ?><a href="/pedidos/editar.php?id=<?= (int) $idPedido ?>" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar pedido</a><?php endif; ?>
                        <a href="/pedidos/cancelar.php?id=<?= (int) $idPedido ?>" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancelar pedido</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($rutaRegreso, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary seguimiento-regresar"><i class="bi bi-arrow-left me-1"></i> Regresar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <section class="card seguimiento-resumen mb-4" aria-label="Resumen del pedido">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-6 col-xl-4">
                            <span class="seguimiento-dato-label">Cliente</span>
                            <div class="seguimiento-dato-valor"><?= htmlspecialchars($pedidoInfo['cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">
                            <span class="seguimiento-dato-label">Teléfono</span>
                            <div class="seguimiento-dato-valor"><?= htmlspecialchars($pedidoInfo['telefono'] ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">
                            <span class="seguimiento-dato-label">Dirección de entrega</span>
                            <div class="seguimiento-dato-valor"><?= htmlspecialchars($pedidoInfo['direccion_entrega'] ?? 'No registrada', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">
                            <span class="seguimiento-dato-label">Fecha de creación</span>
                            <div class="seguimiento-dato-valor"><?= date('d/m/Y H:i', strtotime($pedidoInfo['fecha_pedido'])) ?></div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">
                            <span class="seguimiento-dato-label">Total</span>
                            <div class="seguimiento-total">Q <?= number_format((float) $pedidoInfo['total'], 2) ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ((float) ($pedidoInfo['cargo_cancelacion'] ?? 0) > 0): ?>
                <div class="alert alert-warning"><strong>Cargo de cancelación (20%):</strong> Q <?= number_format((float) $pedidoInfo['cargo_cancelacion'], 2) ?></div>
            <?php endif; ?>

            <section class="dashboard-card seguimiento-productos-card mb-4">
                <h2 class="section-title"><i class="bi bi-bag-check"></i> Productos comprados</h2>
                <?php if ($productosPedido): ?>
                    <div class="seguimiento-productos-lista">
                        <?php foreach ($productosPedido as $producto): ?>
                            <article class="seguimiento-producto">
                                <div class="seguimiento-producto-info">
                                    <strong><?= htmlspecialchars($producto['producto'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="text-muted small">
                                        <?= (int) $producto['cantidad'] ?> × Q <?= number_format((float) $producto['precio_unitario'], 2) ?> c/u
                                    </span>
                                </div>
                                <strong class="seguimiento-producto-subtotal">Q <?= number_format((float) $producto['subtotal'], 2) ?></strong>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between border-top mt-3 pt-3 fw-bold">
                        <span>Total del pedido</span>
                        <span class="text-primary">Q <?= number_format((float) $pedidoInfo['total'], 2) ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light mb-0">Este pedido no tiene productos registrados.</div>
                <?php endif; ?>
            </section>

            <section class="dashboard-card seguimiento-historial-card">
                <h2 class="section-title"><i class="bi bi-clock-history"></i> Historial del pedido</h2>
                <?php if ($historial): ?>
                    <div class="seguimiento-eventos">
                        <?php foreach ($historial as $evento): ?>
                            <article class="seguimiento-evento">
                                <div class="seguimiento-evento-marca" aria-hidden="true"><i class="bi bi-truck"></i></div>
                                <div class="card seguimiento-evento-card">
                                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <strong><?= htmlspecialchars($evento['estado'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <time class="small text-muted" datetime="<?= htmlspecialchars(date('c', strtotime($evento['fecha_hora'])), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= date('d/m/Y · H:i', strtotime($evento['fecha_hora'])) ?>
                                        </time>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($evento['observacion'] ?? 'Sin comentario.', ENT_QUOTES, 'UTF-8')) ?></p>
                                        <span class="small text-muted">Etapa <?= (int) $evento['orden'] ?> de 6</span>
                                        <div class="small text-muted mt-2">
                                            Actualizado por: <em><?= htmlspecialchars($evento['actualizado_por'] ?? 'Sistema / Administrador', ENT_QUOTES, 'UTF-8') ?></em>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>Todavía no hay actualizaciones para este pedido.
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

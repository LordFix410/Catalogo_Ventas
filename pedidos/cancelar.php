<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador', 'Empleado', 'Cliente']);
require_once __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion = Database::getConnection();
$esCliente = ($_SESSION['rol'] ?? '') === 'Cliente';
$idPedido = filter_var($_GET['id'] ?? $_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$volver = $esCliente ? '/portal-cliente/seguimiento.php' : '/pedidos/index.php';

if (!$idPedido || $idPedido < 1) {
    header('Location: ' . $volver);
    exit;
}

if (empty($_SESSION['token_cancelar_pedido'])) {
    $_SESSION['token_cancelar_pedido'] = bin2hex(random_bytes(32));
}

function cargarPedidoCancelacion(mysqli $conexion, int $idPedido, bool $esCliente, int $idUsuario): ?array
{
    $sql = "SELECT p.id_pedido, p.total, p.cargo_cancelacion, p.fecha_pedido,
                   c.nombre, c.apellido,
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
            WHERE p.id_pedido = ?" . ($esCliente ? ' AND c.id_usuario = ? AND c.activo = 1' : '') . '
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($esCliente) mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $idUsuario);
    else mysqli_stmt_bind_param($stmt, 'i', $idPedido);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $pedido = mysqli_fetch_assoc($resultado) ?: null;
    mysqli_free_result($resultado);
    mysqli_stmt_close($stmt);
    return $pedido;
}

$idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
$pedido = cargarPedidoCancelacion($conexion, $idPedido, $esCliente, $idUsuario);
if (!$pedido) {
    header('Location: ' . $volver);
    exit;
}

$error = '';
$etapa = (int) $pedido['orden'];
$esTerminal = in_array(mb_strtolower($pedido['estado']), ['cancelado', 'entregado'], true);
$cargoPrevisto = $etapa >= 3 && $etapa <= 5 ? round((float) $pedido['total'] * 0.20, 2) : 0.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['token_cancelar_pedido'], $token)) {
        $error = 'La confirmación expiró. Recarga la página e inténtalo de nuevo.';
    } elseif ($esTerminal) {
        $error = 'Este pedido ya fue entregado o cancelado y no se puede cancelar nuevamente.';
    } else {
        $transaccionActiva = false;
        try {
            mysqli_begin_transaction($conexion);
            $transaccionActiva = true;

            $sqlBloqueo = 'SELECT p.id_pedido, p.total FROM pedidos p INNER JOIN clientes c ON c.id_cliente = p.id_cliente WHERE p.id_pedido = ?' . ($esCliente ? ' AND c.id_usuario = ? AND c.activo = 1' : '') . ' FOR UPDATE';
            $stmt = mysqli_prepare($conexion, $sqlBloqueo);
            if ($esCliente) mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $idUsuario);
            else mysqli_stmt_bind_param($stmt, 'i', $idPedido);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $pedidoBloqueado = mysqli_fetch_assoc($resultado);
            mysqli_free_result($resultado);
            mysqli_stmt_close($stmt);
            if (!$pedidoBloqueado) throw new RuntimeException('No se encontró el pedido.');

            $stmtEstado = mysqli_prepare(
                $conexion,
                "SELECT e.id_estado, e.nombre, e.orden
                 FROM seguimiento_pedido s
                 INNER JOIN estados_pedido e ON e.id_estado = s.id_estado
                 WHERE s.id_pedido = ?
                 ORDER BY s.fecha_hora DESC, s.id_seguimiento DESC LIMIT 1 FOR UPDATE"
            );
            mysqli_stmt_bind_param($stmtEstado, 'i', $idPedido);
            mysqli_stmt_execute($stmtEstado);
            $resultadoEstado = mysqli_stmt_get_result($stmtEstado);
            $estado = mysqli_fetch_assoc($resultadoEstado) ?: ['id_estado' => 1, 'nombre' => 'Recibido', 'orden' => 1];
            mysqli_free_result($resultadoEstado);
            mysqli_stmt_close($stmtEstado);

            if (in_array(mb_strtolower($estado['nombre']), ['cancelado', 'entregado'], true)) {
                throw new RuntimeException('Este pedido ya fue entregado o cancelado y no se puede cancelar nuevamente.');
            }

            $cargo = (int) $estado['orden'] >= 3 && (int) $estado['orden'] <= 5
                ? round((float) $pedidoBloqueado['total'] * 0.20, 2)
                : 0.00;
            $cargoConfirmado = round((float) ($_POST['cargo_confirmado'] ?? -1), 2);
            if ($cargoConfirmado !== $cargo) {
                throw new RuntimeException('El estado del pedido cambió. Revisa el cargo actualizado y confirma de nuevo.');
            }

            if ((int) $estado['orden'] < 3) {
                $stmtDetalle = mysqli_prepare($conexion, 'SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = ? FOR UPDATE');
                mysqli_stmt_bind_param($stmtDetalle, 'i', $idPedido);
                mysqli_stmt_execute($stmtDetalle);
                $resultadoDetalle = mysqli_stmt_get_result($stmtDetalle);
                $detalle = mysqli_fetch_all($resultadoDetalle, MYSQLI_ASSOC);
                mysqli_free_result($resultadoDetalle);
                mysqli_stmt_close($stmtDetalle);

                $stmtStock = mysqli_prepare($conexion, 'UPDATE productos SET stock = stock + ? WHERE id_producto = ?');
                foreach ($detalle as $linea) {
                    $cantidad = (int) $linea['cantidad'];
                    $idProducto = (int) $linea['id_producto'];
                    mysqli_stmt_bind_param($stmtStock, 'ii', $cantidad, $idProducto);
                    mysqli_stmt_execute($stmtStock);
                }
                mysqli_stmt_close($stmtStock);
            }

            $resultadoEstadoCancelado = mysqli_query($conexion, "SELECT id_estado FROM estados_pedido WHERE nombre = 'Cancelado' LIMIT 1");
            $estadoCancelado = mysqli_fetch_assoc($resultadoEstadoCancelado);
            mysqli_free_result($resultadoEstadoCancelado);
            if (!$estadoCancelado) throw new RuntimeException('La base aún no tiene el estado Cancelado. Ejecuta la migración de pedidos.');

            $stmtActualizar = mysqli_prepare($conexion, 'UPDATE pedidos SET cargo_cancelacion = ? WHERE id_pedido = ?');
            mysqli_stmt_bind_param($stmtActualizar, 'di', $cargo, $idPedido);
            mysqli_stmt_execute($stmtActualizar);
            mysqli_stmt_close($stmtActualizar);

            $idEstadoCancelado = (int) $estadoCancelado['id_estado'];
            $observacion = $cargo > 0
                ? 'Pedido cancelado. Cargo de cancelación del 20%: Q ' . number_format($cargo, 2) . '.'
                : 'Pedido cancelado antes de preparación. Sin cargo de cancelación.';
            $stmtHistorial = mysqli_prepare($conexion, 'INSERT INTO seguimiento_pedido (id_pedido, id_estado, id_usuario, observacion) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmtHistorial, 'iiis', $idPedido, $idEstadoCancelado, $idUsuario, $observacion);
            mysqli_stmt_execute($stmtHistorial);
            mysqli_stmt_close($stmtHistorial);

            mysqli_commit($conexion);
            $transaccionActiva = false;
            $_SESSION['token_cancelar_pedido'] = bin2hex(random_bytes(32));
            header('Location: ' . $volver . ($esCliente ? '?cancelado=' : '?msg=cancelado') . $idPedido);
            exit;
        } catch (Throwable $e) {
            if ($transaccionActiva) mysqli_rollback($conexion);
            error_log('Error al cancelar pedido: ' . $e->getMessage());
            $error = $e->getMessage();
            $pedido = cargarPedidoCancelacion($conexion, $idPedido, $esCliente, $idUsuario) ?? $pedido;
            $etapa = (int) $pedido['orden'];
            $cargoPrevisto = $etapa >= 3 && $etapa <= 5 ? round((float) $pedido['total'] * 0.20, 2) : 0.00;
            $esTerminal = in_array(mb_strtolower($pedido['estado']), ['cancelado', 'entregado'], true);
        }
    }
}

$titulo = 'Cancelar pedido';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="app-main">
    <div class="app-content-header"><div class="container-fluid">
        <span class="page-eyebrow">GESTIÓN DE PEDIDOS</span>
        <h1 class="page-title">Cancelar pedido #<?= str_pad((string) $idPedido, 5, '0', STR_PAD_LEFT) ?></h1>
        <p class="page-description">Revisa el estado y cualquier cargo antes de confirmar.</p>
    </div></div>
    <div class="app-content"><div class="container-fluid"><div class="row justify-content-center"><div class="col-12 col-lg-8 col-xl-6">
        <?php if ($error): ?><div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <section class="dashboard-card">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div><span class="small text-muted">CLIENTE</span><h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido'], ENT_QUOTES, 'UTF-8') ?></h2><span class="badge text-bg-secondary"><?= htmlspecialchars($pedido['estado'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="text-end"><span class="small text-muted d-block">Total del pedido</span><strong class="fs-4">Q <?= number_format((float) $pedido['total'], 2) ?></strong></div>
            </div>
            <?php if ($esTerminal): ?>
                <div class="alert alert-light mt-4 mb-0">Este pedido ya no se puede cancelar.</div>
                <a href="<?= htmlspecialchars($volver, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary mt-3">Regresar</a>
            <?php else: ?>
                <div class="alert <?= $cargoPrevisto > 0 ? 'alert-warning' : 'alert-info' ?> mt-4 mb-3">
                    <?php if ($cargoPrevisto > 0): ?>
                        Si el pedido ya está preparado o en ruta, se aplicará un cargo de cancelación del <strong>20%</strong>: <strong>Q <?= number_format($cargoPrevisto, 2) ?></strong>.
                    <?php else: ?>
                        Al estar antes de la preparación, este pedido se puede cancelar sin cargo.
                    <?php endif; ?>
                </div>
                <p class="small text-muted">La cancelación quedará registrada en el historial. La acción no se puede deshacer.</p>
                <form method="post" class="d-flex flex-wrap justify-content-end gap-2">
                    <input type="hidden" name="id_pedido" value="<?= (int) $idPedido ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token_cancelar_pedido'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="cargo_confirmado" value="<?= number_format($cargoPrevisto, 2, '.', '') ?>">
                    <a href="<?= htmlspecialchars($volver, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Conservar pedido</a>
                    <button type="submit" class="btn btn-danger" <?= $esTerminal ? 'disabled' : '' ?>><i class="bi bi-x-circle me-1"></i>Confirmar cancelación</button>
                </form>
            <?php endif; ?>
        </section>
    </div></div></div></div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

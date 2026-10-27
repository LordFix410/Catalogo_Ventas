
<?php
require_once '../includes/auth.php';
requiereRol(['Cliente']);

require_once '../config/database.php';

$conexion = Database::getConnection();
$nombreCliente = $_SESSION['nombre'] ?? 'Cliente';
$pedidos = [];
$error = '';

$sql = "SELECT
            p.id_pedido,
            p.fecha_pedido,
            p.total,
            p.cargo_cancelacion,
            COALESCE(e.nombre, 'Recibido') AS estado,
            COALESCE(e.orden, 1) AS orden,
            s.fecha_hora AS ultima_actualizacion
        FROM pedidos p
        INNER JOIN clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN seguimiento_pedido s ON s.id_seguimiento = (
            SELECT s2.id_seguimiento
            FROM seguimiento_pedido s2
            WHERE s2.id_pedido = p.id_pedido
            ORDER BY s2.fecha_hora DESC, s2.id_seguimiento DESC
            LIMIT 1
        )
        LEFT JOIN estados_pedido e ON e.id_estado = s.id_estado
        WHERE c.id_usuario = ? AND c.activo = 1
        ORDER BY p.fecha_pedido DESC";

$stmt = mysqli_prepare($conexion, $sql);

if ($stmt) {
    $idUsuario = (int) $_SESSION['id_usuario'];
    mysqli_stmt_bind_param($stmt, "i", $idUsuario);

    if (mysqli_stmt_execute($stmt)) {
        $resultado = mysqli_stmt_get_result($stmt);

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $pedidos[] = $fila;
        }

        mysqli_free_result($resultado);
    } else {
        $error = 'No se pudieron consultar los pedidos.';
    }

    mysqli_stmt_close($stmt);
} else {
    $error = 'No se pudo preparar la consulta de pedidos.';
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../includes/sidebar.php';
?>


<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">
                    <h3 class="mb-0">
                        Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Cliente') ?>
                    </h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">

                        <li class="breadcrumb-item">
                            <a href="/portal-cliente/catalogo.php">Catálogo</a>
                        </li>

                        <li class="breadcrumb-item active">
                            Seguimiento
                        </li>

                    </ol>
                </div>

            </div>

        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">
                        Mis Pedidos
                    </h3>
                </div>

                <div class="card-body">
                    
                    <h4 class="mb-3">
                        Hola, <?= htmlspecialchars($nombreCliente) ?>.
                    </h4>

                    <p class="text-muted">
                        Aquí puedes consultar tus pedidos y su estado actual.
                    </p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php elseif (empty($pedidos)): ?>
                        <div class="alert alert-info">
                            Todavía no tienes pedidos registrados.
                        </div>
                    <?php else: ?>
                <?php if (isset($_GET['cancelado'])): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>El pedido fue cancelado correctamente.</div><?php endif; ?>
                        <div class="cliente-pedidos-lista">
                            <?php foreach ($pedidos as $pedido): ?>
                                <?php
                                $cancelado = mb_strtolower((string) $pedido['estado']) === 'cancelado';
                                $etapa = min(6, max(1, (int) $pedido['orden']));
                                $avance = (int) round(($etapa / 6) * 100);
                                $entregado = $etapa === 6;
                                ?>
                                <article class="cliente-pedido-card">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div>
                                            <span class="small text-muted">PEDIDO</span>
                                            <h5 class="mb-1 fw-bold">#<?= str_pad((string) $pedido['id_pedido'], 5, '0', STR_PAD_LEFT) ?></h5>
                                            <span class="small text-muted"><?= date('d/m/Y · H:i', strtotime($pedido['fecha_pedido'])) ?></span>
                                        </div>
                                        <span class="badge rounded-pill <?= $cancelado ? 'text-bg-secondary' : ($entregado ? 'text-bg-success' : 'text-bg-primary') ?> px-3 py-2">
                                            <?= htmlspecialchars($pedido['estado'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <?php if (!$cancelado): ?><div class="cliente-pedido-progreso mt-4">
                                        <div class="d-flex justify-content-between small mb-2">
                                            <span class="fw-semibold">Avance del pedido</span>
                                            <span class="text-muted">Etapa <?= $etapa ?> de 6</span>
                                        </div>
                                        <div class="progress" role="progressbar" aria-label="Avance del pedido" aria-valuenow="<?= $avance ?>" aria-valuemin="0" aria-valuemax="100" style="height: 9px;">
                                            <div class="progress-bar <?= $entregado ? 'bg-success' : '' ?>" style="width: <?= $avance ?>%"></div>
                                        </div>
                                    </div><?php endif; ?>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-3 border-top">
                                        <div>
                                            <span class="small text-muted d-block">Total</span>
                                            <strong class="text-primary">Q <?= number_format((float) $pedido['total'], 2) ?></strong>
                                        </div>
                                        <div class="text-md-end">
                                            <span class="small text-muted d-block">Último movimiento</span>
                                            <span class="small"><?= $pedido['ultima_actualizacion'] ? date('d/m/Y · H:i', strtotime($pedido['ultima_actualizacion'])) : 'Esperando actualización' ?></span>
                                        </div>
                                        <a href="/seguimiento/detalle.php?id=<?= (int) $pedido['id_pedido'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-clock-history me-1"></i> Ver historial
                                        </a>
                                        <?php if (!$cancelado && !$entregado && $etapa === 1): ?><a href="/pedidos/editar.php?id=<?= (int) $pedido['id_pedido'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a><?php endif; ?>
                                        <?php if (!$cancelado && !$entregado): ?><a href="/pedidos/cancelar.php?id=<?= (int) $pedido['id_pedido'] ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Cancelar</a><?php endif; ?>
                                    </div>
                                    <?php if ($cancelado && (float) $pedido['cargo_cancelacion'] > 0): ?><div class="alert alert-warning mt-3 mb-0">Cargo de cancelación (20%): Q <?= number_format((float) $pedido['cargo_cancelacion'], 2) ?></div><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>
    </div>

</main>

<?php
require_once '../includes/footer.php';
?>

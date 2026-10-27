<?php
$titulo = "Detalle del Pedido";

require_once 'funciones.php';

$id_pedido = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pedido <= 0) {
    header('Location: index.php');
    exit;
}

$conexion = Database::getConnection();
$pedido   = obtenerPedido($conexion, $id_pedido);

if (!$pedido) {
    header('Location: index.php?error=no_encontrado');
    exit;
}

$items     = listarDetallePedido($conexion, $id_pedido);
$historial = listarHistorialSeguimiento($conexion, $id_pedido);
$ultimoEvento = !empty($historial) ? end($historial) : null;
$estadoActual = $ultimoEvento['estado'] ?? null;
$ordenActual = (int) ($ultimoEvento['orden'] ?? 1);

$mensajes = [
    'creado' => 'Pedido registrado correctamente.',
    'actualizado' => 'Pedido actualizado correctamente.',
];
$mensaje = $mensajes[$_GET['msg'] ?? ''] ?? null;

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<style>
  .dato-pedido { color: #6B7280; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
  .valor-pedido { color: #1F2937; font-weight: 600; }
  .pedido-historial-item { position: relative; min-width: 0; padding-left: 28px; padding-bottom: 18px; border-left: 2px solid #FCE7F3; overflow-wrap: anywhere; }
  .pedido-historial-item:last-child { border-left: 2px solid transparent; padding-bottom: 0; }
  .pedido-historial-item::before {
      content: '';
      position: absolute;
      left: -7px;
      top: 2px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #EC4899;
  }
</style>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE PEDIDOS</span>
                    <h1 class="page-title">Pedido #<?= str_pad($id_pedido, 5, '0', STR_PAD_LEFT) ?></h1>
                    <p class="page-description">
                        Cliente: <?= escapar($pedido['cliente']) ?>
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="index.php">Pedidos</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: #10B981; color: #ffffff;">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $mensaje ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-12 col-xxl-4">
                    <div class="dashboard-card mb-4">
                        <h3 class="section-title">
                            <i class="bi bi-receipt"></i> Información del pedido
                        </h3>

                        <div class="mb-3">
                            <span class="badge px-3 py-2 <?= colorBadgeEstado($estadoActual ?? '') ?>">
                                <?= escapar($estadoActual ?? 'Sin estado') ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <div class="dato-pedido">Cliente</div>
                            <div class="valor-pedido"><?= escapar($pedido['cliente']) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="dato-pedido">Teléfono</div>
                            <div class="valor-pedido"><?= escapar($pedido['telefono'] ?: 'No registrado') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="dato-pedido">Dirección de entrega</div>
                            <div class="valor-pedido"><?= escapar($pedido['direccion_entrega'] ?: 'No registrada') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="dato-pedido">Fecha del pedido</div>
                            <div class="valor-pedido"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="dato-pedido">Observaciones</div>
                            <div class="valor-pedido"><?= nl2br(escapar($pedido['observaciones'] ?: 'Sin observaciones')) ?></div>
                        </div>

                        <?php if ((float) ($pedido['cargo_cancelacion'] ?? 0) > 0): ?>
                            <div class="alert alert-warning"><strong>Cargo de cancelación (20%):</strong> Q <?= number_format((float) $pedido['cargo_cancelacion'], 2) ?></div>
                        <?php endif; ?>

                        <div class="pedido-info-acciones">
                            <?php if (!in_array(mb_strtolower((string) ($estadoActual ?? '')), ['cancelado', 'entregado'], true)): ?>
                                <?php if ($ordenActual === 1 && mb_strtolower((string) $estadoActual) === 'recibido'): ?><a href="editar.php?id=<?= $id_pedido ?>" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar</a><?php endif; ?>
                                <a href="cancelar.php?id=<?= $id_pedido ?>" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancelar pedido</a>
                            <?php endif; ?>
                            <a href="/seguimiento/detalle.php?id=<?= $id_pedido ?>" class="btn btn-chiquis">
                                <i class="bi bi-truck me-1"></i> Seguimiento
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Regresar
                            </a>
                        </div>
                    </div>

                </div>

                <div class="col-12 col-xxl-8">
                    <div class="dashboard-card">
                        <h3 class="section-title">
                            <i class="bi bi-box-seam"></i> Detalle de productos
                        </h3>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 tabla-detalle-pedido">
                                <thead style="background-color: #FCE7F3; color: #1F2937;">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Precio unitario</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td data-label="Producto"><?= escapar($item['producto']) ?></td>
                                                <td data-label="Cantidad" class="text-end"><?= (int)$item['cantidad'] ?></td>
                                                <td data-label="Precio unitario" class="text-end">Q <?= number_format($item['precio_unitario'], 2) ?></td>
                                                <td data-label="Subtotal" class="text-end fw-bold">Q <?= number_format($item['subtotal'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Este pedido no tiene productos registrados.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total del pedido</td>
                                        <td class="text-end fw-bold" style="color:#BE185D; font-size: 1.1rem;">
                                            Q <?= number_format($pedido['total'], 2) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <?php if (!empty($historial)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <section class="dashboard-card pedido-historial-card">
                            <h3 class="section-title"><i class="bi bi-clock-history"></i> Historial del pedido</h3>
                            <div class="pedido-historial-lista">
                                <?php foreach (array_reverse($historial) as $h): ?>
                                    <article class="pedido-historial-item">
                                        <div class="fw-bold"><?= escapar($h['estado']) ?></div>
                                        <div class="text-muted small"><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></div>
                                        <?php if (!empty($h['observacion'])): ?>
                                            <div class="small mt-1"><?= escapar($h['observacion']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($h['actualizado_por'])): ?>
                                            <div class="text-muted small mt-1">Por <?= escapar($h['actualizado_por']) ?></div>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

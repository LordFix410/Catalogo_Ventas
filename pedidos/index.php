<?php
$titulo = "Pedidos";

require_once 'funciones.php';

$conexion = Database::getConnection();
$pedidos  = listarPedidosConEstado($conexion);
$estados  = listarEstadosPedido($conexion);

$totalPedidos   = count($pedidos);
$montoTotal     = array_sum(array_column($pedidos, 'total'));
$entregados     = count(array_filter($pedidos, fn($p) =>
    (int)($p['id_estado'] ?? 0) === 6
    || strcasecmp(trim((string)($p['estado'] ?? '')), 'Entregado') === 0
));
$enProceso      = $totalPedidos - $entregados;

$mensajes = [
    'creado' => 'Pedido registrado correctamente.',
];
$errores = [
    'no_encontrado'     => 'El pedido solicitado no existe.',
    'datos_incompletos' => 'La solicitud no tiene los datos necesarios.',
];
$mensaje = $mensajes[$_GET['msg'] ?? ''] ?? null;
$error   = $errores[$_GET['error'] ?? ''] ?? null;

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE PEDIDOS</span>
                    <h1 class="page-title">Pedidos</h1>
                    <p class="page-description">
                        Gestiona de forma centralizada los pedidos registrados por los clientes.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item active">Pedidos</li>
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

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-pedidos">
                        <div class="stat-icon"><i class="bi bi-cart"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Pedidos totales</span>
                            <strong class="stat-number"><?= $totalPedidos ?></strong>
                            <span class="stat-description">Registrados en el sistema</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-pedidos">
                        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Monto total</span>
                            <strong class="stat-number">Q <?= number_format($montoTotal, 2) ?></strong>
                            <span class="stat-description">Suma de todos los pedidos</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-seguimiento">
                        <div class="stat-icon"><i class="bi bi-truck"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">En proceso</span>
                            <strong class="stat-number"><?= $enProceso ?></strong>
                            <span class="stat-description">Aún no entregados</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-productos">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Entregados</span>
                            <strong class="stat-number"><?= $entregados ?></strong>
                            <span class="stat-description">Pedidos completados</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card mb-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <h3 class="section-title mb-0">
                        <i class="bi bi-list-ul"></i> Listado de Pedidos
                    </h3>
                    <a href="crear.php" class="btn btn-chiquis">
                        <i class="bi bi-cart-plus me-1"></i> Nuevo Pedido
                    </a>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroBuscar" class="form-control border-start-0" placeholder="Buscar por cliente o número de pedido...">
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <select id="filtroEstado" class="form-select">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= escapar($e['nombre']) ?>"><?= escapar($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaPedidos">
                        <thead style="background-color: #FCE7F3; color: #1F2937;">
                            <tr>
                                <th>N° Pedido</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="fila-pedido"
                                    data-texto="<?= escapar(strtolower('#' . $p['id_pedido'] . ' ' . $p['cliente'])) ?>"
                                    data-estado="<?= escapar($p['estado'] ?? '') ?>">
                                    <td class="text-nowrap"><strong>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= escapar($p['cliente']) ?></td>
                                    <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                                    <td class="fw-bold">Q <?= number_format($p['total'], 2) ?></td>
                                    <td>
                                        <span class="badge px-3 py-2 <?= colorBadgeEstado($p['estado'] ?? '') ?>">
                                            <?= escapar($p['estado'] ?? 'Sin estado') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="detalle.php?id=<?= $p['id_pedido'] ?>" class="btn btn-sm btn-chiquis" title="Ver detalle">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="filaSinResultados" style="display: none;">
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?= empty($pedidos) ? 'Aún no hay pedidos registrados.' : 'No se encontraron pedidos con esos filtros.' ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBuscar   = document.getElementById('filtroBuscar');
    const filtroEstado  = document.getElementById('filtroEstado');
    const sinResultados = document.getElementById('filaSinResultados');

    function filtrarPedidos() {
        const termino = inputBuscar.value.toLowerCase().trim();
        const estado  = filtroEstado.value;
        let visibles  = 0;

        document.querySelectorAll('#tablaPedidos .fila-pedido').forEach(fila => {
            const coincide = fila.dataset.texto.includes(termino)
                && (estado === '' || fila.dataset.estado === estado);

            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        sinResultados.style.display = visibles === 0 ? '' : 'none';
    }

    inputBuscar.addEventListener('input', filtrarPedidos);
    filtroEstado.addEventListener('change', filtrarPedidos);
    filtrarPedidos();
});
</script>

<?php include '../includes/footer.php'; ?>

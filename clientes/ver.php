<?php
$titulo = "Detalle del Cliente";

require_once 'funciones.php';

$id_cliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_cliente <= 0) {
    header('Location: index.php');
    exit;
}

$conexion = Database::getConnection();
$cliente  = obtenerCliente($conexion, $id_cliente);

if (!$cliente) {
    header('Location: index.php?error=no_encontrado');
    exit;
}

$pedidos = ejecutarProcedimiento($conexion, "CALL sp_portal_pedidos_cliente(?)", 'i', [$id_cliente]);

$totalComprado = array_sum(array_column($pedidos, 'total'));

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<style>
  .badge-minorista { background-color: #FCE7F3; color: #BE185D; }
  .badge-mayorista { background-color: #8B5CF6; color: #FFFFFF; }
  .dato-cliente { color: #6B7280; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
  .valor-cliente { color: #1F2937; font-weight: 600; }
</style>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE CLIENTES</span>
                    <h1 class="page-title"><?= escapar($cliente['nombre'] . ' ' . $cliente['apellido']) ?></h1>
                    <p class="page-description">
                        Cliente #<?= str_pad($id_cliente, 5, '0', STR_PAD_LEFT) ?>
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="index.php">Clientes</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <div class="row g-4">

                <div class="col-12 col-xl-4">
                    <div class="dashboard-card">
                        <h3 class="section-title">
                            <i class="bi bi-person-vcard"></i> Información
                        </h3>

                        <div class="mb-3 d-flex gap-2">
                            <span class="badge px-3 py-2 badge-<?= strtolower($cliente['tipo_cliente']) ?>">
                                <?= escapar($cliente['tipo_cliente']) ?>
                            </span>
                            <?php if ($cliente['activo']): ?>
                                <span class="badge px-3 py-2 text-bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge px-3 py-2 text-bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <div class="dato-cliente">Teléfono</div>
                            <div class="valor-cliente"><?= escapar($cliente['telefono'] ?: 'No registrado') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="dato-cliente">Correo</div>
                            <div class="valor-cliente"><?= escapar($cliente['correo'] ?: 'Sin cuenta en el portal') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="dato-cliente">Dirección</div>
                            <div class="valor-cliente"><?= escapar($cliente['direccion'] ?: 'No registrada') ?></div>
                        </div>
                        <div class="mb-4">
                            <div class="dato-cliente">Fecha de registro</div>
                            <div class="valor-cliente"><?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?></div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="editar.php?id=<?= $id_cliente ?>" class="btn btn-chiquis">
                                <i class="bi bi-pencil-square me-1"></i> Editar
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Regresar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="dashboard-card">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h3 class="section-title mb-0">
                                <i class="bi bi-cart"></i> Pedidos del cliente
                            </h3>
                            <span class="text-muted fw-bold">
                                <?= count($pedidos) ?> pedido(s) · Total Q <?= number_format($totalComprado, 2) ?>
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>N° Pedido</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidos)): ?>
                                        <?php foreach ($pedidos as $p): ?>
                                            <tr>
                                                <td><strong>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                                <td><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                                                <td>Q <?= number_format($p['total'], 2) ?></td>
                                                <td><?= escapar($p['estado'] ?? 'Sin estado') ?></td>
                                                <td class="text-center">
                                                    <a href="/seguimiento/detalle.php?id=<?= $p['id_pedido'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i> Seguimiento
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Este cliente aún no tiene pedidos.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

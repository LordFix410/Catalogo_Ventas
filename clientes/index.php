<?php
// clientes/index.php
// 1.1.2 Consulta de clientes / 1.1.5 Clasificación minorista y mayorista
$titulo = "Clientes";

require_once 'funciones.php';

$conexion = Database::getConnection();
$clientes = ejecutarProcedimiento($conexion, "CALL sp_cliente_listar()");

// Resumen por clasificación (solo clientes activos)
$activos     = array_filter($clientes, fn($c) => $c['activo']);
$minoristas  = count(array_filter($activos, fn($c) => $c['tipo_cliente'] === 'Minorista'));
$mayoristas  = count(array_filter($activos, fn($c) => $c['tipo_cliente'] === 'Mayorista'));
$inactivos   = count($clientes) - count($activos);

$mensajes = [
    'creado'      => 'Cliente registrado correctamente.',
    'actualizado' => 'Datos del cliente actualizados correctamente.',
    'desactivado' => 'Cliente desactivado. Su historial de pedidos se conserva.',
    'reactivado'  => 'Cliente reactivado correctamente.',
];
$errores = [
    'no_encontrado'     => 'El cliente solicitado no existe.',
    'datos_incompletos' => 'La solicitud no tiene los datos necesarios.',
];
$mensaje = $mensajes[$_GET['msg'] ?? ''] ?? null;
$error   = $errores[$_GET['error'] ?? ''] ?? null;

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<!-- Insignias del módulo usando la paleta oficial -->
<style>
  .badge-minorista { background-color: #FCE7F3; color: #BE185D; } /* Rosa Pastel / Rosa Profundo */
  .badge-mayorista { background-color: #8B5CF6; color: #FFFFFF; } /* Morado */
  .fila-inactiva td { color: #6B7280; }                            /* Gris Pizarra */
  .stat-inactivos { background: #F8FAFC; }                          /* Gris Hielo */
  .stat-inactivos .stat-icon { background: #E5E7EB; color: #6B7280; }
</style>

<main class="app-main">

    <!-- ENCABEZADO -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE CLIENTES</span>
                    <h1 class="page-title">Clientes</h1>
                    <p class="page-description">
                        Registra, consulta y administra a los clientes minoristas y mayoristas.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item active">Clientes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENIDO -->
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

            <!-- RESUMEN -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-clientes">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Clientes activos</span>
                            <strong class="stat-number"><?= count($activos) ?></strong>
                            <span class="stat-description">Disponibles para pedidos</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-clientes">
                        <div class="stat-icon"><i class="bi bi-bag"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Minoristas</span>
                            <strong class="stat-number"><?= $minoristas ?></strong>
                            <span class="stat-description">Compra al detalle</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-productos">
                        <div class="stat-icon"><i class="bi bi-boxes"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Mayoristas</span>
                            <strong class="stat-number"><?= $mayoristas ?></strong>
                            <span class="stat-description">Compra por volumen</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="dashboard-stat stat-inactivos">
                        <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
                        <div class="stat-content">
                            <span class="stat-title">Inactivos</span>
                            <strong class="stat-number"><?= $inactivos ?></strong>
                            <span class="stat-description">Dados de baja</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARJETA PRINCIPAL -->
            <div class="dashboard-card mb-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <h3 class="section-title mb-0">
                        <i class="bi bi-person-lines-fill"></i> Listado de Clientes
                    </h3>
                    <a href="crear.php" class="btn btn-chiquis">
                        <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
                    </a>
                </div>

                <!-- FILTROS -->
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroBuscar" class="form-control border-start-0" placeholder="Buscar por nombre, teléfono o dirección...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="filtroTipo" class="form-select">
                            <option value="">Todos los tipos</option>
                            <?php foreach (TIPOS_CLIENTE as $tipo): ?>
                                <option value="<?= $tipo ?>"><?= $tipo ?>s</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="filtroEstado" class="form-select">
                            <option value="1" selected>Solo activos</option>
                            <option value="0">Solo inactivos</option>
                            <option value="">Todos</option>
                        </select>
                    </div>
                </div>

                <!-- TABLA RESPONSIVA -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaClientes">
                        <thead style="background-color: #FCE7F3; color: #1F2937;">
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th class="d-none d-xl-table-cell">Dirección</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                                <?php $nombreCompleto = $c['nombre'] . ' ' . $c['apellido']; ?>
                                <tr class="fila-cliente <?= $c['activo'] ? '' : 'fila-inactiva' ?>"
                                    data-tipo="<?= escapar($c['tipo_cliente']) ?>"
                                    data-activo="<?= $c['activo'] ? '1' : '0' ?>">
                                    <td class="text-nowrap"><strong>#<?= str_pad($c['id_cliente'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <div class="fw-bold"><?= escapar($nombreCompleto) ?></div>
                                        <?php if (!empty($c['correo'])): ?>
                                            <small class="text-muted"><?= escapar($c['correo']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap"><?= escapar($c['telefono'] ?: '—') ?></td>
                                    <td class="text-truncate d-none d-xl-table-cell" style="max-width: 240px;" title="<?= escapar($c['direccion']) ?>">
                                        <?= escapar($c['direccion'] ?: '—') ?>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2 badge-<?= strtolower($c['tipo_cliente']) ?>">
                                            <?= escapar($c['tipo_cliente']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($c['activo']): ?>
                                            <span class="badge px-3 py-2 text-bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge px-3 py-2 text-bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="ver.php?id=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-chiquis" title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <?php if ($c['activo']): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-modal-estado"
                                                        title="Desactivar"
                                                        data-id="<?= $c['id_cliente'] ?>"
                                                        data-nombre="<?= escapar($nombreCompleto) ?>"
                                                        data-accion="desactivar"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEstadoCliente">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success btn-modal-estado"
                                                        title="Reactivar"
                                                        data-id="<?= $c['id_cliente'] ?>"
                                                        data-nombre="<?= escapar($nombreCompleto) ?>"
                                                        data-accion="reactivar"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEstadoCliente">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="filaSinResultados" style="display: none;">
                                <td colspan="7" class="text-center text-muted py-4">
                                    <?= empty($clientes) ? 'No hay clientes registrados en el sistema.' : 'No se encontraron clientes con esos filtros.' ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

</main>

<!-- MODAL DESACTIVAR / REACTIVAR -->
<div class="modal fade" id="modalEstadoCliente" tabindex="-1" aria-labelledby="modalEstadoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="eliminar.php" method="POST">

                <div class="modal-header" style="background-color: #EC4899; color: #ffffff;">
                    <h5 class="modal-title" id="modalEstadoClienteLabel">
                        <i class="bi bi-person-gear me-2"></i> <span id="modalTitulo">Desactivar cliente</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="id_cliente" id="modal_id_cliente">
                    <input type="hidden" name="accion" id="modal_accion">
                    <p class="mb-2" id="modalPregunta"></p>
                    <p class="text-muted small mb-0" id="modalNota"></p>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-chiquis" id="modalConfirmar">Confirmar</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pasar datos al modal según la acción
    document.querySelectorAll('.btn-modal-estado').forEach(btn => {
        btn.addEventListener('click', function () {
            const accion = this.dataset.accion;
            const nombre = this.dataset.nombre;

            document.getElementById('modal_id_cliente').value = this.dataset.id;
            document.getElementById('modal_accion').value = accion;

            if (accion === 'desactivar') {
                document.getElementById('modalTitulo').textContent = 'Desactivar cliente';
                document.getElementById('modalPregunta').textContent = '¿Desea desactivar a ' + nombre + '?';
                document.getElementById('modalNota').textContent = 'El cliente ya no aparecerá al crear pedidos, pero su historial se conserva. Puede reactivarlo en cualquier momento.';
                document.getElementById('modalConfirmar').textContent = 'Desactivar';
            } else {
                document.getElementById('modalTitulo').textContent = 'Reactivar cliente';
                document.getElementById('modalPregunta').textContent = '¿Desea reactivar a ' + nombre + '?';
                document.getElementById('modalNota').textContent = 'El cliente volverá a estar disponible para registrar pedidos.';
                document.getElementById('modalConfirmar').textContent = 'Reactivar';
            }
        });
    });

    // Búsqueda y filtros en tiempo real
    const inputBuscar  = document.getElementById('filtroBuscar');
    const filtroTipo   = document.getElementById('filtroTipo');
    const filtroEstado = document.getElementById('filtroEstado');
    const sinResultados = document.getElementById('filaSinResultados');

    function filtrarClientes() {
        const termino = inputBuscar.value.toLowerCase().trim();
        const tipo    = filtroTipo.value;
        const estado  = filtroEstado.value;
        let visibles  = 0;

        document.querySelectorAll('#tablaClientes .fila-cliente').forEach(fila => {
            const coincide = fila.textContent.toLowerCase().includes(termino)
                && (tipo === '' || fila.dataset.tipo === tipo)
                && (estado === '' || fila.dataset.activo === estado);

            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        sinResultados.style.display = visibles === 0 ? '' : 'none';
    }

    inputBuscar.addEventListener('input', filtrarClientes);
    filtroTipo.addEventListener('change', filtrarClientes);
    filtroEstado.addEventListener('change', filtrarClientes);
    filtrarClientes();
});
</script>

<?php include '../includes/footer.php'; ?>

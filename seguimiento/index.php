<?php
$titulo = "Seguimiento de Pedidos";

require_once '../config/database.php';

$conexion = Database::getConnection();

// 1. Obtener la lista de pedidos con su último estado
$pedidos = [];
$resPedidos = mysqli_query($conexion, "CALL sp_seguimiento_listar()");
if ($resPedidos) {
    $pedidos = mysqli_fetch_all($resPedidos, MYSQLI_ASSOC);
    mysqli_free_result($resPedidos);
}
Database::limpiarResultados($conexion);

// 2. Obtener lista de estados posibles para el modal
$estados = [];
$resEstados = mysqli_query($conexion, "SELECT id_estado, nombre, orden FROM estados_pedido ORDER BY orden ASC");
if ($resEstados) {
    $estados = mysqli_fetch_all($resEstados, MYSQLI_ASSOC);
    mysqli_free_result($resEstados);
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<!-- Estilos de insignias personalizados usando la paleta oficial -->
<style>
  .badge-estado-1 { background-color: #6B7280; color: #fff; } /* Recibido: Gris Pizarra */
  .badge-estado-2 { background-color: #F59E0B; color: #fff; } /* En preparación: Ámbar */
  .badge-estado-3 { background-color: #8B5CF6; color: #fff; } /* Preparado: Morado */
  .badge-estado-4 { background-color: #EC4899; color: #fff; } /* Despachado: Rosa Chiquis */
  .badge-estado-5 { background-color: #BE185D; color: #fff; } /* En proceso de entrega: Rosa Profundo */
  .badge-estado-6 { background-color: #10B981; color: #fff; } /* Entregado: Verde Esmeralda */

  .btn-chiquis {
      background-color: #EC4899;
      color: #ffffff;
      border: none;
      transition: all 0.2s ease-in-out;
  }
  .btn-chiquis:hover {
      background-color: #BE185D;
      color: #ffffff;
  }
</style>

<main class="app-main">

    <!-- ENCABEZADO -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE SEGUIMIENTO</span>
                    <h1 class="page-title">Seguimiento de Pedidos</h1>
                    <p class="page-description">
                        Gestiona y actualiza el estado del ciclo de entrega de cada pedido.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item active">Seguimiento</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENIDO -->
    <div class="app-content">
        <div class="container-fluid">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'actualizado'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: #10B981; color: #ffffff;">
                    <i class="bi bi-check-circle-fill me-2"></i> Estado del pedido actualizado correctamente.
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- TARJETA PRINCIPAL -->
            <div class="dashboard-card mb-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <h3 class="section-title mb-0">
                        <i class="bi bi-truck me-2"></i> Control de Entregas
                    </h3>

                    <!-- Buscador en tiempo real -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroBuscar" class="form-control border-start-0" placeholder="Buscar pedido o cliente...">
                        </div>
                    </div>
                </div>

                <!-- TABLA RESPONSIVA -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaSeguimiento">
                        <thead style="background-color: #FCE7F3; color: #1F2937;">
                            <tr>
                                <th>N° Pedido</th>
                                <th>Cliente</th>
                                <th>Fecha Pedido</th>
                                <th>Total</th>
                                <th>Estado Actual</th>
                                <th>Última Actualización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pedidos)): ?>
                                <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td><strong>#<?= str_pad($p['id_pedido'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($p['cliente']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
                                        <td><strong>Q <?= number_format($p['total'], 2) ?></strong></td>
                                        <td>
                                            <span class="badge px-3 py-2 badge-estado-<?= $p['id_estado'] ?? '1' ?>">
                                                <?= htmlspecialchars($p['estado'] ?? 'Recibido') ?>
                                            </span>
                                        </td>
                                        <td><?= $p['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($p['ultima_actualizacion'])) : 'Sin registros' ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="detalle.php?id=<?= $p['id_pedido'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver Historial">
                                                    <i class="bi bi-eye"></i> Detalle
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-chiquis btn-modal-estado" 
                                                        data-id="<?= $p['id_pedido'] ?>" 
                                                        data-estado="<?= $p['id_estado'] ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalCambiarEstado"
                                                        data-toggle="modal" 
                                                        data-target="#modalCambiarEstado">
                                                    <i class="bi bi-pencil-square"></i> Cambiar Estado
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay pedidos registrados en el sistema.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

</main>

<!-- MODAL CAMBIAR ESTADO -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="cambiar_estado.php" method="POST">
                
                <div class="modal-header" style="background-color: #EC4899; color: #ffffff;">
                    <h5 class="modal-title" id="modalCambiarEstadoLabel">
                        <i class="bi bi-arrow-repeat me-2"></i> Actualizar Estado del Pedido
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="id_pedido" id="modal_id_pedido">
                    
                    <div class="mb-3">
                        <label for="modal_id_estado" class="form-label fw-bold">Nuevo Estado:</label>
                        <select name="id_estado" id="modal_id_estado" class="form-select form-control" required>
                            <?php foreach ($estados as $est): ?>
                                <option value="<?= $est['id_estado'] ?>"><?= $est['orden'] ?>. <?= htmlspecialchars($est['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label fw-bold">Observación / Comentario:</label>
                        <textarea name="observacion" id="observacion" class="form-control" rows="3" placeholder="Ej: Pedido empaquetado y listo para transporte..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-chiquis">Guardar Cambio</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pasar datos al Modal al hacer clic en el botón
    const botonesEstado = document.querySelectorAll('.btn-modal-estado');
    botonesEstado.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_id_pedido').value = this.getAttribute('data-id');
            document.getElementById('modal_id_estado').value = this.getAttribute('data-estado');
        });
    });

    // Buscador en tiempo real de la tabla
    const inputBuscar = document.getElementById('filtroBuscar');
    if (inputBuscar) {
        inputBuscar.addEventListener('keyup', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaSeguimiento tbody tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(termino) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
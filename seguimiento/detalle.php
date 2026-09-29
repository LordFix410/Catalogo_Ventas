<?php
// seguimiento/detalle.php
require_once '../config/database.php';

$id_pedido = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pedido <= 0) {
    header('Location: index.php');
    exit;
}

$conexion = Database::getConnection();

// Datos del pedido
$pedidoInfo = null;
$resP = mysqli_query($conexion, "CALL sp_pedido_obtener($id_pedido)");
if ($resP) {
    $pedidoInfo = mysqli_fetch_assoc($resP);
    mysqli_free_result($resP);
}
Database::limpiarResultados($conexion);

// Historial
$historial = [];
$resH = mysqli_query($conexion, "CALL sp_seguimiento_historial($id_pedido)");
if ($resH) {
    $historial = mysqli_fetch_all($resH, MYSQLI_ASSOC);
    mysqli_free_result($resH);
}
Database::limpiarResultados($conexion);

include_once '../includes/header.php';
include_once '../includes/navbar.php';
include_once '../includes/sidebar.php';
?>

<div class="content-wrapper" style="background-color: #F8FAFC;">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0" style="color: #1F2937; font-weight: bold;">
            Historial del Pedido #<?= str_pad($id_pedido, 5, '0', STR_PAD_LEFT) ?>
          </h1>
        </div>
        <div class="col-sm-6 text-right">
          <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Regresar</a>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if ($pedidoInfo): ?>
        <div class="card mb-4" style="border-left: 4px solid #8B5CF6;">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <strong>Cliente:</strong> <?= htmlspecialchars($pedidoInfo['cliente']) ?><br>
                <strong>Teléfono:</strong> <?= htmlspecialchars($pedidoInfo['telefono'] ?? 'N/A') ?>
              </div>
              <div class="col-md-4">
                <strong>Dirección:</strong> <?= htmlspecialchars($pedidoInfo['direccion'] ?? 'N/A') ?><br>
                <strong>Fecha Creación:</strong> <?= date('d/m/Y H:i', strtotime($pedidoInfo['fecha_pedido'])) ?>
              </div>
              <div class="col-md-4 text-md-right">
                <h4 style="color: #BE185D; font-weight: bold;">Total: Q <?= number_format($pedidoInfo['total'], 2) ?></h4>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Timeline -->
      <div class="row">
        <div class="col-md-12">
          <div class="timeline">
            <?php if (!empty($historial)): ?>
              <?php foreach ($historial as $h): ?>
                <div class="time-label">
                  <span style="background-color: #8B5CF6; color: #fff;">
                    <?= date('d/m/Y', strtotime($h['fecha_hora'])) ?>
                  </span>
                </div>
                <div>
                  <i class="fas fa-truck" style="background-color: #EC4899; color: #fff;"></i>
                  <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock mr-1"></i> <?= date('H:i', strtotime($h['fecha_hora'])) ?></span>
                    <h3 class="timeline-header" style="color: #1F2937;">
                      Estado: <strong><?= htmlspecialchars($h['estado']) ?></strong> (Etapa <?= $h['orden'] ?> de 6)
                    </h3>
                    <div class="timeline-body">
                      <?= htmlspecialchars($h['observacion']) ?>
                    </div>
                    <div class="timeline-footer text-muted" style="font-size: 0.85rem;">
                      Actualizado por: <em><?= htmlspecialchars($h['actualizado_por'] ?? 'Sistema / Administrador') ?></em>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <div>
                <i class="fas fa-check" style="background-color: #10B981; color: #fff;"></i>
              </div>
            <?php else: ?>
              <p class="text-muted">No existen registros de seguimiento.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include_once '../includes/footer.php'; ?>
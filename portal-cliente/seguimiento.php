
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
            COALESCE(e.nombre, 'Pendiente de actualización') AS estado,
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
                            <a href="index.php">Inicio</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="index.php">Portal del Cliente</a>
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
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>No. pedido</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Última actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr>
                                            <td>
                                                #<?= (int) $pedido['id_pedido'] ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($pedido['fecha_pedido']) ?>
                                            </td>
                                            <td>
                                                Q <?= number_format((float) $pedido['total'], 2) ?>
                                            </td>
                                            <td>
                                                <span class="badge text-bg-primary">
                                                    <?= htmlspecialchars($pedido['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $pedido['ultima_actualizacion']
                                                    ? htmlspecialchars($pedido['ultima_actualizacion'])
                                                    : 'Sin actualizaciones' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
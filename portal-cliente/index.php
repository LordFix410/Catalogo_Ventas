<?php

require_once '../includes/auth.php';

requiereRol(['Cliente']);

require_once '../config/database.php';

$conexion = Database::getConnection();

$productos = [];

$sql = "CALL sp_portal_productos()";
$resultado = mysqli_query($conexion, $sql);

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $productos[] = $fila;
    }

    mysqli_free_result($resultado);
    Database::limpiarResultados($conexion);
}
?>

<?php
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
                            <a href="../index.php">Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">
                            Portal del Cliente
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <div class="card-header">
    <h3 class="card-title">
        Catálogo de productos
    </h3>
</div>

<div class="card-body border-bottom">

    <div class="d-flex flex-wrap gap-2">

        <a href="catalogo.php" class="btn btn-primary">
            <i class="bi bi-grid"></i>
            Ver catálogo
        </a>

        <a href="pedido.php" class="btn btn-success">
            <i class="bi bi-cart-plus"></i>
            Realizar pedido
        </a>

        <a href="seguimiento.php" class="btn btn-secondary">
            <i class="bi bi-truck"></i>
            Seguimiento de pedidos
        </a>

    </div>

</div>

                <div class="card-body">
                    <div class="row">

                        <?php if (empty($productos)): ?>

                            <div class="col-12">
                                <div class="alert alert-info">
                                    No hay productos disponibles actualmente.
                                </div>
                            </div>

                        <?php else: ?>

                            <?php foreach ($productos as $producto): ?>

                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">

                                        <?php if (!empty($producto['imagen'])): ?>
                                            <img
                                                src="../<?php echo htmlspecialchars($producto['imagen']); ?>"
                                                class="card-img-top"
                                                alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                            >
                                        <?php endif; ?>

                                        <div class="card-body">

                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($producto['nombre']); ?>
                                            </h5>

                                            <p class="card-text">
                                                <?php echo htmlspecialchars($producto['descripcion']); ?>
                                            </p>

                                            <p class="fw-bold">
                                                Q <?php echo number_format($producto['precio'], 2); ?>
                                            </p>

                                            <span class="badge text-bg-success">
                                                Disponible
                                            </span>

                                        </div>

                                    </div>
                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

</main>

<?php
require_once '../includes/footer.php';
?>
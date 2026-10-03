<?php

require_once '../config/database.php';

$conexion = Database::getConnection();

$productos = [];

$sql = "CALL sp_portal_productos()";
$resultado = mysqli_query($conexion, $sql);

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $productos[] = $fila;
    }

    Database::limpiarResultados($conexion);
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
                    <h3 class="mb-0">Catálogo de Productos</h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="../index.php">Inicio</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="index.php">Portal del Cliente</a>
                        </li>
                        <li class="breadcrumb-item active">
                            Catálogo
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
                        Productos disponibles
                    </h3>
                </div>

                <div class="card-body">

                    <?php if (empty($productos)): ?>

                        <div class="alert alert-info">
                            No hay productos disponibles actualmente.
                        </div>

                    <?php else: ?>

                        <div class="row">

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
                                                Stock disponible
                                            </span>

                                        </div>

                                    </div>

                                </div>

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
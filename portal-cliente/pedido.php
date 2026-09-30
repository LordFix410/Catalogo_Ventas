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
                    <h3 class="mb-0">Realizar Pedido</h3>
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
                            Pedido
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
                        Seleccionar productos
                    </h3>
                </div>

                <div class="card-body">

                    <?php if (empty($productos)): ?>

                        <div class="alert alert-info">
                            No hay productos disponibles para realizar un pedido.
                        </div>

                    <?php else: ?>

                        <form method="POST" action="pedido.php">

                            <div class="row">

                                <?php foreach ($productos as $producto): ?>

                                    <div class="col-md-4 mb-4">

                                        <div class="card h-100">

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

                                                <label
                                                    for="cantidad_<?php echo $producto['id_producto']; ?>"
                                                    class="form-label"
                                                >
                                                    Cantidad
                                                </label>

                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    id="cantidad_<?php echo $producto['id_producto']; ?>"
                                                    name="cantidad[<?php echo $producto['id_producto']; ?>]"
                                                    min="0"
                                                    max="<?php echo $producto['stock']; ?>"
                                                    value="0"
                                                >

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <div class="mb-3">

                                <label for="observaciones" class="form-label">
                                    Observaciones
                                </label>

                                <textarea
                                    class="form-control"
                                    id="observaciones"
                                    name="observaciones"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Escriba alguna observación para su pedido..."
                                ></textarea>

                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i>
                                Crear pedido
                            </button>

                        </form>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</main>

<?php
require_once '../includes/footer.php';
?>


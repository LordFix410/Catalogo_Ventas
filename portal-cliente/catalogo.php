
<?php
require_once '../includes/auth.php';
requiereRol(['Cliente']);

require_once '../config/database.php';

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$conexion = Database::getConnection();
$productos = [];
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $idProducto = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);

        if ($idProducto === false || $idProducto === null || $idProducto <= 0 ||
            $cantidad === false || $cantidad === null || $cantidad <= 0) {
            $error = 'Selecciona un producto y una cantidad válida.';
        } else {
            $stmt = mysqli_prepare(
                $conexion,
                "SELECT nombre, stock
                 FROM productos
                 WHERE id_producto = ? AND activo = 1"
            );

            mysqli_stmt_bind_param($stmt, "i", $idProducto);
            mysqli_stmt_execute($stmt);
            $resultadoProducto = mysqli_stmt_get_result($stmt);
            $producto = mysqli_fetch_assoc($resultadoProducto);

            mysqli_free_result($resultadoProducto);
            mysqli_stmt_close($stmt);

            if (!$producto) {
                $error = 'El producto ya no está disponible.';
            } elseif ($producto['stock'] <= 0) {
                $error = 'Este producto no tiene existencias disponibles.';
            } else {
                $cantidadActual = (int) ($_SESSION['carrito'][$idProducto] ?? 0);
                $cantidadNueva = $cantidadActual + $cantidad;

                if ($cantidadNueva > (int) $producto['stock']) {
                    $error = 'Solo hay ' . (int) $producto['stock'] .
                        ' unidades disponibles de ' . $producto['nombre'] . '.';
                } else {
                    $_SESSION['carrito'][$idProducto] = $cantidadNueva;
                    $mensaje = 'Producto agregado al carrito.';
                }
            }
        }
    }

    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        $mensaje = 'Se ha vaciado el carrito.';
    }
}

$sql = "CALL sp_portal_productos()";
$resultado = mysqli_query($conexion, $sql);

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $productos[] = $fila;
    }

    mysqli_free_result($resultado);
    Database::limpiarResultados($conexion);
}

$totalArticulos = array_sum($_SESSION['carrito']);

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
                            <a href="index.php">Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Catálogo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="mb-0">
                    Artículos en el carrito:
                    <span class="badge text-bg-primary">
                        <?= (int) $totalArticulos ?>
                    </span>
                </p>

                <a href="pedido.php" class="btn btn-success">
                    <i class="bi bi-cart"></i>
                    Ver carrito
                </a>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensaje) ?>
                    <a href="pedido.php" class="alert-link">Ir al carrito</a>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Productos disponibles</h3>
                </div>

                <div class="card-body">
                    <?php if (empty($productos)): ?>
                        <div class="alert alert-info">
                            No hay productos disponibles actualmente.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($productos as $producto): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100">
                                        <?php if (!empty($producto['imagen'])): ?>
                                            <img
                                                src="../<?= htmlspecialchars($producto['imagen']) ?>"
                                                class="card-img-top"
                                                alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                                style="height: 200px; object-fit: contain;"
                                            >
                                        <?php endif; ?>

                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($producto['nombre']) ?>
                                            </h5>

                                            <p class="card-text text-muted">
                                                <?= htmlspecialchars($producto['descripcion'] ?? '') ?>
                                            </p>

                                            <h5 class="text-primary fw-bold">
                                                Q <?= number_format((float) $producto['precio'], 2) ?>
                                            </h5>

                                            <?php if ((int) $producto['stock'] > 0): ?>
                                                <p class="text-success">
                                                    Existencias: <?= (int) $producto['stock'] ?>
                                                </p>

                                                <form method="POST" action="catalogo.php" class="mt-auto">
                                                    <input type="hidden" name="accion" value="agregar">
                                                    <input
                                                        type="hidden"
                                                        name="id_producto"
                                                        value="<?= (int) $producto['id_producto'] ?>"
                                                    >

                                                    <label
                                                        for="cantidad_<?= (int) $producto['id_producto'] ?>"
                                                        class="form-label"
                                                    >
                                                        Cantidad
                                                    </label>

                                                    <input
                                                        type="number"
                                                        class="form-control mb-2"
                                                        id="cantidad_<?= (int) $producto['id_producto'] ?>"
                                                        name="cantidad"
                                                        min="1"
                                                        max="<?= (int) $producto['stock'] ?>"
                                                        value="1"
                                                        required
                                                    >

                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="bi bi-cart-plus"></i>
                                                        Agregar al carrito
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger mt-auto">
                                                    Agotado
                                                </span>
                                            <?php endif; ?>
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

<?php require_once '../includes/footer.php'; ?>


<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../includes/auth.php';
requiereRol(['Cliente']);

require_once '../config/database.php';

$conexion = Database::getConnection();

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if (empty($_SESSION['token_carrito'])) {
    $_SESSION['token_carrito'] = bin2hex(random_bytes(32));
}

$error = '';
$mensaje = '';
$observaciones = $_SESSION['observaciones_pedido'] ?? '';

if (isset($_GET['confirmado'])) {
    $mensaje = '¡Pedido #' . (int) $_GET['confirmado'] . ' confirmado correctamente!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['token_carrito'], $token)) {
        $error = 'La sesión del carrito ha cambiado. Recarga la página e inténtalo de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';

        try {
            if (isset($_POST['eliminar'])) {
                $idEliminar = filter_var($_POST['eliminar'], FILTER_VALIDATE_INT);

                if ($idEliminar === false || $idEliminar <= 0) {
                    throw new Exception('El producto seleccionado no es válido.');
                }

                unset($_SESSION['carrito'][$idEliminar]);
                unset($_SESSION['ajuste_stock'][$idEliminar]);
                unset($_SESSION['ajuste_stock']);
                $mensaje = 'Producto eliminado del carrito.';
            } elseif ($accion === 'vaciar') {
                $_SESSION['carrito'] = [];
                unset($_SESSION['ajuste_stock']);
                unset($_SESSION['observaciones_pedido']);
                $observaciones = '';
                $mensaje = 'Se ha vaciado el carrito.';
            } elseif ($accion === 'actualizar') {
                $cantidades = $_POST['cantidad'] ?? [];

                if (!is_array($cantidades)) {
                    throw new Exception('Las cantidades enviadas no son válidas.');
                }

                $nuevasCantidades = [];

                foreach ($cantidades as $idProducto => $cantidad) {
                    $idProducto = filter_var($idProducto, FILTER_VALIDATE_INT);
                    $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);

                    if ($idProducto === false || $idProducto <= 0 ||
                        $cantidad === false || $cantidad < 0) {
                        throw new Exception('Hay cantidades inválidas.');
                    }

                    if ($cantidad === 0) {
                        $nuevasCantidades[$idProducto] = 0;
                        continue;
                    }

                    $stmt = mysqli_prepare(
                        $conexion,
                        "SELECT stock, activo
                         FROM productos
                         WHERE id_producto = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $idProducto);
                    mysqli_stmt_execute($stmt);
                    $resultado = mysqli_stmt_get_result($stmt);
                    $producto = mysqli_fetch_assoc($resultado);

                    mysqli_free_result($resultado);
                    mysqli_stmt_close($stmt);

                    if (!$producto || !(bool) $producto['activo']) {
                        throw new Exception('Uno de los productos ya no está disponible.');
                    }

                    if ($cantidad > (int) $producto['stock']) {
                        throw new Exception(
                            'La cantidad solicitada supera las existencias actuales. Ajusta la cantidad para continuar.'
                        );
                    }

                    $nuevasCantidades[$idProducto] = $cantidad;
                }

                foreach ($nuevasCantidades as $idProducto => $cantidad) {
                    if ($cantidad === 0) {
                        unset($_SESSION['carrito'][$idProducto]);
                    } else {
                        $_SESSION['carrito'][$idProducto] = $cantidad;
                    }
                }

                unset($_SESSION['ajuste_stock']);
                $mensaje = 'Carrito actualizado correctamente.';
            } elseif ($accion === 'aceptar_ajuste') {
                $ajustes = $_SESSION['ajuste_stock'] ?? null;

                if (!is_array($ajustes)) {
                    throw new Exception('No hay ajustes pendientes. Vuelve a confirmar tu pedido.');
                }

                foreach ($ajustes as $idProducto => $cantidadDisponible) {
                    if (!isset($_SESSION['carrito'][$idProducto])) {
                        continue;
                    }

                    $cantidadDisponible = max(0, (int) $cantidadDisponible);

                    if ($cantidadDisponible === 0) {
                        unset($_SESSION['carrito'][$idProducto]);
                    } else {
                        $_SESSION['carrito'][$idProducto] = $cantidadDisponible;
                    }
                }

                unset($_SESSION['ajuste_stock']);
                $mensaje = 'Cantidades ajustadas. Revisa el carrito y confirma nuevamente tu pedido.';
            } elseif ($accion === 'confirmar') {
                $observaciones = trim($_POST['observaciones'] ?? '');

                if (strlen($observaciones) > 500) {
                    throw new Exception('Las observaciones no pueden superar 500 caracteres.');
                }

                $_SESSION['observaciones_pedido'] = $observaciones;

                if (empty($_SESSION['carrito'])) {
                    throw new Exception('Tu carrito está vacío.');
                }

                $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);

                if ($idUsuario <= 0) {
                    throw new Exception('No se pudo identificar al usuario.');
                }

                $stmtCliente = mysqli_prepare(
                    $conexion,
                    "SELECT id_cliente
                     FROM clientes
                     WHERE id_usuario = ? AND activo = 1
                     LIMIT 1"
                );

                mysqli_stmt_bind_param($stmtCliente, "i", $idUsuario);
                mysqli_stmt_execute($stmtCliente);
                $resultadoCliente = mysqli_stmt_get_result($stmtCliente);
                $cliente = mysqli_fetch_assoc($resultadoCliente);

                mysqli_free_result($resultadoCliente);
                mysqli_stmt_close($stmtCliente);

                if (!$cliente) {
                    throw new Exception('Tu cuenta no está vinculada a un cliente activo.');
                }

                $idCliente = (int) $cliente['id_cliente'];
                $cantidadesCarrito = $_SESSION['carrito'];
                ksort($cantidadesCarrito);

                mysqli_begin_transaction($conexion);
                $transaccionActiva = true;

                $ajustes = [];
                $productosConfirmados = [];

                foreach ($cantidadesCarrito as $idProducto => $cantidad) {
                    $idProducto = (int) $idProducto;
                    $cantidad = (int) $cantidad;

                    if ($idProducto <= 0 || $cantidad <= 0) {
                        throw new Exception('Hay productos o cantidades inválidas en el carrito.');
                    }

                    $stmtProducto = mysqli_prepare(
                        $conexion,
                        "SELECT id_producto, nombre, precio, stock, activo
                         FROM productos
                         WHERE id_producto = ?
                         FOR UPDATE"
                    );

                    mysqli_stmt_bind_param($stmtProducto, "i", $idProducto);
                    mysqli_stmt_execute($stmtProducto);
                    $resultadoProducto = mysqli_stmt_get_result($stmtProducto);
                    $producto = mysqli_fetch_assoc($resultadoProducto);

                    mysqli_free_result($resultadoProducto);
                    mysqli_stmt_close($stmtProducto);

                    $stockDisponible = 0;

                    if ($producto && (bool) $producto['activo']) {
                        $stockDisponible = (int) $producto['stock'];
                    }

                    if (!$producto || !(bool) $producto['activo'] ||
                        $cantidad > $stockDisponible) {
                        $ajustes[$idProducto] = $stockDisponible;
                    } else {
                        $productosConfirmados[$idProducto] = [
                            'cantidad' => $cantidad,
                            'precio' => (float) $producto['precio']
                        ];
                    }
                }

                if (!empty($ajustes)) {
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;

                    $_SESSION['ajuste_stock'] = $ajustes;
                    $error = 'Algunos productos ya no tienen suficientes existencias. Revisa las cantidades disponibles y acepta el ajuste si deseas continuar.';
                } else {
                    $stmtPedido = mysqli_prepare(
                        $conexion,
                        "CALL sp_pedido_insertar(?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmtPedido,
                        "is",
                        $idCliente,
                        $observaciones
                    );

                    mysqli_stmt_execute($stmtPedido);
                    $resultadoPedido = mysqli_stmt_get_result($stmtPedido);
                    $filaPedido = mysqli_fetch_assoc($resultadoPedido);

                    mysqli_free_result($resultadoPedido);
                    mysqli_stmt_close($stmtPedido);
                    Database::limpiarResultados($conexion);

                    if (!$filaPedido || empty($filaPedido['id_pedido'])) {
                        throw new Exception('No se pudo registrar el pedido.');
                    }

                    $idPedido = (int) $filaPedido['id_pedido'];

                    foreach ($productosConfirmados as $idProducto => $item) {
                        $cantidad = (int) $item['cantidad'];

                        $sqlDetalle = "CALL sp_detalle_insertar(
                            " . $idPedido . ",
                            " . (int) $idProducto . ",
                            " . $cantidad . "
                        )";

                        $resultadoDetalle = mysqli_query($conexion, $sqlDetalle);

                        if ($resultadoDetalle instanceof mysqli_result) {
                            mysqli_free_result($resultadoDetalle);
                        }

                        Database::limpiarResultados($conexion);
                    }

                    mysqli_commit($conexion);
                    $transaccionActiva = false;

                    $_SESSION['carrito'] = [];
                    unset($_SESSION['ajuste_stock']);
                    unset($_SESSION['observaciones_pedido']);
                    $_SESSION['token_carrito'] = bin2hex(random_bytes(32));

                    header('Location: pedido.php?confirmado=' . $idPedido);
                    exit;
                }
            }
        } catch (Throwable $e) {
            if (isset($transaccionActiva) && $transaccionActiva) {
                mysqli_rollback($conexion);
            }

            error_log('Error al procesar el carrito: ' . $e->getMessage());
            $error = 'No se pudo completar la operación. Verifica las existencias e inténtalo nuevamente.';
        }
    }
}

$itemsCarrito = [];
$total = 0;
$totalArticulos = 0;

foreach ($_SESSION['carrito'] as $idProducto => $cantidad) {
    $idProducto = (int) $idProducto;
    $cantidad = (int) $cantidad;

    if ($idProducto <= 0 || $cantidad <= 0) {
        unset($_SESSION['carrito'][$idProducto]);
        continue;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "SELECT id_producto, nombre, descripcion, precio, stock, imagen, activo
         FROM productos
         WHERE id_producto = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $idProducto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $producto = mysqli_fetch_assoc($resultado);

    mysqli_free_result($resultado);
    mysqli_stmt_close($stmt);

    if (!$producto) {
        $producto = [
            'id_producto' => $idProducto,
            'nombre' => 'Producto no disponible',
            'descripcion' => '',
            'precio' => 0,
            'stock' => 0,
            'imagen' => null,
            'activo' => 0
        ];
    }

    $producto['cantidad'] = $cantidad;
    $producto['subtotal'] = (float) $producto['precio'] * $cantidad;
    $producto['disponible'] = (bool) $producto['activo'] &&
        (int) $producto['stock'] >= $cantidad;

    $itemsCarrito[] = $producto;

    if ((bool) $producto['activo']) {
        $total += $producto['subtotal'];
        $totalArticulos += $cantidad;
    }
}

$ajustesStock = $_SESSION['ajuste_stock'] ?? [];

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Mi carrito de compras</h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="index.php">Inicio</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="catalogo.php">Catálogo</a>
                        </li>
                        <li class="breadcrumb-item active">Carrito</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensaje) ?>

                    <?php if (isset($_GET['confirmado'])): ?>
                        <div class="mt-2">
                            <a href="seguimiento.php" class="alert-link">
                                Consultar seguimiento del pedido
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($ajustesStock)): ?>
                <div class="card border-warning mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Cambios en las existencias</h3>
                    </div>

                    <div class="card-body">
                        <p>
                            Estas son las cantidades disponibles que encontramos al intentar confirmar tu pedido.
                            Si aceptas, ajustaremos el carrito. Después podrás revisar el total y confirmar nuevamente.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Solicitado</th>
                                        <th>Disponible ahora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemsCarrito as $item): ?>
                                        <?php if (array_key_exists($item['id_producto'], $ajustesStock)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['nombre']) ?></td>
                                                <td><?= (int) $item['cantidad'] ?></td>
                                                <td>
                                                    <?= (int) $ajustesStock[$item['id_producto']] ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="pedido.php">
                            <input
                                type="hidden"
                                name="token"
                                value="<?= htmlspecialchars($_SESSION['token_carrito']) ?>"
                            >
                            <button
                                type="submit"
                                name="accion"
                                value="aceptar_ajuste"
                                class="btn btn-warning"
                            >
                                Aceptar cantidades disponibles
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title">Resumen de compra</h3>
                    <a href="catalogo.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Seguir comprando
                    </a>
                </div>

                <div class="card-body">
                    <?php if (empty($itemsCarrito)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x display-3 text-muted"></i>
                            <h4 class="mt-3">Tu carrito está vacío</h4>
                            <p class="text-muted">
                                Explora el catálogo y agrega los productos que necesitas.
                            </p>
                            <a href="catalogo.php" class="btn btn-primary">
                                Ver catálogo
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="pedido.php">
                            <input
                                type="hidden"
                                name="token"
                                value="<?= htmlspecialchars($_SESSION['token_carrito']) ?>"
                            >

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Precio unitario</th>
                                            <th style="min-width: 130px;">Cantidad</th>
                                            <th>Subtotal</th>
                                            <th>Existencias</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($itemsCarrito as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= htmlspecialchars($item['nombre']) ?>
                                                    </strong>
                                                </td>

                                                <td>
                                                    Q <?= number_format((float) $item['precio'], 2) ?>
                                                </td>

                                                <td>
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        name="cantidad[<?= (int) $item['id_producto'] ?>]"
                                                        value="<?= (int) $item['cantidad'] ?>"
                                                        min="0"
                                                        max="<?= max(0, (int) $item['stock']) ?>"
                                                        required
                                                    >
                                                    <small class="text-muted">
                                                        0 para quitarlo al actualizar.
                                                    </small>
                                                </td>

                                                <td>
                                                    Q <?= number_format((float) $item['subtotal'], 2) ?>
                                                </td>

                                                <td>
                                                    <?php if (!(bool) $item['activo']): ?>
                                                        <span class="badge text-bg-danger">No disponible</span>
                                                    <?php else: ?>
                                                        <?= (int) $item['stock'] ?>

                                                        <?php if (!$item['disponible']): ?>
                                                            <div class="text-danger small">
                                                                Ajusta la cantidad disponible.
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <button
                                                        type="submit"
                                                        name="eliminar"
                                                        value="<?= (int) $item['id_producto'] ?>"
                                                        class="btn btn-outline-danger btn-sm"
                                                        formnovalidate
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                        Quitar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <button
                                type="submit"
                                name="accion"
                                value="actualizar"
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-arrow-repeat"></i>
                                Actualizar cantidades
                            </button>

                            <button
                                type="submit"
                                name="accion"
                                value="vaciar"
                                class="btn btn-outline-danger"
                                formnovalidate
                                onclick="return confirm('¿Seguro que deseas vaciar el carrito?');"
                            >
                                <i class="bi bi-trash"></i>
                                Vaciar carrito
                            </button>
                        </form>

                        <div class="row justify-content-end mt-4">
                            <div class="col-md-6 col-lg-5">
                                <div class="card bg-body-tertiary">
                                    <div class="card-body">
                                        <h5>Totales</h5>

                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total de artículos:</span>
                                            <strong><?= (int) $totalArticulos ?></strong>
                                        </div>

                                        <div class="d-flex justify-content-between border-top pt-3">
                                            <span>Total estimado:</span>
                                            <h4 class="text-primary">
                                                Q <?= number_format($total, 2) ?>
                                            </h4>
                                        </div>

                                        <p class="small text-muted">
                                            Los precios y las existencias se verifican nuevamente al confirmar.
                                        </p>

                                        <form method="POST" action="pedido.php">
                                            <input
                                                type="hidden"
                                                name="token"
                                                value="<?= htmlspecialchars($_SESSION['token_carrito']) ?>"
                                            >

                                            <div class="mb-3">
                                                <label for="observaciones" class="form-label">
                                                    Observaciones del pedido
                                                </label>
                                                <textarea
                                                    name="observaciones"
                                                    id="observaciones"
                                                    class="form-control"
                                                    rows="3"
                                                    maxlength="500"
                                                ><?= htmlspecialchars($observaciones) ?></textarea>
                                            </div>

                                            <button
                                                type="submit"
                                                name="accion"
                                                value="confirmar"
                                                class="btn btn-success w-100"
                                                <?= !empty($ajustesStock) ? 'disabled' : '' ?>
                                                onclick="return confirm('¿Deseas confirmar este pedido?');"
                                            >
                                                <i class="bi bi-check-circle"></i>
                                                Confirmar compra
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

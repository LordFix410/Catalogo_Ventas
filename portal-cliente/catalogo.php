
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

$categoriasCatalogo = array_values(array_unique(array_filter(array_column($productos, 'categoria'))));
sort($categoriasCatalogo, SORT_NATURAL | SORT_FLAG_CASE);

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
                            <a href="/portal-cliente/catalogo.php">Inicio</a>
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
                        <div class="catalogo-filtros row g-2 mb-4">
                            <div class="col-12 col-md-5">
                                <label class="form-label" for="buscarProducto">Buscar producto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="search" id="buscarProducto" class="form-control" placeholder="Nombre o descripción" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="filtrarCategoria">Categoría</label>
                                <select id="filtrarCategoria" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($categoriasCatalogo as $categoria): ?>
                                        <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label" for="filtrarDisponibilidad">Disponibilidad</label>
                                <select id="filtrarDisponibilidad" class="form-select">
                                    <option value="">Todas</option>
                                    <option value="disponible">Disponibles</option>
                                    <option value="agotado">Agotados</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label" for="ordenarProductos">Ordenar</label>
                                <select id="ordenarProductos" class="form-select">
                                    <option value="nombre">Nombre</option>
                                    <option value="precio-asc">Menor precio</option>
                                    <option value="precio-desc">Mayor precio</option>
                                </select>
                            </div>
                            <div class="col-12 small text-muted" id="resultadoCatalogo" aria-live="polite"></div>
                        </div>

                        <div class="row catalogo-grid" id="listaProductos">
                            <?php foreach ($productos as $producto): ?>
                                <div
                                    class="col-md-4 col-lg-3 mb-4 catalogo-item"
                                    data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-descripcion="<?= htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-precio="<?= (float) $producto['precio'] ?>"
                                    data-stock="<?= (int) $producto['stock'] ?>"
                                >
                                    <div class="card h-100 catalogo-producto">
                                        <div class="producto-imagen bg-body-tertiary d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <?php if (!empty($producto['imagen'])): ?>
                                                <img
                                                    src="/<?= htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                                    class="card-img-top producto-imagen-img"
                                                    alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                    style="height: 200px; object-fit: contain;"
                                                >
                                            <?php else: ?>
                                                <i class="bi bi-image text-body-secondary" style="font-size: 3rem;" aria-label="Sin imagen"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body d-flex flex-column">
                                            <span class="badge rounded-pill text-bg-light align-self-start mb-2"><?= htmlspecialchars($producto['categoria'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></span>
                                            <h5 class="card-title">
                                                <?= htmlspecialchars($producto['nombre']) ?>
                                            </h5>

                                            <p class="card-text text-muted producto-descripcion">
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
                        <div class="alert alert-light text-center d-none" id="catalogoSinResultados">
                            No encontramos productos con esos filtros. Prueba con otra búsqueda.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lista = document.getElementById('listaProductos');
    if (!lista) return;

    const tarjetas = Array.from(lista.querySelectorAll('.catalogo-item'));
    const buscar = document.getElementById('buscarProducto');
    const categoria = document.getElementById('filtrarCategoria');
    const disponibilidad = document.getElementById('filtrarDisponibilidad');
    const ordenar = document.getElementById('ordenarProductos');
    const resultado = document.getElementById('resultadoCatalogo');
    const vacio = document.getElementById('catalogoSinResultados');

    const normalizar = valor => valor.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    function actualizarCatalogo() {
        const termino = normalizar(buscar.value.trim());
        const categoriaElegida = normalizar(categoria.value);
        const stockFiltro = disponibilidad.value;
        const visibles = tarjetas.filter(tarjeta => {
            const coincideTexto = normalizar(tarjeta.dataset.nombre + ' ' + tarjeta.dataset.descripcion).includes(termino);
            const coincideCategoria = !categoriaElegida || normalizar(tarjeta.dataset.categoria) === categoriaElegida;
            const stock = Number(tarjeta.dataset.stock);
            const coincideStock = !stockFiltro || (stockFiltro === 'disponible' ? stock > 0 : stock <= 0);
            tarjeta.hidden = !(coincideTexto && coincideCategoria && coincideStock);
            return !tarjeta.hidden;
        });

        visibles.sort((a, b) => {
            if (ordenar.value === 'precio-asc') return Number(a.dataset.precio) - Number(b.dataset.precio);
            if (ordenar.value === 'precio-desc') return Number(b.dataset.precio) - Number(a.dataset.precio);
            return a.dataset.nombre.localeCompare(b.dataset.nombre, 'es', { sensitivity: 'base' });
        }).forEach(tarjeta => lista.appendChild(tarjeta));

        resultado.textContent = `Mostrando ${visibles.length} de ${tarjetas.length} productos`;
        vacio.classList.toggle('d-none', visibles.length > 0);
    }

    [buscar, categoria, disponibilidad, ordenar].forEach(control => {
        control.addEventListener(control === buscar ? 'input' : 'change', actualizarCatalogo);
    });
    actualizarCatalogo();
});
</script>

<?php require_once '../includes/footer.php'; ?>


<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador', 'Empleado']);

require_once __DIR__ . '/../config/database.php';

$conexion = Database::getConnection();

$sql = "SELECT
            p.id_producto,
            p.nombre,
            p.descripcion,
            p.precio,
            p.stock,
            p.imagen,
            p.activo,
            c.nombre AS categoria,
            (
                SELECT ip.nombre_archivo
                FROM imagenes_producto ip
                WHERE ip.id_producto = p.id_producto
                ORDER BY ip.es_principal DESC, ip.orden ASC, ip.id_imagen ASC
                LIMIT 1
            ) AS imagen_principal
        FROM productos p
        INNER JOIN categorias c ON c.id_categoria = p.id_categoria
        ORDER BY p.id_producto DESC";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die('Error al consultar productos: ' . htmlspecialchars(mysqli_error($conexion)));
}

$titulo = 'Productos';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="page-eyebrow">CATÁLOGO</span>
                    <h1 class="page-title">Productos</h1>
                    <p class="page-description">
                        Administra productos, precios, existencias e imágenes.
                    </p>
                </div>

                <a href="/productos/crear.php" class="btn btn-chiquis">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nuevo producto
                </a>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resultado) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
                                            <h5>No hay productos registrados</h5>
                                            <p class="text-secondary mb-3">
                                                Agrega tu primer producto para comenzar el catálogo.
                                            </p>
                                            <a href="/productos/crear.php" class="btn btn-chiquis">
                                                Crear producto
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($producto = mysqli_fetch_assoc($resultado)): ?>
                                        <?php
                                        $imagen = $producto['imagen_principal']
                                            ?: $producto['imagen'];
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($imagen)): ?>
                                                    <img
                                                        src="/<?= htmlspecialchars(ltrim($imagen, '/'), ENT_QUOTES, 'UTF-8') ?>"
                                                        alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                        style="width:65px;height:65px;object-fit:contain"
                                                        class="rounded border p-1"
                                                    >
                                                <?php else: ?>
                                                    <div class="rounded border d-flex align-items-center justify-content-center text-secondary"
                                                         style="width:65px;height:65px">
                                                        <i class="bi bi-image fs-3"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                                <?php if (!empty($producto['descripcion'])): ?>
                                                    <div class="small text-secondary">
                                                        <?= htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                Q <?= number_format((float)$producto['precio'], 2) ?>
                                            </td>
                                            <td><?= (int)$producto['stock'] ?></td>
                                            <td>
                                                <?php if ((int)$producto['activo'] === 1): ?>
                                                    <span class="badge text-bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
mysqli_free_result($resultado);
Database::limpiarResultados($conexion);
include __DIR__ . '/../includes/footer.php';
?>

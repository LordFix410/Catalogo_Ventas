
<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador']);

require_once __DIR__ . '/../config/database.php';

$conexion = Database::getConnection();

$sql = "SELECT id_categoria, nombre, descripcion, activo
        FROM categorias
        ORDER BY nombre ASC";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die('No se pudieron consultar las categorías.');
}

$titulo = 'Categorías';
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
                    <h1 class="page-title">Categorías</h1>
                    <p class="page-description">
                        Organiza los productos de Variedades Chiquis.
                    </p>
                </div>

                <a href="/categorias/crear.php" class="btn btn-chiquis">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nueva categoría
                </a>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <?php if (isset($_GET['creada'])): ?>
                <div class="alert alert-success">
                    Categoría registrada correctamente.
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resultado) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="bi bi-tags fs-1 d-block mb-3 text-secondary"></i>
                                            <h5>No hay categorías registradas</h5>
                                            <p class="text-secondary">
                                                Crea una categoría para comenzar a registrar productos.
                                            </p>
                                            <a href="/categorias/crear.php" class="btn btn-chiquis">
                                                Crear categoría
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($categoria = mysqli_fetch_assoc($resultado)): ?>
                                        <tr>
                                            <td><?= (int)$categoria['id_categoria'] ?></td>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <?php if ((int)$categoria['activo'] === 1): ?>
                                                    <span class="badge text-bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-secondary">Inactiva</span>
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

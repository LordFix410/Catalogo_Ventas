
<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador']);

require_once __DIR__ . '/../config/database.php';

$conexion = Database::getConnection();
$nombre = '';
$descripcion = '';
$error = '';
$exito = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
    } elseif ($nombre === '' || strlen($nombre) > 80) {
        $error = 'El nombre es obligatorio y debe tener como máximo 80 caracteres.';
    } elseif (strlen($descripcion) > 200) {
        $error = 'La descripción debe tener como máximo 200 caracteres.';
    } else {
        $stmt = mysqli_prepare(
            $conexion,
            'INSERT INTO categorias (nombre, descripcion, activo) VALUES (?, ?, 1)'
        );

        mysqli_stmt_bind_param($stmt, 'ss', $nombre, $descripcion);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: /categorias/index.php?creada=1');
            exit;
        }

        if (mysqli_errno($conexion) === 1062) {
            $error = 'Ya existe una categoría con ese nombre.';
        } else {
            $error = 'No se pudo guardar la categoría. Revisa los datos e inténtalo nuevamente.';
        }

        mysqli_stmt_close($stmt);
    }
}

$titulo = 'Nueva categoría';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <span class="page-eyebrow">CATÁLOGO</span>
            <h1 class="page-title">Nueva categoría</h1>
            <p class="page-description">Organiza los productos de Variedades Chiquis.</p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" id="nombre" name="nombre"
                                   class="form-control" maxlength="80" required
                                   value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion"
                                      class="form-control" maxlength="200" rows="3"><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-chiquis">
                            <i class="bi bi-check-circle me-2"></i>Guardar categoría
                        </button>

                        <a href="/categorias/index.php" class="btn btn-outline-secondary ms-2">
                            Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

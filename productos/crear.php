
<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador', 'Empleado']);

require_once __DIR__ . '/../config/database.php';

$conexion = Database::getConnection();

$titulo = 'Registrar producto';
$errores = [];
$mensaje = '';

$nombre = '';
$descripcion = '';
$idCategoria = 0;
$precio = '';
$stock = '0';

if (empty($_SESSION['csrf_productos'])) {
    $_SESSION['csrf_productos'] = bin2hex(random_bytes(32));
}

/*
 * Obtener categorÃ­as activas.
 */
$categorias = [];

$resultadoCategorias = mysqli_query(
    $conexion,
    "SELECT id_categoria, nombre
     FROM categorias
     WHERE activo = 1
     ORDER BY nombre"
);

while ($categoria = mysqli_fetch_assoc($resultadoCategorias)) {
    $categorias[] = $categoria;
}

mysqli_free_result($resultadoCategorias);

/*
 * Registrar producto e imÃ¡genes.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $idCategoria = filter_var(
        $_POST['id_categoria'] ?? null,
        FILTER_VALIDATE_INT
    );
    $precio = trim($_POST['precio'] ?? '');
    $stock = trim($_POST['stock'] ?? '0');

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_productos'], $token)
    ) {
        $errores[] = 'La sesiÃ³n del formulario venciÃ³. RecargÃ¡ la pÃ¡gina.';
    }

    if ($nombre === '' || mb_strlen($nombre) > 120) {
        $errores[] = 'El nombre es obligatorio y no debe superar 120 caracteres.';
    }

    if (mb_strlen($descripcion) > 5000) {
        $errores[] = 'La descripciÃ³n es demasiado larga.';
    }

    if (
        $idCategoria === false ||
        $idCategoria === null ||
        $idCategoria <= 0
    ) {
        $errores[] = 'SeleccionÃ¡ una categorÃ­a vÃ¡lida.';
    }

    if (
        $precio === '' ||
        !is_numeric($precio) ||
        !is_finite((float) $precio) ||
        (float) $precio < 0 ||
        (float) $precio > 99999999.99
    ) {
        $errores[] = 'IngresÃ¡ un precio vÃ¡lido.';
    }

    $stockValidado = filter_var($stock, FILTER_VALIDATE_INT);

    if (
        $stockValidado === false ||
        $stockValidado < 0
    ) {
        $errores[] = 'El stock debe ser un nÃºmero entero igual o mayor que cero.';
    }

    /*
     * Confirmar que la categorÃ­a existe y estÃ¡ activa.
     */
    if (
        $idCategoria !== false &&
        $idCategoria !== null &&
        $idCategoria > 0
    ) {
        $stmtCategoria = mysqli_prepare(
            $conexion,
            "SELECT id_categoria
             FROM categorias
             WHERE id_categoria = ? AND activo = 1"
        );

        mysqli_stmt_bind_param($stmtCategoria, 'i', $idCategoria);
        mysqli_stmt_execute($stmtCategoria);
        mysqli_stmt_store_result($stmtCategoria);

        if (mysqli_stmt_num_rows($stmtCategoria) === 0) {
            $errores[] = 'La categorÃ­a seleccionada no existe o estÃ¡ inactiva.';
        }

        mysqli_stmt_close($stmtCategoria);
    }

    /*
     * Validar las fotografÃ­as antes de guardarlas.
     * Formatos permitidos: JPG, PNG y WEBP.
     * MÃ¡ximo: 5 imÃ¡genes de 5 MB cada una.
     */
    $imagenesValidas = [];
    $archivos = $_FILES['imagenes'] ?? null;

    if (
        !$archivos ||
        !isset($archivos['name']) ||
        !is_array($archivos['name'])
    ) {
        $errores[] = 'SeleccionÃ¡ al menos una imagen.';
    } else {
        $cantidadSeleccionada = 0;

        foreach ($archivos['error'] as $errorArchivo) {
            if ($errorArchivo !== UPLOAD_ERR_NO_FILE) {
                $cantidadSeleccionada++;
            }
        }

        if ($cantidadSeleccionada === 0) {
            $errores[] = 'SeleccionÃ¡ al menos una imagen.';
        }

        if ($cantidadSeleccionada > 5) {
            $errores[] = 'PodÃ©s subir un mÃ¡ximo de 5 imÃ¡genes por producto.';
        }

        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($archivos['name'] as $i => $nombreOriginal) {
            $errorArchivo = $archivos['error'][$i]
                ?? UPLOAD_ERR_NO_FILE;

            if ($errorArchivo === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($errorArchivo !== UPLOAD_ERR_OK) {
                $errores[] = 'Una imagen no se pudo cargar correctamente.';
                continue;
            }

            $temporal = $archivos['tmp_name'][$i] ?? '';
            $tamano = (int) ($archivos['size'][$i] ?? 0);

            if ($tamano <= 0 || $tamano > 5 * 1024 * 1024) {
                $errores[] = 'Cada imagen debe pesar como mÃ¡ximo 5 MB.';
                continue;
            }

            $mime = $finfo->file($temporal);

            if (!isset($tiposPermitidos[$mime])) {
                $errores[] = 'Solo se permiten imÃ¡genes JPG, PNG o WEBP.';
                continue;
            }

            if (@getimagesize($temporal) === false) {
                $errores[] = 'Uno de los archivos no es una imagen vÃ¡lida.';
                continue;
            }

            $nombreArchivo = bin2hex(random_bytes(16))
                . '.' . $tiposPermitidos[$mime];

            $imagenesValidas[] = [
                'temporal' => $temporal,
                'nombre' => $nombreArchivo,
                'ruta' => 'assets/img/productos/' . $nombreArchivo,
            ];
        }
    }

    /*
     * Guardar el producto y sus imÃ¡genes en una transacciÃ³n.
     */
    if (empty($errores)) {
        $directorioImagenes = __DIR__
            . '/../assets/img/productos';

        $archivosMovidos = [];
        $transaccionIniciada = false;

        try {

if (!is_dir($directorioImagenes)) {
    if (!mkdir($directorioImagenes, 0755, true) && !is_dir($directorioImagenes)) {
        throw new RuntimeException(
            'No se pudo crear la carpeta de imÃ¡genes.'
        );
    }
}


            mysqli_begin_transaction($conexion);
            $transaccionIniciada = true;

            $imagenPrincipal = $imagenesValidas[0]['ruta'];

            $stmtProducto = mysqli_prepare(
                $conexion,
                "INSERT INTO productos
                    (id_categoria, nombre, descripcion, precio, stock, imagen, activo)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );

            $precioDecimal = (float) $precio;
            $stockEntero = (int) $stockValidado;

            mysqli_stmt_bind_param(
                $stmtProducto,
                'issdis',
                $idCategoria,
                $nombre,
                $descripcion,
                $precioDecimal,
                $stockEntero,
                $imagenPrincipal
            );

            mysqli_stmt_execute($stmtProducto);
            $idProducto = mysqli_insert_id($conexion);

            mysqli_stmt_close($stmtProducto);

            $stmtImagen = mysqli_prepare(
                $conexion,
                "INSERT INTO imagenes_producto
                    (id_producto, nombre_archivo, es_principal, orden)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($imagenesValidas as $indice => $imagen) {
                $destino = $directorioImagenes
                    . '/' . $imagen['nombre'];

                if (!move_uploaded_file($imagen['temporal'], $destino)) {
                    throw new RuntimeException(
                        'No se pudo guardar una de las imÃ¡genes.'
                    );
                }

                $archivosMovidos[] = $destino;

                $esPrincipal = ($indice === 0) ? 1 : 0;
                $orden = $indice + 1;
                $rutaImagen = $imagen['ruta'];

                mysqli_stmt_bind_param(
                    $stmtImagen,
                    'isii',
                    $idProducto,
                    $rutaImagen,
                    $esPrincipal,
                    $orden
                );

                mysqli_stmt_execute($stmtImagen);
            }

            mysqli_stmt_close($stmtImagen);

            mysqli_commit($conexion);
            $transaccionIniciada = false;

            $mensaje = 'Producto registrado correctamente con '
                . count($imagenesValidas) . ' imagen(es).';

            $nombre = '';
            $descripcion = '';
            $idCategoria = 0;
            $precio = '';
            $stock = '0';

            $_SESSION['csrf_productos'] = bin2hex(random_bytes(32));

        } catch (Throwable $e) {
            if ($transaccionIniciada) {
                mysqli_rollback($conexion);
            }

            foreach ($archivosMovidos as $archivoMovido) {
                if (is_file($archivoMovido)) {
                    @unlink($archivoMovido);
                }
            }

            error_log('Error al registrar producto: ' . $e->getMessage());
$errores[] = 'Detalle tÃ©cnico: ' . $e->getMessage(); }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h1 class="mb-0">Registrar producto</h1>
            <p class="text-body-secondary mb-0">
                AgregÃ¡ los datos y las fotografÃ­as del producto.
            </p>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php foreach ($errores as $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endforeach; ?>

            <?php if (empty($categorias)): ?>
                <div class="alert alert-warning">
                    <strong>TodavÃ­a no hay categorÃ­as.</strong>
                    Primero debemos registrar al menos una categorÃ­a para poder crear productos.
                    No es necesario modificar esta pÃ¡gina; lo prepararemos en el siguiente paso.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Datos del producto</h2>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="card-body">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_productos'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <div class="row g-3">

                            <div class="col-md-8">
                                <label for="nombre" class="form-label">
                                    Nombre del producto
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="nombre"
                                    name="nombre"
                                    maxlength="120"
                                    value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="id_categoria" class="form-label">
                                    CategorÃ­a
                                </label>
                                <select
                                    class="form-select"
                                    id="id_categoria"
                                    name="id_categoria"
                                    required
                                    <?= empty($categorias) ? 'disabled' : '' ?>
                                >
                                    <option value="">SeleccionÃ¡ una categorÃ­a</option>

                                    <?php foreach ($categorias as $categoria): ?>
                                        <option
                                            value="<?= (int) $categoria['id_categoria'] ?>"
                                            <?= (int) $idCategoria === (int) $categoria['id_categoria'] ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="descripcion" class="form-label">
                                    DescripciÃ³n
                                </label>
                                <textarea
                                    class="form-control"
                                    id="descripcion"
                                    name="descripcion"
                                    rows="4"
                                    maxlength="5000"
                                ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="precio" class="form-label">
                                    Precio (Q)
                                </label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="precio"
                                    name="precio"
                                    min="0"
                                    max="99999999.99"
                                    step="0.01"
                                    value="<?= htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="stock" class="form-label">
                                    Existencias
                                </label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="stock"
                                    name="stock"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars((string) $stock, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>

                            <div class="col-12">
                                <label for="imagenes" class="form-label">
                                    FotografÃ­as del producto
                                </label>
                                <input
                                    type="file"
                                    class="form-control"
                                    id="imagenes"
                                    name="imagenes[]"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    multiple
                                    required
                                >
                                <div class="form-text">
                                    SeleccionÃ¡ hasta 5 imÃ¡genes JPG, PNG o WEBP.
                                    Cada archivo debe pesar como mÃ¡ximo 5 MB.
                                    La primera imagen serÃ¡ la principal del catÃ¡logo.
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= empty($categorias) ? 'disabled' : '' ?>
                        >
                            <i class="bi bi-save me-1"></i>
                            Guardar producto
                        </button>

                        <a href="/productos/index.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

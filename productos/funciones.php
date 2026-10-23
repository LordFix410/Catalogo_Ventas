<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Administrador']);
require_once __DIR__ . '/../config/database.php';

function productosEjecutar(mysqli $conexion, string $sql, string $tipos = '', array $valores = []): array
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la operación.');
    }
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $codigo = mysqli_stmt_errno($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($codigo === 1062 ? 'Ya existe un registro con esos datos.' : 'No se pudo completar la operación.');
    }
    $resultado = mysqli_stmt_get_result($stmt);
    $filas = $resultado ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];
    if ($resultado) mysqli_free_result($resultado);
    mysqli_stmt_close($stmt);
    Database::limpiarResultados($conexion);
    return $filas;
}

function productosEsc(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function productosGuardarImagen(array $archivo, ?string $actual = null): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $actual;
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo subir la imagen. Intenta con un archivo más pequeño.');
    if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('La imagen no puede superar los 5 MB.');
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info) throw new RuntimeException('El archivo seleccionado no es una imagen válida.');
    $mime = $info['mime'] ?? '';
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($extensiones[$mime])) throw new RuntimeException('Formato no admitido. Usa JPG, PNG, WEBP o GIF.');
    $directorio = __DIR__ . '/../assets/productos';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
    $nombre = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre)) throw new RuntimeException('No se pudo guardar la imagen.');
    return 'assets/productos/' . $nombre;
}

function productosBorrarImagen(?string $ruta): void
{
    if (!$ruta || !preg_match('~^assets/productos/[a-f0-9]{32}\.(?:jpg|png|webp|gif)$~', $ruta)) return;
    $archivo = __DIR__ . '/../' . $ruta;
    if (is_file($archivo)) @unlink($archivo);
}

function productosCategorias(mysqli $conexion): array
{
    return productosEjecutar($conexion, 'CALL sp_categoria_listar()');
}

function productosEncabezado(string $titulo, string $activo): void
{
    $GLOBALS['titulo'] = $titulo;
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    require __DIR__ . '/../includes/sidebar.php';
    ?>
    <main class="app-main">
      <div class="app-content-header"><div class="container-fluid"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><span class="page-eyebrow">ADMINISTRACIÓN</span><h1 class="page-title"><?= productosEsc($titulo) ?></h1><p class="page-description">Administra productos, precios, existencias e imágenes del catálogo.</p></div>
        <a class="btn btn-outline-secondary" href="/portal-cliente/catalogo.php"><i class="bi bi-eye me-1"></i> Ver catálogo</a>
      </div></div></div>
      <div class="app-content"><div class="container-fluid">
    <?php
}

function productosPie(): void
{
    ?>
      </div></div>
    </main>
    <?php require __DIR__ . '/../includes/footer.php';
}

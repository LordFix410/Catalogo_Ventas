<?php

session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'Cliente') {
        header('Location: /portal-cliente/index.php');
    } else {
        header('Location: /index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Ingresa tu correo y contraseña.';
    } else {

        try {
            $conexion = Database::getConnection();

            $stmt = mysqli_prepare($conexion, "CALL sp_usuario_buscar(?)");
            mysqli_stmt_bind_param($stmt, "s", $correo);
            mysqli_stmt_execute($stmt);

            $resultado = mysqli_stmt_get_result($stmt);
            $usuario = mysqli_fetch_assoc($resultado);

            mysqli_free_result($resultado);
            mysqli_stmt_close($stmt);

            Database::limpiarResultados($conexion);

            if (
                $usuario &&
                $usuario['activo'] &&
                password_verify($password, $usuario['password'])
            ) {

                session_regenerate_id(true);

                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['rol'] = $usuario['rol'];

                if ($usuario['rol'] === 'Cliente') {

                    $stmtCliente = mysqli_prepare(
                        $conexion,
                        "CALL sp_cliente_por_usuario(?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmtCliente,
                        "i",
                        $_SESSION['id_usuario']
                    );

                    mysqli_stmt_execute($stmtCliente);

                    $resultadoCliente = mysqli_stmt_get_result($stmtCliente);
                    $cliente = mysqli_fetch_assoc($resultadoCliente);

                    mysqli_free_result($resultadoCliente);
                    mysqli_stmt_close($stmtCliente);

                    Database::limpiarResultados($conexion);

                    if ($cliente) {
                        $_SESSION['id_cliente'] = $cliente['id_cliente'];
                    } else {
                        $_SESSION['id_cliente'] = null;
                    }

                    header('Location: /portal-cliente/index.php');
                    exit;
                }

                header('Location: /index.php');
                exit;
            }

            $error = 'Correo o contraseña incorrectos.';

        } catch (Exception $e) {
            $error = 'No se pudo iniciar sesión.';
        }
    }
}
?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Iniciar sesión | Variedades Chiquis</title>

    <link
        rel="stylesheet"
        href="/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="/css/styles.css"
    >

</head>

<body class="bg-body-tertiary">

<div class="container d-flex align-items-center justify-content-center min-vh-100">

    <div class="card shadow border-0" style="max-width: 420px; width: 100%;">

        <div class="card-body p-4 p-md-5">

            <div class="text-center mb-4">

                <div class="mb-3">
                    <i
                        class="bi bi-bag-heart-fill"
                        style="font-size: 3rem;"
                    ></i>
                </div>

                <h2 class="fw-bold mb-1">
                    Variedades Chiquis
                </h2>

                <p class="text-body-secondary mb-0">
                    Sistema de Ventas por Catálogo
                </p>

            </div>

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">

                    <label
                        for="correo"
                        class="form-label"
                    >
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="correo"
                        name="correo"
                        required
                        autocomplete="email"
                    >

                </div>

                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Contraseña
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Iniciar sesión
                </button>

            </form>

        </div>

    </div>

</div>

</body>

</html>
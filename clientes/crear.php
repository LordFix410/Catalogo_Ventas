<?php
$titulo = "Nuevo Cliente";

require_once 'funciones.php';

$cliente = ['tipo_cliente' => 'Minorista'];
$acceso = ['con_acceso' => true, 'correo' => ''];
$tieneUsuario = false;
$errores = [];
$errorGeneral = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = Database::getConnection();

    list($cliente, $errores) = validarCliente($_POST);
    list($acceso, $erroresAcceso) = validarAcceso($conexion, $_POST, false);
    $errores = array_merge($errores, $erroresAcceso);

    if (empty($errores)) {
        try {
            mysqli_begin_transaction($conexion);

            $id_usuario = $acceso['con_acceso'] ? crearUsuarioCliente($conexion, $cliente, $acceso) : null;

            ejecutarProcedimiento(
                $conexion,
                "CALL sp_cliente_insertar(?, ?, ?, ?, ?, ?)",
                'isssss',
                [$id_usuario, $cliente['nombre'], $cliente['apellido'], $cliente['telefono'], $cliente['direccion'], $cliente['tipo_cliente']]
            );

            mysqli_commit($conexion);
            header('Location: index.php?msg=creado');
            exit;
        } catch (Exception $ex) {
            mysqli_rollback($conexion);
            $errorGeneral = 'No se pudo registrar el cliente: ' . $ex->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE CLIENTES</span>
                    <h1 class="page-title">Nuevo Cliente</h1>
                    <p class="page-description">
                        Registra la información básica del cliente y su clasificación.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="index.php">Clientes</a></li>
                        <li class="breadcrumb-item active">Nuevo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($errorGeneral): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= escapar($errorGeneral) ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <h3 class="section-title">
                    <i class="bi bi-person-plus"></i> Datos del cliente
                </h3>

                <?php
                $textoBoton = 'Guardar Cliente';
                include 'formulario.php';
                ?>
            </div>

        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

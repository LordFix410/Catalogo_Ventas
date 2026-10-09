<?php
$titulo = "Editar Cliente";

require_once 'funciones.php';

$id_cliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_cliente <= 0) {
    header('Location: index.php');
    exit;
}

$conexion = Database::getConnection();
$registro = obtenerCliente($conexion, $id_cliente);

if (!$registro) {
    header('Location: index.php?error=no_encontrado');
    exit;
}

$cliente = $registro;
$tieneUsuario = !empty($registro['id_usuario']);
$acceso = ['con_acceso' => $tieneUsuario, 'correo' => $registro['correo'] ?? ''];
$errores = [];
$errorGeneral = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($cliente, $errores) = validarCliente($_POST);
    list($acceso, $erroresAcceso) = validarAcceso($conexion, $_POST, $tieneUsuario, $registro['id_usuario']);
    $errores = array_merge($errores, $erroresAcceso);

    if (empty($errores)) {
        try {
            mysqli_begin_transaction($conexion);

            ejecutarProcedimiento(
                $conexion,
                "CALL sp_cliente_actualizar(?, ?, ?, ?, ?, ?)",
                'isssss',
                [$id_cliente, $cliente['nombre'], $cliente['apellido'], $cliente['telefono'], $cliente['direccion'], $cliente['tipo_cliente']]
            );

            if ($tieneUsuario) {
                actualizarUsuarioCliente($conexion, (int)$registro['id_usuario'], $cliente, $acceso, $registro['activo']);
            } elseif ($acceso['con_acceso']) {
                asociarUsuario($conexion, $id_cliente, crearUsuarioCliente($conexion, $cliente, $acceso));
                sincronizarEstadoUsuario($conexion, $id_cliente, $registro['activo']);
            }

            mysqli_commit($conexion);
            header('Location: index.php?msg=actualizado');
            exit;
        } catch (Exception $ex) {
            mysqli_rollback($conexion);
            $errorGeneral = 'No se pudo actualizar el cliente: ' . $ex->getMessage();
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
                    <h1 class="page-title">Editar Cliente</h1>
                    <p class="page-description">
                        Actualiza la información de
                        <strong><?= escapar($registro['nombre'] . ' ' . $registro['apellido']) ?></strong>.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="index.php">Clientes</a></li>
                        <li class="breadcrumb-item active">Editar</li>
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

            <?php if (!$registro['activo']): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Este cliente está <strong>inactivo</strong>. Puede editar sus datos, pero no aparecerá
                    al crear pedidos hasta que se reactive desde el listado.
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="section-title mb-0">
                        <i class="bi bi-pencil-square"></i> Datos del cliente
                    </h3>
                    <span class="text-muted small">
                        Cliente #<?= str_pad($id_cliente, 5, '0', STR_PAD_LEFT) ?>
                        · Registrado el <?= date('d/m/Y', strtotime($registro['fecha_registro'])) ?>
                    </span>
                </div>

                <?php
                $textoBoton = 'Guardar Cambios';
                include 'formulario.php';
                ?>
            </div>

        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

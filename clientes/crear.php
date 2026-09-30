<?php
// clientes/crear.php
// 1.1.1 Registro de clientes
$titulo = "Nuevo Cliente";

require_once 'funciones.php';

$cliente = ['tipo_cliente' => 'Minorista'];
$errores = [];
$errorGeneral = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($cliente, $errores) = validarCliente($_POST);

    if (empty($errores)) {
        try {
            $conexion = Database::getConnection();

            // id_usuario queda en NULL: la cuenta de acceso al portal es opcional
            ejecutarProcedimiento(
                $conexion,
                "CALL sp_cliente_insertar(?, ?, ?, ?, ?, ?)",
                'isssss',
                [null, $cliente['nombre'], $cliente['apellido'], $cliente['telefono'], $cliente['direccion'], $cliente['tipo_cliente']]
            );

            header('Location: index.php?msg=creado');
            exit;
        } catch (Exception $ex) {
            $errorGeneral = 'No se pudo registrar el cliente: ' . $ex->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<main class="app-main">

    <!-- ENCABEZADO -->
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

    <!-- CONTENIDO -->
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

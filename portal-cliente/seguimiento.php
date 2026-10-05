<?php

require_once '../includes/auth.php';

requiereRol(['Cliente']);

require_once '../config/database.php';

$conexion = Database::getConnection();

$pedidos = [];

/*
 * Por ahora no tenemos un cliente autenticado.
 * La consulta quedará preparada para recibir posteriormente
 * el id_cliente desde la sesión.
 */
?>

<?php
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../includes/sidebar.php';
?>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">
                    <h3 class="mb-0">
                        Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Cliente') ?>
                    </h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">

                        <li class="breadcrumb-item">
                            <a href="index.php">Inicio</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="index.php">Portal del Cliente</a>
                        </li>

                        <li class="breadcrumb-item active">
                            Seguimiento
                        </li>

                    </ol>
                </div>

            </div>

        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">
                        Mis Pedidos
                    </h3>
                </div>

                <div class="card-body">

                    <div class="alert alert-info">
                        El seguimiento de pedidos estará disponible
                        cuando el cliente haya iniciado sesión.
                    </div>

                </div>

            </div>

        </div>
    </div>

</main>

<?php
require_once '../includes/footer.php';
?>
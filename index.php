<?php

$titulo = "Inicio";

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';

?>

<main class="app-main">

    <!-- ENCABEZADO -->
    <div class="app-content-header">
        <div class="container-fluid">

            <div class="row align-items-center">

                <div class="col-sm-8">

                    <span class="page-eyebrow">
                        BIENVENIDO
                    </span>

                    <h1 class="page-title">
                        Sistema de Ventas
                    </h1>

                    <p class="page-description">
                        Gestiona clientes, productos, pedidos y seguimiento
                        de forma fácil y organizada.
                    </p>

                </div>

                <div class="col-sm-4">

                    <ol class="breadcrumb float-sm-end mb-0">

                        <li class="breadcrumb-item">
                            <i class="bi bi-house-door"></i>
                        </li>

                        <li class="breadcrumb-item active">
                            Inicio
                        </li>

                    </ol>

                </div>

            </div>

        </div>
    </div>


    <!-- CONTENIDO -->
    <div class="app-content">

        <div class="container-fluid">


            <!--TARJETAS DE RESUMEN-->
            <div class="row g-3 mb-4">

                <!-- Clientes -->
                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="/clientes/index.php"
                       class="dashboard-stat stat-clientes">

                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>

                        <div class="stat-content">

                            <span class="stat-title">
                                Clientes
                            </span>

                            <strong class="stat-number">
                                0
                            </strong>

                            <span class="stat-description">
                                Total registrados
                            </span>

                        </div>

                        <div class="stat-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>

                    </a>

                </div>


                <!-- Productos -->
                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="/productos/index.php"
                       class="dashboard-stat stat-productos">

                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>

                        <div class="stat-content">

                            <span class="stat-title">
                                Productos
                            </span>

                            <strong class="stat-number">
                                0
                            </strong>

                            <span class="stat-description">
                                En catálogo
                            </span>

                        </div>

                        <div class="stat-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>

                    </a>

                </div>


                <!-- Pedidos -->
                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="/pedidos/index.php"
                       class="dashboard-stat stat-pedidos">

                        <div class="stat-icon">
                            <i class="bi bi-cart"></i>
                        </div>

                        <div class="stat-content">

                            <span class="stat-title">
                                Pedidos
                            </span>

                            <strong class="stat-number">
                                0
                            </strong>

                            <span class="stat-description">
                                Total realizados
                            </span>

                        </div>

                        <div class="stat-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>

                    </a>

                </div>


                <!-- Seguimiento -->
                <div class="col-12 col-sm-6 col-xl-3">

                    <a href="/seguimiento/index.php"
                       class="dashboard-stat stat-seguimiento">

                        <div class="stat-icon">
                            <i class="bi bi-truck"></i>
                        </div>

                        <div class="stat-content">

                            <span class="stat-title">
                                Seguimiento
                            </span>

                            <strong class="stat-number">
                                0
                            </strong>

                            <span class="stat-description">
                                En proceso
                            </span>

                        </div>

                        <div class="stat-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>

                    </a>

                </div>

            </div>


            <!-- SEGUNDA FILA-->
            <div class="row g-4">


                <!-- BIENVENIDA -->
                <div class="col-12 col-xl-8">

                    <div class="dashboard-card welcome-card">

                        <div class="welcome-header">

                            <div class="welcome-logo">
                                <i class="bi bi-bag-heart"></i>
                            </div>

                            <div>

                                <h2>
                                    Variedades Chiquis, S.A.
                                </h2>

                                <p>
                                    Más que variedades, siempre contigo.
                                </p>

                            </div>

                        </div>


                        <div class="welcome-divider"></div>


                        <div class="welcome-body">

                            <p>
                                Bienvenido al Sistema de Ventas por Catálogo.
                                Desde este panel podrás administrar clientes,
                                productos, pedidos y dar seguimiento a cada
                                entrega.
                            </p>

                            <a href="/productos/index.php"
                               class="btn btn-chiquis">

                                <i class="bi bi-cart me-2"></i>

                                Comenzar

                                <i class="bi bi-arrow-right ms-2"></i>

                            </a>

                        </div>

                    </div>

                </div>


                <!-- ACCESOS RÁPIDOS -->
                <div class="col-12 col-xl-4">

                    <div class="dashboard-card">

                        <h3 class="section-title">

                            <i class="bi bi-lightning-charge-fill"></i>

                            Accesos rápidos

                        </h3>


                        <div class="quick-grid">

                            <a href="/clientes/index.php"
                               class="quick-link quick-clientes">

                                <i class="bi bi-people"></i>

                                <span>Clientes</span>

                            </a>


                            <a href="/productos/index.php"
                               class="quick-link quick-productos">

                                <i class="bi bi-box-seam"></i>

                                <span>Productos</span>

                            </a>


                            <a href="/pedidos/index.php"
                               class="quick-link quick-pedidos">

                                <i class="bi bi-cart"></i>

                                <span>Pedidos</span>

                            </a>


                            <a href="/seguimiento/index.php"
                               class="quick-link quick-seguimiento">

                                <i class="bi bi-truck"></i>

                                <span>Seguimiento</span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

<?php include 'includes/footer.php'; ?>
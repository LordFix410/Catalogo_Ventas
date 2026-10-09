<?php
$esCliente = isset($_SESSION['rol']) && $_SESSION['rol'] === 'Cliente';
$rutaInicio = $esCliente ? '/portal-cliente/index.php' : '/index.php';
?>

<aside class="app-sidebar sidebar-chiquis shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= $rutaInicio ?>" class="brand-link">
            <img
                src="/assets/img/AdminLTELogo.png"
                alt="Variedades Chiquis"
                class="brand-image opacity-75 shadow"
            >
            <span class="brand-text fw-light">Variedades Chiquis</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false">
                <li class="nav-item">
                    <a href="<?= $rutaInicio ?>" class="nav-link">
                        <i class="nav-icon bi bi-house-door"></i>
                        <p>Inicio</p>
                    </a>
                </li>

                <?php if ($esCliente): ?>
                    <li class="nav-item">
                        <a href="/portal-cliente/index.php" class="nav-link">
                            <i class="nav-icon bi bi-shop"></i>
                            <p>Portal del Cliente</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/portal-cliente/catalogo.php" class="nav-link">
                            <i class="nav-icon bi bi-grid"></i>
                            <p>Catálogo</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/portal-cliente/pedido.php" class="nav-link">
                            <i class="nav-icon bi bi-cart"></i>
                            <p>Carrito / Realizar pedido</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/portal-cliente/seguimiento.php" class="nav-link">
                            <i class="nav-icon bi bi-truck"></i>
                            <p>Mis pedidos</p>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="/clientes/index.php" class="nav-link">
                            <i class="nav-icon bi bi-people"></i>
                            <p>Clientes</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/productos/index.php" class="nav-link">
                            <i class="nav-icon bi bi-box-seam"></i>
                            <p>Productos</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/pedidos/index.php" class="nav-link">
                            <i class="nav-icon bi bi-cart"></i>
                            <p>Pedidos</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/seguimiento/index.php" class="nav-link">
                            <i class="nav-icon bi bi-truck"></i>
                            <p>Seguimiento</p>
                        </a>
                    </li>

                    <li class="nav-header">CLIENTES</li>

                    <li class="nav-item">
                        <a href="/portal-cliente/index.php" class="nav-link">
                            <i class="nav-icon bi bi-shop"></i>
                            <p>Portal Cliente</p>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>
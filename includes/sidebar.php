<?php
$esCliente = isset($_SESSION['rol']) && $_SESSION['rol'] === 'Cliente';
$rutaInicio = $esCliente ? '/portal-cliente/catalogo.php' : '/index.php';
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
                <?php if ($esCliente): ?>
                    <li class="nav-item">
                        <a href="/portal-cliente/catalogo.php" class="nav-link">
                            <i class="nav-icon bi bi-grid"></i>
                            <p>Catálogo</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/portal-cliente/pedido.php" class="nav-link">
                            <i class="nav-icon bi bi-cart"></i>
                            <p>Carrito</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/portal-cliente/seguimiento.php" class="nav-link">
                            <i class="nav-icon bi bi-truck"></i>
                            <p>Seguimiento</p>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?= $rutaInicio ?>" class="nav-link">
                            <i class="nav-icon bi bi-house-door"></i>
                            <p>Inicio</p>
                        </a>
                    </li>

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

                    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?><li class="nav-item">
                        <a href="/inventario/entradas.php" class="nav-link">
                            <i class="nav-icon bi bi-box-arrow-in-down"></i>
                            <p>Cargar factura</p>
                        </a>
                    </li><?php endif; ?>

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
                        <a href="/pedidos/crear.php" class="nav-link">
                            <i class="nav-icon bi bi-cart-plus"></i>
                            <p>Nuevo pedido para cliente</p>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>

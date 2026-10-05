<nav class="app-header navbar navbar-expand bg-body">

    <div class="container-fluid">

        <ul class="navbar-nav">

            <li class="nav-item">
                <a
                    class="nav-link"
                    data-lte-toggle="sidebar"
                    href="#"
                    role="button"
                    aria-label="Abrir menú"
                >
                    <i class="bi bi-list"></i>
                </a>
            </li>

            <li class="nav-item">
                <a href="/index.php" class="nav-link">
                    <i class="bi bi-house me-1"></i>
                    <span class="d-none d-sm-inline">Inicio</span>
                </a>
            </li>

        </ul>

        <ul class="navbar-nav ms-auto align-items-center">

            <li class="nav-item">
                <span class="nav-link">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-lg-inline">
                        <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>
                    </span>
                </span>
            </li>

            <li class="nav-item">
                <a href="/logout.php" class="nav-link" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <span class="d-none d-sm-inline">Cerrar sesión</span>
                </a>
            </li>

        </ul>

    </div>

</nav>
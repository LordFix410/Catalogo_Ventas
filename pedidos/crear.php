<?php
$titulo = "Nuevo Pedido";

require_once 'funciones.php';

$conexion   = Database::getConnection();
$clientes   = listarClientesActivos($conexion);
$productos  = listarProductosDisponibles($conexion);

$pedido        = ['id_cliente' => '', 'observaciones' => ''];
$lineasEnviadas = [];
$errores        = [];
$errorGeneral   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($pedido, $errores)  = validarPedido($_POST, $clientes);
    list($lineas, $errorLin) = validarLineasPedido($_POST, $productos);
    $errores = array_merge($errores, $errorLin);

    // Para poder redibujar el formulario tal como el usuario lo dejó si algo falla
    $lineasEnviadas = array_map(null, $_POST['producto'] ?? [], $_POST['cantidad'] ?? []);

    if (empty($errores)) {
        try {
            mysqli_begin_transaction($conexion);

            $resultado  = ejecutarProcedimiento(
                $conexion,
                "CALL sp_pedido_insertar(?, ?, ?)",
                'iss',
                [$pedido['id_cliente'], $pedido['observaciones'], null]
            );
            $id_pedido = (int)$resultado[0]['id_pedido'];

            foreach ($lineas as $linea) {
                ejecutarProcedimiento(
                    $conexion,
                    "CALL sp_detalle_insertar(?, ?, ?)",
                    'iii',
                    [$id_pedido, $linea['id_producto'], $linea['cantidad']]
                );
            }

            ejecutarProcedimiento(
                $conexion,
                "CALL sp_seguimiento_iniciar(?, ?)",
                'ii',
                [$id_pedido, $_SESSION['id_usuario']]
            );

            mysqli_commit($conexion);
            header('Location: detalle.php?id=' . $id_pedido . '&msg=creado');
            exit;
        } catch (Exception $ex) {
            mysqli_rollback($conexion);
            $errorGeneral = 'No se pudo registrar el pedido: ' . $ex->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<style>
  .fila-linea td { vertical-align: middle; }
  #resumenTotal { font-size: 1.3rem; color: #BE185D; }
</style>

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <span class="page-eyebrow">MÓDULO DE PEDIDOS</span>
                    <h1 class="page-title">Nuevo Pedido</h1>
                    <p class="page-description">
                        Selecciona el cliente y arma el detalle de productos del pedido.
                    </p>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item">
                            <a href="/index.php"><i class="bi bi-house-door"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="index.php">Pedidos</a></li>
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

            <?php if (isset($errores['lineas'])): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= escapar($errores['lineas']) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($clientes)): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    No hay clientes activos registrados. <a href="/clientes/crear.php">Registra un cliente</a> antes de crear un pedido.
                </div>
            <?php elseif (empty($productos)): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    No hay productos con stock disponible. <a href="/productos/crear.php">Registra productos</a> antes de crear un pedido.
                </div>
            <?php endif; ?>

            <form method="POST" id="formPedido">

                <div class="dashboard-card mb-4">
                    <h3 class="section-title">
                        <i class="bi bi-person-lines-fill"></i> Datos del pedido
                    </h3>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="id_cliente" class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                            <select id="id_cliente"
                                    name="id_cliente"
                                    class="form-select <?= isset($errores['id_cliente']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">Seleccione un cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>" <?= (string)($pedido['id_cliente'] ?? '') === (string)$c['id_cliente'] ? 'selected' : '' ?>>
                                        <?= escapar($c['nombre'] . ' ' . $c['apellido']) ?> — <?= escapar($c['tipo_cliente']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= escapar($errores['id_cliente'] ?? '') ?></div>
                        </div>

                        <div class="col-12">
                            <label for="observaciones" class="form-label fw-bold">Observaciones</label>
                            <textarea id="observaciones"
                                      name="observaciones"
                                      rows="2"
                                      maxlength="500"
                                      class="form-control <?= isset($errores['observaciones']) ? 'is-invalid' : '' ?>"
                                      placeholder="Ej: Entregar en horario de tarde, confirmar con el cliente antes de despachar..."><?= escapar($pedido['observaciones'] ?? '') ?></textarea>
                            <div class="invalid-feedback"><?= escapar($errores['observaciones'] ?? '') ?></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="section-title mb-0">
                            <i class="bi bi-box-seam"></i> Productos del pedido
                        </h3>
                        <button type="button" class="btn btn-chiquis btn-sm" id="btnAgregarLinea">
                            <i class="bi bi-plus-lg me-1"></i> Agregar producto
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="tablaLineas">
                            <thead style="background-color: #FCE7F3; color: #1F2937;">
                                <tr>
                                    <th style="min-width: 260px;">Producto</th>
                                    <th style="width: 120px;">Cantidad</th>
                                    <th style="width: 140px;">Precio</th>
                                    <th style="width: 140px;">Subtotal</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoLineas"></tbody>
                        </table>
                    </div>

                    <div class="text-end mt-3">
                        <span class="text-muted fw-bold">Total estimado:</span>
                        <span id="resumenTotal" class="fw-bold">Q 0.00</span>
                        <div class="form-text">El total final se calcula en el servidor al confirmar el pedido.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="button" class="btn btn-chiquis" data-bs-toggle="modal" data-bs-target="#modalConfirmarPedido">
                        <i class="bi bi-save me-1"></i> Guardar Pedido
                    </button>
                </div>

            </form>

        </div>
    </div>

</main>

<div class="modal fade" id="modalConfirmarPedido" tabindex="-1" aria-labelledby="modalConfirmarPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalConfirmarPedidoLabel"><i class="bi bi-cart-check text-primary me-2"></i>Confirmar pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1 small text-muted">Cliente</p>
                <strong id="confirmPedidoCliente">Selecciona un cliente</strong>
                <hr>
                <p class="mb-2 small text-muted">Productos y cantidades</p>
                <ul id="confirmPedidoProductos" class="list-unstyled mb-3"></ul>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <span class="fw-bold">Total estimado</span>
                    <strong id="confirmPedidoTotal" class="fs-4 text-primary">Q 0.00</strong>
                </div>
                <p class="small text-muted mt-3 mb-0">El pedido y las existencias se actualizarán al confirmar.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Seguir editando</button>
                <button type="submit" form="formPedido" class="btn btn-chiquis"><i class="bi bi-check-lg me-1"></i>Confirmar pedido</button>
            </div>
        </div>
    </div>
</div>

<template id="plantillaLinea">
    <tr class="fila-linea">
        <td>
            <input type="search" class="form-control form-control-sm mb-2 buscador-producto-linea" placeholder="Buscar producto..." autocomplete="off" aria-label="Buscar producto en la lista">
            <select name="producto[]" class="form-select select-producto" required>
                <option value="">Seleccione un producto...</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id_producto'] ?>"
                            data-precio="<?= $p['precio'] ?>"
                            data-stock="<?= $p['stock'] ?>">
                        <?= escapar($p['nombre']) ?> (stock: <?= $p['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted small-resultados-producto"></small>
        </td>
        <td>
            <input type="number" name="cantidad[]" class="form-control input-cantidad" min="1" value="1">
        </td>
        <td class="precio-unitario text-nowrap">Q 0.00</td>
        <td class="subtotal-linea text-nowrap fw-bold">Q 0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-linea">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cuerpo      = document.getElementById('cuerpoLineas');
    const plantilla    = document.getElementById('plantillaLinea');
    const btnAgregar   = document.getElementById('btnAgregarLinea');
    const resumenTotal = document.getElementById('resumenTotal');
    const btnResumen   = document.querySelector('[data-bs-target="#modalConfirmarPedido"]');

    function formatearQ(valor) {
        return 'Q ' + valor.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcularFila(fila) {
        const select   = fila.querySelector('.select-producto');
        const cantidad = fila.querySelector('.input-cantidad');
        const opcion   = select.options[select.selectedIndex];
        const precio   = opcion && opcion.value ? parseFloat(opcion.dataset.precio) : 0;
        const stock    = opcion && opcion.value ? parseInt(opcion.dataset.stock, 10) : null;

        if (stock !== null) {
            cantidad.max = stock;
            if (parseInt(cantidad.value, 10) > stock) {
                cantidad.value = stock;
            }
        }

        const cant    = parseInt(cantidad.value, 10) || 0;
        const subtotal = precio * cant;

        fila.querySelector('.precio-unitario').textContent = formatearQ(precio);
        fila.querySelector('.subtotal-linea').textContent  = formatearQ(subtotal);

        recalcularTotal();
    }

    function recalcularTotal() {
        let total = 0;
        cuerpo.querySelectorAll('.fila-linea').forEach(fila => {
            const select   = fila.querySelector('.select-producto');
            const cantidad = fila.querySelector('.input-cantidad');
            const opcion   = select.options[select.selectedIndex];
            const precio   = opcion && opcion.value ? parseFloat(opcion.dataset.precio) : 0;
            const cant     = parseInt(cantidad.value, 10) || 0;
            total += precio * cant;
        });
        resumenTotal.textContent = formatearQ(total);
    }

    function agregarLinea() {
        const nodo = plantilla.content.cloneNode(true);
        const fila = nodo.querySelector('.fila-linea');

        const select = fila.querySelector('.select-producto');
        const busqueda = fila.querySelector('.buscador-producto-linea');
        const resultados = fila.querySelector('.small-resultados-producto');

        busqueda.addEventListener('input', function () {
            const termino = this.value.trim().toLocaleLowerCase('es');
            let encontrados = 0;
            Array.from(select.options).forEach((opcion, indice) => {
                if (indice === 0) {
                    opcion.hidden = false;
                    return;
                }
                const coincide = opcion.textContent.toLocaleLowerCase('es').includes(termino);
                opcion.hidden = !coincide;
                if (coincide) encontrados++;
            });
            if (select.selectedOptions[0] && select.selectedOptions[0].hidden) select.value = '';
            resultados.textContent = termino ? `${encontrados} producto(s) encontrados` : '';
            recalcularFila(fila);
        });

        select.addEventListener('change', () => recalcularFila(fila));
        fila.querySelector('.input-cantidad').addEventListener('input', () => recalcularFila(fila));
        fila.querySelector('.btn-quitar-linea').addEventListener('click', () => {
            fila.remove();
            recalcularTotal();
        });

        cuerpo.appendChild(fila);
    }

    btnAgregar.addEventListener('click', agregarLinea);

    btnResumen.addEventListener('click', function () {
        const cliente = document.getElementById('id_cliente');
        document.getElementById('confirmPedidoCliente').textContent = cliente.selectedOptions[0]?.textContent.trim() || 'Selecciona un cliente';
        const lista = document.getElementById('confirmPedidoProductos');
        lista.replaceChildren();
        cuerpo.querySelectorAll('.fila-linea').forEach(fila => {
            const select = fila.querySelector('.select-producto');
            const cantidad = parseInt(fila.querySelector('.input-cantidad').value, 10) || 0;
            if (!select.value || cantidad < 1) return;
            const opcion = select.selectedOptions[0];
            const precio = parseFloat(opcion.dataset.precio) || 0;
            const li = document.createElement('li');
            li.className = 'd-flex justify-content-between gap-3 py-2 border-bottom';
            const detalle = document.createElement('span');
            detalle.textContent = `${opcion.textContent.split('(stock:')[0].trim()} × ${cantidad}`;
            const subtotal = document.createElement('strong');
            subtotal.textContent = formatearQ(precio * cantidad);
            li.append(detalle, subtotal);
            lista.appendChild(li);
        });
        document.getElementById('confirmPedidoTotal').textContent = resumenTotal.textContent;
    });

    // Arranca con una fila lista para usar
    agregarLinea();
});
</script>

<?php include '../includes/footer.php'; ?>

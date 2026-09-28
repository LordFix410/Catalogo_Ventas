<?php
require_once 'config.php';

// Obtener clientes activos
$stmtClientes =$pdo->query("SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre_completo, tipo_cliente FROM clientes WHERE activo = 1");
$clientes =$stmtClientes->fetchAll();

// Obtener productos activos con stock disponible
$stmtProductos =$pdo->query("SELECT id_producto, nombre, precio, stock FROM productos WHERE activo = 1 AND stock > 0");
$productos =$stmtProductos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuevo Pedido - Sistema de Ventas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #3b82f6;
        --primary-hover: #2563eb;
        --success: #10b981;
        --success-hover: #059669;
        --danger: #ef4444;
        --danger-hover: #dc2626;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
        padding: 40px 20px;
        line-height: 1.5;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        background: var(--card-bg);
        padding: 32px;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        border: 1px solid var(--border-color);
    }

    .header {
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 16px;
        margin-bottom: 24px;
    }

    .header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group.full-width {
        grid-column: span 2;
    }

    label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    select,
    textarea {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        font-family: inherit;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background-color: #fff;
        color: var(--text-dark);
        transition: all 0.2s ease;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    textarea { resize: vertical; min-height: 80px; }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        margin: 28px 0 12px 0;
        color: var(--text-dark);
    }

    .table-container {
        overflow-x: auto;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 16px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        text-align: left;
    }

    th {
        background-color: #f8fafc;
        padding: 12px 14px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        border-bottom: 1px solid var(--border-color);
    }

    td {
        padding: 10px 14px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }

    .subtotal-val {
        font-weight: 600;
        color: var(--text-dark);
    }

    /* Botones */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-primary { background-color: var(--primary); color: #fff; }
    .btn-primary:hover { background-color: var(--primary-hover); }

    .btn-success { background-color: var(--success); color: #fff; font-size: 15px; padding: 12px 24px; }
    .btn-success:hover { background-color: var(--success-hover); }

    .btn-danger {
        background-color: #fee2e2;
        color: var(--danger);
        padding: 6px 12px;
        font-size: 13px;
    }
    .btn-danger:hover { background-color: var(--danger); color: #fff; }

    .footer-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        padding-top: 16px;
    }

    .total-badge {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        background: #f8fafc;
        padding: 8px 18px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Módulo de Pedidos - Sistema de Ventas</h1>
    </div>

    <form id="formPedido" action="guardar_pedido.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="id_cliente">Cliente *</label>
                <select name="id_cliente" id="id_cliente" required>
                    <option value="">-- Seleccione un cliente --</option>
                    <?php foreach ($clientes as$c): ?>
                        <option value="<?= $c['id_cliente'] ?>">
                            <?= htmlspecialchars($c['nombre_completo']) ?> (<?=$c['tipo_cliente'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_pedido">Fecha del pedido *</label>
                <input type="date" name="fecha_pedido" id="fecha_pedido" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="observaciones">Observaciones / Notas del Pedido</label>
                <textarea name="observaciones" id="observaciones" placeholder="Ej. Entregar en horario de la tarde..."></textarea>
            </div>
        </div>

        <h2 class="section-title">Detalle del pedido</h2>

        <div class="table-container">
            <table id="tablaProductos">
                <thead>
                    <tr>
                        <th style="width: 40%;">Producto</th>
                        <th style="width: 20%;">Precio unitario</th>
                        <th style="width: 15%;">Cantidad</th>
                        <th style="width: 15%;">Subtotal</th>
                        <th style="width: 10%;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas dinámicas generadas por JS -->
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-primary" onclick="agregarFila()">+ Agregar producto</button>

        <div class="footer-summary">
            <div class="total-badge">
                Total: Q<span id="totalPedido">0.00</span>
            </div>
            <button type="submit" class="btn btn-success">Guardar Pedido</button>
        </div>
    </form>
</div>

<script>

// Catálogo de productos expue sto en JS para el cálculo dinámico
const productos = <?= json_encode($productos) ?>;

let contadorFilas = 0;

function agregarFila() {
    contadorFilas++;
    const tbody = document.querySelector('#tablaProductos tbody');
    const tr = document.createElement('tr');
    tr.id = `fila-${contadorFilas}`;

    let opciones = '<option value="">-- Seleccionar Producto --</option>';
    productos.forEach(p => {
        opciones += `<option value="${p.id_producto}" data-precio="${p.precio}" data-stock="${p.stock}">
            ${p.nombre} (Stock: ${p.stock})
        </option>`;
    });

    tr.innerHTML = `
        <td>
            <select name="productos[${contadorFilas}][id_producto]" class="producto-select" onchange="actualizarPrecio(${contadorFilas})" required>
                ${opciones}
            </select>
        </td>
        <td>
            <input type="text" class="precio-input" id="precio-${contadorFilas}" value="0.00" readonly style="background-color: #f8fafc; cursor: not-allowed;">
        </td>
        <td>
            <input type="number" name="productos[${contadorFilas}][cantidad]" class="cantidad-input" id="cantidad-${contadorFilas}" value="1" min="1" onchange="calcularSubtotal(${contadorFilas})" onkeyup="calcularSubtotal(${contadorFilas})" required>
        </td>
        <td class="subtotal-val">
            Q<span id="subtotal-${contadorFilas}">0.00</span>
        </td>
        <td>
            <button type="button" class="btn btn-danger" onclick="eliminarFila(${contadorFilas})">Quitar</button>
        </td>
    `;

    tbody.appendChild(tr);
}

function actualizarPrecio(idFila) {
    const select = document.querySelector(`#fila-${idFila} .producto-select`);
    const option = select.options[select.selectedIndex];
    const precio = option.dataset.precio || 0;
    const stock = option.dataset.stock || 1;

    document.getElementById(`precio-${idFila}`).value = parseFloat(precio).toFixed(2);
    
    const cantidadInput = document.getElementById(`cantidad-${idFila}`);
    cantidadInput.max = stock;
    if (parseInt(cantidadInput.value) > parseInt(stock)) {
        cantidadInput.value = stock;
    }

    calcularSubtotal(idFila);
}

function calcularSubtotal(idFila) {
    const precio = parseFloat(document.getElementById(`precio-${idFila}`).value) || 0;
    const cantidad = parseInt(document.getElementById(`cantidad-${idFila}`).value) || 0;
    const subtotal = precio * cantidad;

    document.getElementById(`subtotal-${idFila}`).innerText = subtotal.toFixed(2);
    calcularTotalGeneral();
}

function eliminarFila(idFila) {
    const fila = document.getElementById(`fila-${idFila}`);
    if (fila) {
        fila.remove();
        calcularTotalGeneral();
    }
}

function calcularTotalGeneral() {
    let total = 0;
    document.querySelectorAll('[id^="subtotal-"]').forEach(span => {
        total += parseFloat(span.innerText) || 0;
    });
    document.getElementById('totalPedido').innerText = total.toFixed(2);
}


// aggregar primera fila por defecto al cargar
window.onload = function() {
    agregarFila();
};
</script>

</body>
</html>
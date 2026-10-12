<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requiereRol(['Administrador', 'Empleado']);

const LARGO_MAXIMO_OBSERVACIONES = 500;

function ejecutarProcedimiento($conexion, $sql, $tipos = '', $parametros = []) {
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($error);
    }

    $filas = [];
    $resultado = mysqli_stmt_get_result($stmt);
    if ($resultado) {
        $filas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
        mysqli_free_result($resultado);
    }

    // Liberar los resultados extra de los SP
    while (mysqli_stmt_more_results($stmt) && mysqli_stmt_next_result($stmt)) {
        if ($extra = mysqli_stmt_get_result($stmt)) {
            mysqli_free_result($extra);
        }
    }

    mysqli_stmt_close($stmt);
    return $filas;
}

// Alternativa a mb_strlen, no todos tienen mbstring
function longitud($texto) {
    return preg_match_all('/./us', (string)$texto);
}

function escapar($texto) {
    return htmlspecialchars((string)($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

function obtenerPedido($conexion, $id_pedido) {
    $filas = ejecutarProcedimiento($conexion, "CALL sp_pedido_obtener(?)", 'i', [$id_pedido]);
    return $filas[0] ?? null;
}

function listarDetallePedido($conexion, $id_pedido) {
    return ejecutarProcedimiento($conexion, "CALL sp_detalle_listar(?)", 'i', [$id_pedido]);
}

function listarHistorialSeguimiento($conexion, $id_pedido) {
    return ejecutarProcedimiento($conexion, "CALL sp_seguimiento_historial(?)", 'i', [$id_pedido]);
}

function listarPedidosConEstado($conexion) {
    return ejecutarProcedimiento($conexion, "CALL sp_seguimiento_listar()");
}

function listarClientesActivos($conexion) {
    $clientes = ejecutarProcedimiento($conexion, "CALL sp_cliente_listar()");
    return array_values(array_filter($clientes, fn($c) => $c['activo']));
}

function listarProductosDisponibles($conexion) {
    $productos = ejecutarProcedimiento($conexion, "CALL sp_producto_listar()");
    return array_values(array_filter($productos, fn($p) => $p['activo'] && $p['stock'] > 0));
}

function listarEstadosPedido($conexion) {
    return ejecutarProcedimiento($conexion, "SELECT id_estado, nombre, orden FROM estados_pedido ORDER BY orden");
}

function validarPedido($entrada, $clientesValidos) {
    $datos = [
        'id_cliente'    => (int)($entrada['id_cliente'] ?? 0),
        'observaciones' => trim($entrada['observaciones'] ?? ''),
    ];

    $errores = [];

    $idsValidos = array_column($clientesValidos, 'id_cliente');
    if (!in_array($datos['id_cliente'], $idsValidos, true)) {
        $errores['id_cliente'] = 'Seleccione un cliente válido.';
    }

    if (longitud($datos['observaciones']) > LARGO_MAXIMO_OBSERVACIONES) {
        $errores['observaciones'] = 'Las observaciones no pueden superar los ' . LARGO_MAXIMO_OBSERVACIONES . ' caracteres.';
    }

    $datos['observaciones'] = $datos['observaciones'] !== '' ? $datos['observaciones'] : null;

    return [$datos, $errores];
}

// Arma las líneas del pedido a partir de los arreglos producto[] y cantidad[] del formulario,
// descartando filas vacías que el usuario no llenó
function validarLineasPedido($entrada, $productosDisponibles) {
    $productosPorId = [];
    foreach ($productosDisponibles as $p) {
        $productosPorId[(int)$p['id_producto']] = $p;
    }

    $idsProducto = $entrada['producto'] ?? [];
    $cantidades  = $entrada['cantidad'] ?? [];

    $lineas  = [];
    $errores = [];

    foreach ($idsProducto as $i => $idProducto) {
        $idProducto = (int)$idProducto;
        $cantidad   = (int)($cantidades[$i] ?? 0);

        if ($idProducto <= 0 && $cantidad <= 0) {
            continue; // fila vacía, se ignora
        }

        if (!isset($productosPorId[$idProducto])) {
            $errores['lineas'] = 'Uno de los productos seleccionados no está disponible.';
            continue;
        }

        if ($cantidad <= 0) {
            $errores['lineas'] = 'Ingrese una cantidad válida para ' . $productosPorId[$idProducto]['nombre'] . '.';
            continue;
        }

        if ($cantidad > (int)$productosPorId[$idProducto]['stock']) {
            $errores['lineas'] = 'No hay suficiente stock de ' . $productosPorId[$idProducto]['nombre']
                . ' (disponible: ' . $productosPorId[$idProducto]['stock'] . ').';
            continue;
        }

        $lineas[] = [
            'id_producto' => $idProducto,
            'cantidad'    => $cantidad,
        ];
    }

    if (empty($lineas) && empty($errores)) {
        $errores['lineas'] = 'Agregue al menos un producto al pedido.';
    }

    return [$lineas, $errores];
}

function colorBadgeEstado($estado) {
    $colores = [
        'Recibido'               => 'text-bg-secondary',
        'En preparación'         => 'text-bg-warning',
        'Preparado'              => 'text-bg-info',
        'Despachado'             => 'text-bg-primary',
        'En proceso de entrega'  => 'text-bg-primary',
        'Entregado'              => 'text-bg-success',
    ];

    return $colores[$estado] ?? 'text-bg-secondary';
}
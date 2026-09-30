<?php
// clientes/funciones.php
// Funciones compartidas del Módulo de Clientes (1.1)

require_once __DIR__ . '/../config/database.php';

// Tipos de cliente permitidos (coinciden con el ENUM de la tabla clientes)
const TIPOS_CLIENTE = ['Minorista', 'Mayorista'];

// Ejecuta un procedimiento almacenado con parámetros seguros y devuelve las filas
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

    // Limpia los resultados extra que devuelven los Stored Procedures
    while (mysqli_stmt_more_results($stmt) && mysqli_stmt_next_result($stmt)) {
        if ($extra = mysqli_stmt_get_result($stmt)) {
            mysqli_free_result($extra);
        }
    }

    mysqli_stmt_close($stmt);
    return $filas;
}

function obtenerCliente($conexion, $id_cliente) {
    $filas = ejecutarProcedimiento($conexion, "CALL sp_cliente_obtener(?)", 'i', [$id_cliente]);
    return $filas[0] ?? null;
}

// Limpia y valida los datos del formulario. Devuelve [datos, errores]
function validarCliente($entrada) {
    $datos = [
        'nombre'       => trim($entrada['nombre'] ?? ''),
        'apellido'     => trim($entrada['apellido'] ?? ''),
        'telefono'     => trim($entrada['telefono'] ?? ''),
        'direccion'    => trim($entrada['direccion'] ?? ''),
        'tipo_cliente' => trim($entrada['tipo_cliente'] ?? ''),
    ];

    $errores = [];

    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($datos['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($datos['apellido'] === '') {
        $errores['apellido'] = 'El apellido es obligatorio.';
    } elseif (mb_strlen($datos['apellido']) > 100) {
        $errores['apellido'] = 'El apellido no puede superar los 100 caracteres.';
    }

    if ($datos['telefono'] !== '') {
        $soloDigitos = preg_replace('/\D/', '', $datos['telefono']);
        if (!preg_match('/^\+?[0-9\s\-()]+$/', $datos['telefono'])
            || strlen($soloDigitos) < 8
            || mb_strlen($datos['telefono']) > 20) {
            $errores['telefono'] = 'Ingrese un teléfono válido (mínimo 8 dígitos).';
        }
    }

    if (mb_strlen($datos['direccion']) > 255) {
        $errores['direccion'] = 'La dirección no puede superar los 255 caracteres.';
    }

    if (!in_array($datos['tipo_cliente'], TIPOS_CLIENTE, true)) {
        $errores['tipo_cliente'] = 'Seleccione un tipo de cliente válido.';
    }

    // Los campos opcionales vacíos se guardan como NULL
    $datos['telefono']  = $datos['telefono'] !== '' ? $datos['telefono'] : null;
    $datos['direccion'] = $datos['direccion'] !== '' ? $datos['direccion'] : null;

    return [$datos, $errores];
}

function escapar($texto) {
    return htmlspecialchars((string)($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

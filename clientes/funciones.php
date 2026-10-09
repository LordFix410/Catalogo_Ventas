<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requiereRol(['Administrador', 'Empleado']);

const TIPOS_CLIENTE = ['Minorista', 'Mayorista'];
const LARGO_MINIMO_PASSWORD = 6;

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

function obtenerCliente($conexion, $id_cliente) {
    $filas = ejecutarProcedimiento($conexion, "CALL sp_cliente_obtener(?)", 'i', [$id_cliente]);
    return $filas[0] ?? null;
}

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
    } elseif (longitud($datos['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($datos['apellido'] === '') {
        $errores['apellido'] = 'El apellido es obligatorio.';
    } elseif (longitud($datos['apellido']) > 100) {
        $errores['apellido'] = 'El apellido no puede superar los 100 caracteres.';
    }

    if ($datos['telefono'] !== '') {
        $soloDigitos = preg_replace('/\D/', '', $datos['telefono']);
        if (!preg_match('/^\+?[0-9\s\-()]+$/', $datos['telefono'])
            || strlen($soloDigitos) < 8
            || longitud($datos['telefono']) > 20) {
            $errores['telefono'] = 'Ingrese un teléfono válido (mínimo 8 dígitos).';
        }
    }

    if (longitud($datos['direccion']) > 255) {
        $errores['direccion'] = 'La dirección no puede superar los 255 caracteres.';
    }

    if (!in_array($datos['tipo_cliente'], TIPOS_CLIENTE, true)) {
        $errores['tipo_cliente'] = 'Seleccione un tipo de cliente válido.';
    }

    $datos['telefono']  = $datos['telefono'] !== '' ? $datos['telefono'] : null;
    $datos['direccion'] = $datos['direccion'] !== '' ? $datos['direccion'] : null;

    return [$datos, $errores];
}

// Si el cliente ya tiene usuario, la contraseña vacía significa conservar la actual
function validarAcceso($conexion, $entrada, $tieneUsuario, $id_usuario = null) {
    $acceso = [
        'con_acceso' => $tieneUsuario || !empty($entrada['crear_acceso']),
        'correo'     => trim($entrada['correo'] ?? ''),
        'password'   => $entrada['password'] ?? '',
    ];

    $errores = [];

    if (!$acceso['con_acceso']) {
        return [$acceso, $errores];
    }

    if ($acceso['correo'] === '') {
        $errores['correo'] = 'El correo es obligatorio para la cuenta del portal.';
    } elseif (!filter_var($acceso['correo'], FILTER_VALIDATE_EMAIL) || longitud($acceso['correo']) > 100) {
        $errores['correo'] = 'Ingrese un correo válido.';
    } else {
        $existente = buscarUsuarioPorCorreo($conexion, $acceso['correo']);
        if ($existente && (int)$existente['id_usuario'] !== (int)$id_usuario) {
            $errores['correo'] = 'Este correo ya está registrado en otra cuenta.';
        }
    }

    if ($acceso['password'] === '') {
        if (!$tieneUsuario) {
            $errores['password'] = 'La contraseña es obligatoria.';
        }
    } elseif (strlen($acceso['password']) < LARGO_MINIMO_PASSWORD) {
        $errores['password'] = 'La contraseña debe tener al menos ' . LARGO_MINIMO_PASSWORD . ' caracteres.';
    } elseif ($acceso['password'] !== ($entrada['confirmar_password'] ?? '')) {
        $errores['confirmar_password'] = 'Las contraseñas no coinciden.';
    }

    return [$acceso, $errores];
}

function buscarUsuarioPorCorreo($conexion, $correo) {
    $filas = ejecutarProcedimiento($conexion, "CALL sp_usuario_buscar(?)", 's', [$correo]);
    return $filas[0] ?? null;
}

function idRolCliente($conexion) {
    $filas = ejecutarProcedimiento($conexion, "SELECT id_rol FROM roles WHERE nombre = 'Cliente'");
    if (!$filas) {
        throw new Exception('No existe el rol Cliente.');
    }
    return (int)$filas[0]['id_rol'];
}

function nombreUsuario($cliente) {
    preg_match('/^.{0,100}/us', $cliente['nombre'] . ' ' . $cliente['apellido'], $m);
    return $m[0];
}

function crearUsuarioCliente($conexion, $cliente, $acceso) {
    ejecutarProcedimiento(
        $conexion,
        "CALL sp_usuario_insertar(?, ?, ?, ?)",
        'isss',
        [idRolCliente($conexion), nombreUsuario($cliente), $acceso['correo'], password_hash($acceso['password'], PASSWORD_DEFAULT)]
    );

    return (int)buscarUsuarioPorCorreo($conexion, $acceso['correo'])['id_usuario'];
}

function actualizarUsuarioCliente($conexion, $id_usuario, $cliente, $acceso, $activo) {
    ejecutarProcedimiento(
        $conexion,
        "CALL sp_usuario_actualizar(?, ?, ?, ?, ?)",
        'iissi',
        [$id_usuario, idRolCliente($conexion), nombreUsuario($cliente), $acceso['correo'], $activo ? 1 : 0]
    );

    if ($acceso['password'] !== '') {
        ejecutarProcedimiento(
            $conexion,
            "UPDATE usuarios SET password = ? WHERE id_usuario = ?",
            'si',
            [password_hash($acceso['password'], PASSWORD_DEFAULT), $id_usuario]
        );
    }
}

function asociarUsuario($conexion, $id_cliente, $id_usuario) {
    ejecutarProcedimiento($conexion, "UPDATE clientes SET id_usuario = ? WHERE id_cliente = ?", 'ii', [$id_usuario, $id_cliente]);
}

// Un cliente inactivo no debe poder entrar al portal
function sincronizarEstadoUsuario($conexion, $id_cliente, $activo) {
    ejecutarProcedimiento(
        $conexion,
        "UPDATE usuarios u INNER JOIN clientes c ON c.id_usuario = u.id_usuario SET u.activo = ? WHERE c.id_cliente = ?",
        'ii',
        [$activo ? 1 : 0, $id_cliente]
    );
}

function escapar($texto) {
    return htmlspecialchars((string)($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

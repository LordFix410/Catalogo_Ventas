<?php
require_once '../includes/auth.php';
requiereRol(['Administrador', 'Empleado']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['token'] ?? '';
$idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$idEstado = filter_var($_POST['id_estado'] ?? null, FILTER_VALIDATE_INT);
$observacion = trim((string) ($_POST['observacion'] ?? ''));
$idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
if (!is_string($token) || empty($_SESSION['token_seguimiento']) || !hash_equals($_SESSION['token_seguimiento'], $token)
    || !$idPedido || $idPedido < 1 || !$idEstado || $idEstado < 1 || $idUsuario < 1
    || $observacion === '' || mb_strlen($observacion) > 500) {
    header('Location: index.php?error=datos_incompletos');
    exit;
}

$conexion = Database::getConnection();
$stmtEstado = mysqli_prepare($conexion, 'SELECT nombre FROM estados_pedido WHERE id_estado = ? LIMIT 1');
mysqli_stmt_bind_param($stmtEstado, 'i', $idEstado);
mysqli_stmt_execute($stmtEstado);
$resultadoEstado = mysqli_stmt_get_result($stmtEstado);
$estadoObjetivo = mysqli_fetch_assoc($resultadoEstado);
mysqli_free_result($resultadoEstado);
mysqli_stmt_close($stmtEstado);
if (!$estadoObjetivo || $estadoObjetivo['nombre'] === 'Cancelado') {
    header('Location: index.php?error=datos_incompletos');
    exit;
}

$stmtActual = mysqli_prepare($conexion, 'SELECT e.nombre FROM seguimiento_pedido s INNER JOIN estados_pedido e ON e.id_estado = s.id_estado WHERE s.id_pedido = ? ORDER BY s.fecha_hora DESC, s.id_seguimiento DESC LIMIT 1');
mysqli_stmt_bind_param($stmtActual, 'i', $idPedido);
mysqli_stmt_execute($stmtActual);
$resultadoActual = mysqli_stmt_get_result($stmtActual);
$estadoActual = mysqli_fetch_assoc($resultadoActual);
mysqli_free_result($resultadoActual);
mysqli_stmt_close($stmtActual);
if (in_array(mb_strtolower((string) ($estadoActual['nombre'] ?? '')), ['cancelado', 'entregado'], true)) {
    header('Location: index.php?error=pedido_finalizado');
    exit;
}

try {
    $stmt = mysqli_prepare($conexion, 'CALL sp_seguimiento_actualizar(?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiis', $idPedido, $idEstado, $idUsuario, $observacion);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    Database::limpiarResultados($conexion);
    header('Location: index.php?msg=actualizado');
    exit;
} catch (Throwable $e) {
    error_log('Error al cambiar estado del pedido: ' . $e->getMessage());
    header('Location: index.php?error=operacion');
    exit;
}

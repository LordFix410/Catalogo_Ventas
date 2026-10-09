<?php
// Baja lógica para no romper los pedidos existentes
require_once 'funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
$accion     = $_POST['accion'] ?? 'desactivar';

if ($id_cliente <= 0 || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    header('Location: index.php?error=datos_incompletos');
    exit;
}

$conexion = Database::getConnection();

if (!obtenerCliente($conexion, $id_cliente)) {
    header('Location: index.php?error=no_encontrado');
    exit;
}

try {
    mysqli_begin_transaction($conexion);

    if ($accion === 'desactivar') {
        ejecutarProcedimiento($conexion, "CALL sp_cliente_eliminar(?)", 'i', [$id_cliente]);
    } else {
        ejecutarProcedimiento($conexion, "UPDATE clientes SET activo = TRUE WHERE id_cliente = ?", 'i', [$id_cliente]);
    }
    sincronizarEstadoUsuario($conexion, $id_cliente, $accion === 'reactivar');

    mysqli_commit($conexion);
    header('Location: index.php?msg=' . ($accion === 'desactivar' ? 'desactivado' : 'reactivado'));
    exit;
} catch (Exception $ex) {
    mysqli_rollback($conexion);
    die("Error al actualizar el cliente: " . escapar($ex->getMessage()));
}

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
    if ($accion === 'desactivar') {
        ejecutarProcedimiento($conexion, "CALL sp_cliente_eliminar(?)", 'i', [$id_cliente]);
        header('Location: index.php?msg=desactivado');
    } else {
        ejecutarProcedimiento($conexion, "UPDATE clientes SET activo = TRUE WHERE id_cliente = ?", 'i', [$id_cliente]);
        header('Location: index.php?msg=reactivado');
    }
    exit;
} catch (Exception $ex) {
    die("Error al actualizar el cliente: " . escapar($ex->getMessage()));
}

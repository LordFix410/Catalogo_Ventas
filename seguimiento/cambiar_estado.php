<?php
// seguimiento/cambiar_estado.php
require_once '../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido   = isset($_POST['id_pedido']) ? intval($_POST['id_pedido']) : 0;
    $id_estado   = isset($_POST['id_estado']) ? intval($_POST['id_estado']) : 0;
    $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';
    $id_usuario  = isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 1;

    if ($id_pedido > 0 && $id_estado > 0 && !empty($observacion)) {
        $conexion = Database::getConnection();
        $obsEscapada = mysqli_real_escape_string($conexion, $observacion);
        
        $sql = "CALL sp_seguimiento_actualizar($id_pedido, $id_estado, $id_usuario, '$obsEscapada')";
        
        if (mysqli_query($conexion, $sql)) {
            Database::limpiarResultados($conexion);
            header('Location: index.php?msg=actualizado');
            exit;
        } else {
            die("Error al actualizar estado: " . mysqli_error($conexion));
        }
    } else {
        header('Location: index.php?error=datos_incompletos');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
<?php
require_once __DIR__ . '/funciones.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { header('Location: index.php?error=no_encontrado'); exit; }
$conexion = Database::getConnection();
productosEjecutar($conexion, 'CALL sp_producto_eliminar(?)', 'i', [$id]);
header('Location: index.php?msg=desactivado');
exit;

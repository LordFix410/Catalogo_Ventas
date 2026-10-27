<?php
require_once __DIR__ . '/funciones.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$token = $_POST['token'] ?? '';
if (!is_string($token) || empty($_SESSION['token_producto_estado']) || !hash_equals($_SESSION['token_producto_estado'], $token)) { header('Location: index.php?error=solicitud'); exit; }
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { header('Location: index.php?error=no_encontrado'); exit; }
$conexion = Database::getConnection();
productosEjecutar($conexion, 'CALL sp_producto_eliminar(?)', 'i', [$id]);
header('Location: index.php?msg=desactivado');
exit;

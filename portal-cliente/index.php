<?php
require_once __DIR__ . '/../includes/auth.php';
requiereRol(['Cliente']);

header('Location: /portal-cliente/catalogo.php');
exit;

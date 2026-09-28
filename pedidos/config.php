<?php

date_default_timezone_set('America/Guatemala');

$host    = 'localhost';
$port    = '3306';               // Puerto por defecto de MySQL
$db      = 'catalogo_ventas';    
$user    = 'root';               // Usuario de la base de datos
$pass    = '';                   // Contraseña de la base de datos
$charset = 'utf8mb4';            


$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";


$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Retorna los resultados como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                 // Usa preparaciones nativas de consultas
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opciones);
} catch (PDOException $e) {
    // Muestra el mensaje de error si no logra conectarse
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
<?php
// config/database.php

if (!function_exists('cargarEnv')) {
    function cargarEnv($ruta) {
        if (!file_exists($ruta)) return;
        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $lineaLimpia = trim($linea);
            if (empty($lineaLimpia) || strpos($lineaLimpia, '#') === 0) continue;
            if (strpos($lineaLimpia, '=') !== false) {
                list($nombre, $valor) = explode('=', $lineaLimpia, 2);
                $nombre = trim($nombre);
                $valor = trim($valor, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($nombre, $_SERVER) && !array_key_exists($nombre, $_ENV)) {
                    putenv("{$nombre}={$valor}");
                    $_ENV[$nombre] = $valor;
                    $_SERVER[$nombre] = $valor;
                }
            }
        }
    }
}

class Database {
    private static $conexion = null;

    public static function getConnection() {
        if (self::$conexion === null) {
            cargarEnv(__DIR__ . '/../.env');

            $host     = getenv('DB_HOST') ?: 'localhost';
            $port     = (int)(getenv('DB_PORT') ?: 3309);
            $db_name  = getenv('DB_NAME') ?: 'catalogo_ventas';
            $user     = getenv('DB_USER') ?: 'root';
            $pass     = getenv('DB_PASS') ?: 'admin117';

            self::$conexion = @mysqli_connect($host, $user, $pass, $db_name, $port);

            if (!self::$conexion) {
                die("Error al conectar con la base de datos: " . mysqli_connect_error());
            }

            mysqli_set_charset(self::$conexion, "utf8mb4");
        }
        return self::$conexion;
    }

    // Limpia buffers pendientes de los Stored Procedures
    public static function limpiarResultados($conexion) {
        while (mysqli_more_results($conexion) && mysqli_next_result($conexion)) {
            if ($result = mysqli_store_result($conexion)) {
                mysqli_free_result($result);
            }
        }
    }
}
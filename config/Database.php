<?php

/**
 * Database
 *
 * Punto único de acceso a la conexión con PostgreSQL. Se implementa como
 * singleton para que todos los modelos reutilicen la misma conexión PDO
 * durante la petición, en vez de abrir una conexión nueva cada vez.
 */
class Database
{
    private static ?PDO $conexion = null;

    /**
     * Devuelve la conexión PDO activa, creándola la primera vez que se
     * solicita a partir de los datos definidos en .env (vía Config).
     */
    public static function getConnection(): PDO
    {
        if (self::$conexion === null) {
            $host = Config::get('DB_HOST', 'localhost');
            $puerto = Config::get('DB_PORT', '5432');
            $nombre = Config::get('DB_NAME');
            $usuario = Config::get('DB_USER');
            $clave = Config::get('DB_PASS');

            $dsn = "pgsql:host={$host};port={$puerto};dbname={$nombre}";

            self::$conexion = new PDO($dsn, $usuario, $clave, [
                // Los errores de PDO se lanzan como excepciones en vez de
                // devolver false silenciosamente, para poder capturarlos
                // en los controladores y mostrar un mensaje claro.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Cada fila se devuelve como arreglo asociativo
                // (columna => valor) en vez de objetos o índices numéricos.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$conexion;
    }
}

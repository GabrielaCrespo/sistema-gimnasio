<?php

/**
 * Config
 *
 * Carga las variables de entorno desde el archivo .env de la raíz del
 * proyecto (no versionado, ver .gitignore) y expone un acceso estático de
 * solo lectura para el resto de la aplicación. No se usa ninguna librería
 * externa: un .env es simplemente texto "CLAVE=valor" por línea, así que
 * basta con un parser propio muy simple.
 */
class Config
{
    /** @var array<string,string> Valores ya leídos desde .env */
    private static array $valores = [];

    /** Evita volver a leer el archivo si ya se cargó una vez en esta petición. */
    private static bool $cargado = false;

    /** Lee .env línea por línea y guarda cada CLAVE=valor en memoria. */
    private static function cargar(): void
    {
        if (self::$cargado) {
            return;
        }

        $rutaEnv = __DIR__ . '/.env';

        if (is_file($rutaEnv)) {
            $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lineas as $linea) {
                $linea = trim($linea);

                // Se ignoran líneas vacías y comentarios (empiezan con #).
                if ($linea === '' || str_starts_with($linea, '#')) {
                    continue;
                }

                [$clave, $valor] = array_pad(explode('=', $linea, 2), 2, '');
                self::$valores[trim($clave)] = trim($valor, " \t\n\r\0\x0B\"'");
            }
        }

        self::$cargado = true;
    }

    /** Devuelve el valor de una variable de entorno, o $default si no existe. */
    public static function get(string $clave, ?string $default = null): ?string
    {
        self::cargar();

        return self::$valores[$clave] ?? $default;
    }
}

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

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

        $rutaEnv = dirname(__DIR__) . '/.env';

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

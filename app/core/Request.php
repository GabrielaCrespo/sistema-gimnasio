<?php

/**
 * Request
 *
 * Envoltorio simple sobre $_GET/$_POST/$_SERVER para que los controladores
 * no accedan a las superglobales directamente. No implementa rutas ni
 * patrones de URL: solo agrupa los datos de la petición HTTP actual
 * (controlador y acción solicitados, y los campos enviados por el usuario).
 */
class Request
{
    public readonly string $controlador;
    public readonly string $accion;
    public readonly string $metodo;

    public function __construct()
    {
        $this->controlador = $_GET['controller'] ?? 'home';
        $this->accion = $_GET['action'] ?? 'index';
        $this->metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /** true si la petición actual llegó por POST (envío de formularios). */
    public function esPost(): bool
    {
        return $this->metodo === 'POST';
    }

    /**
     * Obtiene un valor enviado por POST o GET (POST tiene prioridad).
     * Centraliza el trim() de strings y el valor por defecto en un único
     * lugar en vez de repetirlo en cada controlador.
     */
    public function input(string $clave, mixed $default = null): mixed
    {
        if (array_key_exists($clave, $_POST)) {
            $valor = $_POST[$clave];
        } elseif (array_key_exists($clave, $_GET)) {
            $valor = $_GET[$clave];
        } else {
            return $default;
        }

        return is_string($valor) ? trim($valor) : $valor;
    }

    /** Devuelve todos los datos enviados por POST (usado para campos repetidos, ej. filas de una rutina). */
    public function todoPost(): array
    {
        return $_POST;
    }
}

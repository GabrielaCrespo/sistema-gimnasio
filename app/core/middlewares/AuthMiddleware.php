<?php

/**
 * AuthMiddleware
 *
 * Verifica que exista una sesión de usuario activa. Cada controlador
 * llama a AuthMiddleware::handle() como primera línea de cualquier acción
 * que requiera estar autenticado (todas excepto login, register y la
 * portada pública de HomeController).
 */
class AuthMiddleware
{
    /** Si no hay sesión activa, redirige al login y detiene la ejecución. */
    public static function handle(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . url('auth', 'login'));
            exit;
        }
    }
}

<?php

/**
 * RoleMiddleware
 *
 * Verifica que el rol del usuario en sesión esté dentro de los roles
 * permitidos para la acción actual. Debe llamarse SIEMPRE después de
 * AuthMiddleware::handle(), que garantiza que $_SESSION['user'] ya existe.
 */
class RoleMiddleware
{
    /**
     * Corta la ejecución con un 403 si el rol de la sesión no está en la
     * lista de roles permitidos.
     *
     * @param string[] $rolesPermitidos Ej. ['administrador', 'instructor'].
     */
    public static function handle(array $rolesPermitidos): void
    {
        $rolActual = $_SESSION['user']['rol'] ?? null;

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            http_response_code(403);
            require BASE_PATH . '/app/views/layouts/header.php';
            require BASE_PATH . '/app/views/errors/403.php';
            require BASE_PATH . '/app/views/layouts/footer.php';
            exit;
        }
    }
}

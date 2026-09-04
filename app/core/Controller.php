<?php

/**
 * Controller
 *
 * Clase base de la que heredan todos los controladores. Centraliza dos
 * operaciones comunes: renderizar una vista dentro del layout compartido
 * y redirigir a otra acción interna. Ningún controlador concreto debe
 * hacer `include` de una vista directamente ni ejecutar SQL: la vista solo
 * recibe datos ya listos y el acceso a datos vive en los modelos.
 */
abstract class Controller
{
    /**
     * Renderiza una vista dentro del layout (header + nav según el rol +
     * footer).
     *
     * @param string $vista Ruta relativa a app/views sin extensión, ej. "usuario/crear".
     * @param array  $datos Variables que quedarán disponibles dentro de la vista.
     */
    protected function render(string $vista, array $datos = []): void
    {
        // extract() convierte cada clave del arreglo en una variable local
        // (ej. ['usuario' => $u] pasa a ser la variable $usuario) visible
        // para la vista incluida a continuación.
        extract($datos);

        $rutaVista = BASE_PATH . '/app/views/' . $vista . '.php';

        require BASE_PATH . '/app/views/layouts/header.php';

        if (is_file($rutaVista)) {
            require $rutaVista;
        } else {
            require BASE_PATH . '/app/views/errors/404.php';
        }

        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    protected function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }
}

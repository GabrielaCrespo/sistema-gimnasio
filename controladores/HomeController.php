<?php

/**
 * HomeController
 *
 * Controla la portada del sistema. No exige sesión: si el visitante no
 * está autenticado ve un landing con enlaces a iniciar sesión (CU01) o
 * crear una cuenta de cliente (CU02); si ya inició sesión, la misma vista actúa como
 * panel de bienvenida con accesos rápidos según su rol.
 */
class HomeController
{
    /** Portada pública o panel de bienvenida, según haya sesión activa. */
    public function index(): void
    {
        $this->render();
    }

    /** Muestra el panel de bienvenida (vistas/dashboard.php). */
    private function render(): void
    {
        require BASE_PATH . '/vistas/dashboard.php';
    }
}

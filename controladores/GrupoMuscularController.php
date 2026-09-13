<?php

/**
 * GrupoMuscularController
 *
 * CU03 - Administrar catálogo de grupos musculares. Accesible por
 * administrador e instructor: ambos mantienen este catálogo base, que
 * luego CU04 usa para clasificar ejercicios.
 */
class GrupoMuscularController
{
    private GrupoMuscular $grupoMuscularModelo;

    public function __construct()
    {
        $this->grupoMuscularModelo = new GrupoMuscular();
    }

    /** Todas las acciones de este controlador comparten el mismo control de acceso. */
    private function verificarAcceso(): void
    {
        $this->requireRole(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $usuarioSesion = $_SESSION['user'];
        $grupos = $this->grupoMuscularModelo->listarTodos();

        require BASE_PATH . '/vistas/grupo_muscular/listar.php';
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];

        require BASE_PATH . '/vistas/grupo_muscular/crear.php';
    }

    public function guardar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('grupoMuscular', 'crear');
            return;
        }

        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $descripcion = $_POST['descripcion'] ?? $_GET['descripcion'] ?? '';
        if (is_string($descripcion)) { $descripcion = trim($descripcion); }

        $error = $this->validar($nombre, null);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $datos = ['nombre' => $nombre, 'descripcion' => $descripcion];

            require BASE_PATH . '/vistas/grupo_muscular/crear.php';
            return;
        }

        $this->grupoMuscularModelo->crear($nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;

        require BASE_PATH . '/vistas/grupo_muscular/editar.php';
    }

    public function actualizar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            $this->paginaNoEncontrada();
            return;
        }

        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $descripcion = $_POST['descripcion'] ?? $_GET['descripcion'] ?? '';
        if (is_string($descripcion)) { $descripcion = trim($descripcion); }

        $error = $this->validar($nombre, $id);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $grupo = ['id_grupo_muscular' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion];

            require BASE_PATH . '/vistas/grupo_muscular/editar.php';
            return;
        }

        $this->grupoMuscularModelo->actualizar($id, $nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function eliminar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('grupoMuscular', 'index');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $this->grupoMuscularModelo->eliminar($id);

        $this->redirect('grupoMuscular', 'index');
    }

    /** El nombre es obligatorio y único en el catálogo (coincide con la restricción UNIQUE de la tabla). */
    private function validar(string $nombre, ?int $idAExcluir): ?string
    {
        if ($nombre === '') {
            return 'El nombre es obligatorio.';
        }

        if ($this->grupoMuscularModelo->existeNombre($nombre, $idAExcluir)) {
            return 'Ya existe un grupo muscular con ese nombre.';
        }

        return null;
    }

    /** Carga la vista de error 404 cuando se pide un id_grupo_muscular que no existe. */
    private function paginaNoEncontrada(): void
    {
        http_response_code(404);
        require BASE_PATH . '/vistas/404.php';
    }

    /** Exige sesión activa; si no la hay, redirige al login y detiene la ejecución. */
    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('login', 'login');
        }
    }

    /** Exige sesión activa y que el rol en sesión esté entre los permitidos; si no, corta con 403. */
    private function requireRole(array $rolesPermitidos): void
    {
        $this->requireAuth();

        $rolActual = $_SESSION['user']['rol'] ?? null;

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            http_response_code(403);
            require BASE_PATH . '/vistas/403.php';
            exit;
        }
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}

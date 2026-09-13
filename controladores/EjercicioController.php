<?php

/**
 * EjercicioController
 *
 * CU04 - Administrar catálogo de ejercicios, incluida su relación con uno
 * o varios grupos musculares. Accesible por administrador e instructor.
 */
class EjercicioController
{
    private Ejercicio $ejercicioModelo;
    private GrupoMuscular $grupoMuscularModelo;
    private EjercicioGrupoMuscular $ejercicioGrupoModelo;

    public function __construct()
    {
        $this->ejercicioModelo = new Ejercicio();
        $this->grupoMuscularModelo = new GrupoMuscular();
        $this->ejercicioGrupoModelo = new EjercicioGrupoMuscular();
    }

    /** Acceso de gestión del catálogo (crear/editar/eliminar): solo administrador e instructor. */
    private function verificarAcceso(): void
    {
        $this->requireRole(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $ejercicios = $this->ejercicioModelo->listarTodos();

        require BASE_PATH . '/vistas/ejercicio/listar.php';
    }

    /** El detalle de un ejercicio también lo puede consultar el cliente, para ver sus instrucciones, beneficios y video. */
    public function ver(): void
    {
        $this->requireRole(['administrador', 'instructor', 'cliente']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $grupos = $this->ejercicioGrupoModelo->listarGruposPorEjercicio($id);

        require BASE_PATH . '/vistas/ejercicio/ver.php';
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];
        $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();
        $gruposSeleccionados = [];

        require BASE_PATH . '/vistas/ejercicio/crear.php';
    }

    public function guardar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('ejercicio', 'crear');
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        [$video, $errorVideo] = $this->ejercicioModelo->guardarVideo($_FILES['video'] ?? null, null);
        [$imagen1, $errorImagen1] = $this->ejercicioModelo->guardarImagen($_FILES['imagen_1'] ?? null, null);
        [$imagen2, $errorImagen2] = $this->ejercicioModelo->guardarImagen($_FILES['imagen_2'] ?? null, null);
        $error = $errorVideo ?? $errorImagen1 ?? $errorImagen2 ?? $this->validar($datos['nombre'], null);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/crear.php';
            return;
        }

        $datos['url_video'] = $video;
        $datos['url_imagen_1'] = $imagen1;
        $datos['url_imagen_2'] = $imagen2;

        $idEjercicio = $this->ejercicioModelo->crear($datos);
        $this->ejercicioGrupoModelo->asociar($idEjercicio, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();
        $gruposSeleccionados = array_column($this->ejercicioGrupoModelo->listarGruposPorEjercicio($id), 'id_grupo_muscular');

        require BASE_PATH . '/vistas/ejercicio/editar.php';
    }

    public function actualizar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicioExistente = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicioExistente) {
            $this->paginaNoEncontrada();
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        [$video, $errorVideo] = $this->ejercicioModelo->guardarVideo($_FILES['video'] ?? null, $ejercicioExistente['url_video']);
        [$imagen1, $errorImagen1] = $this->ejercicioModelo->guardarImagen($_FILES['imagen_1'] ?? null, $ejercicioExistente['url_imagen_1']);
        [$imagen2, $errorImagen2] = $this->ejercicioModelo->guardarImagen($_FILES['imagen_2'] ?? null, $ejercicioExistente['url_imagen_2']);
        $error = $errorVideo ?? $errorImagen1 ?? $errorImagen2 ?? $this->validar($datos['nombre'], $id);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $ejercicio = [
                'id_ejercicio' => $id,
                ...$datos,
                'url_video' => $ejercicioExistente['url_video'],
                'url_imagen_1' => $ejercicioExistente['url_imagen_1'],
                'url_imagen_2' => $ejercicioExistente['url_imagen_2'],
            ];
            $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/editar.php';
            return;
        }

        $datos['url_video'] = $video;
        $datos['url_imagen_1'] = $imagen1;
        $datos['url_imagen_2'] = $imagen2;

        $this->ejercicioModelo->actualizar($id, $datos);
        $this->ejercicioGrupoModelo->asociar($id, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function eliminar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('ejercicio', 'index');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;

        if (!$this->ejercicioModelo->eliminar($id)) {
            // El ejercicio ya forma parte de alguna rutina (DETALLE_RUTINA
            // lo referencia con ON DELETE RESTRICT): no se puede borrar.
            $usuarioSesion = $_SESSION['user'];
            $error = 'No se puede eliminar: el ejercicio está siendo usado en una o más rutinas.';
            $ejercicios = $this->ejercicioModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/listar.php';
            return;
        }

        $this->redirect('ejercicio', 'index');
    }

    /** Extrae y normaliza los campos del formulario de ejercicio (crear o editar). El video y las imágenes se guardan aparte, ver Ejercicio::guardarVideo() y Ejercicio::guardarImagen(). */
    private function datosFormulario(): array
    {
        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $descripcion = $_POST['descripcion'] ?? $_GET['descripcion'] ?? '';
        if (is_string($descripcion)) { $descripcion = trim($descripcion); }
        $beneficio = $_POST['beneficio'] ?? $_GET['beneficio'] ?? '';
        if (is_string($beneficio)) { $beneficio = trim($beneficio); }
        $indicaciones = $_POST['indicaciones'] ?? $_GET['indicaciones'] ?? '';
        if (is_string($indicaciones)) { $indicaciones = trim($indicaciones); }

        return [
            'nombre' => $nombre,
            'descripcion' => $descripcion ?: null,
            'beneficio' => $beneficio ?: null,
            'indicaciones' => $indicaciones ?: null,
        ];
    }

    /** El nombre es obligatorio y único en el catálogo (coincide con la restricción UNIQUE de la tabla). */
    private function validar(string $nombre, ?int $idAExcluir): ?string
    {
        if ($nombre === '') {
            return 'El nombre es obligatorio.';
        }

        if ($this->ejercicioModelo->existeNombre($nombre, $idAExcluir)) {
            return 'Ya existe un ejercicio con ese nombre.';
        }

        return null;
    }

    /** Carga la vista de error 404 cuando se pide un id_ejercicio que no existe. */
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

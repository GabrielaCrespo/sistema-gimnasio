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

    private function verificarAcceso(): void
    {
        $this->requireRole(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $ejercicios = $this->ejercicioModelo->listarTodos();
        $this->render('listar', ['ejercicios' => $ejercicios, 'error' => null]);
    }

    public function ver(): void
    {
        $this->verificarAcceso();

        $id = (int) input('id', 0);
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $grupos = $this->ejercicioGrupoModelo->listarGruposPorEjercicio($id);
        $this->render('ver', ['ejercicio' => $ejercicio, 'grupos' => $grupos]);
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $this->render('crear', [
            'error' => null,
            'datos' => [],
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => [],
        ]);
    }

    public function guardar(): void
    {
        $this->verificarAcceso();

        if (!esPost()) {
            $this->redirect('ejercicio', 'crear');
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        $error = $this->validar($datos['nombre'], null);

        if ($error !== null) {
            $this->render('crear', [
                'error' => $error,
                'datos' => $datos,
                'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
                'gruposSeleccionados' => $gruposSeleccionados,
            ]);
            return;
        }

        $idEjercicio = $this->ejercicioModelo->crear($datos);
        $this->ejercicioGrupoModelo->asociar($idEjercicio, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = (int) input('id', 0);
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $gruposSeleccionados = array_column($this->ejercicioGrupoModelo->listarGruposPorEjercicio($id), 'id_grupo_muscular');

        $this->render('editar', [
            'error' => null,
            'ejercicio' => $ejercicio,
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => $gruposSeleccionados,
        ]);
    }

    public function actualizar(): void
    {
        $this->verificarAcceso();

        $id = (int) input('id', 0);
        $ejercicioExistente = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicioExistente) {
            $this->paginaNoEncontrada();
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        $error = $this->validar($datos['nombre'], $id);

        if ($error !== null) {
            $this->render('editar', [
                'error' => $error,
                'ejercicio' => ['id_ejercicio' => $id, ...$datos],
                'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
                'gruposSeleccionados' => $gruposSeleccionados,
            ]);
            return;
        }

        $this->ejercicioModelo->actualizar($id, $datos);
        $this->ejercicioGrupoModelo->asociar($id, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function eliminar(): void
    {
        $this->verificarAcceso();

        if (!esPost()) {
            $this->redirect('ejercicio', 'index');
            return;
        }

        $id = (int) input('id', 0);

        if (!$this->ejercicioModelo->eliminar($id)) {
            // El ejercicio ya forma parte de alguna rutina (DETALLE_RUTINA
            // lo referencia con ON DELETE RESTRICT): no se puede borrar.
            $this->render('listar', [
                'ejercicios' => $this->ejercicioModelo->listarTodos(),
                'error' => 'No se puede eliminar: el ejercicio está siendo usado en una o más rutinas.',
            ]);
            return;
        }

        $this->redirect('ejercicio', 'index');
    }

    /** Extrae y normaliza los campos del formulario de ejercicio (crear o editar). */
    private function datosFormulario(): array
    {
        return [
            'nombre' => input('nombre', ''),
            'descripcion' => input('descripcion', '') ?: null,
            'beneficio' => input('beneficio', '') ?: null,
            'indicaciones' => input('indicaciones', '') ?: null,
            'url_video' => input('url_video', '') ?: null,
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

    /** Muestra un error 404 minimal cuando se pide un id_ejercicio que no existe. */
    private function paginaNoEncontrada(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title><link rel="stylesheet" href="/style.css"></head><body><main class="contenedor"><section class="error-pagina"><h1>404 &mdash; Página no encontrada</h1><p>La página que buscas no existe.</p><a class="boton" href="/index.php">Volver al inicio</a></section></main></body></html>';
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
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title><link rel="stylesheet" href="/style.css"></head><body><main class="contenedor"><section class="error-pagina"><h1>403 &mdash; Acceso denegado</h1><p>No tienes permisos para acceder a esta sección del sistema.</p><a class="boton" href="/index.php">Volver al inicio</a></section></main></body></html>';
            exit;
        }
    }

    /** Muestra la vista de ejercicio (vistas/ejercicio.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/ejercicio.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }
}

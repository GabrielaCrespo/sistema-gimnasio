<?php

/**
 * EjercicioController
 *
 * CU04 - Administrar catálogo de ejercicios, incluida su relación con uno
 * o varios grupos musculares. Accesible por administrador e instructor.
 */
class EjercicioController extends Controller
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
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $ejercicios = $this->ejercicioModelo->listarTodos();
        $this->render('ejercicio/index', ['ejercicios' => $ejercicios, 'error' => null]);
    }

    public function ver(Request $request): void
    {
        $this->verificarAcceso();

        $id = (int) $request->input('id', 0);
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $grupos = $this->ejercicioGrupoModelo->listarGruposPorEjercicio($id);
        $this->render('ejercicio/ver', ['ejercicio' => $ejercicio, 'grupos' => $grupos]);
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $this->render('ejercicio/crear', [
            'error' => null,
            'datos' => [],
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => [],
        ]);
    }

    public function guardar(Request $request): void
    {
        $this->verificarAcceso();

        if (!$request->esPost()) {
            $this->redirect('ejercicio', 'crear');
            return;
        }

        $datos = $this->datosFormulario($request);
        $gruposSeleccionados = array_map('intval', $request->todoPost()['grupos'] ?? []);

        $error = $this->validar($datos['nombre'], null);

        if ($error !== null) {
            $this->render('ejercicio/crear', [
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

    public function editar(Request $request): void
    {
        $this->verificarAcceso();

        $id = (int) $request->input('id', 0);
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $gruposSeleccionados = array_column($this->ejercicioGrupoModelo->listarGruposPorEjercicio($id), 'id_grupo_muscular');

        $this->render('ejercicio/editar', [
            'error' => null,
            'ejercicio' => $ejercicio,
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => $gruposSeleccionados,
        ]);
    }

    public function actualizar(Request $request): void
    {
        $this->verificarAcceso();

        $id = (int) $request->input('id', 0);
        $ejercicioExistente = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicioExistente) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $datos = $this->datosFormulario($request);
        $gruposSeleccionados = array_map('intval', $request->todoPost()['grupos'] ?? []);

        $error = $this->validar($datos['nombre'], $id);

        if ($error !== null) {
            $this->render('ejercicio/editar', [
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

    public function eliminar(Request $request): void
    {
        $this->verificarAcceso();

        if (!$request->esPost()) {
            $this->redirect('ejercicio', 'index');
            return;
        }

        $id = (int) $request->input('id', 0);

        if (!$this->ejercicioModelo->eliminar($id)) {
            // El ejercicio ya forma parte de alguna rutina (DETALLE_RUTINA
            // lo referencia con ON DELETE RESTRICT): no se puede borrar.
            $this->render('ejercicio/index', [
                'ejercicios' => $this->ejercicioModelo->listarTodos(),
                'error' => 'No se puede eliminar: el ejercicio está siendo usado en una o más rutinas.',
            ]);
            return;
        }

        $this->redirect('ejercicio', 'index');
    }

    /** Extrae y normaliza los campos del formulario de ejercicio (crear o editar). */
    private function datosFormulario(Request $request): array
    {
        return [
            'nombre' => $request->input('nombre', ''),
            'descripcion' => $request->input('descripcion', '') ?: null,
            'beneficio' => $request->input('beneficio', '') ?: null,
            'indicaciones' => $request->input('indicaciones', '') ?: null,
            'url_video' => $request->input('url_video', '') ?: null,
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
}

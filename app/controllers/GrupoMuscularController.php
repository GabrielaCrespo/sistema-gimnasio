<?php

/**
 * GrupoMuscularController
 *
 * CU03 - Administrar catálogo de grupos musculares. Accesible por
 * administrador e instructor: ambos mantienen este catálogo base, que
 * luego CU04 usa para clasificar ejercicios.
 */
class GrupoMuscularController extends Controller
{
    private GrupoMuscular $grupoMuscularModelo;

    public function __construct()
    {
        $this->grupoMuscularModelo = new GrupoMuscular();
    }

    /** Todas las acciones de este controlador comparten el mismo control de acceso. */
    private function verificarAcceso(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $grupos = $this->grupoMuscularModelo->listarTodos();
        $this->render('grupo_muscular/index', ['grupos' => $grupos]);
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $this->render('grupo_muscular/crear', ['error' => null, 'datos' => []]);
    }

    public function guardar(Request $request): void
    {
        $this->verificarAcceso();

        if (!$request->esPost()) {
            $this->redirect('grupoMuscular', 'crear');
            return;
        }

        $nombre = $request->input('nombre', '');
        $descripcion = $request->input('descripcion', '');

        $error = $this->validar($nombre, null);

        if ($error !== null) {
            $this->render('grupo_muscular/crear', ['error' => $error, 'datos' => ['nombre' => $nombre, 'descripcion' => $descripcion]]);
            return;
        }

        $this->grupoMuscularModelo->crear($nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function editar(Request $request): void
    {
        $this->verificarAcceso();

        $id = (int) $request->input('id', 0);
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('grupo_muscular/editar', ['error' => null, 'grupo' => $grupo]);
    }

    public function actualizar(Request $request): void
    {
        $this->verificarAcceso();

        $id = (int) $request->input('id', 0);
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $nombre = $request->input('nombre', '');
        $descripcion = $request->input('descripcion', '');

        $error = $this->validar($nombre, $id);

        if ($error !== null) {
            $this->render('grupo_muscular/editar', [
                'error' => $error,
                'grupo' => ['id_grupo_muscular' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion],
            ]);
            return;
        }

        $this->grupoMuscularModelo->actualizar($id, $nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function eliminar(Request $request): void
    {
        $this->verificarAcceso();

        if (!$request->esPost()) {
            $this->redirect('grupoMuscular', 'index');
            return;
        }

        $id = (int) $request->input('id', 0);
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
}

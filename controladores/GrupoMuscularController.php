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

        $grupos = $this->grupoMuscularModelo->listarTodos();
        $this->render('listar', ['grupos' => $grupos]);
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $this->render('crear', ['error' => null, 'datos' => []]);
    }

    public function guardar(): void
    {
        $this->verificarAcceso();

        if (!esPost()) {
            $this->redirect('grupoMuscular', 'crear');
            return;
        }

        $nombre = input('nombre', '');
        $descripcion = input('descripcion', '');

        $error = $this->validar($nombre, null);

        if ($error !== null) {
            $this->render('crear', ['error' => $error, 'datos' => ['nombre' => $nombre, 'descripcion' => $descripcion]]);
            return;
        }

        $this->grupoMuscularModelo->crear($nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = (int) input('id', 0);
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            $this->paginaNoEncontrada();
            return;
        }

        $this->render('editar', ['error' => null, 'grupo' => $grupo]);
    }

    public function actualizar(): void
    {
        $this->verificarAcceso();

        $id = (int) input('id', 0);
        $grupo = $this->grupoMuscularModelo->buscarPorId($id);

        if (!$grupo) {
            $this->paginaNoEncontrada();
            return;
        }

        $nombre = input('nombre', '');
        $descripcion = input('descripcion', '');

        $error = $this->validar($nombre, $id);

        if ($error !== null) {
            $this->render('editar', [
                'error' => $error,
                'grupo' => ['id_grupo_muscular' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion],
            ]);
            return;
        }

        $this->grupoMuscularModelo->actualizar($id, $nombre, $descripcion !== '' ? $descripcion : null);

        $this->redirect('grupoMuscular', 'index');
    }

    public function eliminar(): void
    {
        $this->verificarAcceso();

        if (!esPost()) {
            $this->redirect('grupoMuscular', 'index');
            return;
        }

        $id = (int) input('id', 0);
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

    /** Muestra un error 404 minimal cuando se pide un id_grupo_muscular que no existe. */
    private function paginaNoEncontrada(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title></head><body style="margin:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;background:#f6f5f2;color:#24211c;"><main style="max-width:1100px;margin:0 auto;padding:32px 20px 56px;"><section style="text-align:center;padding:72px 20px;background:#fff;border:1px solid #e6e2da;border-top:3px solid #d9782e;border-radius:10px;box-shadow:0 1px 2px rgba(20,15,10,.08);"><h1 style="font-size:1.6rem;margin-bottom:8px;">404 &mdash; Página no encontrada</h1><p style="color:#6c6459;margin-bottom:20px;">La página que buscas no existe.</p><a style="display:inline-flex;padding:10px 18px;border-radius:6px;background:#d9782e;color:#fff;font-weight:700;text-decoration:none;" href="/index.php">Volver al inicio</a></section></main></body></html>';
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
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title></head><body style="margin:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;background:#f6f5f2;color:#24211c;"><main style="max-width:1100px;margin:0 auto;padding:32px 20px 56px;"><section style="text-align:center;padding:72px 20px;background:#fff;border:1px solid #e6e2da;border-top:3px solid #d9782e;border-radius:10px;box-shadow:0 1px 2px rgba(20,15,10,.08);"><h1 style="font-size:1.6rem;margin-bottom:8px;">403 &mdash; Acceso denegado</h1><p style="color:#6c6459;margin-bottom:20px;">No tienes permisos para acceder a esta sección del sistema.</p><a style="display:inline-flex;padding:10px 18px;border-radius:6px;background:#d9782e;color:#fff;font-weight:700;text-decoration:none;" href="/index.php">Volver al inicio</a></section></main></body></html>';
            exit;
        }
    }

    /** Muestra la vista de grupo muscular (vistas/grupo_muscular.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/grupo_muscular.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }
}

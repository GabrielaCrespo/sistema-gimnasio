<?php

/**
 * InstructorController
 *
 * CU02 - Gestionar cuentas de instructor. El administrador tiene control
 * total sobre estas cuentas (crear, listar, editar, activar/desactivar),
 * ya que solo un administrador puede dar de alta a un instructor. El propio
 * instructor puede ver y editar su perfil, incluida su especialidad.
 */
class InstructorController
{
    private Instructor $instructorModelo;
    private Cliente $clienteModelo;

    public function __construct()
    {
        $this->instructorModelo = new Instructor();
        $this->clienteModelo = new Cliente();
    }

    /** Lista todas las cuentas de instructor. Solo el administrador gestiona cuentas ajenas. */
    public function index(): void
    {
        $this->requireRole(['administrador']);

        $instructores = $this->instructorModelo->listarTodos();
        $this->render('listar', ['instructores' => $instructores]);
    }

    /** Muestra el formulario de creación de una nueva cuenta de instructor. */
    public function crear(): void
    {
        $this->requireRole(['administrador']);

        $this->render('crear', ['error' => null, 'datos' => []]);
    }

    /** Procesa la creación de una cuenta de instructor. */
    public function guardar(): void
    {
        $this->requireRole(['administrador']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('instructor', 'crear');
            return;
        }

        $ci = $_POST['ci'] ?? $_GET['ci'] ?? '';
        if (is_string($ci)) { $ci = trim($ci); }
        $nombres = $_POST['nombres'] ?? $_GET['nombres'] ?? '';
        if (is_string($nombres)) { $nombres = trim($nombres); }
        $apellidos = $_POST['apellidos'] ?? $_GET['apellidos'] ?? '';
        if (is_string($apellidos)) { $apellidos = trim($apellidos); }
        $fechaNacimiento = $_POST['fecha_nacimiento'] ?? $_GET['fecha_nacimiento'] ?? '';
        if (is_string($fechaNacimiento)) { $fechaNacimiento = trim($fechaNacimiento); }
        $correo = $_POST['correo'] ?? $_GET['correo'] ?? '';
        if (is_string($correo)) { $correo = trim($correo); }
        $especialidad = $_POST['especialidad'] ?? $_GET['especialidad'] ?? '';
        if (is_string($especialidad)) { $especialidad = trim($especialidad); }

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
            'especialidad' => $especialidad,
        ];
        $password = $_POST['password'] ?? $_GET['password'] ?? '';
        if (is_string($password)) { $password = trim($password); }

        $error = $this->validarDatosBasicos($datos, null, $password);

        if ($error !== null) {
            $this->render('crear', ['error' => $error, 'datos' => $datos]);
            return;
        }

        $this->instructorModelo->crear([...$datos, 'password' => $password]);

        $this->redirect('instructor', 'index');
    }

    /** Muestra el formulario de edición de una cuenta de instructor existente. */
    public function editar(): void
    {
        $this->requireRole(['administrador']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $instructor = $this->instructorModelo->buscarPorId($id);

        if (!$instructor) {
            $this->paginaNoEncontrada();
            return;
        }

        $this->render('editar', ['error' => null, 'instructor' => $instructor]);
    }

    /** Procesa la edición de datos personales y especialidad. La contraseña no se cambia desde este formulario. */
    public function actualizar(): void
    {
        $this->requireRole(['administrador']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $instructor = $this->instructorModelo->buscarPorId($id);

        if (!$instructor) {
            $this->paginaNoEncontrada();
            return;
        }

        $ci = $_POST['ci'] ?? $_GET['ci'] ?? '';
        if (is_string($ci)) { $ci = trim($ci); }
        $nombres = $_POST['nombres'] ?? $_GET['nombres'] ?? '';
        if (is_string($nombres)) { $nombres = trim($nombres); }
        $apellidos = $_POST['apellidos'] ?? $_GET['apellidos'] ?? '';
        if (is_string($apellidos)) { $apellidos = trim($apellidos); }
        $fechaNacimiento = $_POST['fecha_nacimiento'] ?? $_GET['fecha_nacimiento'] ?? '';
        if (is_string($fechaNacimiento)) { $fechaNacimiento = trim($fechaNacimiento); }
        $correo = $_POST['correo'] ?? $_GET['correo'] ?? '';
        if (is_string($correo)) { $correo = trim($correo); }
        $especialidad = $_POST['especialidad'] ?? $_GET['especialidad'] ?? '';
        if (is_string($especialidad)) { $especialidad = trim($especialidad); }

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
            'especialidad' => $especialidad,
        ];

        $error = $this->validarDatosBasicos($datos, $id, null);

        if ($error !== null) {
            $this->render('editar', ['error' => $error, 'instructor' => [...$instructor, ...$datos]]);
            return;
        }

        $this->instructorModelo->actualizar($id, $datos);

        $this->redirect('instructor', 'index');
    }

    /** Activa o desactiva una cuenta de instructor: reemplaza al "eliminar" por integridad referencial (ON DELETE RESTRICT). */
    public function cambiarEstado(): void
    {
        $this->requireRole(['administrador']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('instructor', 'index');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $estado = $_POST['estado'] ?? $_GET['estado'] ?? '0';
        if (is_string($estado)) { $estado = trim($estado); }
        $nuevoEstado = $estado === '1';

        $this->instructorModelo->cambiarEstado($id, $nuevoEstado);

        $this->redirect('instructor', 'index');
    }

    /** Muestra el perfil propio del instructor autenticado. */
    public function perfil(): void
    {
        $this->requireRole(['instructor']);

        $id = (int) $_SESSION['user']['id'];

        $this->render('perfil', [
            'error' => null,
            'exito' => null,
            'instructor' => $this->instructorModelo->buscarPorId($id),
        ]);
    }

    /** Procesa la edición del perfil propio: datos personales y especialidad. */
    public function actualizarPerfil(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('instructor', 'perfil');
            return;
        }

        $id = (int) $_SESSION['user']['id'];

        $ci = $_POST['ci'] ?? $_GET['ci'] ?? '';
        if (is_string($ci)) { $ci = trim($ci); }
        $nombres = $_POST['nombres'] ?? $_GET['nombres'] ?? '';
        if (is_string($nombres)) { $nombres = trim($nombres); }
        $apellidos = $_POST['apellidos'] ?? $_GET['apellidos'] ?? '';
        if (is_string($apellidos)) { $apellidos = trim($apellidos); }
        $fechaNacimiento = $_POST['fecha_nacimiento'] ?? $_GET['fecha_nacimiento'] ?? '';
        if (is_string($fechaNacimiento)) { $fechaNacimiento = trim($fechaNacimiento); }
        $correo = $_POST['correo'] ?? $_GET['correo'] ?? '';
        if (is_string($correo)) { $correo = trim($correo); }
        $especialidad = $_POST['especialidad'] ?? $_GET['especialidad'] ?? '';
        if (is_string($especialidad)) { $especialidad = trim($especialidad); }

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
            'especialidad' => $especialidad,
        ];

        $error = $this->validarDatosBasicos($datos, $id, null);

        if ($error !== null) {
            $this->render('perfil', [
                'error' => $error,
                'exito' => null,
                'instructor' => [...$this->instructorModelo->buscarPorId($id), ...$datos],
            ]);
            return;
        }

        $this->instructorModelo->actualizar($id, $datos);

        // Refleja el nombre/correo actualizados en la sesión (se muestran en la barra de navegación).
        $_SESSION['user']['nombre'] = $datos['nombres'] . ' ' . $datos['apellidos'];
        $_SESSION['user']['correo'] = $datos['correo'];

        $this->render('perfil', [
            'error' => null,
            'exito' => 'Tus datos se actualizaron correctamente.',
            'instructor' => $this->instructorModelo->buscarPorId($id),
        ]);
    }

    /**
     * Valida CI/nombres/apellidos/fecha/correo/especialidad (y contraseña al crear) de un instructor.
     * $password solo se recibe (y valida) al crear una cuenta nueva.
     */
    private function validarDatosBasicos(array $datos, ?int $idAExcluir, ?string $password): ?string
    {
        foreach (['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'correo', 'especialidad'] as $campo) {
            if ($datos[$campo] === '') {
                return 'Todos los campos son obligatorios.';
            }
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        if ($password !== null && strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }

        // El correo/CI deben ser únicos en todo el sistema, no solo en INSTRUCTOR.
        if ($datos['correo'] === Config::get('ADMIN_CORREO', '')
            || $this->instructorModelo->existeCorreo($datos['correo'], $idAExcluir)
            || $this->clienteModelo->existeCorreo($datos['correo'])
        ) {
            return 'Ya existe una cuenta registrada con ese correo.';
        }

        if ($this->instructorModelo->existeCi($datos['ci'], $idAExcluir) || $this->clienteModelo->existeCi($datos['ci'])) {
            return 'Ya existe una cuenta registrada con ese CI.';
        }

        return null;
    }

    /** Muestra un error 404 minimal cuando se pide un id_instructor que no existe. */
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

    /** Muestra la vista de instructor (vistas/instructor.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/instructor.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}

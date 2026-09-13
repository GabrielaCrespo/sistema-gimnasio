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
    private Administrador $administradorModelo;

    public function __construct()
    {
        $this->instructorModelo = new Instructor();
        $this->clienteModelo = new Cliente();
        $this->administradorModelo = new Administrador();
    }

    /** Lista todas las cuentas de instructor. Solo el administrador gestiona cuentas ajenas. */
    public function index(): void
    {
        $this->requireRole(['administrador']);

        $usuarioSesion = $_SESSION['user'];
        $instructores = $this->instructorModelo->listarTodos();

        require BASE_PATH . '/vistas/instructor/listar.php';
    }

    /** Muestra el formulario de creación de una nueva cuenta de instructor. */
    public function crear(): void
    {
        $this->requireRole(['administrador']);

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];

        require BASE_PATH . '/vistas/instructor/crear.php';
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
            $usuarioSesion = $_SESSION['user'];

            require BASE_PATH . '/vistas/instructor/crear.php';
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

        $usuarioSesion = $_SESSION['user'];
        $error = null;

        require BASE_PATH . '/vistas/instructor/editar.php';
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
            $usuarioSesion = $_SESSION['user'];
            $instructor = [...$instructor, ...$datos];

            require BASE_PATH . '/vistas/instructor/editar.php';
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

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $exito = null;
        $instructor = $this->instructorModelo->buscarPorId($id);

        require BASE_PATH . '/vistas/instructor/perfil.php';
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
            $usuarioSesion = $_SESSION['user'];
            $exito = null;
            $instructor = [...$this->instructorModelo->buscarPorId($id), ...$datos];

            require BASE_PATH . '/vistas/instructor/perfil.php';
            return;
        }

        $this->instructorModelo->actualizar($id, $datos);

        // Refleja el nombre/correo actualizados en la sesión (se muestran en la barra de navegación).
        $_SESSION['user']['nombre'] = $datos['nombres'] . ' ' . $datos['apellidos'];
        $_SESSION['user']['correo'] = $datos['correo'];

        $usuarioSesion = $_SESSION['user'];
        $exito = 'Tus datos se actualizaron correctamente.';
        $instructor = $this->instructorModelo->buscarPorId($id);

        require BASE_PATH . '/vistas/instructor/perfil.php';
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
        if ($this->administradorModelo->esCorreoDelAdministrador($datos['correo'])
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

    /** Carga la vista de error 404 cuando se pide un id_instructor que no existe. */
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

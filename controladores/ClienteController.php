<?php

/**
 * ClienteController
 *
 * CU02 - Concentra las operaciones relacionadas con clientes: el
 * auto-registro público de cuentas (sin sesión) y, ya autenticado, la
 * gestión que hace el administrador (listar, editar, activar/desactivar)
 * y el propio cliente sobre su perfil, incluida su altura/peso.
 */
class ClienteController
{
    private Cliente $clienteModelo;
    private Instructor $instructorModelo;
    private Administrador $administradorModelo;

    public function __construct()
    {
        $this->clienteModelo = new Cliente();
        $this->instructorModelo = new Instructor();
        $this->administradorModelo = new Administrador();
    }

    /** Lista todas las cuentas de cliente. Solo el administrador gestiona cuentas ajenas. */
    public function index(): void
    {
        $this->requireRole(['administrador']);

        $usuarioSesion = $_SESSION['user'];
        $clientes = $this->clienteModelo->listarTodos();

        require BASE_PATH . '/vistas/cliente/listar.php';
    }

    /** Muestra la vista de auto-registro público (siempre crea una cuenta de tipo cliente). */
    public function register(): void
    {
        // Si ya hay una sesión activa redirije a la portada
        if (!empty($_SESSION['user'])) {
            $this->redirect('login');
        }

        // Envía a la vista de registro con datos vacíos y sin error
        $error = null;
        $datos = [];

        require BASE_PATH . '/vistas/cliente/register.php';
    }

    // Procesa el formulario de registro de un nuevo cliente
    public function crearCuenta(): void
    {
        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('cliente', 'register');
            return;
        }

        // Obtiene los datos del formulario
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

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
        ];
        $password = $_POST['password'] ?? $_GET['password'] ?? '';
        if (is_string($password)) { $password = trim($password); }
        $passwordConfirmacion = $_POST['password_confirmacion'] ?? $_GET['password_confirmacion'] ?? '';
        if (is_string($passwordConfirmacion)) { $passwordConfirmacion = trim($passwordConfirmacion); }

        // Valida los datos del registro
        $error = $this->validarRegistro($datos, $password, $passwordConfirmacion);

        if ($error !== null) {
            require BASE_PATH . '/vistas/cliente/register.php';
            return;
        }

        // Crea la cuenta (el auto-registro siempre es de tipo cliente)
        $this->clienteModelo->crear([
            ...$datos,
            'password' => $password,
        ]);

        // Redirige a la página de login con un mensaje de éxito
        $this->redirect('login', 'login');
    }

    /** Muestra el formulario de edición de una cuenta de cliente existente. */
    public function editar(): void
    {
        $this->requireRole(['administrador']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $cliente = $this->clienteModelo->buscarPorId($id);

        if (!$cliente) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;

        require BASE_PATH . '/vistas/cliente/editar.php';
    }

    /** Procesa la edición de datos personales de un cliente. La contraseña no se cambia desde este formulario. */
    public function actualizar(): void
    {
        $this->requireRole(['administrador']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $cliente = $this->clienteModelo->buscarPorId($id);

        if (!$cliente) {
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

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
        ];

        $error = $this->validarDatosBasicos($datos, $id);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $cliente = [...$cliente, ...$datos];

            require BASE_PATH . '/vistas/cliente/editar.php';
            return;
        }

        $this->clienteModelo->actualizar($id, $datos);

        $this->redirect('cliente', 'index');
    }

    /** Activa o desactiva una cuenta de cliente: reemplaza al "eliminar" por integridad referencial (ON DELETE RESTRICT). */
    public function cambiarEstado(): void
    {
        $this->requireRole(['administrador']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('cliente', 'index');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $estado = $_POST['estado'] ?? $_GET['estado'] ?? '0';
        if (is_string($estado)) { $estado = trim($estado); }
        $nuevoEstado = $estado === '1';

        $this->clienteModelo->cambiarEstado($id, $nuevoEstado);

        $this->redirect('cliente', 'index');
    }

    /** Muestra el perfil propio del cliente autenticado. */
    public function perfil(): void
    {
        $this->requireRole(['cliente']);

        $id = (int) $_SESSION['user']['id'];

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $exito = null;
        $cliente = $this->clienteModelo->buscarPorId($id);

        require BASE_PATH . '/vistas/cliente/perfil.php';
    }

    /** Procesa la edición del perfil propio: datos personales, altura y peso. */
    public function actualizarPerfil(): void
    {
        $this->requireRole(['cliente']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('cliente', 'perfil');
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

        $datos = [
            'ci' => $ci,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'correo' => $correo,
        ];

        $error = $this->validarDatosBasicos($datos, $id);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $exito = null;
            $cliente = [...$this->clienteModelo->buscarPorId($id), ...$datos];

            require BASE_PATH . '/vistas/cliente/perfil.php';
            return;
        }

        $this->clienteModelo->actualizar($id, $datos);

        $altura = $_POST['altura'] ?? $_GET['altura'] ?? '';
        if (is_string($altura)) { $altura = trim($altura); }
        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }
        $this->clienteModelo->actualizarMedidas(
            $id,
            $altura !== '' ? (float) $altura : null,
            $peso !== '' ? (float) $peso : null
        );

        // Refleja el nombre/correo actualizados en la sesión (se muestran en la barra de navegación).
        $_SESSION['user']['nombre'] = $datos['nombres'] . ' ' . $datos['apellidos'];
        $_SESSION['user']['correo'] = $datos['correo'];

        $usuarioSesion = $_SESSION['user'];
        $exito = 'Tus datos se actualizaron correctamente.';
        $cliente = $this->clienteModelo->buscarPorId($id);

        require BASE_PATH . '/vistas/cliente/perfil.php';
    }

    /** Valida los datos del formulario de registro; devuelve el mensaje de error o null si todo está correcto. */
    private function validarRegistro(array $datos, string $password, string $passwordConfirmacion): ?string
    {
        foreach (['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'correo'] as $campo) {
            if ($datos[$campo] === '') {
                return 'Todos los campos son obligatorios.';
            }
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        if (strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }

        if ($password !== $passwordConfirmacion) {
            return 'Las contraseñas no coinciden.';
        }

        // El correo/CI deben ser únicos en todo el sistema, no solo en CLIENTE:
        // de lo contrario, esa cuenta quedaría inaccesible al iniciar sesión.
        if ($this->administradorModelo->esCorreoDelAdministrador($datos['correo'])
            || $this->clienteModelo->existeCorreo($datos['correo'])
            || $this->instructorModelo->existeCorreo($datos['correo'])
        ) {
            return 'Ya existe una cuenta registrada con ese correo.';
        }

        if ($this->clienteModelo->existeCi($datos['ci']) || $this->instructorModelo->existeCi($datos['ci'])) {
            return 'Ya existe una cuenta registrada con ese CI.';
        }

        // Si todo está correcto, devuelve null
        return null;
    }

    /** Valida CI/nombres/apellidos/fecha/correo antes de editar un cliente (propio o ajeno). */
    private function validarDatosBasicos(array $datos, ?int $idAExcluir): ?string
    {
        foreach (['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'correo'] as $campo) {
            if ($datos[$campo] === '') {
                return 'Todos los campos son obligatorios.';
            }
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        // El correo/CI deben ser únicos en todo el sistema, no solo en CLIENTE.
        if ($this->administradorModelo->esCorreoDelAdministrador($datos['correo'])
            || $this->clienteModelo->existeCorreo($datos['correo'], $idAExcluir)
            || $this->instructorModelo->existeCorreo($datos['correo'])
        ) {
            return 'Ya existe una cuenta registrada con ese correo.';
        }

        if ($this->clienteModelo->existeCi($datos['ci'], $idAExcluir) || $this->instructorModelo->existeCi($datos['ci'])) {
            return 'Ya existe una cuenta registrada con ese CI.';
        }

        return null;
    }

    /** Carga la vista de error 404 cuando se pide un id_cliente que no existe. */
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

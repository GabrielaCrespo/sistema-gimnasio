<?php

/**
 * UsuarioController
 *
 * CU02 - Gestionar cuentas de usuario.
 * El administrador tiene control total sobre todas las cuentas (listar, crear cualquier rol, editar, activar/desactivar)
 * Cualquier usuario autenticado puede ver y editar su propio perfil
 */
class UsuarioController
{
    // Modelos que se usan en este Controller: USUARIO, CLIENTE e INSTRUCTOR.
    private Usuario $usuarioModelo;
    private Cliente $clienteModelo;
    private Instructor $instructorModelo;

    // Constructor: inicializa los modelos que se usarán en las acciones del controlador.
    public function __construct()
    {
        $this->usuarioModelo = new Usuario();
        $this->clienteModelo = new Cliente();
        $this->instructorModelo = new Instructor();
    }

    /** Lista todas las cuentas del sistema. Solo el administrador gestiona cuentas ajenas. */
    public function index(): void
    {
        $this->requireRole(['administrador']);

        $usuarios = $this->usuarioModelo->listarTodos();
        $this->render('listar', ['usuarios' => $usuarios]);
    }

    // Muestra el formulario de creación de una nueva cuenta (administrador, instructor o cliente).
    public function crear(): void
    {
        $this->requireRole(['administrador']);

        $this->render('crear', ['error' => null, 'datos' => []]);
    }

    /** Procesa la creación de una cuenta nueva (administrador, instructor o cliente). */
    public function guardar(): void
    {
        $this->requireRole(['administrador']);

        if (!esPost()) {
            $this->redirect('usuario', 'crear');
            return;
        }

        // Recoge los datos del formulario y valida que estén completos y correctos.
        $datos = [
            'ci' => input('ci', ''),
            'nombres' => input('nombres', ''),
            'apellidos' => input('apellidos', ''),
            'fecha_nacimiento' => input('fecha_nacimiento', ''),
            'correo' => input('correo', ''),
            'rol' => input('rol', ''),
        ];
        $password = input('password', '');
        $especialidad = input('especialidad', '');

        $error = $this->validarDatosBasicos($datos, $password, null);

        if ($error === null && $datos['rol'] === 'instructor' && $especialidad === '') {
            $error = 'La especialidad es obligatoria para un instructor.';
        }

        if ($error !== null) {
            $this->render('crear', ['error' => $error, 'datos' => [...$datos, 'especialidad' => $especialidad]]);
            return;
        }

        $idUsuario = $this->usuarioModelo->crear([
            ...$datos,
            'password' => $password,
        ]);

        // Según el rol elegido se completa la tabla hija correspondiente
        // (herencia por tabla: INSTRUCTOR y CLIENTE comparten PK con USUARIO).

        //si el rol es instructor llama al modelo insuctor para crear la fila en la tabla INSTRUCTOR con la especialidad
        if ($datos['rol'] === 'instructor') {
            $this->instructorModelo->crear($idUsuario, $especialidad);
        }
        //Si el rol es cliente llama al modelo cliente para crear la fila en la tabla CLIENTE con altura y peso nulos
        elseif ($datos['rol'] === 'cliente') {
            $this->clienteModelo->crear($idUsuario, null, null);
        }

        $this->redirect('usuario', 'index');
    }

    /** Muestra el formulario de edición de una cuenta existente. */
    public function editar(): void
    {
        $this->requireRole(['administrador']);

        $id = (int) input('id', 0);
        $usuario = $this->usuarioModelo->buscarPorId($id);

        if (!$usuario) {
            $this->paginaNoEncontrada();
            return;
        }

        $especialidad = $usuario['rol'] === 'instructor'
            ? ($this->instructorModelo->buscarPorId($id)['especialidad'] ?? '')
            : '';

        $this->render('editar', ['error' => null, 'usuario' => $usuario, 'especialidad' => $especialidad]);
    }

    /** Procesa la edición de datos personales. El rol y la contraseña no se cambian desde este formulario. */
    public function actualizar(): void
    {
        $this->requireRole(['administrador']);

        $id = (int) input('id', 0);
        $usuario = $this->usuarioModelo->buscarPorId($id);

        if (!$usuario) {
            $this->paginaNoEncontrada();
            return;
        }

        $datos = [
            'ci' => input('ci', ''),
            'nombres' => input('nombres', ''),
            'apellidos' => input('apellidos', ''),
            'fecha_nacimiento' => input('fecha_nacimiento', ''),
            'correo' => input('correo', ''),
        ];
        $especialidad = input('especialidad', '');

        $error = $this->validarDatosBasicos($datos, null, $id);

        if ($error === null && $usuario['rol'] === 'instructor' && $especialidad === '') {
            $error = 'La especialidad es obligatoria para un instructor.';
        }

        if ($error !== null) {
            $this->render('editar', [
                'error' => $error,
                'usuario' => [...$usuario, ...$datos],
                'especialidad' => $especialidad,
            ]);
            return;
        }

        $this->usuarioModelo->actualizar($id, $datos);

        if ($usuario['rol'] === 'instructor') {
            $this->instructorModelo->actualizar($id, $especialidad);
        }

        $this->redirect('usuario', 'index');
    }

    /** Activa o desactiva una cuenta: reemplaza al "eliminar" para no romper la integridad referencial (ON DELETE RESTRICT). */
    public function cambiarEstado(): void
    {
        $this->requireRole(['administrador']);

        if (!esPost()) {
            $this->redirect('usuario', 'index');
            return;
        }

        $id = (int) input('id', 0);
        $nuevoEstado = input('estado', '0') === '1';

        // Un administrador no puede desactivar su propia cuenta y quedarse sin acceso al sistema.
        if ($id !== (int) $_SESSION['user']['id']) {
            $this->usuarioModelo->cambiarEstado($id, $nuevoEstado);
        }

        $this->redirect('usuario', 'index');
    }

    /** Muestra el perfil propio del usuario autenticado (cualquier rol: administrador, instructor o cliente). */
    public function perfil(): void
    {
        $this->requireAuth();

        $id = (int) $_SESSION['user']['id'];

        $this->render('perfil', array_merge(
            ['error' => null, 'exito' => null],
            $this->datosPerfil($id)
        ));
    }

    /** Procesa la edición del perfil propio: datos personales y, según el rol, altura/peso o especialidad. */
    public function actualizarPerfil(): void
    {
        $this->requireAuth();

        if (!esPost()) {
            $this->redirect('usuario', 'perfil');
            return;
        }

        $id = (int) $_SESSION['user']['id'];
        $usuario = $this->usuarioModelo->buscarPorId($id);

        $datos = [
            'ci' => input('ci', ''),
            'nombres' => input('nombres', ''),
            'apellidos' => input('apellidos', ''),
            'fecha_nacimiento' => input('fecha_nacimiento', ''),
            'correo' => input('correo', ''),
        ];

        $error = $this->validarDatosBasicos($datos, null, $id);

        if ($error !== null) {
            $this->render('perfil', array_merge(
                ['error' => $error, 'exito' => null],
                $this->datosPerfil($id, $datos)
            ));
            return;
        }

        $this->usuarioModelo->actualizar($id, $datos);

        if ($usuario['rol'] === 'cliente') {
            $altura = input('altura', '');
            $peso = input('peso', '');
            $this->clienteModelo->actualizar(
                $id,
                $altura !== '' ? (float) $altura : null,
                $peso !== '' ? (float) $peso : null
            );
        } elseif ($usuario['rol'] === 'instructor') {
            $this->instructorModelo->actualizar($id, input('especialidad', ''));
        }

        // Refleja el nombre/correo actualizados en la sesión (se muestran en la barra de navegación).
        $_SESSION['user']['nombre'] = $datos['nombres'] . ' ' . $datos['apellidos'];
        $_SESSION['user']['correo'] = $datos['correo'];

        $this->render('perfil', array_merge(
            ['error' => null, 'exito' => 'Tus datos se actualizaron correctamente.'],
            $this->datosPerfil($id)
        ));
    }

    /** Devuelve los datos del perfil propio, combinando USUARIO con CLIENTE o INSTRUCTOR según corresponda. */
    private function datosPerfil(int $id, array $sobreescribir = []): array
    {
        $usuario = array_merge($this->usuarioModelo->buscarPorId($id), $sobreescribir);

        return [
            'usuario' => $usuario,
            'clienteInfo' => $usuario['rol'] === 'cliente' ? $this->clienteModelo->buscarPorId($id) : null,
            'instructorInfo' => $usuario['rol'] === 'instructor' ? $this->instructorModelo->buscarPorId($id) : null,
        ];
    }

    /** Valida CI/nombres/apellidos/fecha/correo (y contraseña + rol cuando corresponde) antes de crear o editar. */
    private function validarDatosBasicos(array $datos, ?string $password, ?int $idAExcluir): ?string
    {
        foreach (['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'correo'] as $campo) {
            if ($datos[$campo] === '') {
                return 'Todos los campos son obligatorios.';
            }
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        if ($password !== null) {
            if (strlen($password) < 6) {
                return 'La contraseña debe tener al menos 6 caracteres.';
            }
            if (!in_array($datos['rol'] ?? '', ['administrador', 'instructor', 'cliente'], true)) {
                return 'Selecciona un rol válido.';
            }
        }

        if ($this->usuarioModelo->existeCorreo($datos['correo'], $idAExcluir)) {
            return 'Ya existe una cuenta registrada con ese correo.';
        }

        if ($this->usuarioModelo->existeCi($datos['ci'], $idAExcluir)) {
            return 'Ya existe una cuenta registrada con ese CI.';
        }

        // Todos los datos son válidos.
        return null;
    }

    /** Muestra un error 404 minimal cuando se pide un id_usuario que no existe. */
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

    /** Muestra la vista de usuario (vistas/usuario.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/usuario.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }
}

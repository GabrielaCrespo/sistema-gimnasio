<?php

/**
 * UsuarioController
 *
 * CU02 - Gestionar cuentas de usuario. 
 * El administrador tiene control total sobre todas las cuentas (listar, crear cualquier rol, editar, activar/desactivar)
 * Cualquier usuario autenticado puede ver y editar su propio perfil
 */
class UsuarioController extends Controller
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
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        $usuarios = $this->usuarioModelo->listarTodos();
        $this->render('usuario/index', ['usuarios' => $usuarios]);
    }

    // Muestra el formulario de creación de una nueva cuenta (administrador, instructor o cliente).
    public function crear(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        $this->render('usuario/crear', ['error' => null, 'datos' => []]);
    }

    /** Procesa la creación de una cuenta nueva (administrador, instructor o cliente). */
    public function guardar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        if (!$request->esPost()) {
            $this->redirect('usuario', 'crear');
            return;
        }

        // Recoge los datos del formulario y valida que estén completos y correctos.
        $datos = [
            'ci' => $request->input('ci', ''),
            'nombres' => $request->input('nombres', ''),
            'apellidos' => $request->input('apellidos', ''),
            'fecha_nacimiento' => $request->input('fecha_nacimiento', ''),
            'correo' => $request->input('correo', ''),
            'rol' => $request->input('rol', ''),
        ];
        $password = $request->input('password', '');
        $especialidad = $request->input('especialidad', '');

        $error = $this->validarDatosBasicos($datos, $password, null);

        if ($error === null && $datos['rol'] === 'instructor' && $especialidad === '') {
            $error = 'La especialidad es obligatoria para un instructor.';
        }

        if ($error !== null) {
            $this->render('usuario/crear', ['error' => $error, 'datos' => [...$datos, 'especialidad' => $especialidad]]);
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
    public function editar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        $id = (int) $request->input('id', 0);
        $usuario = $this->usuarioModelo->buscarPorId($id);

        if (!$usuario) {
            $this->renderNoEncontrado();
            return;
        }

        $especialidad = $usuario['rol'] === 'instructor'
            ? ($this->instructorModelo->buscarPorId($id)['especialidad'] ?? '')
            : '';

        $this->render('usuario/editar', ['error' => null, 'usuario' => $usuario, 'especialidad' => $especialidad]);
    }

    /** Procesa la edición de datos personales. El rol y la contraseña no se cambian desde este formulario. */
    public function actualizar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        $id = (int) $request->input('id', 0);
        $usuario = $this->usuarioModelo->buscarPorId($id);

        if (!$usuario) {
            $this->renderNoEncontrado();
            return;
        }

        $datos = [
            'ci' => $request->input('ci', ''),
            'nombres' => $request->input('nombres', ''),
            'apellidos' => $request->input('apellidos', ''),
            'fecha_nacimiento' => $request->input('fecha_nacimiento', ''),
            'correo' => $request->input('correo', ''),
        ];
        $especialidad = $request->input('especialidad', '');

        $error = $this->validarDatosBasicos($datos, null, $id);

        if ($error === null && $usuario['rol'] === 'instructor' && $especialidad === '') {
            $error = 'La especialidad es obligatoria para un instructor.';
        }

        if ($error !== null) {
            $this->render('usuario/editar', [
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
    public function cambiarEstado(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['administrador']);

        if (!$request->esPost()) {
            $this->redirect('usuario', 'index');
            return;
        }

        $id = (int) $request->input('id', 0);
        $nuevoEstado = $request->input('estado', '0') === '1';

        // Un administrador no puede desactivar su propia cuenta y quedarse sin acceso al sistema.
        if ($id !== (int) $_SESSION['user']['id']) {
            $this->usuarioModelo->cambiarEstado($id, $nuevoEstado);
        }

        $this->redirect('usuario', 'index');
    }

    /** Muestra el perfil propio del usuario autenticado (cualquier rol: administrador, instructor o cliente). */
    public function perfil(): void
    {
        AuthMiddleware::handle();

        $id = (int) $_SESSION['user']['id'];

        $this->render('usuario/perfil', array_merge(
            ['error' => null, 'exito' => null],
            $this->datosPerfil($id)
        ));
    }

    /** Procesa la edición del perfil propio: datos personales y, según el rol, altura/peso o especialidad. */
    public function actualizarPerfil(Request $request): void
    {
        AuthMiddleware::handle();

        if (!$request->esPost()) {
            $this->redirect('usuario', 'perfil');
            return;
        }

        $id = (int) $_SESSION['user']['id'];
        $usuario = $this->usuarioModelo->buscarPorId($id);

        $datos = [
            'ci' => $request->input('ci', ''),
            'nombres' => $request->input('nombres', ''),
            'apellidos' => $request->input('apellidos', ''),
            'fecha_nacimiento' => $request->input('fecha_nacimiento', ''),
            'correo' => $request->input('correo', ''),
        ];

        $error = $this->validarDatosBasicos($datos, null, $id);

        if ($error !== null) {
            $this->render('usuario/perfil', array_merge(
                ['error' => $error, 'exito' => null],
                $this->datosPerfil($id, $datos)
            ));
            return;
        }

        $this->usuarioModelo->actualizar($id, $datos);

        if ($usuario['rol'] === 'cliente') {
            $altura = $request->input('altura', '');
            $peso = $request->input('peso', '');
            $this->clienteModelo->actualizar(
                $id,
                $altura !== '' ? (float) $altura : null,
                $peso !== '' ? (float) $peso : null
            );
        } elseif ($usuario['rol'] === 'instructor') {
            $this->instructorModelo->actualizar($id, $request->input('especialidad', ''));
        }

        // Refleja el nombre/correo actualizados en la sesión (se muestran en la barra de navegación).
        $_SESSION['user']['nombre'] = $datos['nombres'] . ' ' . $datos['apellidos'];
        $_SESSION['user']['correo'] = $datos['correo'];

        $this->render('usuario/perfil', array_merge(
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

    /** Renderiza la vista 404 cuando se pide un id_usuario que no existe. */
    private function renderNoEncontrado(): void
    {
        http_response_code(404);
        $this->render('errors/404');
    }
}

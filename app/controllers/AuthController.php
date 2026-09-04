<?php

/**
 * Controlador de Autenticación (AuthController)
 * 
 * CU01: Inicio y cierre de sesión para Administrador, Instructor y Cliente.
 * CU02: Auto-registro de cuentas con rol 'cliente' (sin sesión/parte pública).
 *
 * Nota: Las cuentas de administrador e instructor solo pueden ser creadas por un
 * administrador desde UsuarioController, no aquí.
 */

class AuthController extends Controller
{

    // Modelos que usaremos para trabajar con usuarios y clientes
    private Usuario $usuarioModelo;
    private Cliente $clienteModelo;

    /**
     * Constructor: Crea los objetos para acceder a los datos de usuario y cliente
     */
    public function __construct()
    {
        $this->usuarioModelo = new Usuario();
        $this->clienteModelo = new Cliente();
    }

    /** Muestra la vista(formulario) de inicio de sesión. */
    public function login(Request $request): void
    {
        // Si ya hay una sesión activa redirije a home
        if (!empty($_SESSION['user'])) {
            $this->redirect('home');
        }

        $this->render('auth/login', ['error' => null]);
    }

    /**
     *Procesa el formulario de login: valida credenciales y arranca sesión.
     */
    public function autenticar(Request $request): void
    {
        if (!$request->esPost()) {
            $this->redirect('auth', 'login');
            return;
        }

        //Obtiene los datos del formulario
        $correo = $request->input('correo', '');
        $password = $request->input('password', '');

        // Pide al modelo Usuario que verifique las credenciales
        $usuario = $this->usuarioModelo->verificarCredenciales($correo, $password);

        //Si el modelo devuelve false, vuelve a la vista de login con un mensaje de error
        if (!$usuario) {
            $this->render('auth/login', ['error' => 'Correo o contraseña incorrectos.']);
            return;
        }

        if (!$usuario['estado']) {
            $this->render('auth/login', ['error' => 'Esta cuenta está inactiva. Contacta al administrador.']);
            return;
        }

        // Regenera ID de sesion por seguridad
        session_regenerate_id(true);

        // Guarda los datos del usuario en la sesión
        $_SESSION['user'] = [
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
        ];

        $this->redirect('home');
    }

    /** Cierra la sesión activa y vuelve a la página de inicio. */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('home');
    }

    /** Muestra la vista de auto-registro público (siempre crea una cuenta de tipo cliente). */
    public function register(Request $request): void
    {
        // Si ya hay una sesión activa redirije a home
        if (!empty($_SESSION['user'])) {
            $this->redirect('home');
        }

        // Envía a la vista de registro con datos vacíos y sin error
        $this->render('auth/register', ['error' => null, 'datos' => []]);
    }

    // Procesa el formulario de registro de un nuevo cliente
    public function crearCuenta(Request $request): void
    {
        if (!$request->esPost()) {
            $this->redirect('auth', 'register');
            return;
        }

        // Obtiene los datos del formulario
        $datos = [
            'ci' => $request->input('ci', ''),
            'nombres' => $request->input('nombres', ''),
            'apellidos' => $request->input('apellidos', ''),
            'fecha_nacimiento' => $request->input('fecha_nacimiento', ''),
            'correo' => $request->input('correo', ''),
        ];
        $password = $request->input('password', '');
        $passwordConfirmacion = $request->input('password_confirmacion', '');

        // Valida los datos del registro
        $error = $this->validarRegistro($datos, $password, $passwordConfirmacion);

        if ($error !== null) {
            $this->render('auth/register', ['error' => $error, 'datos' => $datos]);
            return;
        }

        // Pide al modelo Usuario que cree la cuenta con el rol cliente
        $idUsuario = $this->usuarioModelo->crear([
            ...$datos,
            'password' => $password,
            'rol' => 'cliente',
        ]);

       // Pide al modelo Cliente que cree el registro correspondiente en la tabla clientes
        $this->clienteModelo->crear($idUsuario, null, null);

        // Redirige a la página de login con un mensaje de éxito
        $this->redirect('auth', 'login');
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

        if ($this->usuarioModelo->existeCorreo($datos['correo'])) {
            return 'Ya existe una cuenta registrada con ese correo.';
        }

        if ($this->usuarioModelo->existeCi($datos['ci'])) {
            return 'Ya existe una cuenta registrada con ese CI.';
        }

        // Si todo está correcto, devuelve null
        return null;
    }
}

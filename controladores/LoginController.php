<?php

/**
 * Controlador de Autenticación (LoginController)
 *
 * CU01: Inicio y cierre de sesión para Administrador, Instructor y Cliente.
 * Se encarga exclusivamente de autenticación y cierre de sesión: el
 * auto-registro de clientes (CU02) vive en ClienteController.
 *
 * También sirve la portada/panel de bienvenida (acción index): al no requerir
 * sesión ni modelos propios, no justifica un controlador aparte.
 *
 * No existe una tabla ADMINISTRADOR: la cuenta de administrador es única y
 * fija, definida por ADMIN_CORREO/ADMIN_PASSWORD_HASH en .env, y se valida
 * aquí directamente en vez de a través de un modelo.
 *
 * Nota: las cuentas de instructor solo pueden ser creadas por el
 * administrador desde InstructorController, no aquí.
 */

class LoginController
{
    // Modelos que usaremos para trabajar con clientes e instructores.
    private Cliente $clienteModelo;
    private Instructor $instructorModelo;

    /**
     * Constructor: Crea los objetos para acceder a los datos de cliente e instructor.
     */
    public function __construct()
    {
        $this->clienteModelo = new Cliente();
        $this->instructorModelo = new Instructor();
    }

    /** Portada pública o panel de bienvenida, según haya sesión activa (ruta por defecto del sistema). */
    public function index(): void
    {
        require BASE_PATH . '/vistas/dashboard.php';
    }

    /** Muestra la vista(formulario) de inicio de sesión. */
    public function login(): void
    {
        // Si ya hay una sesión activa redirije a la portada
        if (!empty($_SESSION['user'])) {
            $this->redirect('login');
        }

        $this->render('login', ['error' => null]);
    }

    /**
     *Procesa el formulario de login: valida credenciales y arranca sesión.
     */
    public function autenticar(): void
    {
        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('login', 'login');
            return;
        }

        //Obtiene los datos del formulario
        $correo = $_POST['correo'] ?? $_GET['correo'] ?? '';
        if (is_string($correo)) { $correo = trim($correo); }
        $password = $_POST['password'] ?? $_GET['password'] ?? '';
        if (is_string($password)) { $password = trim($password); }

        // Se prueba primero contra la cuenta fija de administrador (no vive en ninguna tabla).
        $datosSesion = $this->verificarAdministrador($correo, $password);

        // Si no es el administrador, se prueba contra CLIENTE y luego contra INSTRUCTOR.
        if ($datosSesion === null) {
            $cliente = $this->clienteModelo->verificarCredenciales($correo, $password);

            if ($cliente !== null) {
                if (!$cliente['estado']) {
                    $this->render('login', ['error' => 'Esta cuenta está inactiva. Contacta al administrador.']);
                    return;
                }

                $datosSesion = [
                    'id' => $cliente['id_cliente'],
                    'nombre' => $cliente['nombres'] . ' ' . $cliente['apellidos'],
                    'correo' => $cliente['correo'],
                    'rol' => 'cliente',
                ];
            }
        }

        if ($datosSesion === null) {
            $instructor = $this->instructorModelo->verificarCredenciales($correo, $password);

            if ($instructor !== null) {
                if (!$instructor['estado']) {
                    $this->render('login', ['error' => 'Esta cuenta está inactiva. Contacta al administrador.']);
                    return;
                }

                $datosSesion = [
                    'id' => $instructor['id_instructor'],
                    'nombre' => $instructor['nombres'] . ' ' . $instructor['apellidos'],
                    'correo' => $instructor['correo'],
                    'rol' => 'instructor',
                ];
            }
        }

        // Ninguna de las tres fuentes reconoció las credenciales.
        if ($datosSesion === null) {
            $this->render('login', ['error' => 'Correo o contraseña incorrectos.']);
            return;
        }

        // Regenera ID de sesion por seguridad
        session_regenerate_id(true);

        $_SESSION['user'] = $datosSesion;

        $this->redirect('login');
    }

    /** Cierra la sesión activa y vuelve a la página de inicio. */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('login');
    }

    /**
     * Comprueba si el correo/contraseña corresponden al administrador fijo
     * definido en .env. Devuelve los datos listos para la sesión, o null si
     * no coinciden (para que autenticar() siga probando con CLIENTE/INSTRUCTOR).
     */
    private function verificarAdministrador(string $correo, string $password): ?array
    {
        $correoAdmin = Config::get('ADMIN_CORREO', '');
        $hashAdmin = Config::get('ADMIN_PASSWORD_HASH', '');

        if ($correoAdmin === '' || $correo !== $correoAdmin || !password_verify($password, $hashAdmin)) {
            return null;
        }

        return [
            'id' => 0,
            'nombre' => 'Administrador',
            'correo' => $correoAdmin,
            'rol' => 'administrador',
        ];
    }

    /** Muestra la vista de login/registro (vistas/login.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/login.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}

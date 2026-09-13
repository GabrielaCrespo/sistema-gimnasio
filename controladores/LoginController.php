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
 * Los tres roles se verifican igual: el controlador recibe la petición, le
 * pregunta al modelo correspondiente (Administrador, Cliente o Instructor) y
 * decide qué vista cargar. Que el administrador no tenga tabla y viva en .env
 * es un detalle que resuelve su modelo, no este controlador.
 *
 * Nota: las cuentas de instructor solo pueden ser creadas por el
 * administrador desde InstructorController, no aquí.
 */

class LoginController
{
    // Modelos que usaremos para verificar las credenciales de cada rol.
    private Administrador $administradorModelo;
    private Cliente $clienteModelo;
    private Instructor $instructorModelo;

    /**
     * Constructor: Crea los objetos para acceder a los datos de administrador,
     * cliente e instructor.
     */
    public function __construct()
    {
        $this->administradorModelo = new Administrador();
        $this->clienteModelo = new Cliente();
        $this->instructorModelo = new Instructor();
    }

    /** Portada pública o panel de bienvenida, según haya sesión activa (ruta por defecto del sistema). */
    public function index(): void
    {
        $usuarioSesion = $_SESSION['user'] ?? null;

        require BASE_PATH . '/vistas/dashboard.php';
    }

    /** Muestra la vista(formulario) de inicio de sesión. */
    public function login(): void
    {
        // Si ya hay una sesión activa redirije a la portada
        if (!empty($_SESSION['user'])) {
            $this->redirect('login');
        }

        $error = null;

        require BASE_PATH . '/vistas/login/login.php';
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

        $datosSesion = null;

        // Se prueba primero contra la cuenta fija de administrador.
        $administrador = $this->administradorModelo->verificarCredenciales($correo, $password);

        if ($administrador !== null) {
            $datosSesion = [
                'id' => 0,
                'nombre' => $administrador['nombre'],
                'correo' => $administrador['correo'],
                'rol' => 'administrador',
            ];
        }

        // Si no es el administrador, se prueba contra CLIENTE y luego contra INSTRUCTOR.
        if ($datosSesion === null) {
            $cliente = $this->clienteModelo->verificarCredenciales($correo, $password);

            if ($cliente !== null) {
                if (!$cliente['estado']) {
                    $error = 'Esta cuenta está inactiva. Contacta al administrador.';
                    require BASE_PATH . '/vistas/login/login.php';
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
                    $error = 'Esta cuenta está inactiva. Contacta al administrador.';
                    require BASE_PATH . '/vistas/login/login.php';
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
            $error = 'Correo o contraseña incorrectos.';
            require BASE_PATH . '/vistas/login/login.php';
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

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}

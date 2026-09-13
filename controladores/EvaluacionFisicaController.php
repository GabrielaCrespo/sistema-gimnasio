<?php

/**
 * EvaluacionFisicaController
 *
 * CU05 - Gestionar evaluación física: registrar evaluaciones (solo
 * instructor) y consultar el historial. El instructor puede consultar el
 * historial de cualquier cliente; el cliente solo el suyo propio.
 */
class EvaluacionFisicaController
{
    private EvaluacionFisica $evaluacionModelo;
    private Cliente $clienteModelo;

    public function __construct()
    {
        $this->evaluacionModelo = new EvaluacionFisica();
        $this->clienteModelo = new Cliente();
    }

    /** Muestra el formulario para registrar una evaluación, con la lista de clientes a elegir. */
    public function registrar(): void
    {
        $this->requireRole(['instructor']);

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];
        $clientes = $this->clienteModelo->listarTodos();

        require BASE_PATH . '/vistas/evaluacion/registrar.php';
    }

    /** Procesa el registro de una nueva evaluación física. */
    public function guardar(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('evaluacionFisica', 'registrar');
            return;
        }

        $idCliente = $_POST['id_cliente'] ?? $_GET['id_cliente'] ?? 0;
        if (is_string($idCliente)) { $idCliente = trim($idCliente); }
        $idCliente = (int) $idCliente;
        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }
        $altura = $_POST['altura'] ?? $_GET['altura'] ?? '';
        if (is_string($altura)) { $altura = trim($altura); }
        $objetivo = $_POST['objetivo'] ?? $_GET['objetivo'] ?? '';
        if (is_string($objetivo)) { $objetivo = trim($objetivo); }
        $porcentajeGrasa = $_POST['porcentaje_grasa'] ?? $_GET['porcentaje_grasa'] ?? '';
        if (is_string($porcentajeGrasa)) { $porcentajeGrasa = trim($porcentajeGrasa); }
        $masaMuscular = $_POST['masa_muscular'] ?? $_GET['masa_muscular'] ?? '';
        if (is_string($masaMuscular)) { $masaMuscular = trim($masaMuscular); }
        $flexibilidad = $_POST['flexibilidad'] ?? $_GET['flexibilidad'] ?? '';
        if (is_string($flexibilidad)) { $flexibilidad = trim($flexibilidad); }
        $observaciones = $_POST['observaciones'] ?? $_GET['observaciones'] ?? '';
        if (is_string($observaciones)) { $observaciones = trim($observaciones); }

        $datos = [
            'id_cliente' => $idCliente,
            'peso' => $peso,
            'altura' => $altura,
            'objetivo' => $objetivo ?: null,
            'porcentaje_grasa' => $porcentajeGrasa,
            'masa_muscular' => $masaMuscular,
            'flexibilidad' => $flexibilidad,
            'observaciones' => $observaciones ?: null,
        ];

        $error = $this->validar($datos);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $clientes = $this->clienteModelo->listarTodos();

            require BASE_PATH . '/vistas/evaluacion/registrar.php';
            return;
        }

        $this->evaluacionModelo->crear([
            'id_cliente' => $datos['id_cliente'],
            'id_instructor' => (int) $_SESSION['user']['id'],
            'peso' => $datos['peso'],
            'altura' => $datos['altura'],
            'objetivo' => $datos['objetivo'],
            'porcentaje_grasa' => $datos['porcentaje_grasa'] !== '' ? $datos['porcentaje_grasa'] : null,
            'masa_muscular' => $datos['masa_muscular'] !== '' ? $datos['masa_muscular'] : null,
            'flexibilidad' => $datos['flexibilidad'] !== '' ? $datos['flexibilidad'] : null,
            'observaciones' => $datos['observaciones'],
        ]);

        // La evaluación trae el peso/altura más recientes del cliente:
        // se reflejan también en CLIENTE, que es lo que se muestra en su perfil.
        $this->clienteModelo->actualizarMedidas($datos['id_cliente'], (float) $datos['altura'], (float) $datos['peso']);

        $this->redirect('evaluacionFisica', 'historial', ['id' => $datos['id_cliente']]);
    }

    /** Formulario para editar una evaluación ya registrada. Solo puede editarla el instructor que la registró. */
    public function editar(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $evaluacion = $this->evaluacionModelo->buscarPorId($id);

        if (!$evaluacion || !$this->esPropietario($evaluacion)) {
            $this->paginaNoEncontrada();
            return;
        }

        $cliente = $this->clienteModelo->buscarPorId((int) $evaluacion['id_cliente']);

        $usuarioSesion = $_SESSION['user'];
        $error = null;

        require BASE_PATH . '/vistas/evaluacion/editar.php';
    }

    /** Procesa la edición de una evaluación física ya registrada. */
    public function actualizar(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $evaluacion = $this->evaluacionModelo->buscarPorId($id);

        if (!$evaluacion || !$this->esPropietario($evaluacion)) {
            $this->paginaNoEncontrada();
            return;
        }

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('evaluacionFisica', 'editar', ['id' => $id]);
            return;
        }

        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }
        $altura = $_POST['altura'] ?? $_GET['altura'] ?? '';
        if (is_string($altura)) { $altura = trim($altura); }
        $objetivo = $_POST['objetivo'] ?? $_GET['objetivo'] ?? '';
        if (is_string($objetivo)) { $objetivo = trim($objetivo); }
        $porcentajeGrasa = $_POST['porcentaje_grasa'] ?? $_GET['porcentaje_grasa'] ?? '';
        if (is_string($porcentajeGrasa)) { $porcentajeGrasa = trim($porcentajeGrasa); }
        $masaMuscular = $_POST['masa_muscular'] ?? $_GET['masa_muscular'] ?? '';
        if (is_string($masaMuscular)) { $masaMuscular = trim($masaMuscular); }
        $flexibilidad = $_POST['flexibilidad'] ?? $_GET['flexibilidad'] ?? '';
        if (is_string($flexibilidad)) { $flexibilidad = trim($flexibilidad); }
        $observaciones = $_POST['observaciones'] ?? $_GET['observaciones'] ?? '';
        if (is_string($observaciones)) { $observaciones = trim($observaciones); }

        $datos = [
            'peso' => $peso,
            'altura' => $altura,
            'objetivo' => $objetivo ?: null,
            'porcentaje_grasa' => $porcentajeGrasa,
            'masa_muscular' => $masaMuscular,
            'flexibilidad' => $flexibilidad,
            'observaciones' => $observaciones ?: null,
        ];

        $error = $this->validar([...$datos, 'id_cliente' => (int) $evaluacion['id_cliente']]);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $cliente = $this->clienteModelo->buscarPorId((int) $evaluacion['id_cliente']);
            $evaluacion = [...$evaluacion, ...$datos];

            require BASE_PATH . '/vistas/evaluacion/editar.php';
            return;
        }

        $this->evaluacionModelo->actualizar($id, [
            'peso' => $datos['peso'],
            'altura' => $datos['altura'],
            'objetivo' => $datos['objetivo'],
            'porcentaje_grasa' => $datos['porcentaje_grasa'] !== '' ? $datos['porcentaje_grasa'] : null,
            'masa_muscular' => $datos['masa_muscular'] !== '' ? $datos['masa_muscular'] : null,
            'flexibilidad' => $datos['flexibilidad'] !== '' ? $datos['flexibilidad'] : null,
            'observaciones' => $datos['observaciones'],
        ]);

        // Igual que al registrar: refleja el peso/altura editados en CLIENTE, que es lo que se muestra en su perfil.
        $this->clienteModelo->actualizarMedidas((int) $evaluacion['id_cliente'], (float) $datos['altura'], (float) $datos['peso']);

        $this->redirect('evaluacionFisica', 'historial', ['id' => $evaluacion['id_cliente']]);
    }

    /** Muestra el detalle completo de una evaluación (incluye observaciones, que la tabla del historial no muestra). */
    public function ver(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $evaluacion = $this->evaluacionModelo->buscarPorIdConNombres($id);

        if (!$evaluacion || !$this->tieneAcceso($evaluacion)) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];

        require BASE_PATH . '/vistas/evaluacion/ver.php';
    }

    /** Elimina una evaluación ya registrada. Solo puede borrarla el instructor que la registró. */
    public function eliminar(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('evaluacionFisica', 'historial');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $evaluacion = $this->evaluacionModelo->buscarPorId($id);

        if (!$evaluacion || !$this->esPropietario($evaluacion)) {
            $this->paginaNoEncontrada();
            return;
        }

        $this->evaluacionModelo->eliminar($id);

        $this->redirect('evaluacionFisica', 'historial', ['id' => $evaluacion['id_cliente']]);
    }

    /** true si la evaluación fue registrada por el instructor en sesión (única acción autorizada a editarla o borrarla). */
    private function esPropietario(array $evaluacion): bool
    {
        return (int) $evaluacion['id_instructor'] === (int) $_SESSION['user']['id'];
    }

    /** true si el usuario en sesión puede VER la evaluación: el instructor que la registró, cualquier otro instructor, o el cliente al que pertenece. */
    private function tieneAcceso(array $evaluacion): bool
    {
        if ($_SESSION['user']['rol'] === 'instructor') {
            return true;
        }

        return (int) $evaluacion['id_cliente'] === (int) $_SESSION['user']['id'];
    }

    /**
     * CU05: muestra el historial de evaluaciones. Un instructor puede
     * consultar el de cualquier cliente (elige uno de una lista si no
     * indica id); un cliente solo puede ver el suyo propio, sin importar
     * qué id venga en la URL.
     */
    public function historial(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        $usuarioSesion = $_SESSION['user'];

        if ($_SESSION['user']['rol'] === 'cliente') {
            $idCliente = (int) $_SESSION['user']['id'];

            $evaluaciones = $this->evaluacionModelo->listarPorCliente($idCliente);
            $clientes = null;
            $clienteSeleccionado = null;

            require BASE_PATH . '/vistas/evaluacion/historial.php';
            return;
        }

        // Rol instructor: si no se especificó cliente, se muestra el
        // selector en vez de una tabla vacía.
        $idCliente = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($idCliente)) { $idCliente = trim($idCliente); }
        $idCliente = (int) $idCliente;
        $clientes = $this->clienteModelo->listarTodos();

        if ($idCliente === 0) {
            $evaluaciones = [];
            $clienteSeleccionado = null;

            require BASE_PATH . '/vistas/evaluacion/historial.php';
            return;
        }

        $clienteSeleccionado = $this->clienteModelo->buscarPorId($idCliente);
        $evaluaciones = $clienteSeleccionado ? $this->evaluacionModelo->listarPorCliente($idCliente) : [];

        require BASE_PATH . '/vistas/evaluacion/historial.php';
    }

    /** Valida los campos obligatorios de una evaluación física. */
    private function validar(array $datos): ?string
    {
        if ($datos['id_cliente'] === 0) {
            return 'Selecciona un cliente.';
        }

        if ($datos['peso'] === '' || $datos['altura'] === '') {
            return 'El peso y la altura son obligatorios.';
        }

        if (!is_numeric($datos['peso']) || (float) $datos['peso'] <= 0) {
            return 'El peso debe ser un número mayor a 0.';
        }

        if (!is_numeric($datos['altura']) || (float) $datos['altura'] <= 0) {
            return 'La altura debe ser un número mayor a 0.';
        }

        return null;
    }

    /** Carga la vista de error 404 cuando se pide una evaluación que no existe o que registró otro instructor. */
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

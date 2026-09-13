<?php

/**
 * RutinaController
 *
 * CU06 - Gestionar rutinas de entrenamiento: el instructor crea la rutina
 * para un cliente, le agrega/quita ejercicios (día, series, repeticiones,
 * descanso y orden), edita sus datos generales y la visualiza; el cliente
 * consulta sus propias rutinas y el detalle de sus ejercicios, usando la
 * misma acción "ver" que el instructor, en modo solo lectura para su rol.
 */
class RutinaController
{
    /** Días válidos para dia_semana (columna VARCHAR(10)); fijan también el orden en que se agrupan en la vista "ver". */
    private const DIAS_SEMANA = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    private Rutina $rutinaModelo;
    private DetalleRutina $detalleModelo;
    private Cliente $clienteModelo;
    private Ejercicio $ejercicioModelo;

    public function __construct()
    {
        $this->rutinaModelo = new Rutina();
        $this->detalleModelo = new DetalleRutina();
        $this->clienteModelo = new Cliente();
        $this->ejercicioModelo = new Ejercicio();
    }

    /** Lista las rutinas según el rol: el instructor ve las que creó, el cliente ve las suyas (CU06). */
    public function index(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        $idUsuario = (int) $_SESSION['user']['id'];
        $rol = $_SESSION['user']['rol'];

        $usuarioSesion = $_SESSION['user'];
        $rutinas = $rol === 'instructor'
            ? $this->rutinaModelo->listarPorInstructor($idUsuario)
            : $this->rutinaModelo->listarPorCliente($idUsuario);

        require BASE_PATH . '/vistas/rutina/listar.php';
    }

    /** Muestra el detalle de una rutina y sus ejercicios agrupados por día, tanto para el instructor como para el cliente (CU06). */
    public function ver(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->tieneAcceso($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $detallePorDia = $this->agruparPorDia($this->detalleModelo->listarPorRutina($id));

        require BASE_PATH . '/vistas/rutina/ver.php';
    }

    /** Formulario para crear una rutina nueva, eligiendo el cliente al que se le asigna. */
    public function crear(): void
    {
        $this->requireRole(['instructor']);

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];
        $clientes = $this->clienteModelo->listarTodos();

        require BASE_PATH . '/vistas/rutina/crear.php';
    }

    /** Procesa la creación de la rutina; luego redirige a "asignar" para agregarle ejercicios. */
    public function guardar(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('rutina', 'crear');
            return;
        }

        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? '';
        if (is_string($tipo)) { $tipo = trim($tipo); }
        $fechaInicio = $_POST['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? '';
        if (is_string($fechaInicio)) { $fechaInicio = trim($fechaInicio); }
        $fechaFin = $_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? '';
        if (is_string($fechaFin)) { $fechaFin = trim($fechaFin); }
        $idCliente = $_POST['id_cliente'] ?? $_GET['id_cliente'] ?? 0;
        if (is_string($idCliente)) { $idCliente = trim($idCliente); }
        $idCliente = (int) $idCliente;

        $datos = [
            'nombre' => $nombre,
            'tipo' => $tipo ?: null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin ?: null,
            'id_cliente' => $idCliente,
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $clientes = $this->clienteModelo->listarTodos();

            require BASE_PATH . '/vistas/rutina/crear.php';
            return;
        }

        $idRutina = $this->rutinaModelo->crear([
            ...$datos,
            'id_instructor' => (int) $_SESSION['user']['id'],
        ]);

        $this->redirect('rutina', 'asignar', ['id' => $idRutina]);
    }

    /** Formulario de edición de los datos generales de una rutina. Solo puede editarla el instructor que la creó. */
    public function editar(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;

        require BASE_PATH . '/vistas/rutina/editar.php';
    }

    public function actualizar(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? '';
        if (is_string($tipo)) { $tipo = trim($tipo); }
        $fechaInicio = $_POST['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? '';
        if (is_string($fechaInicio)) { $fechaInicio = trim($fechaInicio); }
        $fechaFin = $_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? '';
        if (is_string($fechaFin)) { $fechaFin = trim($fechaFin); }
        $estado = $_POST['estado'] ?? $_GET['estado'] ?? 'activa';
        if (is_string($estado)) { $estado = trim($estado); }

        $datos = [
            'nombre' => $nombre,
            'tipo' => $tipo ?: null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin ?: null,
            'estado' => $estado,
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error === null && !in_array($datos['estado'], ['activa', 'completada', 'cancelada'], true)) {
            $error = 'Selecciona un estado válido.';
        }

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $rutina = [...$rutina, ...$datos];

            require BASE_PATH . '/vistas/rutina/editar.php';
            return;
        }

        $this->rutinaModelo->actualizar($id, $datos);

        $this->redirect('rutina', 'ver', ['id' => $id]);
    }

    /** Pantalla para agregar/quitar ejercicios de la rutina (CU06: "agregar/quitar ejercicios y asignar rutinas"). */
    public function asignar(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $detalle = $this->detalleModelo->listarPorRutina($id);
        $ejercicios = $this->ejercicioModelo->listarTodos();
        $dias = self::DIAS_SEMANA;

        require BASE_PATH . '/vistas/rutina/asignar.php';
    }

    /** Agrega un ejercicio (día, series, repeticiones, peso, descanso, orden) a la rutina. */
    public function agregarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        $id = $_POST['id_rutina'] ?? $_GET['id_rutina'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('rutina', 'asignar', ['id' => $id]);
            return;
        }

        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }

        $idEjercicio = $_POST['id_ejercicio'] ?? $_GET['id_ejercicio'] ?? 0;
        if (is_string($idEjercicio)) { $idEjercicio = trim($idEjercicio); }
        $idEjercicio = (int) $idEjercicio;
        $diaSemana = $_POST['dia_semana'] ?? $_GET['dia_semana'] ?? '';
        if (is_string($diaSemana)) { $diaSemana = trim($diaSemana); }
        $series = $_POST['series'] ?? $_GET['series'] ?? 0;
        if (is_string($series)) { $series = trim($series); }
        $series = (int) $series;
        $repeticiones = $_POST['repeticiones'] ?? $_GET['repeticiones'] ?? 0;
        if (is_string($repeticiones)) { $repeticiones = trim($repeticiones); }
        $repeticiones = (int) $repeticiones;
        $tiempoDescanso = $_POST['tiempo_descanso'] ?? $_GET['tiempo_descanso'] ?? 0;
        if (is_string($tiempoDescanso)) { $tiempoDescanso = trim($tiempoDescanso); }
        $tiempoDescanso = (int) $tiempoDescanso;
        $orden = $_POST['orden'] ?? $_GET['orden'] ?? 0;
        if (is_string($orden)) { $orden = trim($orden); }
        $orden = (int) $orden;

        $datos = [
            'id_ejercicio' => $idEjercicio,
            'dia_semana' => $diaSemana,
            'series' => $series,
            'repeticiones' => $repeticiones,
            'peso' => $peso !== '' ? (float) $peso : null,
            'tiempo_descanso' => $tiempoDescanso,
            'orden' => $orden,
        ];

        $error = $this->validarDetalle($datos);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $detalle = $this->detalleModelo->listarPorRutina($id);
            $ejercicios = $this->ejercicioModelo->listarTodos();
            $dias = self::DIAS_SEMANA;

            require BASE_PATH . '/vistas/rutina/asignar.php';
            return;
        }

        $this->detalleModelo->agregar($id, $datos);

        $this->redirect('rutina', 'asignar', ['id' => $id]);
    }

    /** Formulario para editar una fila de DETALLE_RUTINA ya asignada a la rutina. */
    public function editarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        $idRutina = $_POST['id_rutina'] ?? $_GET['id_rutina'] ?? 0;
        if (is_string($idRutina)) { $idRutina = trim($idRutina); }
        $idRutina = (int) $idRutina;
        $rutina = $this->rutinaModelo->buscarPorId($idRutina);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $idDetalle = $_POST['id_detalle'] ?? $_GET['id_detalle'] ?? 0;
        if (is_string($idDetalle)) { $idDetalle = trim($idDetalle); }
        $idDetalle = (int) $idDetalle;
        $detalle = $this->detalleModelo->buscarPorId($idDetalle, $idRutina);

        if (!$detalle) {
            $this->paginaNoEncontrada();
            return;
        }

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $ejercicios = $this->ejercicioModelo->listarTodos();
        $dias = self::DIAS_SEMANA;

        require BASE_PATH . '/vistas/rutina/editar_ejercicio.php';
    }

    /** Procesa la edición de una fila de DETALLE_RUTINA ya asignada a la rutina. */
    public function actualizarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        $idRutina = $_POST['id_rutina'] ?? $_GET['id_rutina'] ?? 0;
        if (is_string($idRutina)) { $idRutina = trim($idRutina); }
        $idRutina = (int) $idRutina;
        $rutina = $this->rutinaModelo->buscarPorId($idRutina);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $idDetalle = $_POST['id_detalle'] ?? $_GET['id_detalle'] ?? 0;
        if (is_string($idDetalle)) { $idDetalle = trim($idDetalle); }
        $idDetalle = (int) $idDetalle;
        $detalleExistente = $this->detalleModelo->buscarPorId($idDetalle, $idRutina);

        if (!$detalleExistente) {
            $this->paginaNoEncontrada();
            return;
        }

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('rutina', 'asignar', ['id' => $idRutina]);
            return;
        }

        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }

        $idEjercicio = $_POST['id_ejercicio'] ?? $_GET['id_ejercicio'] ?? 0;
        if (is_string($idEjercicio)) { $idEjercicio = trim($idEjercicio); }
        $idEjercicio = (int) $idEjercicio;
        $diaSemana = $_POST['dia_semana'] ?? $_GET['dia_semana'] ?? '';
        if (is_string($diaSemana)) { $diaSemana = trim($diaSemana); }
        $series = $_POST['series'] ?? $_GET['series'] ?? 0;
        if (is_string($series)) { $series = trim($series); }
        $series = (int) $series;
        $repeticiones = $_POST['repeticiones'] ?? $_GET['repeticiones'] ?? 0;
        if (is_string($repeticiones)) { $repeticiones = trim($repeticiones); }
        $repeticiones = (int) $repeticiones;
        $tiempoDescanso = $_POST['tiempo_descanso'] ?? $_GET['tiempo_descanso'] ?? 0;
        if (is_string($tiempoDescanso)) { $tiempoDescanso = trim($tiempoDescanso); }
        $tiempoDescanso = (int) $tiempoDescanso;
        $orden = $_POST['orden'] ?? $_GET['orden'] ?? 0;
        if (is_string($orden)) { $orden = trim($orden); }
        $orden = (int) $orden;

        $datos = [
            'id_ejercicio' => $idEjercicio,
            'dia_semana' => $diaSemana,
            'series' => $series,
            'repeticiones' => $repeticiones,
            'peso' => $peso !== '' ? (float) $peso : null,
            'tiempo_descanso' => $tiempoDescanso,
            'orden' => $orden,
        ];

        $error = $this->validarDetalle($datos);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $detalle = ['id_detalle' => $idDetalle, ...$datos];
            $ejercicios = $this->ejercicioModelo->listarTodos();
            $dias = self::DIAS_SEMANA;

            require BASE_PATH . '/vistas/rutina/editar_ejercicio.php';
            return;
        }

        $this->detalleModelo->actualizar($idDetalle, $idRutina, $datos);

        $this->redirect('rutina', 'asignar', ['id' => $idRutina]);
    }

    /** Quita un ejercicio de la rutina. */
    public function quitarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('rutina', 'index');
            return;
        }

        $idRutina = $_POST['id_rutina'] ?? $_GET['id_rutina'] ?? 0;
        if (is_string($idRutina)) { $idRutina = trim($idRutina); }
        $idRutina = (int) $idRutina;
        $rutina = $this->rutinaModelo->buscarPorId($idRutina);

        if ($rutina && $this->esPropietario($rutina)) {
            $idDetalle = $_POST['id_detalle'] ?? $_GET['id_detalle'] ?? 0;
        if (is_string($idDetalle)) { $idDetalle = trim($idDetalle); }
        $idDetalle = (int) $idDetalle;
            $this->detalleModelo->eliminar($idDetalle, $idRutina);
        }

        $this->redirect('rutina', 'asignar', ['id' => $idRutina]);
    }

    /** true si la rutina fue creada por el instructor en sesión (acciones de gestión: editar, asignar ejercicios). */
    private function esPropietario(array $rutina): bool
    {
        return (int) $rutina['id_instructor'] === (int) $_SESSION['user']['id'];
    }

    /** true si el usuario en sesión puede VER la rutina: el instructor que la creó o el cliente al que pertenece. */
    private function tieneAcceso(array $rutina): bool
    {
        $idUsuario = (int) $_SESSION['user']['id'];

        return (int) $rutina['id_instructor'] === $idUsuario || (int) $rutina['id_cliente'] === $idUsuario;
    }

    /** Agrupa el detalle de una rutina por día de la semana (en el orden fijo de DIAS_SEMANA) para mostrarlo día por día en la vista "ver". */
    private function agruparPorDia(array $detalle): array
    {
        $porDia = array_fill_keys(self::DIAS_SEMANA, []);

        foreach ($detalle as $fila) {
            $porDia[$fila['dia_semana']][] = $fila;
        }

        return array_filter($porDia, fn (array $ejercicios): bool => $ejercicios !== []);
    }

    /** Valida los datos generales de una rutina (nombre, fechas y, al crear, el cliente elegido). */
    private function validarDatosGenerales(array $datos): ?string
    {
        if ($datos['nombre'] === '') {
            return 'El nombre de la rutina es obligatorio.';
        }

        if ($datos['fecha_inicio'] === '') {
            return 'La fecha de inicio es obligatoria.';
        }

        if ($datos['fecha_fin'] !== null && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            return 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        }

        if (array_key_exists('id_cliente', $datos) && $datos['id_cliente'] === 0) {
            return 'Selecciona un cliente.';
        }

        return null;
    }

    /** Valida los datos de una fila de DETALLE_RUTINA antes de agregarla. */
    private function validarDetalle(array $datos): ?string
    {
        if ($datos['id_ejercicio'] === 0) {
            return 'Selecciona un ejercicio.';
        }

        if (!in_array($datos['dia_semana'], self::DIAS_SEMANA, true)) {
            return 'Selecciona un día de la semana válido.';
        }

        if ($datos['series'] <= 0 || $datos['repeticiones'] <= 0) {
            return 'Las series y las repeticiones deben ser mayores a 0.';
        }

        if ($datos['peso'] !== null && $datos['peso'] < 0) {
            return 'El peso no puede ser negativo.';
        }

        if ($datos['tiempo_descanso'] < 0) {
            return 'El tiempo de descanso no puede ser negativo.';
        }

        return null;
    }

    /** Carga la vista de error 404 cuando se pide una rutina que no existe o a la que no se tiene acceso. */
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

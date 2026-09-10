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

        $rutinas = $rol === 'instructor'
            ? $this->rutinaModelo->listarPorInstructor($idUsuario)
            : $this->rutinaModelo->listarPorCliente($idUsuario);

        $this->render('listar', ['rutinas' => $rutinas, 'rol' => $rol]);
    }

    /** Muestra el detalle de una rutina y sus ejercicios agrupados por día, tanto para el instructor como para el cliente (CU06). */
    public function ver(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        $id = (int) input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->tieneAcceso($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $detalle = $this->detalleModelo->listarPorRutina($id);
        $this->render('ver', ['rutina' => $rutina, 'detallePorDia' => $this->agruparPorDia($detalle)]);
    }

    /** Formulario para crear una rutina nueva, eligiendo el cliente al que se le asigna. */
    public function crear(): void
    {
        $this->requireRole(['instructor']);

        $clientes = $this->clienteModelo->listarTodos();
        $this->render('crear', ['error' => null, 'datos' => [], 'clientes' => $clientes]);
    }

    /** Procesa la creación de la rutina; luego redirige a "asignar" para agregarle ejercicios. */
    public function guardar(): void
    {
        $this->requireRole(['instructor']);

        if (!esPost()) {
            $this->redirect('rutina', 'crear');
            return;
        }

        $datos = [
            'nombre' => input('nombre', ''),
            'tipo' => input('tipo', '') ?: null,
            'fecha_inicio' => input('fecha_inicio', ''),
            'fecha_fin' => input('fecha_fin', '') ?: null,
            'id_cliente' => (int) input('id_cliente', 0),
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error !== null) {
            $this->render('crear', ['error' => $error, 'datos' => $datos, 'clientes' => $this->clienteModelo->listarTodos()]);
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

        $id = (int) input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $this->render('editar', ['error' => null, 'rutina' => $rutina]);
    }

    public function actualizar(): void
    {
        $this->requireRole(['instructor']);

        $id = (int) input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $datos = [
            'nombre' => input('nombre', ''),
            'tipo' => input('tipo', '') ?: null,
            'fecha_inicio' => input('fecha_inicio', ''),
            'fecha_fin' => input('fecha_fin', '') ?: null,
            'estado' => input('estado', 'activa'),
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error === null && !in_array($datos['estado'], ['activa', 'completada', 'cancelada'], true)) {
            $error = 'Selecciona un estado válido.';
        }

        if ($error !== null) {
            $this->render('editar', ['error' => $error, 'rutina' => [...$rutina, ...$datos]]);
            return;
        }

        $this->rutinaModelo->actualizar($id, $datos);

        $this->redirect('rutina', 'ver', ['id' => $id]);
    }

    /** Pantalla para agregar/quitar ejercicios de la rutina (CU06: "agregar/quitar ejercicios y asignar rutinas"). */
    public function asignar(): void
    {
        $this->requireRole(['instructor']);

        $id = (int) input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        $this->render('asignar', [
            'error' => null,
            'rutina' => $rutina,
            'detalle' => $this->detalleModelo->listarPorRutina($id),
            'ejercicios' => $this->ejercicioModelo->listarTodos(),
            'dias' => self::DIAS_SEMANA,
        ]);
    }

    /** Agrega un ejercicio (día, series, repeticiones, descanso, orden) a la rutina. */
    public function agregarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        $id = (int) input('id_rutina', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            $this->paginaNoEncontrada();
            return;
        }

        if (!esPost()) {
            $this->redirect('rutina', 'asignar', ['id' => $id]);
            return;
        }

        $datos = [
            'id_ejercicio' => (int) input('id_ejercicio', 0),
            'dia_semana' => input('dia_semana', ''),
            'series' => (int) input('series', 0),
            'repeticiones' => (int) input('repeticiones', 0),
            'tiempo_descanso' => (int) input('tiempo_descanso', 0),
            'orden' => (int) input('orden', 0),
        ];

        $error = $this->validarDetalle($datos);

        if ($error !== null) {
            $this->render('asignar', [
                'error' => $error,
                'rutina' => $rutina,
                'detalle' => $this->detalleModelo->listarPorRutina($id),
                'ejercicios' => $this->ejercicioModelo->listarTodos(),
                'dias' => self::DIAS_SEMANA,
            ]);
            return;
        }

        $this->detalleModelo->agregar($id, $datos);

        $this->redirect('rutina', 'asignar', ['id' => $id]);
    }

    /** Quita un ejercicio de la rutina. */
    public function quitarEjercicio(): void
    {
        $this->requireRole(['instructor']);

        if (!esPost()) {
            $this->redirect('rutina', 'index');
            return;
        }

        $idRutina = (int) input('id_rutina', 0);
        $rutina = $this->rutinaModelo->buscarPorId($idRutina);

        if ($rutina && $this->esPropietario($rutina)) {
            $idDetalle = (int) input('id_detalle', 0);
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

        if ($datos['tiempo_descanso'] < 0) {
            return 'El tiempo de descanso no puede ser negativo.';
        }

        return null;
    }

    /** Muestra un error 404 minimal cuando se pide una rutina que no existe o a la que no se tiene acceso. */
    private function paginaNoEncontrada(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title><link rel="stylesheet" href="/style.css"></head><body><main class="contenedor"><section class="error-pagina"><h1>404 &mdash; Página no encontrada</h1><p>La página que buscas no existe.</p><a class="boton" href="/index.php">Volver al inicio</a></section></main></body></html>';
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
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title><link rel="stylesheet" href="/style.css"></head><body><main class="contenedor"><section class="error-pagina"><h1>403 &mdash; Acceso denegado</h1><p>No tienes permisos para acceder a esta sección del sistema.</p><a class="boton" href="/index.php">Volver al inicio</a></section></main></body></html>';
            exit;
        }
    }

    /** Muestra la vista de rutina (vistas/rutina.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/rutina.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . url($controlador, $accion, $parametros));
        exit;
    }
}

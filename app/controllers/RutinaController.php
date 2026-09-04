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
class RutinaController extends Controller
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
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor', 'cliente']);

        $idUsuario = (int) $_SESSION['user']['id'];
        $rol = $_SESSION['user']['rol'];

        $rutinas = $rol === 'instructor'
            ? $this->rutinaModelo->listarPorInstructor($idUsuario)
            : $this->rutinaModelo->listarPorCliente($idUsuario);

        $this->render('rutina/index', ['rutinas' => $rutinas, 'rol' => $rol]);
    }

    /** Muestra el detalle de una rutina y sus ejercicios agrupados por día, tanto para el instructor como para el cliente (CU06). */
    public function ver(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor', 'cliente']);

        $id = (int) $request->input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->tieneAcceso($rutina)) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $detalle = $this->detalleModelo->listarPorRutina($id);
        $this->render('rutina/ver', ['rutina' => $rutina, 'detallePorDia' => $this->agruparPorDia($detalle)]);
    }

    /** Formulario para crear una rutina nueva, eligiendo el cliente al que se le asigna. */
    public function crear(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $clientes = $this->clienteModelo->listarTodos();
        $this->render('rutina/crear', ['error' => null, 'datos' => [], 'clientes' => $clientes]);
    }

    /** Procesa la creación de la rutina; luego redirige a "asignar" para agregarle ejercicios. */
    public function guardar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        if (!$request->esPost()) {
            $this->redirect('rutina', 'crear');
            return;
        }

        $datos = [
            'nombre' => $request->input('nombre', ''),
            'tipo' => $request->input('tipo', '') ?: null,
            'fecha_inicio' => $request->input('fecha_inicio', ''),
            'fecha_fin' => $request->input('fecha_fin', '') ?: null,
            'id_cliente' => (int) $request->input('id_cliente', 0),
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error !== null) {
            $this->render('rutina/crear', ['error' => $error, 'datos' => $datos, 'clientes' => $this->clienteModelo->listarTodos()]);
            return;
        }

        $idRutina = $this->rutinaModelo->crear([
            ...$datos,
            'id_instructor' => (int) $_SESSION['user']['id'],
        ]);

        $this->redirect('rutina', 'asignar', ['id' => $idRutina]);
    }

    /** Formulario de edición de los datos generales de una rutina. Solo puede editarla el instructor que la creó. */
    public function editar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $id = (int) $request->input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('rutina/editar', ['error' => null, 'rutina' => $rutina]);
    }

    public function actualizar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $id = (int) $request->input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $datos = [
            'nombre' => $request->input('nombre', ''),
            'tipo' => $request->input('tipo', '') ?: null,
            'fecha_inicio' => $request->input('fecha_inicio', ''),
            'fecha_fin' => $request->input('fecha_fin', '') ?: null,
            'estado' => $request->input('estado', 'activa'),
        ];

        $error = $this->validarDatosGenerales($datos);

        if ($error === null && !in_array($datos['estado'], ['activa', 'completada', 'cancelada'], true)) {
            $error = 'Selecciona un estado válido.';
        }

        if ($error !== null) {
            $this->render('rutina/editar', ['error' => $error, 'rutina' => [...$rutina, ...$datos]]);
            return;
        }

        $this->rutinaModelo->actualizar($id, $datos);

        $this->redirect('rutina', 'ver', ['id' => $id]);
    }

    /** Pantalla para agregar/quitar ejercicios de la rutina (CU06: "agregar/quitar ejercicios y asignar rutinas"). */
    public function asignar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $id = (int) $request->input('id', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        $this->render('rutina/asignar', [
            'error' => null,
            'rutina' => $rutina,
            'detalle' => $this->detalleModelo->listarPorRutina($id),
            'ejercicios' => $this->ejercicioModelo->listarTodos(),
            'dias' => self::DIAS_SEMANA,
        ]);
    }

    /** Agrega un ejercicio (día, series, repeticiones, descanso, orden) a la rutina. */
    public function agregarEjercicio(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $id = (int) $request->input('id_rutina', 0);
        $rutina = $this->rutinaModelo->buscarPorId($id);

        if (!$rutina || !$this->esPropietario($rutina)) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }

        if (!$request->esPost()) {
            $this->redirect('rutina', 'asignar', ['id' => $id]);
            return;
        }

        $datos = [
            'id_ejercicio' => (int) $request->input('id_ejercicio', 0),
            'dia_semana' => $request->input('dia_semana', ''),
            'series' => (int) $request->input('series', 0),
            'repeticiones' => (int) $request->input('repeticiones', 0),
            'tiempo_descanso' => (int) $request->input('tiempo_descanso', 0),
            'orden' => (int) $request->input('orden', 0),
        ];

        $error = $this->validarDetalle($datos);

        if ($error !== null) {
            $this->render('rutina/asignar', [
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
    public function quitarEjercicio(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        if (!$request->esPost()) {
            $this->redirect('rutina', 'index');
            return;
        }

        $idRutina = (int) $request->input('id_rutina', 0);
        $rutina = $this->rutinaModelo->buscarPorId($idRutina);

        if ($rutina && $this->esPropietario($rutina)) {
            $idDetalle = (int) $request->input('id_detalle', 0);
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
}

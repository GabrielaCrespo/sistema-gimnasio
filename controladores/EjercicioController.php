<?php

/**
 * EjercicioController
 *
 * CU04 - Administrar catálogo de ejercicios, incluida su relación con uno
 * o varios grupos musculares. Accesible por administrador e instructor.
 */
class EjercicioController
{
    /** Extensiones de video local aceptadas al subir el archivo de un ejercicio. */
    private const EXTENSIONES_VIDEO_PERMITIDAS = ['mp4', 'webm', 'ogg', 'mov'];

    /** Tamaño máximo aceptado para el archivo de video (100 MB). */
    private const TAMANO_MAXIMO_VIDEO = 100 * 1024 * 1024;

    /** Carpeta local (fuera del control de versiones) donde se guardan los videos subidos. */
    private const CARPETA_VIDEOS = BASE_PATH . '/videos/';

    private Ejercicio $ejercicioModelo;
    private GrupoMuscular $grupoMuscularModelo;
    private EjercicioGrupoMuscular $ejercicioGrupoModelo;

    public function __construct()
    {
        $this->ejercicioModelo = new Ejercicio();
        $this->grupoMuscularModelo = new GrupoMuscular();
        $this->ejercicioGrupoModelo = new EjercicioGrupoMuscular();
    }

    /** Acceso de gestión del catálogo (crear/editar/eliminar): solo administrador e instructor. */
    private function verificarAcceso(): void
    {
        $this->requireRole(['administrador', 'instructor']);
    }

    public function index(): void
    {
        $this->verificarAcceso();

        $ejercicios = $this->ejercicioModelo->listarTodos();
        $this->render('listar', ['ejercicios' => $ejercicios, 'error' => null]);
    }

    /** El detalle de un ejercicio también lo puede consultar el cliente, para ver sus instrucciones, beneficios y video. */
    public function ver(): void
    {
        $this->requireRole(['administrador', 'instructor', 'cliente']);

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $grupos = $this->ejercicioGrupoModelo->listarGruposPorEjercicio($id);
        $this->render('ver', ['ejercicio' => $ejercicio, 'grupos' => $grupos]);
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $this->render('crear', [
            'error' => null,
            'datos' => [],
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => [],
        ]);
    }

    public function guardar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('ejercicio', 'crear');
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        [$video, $errorVideo] = $this->procesarVideo(null);
        $error = $errorVideo ?? $this->validar($datos['nombre'], null);

        if ($error !== null) {
            $this->render('crear', [
                'error' => $error,
                'datos' => $datos,
                'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
                'gruposSeleccionados' => $gruposSeleccionados,
            ]);
            return;
        }

        $datos['url_video'] = $video;

        $idEjercicio = $this->ejercicioModelo->crear($datos);
        $this->ejercicioGrupoModelo->asociar($idEjercicio, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function editar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicio = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicio) {
            $this->paginaNoEncontrada();
            return;
        }

        $gruposSeleccionados = array_column($this->ejercicioGrupoModelo->listarGruposPorEjercicio($id), 'id_grupo_muscular');

        $this->render('editar', [
            'error' => null,
            'ejercicio' => $ejercicio,
            'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
            'gruposSeleccionados' => $gruposSeleccionados,
        ]);
    }

    public function actualizar(): void
    {
        $this->verificarAcceso();

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;
        $ejercicioExistente = $this->ejercicioModelo->buscarPorId($id);

        if (!$ejercicioExistente) {
            $this->paginaNoEncontrada();
            return;
        }

        $datos = $this->datosFormulario();
        $gruposSeleccionados = array_map('intval', $_POST['grupos'] ?? []);

        [$video, $errorVideo] = $this->procesarVideo($ejercicioExistente['url_video']);
        $error = $errorVideo ?? $this->validar($datos['nombre'], $id);

        if ($error !== null) {
            $this->render('editar', [
                'error' => $error,
                'ejercicio' => ['id_ejercicio' => $id, ...$datos, 'url_video' => $ejercicioExistente['url_video']],
                'gruposDisponibles' => $this->grupoMuscularModelo->listarTodos(),
                'gruposSeleccionados' => $gruposSeleccionados,
            ]);
            return;
        }

        $datos['url_video'] = $video;

        $this->ejercicioModelo->actualizar($id, $datos);
        $this->ejercicioGrupoModelo->asociar($id, $gruposSeleccionados);

        $this->redirect('ejercicio', 'index');
    }

    public function eliminar(): void
    {
        $this->verificarAcceso();

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('ejercicio', 'index');
            return;
        }

        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($id)) { $id = trim($id); }
        $id = (int) $id;

        if (!$this->ejercicioModelo->eliminar($id)) {
            // El ejercicio ya forma parte de alguna rutina (DETALLE_RUTINA
            // lo referencia con ON DELETE RESTRICT): no se puede borrar.
            $this->render('listar', [
                'ejercicios' => $this->ejercicioModelo->listarTodos(),
                'error' => 'No se puede eliminar: el ejercicio está siendo usado en una o más rutinas.',
            ]);
            return;
        }

        $this->redirect('ejercicio', 'index');
    }

    /** Extrae y normaliza los campos del formulario de ejercicio (crear o editar). El video se procesa aparte, ver procesarVideo(). */
    private function datosFormulario(): array
    {
        $nombre = $_POST['nombre'] ?? $_GET['nombre'] ?? '';
        if (is_string($nombre)) { $nombre = trim($nombre); }
        $descripcion = $_POST['descripcion'] ?? $_GET['descripcion'] ?? '';
        if (is_string($descripcion)) { $descripcion = trim($descripcion); }
        $beneficio = $_POST['beneficio'] ?? $_GET['beneficio'] ?? '';
        if (is_string($beneficio)) { $beneficio = trim($beneficio); }
        $indicaciones = $_POST['indicaciones'] ?? $_GET['indicaciones'] ?? '';
        if (is_string($indicaciones)) { $indicaciones = trim($indicaciones); }

        return [
            'nombre' => $nombre,
            'descripcion' => $descripcion ?: null,
            'beneficio' => $beneficio ?: null,
            'indicaciones' => $indicaciones ?: null,
        ];
    }

    /**
     * Procesa el archivo de video local subido en el campo "video" del formulario.
     * Si no se subió un archivo nuevo, conserva $videoActual (el que ya tenía el
     * ejercicio al editar, o null al crear uno nuevo). Devuelve [nombreDeArchivo, error].
     */
    private function procesarVideo(?string $videoActual): array
    {
        if (!isset($_FILES['video']) || $_FILES['video']['error'] === UPLOAD_ERR_NO_FILE) {
            return [$videoActual, null];
        }

        if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            return [$videoActual, match ($_FILES['video']['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'El video supera el tamaño máximo permitido por la configuración actual del servidor (upload_max_filesize/post_max_size en php.ini). Pide al administrador del servidor que los aumente.',
                UPLOAD_ERR_PARTIAL => 'El video se subió solo parcialmente. Revisa tu conexión e inténtalo de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    'El servidor no pudo procesar el archivo subido. Inténtalo de nuevo más tarde.',
                default => 'Ocurrió un error al subir el video. Inténtalo de nuevo.',
            }];
        }

        if ($_FILES['video']['size'] > self::TAMANO_MAXIMO_VIDEO) {
            return [$videoActual, 'El video no puede superar los 100 MB.'];
        }

        $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_VIDEO_PERMITIDAS, true)) {
            return [$videoActual, 'El video debe ser un archivo MP4, WEBM, OGG o MOV.'];
        }

        if (!is_dir(self::CARPETA_VIDEOS) && !mkdir(self::CARPETA_VIDEOS, 0755, true) && !is_dir(self::CARPETA_VIDEOS)) {
            return [$videoActual, 'No se pudo preparar el almacenamiento de videos en el servidor.'];
        }

        // Nombre aleatorio para evitar colisiones y no depender del nombre original del archivo.
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($_FILES['video']['tmp_name'], self::CARPETA_VIDEOS . $nombreArchivo)) {
            return [$videoActual, 'No se pudo guardar el video en el servidor.'];
        }

        return [$nombreArchivo, null];
    }

    /** El nombre es obligatorio y único en el catálogo (coincide con la restricción UNIQUE de la tabla). */
    private function validar(string $nombre, ?int $idAExcluir): ?string
    {
        if ($nombre === '') {
            return 'El nombre es obligatorio.';
        }

        if ($this->ejercicioModelo->existeNombre($nombre, $idAExcluir)) {
            return 'Ya existe un ejercicio con ese nombre.';
        }

        return null;
    }

    /** Muestra un error 404 minimal cuando se pide un id_ejercicio que no existe. */
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

    /** Muestra la vista de ejercicio (vistas/ejercicio.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/ejercicio.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}

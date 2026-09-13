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

    /** Extensiones de imagen aceptadas al subir la imagen de un ejercicio. */
    private const EXTENSIONES_IMAGEN_PERMITIDAS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** Tamaño máximo aceptado para el archivo de imagen (10 MB). */
    private const TAMANO_MAXIMO_IMAGEN = 10 * 1024 * 1024;

    /** Carpeta local (fuera del control de versiones) donde se guardan los videos subidos, dentro de la carpeta única "archivos/". */
    private const CARPETA_VIDEOS = BASE_PATH . '/archivos/videos/';

    /** Carpeta local (fuera del control de versiones) donde se guardan las imágenes subidas, dentro de la carpeta única "archivos/". */
    private const CARPETA_IMAGENES = BASE_PATH . '/archivos/imagenes/';

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

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $ejercicios = $this->ejercicioModelo->listarTodos();

        require BASE_PATH . '/vistas/ejercicio/listar.php';
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

        $usuarioSesion = $_SESSION['user'];
        $grupos = $this->ejercicioGrupoModelo->listarGruposPorEjercicio($id);

        require BASE_PATH . '/vistas/ejercicio/ver.php';
    }

    public function crear(): void
    {
        $this->verificarAcceso();

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $datos = [];
        $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();
        $gruposSeleccionados = [];

        require BASE_PATH . '/vistas/ejercicio/crear.php';
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
        [$imagen1, $errorImagen1] = $this->procesarImagen('imagen_1', null);
        [$imagen2, $errorImagen2] = $this->procesarImagen('imagen_2', null);
        $error = $errorVideo ?? $errorImagen1 ?? $errorImagen2 ?? $this->validar($datos['nombre'], null);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/crear.php';
            return;
        }

        $datos['url_video'] = $video;
        $datos['url_imagen_1'] = $imagen1;
        $datos['url_imagen_2'] = $imagen2;

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

        $usuarioSesion = $_SESSION['user'];
        $error = null;
        $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();
        $gruposSeleccionados = array_column($this->ejercicioGrupoModelo->listarGruposPorEjercicio($id), 'id_grupo_muscular');

        require BASE_PATH . '/vistas/ejercicio/editar.php';
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
        [$imagen1, $errorImagen1] = $this->procesarImagen('imagen_1', $ejercicioExistente['url_imagen_1']);
        [$imagen2, $errorImagen2] = $this->procesarImagen('imagen_2', $ejercicioExistente['url_imagen_2']);
        $error = $errorVideo ?? $errorImagen1 ?? $errorImagen2 ?? $this->validar($datos['nombre'], $id);

        if ($error !== null) {
            $usuarioSesion = $_SESSION['user'];
            $ejercicio = [
                'id_ejercicio' => $id,
                ...$datos,
                'url_video' => $ejercicioExistente['url_video'],
                'url_imagen_1' => $ejercicioExistente['url_imagen_1'],
                'url_imagen_2' => $ejercicioExistente['url_imagen_2'],
            ];
            $gruposDisponibles = $this->grupoMuscularModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/editar.php';
            return;
        }

        $datos['url_video'] = $video;
        $datos['url_imagen_1'] = $imagen1;
        $datos['url_imagen_2'] = $imagen2;

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
            $usuarioSesion = $_SESSION['user'];
            $error = 'No se puede eliminar: el ejercicio está siendo usado en una o más rutinas.';
            $ejercicios = $this->ejercicioModelo->listarTodos();

            require BASE_PATH . '/vistas/ejercicio/listar.php';
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

    /**
     * Procesa el archivo de imagen local subido en el campo $campoFormulario del
     * formulario (el ejercicio admite dos imágenes: "imagen_1" e "imagen_2", cada
     * una se procesa con su propia llamada a este método). Si no se subió un
     * archivo nuevo, conserva $imagenActual (la que ya tenía el ejercicio en esa
     * posición al editar, o null al crear uno nuevo). Devuelve [nombreDeArchivo, error].
     */
    private function procesarImagen(string $campoFormulario, ?string $imagenActual): array
    {
        if (!isset($_FILES[$campoFormulario]) || $_FILES[$campoFormulario]['error'] === UPLOAD_ERR_NO_FILE) {
            return [$imagenActual, null];
        }

        if ($_FILES[$campoFormulario]['error'] !== UPLOAD_ERR_OK) {
            return [$imagenActual, match ($_FILES[$campoFormulario]['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'La imagen supera el tamaño máximo permitido por la configuración actual del servidor (upload_max_filesize/post_max_size en php.ini). Pide al administrador del servidor que los aumente.',
                UPLOAD_ERR_PARTIAL => 'La imagen se subió solo parcialmente. Revisa tu conexión e inténtalo de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    'El servidor no pudo procesar el archivo subido. Inténtalo de nuevo más tarde.',
                default => 'Ocurrió un error al subir la imagen. Inténtalo de nuevo.',
            }];
        }

        if ($_FILES[$campoFormulario]['size'] > self::TAMANO_MAXIMO_IMAGEN) {
            return [$imagenActual, 'La imagen no puede superar los 10 MB.'];
        }

        $extension = strtolower(pathinfo($_FILES[$campoFormulario]['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_IMAGEN_PERMITIDAS, true)) {
            return [$imagenActual, 'La imagen debe ser un archivo JPG, PNG, WEBP o GIF.'];
        }

        if (!is_dir(self::CARPETA_IMAGENES) && !mkdir(self::CARPETA_IMAGENES, 0755, true) && !is_dir(self::CARPETA_IMAGENES)) {
            return [$imagenActual, 'No se pudo preparar el almacenamiento de imágenes en el servidor.'];
        }

        // Nombre aleatorio para evitar colisiones y no depender del nombre original del archivo.
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($_FILES[$campoFormulario]['tmp_name'], self::CARPETA_IMAGENES . $nombreArchivo)) {
            return [$imagenActual, 'No se pudo guardar la imagen en el servidor.'];
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

    /** Carga la vista de error 404 cuando se pide un id_ejercicio que no existe. */
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

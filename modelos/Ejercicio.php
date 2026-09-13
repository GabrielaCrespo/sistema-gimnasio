<?php

/**
 * Ejercicio
 *
 * Acceso a datos del catálogo EJERCICIO (CU04). Cada ejercicio puede estar
 * asociado a varios grupos musculares; esa relación N:M vive en
 * EjercicioGrupoMuscular.php, no aquí. También se encarga de almacenar en
 * disco el video y las imágenes del ejercicio: son datos del ejercicio igual
 * que su nombre o su descripción, solo que persistidos como archivo en vez
 * de como columna, así que su almacenamiento es responsabilidad del modelo
 * y no del controlador.
 */
class Ejercicio
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

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO EJERCICIO (nombre, descripcion, beneficio, indicaciones, url_video, url_imagen_1, url_imagen_2)
                VALUES (:nombre, :descripcion, :beneficio, :indicaciones, :url_video, :url_imagen_1, :url_imagen_2)
                RETURNING id_ejercicio';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'beneficio' => $datos['beneficio'],
            'indicaciones' => $datos['indicaciones'],
            'url_video' => $datos['url_video'],
            'url_imagen_1' => $datos['url_imagen_1'],
            'url_imagen_2' => $datos['url_imagen_2'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE EJERCICIO
                SET nombre = :nombre, descripcion = :descripcion, beneficio = :beneficio,
                    indicaciones = :indicaciones, url_video = :url_video,
                    url_imagen_1 = :url_imagen_1, url_imagen_2 = :url_imagen_2
                WHERE id_ejercicio = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'beneficio' => $datos['beneficio'],
            'indicaciones' => $datos['indicaciones'],
            'url_video' => $datos['url_video'],
            'url_imagen_1' => $datos['url_imagen_1'],
            'url_imagen_2' => $datos['url_imagen_2'],
            'id' => $id,
        ]);
    }

    /**
     * Elimina un ejercicio del catálogo. EJERCICIO_GRUPO_MUSCULAR tiene
     * ON DELETE CASCADE hacia esta tabla (sus asociaciones se limpian
     * solas), pero DETALLE_RUTINA lo referencia con ON DELETE RESTRICT:
     * si el ejercicio ya forma parte de alguna rutina, PostgreSQL rechaza
     * el borrado y devolvemos false para que el controlador avise al usuario.
     */
    public function eliminar(int $id): bool
    {
        try {
            $sentencia = $this->db->prepare('DELETE FROM EJERCICIO WHERE id_ejercicio = :id');
            $sentencia->execute(['id' => $id]);

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM EJERCICIO WHERE id_ejercicio = :id');
        $sentencia->execute(['id' => $id]);
        $ejercicio = $sentencia->fetch();

        return $ejercicio ?: null;
    }

    public function buscarPorNombre(string $nombre): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM EJERCICIO WHERE nombre = :nombre');
        $sentencia->execute(['nombre' => $nombre]);
        $ejercicio = $sentencia->fetch();

        return $ejercicio ?: null;
    }

    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM EJERCICIO ORDER BY nombre')->fetchAll();
    }

    /**
     * true si ya existe un ejercicio con ese nombre. $idExcluir permite que
     * un ejercicio conserve su propio nombre al editarlo sin que la
     * validación lo interprete como un duplicado.
     */
    public function existeNombre(string $nombre, ?int $idExcluir = null): bool
    {
        $ejercicio = $this->buscarPorNombre($nombre);

        return $ejercicio !== null && (int) $ejercicio['id_ejercicio'] !== $idExcluir;
    }

    /**
     * Guarda en disco el video subido de un ejercicio. $archivoSubido es la
     * entrada de $_FILES tal como llega del formulario (o null si el campo
     * no venía en la petición). Si no se subió un archivo nuevo, conserva
     * $videoActual (el que ya tenía el ejercicio al editar, o null al crear
     * uno nuevo). Devuelve [nombreDeArchivo, error].
     */
    public function guardarVideo(?array $archivoSubido, ?string $videoActual): array
    {
        if ($archivoSubido === null || $archivoSubido['error'] === UPLOAD_ERR_NO_FILE) {
            return [$videoActual, null];
        }

        if ($archivoSubido['error'] !== UPLOAD_ERR_OK) {
            return [$videoActual, match ($archivoSubido['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'El video supera el tamaño máximo permitido por la configuración actual del servidor (upload_max_filesize/post_max_size en php.ini). Pide al administrador del servidor que los aumente.',
                UPLOAD_ERR_PARTIAL => 'El video se subió solo parcialmente. Revisa tu conexión e inténtalo de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    'El servidor no pudo procesar el archivo subido. Inténtalo de nuevo más tarde.',
                default => 'Ocurrió un error al subir el video. Inténtalo de nuevo.',
            }];
        }

        if ($archivoSubido['size'] > self::TAMANO_MAXIMO_VIDEO) {
            return [$videoActual, 'El video no puede superar los 100 MB.'];
        }

        $extension = strtolower(pathinfo($archivoSubido['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_VIDEO_PERMITIDAS, true)) {
            return [$videoActual, 'El video debe ser un archivo MP4, WEBM, OGG o MOV.'];
        }

        if (!is_dir(self::CARPETA_VIDEOS) && !mkdir(self::CARPETA_VIDEOS, 0755, true) && !is_dir(self::CARPETA_VIDEOS)) {
            return [$videoActual, 'No se pudo preparar el almacenamiento de videos en el servidor.'];
        }

        // Nombre aleatorio para evitar colisiones y no depender del nombre original del archivo.
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($archivoSubido['tmp_name'], self::CARPETA_VIDEOS . $nombreArchivo)) {
            return [$videoActual, 'No se pudo guardar el video en el servidor.'];
        }

        return [$nombreArchivo, null];
    }

    /**
     * Guarda en disco una de las dos imágenes subidas de un ejercicio.
     * $archivoSubido es la entrada de $_FILES tal como llega del formulario
     * (o null si el campo no venía en la petición). Si no se subió un
     * archivo nuevo, conserva $imagenActual (la que ya tenía el ejercicio en
     * esa posición al editar, o null al crear uno nuevo). Devuelve
     * [nombreDeArchivo, error].
     */
    public function guardarImagen(?array $archivoSubido, ?string $imagenActual): array
    {
        if ($archivoSubido === null || $archivoSubido['error'] === UPLOAD_ERR_NO_FILE) {
            return [$imagenActual, null];
        }

        if ($archivoSubido['error'] !== UPLOAD_ERR_OK) {
            return [$imagenActual, match ($archivoSubido['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'La imagen supera el tamaño máximo permitido por la configuración actual del servidor (upload_max_filesize/post_max_size en php.ini). Pide al administrador del servidor que los aumente.',
                UPLOAD_ERR_PARTIAL => 'La imagen se subió solo parcialmente. Revisa tu conexión e inténtalo de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    'El servidor no pudo procesar el archivo subido. Inténtalo de nuevo más tarde.',
                default => 'Ocurrió un error al subir la imagen. Inténtalo de nuevo.',
            }];
        }

        if ($archivoSubido['size'] > self::TAMANO_MAXIMO_IMAGEN) {
            return [$imagenActual, 'La imagen no puede superar los 10 MB.'];
        }

        $extension = strtolower(pathinfo($archivoSubido['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONES_IMAGEN_PERMITIDAS, true)) {
            return [$imagenActual, 'La imagen debe ser un archivo JPG, PNG, WEBP o GIF.'];
        }

        if (!is_dir(self::CARPETA_IMAGENES) && !mkdir(self::CARPETA_IMAGENES, 0755, true) && !is_dir(self::CARPETA_IMAGENES)) {
            return [$imagenActual, 'No se pudo preparar el almacenamiento de imágenes en el servidor.'];
        }

        // Nombre aleatorio para evitar colisiones y no depender del nombre original del archivo.
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($archivoSubido['tmp_name'], self::CARPETA_IMAGENES . $nombreArchivo)) {
            return [$imagenActual, 'No se pudo guardar la imagen en el servidor.'];
        }

        return [$nombreArchivo, null];
    }
}

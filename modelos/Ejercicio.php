<?php

/**
 * Ejercicio
 *
 * Acceso a datos del catálogo EJERCICIO (CU04). Cada ejercicio puede estar
 * asociado a varios grupos musculares; esa relación N:M vive en
 * EjercicioGrupoMuscular.php, no aquí.
 */
class Ejercicio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO EJERCICIO (nombre, descripcion, beneficio, indicaciones, url_video)
                VALUES (:nombre, :descripcion, :beneficio, :indicaciones, :url_video)
                RETURNING id_ejercicio';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'beneficio' => $datos['beneficio'],
            'indicaciones' => $datos['indicaciones'],
            'url_video' => $datos['url_video'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE EJERCICIO
                SET nombre = :nombre, descripcion = :descripcion, beneficio = :beneficio,
                    indicaciones = :indicaciones, url_video = :url_video
                WHERE id_ejercicio = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'beneficio' => $datos['beneficio'],
            'indicaciones' => $datos['indicaciones'],
            'url_video' => $datos['url_video'],
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
}

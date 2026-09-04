<?php

/**
 * GrupoMuscular
 *
 * Acceso a datos del catálogo GRUPO_MUSCULAR (CU03). Es un catálogo simple
 * sin tablas hijas propias; su única relación es con EJERCICIO a través de
 * la tabla pivote EJERCICIO_GRUPO_MUSCULAR (ver EjercicioGrupoMuscular.php).
 */
class GrupoMuscular extends Model
{
    public function crear(string $nombre, ?string $descripcion): int
    {
        $sentencia = $this->db->prepare(
            'INSERT INTO GRUPO_MUSCULAR (nombre, descripcion) VALUES (:nombre, :descripcion) RETURNING id_grupo_muscular'
        );
        $sentencia->execute(['nombre' => $nombre, 'descripcion' => $descripcion]);

        return (int) $sentencia->fetchColumn();
    }

    public function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $sentencia = $this->db->prepare(
            'UPDATE GRUPO_MUSCULAR SET nombre = :nombre, descripcion = :descripcion WHERE id_grupo_muscular = :id'
        );
        $sentencia->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'id' => $id]);
    }

    /**
     * Elimina un grupo muscular del catálogo. EJERCICIO_GRUPO_MUSCULAR
     * tiene ON DELETE CASCADE hacia esta tabla, así que las asociaciones
     * con ejercicios se eliminan automáticamente; los ejercicios en sí no
     * se ven afectados.
     */
    public function eliminar(int $id): void
    {
        $sentencia = $this->db->prepare('DELETE FROM GRUPO_MUSCULAR WHERE id_grupo_muscular = :id');
        $sentencia->execute(['id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM GRUPO_MUSCULAR WHERE id_grupo_muscular = :id');
        $sentencia->execute(['id' => $id]);
        $grupo = $sentencia->fetch();

        return $grupo ?: null;
    }

    public function buscarPorNombre(string $nombre): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM GRUPO_MUSCULAR WHERE nombre = :nombre');
        $sentencia->execute(['nombre' => $nombre]);
        $grupo = $sentencia->fetch();

        return $grupo ?: null;
    }

    /** Lista el catálogo completo, usado tanto en CU03 (administrarlo) como en CU04 (asociarlo a ejercicios). */
    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM GRUPO_MUSCULAR ORDER BY nombre')->fetchAll();
    }

    /**
     * true si ya existe un grupo muscular con ese nombre. $idExcluir permite
     * que un grupo conserve su propio nombre al editarlo sin que la
     * validación lo interprete como un duplicado.
     */
    public function existeNombre(string $nombre, ?int $idExcluir = null): bool
    {
        $grupo = $this->buscarPorNombre($nombre);

        return $grupo !== null && (int) $grupo['id_grupo_muscular'] !== $idExcluir;
    }
}

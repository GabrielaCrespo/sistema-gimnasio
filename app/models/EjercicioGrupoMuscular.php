<?php

/**
 * EjercicioGrupoMuscular
 *
 * Acceso a la tabla pivote EJERCICIO_GRUPO_MUSCULAR, que representa la
 * relación N:M entre EJERCICIO y GRUPO_MUSCULAR (CU04). No se usa UPDATE:
 * cada vez que se guarda un ejercicio se reemplazan sus asociaciones por
 * completo con un DELETE + INSERT, que es más simple y seguro que calcular
 * qué filas agregar o quitar comparando dos listas.
 */
class EjercicioGrupoMuscular extends Model
{
    /** Reemplaza las asociaciones de un ejercicio por la lista de ids de grupo muscular recibida. */
    public function asociar(int $idEjercicio, array $idsGrupoMuscular): void
    {
        $eliminar = $this->db->prepare('DELETE FROM EJERCICIO_GRUPO_MUSCULAR WHERE id_ejercicio = :id_ejercicio');
        $eliminar->execute(['id_ejercicio' => $idEjercicio]);

        $insertar = $this->db->prepare(
            'INSERT INTO EJERCICIO_GRUPO_MUSCULAR (id_ejercicio, id_grupo_muscular) VALUES (:id_ejercicio, :id_grupo_muscular)'
        );

        foreach ($idsGrupoMuscular as $idGrupoMuscular) {
            $insertar->execute(['id_ejercicio' => $idEjercicio, 'id_grupo_muscular' => (int) $idGrupoMuscular]);
        }
    }

    /** Devuelve los grupos musculares asociados a un ejercicio. */
    public function listarGruposPorEjercicio(int $idEjercicio): array
    {
        $sql = 'SELECT gm.*
                FROM EJERCICIO_GRUPO_MUSCULAR egm
                JOIN GRUPO_MUSCULAR gm ON gm.id_grupo_muscular = egm.id_grupo_muscular
                WHERE egm.id_ejercicio = :id_ejercicio
                ORDER BY gm.nombre';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_ejercicio' => $idEjercicio]);

        return $sentencia->fetchAll();
    }

    /** Devuelve los ejercicios asociados a un grupo muscular. */
    public function listarEjerciciosPorGrupo(int $idGrupoMuscular): array
    {
        $sql = 'SELECT e.*
                FROM EJERCICIO_GRUPO_MUSCULAR egm
                JOIN EJERCICIO e ON e.id_ejercicio = egm.id_ejercicio
                WHERE egm.id_grupo_muscular = :id_grupo_muscular
                ORDER BY e.nombre';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_grupo_muscular' => $idGrupoMuscular]);

        return $sentencia->fetchAll();
    }
}

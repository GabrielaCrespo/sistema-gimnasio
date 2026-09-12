<?php

/**
 * DetalleRutina
 *
 * Acceso a datos de DETALLE_RUTINA: los ejercicios que componen una
 * rutina, con el día de la semana, series, repeticiones, descanso y el
 * orden en que se realizan (CU06).
 */
class DetalleRutina
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Agrega un ejercicio a una rutina y devuelve el id del detalle creado. El peso es opcional (ej. ejercicios sin carga externa). */
    public function agregar(int $idRutina, array $datos): int
    {
        $sql = 'INSERT INTO DETALLE_RUTINA (id_rutina, id_ejercicio, dia_semana, series, repeticiones, peso, tiempo_descanso, orden)
                VALUES (:id_rutina, :id_ejercicio, :dia_semana, :series, :repeticiones, :peso, :tiempo_descanso, :orden)
                RETURNING id_detalle';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'id_rutina' => $idRutina,
            'id_ejercicio' => $datos['id_ejercicio'],
            'dia_semana' => $datos['dia_semana'],
            'series' => $datos['series'],
            'repeticiones' => $datos['repeticiones'],
            'peso' => $datos['peso'],
            'tiempo_descanso' => $datos['tiempo_descanso'],
            'orden' => $datos['orden'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    /**
     * Quita un ejercicio de la rutina. Se filtra también por id_rutina
     * (además del id_detalle) para que un instructor no pueda borrar,
     * adivinando el id, un detalle de una rutina que no le pertenece.
     */
    public function eliminar(int $idDetalle, int $idRutina): void
    {
        $sentencia = $this->db->prepare(
            'DELETE FROM DETALLE_RUTINA WHERE id_detalle = :id_detalle AND id_rutina = :id_rutina'
        );
        $sentencia->execute(['id_detalle' => $idDetalle, 'id_rutina' => $idRutina]);
    }

    /**
     * Actualiza una fila de DETALLE_RUTINA ya asignada (día, series, repeticiones,
     * peso, descanso y orden). Se filtra también por id_rutina, igual que en
     * eliminar(), para que un instructor no pueda editar un detalle de una
     * rutina que no le pertenece.
     */
    public function actualizar(int $idDetalle, int $idRutina, array $datos): void
    {
        $sql = 'UPDATE DETALLE_RUTINA
                SET id_ejercicio = :id_ejercicio, dia_semana = :dia_semana, series = :series,
                    repeticiones = :repeticiones, peso = :peso, tiempo_descanso = :tiempo_descanso, orden = :orden
                WHERE id_detalle = :id_detalle AND id_rutina = :id_rutina';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'id_ejercicio' => $datos['id_ejercicio'],
            'dia_semana' => $datos['dia_semana'],
            'series' => $datos['series'],
            'repeticiones' => $datos['repeticiones'],
            'peso' => $datos['peso'],
            'tiempo_descanso' => $datos['tiempo_descanso'],
            'orden' => $datos['orden'],
            'id_detalle' => $idDetalle,
            'id_rutina' => $idRutina,
        ]);
    }

    /**
     * Busca una fila de DETALLE_RUTINA por su id, filtrando también por
     * id_rutina para no exponer (ni permitir editar) el detalle de una
     * rutina ajena.
     */
    public function buscarPorId(int $idDetalle, int $idRutina): ?array
    {
        $sentencia = $this->db->prepare(
            'SELECT * FROM DETALLE_RUTINA WHERE id_detalle = :id_detalle AND id_rutina = :id_rutina'
        );
        $sentencia->execute(['id_detalle' => $idDetalle, 'id_rutina' => $idRutina]);
        $detalle = $sentencia->fetch();

        return $detalle ?: null;
    }

    /** Ejercicios de una rutina, ordenados por día y por el orden definido dentro de cada día. */
    public function listarPorRutina(int $idRutina): array
    {
        $sql = 'SELECT dr.*, e.nombre AS ejercicio_nombre
                FROM DETALLE_RUTINA dr
                JOIN EJERCICIO e ON e.id_ejercicio = dr.id_ejercicio
                WHERE dr.id_rutina = :id_rutina
                ORDER BY dr.dia_semana, dr.orden';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_rutina' => $idRutina]);

        return $sentencia->fetchAll();
    }
}

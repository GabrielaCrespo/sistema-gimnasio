<?php

/**
 * EvaluacionFisica
 *
 * Acceso a datos de EVALUACION_FISICA (CU05: registrar evaluaciones y
 * consultar el historial). Cada evaluación queda ligada al cliente evaluado y al
 * instructor que la registró.
 */
class EvaluacionFisica
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Registra una nueva evaluación física y devuelve su id. */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO EVALUACION_FISICA
                    (peso, altura, objetivo, porcentaje_grasa, masa_muscular, flexibilidad, observaciones, id_cliente, id_instructor)
                VALUES
                    (:peso, :altura, :objetivo, :porcentaje_grasa, :masa_muscular, :flexibilidad, :observaciones, :id_cliente, :id_instructor)
                RETURNING id_evaluacion_fisica';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'peso' => $datos['peso'],
            'altura' => $datos['altura'],
            'objetivo' => $datos['objetivo'],
            'porcentaje_grasa' => $datos['porcentaje_grasa'],
            'masa_muscular' => $datos['masa_muscular'],
            'flexibilidad' => $datos['flexibilidad'],
            'observaciones' => $datos['observaciones'],
            'id_cliente' => $datos['id_cliente'],
            'id_instructor' => $datos['id_instructor'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    /** Historial de evaluaciones de un cliente, de la más reciente a la más antigua (CU05). */
    public function listarPorCliente(int $idCliente): array
    {
        $sql = 'SELECT ef.*, i.nombres AS instructor_nombres, i.apellidos AS instructor_apellidos
                FROM EVALUACION_FISICA ef
                JOIN INSTRUCTOR i ON i.id_instructor = ef.id_instructor
                WHERE ef.id_cliente = :id_cliente
                ORDER BY ef.fecha DESC, ef.id_evaluacion_fisica DESC';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_cliente' => $idCliente]);

        return $sentencia->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM EVALUACION_FISICA WHERE id_evaluacion_fisica = :id');
        $sentencia->execute(['id' => $id]);
        $evaluacion = $sentencia->fetch();

        return $evaluacion ?: null;
    }

    /** Evaluación con nombres del cliente y del instructor, para la vista de detalle. */
    public function buscarPorIdConNombres(int $id): ?array
    {
        $sql = 'SELECT ef.*, c.nombres AS cliente_nombres, c.apellidos AS cliente_apellidos,
                       i.nombres AS instructor_nombres, i.apellidos AS instructor_apellidos
                FROM EVALUACION_FISICA ef
                JOIN CLIENTE c ON c.id_cliente = ef.id_cliente
                JOIN INSTRUCTOR i ON i.id_instructor = ef.id_instructor
                WHERE ef.id_evaluacion_fisica = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id' => $id]);
        $evaluacion = $sentencia->fetch();

        return $evaluacion ?: null;
    }

    /** Actualiza los datos registrados de una evaluación física existente. No cambia ni el cliente ni el instructor que la registró. */
    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE EVALUACION_FISICA
                SET peso = :peso, altura = :altura, objetivo = :objetivo,
                    porcentaje_grasa = :porcentaje_grasa, masa_muscular = :masa_muscular,
                    flexibilidad = :flexibilidad, observaciones = :observaciones
                WHERE id_evaluacion_fisica = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'peso' => $datos['peso'],
            'altura' => $datos['altura'],
            'objetivo' => $datos['objetivo'],
            'porcentaje_grasa' => $datos['porcentaje_grasa'],
            'masa_muscular' => $datos['masa_muscular'],
            'flexibilidad' => $datos['flexibilidad'],
            'observaciones' => $datos['observaciones'],
            'id' => $id,
        ]);
    }

    /** Elimina una evaluación física registrada. */
    public function eliminar(int $id): void
    {
        $sentencia = $this->db->prepare('DELETE FROM EVALUACION_FISICA WHERE id_evaluacion_fisica = :id');
        $sentencia->execute(['id' => $id]);
    }
}

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
        $sql = 'SELECT ef.*, u.nombres AS instructor_nombres, u.apellidos AS instructor_apellidos
                FROM EVALUACION_FISICA ef
                JOIN USUARIO u ON u.id_usuario = ef.id_instructor
                WHERE ef.id_cliente = :id_cliente
                ORDER BY ef.fecha DESC, ef.id_evaluacion_fisica DESC';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_cliente' => $idCliente]);

        return $sentencia->fetchAll();
    }
}

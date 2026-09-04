<?php

/**
 * Rutina
 *
 * Acceso a datos de RUTINA (CU06: crear, gestionar y visualizar rutinas).
 * Cada rutina pertenece a un único cliente y fue creada por un instructor; los
 * ejercicios que la componen viven en DETALLE_RUTINA (ver DetalleRutina.php).
 */
class Rutina extends Model
{
    /** Crea una rutina nueva (sin ejercicios todavía) y devuelve su id. */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO RUTINA (nombre, tipo, fecha_inicio, fecha_fin, id_cliente, id_instructor)
                VALUES (:nombre, :tipo, :fecha_inicio, :fecha_fin, :id_cliente, :id_instructor)
                RETURNING id_rutina';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'id_cliente' => $datos['id_cliente'],
            'id_instructor' => $datos['id_instructor'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    /** Actualiza los datos generales de la rutina (nombre, tipo, fechas, estado). El cliente y el instructor no cambian. */
    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE RUTINA
                SET nombre = :nombre, tipo = :tipo, fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin, estado = :estado
                WHERE id_rutina = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'estado' => $datos['estado'],
            'id' => $id,
        ]);
    }

    /** Devuelve la rutina junto con el nombre de su cliente e instructor, listos para mostrar sin consultas adicionales. */
    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT r.*,
                       uc.nombres AS cliente_nombres, uc.apellidos AS cliente_apellidos,
                       ui.nombres AS instructor_nombres, ui.apellidos AS instructor_apellidos
                FROM RUTINA r
                JOIN USUARIO uc ON uc.id_usuario = r.id_cliente
                JOIN USUARIO ui ON ui.id_usuario = r.id_instructor
                WHERE r.id_rutina = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id' => $id]);
        $rutina = $sentencia->fetch();

        return $rutina ?: null;
    }

    /** Rutinas asignadas a un cliente (CU06), de la más reciente a la más antigua. */
    public function listarPorCliente(int $idCliente): array
    {
        $sql = 'SELECT r.*, ui.nombres AS instructor_nombres, ui.apellidos AS instructor_apellidos
                FROM RUTINA r
                JOIN USUARIO ui ON ui.id_usuario = r.id_instructor
                WHERE r.id_cliente = :id_cliente
                ORDER BY r.fecha_inicio DESC';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_cliente' => $idCliente]);

        return $sentencia->fetchAll();
    }

    /** Rutinas creadas por un instructor (CU06), para su panel de gestión. */
    public function listarPorInstructor(int $idInstructor): array
    {
        $sql = 'SELECT r.*, uc.nombres AS cliente_nombres, uc.apellidos AS cliente_apellidos
                FROM RUTINA r
                JOIN USUARIO uc ON uc.id_usuario = r.id_cliente
                WHERE r.id_instructor = :id_instructor
                ORDER BY r.fecha_inicio DESC';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_instructor' => $idInstructor]);

        return $sentencia->fetchAll();
    }
}

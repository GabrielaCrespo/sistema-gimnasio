<?php

/**
 * Instructor
 *
 * Acceso a datos de la tabla INSTRUCTOR, que extiende a USUARIO cuando el
 * rol es 'instructor' (id_usuario es a la vez PK y FK, herencia por tabla).
 * Guarda el único dato propio del instructor: su especialidad.
 */
class Instructor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Crea la fila INSTRUCTOR asociada a un usuario ya existente con rol 'instructor'. */
    public function crear(int $idUsuario, string $especialidad): void
    {
        $sentencia = $this->db->prepare(
            'INSERT INTO INSTRUCTOR (id_usuario, especialidad) VALUES (:id_usuario, :especialidad)'
        );
        $sentencia->execute(['id_usuario' => $idUsuario, 'especialidad' => $especialidad]);
    }

    public function actualizar(int $idUsuario, string $especialidad): void
    {
        $sentencia = $this->db->prepare(
            'UPDATE INSTRUCTOR SET especialidad = :especialidad WHERE id_usuario = :id_usuario'
        );
        $sentencia->execute(['especialidad' => $especialidad, 'id_usuario' => $idUsuario]);
    }

    /** Devuelve los datos del instructor combinados con los de USUARIO. */
    public function buscarPorId(int $idUsuario): ?array
    {
        $sql = 'SELECT u.*, i.especialidad
                FROM INSTRUCTOR i
                JOIN USUARIO u ON u.id_usuario = i.id_usuario
                WHERE i.id_usuario = :id_usuario';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);
        $instructor = $sentencia->fetch();

        return $instructor ?: null;
    }

    /** Lista todos los instructores con sus datos de USUARIO. */
    public function listarTodos(): array
    {
        $sql = 'SELECT u.*, i.especialidad
                FROM INSTRUCTOR i
                JOIN USUARIO u ON u.id_usuario = i.id_usuario
                ORDER BY u.apellidos, u.nombres';

        return $this->db->query($sql)->fetchAll();
    }
}

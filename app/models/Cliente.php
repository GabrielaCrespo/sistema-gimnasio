<?php

/**
 * Modelo Cliente
 *
 * Gestiona la tabla CLIENTE, que extiende a USUARIO cuando el rol es 'cliente'.
 * Guarda datos específicos: fecha de registro, altura y peso.
 */
class Cliente extends Model
{
    /** Crea la fila CLIENTE asociada a un usuario ya existente con rol 'cliente'. */
    public function crear(int $idUsuario, ?float $altura, ?float $peso): void
    {
        $sentencia = $this->db->prepare(
            'INSERT INTO CLIENTE (id_usuario, altura, peso) VALUES (:id_usuario, :altura, :peso)'
        );
        $sentencia->execute(['id_usuario' => $idUsuario, 'altura' => $altura, 'peso' => $peso]);
    }

    /** Actualiza altura/peso del cliente (desde su perfil o desde una evaluación física). */
    public function actualizar(int $idUsuario, ?float $altura, ?float $peso): void
    {
        $sentencia = $this->db->prepare(
            'UPDATE CLIENTE SET altura = :altura, peso = :peso WHERE id_usuario = :id_usuario'
        );
        $sentencia->execute(['altura' => $altura, 'peso' => $peso, 'id_usuario' => $idUsuario]);
    }

    /**
     * Obtiene los datos completos de un cliente (de CLIENTE + USUARIO).
     * Devuelve los datos combinados o null si no existe.
     */
    public function buscarPorId(int $idUsuario): ?array
    {
        $sql = 'SELECT u.*, c.fecha_registro, c.altura, c.peso
                FROM CLIENTE c
                JOIN USUARIO u ON u.id_usuario = c.id_usuario
                WHERE c.id_usuario = :id_usuario';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute(['id_usuario' => $idUsuario]);
        $cliente = $sentencia->fetch();

        return $cliente ?: null;
    }

    /**
     * Lista todos los clientes con sus datos de USUARIO (usado por el instructor para elegir a quién evaluar o asignar una rutina).
     */
    public function listarTodos(): array
    {
        $sql = 'SELECT u.*, c.fecha_registro, c.altura, c.peso
                FROM CLIENTE c
                JOIN USUARIO u ON u.id_usuario = c.id_usuario
                ORDER BY u.apellidos, u.nombres';

        return $this->db->query($sql)->fetchAll();
    }
}

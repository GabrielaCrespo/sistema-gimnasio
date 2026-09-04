<?php

/**
 * Model
 *
 * Clase base de la que heredan todos los modelos (Usuario, Ejercicio,
 * Rutina, etc.). Da acceso a la conexión PDO ya lista para usar. Aquí NO
 * se genera HTML ni se toca $_SESSION: los modelos solo ejecutan consultas
 * SQL contra PostgreSQL, siempre con sentencias preparadas.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}

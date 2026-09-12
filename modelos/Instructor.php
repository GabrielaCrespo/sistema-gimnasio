<?php

/**
 * Modelo Instructor
 *
 * Acceso a datos de la tabla INSTRUCTOR, independiente y con sus propias
 * columnas de cuenta (ci, correo, password_hash, estado, etc.). Ya no
 * existe una tabla USUARIO común: cada instructor es autosuficiente.
 */
class Instructor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Crea un instructor nuevo. Solo lo hace un administrador (CU02). */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO INSTRUCTOR (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, especialidad)
                VALUES (:ci, :nombres, :apellidos, :fecha_nacimiento, :correo, :password_hash, :especialidad)
                RETURNING id_instructor';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'correo' => $datos['correo'],
            'password_hash' => password_hash($datos['password'], PASSWORD_BCRYPT),
            'especialidad' => $datos['especialidad'],
        ]);

        return (int) $sentencia->fetchColumn();
    }

    /** Actualiza los datos personales y la especialidad del instructor. No toca la contraseña. */
    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE INSTRUCTOR
                SET ci = :ci, nombres = :nombres, apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento, correo = :correo, especialidad = :especialidad
                WHERE id_instructor = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'correo' => $datos['correo'],
            'especialidad' => $datos['especialidad'],
            'id' => $id,
        ]);
    }

    /** Activa o desactiva una cuenta de instructor (CU02). Se usa en vez de DELETE por integridad referencial. */
    public function cambiarEstado(int $id, bool $estado): void
    {
        $sentencia = $this->db->prepare('UPDATE INSTRUCTOR SET estado = :estado WHERE id_instructor = :id');
        $sentencia->execute(['estado' => $estado, 'id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM INSTRUCTOR WHERE id_instructor = :id');
        $sentencia->execute(['id' => $id]);
        $instructor = $sentencia->fetch();

        return $instructor ?: null;
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM INSTRUCTOR WHERE correo = :correo');
        $sentencia->execute(['correo' => $correo]);
        $instructor = $sentencia->fetch();

        return $instructor ?: null;
    }

    public function buscarPorCi(string $ci): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM INSTRUCTOR WHERE ci = :ci');
        $sentencia->execute(['ci' => $ci]);
        $instructor = $sentencia->fetch();

        return $instructor ?: null;
    }

    /** Lista todos los instructores, usada por el administrador (CU02). */
    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM INSTRUCTOR ORDER BY apellidos, nombres')->fetchAll();
    }

    /** Verifica credenciales de login. Recibe correo y contraseña en texto plano. */
    public function verificarCredenciales(string $correo, string $password): ?array
    {
        $instructor = $this->buscarPorCorreo($correo);

        if (!$instructor || !password_verify($password, $instructor['password_hash'])) {
            return null;
        }

        return $instructor;
    }

    /** true si ya existe un instructor con ese correo. $idExcluir permite ediciones de perfil. */
    public function existeCorreo(string $correo, ?int $idExcluir = null): bool
    {
        $instructor = $this->buscarPorCorreo($correo);

        return $instructor !== null && (int) $instructor['id_instructor'] !== $idExcluir;
    }

    /** true si ya existe un instructor con ese CI. $idExcluir permite ediciones de perfil. */
    public function existeCi(string $ci, ?int $idExcluir = null): bool
    {
        $instructor = $this->buscarPorCi($ci);

        return $instructor !== null && (int) $instructor['id_instructor'] !== $idExcluir;
    }
}

<?php

/**
 * Modelo Usuario
 *
 * Gestiona la tabla USUARIO, común a administradores, instructores y clientes.
 * Los datos específicos de cliente e instructor están en sus propios modelos.
 */

class Usuario extends Model
{
    /**
     * Inserta un nuevo usuario en la base de datos.
     * Recibe los datos (incluyendo contraseña en texto plano) y calcula su hash.
     */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO USUARIO (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, rol)
                VALUES (:ci, :nombres, :apellidos, :fecha_nacimiento, :correo, :password_hash, :rol)
                RETURNING id_usuario';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'correo' => $datos['correo'],
            'password_hash' => password_hash($datos['password'], PASSWORD_BCRYPT),
            'rol' => $datos['rol'],
        ]);

        // Devuelve el ID del usuario recién creado.
        return (int) $sentencia->fetchColumn();
    }

    /** Actualiza los datos personales de un usuario. No toca la contraseña ni el rol. */
    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE USUARIO
                SET ci = :ci, nombres = :nombres, apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento, correo = :correo
                WHERE id_usuario = :id';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'correo' => $datos['correo'],
            'id' => $id,
        ]);
    }

    /** Cambia la contraseña de un usuario. Recibe el hash ya generado con password_hash(), nunca la contraseña en texto plano. */
    public function actualizarPassword(int $id, string $passwordHash): void
    {
        $sentencia = $this->db->prepare('UPDATE USUARIO SET password_hash = :hash WHERE id_usuario = :id');
        $sentencia->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    /**
     * Activa o desactiva una cuenta (CU02). 
     * Se usa en vez de DELETE porque INSTRUCTOR/CLIENTE referencian a USUARIO con ON DELETE RESTRICT y,
     * además, un usuario desactivado puede tener evaluaciones o rutinas
     * históricas que deben conservarse íntegras.
     */
    public function cambiarEstado(int $id, bool $estado): void
    {
        $sentencia = $this->db->prepare('UPDATE USUARIO SET estado = :estado WHERE id_usuario = :id');
        $sentencia->execute(['estado' => $estado, 'id' => $id]);
    }

    /** Busca un usuario por su ID y devuelve la fila completa de USUARIO, o null si no existe. */
    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM USUARIO WHERE id_usuario = :id');
        $sentencia->execute(['id' => $id]);
        $usuario = $sentencia->fetch();

        return $usuario ?: null;
    }

    /** Busca un usuario por su correo y devuelve la fila completa de USUARIO, o null si no existe. */
    public function buscarPorCorreo(string $correo): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM USUARIO WHERE correo = :correo');
        $sentencia->execute(['correo' => $correo]);
        $usuario = $sentencia->fetch();

        return $usuario ?: null;
    }

    /** Busca un usuario por su CI y devuelve la fila completa de USUARIO, o null si no existe. */
    public function buscarPorCi(string $ci): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM USUARIO WHERE ci = :ci');
        $sentencia->execute(['ci' => $ci]);
        $usuario = $sentencia->fetch();

        return $usuario ?: null;
    }

    /** Lista todos los usuarios ordenados alfabéticamente, para el panel de administración (CU02). */
    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM USUARIO ORDER BY apellidos, nombres')->fetchAll();
    }

    /**
     * Verifica las credenciales para login.
     * Recibe correo y contraseña en texto plano.
     * Devuelve los datos del usuario si son correctas, o null.
     */
    public function verificarCredenciales(string $correo, string $password): ?array
    {
        // Busca el usuario por correo usando la función ya existente. Esto evita duplicar la lógica de búsqueda.
        $usuario = $this->buscarPorCorreo($correo);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            return null;
        }

        return $usuario;
    }

    /**
     * Comprueba si ya existe una cuenta con ese correo.
     * Permite excluir un id (para ediciones de perfil).
     */
    public function existeCorreo(string $correo, ?int $idExcluir = null): bool
    {
        $usuario = $this->buscarPorCorreo($correo);

        return $usuario !== null && (int) $usuario['id_usuario'] !== $idExcluir;
    }

    /**
     * Comprueba si ya existe una cuenta con ese CI.
     * Igual que existeCorreo pero con CI.
     */
    public function existeCi(string $ci, ?int $idExcluir = null): bool
    {
        $usuario = $this->buscarPorCi($ci);

        return $usuario !== null && (int) $usuario['id_usuario'] !== $idExcluir;
    }
}

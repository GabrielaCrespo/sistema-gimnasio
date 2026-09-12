<?php

/**
 * Modelo Cliente
 *
 * Acceso a datos de la tabla CLIENTE, independiente y con sus propias
 * columnas de cuenta (ci, correo, password_hash, estado, etc.). Ya no
 * existe una tabla USUARIO común: cada cliente es autosuficiente.
 */
class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Crea un cliente nuevo (auto-registro público) y devuelve su id. */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO CLIENTE (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, altura, peso)
                VALUES (:ci, :nombres, :apellidos, :fecha_nacimiento, :correo, :password_hash, :altura, :peso)
                RETURNING id_cliente';

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute([
            'ci' => $datos['ci'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'correo' => $datos['correo'],
            'password_hash' => password_hash($datos['password'], PASSWORD_BCRYPT),
            'altura' => $datos['altura'] ?? null,
            'peso' => $datos['peso'] ?? null,
        ]);

        return (int) $sentencia->fetchColumn();
    }

    /** Actualiza los datos personales del cliente. No toca contraseña, altura ni peso. */
    public function actualizar(int $id, array $datos): void
    {
        $sql = 'UPDATE CLIENTE
                SET ci = :ci, nombres = :nombres, apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento, correo = :correo
                WHERE id_cliente = :id';

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

    /** Actualiza altura/peso, ya sea desde el propio perfil o al registrar una evaluación física. */
    public function actualizarMedidas(int $id, ?float $altura, ?float $peso): void
    {
        $sentencia = $this->db->prepare('UPDATE CLIENTE SET altura = :altura, peso = :peso WHERE id_cliente = :id');
        $sentencia->execute(['altura' => $altura, 'peso' => $peso, 'id' => $id]);
    }

    /** Activa o desactiva una cuenta de cliente (CU02). Se usa en vez de DELETE por integridad referencial. */
    public function cambiarEstado(int $id, bool $estado): void
    {
        $sentencia = $this->db->prepare('UPDATE CLIENTE SET estado = :estado WHERE id_cliente = :id');
        $sentencia->execute(['estado' => $estado, 'id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM CLIENTE WHERE id_cliente = :id');
        $sentencia->execute(['id' => $id]);
        $cliente = $sentencia->fetch();

        return $cliente ?: null;
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM CLIENTE WHERE correo = :correo');
        $sentencia->execute(['correo' => $correo]);
        $cliente = $sentencia->fetch();

        return $cliente ?: null;
    }

    public function buscarPorCi(string $ci): ?array
    {
        $sentencia = $this->db->prepare('SELECT * FROM CLIENTE WHERE ci = :ci');
        $sentencia->execute(['ci' => $ci]);
        $cliente = $sentencia->fetch();

        return $cliente ?: null;
    }

    /** Lista todos los clientes, usada por el administrador (CU02) y por el instructor para elegir a quién evaluar o asignar una rutina. */
    public function listarTodos(): array
    {
        return $this->db->query('SELECT * FROM CLIENTE ORDER BY apellidos, nombres')->fetchAll();
    }

    /** Verifica credenciales de login. Recibe correo y contraseña en texto plano. */
    public function verificarCredenciales(string $correo, string $password): ?array
    {
        $cliente = $this->buscarPorCorreo($correo);

        if (!$cliente || !password_verify($password, $cliente['password_hash'])) {
            return null;
        }

        return $cliente;
    }

    /** true si ya existe un cliente con ese correo. $idExcluir permite ediciones de perfil. */
    public function existeCorreo(string $correo, ?int $idExcluir = null): bool
    {
        $cliente = $this->buscarPorCorreo($correo);

        return $cliente !== null && (int) $cliente['id_cliente'] !== $idExcluir;
    }

    /** true si ya existe un cliente con ese CI. $idExcluir permite ediciones de perfil. */
    public function existeCi(string $ci, ?int $idExcluir = null): bool
    {
        $cliente = $this->buscarPorCi($ci);

        return $cliente !== null && (int) $cliente['id_cliente'] !== $idExcluir;
    }
}

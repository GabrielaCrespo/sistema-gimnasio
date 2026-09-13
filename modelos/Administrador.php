<?php

/**
 * Modelo Administrador
 *
 * Acceso a los datos de la cuenta de administrador. A diferencia de Cliente e
 * Instructor, el administrador no vive en una tabla de la base de datos: es
 * una única cuenta fija definida por ADMIN_CORREO y ADMIN_PASSWORD_HASH en el
 * archivo .env, que se lee a través de la clase Config.
 *
 * Aun así se modela como un Modelo y no como código suelto dentro de
 * LoginController, para que los tres roles se verifiquen igual (el controlador
 * pregunta al modelo) y para que ningún acceso a datos -venga de la base de
 * datos o del .env- quede dentro de un controlador.
 */
class Administrador
{
    /** Correo de la cuenta fija de administrador, tal como está en .env. */
    private string $correo;

    /** Hash bcrypt de la contraseña del administrador, tal como está en .env. */
    private string $passwordHash;

    public function __construct()
    {
        $this->correo = Config::get('ADMIN_CORREO', '') ?? '';
        $this->passwordHash = Config::get('ADMIN_PASSWORD_HASH', '') ?? '';
    }

    /**
     * Verifica credenciales de login contra la cuenta fija de administrador.
     * Recibe correo y contraseña en texto plano y devuelve los datos de la
     * cuenta, o null si no coinciden (para que el controlador siga probando
     * con CLIENTE e INSTRUCTOR).
     */
    public function verificarCredenciales(string $correo, string $password): ?array
    {
        if ($this->correo === '' || $correo !== $this->correo) {
            return null;
        }

        if (!password_verify($password, $this->passwordHash)) {
            return null;
        }

        return [
            'correo' => $this->correo,
            'nombre' => 'Administrador',
        ];
    }

    /**
     * true si ese correo es el del administrador. Cliente e Instructor lo
     * consultan al validar altas y ediciones: si se permitiera registrar una
     * cuenta con este correo, esa cuenta quedaría inaccesible al iniciar
     * sesión, porque el administrador siempre se comprueba primero.
     */
    public function esCorreoDelAdministrador(string $correo): bool
    {
        return $this->correo !== '' && $correo === $this->correo;
    }
}

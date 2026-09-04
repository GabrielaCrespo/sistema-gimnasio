<!-- Vista de inicio de sesión (auth/login.php) -->
<!-- Muestra el formulario para que los usuarios ingresen sus credenciales -->

<section class="auth-panel panel">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

<!-- Formulario de login: envía correo y contraseña al controlador AuthController::autenticar() -->    
    <form class="formulario" method="post" action="<?= url('auth', 'autenticar') ?>">
        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" required autofocus>
        </div>
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="boton">Ingresar</button>
    </form>

<!-- Enlace al controlador AuthController, acción register, que mostrará el formulario de registro público (solo para clientes). -->
    <p class="auth-pie">¿No tienes cuenta? <a href="<?= url('auth', 'register') ?>">Regístrate como cliente</a></p>
</section>

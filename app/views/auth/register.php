<?php $datos = $datos ?? []; ?>
<section class="auth-panel panel">
    <h1>Crear cuenta de cliente</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= url('auth', 'crearCuenta') ?>">
        <div class="fila-formulario">
            <div class="campo">
                <label for="nombres">Nombres</label>
                <input type="text" id="nombres" name="nombres" value="<?= e($datos['nombres'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="apellidos">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" value="<?= e($datos['apellidos'] ?? '') ?>" required>
            </div>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="ci">Cédula de identidad</label>
                <input type="text" id="ci" name="ci" value="<?= e($datos['ci'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($datos['fecha_nacimiento'] ?? '') ?>" required>
            </div>
        </div>
        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="<?= e($datos['correo'] ?? '') ?>" required>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="campo">
                <label for="password_confirmacion">Confirmar contraseña</label>
                <input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="6">
            </div>
        </div>
        <button type="submit" class="boton">Crear cuenta</button>
    </form>

    <p class="auth-pie">¿Ya tienes cuenta? <a href="<?= url('auth', 'login') ?>">Inicia sesión</a></p>
</section>

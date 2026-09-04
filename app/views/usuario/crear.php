<!-- Vista para crear una nueva cuenta de usuario -->
<?php $datos = $datos ?? []; ?>
<section class="panel">
    <h1>Crear cuenta</h1>

<!-- Muestra un mensaje de error si existe (enviado desde el controlador) -->
    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

 <!-- Formulario que envía los datos al controlador (acción 'guardar') -->
    <form class="formulario" method="post" action="<?= url('usuario', 'guardar') ?>">
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
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="campo">
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required onchange="document.getElementById('campo-especialidad').hidden = this.value !== 'instructor'">
                <option value="">Selecciona un rol</option>
                <option value="administrador" <?= ($datos['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="instructor" <?= ($datos['rol'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                <option value="cliente" <?= ($datos['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            </select>
        </div>

    <!-- Campo de especialidad que solo se muestra si el rol seleccionado es 'instructor' -->    
        <div class="campo" id="campo-especialidad" <?= ($datos['rol'] ?? '') === 'instructor' ? '' : 'hidden' ?>>
            <label for="especialidad">Especialidad</label>
            <input type="text" id="especialidad" name="especialidad" value="<?= e($datos['especialidad'] ?? '') ?>">
        </div>
        <button type="submit" class="boton">Crear cuenta</button>
    </form>
</section>

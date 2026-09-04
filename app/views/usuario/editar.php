<!-- Vista para editar un usuario existente -->
<section class="panel">
    <h1>Editar usuario</h1>

<!-- Muestra un mensaje de error si existe (enviado desde el controlador) -->    
    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

<!-- Formulario que envía los datos al controlador (acción 'actualizar') -->
    <form class="formulario" method="post" action="<?= url('usuario', 'actualizar') ?>">
        <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">

        <div class="campo">
            <label>Rol</label>
            <input type="text" value="<?= e(ucfirst($usuario['rol'])) ?>" disabled>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombres">Nombres</label>
                <input type="text" id="nombres" name="nombres" value="<?= e($usuario['nombres']) ?>" required>
            </div>
            <div class="campo">
                <label for="apellidos">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" value="<?= e($usuario['apellidos']) ?>" required>
            </div>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="ci">Cédula de identidad</label>
                <input type="text" id="ci" name="ci" value="<?= e($usuario['ci']) ?>" required>
            </div>
            <div class="campo">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>" required>
            </div>
        </div>
        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="<?= e($usuario['correo']) ?>" required>
        </div>

<!-- Muestra el campo de especialidad solo si el usuario es un instructor -->
        <?php if ($usuario['rol'] === 'instructor'): ?>
            <div class="campo">
                <label for="especialidad">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad" value="<?= e($especialidad) ?>" required>
            </div>
        <?php endif; ?>

        <button type="submit" class="boton">Guardar cambios</button>
    </form>
</section>

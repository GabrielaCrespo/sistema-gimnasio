<!-- Vista para mostrar el perfil del usuario -->
<section class="panel">
    <h1>Mi perfil</h1>

<!-- Muestra un mensaje de error o éxito si existe (enviado desde el controlador) -->
    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($exito)): ?>
        <div class="alerta alerta-exito"><?= e($exito) ?></div>
    <?php endif; ?>

<!-- Formulario que envía los datos al controlador (acción 'actualizarPerfil') -->
    <form class="formulario" method="post" action="<?= url('usuario', 'actualizarPerfil') ?>">
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

    <!-- Muestra campos adicionales según el rol del usuario -->
        <?php if ($usuario['rol'] === 'cliente'): ?>
            <div class="fila-formulario">
                <div class="campo">
                    <label for="altura">Altura (m)</label>
                    <input type="number" step="0.01" id="altura" name="altura" value="<?= e($clienteInfo['altura'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="peso">Peso (kg)</label>
                    <input type="number" step="0.01" id="peso" name="peso" value="<?= e($clienteInfo['peso'] ?? '') ?>">
                </div>
            </div>
            
        <?php elseif ($usuario['rol'] === 'instructor'): ?>
            <div class="campo">
                <label for="especialidad">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad" value="<?= e($instructorInfo['especialidad'] ?? '') ?>" required>
            </div>
        <?php endif; ?>

        <button type="submit" class="boton">Guardar cambios</button>
    </form>
</section>

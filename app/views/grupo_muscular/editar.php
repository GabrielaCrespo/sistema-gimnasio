<section class="panel">
    <h1>Editar grupo muscular</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= url('grupoMuscular', 'actualizar') ?>">
        <input type="hidden" name="id" value="<?= e($grupo['id_grupo_muscular']) ?>">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($grupo['nombre']) ?>" required>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4"><?= e($grupo['descripcion'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="boton">Guardar cambios</button>
    </form>
</section>

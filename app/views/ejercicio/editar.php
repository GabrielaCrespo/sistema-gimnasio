<section class="panel">
    <h1>Editar ejercicio</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'actualizar') ?>">
        <input type="hidden" name="id" value="<?= e($ejercicio['id_ejercicio']) ?>">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($ejercicio['nombre']) ?>" required>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= e($ejercicio['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="beneficio">Beneficio</label>
            <textarea id="beneficio" name="beneficio" rows="3"><?= e($ejercicio['beneficio'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="indicaciones">Indicaciones</label>
            <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($ejercicio['indicaciones'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="url_video">URL de video (opcional)</label>
            <input type="url" id="url_video" name="url_video" value="<?= e($ejercicio['url_video'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Grupos musculares</label>
            <?php foreach ($gruposDisponibles as $grupo): ?>
                <label>
                    <input type="checkbox" name="grupos[]" value="<?= e($grupo['id_grupo_muscular']) ?>"
                        <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados, true) ? 'checked' : '' ?>>
                    <?= e($grupo['nombre']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="boton">Guardar cambios</button>
    </form>
</section>

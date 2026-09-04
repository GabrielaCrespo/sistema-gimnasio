<?php $datos = $datos ?? []; ?>
<section class="panel">
    <h1>Nuevo ejercicio</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'guardar') ?>">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= e($datos['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="beneficio">Beneficio</label>
            <textarea id="beneficio" name="beneficio" rows="3"><?= e($datos['beneficio'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="indicaciones">Indicaciones</label>
            <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($datos['indicaciones'] ?? '') ?></textarea>
        </div>
        <div class="campo">
            <label for="url_video">URL de video (opcional)</label>
            <input type="url" id="url_video" name="url_video" value="<?= e($datos['url_video'] ?? '') ?>">
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
        <button type="submit" class="boton">Guardar</button>
    </form>
</section>

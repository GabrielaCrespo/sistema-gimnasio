<?php $datos = $datos ?? []; ?>
<section class="panel">
    <h1>Nuevo grupo muscular</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= url('grupoMuscular', 'guardar') ?>">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4"><?= e($datos['descripcion'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="boton">Guardar</button>
    </form>
</section>

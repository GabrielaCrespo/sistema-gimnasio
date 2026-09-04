<section class="panel">
    <h1>Editar rutina</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= url('rutina', 'actualizar') ?>">
        <input type="hidden" name="id" value="<?= e($rutina['id_rutina']) ?>">
        <div class="campo">
            <label for="nombre">Nombre de la rutina</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($rutina['nombre']) ?>" required>
        </div>
        <div class="campo">
            <label for="tipo">Tipo</label>
            <input type="text" id="tipo" name="tipo" value="<?= e($rutina['tipo'] ?? '') ?>">
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="fecha_inicio">Fecha de inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($rutina['fecha_inicio']) ?>" required>
            </div>
            <div class="campo">
                <label for="fecha_fin">Fecha de fin (opcional)</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?= e($rutina['fecha_fin'] ?? '') ?>">
            </div>
        </div>
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <?php foreach (['activa' => 'Activa', 'completada' => 'Completada', 'cancelada' => 'Cancelada'] as $valor => $etiqueta): ?>
                    <option value="<?= $valor ?>" <?= $rutina['estado'] === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="boton">Guardar cambios</button>
    </form>
</section>

<?php $datos = $datos ?? []; ?>
<section class="panel">
    <h1>Registrar evaluación física</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario formulario-ancho" method="post" action="<?= url('evaluacionFisica', 'guardar') ?>">
        <div class="campo">
            <label for="id_cliente">Cliente</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= e($cliente['id_usuario']) ?>" <?= (int) ($datos['id_cliente'] ?? 0) === (int) $cliente['id_usuario'] ? 'selected' : '' ?>>
                        <?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="peso">Peso (kg)</label>
                <input type="number" step="0.01" id="peso" name="peso" value="<?= e($datos['peso'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="altura">Altura (m)</label>
                <input type="number" step="0.01" id="altura" name="altura" value="<?= e($datos['altura'] ?? '') ?>" required>
            </div>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="porcentaje_grasa">% de grasa corporal</label>
                <input type="number" step="0.01" id="porcentaje_grasa" name="porcentaje_grasa" value="<?= e($datos['porcentaje_grasa'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="masa_muscular">Masa muscular (kg)</label>
                <input type="number" step="0.01" id="masa_muscular" name="masa_muscular" value="<?= e($datos['masa_muscular'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="flexibilidad">Flexibilidad (cm)</label>
                <input type="number" step="0.01" id="flexibilidad" name="flexibilidad" value="<?= e($datos['flexibilidad'] ?? '') ?>">
            </div>
        </div>
        <div class="campo">
            <label for="objetivo">Objetivo</label>
            <input type="text" id="objetivo" name="objetivo" value="<?= e($datos['objetivo'] ?? '') ?>">
        </div>
        <div class="campo">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3"><?= e($datos['observaciones'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="boton">Guardar evaluación</button>
    </form>
</section>

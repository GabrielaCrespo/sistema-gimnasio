<?php $datos = $datos ?? []; ?>
<section class="panel">
    <h1>Nueva rutina</h1>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="formulario" method="post" action="<?= url('rutina', 'guardar') ?>">
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
        <div class="campo">
            <label for="nombre">Nombre de la rutina</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
        </div>
        <div class="campo">
            <label for="tipo">Tipo</label>
            <input type="text" id="tipo" name="tipo" value="<?= e($datos['tipo'] ?? '') ?>" placeholder="Ej. Fuerza, Cardio, Hipertrofia">
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="fecha_inicio">Fecha de inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($datos['fecha_inicio'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="fecha_fin">Fecha de fin (opcional)</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?= e($datos['fecha_fin'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="boton">Crear y continuar</button>
    </form>
</section>

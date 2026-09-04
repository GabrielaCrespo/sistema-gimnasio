<section class="panel">
    <div class="panel-cabecera">
        <h1><?= e($ejercicio['nombre']) ?></h1>
        <a class="boton boton-secundario" href="<?= url('ejercicio', 'index') ?>">Volver</a>
    </div>

    <p><strong>Descripción:</strong> <?= e($ejercicio['descripcion'] ?? 'Sin descripción') ?></p>
    <p><strong>Beneficio:</strong> <?= e($ejercicio['beneficio'] ?? 'No especificado') ?></p>
    <p><strong>Indicaciones:</strong> <?= e($ejercicio['indicaciones'] ?? 'No especificadas') ?></p>
    <?php if (!empty($ejercicio['url_video'])): ?>
        <p><strong>Video:</strong> <a href="<?= e($ejercicio['url_video']) ?>" target="_blank" rel="noopener">Ver video</a></p>
    <?php endif; ?>

    <p><strong>Grupos musculares:</strong></p>
    <?php if (empty($grupos)): ?>
        <p class="texto-suave">No tiene grupos musculares asociados.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($grupos as $grupo): ?>
                <li><?= e($grupo['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-cabecera">
        <h1>Grupos musculares</h1>
        <a class="boton" href="<?= url('grupoMuscular', 'crear') ?>">Nuevo grupo muscular</a>
    </div>

    <div class="tabla-envoltura">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $grupo): ?>
                    <tr>
                        <td><?= e($grupo['nombre']) ?></td>
                        <td><?= e($grupo['descripcion'] ?? '') ?></td>
                        <td class="acciones">
                            <a class="boton boton-pequeno boton-secundario" href="<?= url('grupoMuscular', 'editar', ['id' => $grupo['id_grupo_muscular']]) ?>">Editar</a>
                            <form method="post" action="<?= url('grupoMuscular', 'eliminar') ?>" onsubmit="return confirm('¿Eliminar este grupo muscular? También se quitará de los ejercicios asociados.');">
                                <input type="hidden" name="id" value="<?= e($grupo['id_grupo_muscular']) ?>">
                                <button type="submit" class="boton boton-pequeno boton-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

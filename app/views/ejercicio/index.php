<section class="panel">
    <div class="panel-cabecera">
        <h1>Ejercicios</h1>
        <a class="boton" href="<?= url('ejercicio', 'crear') ?>">Nuevo ejercicio</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

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
                <?php foreach ($ejercicios as $ejercicio): ?>
                    <tr>
                        <td><?= e($ejercicio['nombre']) ?></td>
                        <td><?= e($ejercicio['descripcion'] ?? '') ?></td>
                        <td class="acciones">
                            <a class="boton boton-pequeno boton-secundario" href="<?= url('ejercicio', 'ver', ['id' => $ejercicio['id_ejercicio']]) ?>">Ver</a>
                            <a class="boton boton-pequeno boton-secundario" href="<?= url('ejercicio', 'editar', ['id' => $ejercicio['id_ejercicio']]) ?>">Editar</a>
                            <form method="post" action="<?= url('ejercicio', 'eliminar') ?>" onsubmit="return confirm('¿Eliminar este ejercicio?');">
                                <input type="hidden" name="id" value="<?= e($ejercicio['id_ejercicio']) ?>">
                                <button type="submit" class="boton boton-pequeno boton-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

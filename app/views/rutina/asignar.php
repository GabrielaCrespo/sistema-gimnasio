<section class="panel">
    <div class="panel-cabecera">
        <h1>Ejercicios de "<?= e($rutina['nombre']) ?>"</h1>
        <a class="boton boton-secundario" href="<?= url('rutina', 'ver', ['id' => $rutina['id_rutina']]) ?>">Ver rutina</a>
    </div>
    <p class="texto-suave">Cliente: <?= e($rutina['cliente_nombres'] . ' ' . $rutina['cliente_apellidos']) ?></p>

    <?php if (!empty($error)): ?>
        <div class="alerta alerta-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($detalle)): ?>
        <p class="texto-suave espacio-superior">Todavía no se agregaron ejercicios.</p>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Ejercicio</th>
                        <th>Series</th>
                        <th>Repeticiones</th>
                        <th>Descanso (s)</th>
                        <th>Orden</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle as $fila): ?>
                        <tr>
                            <td><?= e($fila['dia_semana']) ?></td>
                            <td><?= e($fila['ejercicio_nombre']) ?></td>
                            <td><?= e($fila['series']) ?></td>
                            <td><?= e($fila['repeticiones']) ?></td>
                            <td><?= e($fila['tiempo_descanso']) ?></td>
                            <td><?= e($fila['orden']) ?></td>
                            <td>
                                <form method="post" action="<?= url('rutina', 'quitarEjercicio') ?>" onsubmit="return confirm('¿Quitar este ejercicio de la rutina?');">
                                    <input type="hidden" name="id_rutina" value="<?= e($rutina['id_rutina']) ?>">
                                    <input type="hidden" name="id_detalle" value="<?= e($fila['id_detalle']) ?>">
                                    <button type="submit" class="boton boton-pequeno boton-peligro">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="espacio-superior">Agregar ejercicio</h2>
    <form class="formulario" method="post" action="<?= url('rutina', 'agregarEjercicio') ?>">
        <input type="hidden" name="id_rutina" value="<?= e($rutina['id_rutina']) ?>">
        <div class="campo">
            <label for="id_ejercicio">Ejercicio</label>
            <select id="id_ejercicio" name="id_ejercicio" required>
                <option value="">Selecciona un ejercicio</option>
                <?php foreach ($ejercicios as $ejercicio): ?>
                    <option value="<?= e($ejercicio['id_ejercicio']) ?>"><?= e($ejercicio['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="dia_semana">Día de la semana</label>
            <select id="dia_semana" name="dia_semana" required>
                <?php foreach ($dias as $dia): ?>
                    <option value="<?= e($dia) ?>"><?= e($dia) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fila-formulario">
            <div class="campo">
                <label for="series">Series</label>
                <input type="number" id="series" name="series" min="1" required>
            </div>
            <div class="campo">
                <label for="repeticiones">Repeticiones</label>
                <input type="number" id="repeticiones" name="repeticiones" min="1" required>
            </div>
            <div class="campo">
                <label for="tiempo_descanso">Descanso (segundos)</label>
                <input type="number" id="tiempo_descanso" name="tiempo_descanso" min="0" required>
            </div>
            <div class="campo">
                <label for="orden">Orden</label>
                <input type="number" id="orden" name="orden" min="0" required>
            </div>
        </div>
        <button type="submit" class="boton">Agregar</button>
    </form>
</section>

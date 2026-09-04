<section class="panel">
    <div class="panel-cabecera">
        <h1><?= e($rutina['nombre']) ?></h1>
        <?php if ((int) $rutina['id_instructor'] === (int) $_SESSION['user']['id']): ?>
            <a class="boton boton-secundario" href="<?= url('rutina', 'asignar', ['id' => $rutina['id_rutina']]) ?>">Gestionar ejercicios</a>
        <?php endif; ?>
    </div>

    <p><strong>Cliente:</strong> <?= e($rutina['cliente_nombres'] . ' ' . $rutina['cliente_apellidos']) ?></p>
    <p><strong>Instructor:</strong> <?= e($rutina['instructor_nombres'] . ' ' . $rutina['instructor_apellidos']) ?></p>
    <p><strong>Tipo:</strong> <?= e($rutina['tipo'] ?? '-') ?></p>
    <p><strong>Periodo:</strong> <?= e($rutina['fecha_inicio']) ?> &mdash; <?= e($rutina['fecha_fin'] ?? 'sin definir') ?></p>
    <p><strong>Estado:</strong> <span class="insignia insignia-rol"><?= e(ucfirst($rutina['estado'])) ?></span></p>

    <?php if (empty($detallePorDia)): ?>
        <p class="texto-suave espacio-superior">Esta rutina todavía no tiene ejercicios asignados.</p>
    <?php else: ?>
        <?php foreach ($detallePorDia as $dia => $ejercicios): ?>
            <h2 class="espacio-superior"><?= e($dia) ?></h2>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Ejercicio</th>
                            <th>Series</th>
                            <th>Repeticiones</th>
                            <th>Descanso (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ejercicios as $fila): ?>
                            <tr>
                                <td><?= e($fila['ejercicio_nombre']) ?></td>
                                <td><?= e($fila['series']) ?></td>
                                <td><?= e($fila['repeticiones']) ?></td>
                                <td><?= e($fila['tiempo_descanso']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

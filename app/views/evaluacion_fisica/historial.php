<section class="panel">
    <?php if ($clientes === null): ?>
        <h1>Mi historial de evaluaciones</h1>
    <?php elseif ($clienteSeleccionado === null): ?>
        <h1>Historial de evaluaciones</h1>
        <p class="texto-suave">Selecciona un cliente para ver su historial.</p>
        <div class="tarjetas">
            <?php foreach ($clientes as $cliente): ?>
                <a class="tarjeta" href="<?= url('evaluacionFisica', 'historial', ['id' => $cliente['id_usuario']]) ?>">
                    <?= e($cliente['nombres'] . ' ' . $cliente['apellidos']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel-cabecera">
            <h1>Historial de <?= e($clienteSeleccionado['nombres'] . ' ' . $clienteSeleccionado['apellidos']) ?></h1>
            <a class="boton boton-secundario" href="<?= url('evaluacionFisica', 'historial') ?>">Elegir otro cliente</a>
        </div>
    <?php endif; ?>

    <?php if ($clientes === null || $clienteSeleccionado !== null): ?>
        <?php if (empty($evaluaciones)): ?>
            <p class="texto-suave espacio-superior">Todavía no hay evaluaciones registradas.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Peso</th>
                            <th>Altura</th>
                            <th>% Grasa</th>
                            <th>Masa muscular</th>
                            <th>Flexibilidad</th>
                            <th>Objetivo</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluaciones as $evaluacion): ?>
                            <tr>
                                <td><?= e($evaluacion['fecha']) ?></td>
                                <td><?= e($evaluacion['peso']) ?> kg</td>
                                <td><?= e($evaluacion['altura']) ?> m</td>
                                <td><?= e($evaluacion['porcentaje_grasa'] ?? '-') ?></td>
                                <td><?= e($evaluacion['masa_muscular'] ?? '-') ?></td>
                                <td><?= e($evaluacion['flexibilidad'] ?? '-') ?></td>
                                <td><?= e($evaluacion['objetivo'] ?? '-') ?></td>
                                <td><?= e($evaluacion['instructor_nombres'] . ' ' . $evaluacion['instructor_apellidos']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

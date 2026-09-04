<section class="panel">
    <div class="panel-cabecera">
        <h1><?= $rol === 'instructor' ? 'Rutinas creadas' : 'Mis rutinas asignadas' ?></h1>
        <?php if ($rol === 'instructor'): ?>
            <a class="boton" href="<?= url('rutina', 'crear') ?>">Nueva rutina</a>
        <?php endif; ?>
    </div>

    <?php if (empty($rutinas)): ?>
        <p class="texto-suave">No hay rutinas registradas todavía.</p>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th><?= $rol === 'instructor' ? 'Cliente' : 'Instructor' ?></th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rutinas as $rutina): ?>
                        <tr>
                            <td><?= e($rutina['nombre']) ?></td>
                            <td><?= e($rutina['tipo'] ?? '-') ?></td>
                            <td>
                                <?= $rol === 'instructor'
                                    ? e($rutina['cliente_nombres'] . ' ' . $rutina['cliente_apellidos'])
                                    : e($rutina['instructor_nombres'] . ' ' . $rutina['instructor_apellidos']) ?>
                            </td>
                            <td><?= e($rutina['fecha_inicio']) ?></td>
                            <td><?= e($rutina['fecha_fin'] ?? '-') ?></td>
                            <td><span class="insignia insignia-rol"><?= e(ucfirst($rutina['estado'])) ?></span></td>
                            <td class="acciones">
                                <a class="boton boton-pequeno boton-secundario" href="<?= url('rutina', 'ver', ['id' => $rutina['id_rutina']]) ?>">Ver</a>
                                <?php if ($rol === 'instructor'): ?>
                                    <a class="boton boton-pequeno boton-secundario" href="<?= url('rutina', 'editar', ['id' => $rutina['id_rutina']]) ?>">Editar</a>
                                    <a class="boton boton-pequeno boton-secundario" href="<?= url('rutina', 'asignar', ['id' => $rutina['id_rutina']]) ?>">Ejercicios</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

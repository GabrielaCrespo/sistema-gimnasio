<?php
/**
 * Vista de rutinas (RutinaController). $accion decide el contenido:
 * listar, ver, crear, editar o asignar (agregar/quitar ejercicios).
 */
$usuarioSesion = $_SESSION['user'] ?? null;
$datos = $datos ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Gimnasio</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<nav class="nav">
    <span class="nav-marca">Gimnasio</span>
    <div class="nav-enlaces">
        <a href="<?= url('home') ?>">Inicio</a>
        <?php if ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
            <a href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
            <a href="<?= url('rutina') ?>">Rutinas</a>
        <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
            <a href="<?= url('rutina') ?>">Mis rutinas</a>
            <a href="<?= url('evaluacionFisica', 'historial') ?>">Mis evaluaciones</a>
        <?php endif; ?>
    </div>
    <div class="nav-enlaces">
        <a href="<?= url('usuario', 'perfil') ?>">Mi perfil</a>
        <a href="<?= url('login', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>
<main class="contenedor">

<?php if ($accion === 'listar'): ?>

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

<?php elseif ($accion === 'ver'): ?>

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

<?php elseif ($accion === 'crear'): ?>

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

<?php elseif ($accion === 'editar'): ?>

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

<?php elseif ($accion === 'asignar'): ?>

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

<?php endif; ?>

</main>
<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio</p>
</footer>
</body>
</html>

<?php
/**
 * Vista de evaluación física (EvaluacionFisicaController). $accion decide
 * el contenido: registrar (solo instructor) o historial (instructor o cliente).
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

<?php if ($accion === 'registrar'): ?>

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

<?php elseif ($accion === 'historial'): ?>

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

<?php endif; ?>

</main>
<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio</p>
</footer>
</body>
</html>

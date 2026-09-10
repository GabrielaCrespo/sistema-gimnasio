<?php
/**
 * Vista de ejercicios (EjercicioController). $accion decide el contenido:
 * listar, ver, crear o editar.
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
        <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
            <a href="<?= url('usuario') ?>">Usuarios</a>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
        <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
            <a href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
            <a href="<?= url('rutina') ?>">Rutinas</a>
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

<?php elseif ($accion === 'ver'): ?>

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

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <h1>Nuevo ejercicio</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'guardar') ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= e($datos['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3"><?= e($datos['beneficio'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones</label>
                <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($datos['indicaciones'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="url_video">URL de video (opcional)</label>
                <input type="url" id="url_video" name="url_video" value="<?= e($datos['url_video'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Grupos musculares</label>
                <?php foreach ($gruposDisponibles as $grupo): ?>
                    <label>
                        <input type="checkbox" name="grupos[]" value="<?= e($grupo['id_grupo_muscular']) ?>"
                            <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados, true) ? 'checked' : '' ?>>
                        <?= e($grupo['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="boton">Guardar</button>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <h1>Editar ejercicio</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'actualizar') ?>">
            <input type="hidden" name="id" value="<?= e($ejercicio['id_ejercicio']) ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($ejercicio['nombre']) ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= e($ejercicio['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3"><?= e($ejercicio['beneficio'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones</label>
                <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($ejercicio['indicaciones'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="url_video">URL de video (opcional)</label>
                <input type="url" id="url_video" name="url_video" value="<?= e($ejercicio['url_video'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Grupos musculares</label>
                <?php foreach ($gruposDisponibles as $grupo): ?>
                    <label>
                        <input type="checkbox" name="grupos[]" value="<?= e($grupo['id_grupo_muscular']) ?>"
                            <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados, true) ? 'checked' : '' ?>>
                        <?= e($grupo['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="boton">Guardar cambios</button>
        </form>
    </section>

<?php endif; ?>

</main>
<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio</p>
</footer>
</body>
</html>

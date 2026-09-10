<?php
/**
 * Vista de grupos musculares (GrupoMuscularController). $accion decide el
 * contenido: listar, crear o editar.
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

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <h1>Nuevo grupo muscular</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('grupoMuscular', 'guardar') ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?= e($datos['descripcion'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="boton">Guardar</button>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <h1>Editar grupo muscular</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('grupoMuscular', 'actualizar') ?>">
            <input type="hidden" name="id" value="<?= e($grupo['id_grupo_muscular']) ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($grupo['nombre']) ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?= e($grupo['descripcion'] ?? '') ?></textarea>
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

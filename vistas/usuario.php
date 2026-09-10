<?php
/**
 * Vista de usuarios (UsuarioController). $accion decide el contenido:
 * listar, crear, editar o perfil.
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
            <h1>Usuarios</h1>
            <a class="boton" href="<?= url('usuario', 'crear') ?>">Crear cuenta</a>
        </div>

        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>CI</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= e($usuario['ci']) ?></td>
                            <td><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></td>
                            <td><?= e($usuario['correo']) ?></td>
                            <td><span class="insignia insignia-rol"><?= e(ucfirst($usuario['rol'])) ?></span></td>
                            <td>
                                <?php if ($usuario['estado']): ?>
                                    <span class="insignia insignia-activo">Activo</span>
                                <?php else: ?>
                                    <span class="insignia insignia-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones">
                                <a class="boton boton-pequeno boton-secundario" href="<?= url('usuario', 'editar', ['id' => $usuario['id_usuario']]) ?>">Editar</a>
                                <?php if ((int) $usuario['id_usuario'] !== (int) $_SESSION['user']['id']): ?>
                                    <form method="post" action="<?= url('usuario', 'cambiarEstado') ?>">
                                        <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">
                                        <input type="hidden" name="estado" value="<?= $usuario['estado'] ? '0' : '1' ?>">
                                        <button type="submit" class="boton boton-pequeno <?= $usuario['estado'] ? 'boton-peligro' : '' ?>">
                                            <?= $usuario['estado'] ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <h1>Crear cuenta</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'guardar') ?>">
            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($datos['nombres'] ?? '') ?>" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($datos['apellidos'] ?? '') ?>" required>
                </div>
            </div>
            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($datos['ci'] ?? '') ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($datos['fecha_nacimiento'] ?? '') ?>" required>
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($datos['correo'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="campo">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" required onchange="document.getElementById('campo-especialidad').hidden = this.value !== 'instructor'">
                    <option value="">Selecciona un rol</option>
                    <option value="administrador" <?= ($datos['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="instructor" <?= ($datos['rol'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                    <option value="cliente" <?= ($datos['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                </select>
            </div>

            <div class="campo" id="campo-especialidad" <?= ($datos['rol'] ?? '') === 'instructor' ? '' : 'hidden' ?>>
                <label for="especialidad">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad" value="<?= e($datos['especialidad'] ?? '') ?>">
            </div>
            <button type="submit" class="boton">Crear cuenta</button>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <h1>Editar usuario</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'actualizar') ?>">
            <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">

            <div class="campo">
                <label>Rol</label>
                <input type="text" value="<?= e(ucfirst($usuario['rol'])) ?>" disabled>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($usuario['nombres']) ?>" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($usuario['apellidos']) ?>" required>
                </div>
            </div>
            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($usuario['ci']) ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>" required>
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($usuario['correo']) ?>" required>
            </div>

            <?php if ($usuario['rol'] === 'instructor'): ?>
                <div class="campo">
                    <label for="especialidad">Especialidad</label>
                    <input type="text" id="especialidad" name="especialidad" value="<?= e($especialidad) ?>" required>
                </div>
            <?php endif; ?>

            <button type="submit" class="boton">Guardar cambios</button>
        </form>
    </section>

<?php elseif ($accion === 'perfil'): ?>

    <section class="panel">
        <h1>Mi perfil</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($exito)): ?>
            <div class="alerta alerta-exito"><?= e($exito) ?></div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'actualizarPerfil') ?>">
            <div class="campo">
                <label>Rol</label>
                <input type="text" value="<?= e(ucfirst($usuario['rol'])) ?>" disabled>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($usuario['nombres']) ?>" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($usuario['apellidos']) ?>" required>
                </div>
            </div>
            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($usuario['ci']) ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>" required>
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($usuario['correo']) ?>" required>
            </div>

            <?php if ($usuario['rol'] === 'cliente'): ?>
                <div class="fila-formulario">
                    <div class="campo">
                        <label for="altura">Altura (m)</label>
                        <input type="number" step="0.01" id="altura" name="altura" value="<?= e($clienteInfo['altura'] ?? '') ?>">
                    </div>
                    <div class="campo">
                        <label for="peso">Peso (kg)</label>
                        <input type="number" step="0.01" id="peso" name="peso" value="<?= e($clienteInfo['peso'] ?? '') ?>">
                    </div>
                </div>

            <?php elseif ($usuario['rol'] === 'instructor'): ?>
                <div class="campo">
                    <label for="especialidad">Especialidad</label>
                    <input type="text" id="especialidad" name="especialidad" value="<?= e($instructorInfo['especialidad'] ?? '') ?>" required>
                </div>
            <?php endif; ?>

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

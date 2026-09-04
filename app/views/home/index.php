<?php
/** @var array|null $usuarioSesion definida en el layout header, disponible aquí también */
$usuarioSesion = $_SESSION['user'] ?? null;
?>
<section class="landing">
    <?php if ($usuarioSesion): ?>
        <h1>Bienvenido/a, <?= e($usuarioSesion['nombre']) ?></h1>
        <p>Rol: <?= e(ucfirst($usuarioSesion['rol'])) ?></p>

        <div class="tarjetas">
            <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
                <a class="tarjeta" href="<?= url('usuario') ?>">Gestionar usuarios</a>
                <a class="tarjeta" href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
                <a class="tarjeta" href="<?= url('ejercicio') ?>">Ejercicios</a>
            <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
                <a class="tarjeta" href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
                <a class="tarjeta" href="<?= url('rutina') ?>">Gestionar rutinas</a>
                <a class="tarjeta" href="<?= url('ejercicio') ?>">Catálogo de ejercicios</a>
            <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
                <a class="tarjeta" href="<?= url('rutina') ?>">Mis rutinas</a>
                <a class="tarjeta" href="<?= url('evaluacionFisica', 'historial') ?>">Mi historial de evaluaciones</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h1>Sistema de Gestión de Gimnasio</h1>
        <p>Administra usuarios, ejercicios, evaluaciones físicas y rutinas de entrenamiento en un solo lugar.</p>
        <div class="landing-acciones">
            <a class="boton" href="<?= url('auth', 'login') ?>">Iniciar sesión</a>
            <a class="boton boton-secundario" href="<?= url('auth', 'register') ?>">Crear cuenta</a>
        </div>
    <?php endif; ?>
</section>

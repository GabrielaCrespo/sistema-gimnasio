<?php
/**
 * Vista de portada / panel de bienvenida (HomeController::index()).
 * Sin sesión activa muestra un landing público; con sesión activa muestra
 * un panel de bienvenida con accesos rápidos según el rol.
 */
$usuarioSesion = $_SESSION['user'] ?? null;
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
<?php if ($usuarioSesion): ?>
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
<?php endif; ?>
<main class="contenedor">

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
            <a class="boton" href="<?= url('login', 'login') ?>">Iniciar sesión</a>
            <a class="boton boton-secundario" href="<?= url('login', 'register') ?>">Crear cuenta</a>
        </div>
    <?php endif; ?>
</section>

</main>
<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio</p>
</footer>
</body>
</html>

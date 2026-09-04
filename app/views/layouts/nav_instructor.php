<?php
/**
 * Barra de navegación del rol Instructor.
 * CU03/CU04: catálogos. CU05: evaluaciones físicas. CU06: rutinas.
 */
?>
<nav class="nav">
    <span class="nav-marca">Gimnasio</span>
    <div class="nav-enlaces">
        <a href="<?= url('home') ?>">Inicio</a>
        <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
        <a href="<?= url('ejercicio') ?>">Ejercicios</a>
        <a href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
        <a href="<?= url('rutina') ?>">Rutinas</a>
    </div>
    <div class="nav-enlaces">
        <a href="<?= url('usuario', 'perfil') ?>">Mi perfil</a>
        <a href="<?= url('auth', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>

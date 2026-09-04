<?php
/**
 * Barra de navegación del rol Administrador.
 * CU02: gestión de cuentas de usuario. CU03/CU04: catálogos del sistema.
 */
?>
<nav class="nav">
    <span class="nav-marca">Gimnasio</span>
    <div class="nav-enlaces">
        <a href="<?= url('home') ?>">Inicio</a>
        <a href="<?= url('usuario') ?>">Usuarios</a>
        <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
        <a href="<?= url('ejercicio') ?>">Ejercicios</a>
    </div>
    <div class="nav-enlaces">
        <a href="<?= url('usuario', 'perfil') ?>">Mi perfil</a>
        <a href="<?= url('auth', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>

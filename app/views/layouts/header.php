<?php
/**
 * Layout: header
 *
 * Se incluye al inicio de cada render(). Define el <head> común, abre el
 * <body> y muestra la barra de navegación del rol de la sesión activa (o
 * ninguna barra si no hay sesión, ej. en login/registro/portada pública).
 */
$usuarioSesion = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Gimnasio</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php if ($usuarioSesion): ?>
    <?php
    // Cada rol ve opciones distintas del menú (CU02/CU03/CU04 para
    // administrador/instructor, CU06 para cliente, etc.), por eso hay un
    // archivo de navegación separado por rol en vez de un único menú con
    // condicionales repetidos.
    $rutaNav = __DIR__ . '/nav_' . $usuarioSesion['rol'] . '.php';
    if (is_file($rutaNav)) {
        require $rutaNav;
    }
    ?>
<?php endif; ?>
<main class="contenedor">

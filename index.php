<?php

/**
 * Front Controller
 *
 * Único punto de entrada de la aplicación. No existe un sistema de rutas:
 * simplemente se lee qué controlador y acción pidió el usuario por la URL
 * (?controller=..&action=..), se autocarga la clase correspondiente y se
 * invoca el método. Esto cumple la restricción del proyecto de no usar
 * ningún framework ni librería de enrutamiento: todo el "ruteo" es un
 * mapeo directo nombre-de-clase -> archivo, sin patrones ni tablas de rutas.
 */

declare(strict_types=1);

session_start();

define('BASE_PATH', __DIR__);

require BASE_PATH . '/config.php';

/**
 * Autoload manual: cuando el código usa una clase que todavía no está
 * definida, PHP llama a esta función con el nombre de la clase y buscamos
 * un archivo "<Clase>.php" en cada carpeta donde podría vivir. Reemplaza
 * a Composer/PSR-4 sin necesitar dependencias externas ni namespaces.
 */
spl_autoload_register(function (string $clase): void {
    $carpetas = [
        '/modelos/',
        '/controladores/',
    ];

    foreach ($carpetas as $carpeta) {
        $ruta = BASE_PATH . $carpeta . $clase . '.php';
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

/** Página mínima de error 404, usada solo cuando el controlador o la acción pedidos no existen. */
function paginaNoEncontrada(): void
{
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title><link rel="stylesheet" href="/style.css"></head><body><main class="contenedor"><section class="error-pagina"><h1>404 &mdash; Página no encontrada</h1><p>La página que buscas no existe.</p><a class="boton" href="/index.php">Volver al inicio</a></section></main></body></html>';
}

// El nombre del controlador llega en minúsculas/camelCase desde la URL
// (ej. "grupoMuscular"); ucfirst() arma el nombre real de la clase
// (ej. "GrupoMuscularController") que coincide con el archivo en controladores/.
$controlador = $_GET['controller'] ?? 'home';
$accion = $_GET['action'] ?? 'index';

$nombreClaseControlador = ucfirst($controlador) . 'Controller';

if (!class_exists($nombreClaseControlador)) {
    paginaNoEncontrada();
    exit;
}

$controladorObj = new $nombreClaseControlador();

if (!method_exists($controladorObj, $accion)) {
    paginaNoEncontrada();
    exit;
}

// A partir de aquí el control pasa al controlador: él decide qué
// modelo consultar y qué vista renderizar.
$controladorObj->$accion();

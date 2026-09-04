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

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/core/helpers.php';

/**
 * Autoload manual: cuando el código usa una clase que todavía no está
 * definida, PHP llama a esta función con el nombre de la clase y buscamos
 * un archivo "<Clase>.php" en cada carpeta donde podría vivir. Reemplaza
 * a Composer/PSR-4 sin necesitar dependencias externas ni namespaces.
 */
spl_autoload_register(function (string $clase): void {
    $carpetas = [
        '/config/',
        '/app/core/',
        '/app/core/middlewares/',
        '/app/models/',
        '/app/controllers/',
    ];

    foreach ($carpetas as $carpeta) {
        $ruta = BASE_PATH . $carpeta . $clase . '.php';
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

$request = new Request();

// El nombre del controlador llega en minúsculas/camelCase desde la URL
// (ej. "grupoMuscular"); ucfirst() arma el nombre real de la clase
// (ej. "GrupoMuscularController") que coincide con el archivo en app/controllers.
$nombreClaseControlador = ucfirst($request->controlador) . 'Controller';

if (!class_exists($nombreClaseControlador)) {
    http_response_code(404);
    require BASE_PATH . '/app/views/layouts/header.php';
    require BASE_PATH . '/app/views/errors/404.php';
    require BASE_PATH . '/app/views/layouts/footer.php';
    exit;
}

$controlador = new $nombreClaseControlador();

if (!method_exists($controlador, $request->accion)) {
    http_response_code(404);
    require BASE_PATH . '/app/views/layouts/header.php';
    require BASE_PATH . '/app/views/errors/404.php';
    require BASE_PATH . '/app/views/layouts/footer.php';
    exit;
}

// A partir de aquí el control pasa al controlador: él decide qué
// middlewares aplicar, qué modelo consultar y qué vista renderizar.
$accion = $request->accion;
$controlador->$accion($request);

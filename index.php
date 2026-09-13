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

// Cuando se ejecuta con el servidor embebido de PHP (`php -S`, solo para
// pruebas locales) se le indica que sirva directamente los archivos
// estáticos que ya existen en disco -como los videos e imágenes subidos en /archivos/-
// en vez de pasar por este front controller. En un hosting real
// (Apache/Nginx) esto no hace falta: el propio servidor ya sirve esos
// archivos sin tocar index.php.
if (PHP_SAPI === 'cli-server') {
    $rutaSolicitada = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($rutaSolicitada !== __DIR__ . '/index.php' && is_file($rutaSolicitada)) {
        return false;
    }
}

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

/** Carga la vista de error 404, usada solo cuando el controlador o la acción pedidos no existen. */
function paginaNoEncontrada(): void
{
    http_response_code(404);
    require BASE_PATH . '/vistas/404.php';
}

// El nombre del controlador llega en minúsculas/camelCase desde la URL
// (ej. "grupoMuscular"); ucfirst() arma el nombre real de la clase
// (ej. "GrupoMuscularController") que coincide con el archivo en controladores/.
// Sin parámetros en la URL, se muestra LoginController::index() (portada/panel de bienvenida).
$controlador = $_GET['controller'] ?? 'login';
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

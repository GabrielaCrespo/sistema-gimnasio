<?php

/**
 * Funciones auxiliares globales, disponibles en controladores y vistas.
 * No son clases porque no representan una entidad del dominio ni guardan
 * estado: son utilidades puras que evitan repetir código en cada archivo.
 */

/**
 * Construye una URL interna del tipo /index.php?controller=..&action=..
 * Todas las vistas y controladores generan enlaces con esta función en
 * vez de escribir la query string a mano.
 */
function url(string $controlador, string $accion = 'index', array $parametros = []): string
{
    $query = array_merge(['controller' => $controlador, 'action' => $accion], $parametros);
    return '/index.php?' . http_build_query($query);
}

/**
 * Escapa un valor para imprimirlo de forma segura dentro de HTML.
 * Se usa en todas las vistas al mostrar datos que vienen del usuario o
 * de la base de datos, para prevenir XSS.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

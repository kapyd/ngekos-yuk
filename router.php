<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$routes = [
    '/login' => __DIR__ . '/login.php',
    '/register' => __DIR__ . '/register.php',
];

if (isset($routes[$path])) {
    require $routes[$path];
    return true;
}

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';

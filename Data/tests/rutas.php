<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$source = file_get_contents(dirname(__DIR__) . '/src/app/routes.php');
preg_match_all("/([A-Za-z_][A-Za-z0-9_]*)::class\\s*\\.\\s*':([A-Za-z_][A-Za-z0-9_]*)'/", $source, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    $class = 'App\\controllers\\' . $match[1];
    if (!class_exists($class) || !method_exists($class, $match[2]) || !(new ReflectionMethod($class, $match[2]))->isPublic()) {
        throw new RuntimeException('Ruta sin controlador público: ' . $class . '::' . $match[2]);
    }
}
if (!$matches) throw new RuntimeException('No se reconocieron rutas.');
echo 'OK: ' . count($matches) . " referencias de rutas tienen controlador y método públicos.\n";

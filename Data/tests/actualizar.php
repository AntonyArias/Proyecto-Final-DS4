<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable('/var/www/html')->load();
$container = new DI\Container();
require dirname(__DIR__) . '/src/app/config.php';
require dirname(__DIR__) . '/src/app/eloquent.php';
$db = $container->get('eloquent');
foreach (glob(dirname(__DIR__) . '/db_dumb/actualizaciones/*.sql') as $file) {
    $db->getConnection()->unprepared(file_get_contents($file));
    echo 'Aplicada actualización idempotente: ' . basename($file) . "\n";
}

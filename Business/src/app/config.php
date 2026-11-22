<?php
$container->set('config', function() use ($container) {
    return (object) [
        'api_data' => rtrim($_ENV['API_DATA'], '/'),
        'key' => $_ENV['KEY'],
        'refresh_key' => $_ENV['JWT_REFRESH_KEY'],
        'timeout' => (float) ($_ENV['API_TIMEOUT'] ?? 10),
        'jwt_ttl' => (int) ($_ENV['JWT_TTL'] ?? 3600),
        'jwt_refresh_ttl' => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800),
        'internal_key' => $_ENV['INTERNAL_KEY'],
        'authorization' => $container->has('authorization') ? $container->get('authorization') : '',
    ];
});
$container->set(\App\Services\DataService::class, function($container) {
    return new \App\Services\DataService($container->get('config'));
});

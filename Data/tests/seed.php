<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable('/var/www/html')->load();
if (($_ENV['APP_ENV'] ?? '') !== 'local') { fwrite(STDERR, "Las semillas de demostración requieren APP_ENV=local.\n"); exit(1); }
$container = new DI\Container();
require dirname(__DIR__) . '/src/app/config.php';
require dirname(__DIR__) . '/src/app/eloquent.php';
$db = $container->get('eloquent');
$db->getConnection()->transaction(function() {
    foreach (['adminforestal' => [1, 'Administrador Demo'], 'tecnicocampo' => [2, 'Técnico Demo'], 'clientefinca' => [3, 'Cliente Demo']] as $username => [$role, $name]) {
        if (!App\Models\User::query()->where('username', $username)->exists()) App\Models\User::create([
            'username' => $username, 'nombre_completo' => $name, 'email' => $username . '@example.test', 'rol_id' => $role,
            'password' => password_hash('Forestal2026!', PASSWORD_DEFAULT), 'activo' => true]);
    }
    if (!App\Models\Cliente::query()->where('cedula', 'DEMO-001')->exists()) App\Models\Cliente::create([
        'usuario_id' => App\Models\User::query()->where('username', 'clientefinca')->value('id'), 'cedula' => 'DEMO-001',
        'nombre_completo' => 'Finca de demostración', 'email' => 'finca@example.test', 'telefono' => '2222-0000']);
});
echo "OK: tres usuarios y un cliente ficticios; no se sobrescribieron cuentas existentes.\n";

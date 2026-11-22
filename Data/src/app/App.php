<?php
use Slim\Factory\AppFactory;
use DI\Container;
use App\Services\AutorizacionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/../../vendor/autoload.php';
date_default_timezone_set('UTC');
$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/eloquent.php';
require_once __DIR__ . '/routes.php';
$app->add(function(Request $request, RequestHandler $handler) use ($container) {
    $key = $request->getHeaderLine('X-Internal-Key');
    if (strlen($_ENV['INTERNAL_KEY'] ?? '') < 32 || !hash_equals($_ENV['INTERNAL_KEY'], $key)) throw new RuntimeException('Servicio interno no autorizado.', 403);
    $container->get('eloquent');
    $path = $request->getUri()->getPath();
    $public = ($request->getMethod() === 'POST' && in_array($path, ['/api/auth/credentials', '/api/auth/session', '/api/auth/refresh', '/api/public/solicitud'], true))
        || ($request->getMethod() === 'GET' && in_array($path, ['/api/public/servicio', '/api/health'], true));
    if (!$public) $request = $request->withAttribute('usuario', AutorizacionService::autenticar($request));
    return $handler->handle($request);
});
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(function($request, Throwable $exception) use ($app) {
    $status = $exception instanceof \Slim\Exception\HttpException ? $exception->getCode() : (int) $exception->getCode();
    if ($exception instanceof \Illuminate\Database\QueryException) {
        $status = in_array((int) ($exception->errorInfo[1] ?? 0), [1062,1451,1452,3819], true) ? 409 : 503;
    }
    if ($status < 400 || $status > 599) $status = 500;
    $message = $status === 409 && $exception instanceof \Illuminate\Database\QueryException ? 'Registro duplicado o relacionado con otros datos.'
        : ($status >= 500 ? 'No fue posible completar la operación de datos.' : $exception->getMessage());
    $response = $app->getResponseFactory()->createResponse($status);
    $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});
$app->run();

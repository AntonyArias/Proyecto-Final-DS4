<?php
use Slim\Factory\AppFactory;
use DI\Container;
use JimTools\JwtAuth\Decoder\FirebaseDecoder;
use JimTools\JwtAuth\Exceptions\AuthorizationException;
use JimTools\JwtAuth\Middleware\JwtAuthentication;
use JimTools\JwtAuth\Options;
use JimTools\JwtAuth\Rules\RequestPathRule;
use JimTools\JwtAuth\Secret;
use Psr\Http\Message\ResponseInterface as Response;
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
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/config.php';
$app->add(new JwtAuthentication(new Options(isSecure: false),
    new FirebaseDecoder(new Secret($_ENV['KEY'], 'HS256')),
    [new RequestPathRule(['/'], ['/api/auth/login', '/api/auth/refresh', '/api/public', '/api/health', '/api/docs', '/api/openapi.json'])]));
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(function($request, Throwable $exception) use ($app) {
    $status = $exception instanceof AuthorizationException ? 401 : (int) $exception->getCode();
    if ($status < 400 || $status > 599) $status = 500;
    $message = $status >= 500 ? 'Error interno del servicio de negocio.' : ($status === 401 ? 'Token inválido o ausente.' : $exception->getMessage());
    $response = $app->getResponseFactory()->createResponse($status);
    $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});
$app->add(function(Request $request, RequestHandler $handler) use ($app, $container): Response {
    $container->set('authorization', $request->getHeaderLine('Authorization'));
    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:4200,https://localhost'));
    $isAllowed = $origin !== '' && in_array($origin, $allowedOrigins, true);
    if ($request->getMethod() === 'OPTIONS' && $isAllowed) $response = $app->getResponseFactory()->createResponse(204);
    else $response = $handler->handle($request);
    $response = $response->withHeader('X-Content-Type-Options', 'nosniff')->withHeader('Cache-Control', 'no-store');
    if (!$isAllowed) return $response;
    return $response->withHeader('Access-Control-Allow-Origin', $origin)->withHeader('Vary', 'Origin')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, Origin, X-Requested-With');
});
$app->run();

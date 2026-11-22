<?php
namespace App\controllers;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class Sistema {
    public function __construct(private ContainerInterface $container) {}
    public function health(Request $request, Response $response): Response {
        try {
            $upstream = $this->container->get(DataService::class)->get('health');
            $response->getBody()->write((string) $upstream->getBody());
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }
    public function openapi(Request $request, Response $response): Response {
        $response->getBody()->write(file_get_contents(__DIR__ . '/../../public/openapi.json'));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
    public function docs(Request $request, Response $response): Response {
        $response->getBody()->write('<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>API Ambiental — Swagger</title><link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css"><div id="swagger-ui"></div><script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script><script>SwaggerUIBundle({url:"/api/openapi.json",dom_id:"#swagger-ui"})</script></html>');
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}

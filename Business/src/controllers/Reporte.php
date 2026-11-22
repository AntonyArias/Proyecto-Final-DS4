<?php
namespace App\controllers;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class Reporte {
    public function __construct(private ContainerInterface $container) {}

    public function ingresos(Request $request, Response $response): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->get('reporte/ingresos', $request->getQueryParams());
            $response->getBody()->write((string) $upstream->getBody());
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }

    public function estados(Request $request, Response $response): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->get('reporte/proyectos-estado', $request->getQueryParams());
            $response->getBody()->write((string) $upstream->getBody());
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }

    public function visitas(Request $request, Response $response): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->get('reporte/visitas', $request->getQueryParams());
            $response->getBody()->write((string) $upstream->getBody());
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }

    public function mapa(Request $request, Response $response): Response {
        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->get('reporte/mapa', $request->getQueryParams());
            $response->getBody()->write((string) $upstream->getBody());
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }

}

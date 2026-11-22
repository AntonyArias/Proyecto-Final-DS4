<?php
namespace App\controllers;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Proyecto {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        try {

            $dataService = $this->container->get(DataService::class);
            $path = isset($args['id']) ? 'proyecto/' . rawurlencode($args['id']) : 'proyecto';
            return $this->forward($response, $dataService->get($path, $request->getQueryParams()));
        } catch (ConnectException | RequestException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        }
    }
    public function filter(Request $request, Response $response, array $args): Response {
        try {

            $dataService = $this->container->get(DataService::class);
            $path = 'proyecto/filter/' . rawurlencode($args['offset']) . '/' . rawurlencode($args['limit']);
            return $this->forward($response, $dataService->get($path, $request->getQueryParams()));
        } catch (ConnectException | RequestException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        }
    }
    public function update(Request $request, Response $response, array $args): Response {
        try {
            $data = $this->datos($request);
            $dataService = $this->container->get(DataService::class);
            return $this->forward($response, $dataService->put('proyecto/' . rawurlencode($args['id']), json_encode($data, JSON_THROW_ON_ERROR)));
        } catch (ConnectException | RequestException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        } catch (\JsonException) { return $this->json($response, ['error' => 'El cuerpo debe ser un objeto JSON válido.'], 400); }
    }
    public function changeState(Request $request, Response $response, array $args): Response {
        try {
            $data = $this->datos($request);

            $dataService = $this->container->get(DataService::class);
            $path = 'proyecto/' . rawurlencode($args['id']) . '/estado';
            return $this->forward($response, $dataService->post($path, json_encode($data, JSON_THROW_ON_ERROR)));
        } catch (ConnectException | RequestException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        } catch (\JsonException) { return $this->json($response, ['error' => 'El cuerpo debe ser un objeto JSON válido.'], 400); }
    }
    public function assign(Request $request, Response $response, array $args): Response {
        try {
            $data = $this->datos($request);

            $dataService = $this->container->get(DataService::class);
            $path = 'proyecto/' . rawurlencode($args['id']) . '/tecnico';
            return $this->forward($response, $dataService->post($path, json_encode($data, JSON_THROW_ON_ERROR)));
        } catch (ConnectException | RequestException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        } catch (\JsonException) { return $this->json($response, ['error' => 'El cuerpo debe ser un objeto JSON válido.'], 400); }
    }

    private function datos(Request $request): array {
        $request->getBody()->rewind();
        $body = (string) $request->getBody();
        $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !str_starts_with(ltrim($body), '{')) throw new \JsonException('Objeto requerido.');
        return $data;
    }
    private function forward(Response $response, \Psr\Http\Message\ResponseInterface $upstream): Response {
        $response->getBody()->write((string) $upstream->getBody());
        return $response->withHeader('Content-Type', $upstream->getHeaderLine('Content-Type') ?: 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
    }
    private function json(Response $response, array $payload, int $status): Response {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

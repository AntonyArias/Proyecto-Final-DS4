<?php
namespace App\controllers;
use App\Services\DataService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Evidencia {
    public function __construct(private ContainerInterface $container) {}
    public function create(Request $request, Response $response, array $args): Response {
        $file = $request->getUploadedFiles()['archivo'] ?? null;
        $uuid = ($request->getParsedBody() ?? [])['uuid'] ?? null;
        if (!$file || $file->getError() !== UPLOAD_ERR_OK || !$file->getSize() || $file->getSize() > 5242880 || !is_string($uuid)) {
            $response->getBody()->write(json_encode(['error' => 'UUID y foto de máximo 5 MiB requeridos.']));
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(422);
        }
        try {
            $upstream = $this->container->get(DataService::class)->upload('visita/' . rawurlencode($args['id']) . '/evidencia', $uuid, $file);
            return $this->forward($response, $upstream);
        } catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }
    public function read(Request $request, Response $response, array $args): Response {
        try { return $this->forward($response, $this->container->get(DataService::class)->get('evidencia/' . rawurlencode($args['id']))); }
        catch (ConnectException | RequestException) { throw new \RuntimeException('El servicio de datos no está disponible.', 502); }
    }
    private function forward(Response $response, \Psr\Http\Message\ResponseInterface $upstream): Response {
        $response = $response->withBody($upstream->getBody())->withStatus($upstream->getStatusCode());
        foreach (['Content-Type', 'Content-Disposition', 'Content-Length'] as $header) if ($upstream->hasHeader($header)) $response = $response->withHeader($header, $upstream->getHeaderLine($header));
        return $response;
    }
}

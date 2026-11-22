<?php
namespace App\controllers;
use App\Services\VisitaService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;

class Evidencia {
    public function __construct(private ContainerInterface $container) {}
    public function create(Request $request, Response $response, array $args): Response {
        $result = (new VisitaService())->foto($request->getAttribute('usuario'), v::id($args['id']),
            $request->getParsedBody() ?? [], $request->getUploadedFiles()['archivo'] ?? null);
        $response->getBody()->write(json_encode(v::salida($result), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($result['repetido'] ? 200 : 201);
    }
    public function read(Request $request, Response $response, array $args): Response {
        $file = (new VisitaService())->archivo($request->getAttribute('usuario'), v::id($args['id']));
        return $response->withBody(new Stream(fopen($file['path'], 'rb')))->withHeader('Content-Type', $file['mime'])
            ->withHeader('Content-Length', (string) $file['bytes'])->withHeader('Content-Disposition', 'inline; filename="' . $file['nombre'] . '"');
    }
}

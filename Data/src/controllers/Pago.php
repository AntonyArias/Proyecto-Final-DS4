<?php
namespace App\controllers;
use App\Repositories\PagoRepository;
use App\Services\PagoService;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Pago {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario'); AutorizacionService::roles($actor, 1, 3);
        if (isset($args['id'])) {
            $id = v::id($args['id']);
            $data = (new PagoRepository())->find($id, $actor)->toArray();
            $data['historial'] = \Illuminate\Database\Capsule\Manager::table('historial_pagos')->where('pago_id', $id)->orderBy('id')->get()->all();
            return $this->json($response, ['data' => $data]);
        }
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario'); AutorizacionService::roles($actor, 1, 3);
        [$offset, $limit] = v::pagina($args); $params = $request->getQueryParams();
        $query = (new PagoRepository())->query($actor);
        if (isset($params['proyecto_id'])) $query->where('proyecto_id', v::id($params['proyecto_id']));
        foreach (['tipo', 'estado'] as $campo) if (isset($params[$campo])) $query->where($campo, v::texto($params, $campo, 30));
        \App\Repositories\ReporteRepository::fechas($query, $params, 'fecha_pago');
        $total = (clone $query)->count('id');
        $data = $query->orderByDesc('id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function create(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new PagoService())->guardar($request->getAttribute('usuario'), $request->getParsedBody() ?? [])], 201);
    }
    public function update(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new PagoService())->guardar($request->getAttribute('usuario'), $request->getParsedBody() ?? [], v::id($args['id']))]);
    }
    public function delete(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new PagoService())->anular($request->getAttribute('usuario'), v::id($args['id']))]);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

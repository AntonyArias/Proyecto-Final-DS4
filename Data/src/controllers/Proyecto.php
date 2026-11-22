<?php
namespace App\controllers;
use App\Repositories\ProyectoRepository;
use App\Services\ProyectoService;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Proyecto {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        if (isset($args['id'])) return $this->json($response, ['data' => (new ProyectoService())->detalle($request->getAttribute('usuario'), v::id($args['id']))]);
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario');
        [$offset, $limit] = v::pagina($args); $params = $request->getQueryParams();
        $query = (new ProyectoRepository())->query($actor);
        if (isset($params['estado'])) $query->where('proyectos.estado', v::texto($params, 'estado', 30));
        if (isset($params['nombre'])) $query->where('proyectos.nombre', 'LIKE', '%' . v::texto($params, 'nombre', 150, false) . '%');
        foreach (['cliente_id', 'servicio_id'] as $campo) if (isset($params[$campo])) $query->where('cotizaciones.' . $campo, v::id($params[$campo]));
        $total = (clone $query)->count('proyectos.id');
        $data = $query->orderByDesc('proyectos.id')->offset($offset)->limit($limit)->get()->toArray();
        if ($actor['rol_id'] === 2) foreach ($data as &$fila) unset($fila['monto'], $fila['moneda']);
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function update(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario');
        AutorizacionService::roles($actor, 1);
        $id = v::id($args['id']);
        $data = $request->getParsedBody() ?? [];
        $nombre = v::texto($data, 'nombre', 150);
        \Illuminate\Database\Capsule\Manager::connection()->transaction(function() use ($id, $actor, $nombre) {
            (new ProyectoRepository())->find($id, $actor, true);
            \App\Models\Proyecto::query()->where('id', $id)->update(['nombre' => $nombre]);
        });
        return $this->json($response, ['data' => (new ProyectoService())->detalle($actor, $id)]);
    }
    public function changeState(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new ProyectoService())->estado($request->getAttribute('usuario'), v::id($args['id']), $request->getParsedBody() ?? [])]);
    }
    public function assign(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new ProyectoService())->asignar($request->getAttribute('usuario'), v::id($args['id']), $request->getParsedBody() ?? [])]);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

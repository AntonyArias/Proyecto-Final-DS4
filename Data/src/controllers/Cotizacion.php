<?php
namespace App\controllers;
use App\Repositories\CotizacionRepository;
use App\Services\CotizacionService;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Cotizacion {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario'); AutorizacionService::roles($actor, 1, 3);
        if (isset($args['id'])) {
            $id = v::id($args['id']);
            $data = (new CotizacionRepository())->find($id, $actor)->toArray();
            $data['historial'] = \Illuminate\Database\Capsule\Manager::table('historial_cotizaciones')->where('cotizacion_id', $id)->orderBy('id')->get()->all();
            return $this->json($response, ['data' => $data]);
        }
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario'); AutorizacionService::roles($actor, 1, 3);
        [$offset, $limit] = v::pagina($args); $params = $request->getQueryParams();
        $query = (new CotizacionRepository())->query($actor);
        if (isset($params['estado'])) $query->where('cotizaciones.estado', v::texto($params, 'estado', 30));
        foreach (['cliente_id', 'servicio_id'] as $campo) if (isset($params[$campo])) $query->where('cotizaciones.' . $campo, v::id($params[$campo]));
        $total = (clone $query)->count('cotizaciones.id');
        $data = $query->orderByDesc('cotizaciones.id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function create(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new CotizacionService())->guardar($request->getAttribute('usuario'), $request->getParsedBody() ?? [])], 201);
    }
    public function update(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new CotizacionService())->guardar($request->getAttribute('usuario'), $request->getParsedBody() ?? [], v::id($args['id']))]);
    }
    public function changeState(Request $request, Response $response, array $args): Response {
        return $this->json($response, ['data' => (new CotizacionService())->estado($request->getAttribute('usuario'), v::id($args['id']), $request->getParsedBody() ?? [])]);
    }
    public function delete(Request $request, Response $response, array $args): Response {
        (new CotizacionService())->eliminar($request->getAttribute('usuario'), v::id($args['id']));
        return $this->json($response, ['message' => 'Cotización eliminada.']);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

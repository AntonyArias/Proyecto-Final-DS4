<?php
namespace App\controllers;
use App\Models\Visita as VisitaModel;
use App\Repositories\VisitaRepository;
use App\Repositories\ReporteRepository;
use App\Services\VisitaService;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Visita {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        if (isset($args['id'])) return $this->json($response, ['data' => (new VisitaService())->detalle($request->getAttribute('usuario'), v::id($args['id']))]);
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function readUuid(Request $request, Response $response, array $args): Response {
        $visita = VisitaModel::query()->where('uuid', v::uuid($args['uuid']))->first();
        if (!$visita) throw new \RuntimeException('Visita no encontrada.', 404);
        return $this->json($response, ['data' => (new VisitaService())->detalle($request->getAttribute('usuario'), $visita->id)]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        [$offset, $limit] = v::pagina($args); $params = $request->getQueryParams();
        $query = (new VisitaRepository())->query($request->getAttribute('usuario'));
        if (isset($params['proyecto_id'])) $query->where('proyecto_id', v::id($params['proyecto_id']));
        ReporteRepository::fechas($query, $params, 'fecha_captura');
        $total = (clone $query)->count('id');
        $data = $query->orderByDesc('fecha_captura')->orderByDesc('id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function create(Request $request, Response $response, array $args): Response {
        $result = (new VisitaService())->sincronizar($request->getAttribute('usuario'), $request->getParsedBody() ?? []);
        return $this->json($response, $result, $result['repetido'] ? 200 : 201);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

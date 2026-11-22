<?php
namespace App\controllers;
use App\Models\Solicitud as SolicitudModel;
use App\Models\Servicio;
use App\Validators\SolicitudValidator;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Solicitud {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        if (isset($args['id'])) {
            $registro = SolicitudModel::find(v::id($args['id']));
            if (!$registro) throw new \RuntimeException('Solicitud no encontrada.', 404);
            return $this->json($response, ['data' => $registro->toArray()]);
        }
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        [$offset, $limit] = v::pagina($args);
        $query = SolicitudModel::query();
        $params = $request->getQueryParams();
        if (isset($params['estado'])) $query->where('estado', v::texto($params, 'estado', 30));
        $total = (clone $query)->count('id');
        $data = $query->orderByDesc('id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody() ?? [];
        $errors = (new SolicitudValidator())->validate($data);
        if ($errors !== []) return $this->json($response, ['errors' => $errors], 422);
        $servicio = Servicio::find($data['servicio_id']);
        if (!$servicio || !$servicio->activo) throw new \InvalidArgumentException('Servicio no disponible.', 422);
        $data = array_intersect_key($data, array_flip(['servicio_id', 'nombre_completo', 'email', 'telefono', 'descripcion']));
        $solicitud = SolicitudModel::create($data);
        return $this->json($response, ['data' => $solicitud->fresh()->toArray()], 201);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

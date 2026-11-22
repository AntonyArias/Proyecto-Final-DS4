<?php
namespace App\controllers;
use App\Models\Servicio as ServicioModel;
use App\Repositories\ServicioRepository;
use App\Validators\ServicioValidator;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Servicio {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        if (isset($args['id'])) return $this->json($response, ['data' => (new ServicioRepository())->find(v::id($args['id']))->toArray()]);
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        [$offset, $limit] = v::pagina($args);
        $query = (new ServicioRepository())->query($request->getQueryParams());
        $total = (clone $query)->count('servicios.id');
        $data = $query->orderBy('servicios.id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }

    public function publicRead(Request $request, Response $response, array $args): Response {
        [$offset, $limit] = v::pagina($request->getQueryParams());
        $params = $request->getQueryParams(); $params['activo'] = '1';
        $query = (new ServicioRepository())->query($params);
        $total = (clone $query)->count('id');
        $data = $query->orderBy('id')->offset($offset)->limit($limit)->get()->toArray();
        return $this->json($response, ['data' => $data, 'pagination' => compact('offset', 'limit', 'total')]);
    }
    public function create(Request $request, Response $response, array $args): Response {
        return $this->guardar($request, $response, null);
    }
    public function update(Request $request, Response $response, array $args): Response {
        return $this->guardar($request, $response, v::id($args['id']));
    }
    private function guardar(Request $request, Response $response, ?int $id): Response {
        $actor = $request->getAttribute('usuario');
        AutorizacionService::roles($actor, 1);
        $input = $request->getParsedBody() ?? [];
        $errors = (new ServicioValidator())->validate($input, $id !== null);
        if ($errors !== []) return $this->json($response, ['errors' => $errors], 422);
        $resultado = DB::connection()->transaction(function() use ($actor, $input, $id) {
            $repository = new ServicioRepository();
            $registro = $id ? $repository->find($id, true) : null;
            $data = array_intersect_key($input, array_flip(['nombre', 'descripcion', 'activo']));
            $data = array_replace(['activo' => true], $registro ? $registro->getAttributes() : [], $data);
            $data = array_intersect_key($data, array_flip(['nombre', 'descripcion', 'activo']));

            if ($registro) { $registro->fill($data); $registro->save(); }
            else $registro = $repository->create($data);
            return $registro->fresh()->toArray();
        });
        return $this->json($response, ['data' => $resultado], $id ? 200 : 201);
    }
    public function delete(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario');
        AutorizacionService::roles($actor, 1);
        $id = v::id($args['id']);
        (new ServicioRepository())->find($id)->delete();
        return $this->json($response, ['message' => 'Servicio eliminado.']);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

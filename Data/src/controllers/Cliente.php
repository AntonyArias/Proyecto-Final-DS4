<?php
namespace App\controllers;
use App\Models\Cliente as ClienteModel;
use App\Models\User;
use App\Repositories\ClienteRepository;
use App\Validators\ClienteValidator;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Cliente {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        if (isset($args['id'])) return $this->json($response, ['data' => (new ClienteRepository())->find(v::id($args['id']))->toArray()]);
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        [$offset, $limit] = v::pagina($args);
        $query = (new ClienteRepository())->query($request->getQueryParams());
        $total = (clone $query)->count('clientes.id');
        $data = $query->orderBy('clientes.id')->offset($offset)->limit($limit)->get()->toArray();
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
        $errors = (new ClienteValidator())->validate($input, $id !== null);
        if ($errors !== []) return $this->json($response, ['errors' => $errors], 422);
        $resultado = DB::connection()->transaction(function() use ($actor, $input, $id) {
            $repository = new ClienteRepository();
            $registro = $id ? $repository->find($id, true) : null;
            $data = array_intersect_key($input, array_flip(['usuario_id', 'cedula', 'nombre_completo', 'email', 'telefono', 'direccion', 'activo']));
            $data = array_replace(['usuario_id' => null, 'telefono' => null, 'direccion' => null, 'activo' => true], $registro ? $registro->getAttributes() : [], $data);
            $data = array_intersect_key($data, array_flip(['usuario_id', 'cedula', 'nombre_completo', 'email', 'telefono', 'direccion', 'activo']));

            if ($data['usuario_id'] !== null) {
                $usuario = User::find(v::id($data['usuario_id']));
                if (!$usuario || (int) $usuario->rol_id !== 3 || !$usuario->activo) throw new \InvalidArgumentException('Debe asociar una cuenta activa con rol Cliente.', 422);
            }

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
        (new ClienteRepository())->find($id)->delete();
        return $this->json($response, ['message' => 'Cliente eliminado.']);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

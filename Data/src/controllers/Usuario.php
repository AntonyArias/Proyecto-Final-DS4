<?php
namespace App\controllers;
use App\Models\User as UsuarioModel;
use App\Repositories\UsuarioRepository;
use App\Validators\UsuarioValidator;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Usuario {
    public function __construct(private ContainerInterface $container) {}
    public function read(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        if (isset($args['id'])) return $this->json($response, ['data' => (new UsuarioRepository())->find(v::id($args['id']))->toArray()]);
        return $this->filter($request, $response, ['offset' => 0, 'limit' => 20]);
    }
    public function filter(Request $request, Response $response, array $args): Response {
        AutorizacionService::roles($request->getAttribute('usuario'), 1);
        [$offset, $limit] = v::pagina($args);
        $query = (new UsuarioRepository())->query($request->getQueryParams());
        $total = (clone $query)->count('usuarios.id');
        $data = $query->orderBy('usuarios.id')->offset($offset)->limit($limit)->get()->toArray();
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
        $errors = (new UsuarioValidator())->validate($input, $id !== null);
        if ($errors !== []) return $this->json($response, ['errors' => $errors], 422);
        $resultado = DB::connection()->transaction(function() use ($actor, $input, $id) {
            $repository = new UsuarioRepository();
            $registro = $id ? $repository->find($id, true) : null;
            $data = array_intersect_key($input, array_flip(['username', 'password', 'nombre_completo', 'email', 'rol_id', 'activo']));
            $data = array_replace(['activo' => true], $registro ? $registro->getAttributes() : [], $data);
            $data = array_intersect_key($data, array_flip(['username', 'password', 'nombre_completo', 'email', 'rol_id', 'activo']));

            $data['rol_id'] = (int) $data['rol_id'];
            if ($id === $actor['id'] && (!(bool) $data['activo'] || $data['rol_id'] !== 1)) throw new \RuntimeException('No puede desactivar ni cambiar el rol de su propia cuenta.', 409);
            if ($registro && (int) $registro->rol_id !== $data['rol_id'] && (
                DB::table('clientes')->where('usuario_id', $id)->exists() || DB::table('proyecto_tecnicos')->where('tecnico_id', $id)->exists() ||
                DB::table('visitas')->where('tecnico_id', $id)->exists())) throw new \RuntimeException('El usuario tiene registros asociados a su rol.', 409);
            if (isset($input['password'])) $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            if ($registro && (isset($input['password']) || (int) $registro->rol_id !== $data['rol_id'] || (bool) $registro->activo !== (bool) $data['activo'])) {
                $data['tkRef'] = null; $data['sesion_id'] = null;
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
        if ($id === $actor['id']) throw new \RuntimeException('No puede eliminar su propia cuenta.', 409);
        (new UsuarioRepository())->find($id)->delete();
        return $this->json($response, ['message' => 'Usuario eliminado.']);
    }
    private function json(Response $response, array $payload, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

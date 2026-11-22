<?php
declare(strict_types=1);
namespace App\controllers;
use App\Models\User;
use App\Services\AutorizacionService;
use App\Services\ValidacionService as v;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class Auth {
    public function __construct(private ContainerInterface $container) {}
    public function credentials(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];
        $username = v::texto($data, 'username', 50);
        $password = $data['password'] ?? '';
        if (!is_string($password) || strlen($password) > 256) throw new \InvalidArgumentException('Contraseña inválida.', 422);
        $usuario = User::query()->where('username', $username)->where('activo', true)->first();
        $hash = $usuario?->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password, $hash) || !$usuario) throw new \RuntimeException('Credenciales inválidas.', 401);
        return $this->json($response, $usuario->toArray());
    }
    public function refresh(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];
        $usuario = User::find(v::id($data['id'] ?? null));
        if (!$usuario || !$usuario->activo || !$usuario->tkRef || !is_string($data['tkRef'] ?? null)
            || !hash_equals($usuario->tkRef, hash('sha256', $data['tkRef']))
            || !hash_equals($usuario->sesion_id ?? '', (string) ($data['sid'] ?? ''))) throw new \RuntimeException('Sesión de refresco inválida.', 401);
        return $this->json($response, $usuario->toArray());
    }
    public function session(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];
        DB::connection()->transaction(function() use ($data) {
            $usuario = User::query()->where('id', v::id($data['id'] ?? null))->lockForUpdate()->first();
            if (!$usuario || !$usuario->activo) throw new \RuntimeException('Usuario no disponible.', 401);
            if (isset($data['expected_sid']) && (!hash_equals($usuario->sesion_id ?? '', (string) $data['expected_sid'])
                || !hash_equals($usuario->tkRef ?? '', hash('sha256', (string) ($data['expected_tkRef'] ?? ''))))) throw new \RuntimeException('La sesión ya fue renovada o revocada.', 401);
            $sid = v::texto($data, 'sid', 64);
            if (!preg_match('/^[a-f0-9]{64}$/D', $sid)) throw new \InvalidArgumentException('Identificador de sesión inválido.', 422);
            $usuario->sesion_id = $sid;
            $usuario->tkRef = hash('sha256', v::texto($data, 'tkRef', 4096));
            $usuario->save();
        });
        return $this->json($response, ['message' => 'Sesión registrada.']);
    }
    public function updateTokenRef(Request $request, Response $response, array $args): Response {
        $actor = $request->getAttribute('usuario');
        if ($actor['id'] !== v::id($args['id'])) throw new \RuntimeException('No puede cerrar la sesión de otro usuario.', 403);
        User::query()->where('id', $actor['id'])->update(['tkRef' => null, 'sesion_id' => null]);
        return $this->json($response, ['message' => 'Sesión cerrada.']);
    }
    public function read(Request $request, Response $response): Response {
        return $this->json($response, ['data' => User::findOrFail($request->getAttribute('usuario')['id'])->toArray()]);
    }
    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode(v::salida($data), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

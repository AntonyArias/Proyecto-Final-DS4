<?php
declare(strict_types=1);
namespace App\controllers;
use App\Services\DataService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Auth {
    public function __construct(private ContainerInterface $container) {}
    public function login(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];
        if (!is_array($data) || !is_string($data['username'] ?? null) || trim($data['username']) === ''
            || !is_string($data['password'] ?? null) || $data['password'] === '') return $this->json($response, ['error' => 'El usuario y la contraseña son obligatorios.'], 422);
        try {
            $service = $this->container->get(DataService::class);
            $upstream = $service->post('auth/credentials', json_encode($data, JSON_THROW_ON_ERROR));
            if ($upstream->getStatusCode() !== 200) return $this->forward($response, $upstream);
            $usuario = json_decode((string) $upstream->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $tokens = $this->tokens($usuario);
            $saved = $service->post('auth/session', json_encode(['id' => $usuario['id'], 'sid' => $tokens['sid'], 'tkRef' => $tokens['tkRef']], JSON_THROW_ON_ERROR));
            if ($saved->getStatusCode() !== 200) return $this->forward($response, $saved);
            unset($tokens['sid']);
            return $this->json($response, $tokens, 200);
        } catch (ConnectException | RequestException) { return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502); }
    }
    public function refresh(Request $request, Response $response): Response {
        $data = $request->getParsedBody() ?? [];
        if (!is_string($data['tkRef'] ?? null)) return $this->json($response, ['error' => 'Token de refresco requerido.'], 422);
        $config = $this->container->get('config');
        try { $claims = JWT::decode($data['tkRef'], new Key($config->refresh_key, 'HS256')); }
        catch (\UnexpectedValueException | \DomainException) { return $this->json($response, ['error' => 'Refresco inválido o vencido.'], 401); }
        if (($claims->token_use ?? '') !== 'refresh' || ($claims->iss ?? '') !== 'ambiental-business' || !isset($claims->exp, $claims->sub, $claims->sid)) return $this->json($response, ['error' => 'Refresco inválido.'], 401);
        try {
            $service = $this->container->get(DataService::class);
            $upstream = $service->post('auth/refresh', json_encode(['id' => $claims->sub, 'sid' => $claims->sid, 'tkRef' => $data['tkRef']], JSON_THROW_ON_ERROR));
            if ($upstream->getStatusCode() !== 200) return $this->forward($response, $upstream);
            $usuario = json_decode((string) $upstream->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $tokens = $this->tokens($usuario);
            $saved = $service->post('auth/session', json_encode(['id' => $usuario['id'], 'sid' => $tokens['sid'], 'tkRef' => $tokens['tkRef'],
                'expected_sid' => $claims->sid, 'expected_tkRef' => $data['tkRef']], JSON_THROW_ON_ERROR));
            if ($saved->getStatusCode() !== 200) return $this->forward($response, $saved);
            unset($tokens['sid']);
            return $this->json($response, $tokens, 200);
        } catch (ConnectException | RequestException) { return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502); }
    }
    public function logout(Request $request, Response $response, array $args): Response {
        try {
            $service = $this->container->get(DataService::class);
            return $this->forward($response, $service->put('auth/tkRef/' . rawurlencode($args['id']), json_encode(['tkRef' => ''], JSON_THROW_ON_ERROR)));
        } catch (ConnectException | RequestException) { return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502); }
    }
    public function read(Request $request, Response $response): Response {
        try { return $this->forward($response, $this->container->get(DataService::class)->get('auth/me')); }
        catch (ConnectException | RequestException) { return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502); }
    }
    private function tokens(array $usuario): array {
        $config = $this->container->get('config');
        $now = time(); $sid = bin2hex(random_bytes(32));
        $ttl = max(60, min(86400, $config->jwt_ttl));
        $base = ['iss' => 'ambiental-business', 'iat' => $now, 'sub' => (string) $usuario['id'], 'sid' => $sid];
        $payload = $base + ['exp' => $now + $ttl, 'token_use' => 'access', 'username' => $usuario['username'], 'rol_id' => (int) $usuario['rol_id']];
        $refresh = $base + ['exp' => $now + max(60, $config->jwt_refresh_ttl), 'token_use' => 'refresh'];
        return ['token' => JWT::encode($payload, $config->key, 'HS256'), 'tkRef' => JWT::encode($refresh, $config->refresh_key, 'HS256'),
            'expires_in' => $ttl, 'user' => $usuario, 'sid' => $sid];
    }
    private function forward(Response $response, \Psr\Http\Message\ResponseInterface $upstream): Response {
        $response->getBody()->write((string) $upstream->getBody());
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($upstream->getStatusCode());
    }
    private function json(Response $response, array $data, int $status): Response {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}

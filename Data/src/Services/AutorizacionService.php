<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AutorizacionService {
    public static function autenticar(Request $request): array {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer (\S+)$/iD', $header, $partes)) throw new \RuntimeException('Sesión requerida.', 401);
        try { $claims = JWT::decode($partes[1], new Key($_ENV['KEY'], 'HS256')); }
        catch (\UnexpectedValueException | \DomainException) { throw new \RuntimeException('Token inválido o vencido.', 401); }
        if (($claims->iss ?? '') !== 'ambiental-business' || ($claims->token_use ?? '') !== 'access' || !isset($claims->exp, $claims->sid, $claims->sub)) throw new \RuntimeException('Token inválido.', 401);
        $usuario = User::query()->where('id', $claims->sub)->where('activo', true)->first();
        if (!$usuario || !$usuario->sesion_id || !hash_equals($usuario->sesion_id, (string) $claims->sid)) throw new \RuntimeException('Sesión revocada.', 401);
        return ['id' => $usuario->id, 'username' => $usuario->username, 'rol_id' => $usuario->rol_id];
    }
    public static function roles(array $usuario, int ...$roles): void {
        if (!in_array($usuario['rol_id'], $roles, true)) throw new \RuntimeException('Operación no autorizada para este rol.', 403);
    }
}

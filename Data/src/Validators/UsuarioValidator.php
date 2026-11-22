<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class UsuarioValidator {
    private function reglas(): array {
        return [
            'username' => ['regla' => v::stringType()->notBlank()->length(v::between(8, 16))->regex('/^[a-z0-9]{8,16}$/'), 'requerido' => true, 'mensaje' => 'Valor inválido para username.'],
            'password' => ['regla' => v::stringType()->length(v::between(8, 64))->regex('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&])[\s\S]{8,64}$/'), 'requerido' => true, 'mensaje' => 'Valor inválido para password.'],
            'nombre_completo' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para nombre_completo.'],
            'email' => ['regla' => v::stringType()->email()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para email.'],
            'rol_id' => ['regla' => v::intType()->in([1, 2, 3]), 'requerido' => true, 'mensaje' => 'Valor inválido para rol_id.'],
            'activo' => ['regla' => v::boolType(), 'requerido' => false, 'mensaje' => 'Valor inválido para activo.'],
        ];
    }
    public function validate(array $data, bool $partial = false): array {
        $errores = [];
        foreach ($this->reglas() as $campo => $config) {
            if (!array_key_exists($campo, $data) && ($partial || !$config['requerido'])) continue;
            if (array_key_exists($campo, $data) && $data[$campo] === null && !$config['requerido'] && in_array($campo, ['usuario_id', 'solicitud_id', 'telefono', 'direccion'], true)) continue;
            if ($config['regla']->validate($data[$campo] ?? null)->hasFailed()) $errores[$campo] = $config['mensaje'];
        }
        return $errores;
    }
}

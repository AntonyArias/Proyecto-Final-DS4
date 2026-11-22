<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class ClienteValidator {
    private function reglas(): array {
        return [
            'usuario_id' => ['regla' => v::intType()->positive(), 'requerido' => false, 'mensaje' => 'Valor inválido para usuario_id.'],
            'cedula' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 30)), 'requerido' => true, 'mensaje' => 'Valor inválido para cedula.'],
            'nombre_completo' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para nombre_completo.'],
            'email' => ['regla' => v::stringType()->email()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para email.'],
            'telefono' => ['regla' => v::stringType()->length(v::between(0, 30)), 'requerido' => false, 'mensaje' => 'Valor inválido para telefono.'],
            'direccion' => ['regla' => v::stringType()->length(v::between(0, 500)), 'requerido' => false, 'mensaje' => 'Valor inválido para direccion.'],
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

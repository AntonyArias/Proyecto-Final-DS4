<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class ServicioValidator {
    private function reglas(): array {
        return [
            'nombre' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para nombre.'],
            'descripcion' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 10000)), 'requerido' => true, 'mensaje' => 'Valor inválido para descripcion.'],
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

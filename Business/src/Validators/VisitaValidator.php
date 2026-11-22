<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class VisitaValidator {
    private function reglas(): array {
        return [
            'uuid' => ['regla' => v::stringType()->length(v::equals(36)), 'requerido' => true, 'mensaje' => 'Valor inválido para uuid.'],
            'proyecto_id' => ['regla' => v::intType()->positive(), 'requerido' => true, 'mensaje' => 'Valor inválido para proyecto_id.'],
            'fecha_captura' => ['regla' => v::stringType()->length(v::between(20, 32)), 'requerido' => true, 'mensaje' => 'Valor inválido para fecha_captura.'],
            'observaciones' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 10000)), 'requerido' => true, 'mensaje' => 'Valor inválido para observaciones.'],
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

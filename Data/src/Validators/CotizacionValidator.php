<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class CotizacionValidator {
    private function reglas(): array {
        return [
            'solicitud_id' => ['regla' => v::intType()->positive(), 'requerido' => false, 'mensaje' => 'Valor inválido para solicitud_id.'],
            'cliente_id' => ['regla' => v::intType()->positive(), 'requerido' => true, 'mensaje' => 'Valor inválido para cliente_id.'],
            'servicio_id' => ['regla' => v::intType()->positive(), 'requerido' => true, 'mensaje' => 'Valor inválido para servicio_id.'],
            'descripcion' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 10000)), 'requerido' => true, 'mensaje' => 'Valor inválido para descripcion.'],
            'monto' => ['regla' => v::stringType()->regex('/^(0|[1-9]\d{0,9})(?:\.\d{1,2})?$/D'), 'requerido' => true, 'mensaje' => 'Valor inválido para monto.'],
            'moneda' => ['regla' => v::stringType()->equals('CRC'), 'requerido' => false, 'mensaje' => 'Valor inválido para moneda.'],
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

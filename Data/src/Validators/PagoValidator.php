<?php
namespace App\Validators;
use Respect\Validation\ValidatorBuilder as v;

final class PagoValidator {
    private function reglas(): array {
        return [
            'proyecto_id' => ['regla' => v::intType()->positive(), 'requerido' => true, 'mensaje' => 'Valor inválido para proyecto_id.'],
            'tipo' => ['regla' => v::stringType()->in(['Anticipo', 'Final']), 'requerido' => true, 'mensaje' => 'Valor inválido para tipo.'],
            'estado' => ['regla' => v::stringType()->in(['Pendiente', 'Recibido']), 'requerido' => true, 'mensaje' => 'Valor inválido para estado.'],
            'monto' => ['regla' => v::stringType()->regex('/^(0|[1-9]\d{0,9})(?:\.\d{1,2})?$/D'), 'requerido' => true, 'mensaje' => 'Valor inválido para monto.'],
            'moneda' => ['regla' => v::stringType()->equals('CRC'), 'requerido' => false, 'mensaje' => 'Valor inválido para moneda.'],
            'fecha_pago' => ['regla' => v::stringType()->length(v::between(20, 32)), 'requerido' => true, 'mensaje' => 'Valor inválido para fecha_pago.'],
            'referencia' => ['regla' => v::stringType()->notBlank()->length(v::between(1, 150)), 'requerido' => true, 'mensaje' => 'Valor inválido para referencia.'],
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

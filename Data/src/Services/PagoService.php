<?php
namespace App\Services;
use App\Models\Pago;
use App\Repositories\ProyectoRepository;
use App\Validators\PagoValidator;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;

class PagoService {
    public static function validarTotal(int $recibido, int $nuevo, int $cotizado): void {
        if ($nuevo < 1 || $recibido + $nuevo > $cotizado) throw new \RuntimeException('El pago debe ser positivo y los recibidos no pueden exceder la cotización.', 409);
    }
    public function guardar(array $actor, array $input, ?int $id = null): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $input, $id) {
            $anterior = $id ? Pago::find($id) : null;
            if ($id && !$anterior) throw new \RuntimeException('Pago no encontrado.', 404);
            $data = array_replace($anterior ? $anterior->getAttributes() : ['moneda' => 'CRC'], $input);
            if ($anterior && !isset($input['fecha_pago'])) $data['fecha_pago'] = str_replace(' ', 'T', $data['fecha_pago']) . 'Z';
            if ((new PagoValidator())->validate($data) !== []) throw new \InvalidArgumentException('Datos de pago inválidos.', 422);
            $proyecto = (new ProyectoRepository())->find(v::id($data['proyecto_id']), $actor, true);
            if ($anterior) {
                $anterior = Pago::query()->where('id', $id)->lockForUpdate()->first();
                if ($anterior->estado !== 'Pendiente' || $anterior->proyecto_id !== $proyecto->id) throw new \RuntimeException('Solo se edita un pago pendiente del mismo proyecto.', 409);
            }
            $centimos = v::centimos($data['monto']);
            if ($centimos < 1) throw new \InvalidArgumentException('Monto positivo requerido.', 422);
            if ($data['estado'] === 'Recibido') {
                $recibido = v::centimos((string) Pago::query()->where('proyecto_id', $proyecto->id)->where('estado', 'Recibido')->sum('monto'));
                self::validarTotal($recibido, $centimos, v::centimos($proyecto->monto));
                if ($data['tipo'] === 'Final' && $proyecto->estado !== 'Facturada') throw new \RuntimeException('El pago final recibido requiere que el proyecto esté facturado.', 409);
            }
            $data['monto'] = v::monto($centimos);
            $data['fecha_pago'] = v::fecha($data['fecha_pago']);
            $data = array_intersect_key($data, array_flip(['proyecto_id', 'tipo', 'estado', 'monto', 'moneda', 'fecha_pago', 'referencia']));
            $estadoAnterior = $anterior?->estado;
            if ($anterior) { $pago = $anterior; $pago->fill($data); $pago->save(); }
            else $pago = Pago::create($data + ['registrado_por' => $actor['id']]);
            DB::table('historial_pagos')->insert(['pago_id' => $pago->id, 'usuario_id' => $actor['id'], 'estado_anterior' => $estadoAnterior, 'estado_nuevo' => $pago->estado]);
            return $pago->fresh()->toArray();
        });
    }
    public function anular(array $actor, int $id): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $id) {
            $pago = Pago::find($id);
            if (!$pago) throw new \RuntimeException('Pago no encontrado.', 404);
            (new ProyectoRepository())->find($pago->proyecto_id, $actor, true);
            $pago = Pago::query()->where('id', $id)->lockForUpdate()->first();
            if ($pago->estado !== 'Anulado') {
                DB::table('historial_pagos')->insert(['pago_id' => $id, 'usuario_id' => $actor['id'], 'estado_anterior' => $pago->estado, 'estado_nuevo' => 'Anulado']);
                $pago->estado = 'Anulado'; $pago->save();
            }
            return $pago->toArray();
        });
    }
}

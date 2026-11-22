<?php
namespace App\Services;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Models\Proyecto;
use App\Models\Cotizacion;
use App\Repositories\CotizacionRepository;
use App\Validators\CotizacionValidator;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;

class CotizacionService {
    public function guardar(array $actor, array $input, ?int $id = null): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $input, $id) {
            $registro = $id ? (new CotizacionRepository())->find($id, $actor, true) : null;
            if ($registro && $registro->estado !== 'Cotizacion') throw new \RuntimeException('Solo se puede editar una cotización en borrador.', 409);
            $data = array_replace($registro ? $registro->getAttributes() : ['solicitud_id' => null, 'moneda' => 'CRC'], $input);
            if ((new CotizacionValidator())->validate($data) !== []) throw new \InvalidArgumentException('Datos de cotización inválidos.', 422);
            $cliente = Cliente::find(v::id($data['cliente_id']));
            $servicio = Servicio::find(v::id($data['servicio_id']));
            if (!$cliente || !$servicio || !$cliente->activo || !$servicio->activo) throw new \InvalidArgumentException('Seleccione cliente y servicio activos.', 422);
            $centimos = v::centimos($data['monto']);
            if ($centimos <= 0) throw new \InvalidArgumentException('El monto debe ser mayor que cero.', 422);
            $data['monto'] = v::monto($centimos);
            if ($data['solicitud_id'] !== null) {
                $solicitud = Solicitud::query()->where('id', v::id($data['solicitud_id']))->lockForUpdate()->first();
                if (!$solicitud || $solicitud->servicio_id !== $servicio->id) throw new \InvalidArgumentException('La solicitud no corresponde al servicio.', 422);
                $solicitud->estado = 'Cotizada'; $solicitud->save();
            }
            $data = array_intersect_key($data, array_flip(['solicitud_id', 'cliente_id', 'servicio_id', 'descripcion', 'monto', 'moneda']));
            if ($registro) { $registro->fill($data); $registro->save(); }
            else {
                $registro = Cotizacion::create($data + ['creado_por' => $actor['id']]);
                DB::table('historial_cotizaciones')->insert(['cotizacion_id' => $registro->id, 'usuario_id' => $actor['id'], 'estado_nuevo' => 'Cotizacion']);
            }
            return $registro->fresh()->toArray();
        });
    }
    public function estado(array $actor, int $id, array $data): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $id, $data) {
            $registro = (new CotizacionRepository())->find($id, $actor, true);
            $estado = v::texto($data, 'estado', 30);
            if ($estado === 'Aprobada') {
                if ($registro->estado === 'Aprobada') return ['cotizacion' => $registro->toArray(),
                    'proyecto' => Proyecto::query()->where('cotizacion_id', $id)->firstOrFail()->toArray(), 'repetido' => true];
                if ($registro->estado !== 'Enviada') throw new \RuntimeException('Envíe la cotización antes de aprobarla.', 409);
                $registro->estado = 'Aprobada'; $registro->aprobado_por = $actor['id']; $registro->fecha_aprobacion = gmdate('Y-m-d H:i:s'); $registro->save();
                DB::table('historial_cotizaciones')->insert(['cotizacion_id' => $id, 'usuario_id' => $actor['id'], 'estado_anterior' => 'Enviada', 'estado_nuevo' => 'Aprobada']);
                $proyecto = Proyecto::create(['cotizacion_id' => $id, 'nombre' => 'Proyecto ' . $id, 'estado' => 'Aprobada']);
                DB::table('historial_estados')->insert(['proyecto_id' => $proyecto->id, 'usuario_id' => $actor['id'], 'estado_nuevo' => 'Aprobada', 'motivo' => 'Aprobación de cotización.']);
                return ['cotizacion' => $registro->toArray(), 'proyecto' => $proyecto->fresh()->toArray(), 'repetido' => false];
            }
            $permitidos = ['Cotizacion' => ['Enviada', 'Rechazada'], 'Enviada' => ['Rechazada']];
            if (!in_array($estado, $permitidos[$registro->estado] ?? [], true)) throw new \RuntimeException('Transición de cotización no permitida.', 409);
            DB::table('historial_cotizaciones')->insert(['cotizacion_id' => $id, 'usuario_id' => $actor['id'], 'estado_anterior' => $registro->estado, 'estado_nuevo' => $estado]);
            $registro->estado = $estado; $registro->save();
            return ['cotizacion' => $registro->toArray()];
        });
    }
    public function eliminar(array $actor, int $id): void {
        AutorizacionService::roles($actor, 1);
        DB::connection()->transaction(function() use ($actor, $id) {
            $registro = (new CotizacionRepository())->find($id, $actor, true);
            if ($registro->estado !== 'Cotizacion') throw new \RuntimeException('Solo se elimina una cotización en borrador.', 409);
            $registro->delete();
        });
    }
}

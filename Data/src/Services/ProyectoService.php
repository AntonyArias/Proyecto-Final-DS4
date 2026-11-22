<?php
namespace App\Services;
use App\Models\Proyecto;
use App\Models\User;
use App\Repositories\ProyectoRepository;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;

class ProyectoService {
    public const TRANSICIONES = ['Aprobada' => 'En campo', 'En campo' => 'Informe entregado', 'Informe entregado' => 'Facturada'];
    public static function validarEstado(string $actual, string $nuevo): void {
        if ((self::TRANSICIONES[$actual] ?? null) !== $nuevo) throw new \RuntimeException('Transición de proyecto no permitida.', 409);
    }
    public function detalle(array $actor, int $id): array {
        $proyecto = (new ProyectoRepository())->find($id, $actor)->toArray();
        if ($actor['rol_id'] === 2) unset($proyecto['monto'], $proyecto['moneda']);
        $proyecto['tecnicos'] = DB::table('proyecto_tecnicos')->join('usuarios', 'usuarios.id', '=', 'proyecto_tecnicos.tecnico_id')
            ->where('proyecto_id', $id)->select('usuarios.id', 'usuarios.nombre_completo', 'usuarios.activo')->orderBy('usuarios.id')->get()->toArray();
        $proyecto['historial'] = DB::table('historial_estados')->where('proyecto_id', $id)->orderBy('id')->get()->toArray();
        return $proyecto;
    }
    public function asignar(array $actor, int $id, array $data): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $id, $data) {
            $proyecto = (new ProyectoRepository())->find($id, $actor, true);
            if ($proyecto->estado === 'Facturada') throw new \RuntimeException('No se asignan nuevos técnicos a proyectos facturados.', 409);
            $tecnico = User::query()->where('id', v::id($data['tecnico_id'] ?? null))->lockForUpdate()->first();
            if (!$tecnico || $tecnico->rol_id !== 2 || !$tecnico->activo) throw new \InvalidArgumentException('Técnico activo requerido.', 422);
            DB::table('proyecto_tecnicos')->insertOrIgnore(['proyecto_id' => $id, 'tecnico_id' => $tecnico->id, 'asignado_por' => $actor['id']]);
            return $this->detalle($actor, $id);
        });
    }
    public function estado(array $actor, int $id, array $data): array {
        AutorizacionService::roles($actor, 1);
        return DB::connection()->transaction(function() use ($actor, $id, $data) {
            $proyecto = (new ProyectoRepository())->find($id, $actor, true);
            $estado = v::texto($data, 'estado', 30);
            self::validarEstado($proyecto->estado, $estado);
            $update = ['estado' => $estado];
            if ($estado === 'En campo' && !DB::table('proyecto_tecnicos')->where('proyecto_id', $id)->exists()) throw new \RuntimeException('Asigne al menos un técnico antes de pasar a campo.', 409);
            if ($estado === 'Informe entregado') $update['informe_referencia'] = v::texto($data, 'informe_referencia', 500);
            if ($estado === 'Facturada') $update['factura_referencia'] = v::texto($data, 'factura_referencia', 150);
            DB::table('historial_estados')->insert(['proyecto_id' => $id, 'usuario_id' => $actor['id'],
                'estado_anterior' => $proyecto->estado, 'estado_nuevo' => $estado, 'motivo' => v::texto($data, 'motivo', 500, false)]);
            Proyecto::query()->where('id', $id)->update($update);
            return $this->detalle($actor, $id);
        });
    }
}

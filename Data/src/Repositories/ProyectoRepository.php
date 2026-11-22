<?php
namespace App\Repositories;
use App\Models\Proyecto as ProyectoModel;

class ProyectoRepository {
    public function query(array $actor) {
        $query = ProyectoModel::query()->join('cotizaciones', 'cotizaciones.id', '=', 'proyectos.cotizacion_id')
            ->join('clientes', 'clientes.id', '=', 'cotizaciones.cliente_id');
        if ($actor['rol_id'] === 3) $query->where('clientes.usuario_id', $actor['id']);
        if ($actor['rol_id'] === 2) $query->whereExists(function($q) use ($actor) {
            $q->selectRaw('1')->from('proyecto_tecnicos')->whereColumn('proyecto_tecnicos.proyecto_id', 'proyectos.id')->where('tecnico_id', $actor['id']);
        });
        return $query->select('proyectos.*', 'cotizaciones.cliente_id', 'cotizaciones.servicio_id', 'cotizaciones.monto', 'cotizaciones.moneda', 'clientes.nombre_completo as cliente');
    }
    public function find(int $id, array $actor, bool $lock = false): ProyectoModel {
        $query = $this->query($actor)->where('proyectos.id', $id);
        if ($lock) $query->lockForUpdate();
        $registro = $query->first();
        if (!$registro) throw new \RuntimeException('Proyecto no encontrado.', 404);
        return $registro;
    }
}

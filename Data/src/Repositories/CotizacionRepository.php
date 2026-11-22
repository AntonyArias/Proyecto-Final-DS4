<?php
namespace App\Repositories;
use App\Models\Cotizacion as CotizacionModel;

class CotizacionRepository {
    public function query(array $actor) {
        $query = CotizacionModel::query()->join('clientes', 'clientes.id', '=', 'cotizaciones.cliente_id');
        if ($actor['rol_id'] === 3) $query->where('clientes.usuario_id', $actor['id']);
        return $query->select('cotizaciones.*');
    }
    public function find(int $id, array $actor, bool $lock = false): CotizacionModel {
        $query = $this->query($actor)->where('cotizaciones.id', $id);
        if ($lock) $query->lockForUpdate();
        $registro = $query->first();
        if (!$registro) throw new \RuntimeException('Cotización no encontrada.', 404);
        return $registro;
    }
}

<?php
namespace App\Repositories;
use App\Models\Pago as PagoModel;
class PagoRepository {
    public function query(array $actor) {
        return PagoModel::query()->whereIn('proyecto_id', (new ProyectoRepository())->query($actor)->select('proyectos.id'));
    }
    public function find(int $id, array $actor): PagoModel {
        $pago = $this->query($actor)->where('pagos.id', $id)->first();
        if (!$pago) throw new \RuntimeException('Pago no encontrado.', 404);
        return $pago;
    }
}

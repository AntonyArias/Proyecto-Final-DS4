<?php
namespace App\Repositories;
use App\Models\Servicio as ServicioModel;

class ServicioRepository {
    public function query(array $params = []) {
        $query = ServicioModel::query();
        if (isset($params['nombre']) && is_string($params['nombre']) && $params['nombre'] !== '') $query->where('nombre', 'LIKE', '%' . $params['nombre'] . '%');
        if (isset($params['activo'])) {
            if (!in_array((string) $params['activo'], ['0', '1'], true)) throw new \InvalidArgumentException('activo debe ser 0 o 1.', 422);
            $query->where('activo', (int) $params['activo']);
        }
        return $query;
    }
    public function find(int $id, bool $lock = false): ServicioModel {
        $query = ServicioModel::query()->where('id', $id);
        if ($lock) $query->lockForUpdate();
        $registro = $query->first();
        if ($registro === null) throw new \RuntimeException('Servicio no encontrado.', 404);
        return $registro;
    }
    public function create(array $data): ServicioModel {
        return ServicioModel::create($data);
    }
}

<?php
namespace App\Repositories;
use App\Models\Cliente as ClienteModel;

class ClienteRepository {
    public function query(array $params = []) {
        $query = ClienteModel::query();
        if (isset($params['nombre']) && is_string($params['nombre']) && $params['nombre'] !== '') $query->where('nombre_completo', 'LIKE', '%' . $params['nombre'] . '%');
        if (isset($params['cedula']) && is_string($params['cedula']) && $params['cedula'] !== '') $query->where('cedula', 'LIKE', '%' . $params['cedula'] . '%');
        if (isset($params['activo'])) {
            if (!in_array((string) $params['activo'], ['0', '1'], true)) throw new \InvalidArgumentException('activo debe ser 0 o 1.', 422);
            $query->where('activo', (int) $params['activo']);
        }
        return $query;
    }
    public function find(int $id, bool $lock = false): ClienteModel {
        $query = ClienteModel::query()->where('id', $id);
        if ($lock) $query->lockForUpdate();
        $registro = $query->first();
        if ($registro === null) throw new \RuntimeException('Cliente no encontrado.', 404);
        return $registro;
    }
    public function create(array $data): ClienteModel {
        return ClienteModel::create($data);
    }
}

<?php
namespace App\Repositories;
use App\Models\User as UsuarioModel;

class UsuarioRepository {
    public function query(array $params = []) {
        $query = UsuarioModel::query();
        if (isset($params['nombre']) && is_string($params['nombre']) && $params['nombre'] !== '') $query->where('username', 'LIKE', '%' . $params['nombre'] . '%');
        if (isset($params['activo'])) {
            if (!in_array((string) $params['activo'], ['0', '1'], true)) throw new \InvalidArgumentException('activo debe ser 0 o 1.', 422);
            $query->where('activo', (int) $params['activo']);
        }
        return $query;
    }
    public function find(int $id, bool $lock = false): UsuarioModel {
        $query = UsuarioModel::query()->where('id', $id);
        if ($lock) $query->lockForUpdate();
        $registro = $query->first();
        if ($registro === null) throw new \RuntimeException('Usuario no encontrado.', 404);
        return $registro;
    }
    public function create(array $data): UsuarioModel {
        return UsuarioModel::create($data);
    }
}

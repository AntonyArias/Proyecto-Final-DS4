<?php
namespace App\Repositories;
use App\Models\Visita as VisitaModel;
class VisitaRepository {
    public function query(array $actor) {
        return VisitaModel::query()->whereIn('proyecto_id', (new ProyectoRepository())->query($actor)->select('proyectos.id'));
    }
}

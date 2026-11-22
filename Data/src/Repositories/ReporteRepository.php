<?php
namespace App\Repositories;
use App\Services\ValidacionService as v;
use Illuminate\Database\Capsule\Manager as DB;

class ReporteRepository {
    private function base(array $actor, array $params) {
        $permitidos = (new ProyectoRepository())->query($actor)->select('proyectos.id');
        $query = DB::table('proyectos')->join('cotizaciones', 'cotizaciones.id', '=', 'proyectos.cotizacion_id')
            ->join('clientes', 'clientes.id', '=', 'cotizaciones.cliente_id')->whereIn('proyectos.id', $permitidos);
        foreach (['proyecto_id' => 'proyectos.id', 'cliente_id' => 'cotizaciones.cliente_id', 'servicio_id' => 'cotizaciones.servicio_id'] as $key => $column) {
            if (isset($params[$key])) $query->where($column, v::id($params[$key]));
        }
        return $query;
    }
    public static function fechas($query, array $params, string $column): void {
        $desde = isset($params['desde']) ? v::fecha($params['desde']) : null;
        $hasta = isset($params['hasta']) ? v::fecha($params['hasta']) : null;
        if ($desde && $hasta && $desde > $hasta) throw new \InvalidArgumentException('desde debe ser anterior o igual a hasta.', 422);
        if ($desde) $query->where($column, '>=', $desde);
        if ($hasta) $query->where($column, '<=', $hasta);
    }
    public function ingresos(array $actor, array $params) {
        $query = $this->base($actor, $params)->join('pagos', 'pagos.proyecto_id', '=', 'proyectos.id')
            ->join('servicios', 'servicios.id', '=', 'cotizaciones.servicio_id')->where('pagos.estado', 'Recibido');
        self::fechas($query, $params, 'pagos.fecha_pago');
        return $query->select('cotizaciones.cliente_id', 'clientes.nombre_completo as cliente', 'cotizaciones.servicio_id', 'servicios.nombre as servicio', 'pagos.moneda')
            ->selectRaw('SUM(pagos.monto) as total_recibido, COUNT(pagos.id) as cantidad_pagos')
            ->groupBy('cotizaciones.cliente_id', 'clientes.nombre_completo', 'cotizaciones.servicio_id', 'servicios.nombre', 'pagos.moneda')
            ->orderBy('cotizaciones.cliente_id')->orderBy('cotizaciones.servicio_id');
    }
    public function estados(array $actor, array $params) {
        return $this->base($actor, $params)->select('proyectos.estado')->selectRaw('COUNT(proyectos.id) as total')->groupBy('proyectos.estado')->orderBy('proyectos.estado');
    }
    public function visitas(array $actor, array $params) {
        $query = $this->base($actor, $params)->join('visitas', 'visitas.proyecto_id', '=', 'proyectos.id');
        self::fechas($query, $params, 'visitas.fecha_captura');
        return $query->select('proyectos.id as proyecto_id', 'proyectos.nombre as proyecto')->selectRaw('COUNT(visitas.id) as total_visitas')
            ->groupBy('proyectos.id', 'proyectos.nombre')->orderBy('proyectos.id');
    }
    public function mapa(array $actor, array $params) {
        $query = $this->base($actor, $params)->join('visitas', 'visitas.proyecto_id', '=', 'proyectos.id');
        self::fechas($query, $params, 'visitas.fecha_captura');
        return $query->select('visitas.id', 'visitas.uuid', 'proyectos.id as proyecto_id', 'proyectos.nombre as proyecto', 'visitas.fecha_captura', 'visitas.latitud', 'visitas.longitud')
            ->orderBy('visitas.fecha_captura')->orderBy('visitas.id');
    }
}

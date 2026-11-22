<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model {
    protected $table = 'cotizaciones';
    public $timestamps = false;
    protected $fillable = ['solicitud_id', 'cliente_id', 'servicio_id', 'descripcion', 'monto', 'moneda', 'estado', 'creado_por', 'aprobado_por', 'fecha_registro', 'fecha_aprobacion'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'solicitud_id' => 'integer',
        'cliente_id' => 'integer',
        'servicio_id' => 'integer',
    ];

}

<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model {
    protected $table = 'proyectos';
    public $timestamps = false;
    protected $fillable = ['cotizacion_id', 'nombre', 'estado', 'informe_referencia', 'factura_referencia', 'fecha_registro'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'cotizacion_id' => 'integer',
    ];

}

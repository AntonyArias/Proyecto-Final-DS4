<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model {
    protected $table = 'pagos';
    public $timestamps = false;
    protected $fillable = ['proyecto_id', 'tipo', 'monto', 'moneda', 'estado', 'fecha_pago', 'referencia', 'registrado_por', 'fecha_registro'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'proyecto_id' => 'integer',
    ];

}

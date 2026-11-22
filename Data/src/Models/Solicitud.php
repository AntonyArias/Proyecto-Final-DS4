<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model {
    protected $table = 'solicitudes';
    public $timestamps = false;
    protected $fillable = ['servicio_id', 'nombre_completo', 'email', 'telefono', 'descripcion', 'estado', 'fecha_registro'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'servicio_id' => 'integer',
    ];

}

<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model {
    protected $table = 'visitas';
    public $timestamps = false;
    protected $fillable = ['uuid', 'proyecto_id', 'tecnico_id', 'fecha_captura', 'observaciones', 'latitud', 'longitud', 'huella', 'fecha_registro'];
    protected $hidden = ['huella'];
    protected $casts = [
        'id' => 'integer',
        'proyecto_id' => 'integer',
        'tecnico_id' => 'integer',
    ];

}

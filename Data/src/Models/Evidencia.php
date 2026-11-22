<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidencia extends Model {
    protected $table = 'evidencias';
    public $timestamps = false;
    protected $fillable = ['visita_id', 'uuid', 'nombre', 'mime', 'bytes', 'sha256', 'ruta', 'fecha_registro'];
    protected $hidden = ['ruta'];
    protected $casts = [
        'id' => 'integer',
        'visita_id' => 'integer',
    ];

}

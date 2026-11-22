<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model {
    protected $table = 'servicios';
    public $timestamps = false;
    protected $fillable = ['nombre', 'descripcion', 'activo'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'activo' => 'boolean',
    ];

}

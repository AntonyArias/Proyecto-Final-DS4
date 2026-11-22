<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $table = 'usuarios';
    public $timestamps = false;
    protected $fillable = ['username', 'password', 'nombre_completo', 'email', 'rol_id', 'activo', 'tkRef', 'sesion_id'];
    protected $hidden = ['password', 'tkRef', 'sesion_id'];
    protected $casts = [
        'id' => 'integer',
        'rol_id' => 'integer',
        'activo' => 'boolean',
    ];

}

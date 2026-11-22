<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model {
    protected $table = 'clientes';
    public $timestamps = false;
    protected $fillable = ['usuario_id', 'cedula', 'nombre_completo', 'email', 'telefono', 'direccion', 'activo'];
    protected $hidden = [];
    protected $casts = [
        'id' => 'integer',
        'usuario_id' => 'integer',
        'activo' => 'boolean',
    ];

    public function usuario() {
        return $this->belongsTo(User::class, 'usuario_id');
    }

}

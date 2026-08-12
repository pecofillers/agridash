<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $ID_Usuario
 * @property string $Username
 * @property string|null $Nombre
 * @property string|null $Apellidos
 * @property string|null $Telefono
 * @property string|null $Correo
 * @property string $Password_Hash
 * @property int $ID_Rol
 * @property string $Estado
 * @property int $Intentos_Fallidos
 * @property \Illuminate\Support\Carbon|null $Bloqueado_Hasta
 * @property-read Rol|null $rol
 */
class Usuario extends Authenticatable
{
    use HasFactory;

protected $table = 'dim_usuarios';
    protected $primaryKey = 'ID_Usuario';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Username', 'Nombre', 'Apellidos', 'Telefono', 'Correo',
        'Password_Hash', 'ID_Rol', 'ID_Grupo', 'Estado', 'Intentos_Fallidos', 'Bloqueado_Hasta',
    ];

    protected $hidden = ['Password_Hash'];

    protected $casts = [
        'Bloqueado_Hasta' => 'datetime',
        'Intentos_Fallidos' => 'integer',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'ID_Rol', 'ID_Rol');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'ID_Grupo', 'ID_Grupo');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->Nombre ?? '') . ' ' . ($this->Apellidos ?? '')) ?: ($this->Username ?? 'Sin nombre');
    }

    public function getAuthPassword()
    {
        return $this->Password_Hash;
    }
}

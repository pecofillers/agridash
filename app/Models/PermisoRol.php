<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoRol extends Model
{
    use HasFactory;

    protected $table = 'dim_permisos_rol';
    protected $primaryKey = 'ID_Permiso';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

protected $fillable = [
        'ID_Rol', 'Modulo', 'Submodulo',
        'Permiso_Ver',
    ];

    protected $casts = [
        'Permiso_Ver' => 'boolean',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'ID_Rol', 'ID_Rol');
    }
}

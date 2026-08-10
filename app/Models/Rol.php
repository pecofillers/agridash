<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'dim_roles';
    protected $primaryKey = 'ID_Rol';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['Nombre_Rol', 'Descripcion'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'ID_Rol', 'ID_Rol');
    }

    public function permisos()
    {
        return $this->hasMany(PermisoRol::class, 'ID_Rol', 'ID_Rol');
    }
}

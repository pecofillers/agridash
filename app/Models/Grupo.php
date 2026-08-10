<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'dim_grupos';
    protected $primaryKey = 'ID_Grupo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['Nombre_Grupo', 'Supervisor_Asignado'];

    public function colaboradores()
    {
        return $this->hasMany(Colaborador::class, 'ID_Grupo', 'ID_Grupo');
    }
}

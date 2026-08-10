<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    use HasFactory;

    protected $table = 'dim_colaboradores';
    protected $primaryKey = 'ID_Colaborador';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['Nombre_Colaborador', 'ID_Grupo', 'Estado'];

public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'ID_Grupo', 'ID_Grupo');
    }

    public function scopeActive($query)
    {
        return $query->where('Estado', 'ACTIVO');
    }
}

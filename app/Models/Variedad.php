<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variedad extends Model
{
    use HasFactory;

    protected $table = 'dim_variedades';
    protected $primaryKey = 'ID_Variedad';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['Nombre_Variedad', 'Color', 'Ciclo_Dias'];

    public function siembras()
    {
        return $this->hasMany(Siembra::class, 'ID_Variedad', 'ID_Variedad');
    }
}

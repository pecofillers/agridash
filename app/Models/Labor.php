<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Labor extends Model
{
    protected $table = 'dim_labores';
    protected $primaryKey = 'ID_Labor';
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Nombre_Labor', 
        'Unidad_Medida', 
        'Umbral_Verde', 
        'Umbral_Naranja'
    ];
}
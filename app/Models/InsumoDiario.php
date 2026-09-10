<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoDiario extends Model
{
    protected $table = 'insumos_diarios';

    protected $fillable = [
        'fecha',
        'amortiguador_medida_visual',
        'amortiguador_medida_registro',
        'amortiguador_diferencia_visual',
        'amortiguador_diferencia_registro',
        'agrofeed_medida_visual',
        'agrofeed_medida_registro',
        'agrofeed_diferencia_visual',
        'agrofeed_diferencia_registro',
        'agua_lectura',
        'agua_diferencia_registro',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
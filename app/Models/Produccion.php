<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    use HasFactory;

protected $table = 'fact_produccion';
    protected $primaryKey = 'ID_Produccion';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Bloque', 'Nave', 'Cama', 'Semana', 'Anio',
        'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes',
        'Sabado', 'Domingo', 'Bajas', 'Total',
    ];
}

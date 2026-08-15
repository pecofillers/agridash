<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siembra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dim_siembras';
    protected $primaryKey = 'ID_Siembra';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'ID_Ubicacion', 'ID_Variedad', 'Fecha_Siembra',
        'Fecha_Fin', 'Cantidad_Plantas', 'Metros_Lineales', 'Densidad_Plantacion',
        'Estado_Siembra', 'Ciclo_Actual', 'Fecha_Pinch', 'Fecha_Hormona', 'Fecha_Erradicacion'
    ];

    protected $casts = [
        'Fecha_Siembra' => 'date',
        'Fecha_Fin' => 'date',
        'Fecha_Pinch' => 'date',
        'Fecha_Hormona' => 'date',
        'Fecha_Erradicacion' => 'date',
        'Metros_Lineales' => 'decimal:2',
        'Densidad_Plantacion' => 'decimal:2',
    ];

    public function variedad()
    {
        return $this->belongsTo(Variedad::class, 'ID_Variedad', 'ID_Variedad');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ID_Ubicacion', 'ID_Ubicacion');
    }
}
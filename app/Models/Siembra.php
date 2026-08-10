<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siembra extends Model
{
    use HasFactory;

    protected $table = 'dim_siembras';
    protected $primaryKey = 'ID_Siembra';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Bloque', 'Nave', 'Cama', 'ID_Variedad', 'Fecha_Siembra',
        'Fecha_Fin', 'Cantidad_Plantas', 'Metros_Lineales', 'Densidad_Plantacion',
    ];

    protected $casts = [
        'Fecha_Siembra' => 'date',
        'Fecha_Fin' => 'date',
        'Metros_Lineales' => 'decimal:2',
        'Densidad_Plantacion' => 'decimal:2',
    ];

    public function variedad()
    {
        return $this->belongsTo(Variedad::class, 'ID_Variedad', 'ID_Variedad');
    }
}

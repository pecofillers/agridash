<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RendimientoDetalle extends Model
{
    protected $table = 'fact_rendimiento_detalles';
    protected $primaryKey = 'ID_Detalle';
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'ID_Rendimiento',
        'Nombre_Variante',
        'Cantidad',
    ];

    public function rendimientoCabecera()
    {
        return $this->belongsTo(RendimientoLabor::class, 'ID_Rendimiento', 'ID_Rendimiento');
    }
}
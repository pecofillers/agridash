<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendimientoLabor extends Model
{
    use HasFactory;

    protected $table = 'fact_rendimientos_labor';
    protected $primaryKey = 'ID_Rendimiento';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Fecha', 
        'ID_Colaborador', 
        'Nombre_Colaborador', 
        'ID_Grupo', 
        'Supervisor',
        'Tipo_Labor', 
        'Unidad_Medida', 
        'Hora_Inicio', 
        'Hora_Fin',
        'Horas_Trabajadas', 
        'Cantidad', 
        'Rendimiento_Hora',
    ];

    protected $casts = [
        'Fecha' => 'date',
        'Hora_Inicio' => 'string',
        'Hora_Fin' => 'string',
        'Horas_Trabajadas' => 'decimal:2',
        'Cantidad' => 'decimal:2',
        'Rendimiento_Hora' => 'decimal:2',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'ID_Colaborador', 'ID_Colaborador');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'ID_Grupo', 'ID_Grupo');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RendimientoLabor extends Model
{
    protected $table = 'fact_rendimientos_labor';
    protected $primaryKey = 'ID_Rendimiento';
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Fecha', 
        'ID_Usuario',     // <--- Cambiado de ID_Colaborador a ID_Usuario
        'ID_Grupo', 
        'ID_Labor', 
        'ID_Ubicacion', 
        'ID_Siembra',
        'Hora_Inicio', 
        'Hora_Fin',
        'Horas_Trabajadas', 
        'Cantidad', 
        'Rendimiento_Hora',
    ];

    // Ahora la relación se llama "usuario"
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }

    public function labor()
    {
        return $this->belongsTo(Labor::class, 'ID_Labor', 'ID_Labor');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'ID_Grupo', 'ID_Grupo');
    }
}
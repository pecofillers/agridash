<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    // Nombre exacto de la tabla en la base de datos
    protected $table = 'planillas';
    
    // Permitir guardar datos en estas columnas
    protected $fillable = ['bloque', 'url_onedrive'];
}
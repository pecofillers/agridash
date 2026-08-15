<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produccion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fact_produccion';
    protected $primaryKey = 'ID_Produccion';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    // Quitamos los días de la semana, dejamos solo Bajas y Total
    protected $fillable = [
        'ID_Ubicacion', 'Semana', 'Anio',
        'Bajas', 'Total',
    ];

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ID_Ubicacion', 'ID_Ubicacion');
    }
}
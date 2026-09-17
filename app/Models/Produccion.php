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

    protected $fillable = [
        'ID_Ubicacion',
        'ID_Siembra',
        'Semana',
        'Anio',
        'Bajas',
        'Total',
    ];

    public function ubicacion()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'ID_Ubicacion',
            'ID_Ubicacion'
        );
    }

    public function siembra()
    {
        return $this->belongsTo(
            Siembra::class,
            'ID_Siembra',
            'ID_Siembra'
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CicloSiembra extends Model
{
    use HasFactory;

    protected $table = 'dim_ciclos_siembras';
    protected $primaryKey = 'ID_Ciclo_Siembra';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'ID_Bloque',
        'ID_Variedad',
        'Fecha_Siembra',
        'Estado',
    ];

    protected $casts = [
        'Fecha_Siembra' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bloque()
    {
        return $this->belongsTo(
            Bloque::class,
            'ID_Bloque',
            'ID_Bloque'
        );
    }

    public function variedad()
    {
        return $this->belongsTo(
            Variedad::class,
            'ID_Variedad',
            'ID_Variedad'
        );
    }

    public function siembras()
    {
        return $this->hasMany(
            Siembra::class,
            'ID_Ciclo_Siembra',
            'ID_Ciclo_Siembra'
        );
    }
}
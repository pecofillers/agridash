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
    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'ID_Ubicacion',
        'ID_Variedad',
        'ID_Ciclo_Siembra',
        'Fecha_Siembra',
        'Cantidad_Plantas',
        'Estado_Siembra',
        'Ciclo_Actual',
        'Fecha_Pinch',
        'Fecha_Hormona',
        'Fecha_Erradicacion',
    ];

    protected $casts = [
        'Fecha_Siembra' => 'date',
        'Fecha_Pinch' => 'date',
        'Fecha_Hormona' => 'date',
        'Fecha_Erradicacion' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Siembra $siembra) {
            if ($siembra->exists && !$siembra->isDirty(['ID_Ubicacion', 'ID_Variedad', 'Fecha_Siembra', 'ID_Ciclo_Siembra']) && $siembra->ID_Ciclo_Siembra) {
                return;
            }
            $ubicacion = Ubicacion::find($siembra->ID_Ubicacion);
            $idBloque = $ubicacion?->ID_Bloque;
            if (!$idBloque && $ubicacion) {
                $idBloque = Bloque::where('Codigo_Bloque', trim($ubicacion->Bloque))->value('ID_Bloque');
            }
            if (!$idBloque || !$siembra->ID_Variedad || !$siembra->Fecha_Siembra) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ID_Ciclo_Siembra' => 'La siembra necesita una ubicación con bloque, variedad y fecha para asignar su ciclo.',
                ]);
            }
            $ciclo = CicloSiembra::whereDate('Fecha_Siembra', $siembra->Fecha_Siembra->format('Y-m-d'))
                ->firstOrCreate([
                    'ID_Bloque' => $idBloque,
                    'ID_Variedad' => $siembra->ID_Variedad,
                ], [
                    'Fecha_Siembra' => $siembra->Fecha_Siembra->format('Y-m-d'),
                    'Estado' => 'ACTIVO',
                ]);
            $siembra->ID_Ciclo_Siembra = $ciclo->ID_Ciclo_Siembra;
            $siembra->unsetRelation('ciclo');
        });
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class, 'ID_Siembra', 'ID_Siembra');
    }

    public function densidadPlantacion()
    {
        if (
            $this->ubicacion &&
            $this->ubicacion->Metros_Lineales > 0
        ) {
            return round(
                $this->Cantidad_Plantas /
                $this->ubicacion->Metros_Lineales,
                2
            );
        }

        return 0;
    }

    public function variedad()
    {
        return $this->belongsTo(
            Variedad::class,
            'ID_Variedad',
            'ID_Variedad'
        );
    }

    public function ubicacion()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'ID_Ubicacion',
            'ID_Ubicacion'
        );
    }

    public function ciclo()
    {
        return $this->belongsTo(
            CicloSiembra::class,
            'ID_Ciclo_Siembra',
            'ID_Ciclo_Siembra'
        );
    }
}
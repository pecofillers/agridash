<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ubicacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dim_ubicaciones';
    protected $primaryKey = 'ID_Ubicacion';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['ID_Bloque', 'ID_Ubicacion', 'Bloque', 'Nave', 'Cama', 'Estado', 'Metros_Lineales', 'Cuadros'];

    protected $casts = [
        'Metros_Lineales' => 'decimal:2',
        'Cuadros'         => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Ubicacion $ubicacion) {
            if (!$ubicacion->exists || $ubicacion->isDirty('Bloque') || !$ubicacion->ID_Bloque) {
                $codigo = trim((string) $ubicacion->Bloque);
                if ($codigo === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['Bloque' => 'Selecciona un bloque.']);
                }
                $bloque = Bloque::firstOrCreate(
                    ['Codigo_Bloque' => $codigo],
                    ['Nombre_Bloque' => 'Bloque '.$codigo, 'Estado' => 'ACTIVO']
                );
                $ubicacion->Bloque = $codigo;
                $ubicacion->ID_Bloque = $bloque->ID_Bloque;
                $ubicacion->unsetRelation('bloque');
            }
        });
    }

    public function siembras()
    {
        return $this->hasMany(Siembra::class, 'ID_Ubicacion', 'ID_Ubicacion');
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class, 'ID_Ubicacion', 'ID_Ubicacion');
    }

    public static function bloques()
    {
        return self::query()->select('Bloque')->distinct()->orderBy('Bloque')->pluck('Bloque');
    }

    public static function naves($bloque)
    {
        return self::query()->where('Bloque', $bloque)->select('Nave')->distinct()->orderBy('Nave')->pluck('Nave');
    }

    public static function camas($bloque, $nave)
    {
        return self::query()->where('Bloque', $bloque)->where('Nave', $nave)->select('Cama')->distinct()->orderBy('Cama')->pluck('Cama');
    }

    public function bloque()
    {
        return $this->belongsTo(
            Bloque::class,
            'ID_Bloque',
            'ID_Bloque'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'dim_ubicaciones';
    protected $primaryKey = 'ID_Ubicacion';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['Bloque', 'Nave', 'Cama', 'Estado'];

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
}

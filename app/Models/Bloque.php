<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloque extends Model
{
    use HasFactory;

    protected $table = 'dim_bloques';

    protected $primaryKey = 'ID_Bloque';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'Codigo_Bloque',
        'Nombre_Bloque',
        'Descripcion',
        'url_onedrive',
        'Estado',
    ];

    /**
     * Ciclos de siembra pertenecientes al bloque.
     */
    public function ciclosSiembra()
    {
        return $this->hasMany(
            CicloSiembra::class,
            'ID_Bloque',
            'ID_Bloque'
        );
    }

    /**
     * Ubicaciones/camas pertenecientes al bloque.
     */
    public function ubicaciones()
    {
        return $this->hasMany(
            Ubicacion::class,
            'ID_Bloque',
            'ID_Bloque'
        );
    }
}
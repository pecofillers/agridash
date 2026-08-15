<?php

namespace App\Imports;

use App\Models\Produccion;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProduccionImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Ignorar filas vacías o sin los datos mínimos
        if (!isset($row['id_ubicacion']) || !isset($row['semana']) || !isset($row['anio'])) {
            return null;
        }

        // Buscar si ya existe para actualizar, o crear uno nuevo
        return Produccion::updateOrCreate(
            [
                'ID_Ubicacion' => $row['id_ubicacion'],
                'Semana'       => $row['semana'],
                'Anio'         => $row['anio'],
            ],
            [
                'Bajas' => $row['bajas'] ?? 0,
                'Total' => $row['total'] ?? 0,
            ]
        );
    }
}
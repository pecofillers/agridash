<?php

namespace App\Exports;

use App\Models\Produccion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProduccionExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Descargamos solo las columnas necesarias
        return Produccion::select('ID_Ubicacion', 'Semana', 'Anio', 'Bajas', 'Total')->get();
    }

    public function headings(): array
    {
        // Estas cabeceras son importantes porque así mismo debe subirse el Excel
        return ['id_ubicacion', 'semana', 'anio', 'bajas', 'total'];
    }
}
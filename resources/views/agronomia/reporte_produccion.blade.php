@extends('layouts.app')

@section('title','Reporte Producción')

@push('styles')
<style>
    .reporte-card {
        border: 1px solid #dfe7e2;
        border-radius: 10px;
        overflow: hidden
    }

    .reporte-header {
        background: #f5f8f6;
        border-bottom: 1px solid #dfe7e2;
        padding: 12px 16px
    }

    .reporte-header h5 {
        margin: 0;
        font-weight: 700
    }

    .tabla-produccion {
        font-size: .82rem;
        white-space: nowrap
    }

    .tabla-produccion thead th {
        background: #2e7d32;
        color: #fff;
        text-align: center;
        vertical-align: middle
    }

    .tabla-produccion tbody td,
    .tabla-produccion tbody th {
        border-color: #d0d8d3;
        padding: 8px
    }

    .total-row {
        background: #f1f8f3;
        font-weight: bold
    }

    .indicador {
        background: #e7f3ea;
        font-weight: bold
    }

    .filtro-label {
        font-size: .75rem;
        font-weight: bold;
        color: #6c757d;
        text-transform: uppercase
    }
</style>
@endpush

@section('content')

<h2 class="page-title mb-4">📊 REPORTE DE PRODUCCIÓN</h2>

@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('agronomia.reporte-produccion') }}" class="card p-3 mb-4 shadow-sm border-0 bg-light">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="filtro-label">Año</label>
            <select name="anio" class="form-select form-select-sm">
                <option value="">Seleccione</option>
                @foreach($años as $anio)
                <option value="{{ $anio }}" @selected(request('anio')==$anio)>
                    {{ $anio }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="filtro-label">Mes</label>
            <select name="mes" class="form-select form-select-sm">
                <option value="">Seleccione</option>
                @foreach($meses as $numero=>$mes)
                <option value="{{ $numero }}" @selected(request('mes')==$numero)>
                    {{ $mes }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="filtro-label">Variedad</label>
            <select name="variedad" class="form-select form-select-sm">
                <option value="">Todas</option>
                @foreach($variedades as $variedad)
                <option value="{{ $variedad->ID_Variedad }}" @selected(request('variedad')==$variedad->ID_Variedad)>
                    {{ $variedad->Nombre_Variedad }}
                    @if($variedad->Color)
                    - {{ $variedad->Color }}
                    @endif
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="filtro-label">Bloque</label>
            <select name="bloque" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach($bloques as $bloque)
                <option value="{{ $bloque->ID_Bloque }}" @selected(request('bloque')==$bloque->ID_Bloque)>
                    {{ $bloque->Codigo_Bloque }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100" type="submit">
                🔍
            </button>
        </div>
    </div>
</form>

@if($datos->isNotEmpty())

<div class="card shadow-sm reporte-card">

    <div class="reporte-header">
        <h5>Producción mensual</h5>
        <small class="text-muted">
            {{ request('anio') }} - {{ $meses[request('mes')] ?? '' }}
        </small>
    </div>

    <div class="table-responsive">

        <table class="table table-bordered mb-0 tabla-produccion">

            @php
            $semanasMes = [];
            $inicio = \Carbon\Carbon::create(request('anio'), request('mes'), 1);
            $fin = $inicio->copy()->endOfMonth();
            while ($inicio <= $fin) {
                $semana = $inicio->weekOfYear;
                if (!in_array($semana, $semanasMes)) {
                    $semanasMes[] = $semana;
                }
                $inicio->addDay();
            }
            @endphp

            <thead>
                <tr>
                    <th>Bloque</th>
                    <th>Camas</th>
                    <th>Plantas</th>
                    @foreach($semanasMes as $indice => $semana)
                    <th>Semana {{ $indice + 1 }}</th>
                    @endforeach
                    <th>Tallos Mes</th>
                    <th>Tallos Planta Mes</th>
                </tr>
            </thead>

            <tbody>

                @php
                $totalPlantas=0;
                $totalProduccion=0;
                @endphp

                @foreach($datos as $fila)

                @php
                $totalPlantas += $fila['plantas'];
                $totalProduccion += $fila['total'];
                @endphp

                <tr>
                    <td class="fw-bold">{{ $fila['bloque'] }}</td>
                    <td class="text-center">{{ number_format($fila['camas']) }}</td>
                    <td class="text-end">{{ number_format($fila['plantas'],0,',','.') }}</td>

                    @foreach($semanasMes as $semana)
                        <td class="text-end">
                        {{ number_format(
                            $fila['semanas'][$semana] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                        </td>
                    @endforeach

                        <td class="text-end fw-bold">{{ number_format($fila['total'],0,',','.') }}</td>
                        <td class="text-end indicador">{{ number_format($fila['plantas']>0 ? $fila['total']/$fila['plantas']:0,2,',','.') }}</td>
                </tr>

                @endforeach

                <tr class="total-row">

                    <th>TOTAL</th>
                    <th></th>
                    <th class="text-end">{{ number_format($totalPlantas,0,',','.') }}</th>

                    @foreach($semanasMes as $semana)
                        <th></th>
                    @endforeach

                        <th class="text-end">{{ number_format($totalProduccion,0,',','.') }}</th>

                        <th>{{ number_format($totalPlantas>0?$totalProduccion/$totalPlantas:0,2,',','.') }}</th>
                </tr>
            </tbody>
        </table>

        <h5 class="mt-4">
            🌱 Siembras del periodo
        </h5>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Bloque</th>
                    <th>Camas</th>
                    <th>Fecha Siembra</th>
                    <th>Plantas</th>
                    @foreach($semanasMes as $indice => $semana)
                    <th>Semana {{ $indice + 1 }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>

                @foreach($datos as $fila)

                @foreach($fila['siembras'] as $siembra)

                <tr>

                    <td>
                        {{ $siembra['bloque'] }}
                    </td>

                    <td class="text-center">
                        {{ $siembra['camas'] }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($siembra['fecha_siembra'])->format('Y-m-d') }}
                    </td>

                    <td class="text-end">
                        {{ number_format($siembra['plantas'],0,',','.') }}
                    </td>


                    @foreach($semanasMes as $semana)

                        <td class="text-end">
                        {{ number_format(
                            $siembra['semanas'][$semana] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                        </td>

                    @endforeach


                        <td class="text-end fw-bold">

                            {{ number_format(
                                $siembra['total'],
                                0,
                                ',',
                                '.'
                            ) }}

                        </td>


                </tr>

                @endforeach

                @endforeach

            </tbody>
        </table>
    </div>
</div>

@elseif(request()->has('anio'))

<div class="alert alert-warning">
    No se encontraron registros con los filtros seleccionados.
</div>

@endif

@endsection
@extends('layouts.app')

@section('title', 'Histórico Productivo')

@push('styles')
<style>
    .table-excel {
        font-size: .82rem;
    }

    .table-excel th {
        background: #2e7d32 !important;
        color: white;
        text-align: center;
        vertical-align: middle;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .table-excel td {
        vertical-align: middle;
    }

    .sticky-bloque,
    .sticky-nave,
    .sticky-cama {
        position: sticky;
        background: #fff;
        z-index: 10;
    }

    .sticky-bloque {
        left: 0;
        min-width: 70px;
    }

    .sticky-nave {
        left: 70px;
        min-width: 70px;
    }

    .sticky-cama {
        left: 140px;
        min-width: 70px;
        border-right: 2px solid #999;
    }

    thead .sticky-bloque,
    thead .sticky-nave,
    thead .sticky-cama {
        background: #2e7d32 !important;
        color: white;
        z-index: 30;
    }

    .scroll-table {
        max-height: 75vh;
        overflow: auto;
    }
</style>
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="container-fluid py-3">

    <div class="mb-3">
        <h4 class="mb-0">
            <i class="bi bi-flower3"></i>
            Histórico Productivo
        </h4>

        <small class="text-muted">
            Seguimiento de producción por ciclo de siembra
        </small>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body bg-light">

            <form
                method="GET"
                action="{{ route('agronomia.historico_siembras') }}"
                class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">
                        Bloque
                    </label>

                    <select
                        name="bloque"
                        id="bloque"
                        class="form-select">
                        <option value="">
                            Seleccione
                        </option>

                        @foreach ($bloques as $bloque)
                        <option
                            value="{{ $bloque->Codigo_Bloque }}"
                            @selected(request('bloque')==$bloque->Codigo_Bloque)
                            >
                            {{ $bloque->Nombre_Bloque }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label">
                        Siembra
                    </label>

                    <select
                        name="siembra"
                        id="siembra"
                        class="form-select">
                        <option value="">
                            Seleccione bloque primero
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-success w-100">
                        <i class="bi bi-search"></i>
                        Consultar
                    </button>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <a
                        href="{{ route('agronomia.historico_siembras') }}"
                        class="btn btn-outline-secondary w-100">
                        Limpiar
                    </a>
                </div>
            </form>

        </div>
    </div>

    @if (!$reporte)
    <div class="alert alert-info">
        Seleccione un bloque y una siembra para consultar el histórico productivo.
    </div>
    @endif

    @if ($reporte)
    @php
    $fecha = function ($valor) {
    if (empty($valor)) {
    return '-';
    }

    return \Carbon\Carbon::parse($valor)->format('d/m/Y');
    };
    @endphp

    <div class="card shadow-sm mb-3">
        <div class="card-body">

            <div class="row text-center">

                <div class="col-md-3">
                    <strong>Fecha siembra</strong>
                    <br>
                    {{ $reporte['resumen']['fecha'] }}
                </div>

                <div class="col-md-3">
                    <strong>Variedad</strong>
                    <br>
                    {{ $reporte['resumen']['variedad'] }}
                </div>

                <div class="col-md-3">
                    <strong>Camas</strong>
                    <br>
                    {{ $reporte['resumen']['camas'] }}
                </div>

                <div class="col-md-3">
                    <strong>Plantas</strong>
                    <br>
                    {{ number_format($reporte['resumen']['plantas'], 0, ',', '.') }}
                </div>

            </div>

        </div>
    </div>

    <div class="card shadow-sm">

        <div class="table-responsive scroll-table">

            <table class="table table-bordered table-hover table-sm table-excel mb-0">

                <thead>
                    <tr>
                        <th class="sticky-bloque">Bloque</th>

                        <th class="sticky-nave">Nave</th>

                        <th class="sticky-cama">Cama</th>

                        <th>Variedad</th>

                        <th>
                            Siembra
                        </th>

                        <th>
                            Pinch
                        </th>

                        <th>
                            Hormona
                        </th>

                        <th>
                            Erradicación
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Plantas
                        </th>

                        @foreach ($reporte['semanas'] as $semana)
                        <th>
                            {{ $semana }}
                        </th>
                        @endforeach

                        <th>
                            Total
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($reporte['filas'] as $fila)

                    @php
                    $s = $fila['siembra'];
                    $ubicacion = $s->ubicacion;
                    $bloque = $ubicacion->bloque;
                    @endphp

                    <tr>

                        <td class="sticky-bloque">
                            {{ $bloque->Codigo_Bloque ?? 'N/D' }}
                        </td>

                        <td class="sticky-nave">
                            {{ $ubicacion->Nave ?? 'N/D' }}
                        </td>

                        <td class="sticky-cama">
                            {{ $ubicacion->Cama ?? 'N/D' }}
                        </td>

                        <td class="fw-bold text-success">

                            {{ $s->variedad->Nombre_Variedad ?? 'N/D' }}

                            @if (!empty($s->variedad->Color))
                            <br>

                            <small>
                                {{ $s->variedad->Color }}
                            </small>
                            @endif

                        </td>

                        <td>
                            {{ $fecha($s->Fecha_Siembra) }}
                        </td>

                        <td>
                            {{ $fecha($s->Fecha_Pinch) }}
                        </td>

                        <td>
                            {{ $fecha($s->Fecha_Hormona) }}
                        </td>

                        <td class="text-danger">
                            {{ $fecha($s->Fecha_Erradicacion) }}
                        </td>

                        <td>
                            @if (empty($s->Fecha_Erradicacion))
                            <span class="badge bg-success">
                                En producción
                            </span>
                            @else
                            <span class="badge bg-secondary">
                                Erradicada
                            </span>
                            @endif
                        </td>

                        <td class="text-end">
                            {{ number_format($s->Cantidad_Plantas, 0, ',', '.') }}
                        </td>

                        @foreach ($reporte['semanas'] as $semana)
                        <td class="text-end">
                            {{ number_format($fila['semanas'][$semana] ?? 0, 0, ',', '.') }}
                        </td>
                        @endforeach

                        <td class="text-end fw-bold">
                            {{ number_format($fila['total'], 0, ',', '.') }}
                        </td>

                    </tr>

                    @endforeach

                </tbody>

                <tfoot>

                    <tr class="table-secondary fw-bold">

                        <td colspan="10">
                            TOTAL SEMANAL
                        </td>

                        @foreach ($reporte['semanas'] as $semana)
                        <td class="text-end">
                            {{ number_format($reporte['totalSemanas'][$semana] ?? 0, 0, ',', '.') }}
                        </td>
                        @endforeach

                        <td></td>

                    </tr>

                </tfoot>

            </table>

            <hr>

            <h6 class="fw-bold text-success px-2 mt-3">
                Índice de Tallos por Planta
            </h6>

            <table class="table table-bordered table-hover table-sm table-excel mb-0">

                <thead>
                    <tr>

                        <th>
                            Indicador
                        </th>

                        @foreach ($reporte['semanas'] as $semana)
                        <th>
                            {{ $semana }}
                        </th>
                        @endforeach

                        <th>
                            Total
                        </th>

                    </tr>
                </thead>

                <tbody>

                    <tr>

                        <td class="fw-bold">
                            Producción semanal
                        </td>

                        @foreach ($reporte['semanas'] as $semana)
                        <td class="text-end">
                            {{ number_format($reporte['totalSemanas'][$semana] ?? 0, 0, ',', '.') }}
                        </td>
                        @endforeach

                        <td class="text-end fw-bold">
                            {{ number_format(array_sum($reporte['totalSemanas']), 0, ',', '.') }}
                        </td>

                    </tr>

                    <tr>

                        <td class="fw-bold">
                            Producción / Planta
                        </td>

                        @foreach ($reporte['semanas'] as $semana)
                        <td class="text-end">
                            {{ number_format($reporte['resumen']['produccionPlanta'][$semana] ?? 0, 4, ',', '.') }}
                        </td>
                        @endforeach

                        <td class="text-end fw-bold">
                            {{ number_format(array_sum($reporte['resumen']['produccionPlanta']), 4, ',', '.') }}
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>
    </div>

    @endif

</div>
@endsection

@push('scripts')
<script>
    let seleccionInicial = @json(request('siembra'));
    document.getElementById('bloque')?.addEventListener('change', function() {
        const bloque = this.value;
        const select = document.getElementById('siembra');

        select.innerHTML = '<option>Cargando...</option>';

        if (!bloque) {
            select.innerHTML = '<option>Seleccione bloque primero</option>';
            return;
        }

        fetch(@json(route('agronomia.siembras_bloque', ['bloque' => '__BLOQUE__'])).replace('__BLOQUE__', encodeURIComponent(bloque)))
            .then(response => {
                if (!response.ok) {
                    throw new Error('No se pudieron cargar las siembras.');
                }

                return response.json();
            })
            .then(data => {
                select.innerHTML =
                    '<option value="">Seleccione siembra</option>';

                data.forEach(item => {
                    const option = document.createElement('option');

                    option.value = JSON.stringify(item.ids);
                    option.textContent = item.texto;

                    option.selected = seleccionInicial === option.value;
                    select.appendChild(option);
                });
                seleccionInicial = null;
            })
            .catch(error => {
                console.error(error);

                select.innerHTML =
                    '<option value="">Error cargando siembras</option>';
            });
    });
    if (document.getElementById('bloque')?.value) {
        document.getElementById('bloque').dispatchEvent(new Event('change'));
    }
</script>
@endpush
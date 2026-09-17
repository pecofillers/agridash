@extends('layouts.app')

@section('title', 'Comparador de Siembras')

@section('content')

<h2 class="page-title mb-4">📊 COMPARADOR DE SIEMBRAS</h2>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">🔎 Seleccionar Siembras</h5>
    </div>

    <div class="card-body">

        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <label class="form-label fw-bold small">Bloque</label>
                <select class="form-select form-select-sm">
                    <option>Todos los bloques</option>
                    <option>Bloque 1</option>
                    <option>Bloque 2</option>
                    <option>Bloque 3</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Variedad</label>
                <select class="form-select form-select-sm">
                    <option>Todas las variedades</option>
                    <option>LIMONIUM MISTY - BLUE</option>
                    <option>LIMONIUM MISTY - WHITE</option>
                    <option>STATICE PURPLE</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Buscar</label>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Fecha, bloque o variedad..."
                >
            </div>

        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"></th>
                        <th>Bloque</th>
                        <th>Variedad</th>
                        <th>Fecha Siembra</th>
                        <th class="text-center">Camas</th>
                        <th class="text-end">Plantas</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                checked
                            >
                        </td>
                        <td>Bloque 1</td>
                        <td>LIMONIUM MISTY - BLUE</td>
                        <td>23/07/2025</td>
                        <td class="text-center">11</td>
                        <td class="text-end">1.629</td>
                    </tr>

                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                checked
                            >
                        </td>
                        <td>Bloque 3</td>
                        <td>LIMONIUM MISTY - BLUE</td>
                        <td>15/09/2025</td>
                        <td class="text-center">18</td>
                        <td class="text-end">2.650</td>
                    </tr>

                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input"
                            >
                        </td>
                        <td>Bloque 7</td>
                        <td>LIMONIUM MISTY - WHITE</td>
                        <td>03/11/2025</td>
                        <td class="text-center">15</td>
                        <td class="text-end">2.200</td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                2 siembras seleccionadas
            </small>

            <button class="btn btn-primary btn-sm">
                📊 Comparar Siembras
            </button>
        </div>

    </div>
</div>


{{-- ========================================================= --}}
{{-- INFORMACIÓN DE LAS SIEMBRAS --}}
{{-- ========================================================= --}}

<div class="row g-3 mb-4">

    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    Bloque 1 · LIMONIUM MISTY - BLUE
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Siembra
                        </div>
                        <div class="fw-bold">
                            23/07/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Pinch
                        </div>
                        <div class="fw-bold">
                            15/10/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Hormona
                        </div>
                        <div class="fw-bold">
                            16/10/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Camas
                        </div>
                        <div class="fw-bold">
                            11
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Plantas
                        </div>
                        <div class="fw-bold">
                            1.629
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Erradicación
                        </div>
                        <div class="fw-bold">
                            —
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>


    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    Bloque 3 · LIMONIUM MISTY - BLUE
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Siembra
                        </div>
                        <div class="fw-bold">
                            15/09/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Pinch
                        </div>
                        <div class="fw-bold">
                            05/12/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Fecha Hormona
                        </div>
                        <div class="fw-bold">
                            06/12/2025
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Camas
                        </div>
                        <div class="fw-bold">
                            18
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Plantas
                        </div>
                        <div class="fw-bold">
                            2.650
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="text-muted small">
                            Erradicación
                        </div>
                        <div class="fw-bold">
                            —
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- GRÁFICA --}}
{{-- ========================================================= --}}

<div class="card shadow-sm mb-4">

    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            📈 Índice de Tallos por Planta
        </h5>
    </div>

    <div class="card-body">

        <div
            class="border rounded d-flex align-items-center justify-content-center"
            style="height:380px;"
        >
            <div class="text-center text-muted">
                <div style="font-size:40px;">📈</div>
                <div class="fw-bold">
                    Aquí irá la gráfica
                </div>
                <small>
                    Producción semanal / plantas activas
                </small>
            </div>
        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- TABLA DE RESPALDO --}}
{{-- ========================================================= --}}

<div class="card shadow-sm">

    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            📋 Índice de Tallos por Planta
        </h5>
    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-sm table-bordered align-middle mb-0 text-center">

                <thead class="table-light">

                    <tr>
                        <th
                            rowspan="2"
                            class="align-middle text-start"
                            style="min-width:190px;"
                        >
                            Indicador
                        </th>

                        <th colspan="10">
                            Semanas
                        </th>
                    </tr>

                    <tr>
                        <th>2025-51</th>
                        <th>2025-52</th>
                        <th>2026-01</th>
                        <th>2026-02</th>
                        <th>2026-03</th>
                        <th>2026-04</th>
                        <th>2026-05</th>
                        <th>2026-06</th>
                        <th>2026-07</th>
                        <th>2026-08</th>
                    </tr>

                </thead>

                <tbody>

                    <tr class="table-light">
                        <th class="text-start">
                            Producción semanal
                        </th>

                        <td>76</td>
                        <td>684</td>
                        <td>1.374</td>
                        <td>1.770</td>
                        <td>1.773</td>
                        <td>2.870</td>
                        <td>3.099</td>
                        <td>1.106</td>
                        <td>583</td>
                        <td>356</td>
                    </tr>

                    <tr>
                        <th class="text-start">
                            Plantas activas
                        </th>

                        <td>1.629</td>
                        <td>1.629</td>
                        <td>1.629</td>
                        <td>1.629</td>
                        <td>1.629</td>
                        <td>1.629</td>
                        <td>1.500</td>
                        <td>1.500</td>
                        <td>1.500</td>
                        <td>1.500</td>
                    </tr>

                    <tr class="table-primary fw-bold">
                        <th class="text-start">
                            Producción / Planta
                        </th>

                        <td>0,0467</td>
                        <td>0,4199</td>
                        <td>0,8435</td>
                        <td>1,0866</td>
                        <td>1,0884</td>
                        <td>1,7618</td>
                        <td>2,0660</td>
                        <td>0,7373</td>
                        <td>0,3887</td>
                        <td>0,2373</td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
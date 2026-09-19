@extends('layouts.app')

@section('title', 'Comparador de Siembras')

@push('styles')
<style>
.comparador-card{border:1px solid #dfe7e2;border-radius:10px;overflow:hidden}
.comparador-header{background:#f5f8f6;border-bottom:1px solid #dfe7e2;padding:12px 16px}
.comparador-header h5{margin:0;color:#1f2933;font-weight:700}
.tabla-comparador{font-size:.82rem;white-space:nowrap}
.tabla-comparador thead th{background:#2e7d32;color:#fff;text-align:center;vertical-align:middle;border-color:#d0d8d3;padding:9px 7px}
.tabla-comparador tbody td,.tabla-comparador tbody th{border-color:#d0d8d3;vertical-align:middle;padding:8px 7px}
.tabla-comparador .col-siembra{min-width:220px;text-align:left;white-space:normal}
.tabla-comparador .col-indicador{min-width:155px;text-align:left}
.tabla-comparador .fila-plantas td,.tabla-comparador .fila-plantas th{background:#f8faf9}
.tabla-comparador .fila-indice td,.tabla-comparador .fila-indice th{background:#e7f3ea;font-weight:700}
.nombre-siembra{font-weight:700;color:#198754}
.detalle-siembra{font-size:.72rem;color:#6c757d;margin-top:2px}
.dato-label{color:#6c757d;font-size:.7rem;font-weight:700;text-transform:uppercase}
.dato-valor{font-size:.88rem;font-weight:600}
.ficha-siembra{border-left:4px solid #2e7d32}
.grafica-wrapper{position:relative;height:420px}
.resultado-seleccionado{background:#f1f8f3!important}
.seleccion-ciclo{cursor:pointer}
@media(max-width:768px){
    .grafica-wrapper{height:320px}
    .tabla-comparador{min-width:1050px}
}
</style>
@endpush

@section('content')

<h2 class="page-title mb-4">📊 COMPARADOR DE SIEMBRAS</h2>
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<p class="text-muted small">Semana 1: semana calendario que contiene la fecha de siembra. — indica ausencia de registro o de un denominador válido.</p>

<form method="GET" action="{{ route('agronomia.comparador-siembras') }}" class="card p-3 mb-4 shadow-sm border-0 bg-light">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1">Bloques</label>
            <select name="bloques[]" multiple class="form-select form-select-sm" style="height: 80px;">
                @foreach($bloques as $bloque)
                    <option value="{{ $bloque->ID_Bloque }}" @selected(in_array($bloque->ID_Bloque, (array) request('bloques', [])))>
                        {{ $bloque->Codigo_Bloque }} - {{ $bloque->Nombre_Bloque }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted" style="font-size: 0.7rem;">Ctrl+Click para múltiples</small>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1">Variedades</label>
            <select name="variedades[]" multiple class="form-select form-select-sm" style="height: 80px;">
                @foreach($variedades as $variedad)
                    <option value="{{ $variedad->ID_Variedad }}" @selected(in_array($variedad->ID_Variedad, (array) request('variedades', [])))>
                        {{ $variedad->Nombre_Variedad }} @if($variedad->Color) - {{ $variedad->Color }} @endif
                    </option>
                @endforeach
            </select>
            <small class="text-muted" style="font-size: 0.7rem;">Ctrl+Click para múltiples</small>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold small text-muted mb-1">Buscar</label>
            <input type="text" name="texto" class="form-control form-control-sm" placeholder="Bloque, variedad o color..." value="{{ request('texto') }}">
        </div>
        <div class="col-md-2">
            <button type="submit" name="buscar" value="1" class="btn btn-primary btn-sm w-100 fw-bold">🔍 Buscar</button>
        </div>
    </div>
</form>

@if(request()->boolean('buscar'))

<div class="card shadow-sm mb-4 comparador-card">
    <div class="comparador-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5>🌱 Siembras encontradas</h5>
                <small class="text-muted">Seleccione los ciclos que desea comparar.</small>
            </div>
            <div>
                <span class="badge bg-secondary me-1">{{ $ciclos->count() }} encontradas</span>
                <span class="badge bg-success" id="contadorSeleccionadas">0 seleccionadas</span>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('agronomia.comparador-siembras') }}" id="formComparar">
        @foreach((array) request('bloques', []) as $bloque)
            <input type="hidden" name="bloques[]" value="{{ $bloque }}">
        @endforeach
        @foreach((array) request('variedades', []) as $variedad)
            <input type="hidden" name="variedades[]" value="{{ $variedad }}">
        @endforeach
        <input type="hidden" name="texto" value="{{ request('texto') }}">
        <input type="hidden" name="buscar" value="1">

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 tabla-comparador">
                <thead>
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" class="form-check-input" id="seleccionarTodas">
                        </th>
                        <th>Bloque</th>
                        <th>Variedad</th>
                        <th>Siembra</th>
                        <th>Pinch</th>
                        <th>Hormona</th>
                        <th>Erradicación</th>
                        <th>Camas</th>
                        <th>Plantas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ciclos as $ciclo)
                    <tr class="fila-ciclo">
                        <td class="text-center">
                            <input type="checkbox" name="ciclos[]" value="{{ $ciclo->ID_Ciclo_Siembra }}" class="form-check-input seleccion-ciclo" @checked(in_array($ciclo->ID_Ciclo_Siembra, (array) request('ciclos', [])))>
                        </td>
                        <td class="fw-bold">{{ $ciclo->Codigo_Bloque }}</td>
                        <td class="text-success fw-bold">{{ $ciclo->nombre_variedad }}</td>
                        <td>{{ optional($ciclo->Fecha_Siembra)->format('d/m/Y') }}</td>
                        <td>
                            {{ $ciclo->fecha_pinch ? \Carbon\Carbon::parse($ciclo->fecha_pinch)->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            {{ $ciclo->fecha_hormona ? \Carbon\Carbon::parse($ciclo->fecha_hormona)->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            {{ $ciclo->fecha_erradicacion ? \Carbon\Carbon::parse($ciclo->fecha_erradicacion)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-center">{{ $ciclo->cantidad_camas }}</td>
                        <td class="text-end fw-bold">{{ number_format($ciclo->cantidad_plantas, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No se encontraron siembras con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ciclos->isNotEmpty())
        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm me-1" id="btnSeleccionarTodas">
                    Seleccionar todas
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarSeleccion">
                    Limpiar selección
                </button>
            </div>
            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                📊 Comparar seleccionadas
            </button>
        </div>
        @endif
    </form>
</div>

@endif

@if($reporte)

<div class="row g-3 mb-4">
    @foreach($reporte['ciclos'] as $indice => $ciclo)
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 ficha-siembra">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="fw-bold">Bloque {{ $ciclo['bloque'] }}</div>
                        <div class="text-success fw-bold">{{ $ciclo['variedad'] }}</div>
                    </div>
                    <span class="badge bg-success">Siembra {{ $indice + 1 }}</span>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="dato-label">Siembra</div>
                        <div class="dato-valor">
                            {{ $ciclo['fecha'] ? $ciclo['fecha']->format('d/m/Y') : '—' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="dato-label">Pinch</div>
                        <div class="dato-valor">
                            {{ $ciclo['pinch'] ? \Carbon\Carbon::parse($ciclo['pinch'])->format('d/m/Y') : '—' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="dato-label">Hormona</div>
                        <div class="dato-valor">
                            {{ $ciclo['hormona'] ? \Carbon\Carbon::parse($ciclo['hormona'])->format('d/m/Y') : '—' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="dato-label">Erradicación</div>
                        <div class="dato-valor">
                            {{ $ciclo['erradicacion'] ? \Carbon\Carbon::parse($ciclo['erradicacion'])->format('d/m/Y') : '—' }}
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="dato-label">Camas</div>
                        <div class="dato-valor">{{ $ciclo['camas'] }}</div>
                    </div>

                    <div class="col-6">
                        <div class="dato-label">Plantas iniciales</div>
                        <div class="dato-valor">{{ number_format($ciclo['plantas'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card shadow-sm mb-4 comparador-card">
    <div class="comparador-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5>📈 Índice de Tallos por Planta</h5>
                <small class="text-muted">Producción semanal / plantas iniciales</small>
            </div>
            <span class="badge bg-success">Semana del ciclo</span>
        </div>
    </div>
    <div class="card-body">
        <div class="grafica-wrapper">
            <canvas id="graficaProduccionPlanta"></canvas>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 comparador-card">
    <div class="comparador-header">
        <h5>📋 Datos de producción</h5>
        <small class="text-muted">Producción semanal, plantas iniciales y producción por planta.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered mb-0 tabla-comparador">
            <thead>
                <tr>
                    <th class="col-siembra">SIEMBRA</th>
                    <th class="col-indicador">INDICADOR</th>
                    @foreach($reporte['semanas'] as $semana)
                        <th>Semana {{ $semana }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($reporte['ciclos'] as $ciclo)

                <tr>
                    <th rowspan="3" class="col-siembra">
                        <div class="nombre-siembra">
                            Bloque {{ $ciclo['bloque'] }}
                        </div>
                        <div>{{ $ciclo['variedad'] }}</div>
                        <div class="detalle-siembra">
                            Siembra:
                            {{ $ciclo['fecha'] ? $ciclo['fecha']->format('d/m/Y') : '—' }}
                        </div>
                    </th>

                    <th class="col-indicador">Producción semanal</th>

                    @foreach($reporte['semanas'] as $semana)
                        <td>
                            {{ isset($ciclo['semanas'][$semana]) ? number_format($ciclo['semanas'][$semana]['produccion'], 0, ',', '.') : '—' }}
                        </td>
                    @endforeach
                </tr>

                <tr class="fila-plantas">
                    <th class="col-indicador">Plantas iniciales</th>

                    @foreach($reporte['semanas'] as $semana)
                        <td>
                            {{ isset($ciclo['semanas'][$semana]) ? number_format($ciclo['semanas'][$semana]['plantas'], 0, ',', '.') : '—' }}
                        </td>
                    @endforeach
                </tr>

                <tr class="fila-indice">
                    <th class="col-indicador">Producción / Planta</th>

                    @foreach($reporte['semanas'] as $semana)
                        <td>
                            {{ isset($ciclo['semanas'][$semana]['indice']) ? number_format($ciclo['semanas'][$semana]['indice'], 4, ',', '.') : '—' }}
                        </td>
                    @endforeach
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm comparador-card">
    <div class="comparador-header">
        <h5>📅 Referencia de semanas</h5>
        <small class="text-muted">Semana calendario correspondiente a cada semana del ciclo.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 tabla-comparador text-center">
            <thead>
                <tr>
                    <th>Siembra</th>
                    @foreach($reporte['semanas'] as $semana)
                        <th>Semana {{ $semana }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($reporte['ciclos'] as $ciclo)
                <tr>
                    <th>
                        Bloque {{ $ciclo['bloque'] }}
                        ·
                        {{ $ciclo['fecha'] ? $ciclo['fecha']->format('d/m/Y') : '—' }}
                    </th>

                    @foreach($reporte['semanas'] as $semana)
                        <td>
                            {{ $ciclo['semanas'][$semana]['calendario'] ?? '—' }}
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection

@push('scripts')
@if(request()->boolean('buscar'))
<script>
document.addEventListener('DOMContentLoaded',function(){
    const checks=document.querySelectorAll('.seleccion-ciclo');
    const contador=document.getElementById('contadorSeleccionadas');
    const master=document.getElementById('seleccionarTodas');
    const btnTodas=document.getElementById('btnSeleccionarTodas');
    const btnLimpiar=document.getElementById('btnLimpiarSeleccion');

    function actualizar(){
        if (!contador || !master) return;
        const seleccionadas=[...checks].filter(c=>c.checked).length;
        contador.textContent=seleccionadas+' seleccionada'+(seleccionadas===1?'':'s');
        master.checked=checks.length>0&&seleccionadas===checks.length;
        master.indeterminate=seleccionadas>0&&seleccionadas<checks.length;
        checks.forEach(c=>{
            c.closest('tr').classList.toggle('resultado-seleccionado',c.checked);
        });
    }

    checks.forEach(c=>c.addEventListener('change',actualizar));

    master?.addEventListener('change',function(){
        checks.forEach(c=>c.checked=this.checked);
        actualizar();
    });

    btnTodas?.addEventListener('click',function(){
        checks.forEach(c=>c.checked=true);
        actualizar();
    });

    btnLimpiar?.addEventListener('click',function(){
        checks.forEach(c=>c.checked=false);
        actualizar();
    });

    actualizar();
});
</script>
@endif

@if($reporte)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const canvas=document.getElementById('graficaProduccionPlanta');
    if(!canvas)return;

    const semanas=@json($reporte['semanas']);

    const ciclos=@json($reporte['grafica']);

    new Chart(canvas,{
        type:'line',
        data:{
            labels:semanas.map(semana=>'Semana '+semana),
            datasets:ciclos.map(dataset=>({
                label:dataset.label,
                data:dataset.data,
                tension:.25,
                borderWidth:2,
                pointRadius:3,
                pointHoverRadius:5
            }))
        },
        options:{
            responsive:true,
            maintainAspectRatio:false,
            interaction:{
                mode:'index',
                intersect:false
            },
            plugins:{
                legend:{
                    position:'top'
                },
                tooltip:{
                    callbacks:{
                        label:function(context){
                            return context.dataset.label+': '+Number(context.parsed.y).toFixed(4);
                        }
                    }
                }
            },
            scales:{
                x:{
                    title:{
                        display:true,
                        text:'Semana desde la siembra'
                    }
                },
                y:{
                    beginAtZero:true,
                    title:{
                        display:true,
                        text:'Producción / Planta'
                    }
                }
            }
        }
    });
});
</script>
@endif
@endpush
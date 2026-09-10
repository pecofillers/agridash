@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Editar registro del {{ $insumo->fecha->format('d/m/Y') }}</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('agronomia.insumos.actualizar', $insumo->id) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="text" class="form-control" value="{{ $insumo->fecha->format('d/m/Y') }}" disabled>
                        <small class="text-muted">La fecha no se puede modificar. Si necesitas cambiarla, borra este registro y crea uno nuevo.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amortiguador - Medida Visual (Litros)</label>
                        <input type="number" step="0.01" name="amortiguador_medida_visual" class="form-control" value="{{ old('amortiguador_medida_visual', $insumo->amortiguador_medida_visual) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amortiguador - Medida Registro (m³)</label>
                        <input type="number" step="0.01" name="amortiguador_medida_registro" class="form-control" value="{{ old('amortiguador_medida_registro', $insumo->amortiguador_medida_registro) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agrofeed - Medida Visual (Litros)</label>
                        <input type="number" step="0.01" name="agrofeed_medida_visual" class="form-control" value="{{ old('agrofeed_medida_visual', $insumo->agrofeed_medida_visual) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agrofeed - Medida Registro (m³)</label>
                        <input type="number" step="0.01" name="agrofeed_medida_registro" class="form-control" value="{{ old('agrofeed_medida_registro', $insumo->agrofeed_medida_registro) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agua - Lectura del contador (m³)</label>
                        <input type="number" step="0.01" name="agua_lectura" class="form-control" value="{{ old('agua_lectura', $insumo->agua_lectura) }}">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('agronomia.insumos') }}" class="btn btn-secondary">Cancelar</a>
                </div>

                <small class="text-muted d-block mt-3">
                    Al guardar, las diferencias (visual y registro) de este día y del día siguiente se recalculan automáticamente.
                </small>
            </form>
        </div>
    </div>
</div>
@endsection
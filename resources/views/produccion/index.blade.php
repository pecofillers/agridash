@extends('layouts.app')

@section('title', 'Producción')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {!! session('success') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<h2 class="page-title mb-4">👨‍🌾 PRODUCCIÓN</h2>

<div class="card card-dashboard p-4 mb-4 bg-light border-primary">
    <div class="row align-items-center">

        <div class="col-md-12 mb-3">
            <h5>📚 Producción por Bloque</h5>
            <p class="text-muted small mb-0">
                Sincroniza la producción desde OneDrive o descarga el historial consolidado de un bloque.
            </p>
        </div>

        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
            <h6 class="text-warning fw-bold">🔄 Sincronización de Producción</h6>

            <form
                id="form-sincronizar-bloque"
                action="{{ route('produccion.sincronizar_bloque') }}"
                method="POST"
                class="row g-2"
            >
                @csrf

                <div class="col-md-8">
                    <label class="form-label small fw-bold">Bloque</label>

                    <select name="bloque" class="form-select form-select-sm" required>
                        <option value="">Selecciona el bloque...</option>

                        @foreach($bloques as $bloque)
                            <option value="{{ $bloque->Codigo_Bloque }}">
                                {{ $bloque->Nombre_Bloque }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button
                        type="submit"
                        class="btn btn-success btn-sm w-100"
                    >
                        🔄 Sincronizar
                    </button>
                </div>
            </form>

            <form
                id="form-sincronizar-todo"
                action="{{ route('produccion.sincronizar_todo') }}"
                method="POST"
                class="mt-3"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-warning btn-sm w-100"
                >
                    🔄🌐 Sincronizar TODOS los Bloques
                </button>
            </form>
        </div>

        <div class="col-md-6 ps-md-4">
            <h6>📥 Descarga Masiva de Producción</h6>

            <form
                action="{{ route('produccion.exportar_multinave') }}"
                method="GET"
                class="row g-2"
            >
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Bloque</label>

                    <select
                        name="bloque_exportar"
                        class="form-select form-select-sm"
                        required
                    >
                        <option value="">Selecciona el bloque...</option>

                        @foreach($bloques as $bloque)
                            <option value="{{ $bloque->Codigo_Bloque }}">
                                {{ $bloque->Nombre_Bloque }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button
                        type="submit"
                        class="btn btn-outline-primary btn-sm w-100"
                    >
                        📥 Descargar
                    </button>
                </div>
            </form>

            <p class="text-muted small mt-3 mb-0">
                El archivo generado tendrá el formato:
                <strong>Bloque_#.xlsx</strong>
            </p>
        </div>
    </div>
</div>

<div id="sync-status-panel" class="alert alert-info d-none">
    <div class="d-flex justify-content-between align-items-center">
        <span id="sync-status-msg">Sincronizando...</span>
        <span id="sync-status-progress" class="badge bg-secondary"></span>
    </div>
</div>

<script>
let syncPollTimer = null;

function iniciarPollingEstado() {
    const panel = document.getElementById('sync-status-panel');
    const msg = document.getElementById('sync-status-msg');
    const progreso = document.getElementById('sync-status-progress');

    panel.classList.remove(
        'd-none',
        'alert-success',
        'alert-danger'
    );

    panel.classList.add('alert-info');

    syncPollTimer = setInterval(async () => {
        try {
            const res = await fetch(
                "{{ route('produccion.estado_sincronizacion') }}",
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await res.json();

            msg.textContent = data.mensaje ?? 'Sincronizando...';
            progreso.textContent = data.progreso ?? '';

            if (data.activo === false) {
                clearInterval(syncPollTimer);

                panel.classList.remove('alert-info');

                panel.classList.add(
                    data.error
                        ? 'alert-danger'
                        : 'alert-success'
                );

                if (data.error) {
                    msg.textContent = 'Error: ' + data.error;
                }
            }
        } catch (e) {
        }
    }, 1500);
}

async function enviarSincronizacion(form, event) {
    event.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    const textoOriginal = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '⏳ Sincronizando...';

    iniciarPollingEstado();

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });

        const data = await res.json();

        clearInterval(syncPollTimer);

        const panel = document.getElementById('sync-status-panel');

        panel.classList.remove(
            'alert-info',
            'alert-danger',
            'alert-success'
        );

        panel.classList.add(
            data.success
                ? 'alert-success'
                : 'alert-danger'
        );

        document.getElementById('sync-status-msg').innerHTML =
            data.mensaje ?? 'Proceso terminado.';

    } catch (e) {
        clearInterval(syncPollTimer);

        const panel = document.getElementById('sync-status-panel');

        panel.classList.remove('alert-info');
        panel.classList.add('alert-danger');

        document.getElementById('sync-status-msg').textContent =
            'Ocurrió un error de conexión.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const formBloque =
        document.getElementById('form-sincronizar-bloque');

    const formTodo =
        document.getElementById('form-sincronizar-todo');

    if (formBloque) {
        formBloque.addEventListener('submit', e => {
            enviarSincronizacion(formBloque, e);
        });
    }

    if (formTodo) {
        formTodo.addEventListener('submit', e => {
            if (!confirm(
                'Esto va a sincronizar TODOS los bloques configurados. ¿Continuar?'
            )) {
                e.preventDefault();
                return;
            }

            enviarSincronizacion(formTodo, e);
        });
    }
});
</script>

@endsection
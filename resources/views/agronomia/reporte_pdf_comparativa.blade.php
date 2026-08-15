<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Comparativo de Siembras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10pt; color: #1e293b; background: #fff;}
        .header { border-bottom: 2px solid #0f5132; padding-bottom: 15px; margin-bottom: 20px; }
        .kpi-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; }
        .table-pdf th { background-color: #0f5132 !important; color: white !important; font-size: 9pt; }
        .table-pdf td { font-size: 9pt; padding: 6px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="p-4">

    <div class="no-print mb-4 p-3 bg-light rounded border d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-danger">📄 Vista Previa de PDF (Guárdalo desde el menú de impresión)</h6>
        <button onclick="window.print()" class="btn btn-success btn-sm">🖨️ Imprimir / Guardar PDF</button>
    </div>

    <div class="header d-flex justify-content-between align-items-end">
        <div>
            <div class="text-success fw-bold text-uppercase small">Informe Agronómico Especializado</div>
            <h2 class="fw-bold m-0 text-dark">Comparativa de Producción de Siembras</h2>
        </div>
        <div class="text-end small text-muted">Fecha: {{ date('d/m/Y H:i') }}<br>SISTEMA AGRIDASH</div>
    </div>

    @php
        $totalCamas = $detalleCamas->count();
        $totalPlantas = $detalleCamas->sum('plantas');
        $totalTallos = $detalleCamas->sum('total_tallos');
    @endphp

    <div class="row g-2 mb-4">
        <div class="col-3"><div class="kpi-box"><div class="small text-muted text-uppercase fw-bold">Camas Evaluadas</div><h4 class="m-0 text-dark">{{ $totalCamas }}</h4></div></div>
        <div class="col-3"><div class="kpi-box"><div class="small text-muted text-uppercase fw-bold">Plantas</div><h4 class="m-0 text-dark">{{ number_format($totalPlantas) }}</h4></div></div>
        <div class="col-3"><div class="kpi-box"><div class="small text-muted text-uppercase fw-bold">Tallos Extraídos</div><h4 class="m-0 text-success fw-bold">{{ number_format($totalTallos) }}</h4></div></div>
        <div class="col-3"><div class="kpi-box"><div class="small text-muted text-uppercase fw-bold">Rendimiento Promedio</div><h4 class="m-0 text-dark">{{ $totalPlantas > 0 ? number_format($totalTallos/$totalPlantas, 2) : 0 }} t/pl</h4></div></div>
    </div>

    <div class="mb-4 p-3 border rounded" style="background-color: #fcfcfc;">
        <h6 class="fw-bold text-secondary mb-2 text-center">Curva de Producción (Semanas Relativas de Cosecha)</h6>
        <div style="height: 250px; width: 100%;"><canvas id="chartComparativa"></canvas></div>
    </div>

    <h6 class="fw-bold mt-4">Matriz de Producción Semanal y Rendimiento (t/planta)</h6>
    <div style="overflow-x: auto; margin-bottom: 25px;">
        <table class="table table-bordered table-pdf text-center align-middle">
            <thead>
                <tr>
                    <th class="text-start" style="min-width: 180px;">Siembra Evaluada</th>
                    <th>Métrica</th>
                    @foreach($chartLabels as $lbl)
                        <th>{{ str_replace('Semana ', 'S.', $lbl) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $colores = ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6610f2', '#fd7e14', '#20c997']; @endphp

                @foreach($consolidado as $index => $c)
                    @php $color = $colores[$index % count($colores)]; @endphp

                    <tr>
                        <td rowspan="2" class="text-start fw-bold align-middle" style="color: {{ $color }};">
                            ● {{ $c->fecha_siembra }} - {{ $c->variedad }}<br>
                            <small class="text-muted font-normal">{{ number_format($c->numero_plantas) }} pl.</small>
                        </td>
                        <td class="fw-bold bg-light small">Tallos</td>
                        @for($i = 1; $i <= $maxSemanasRelativasGlobal; $i++)
                            @php
                                $semKey = 'Semana ' . $i;
                                $tallosSem = $i > $c->max_semana_lote ? null : ($c->produccion_semanal[$semKey] ?? 0);
                            @endphp
                            <td>{{ $tallosSem !== null ? number_format($tallosSem) : '-' }}</td>
                        @endfor
                    </tr>

                    <tr style="background-color: #f8fafc;">
                        <td class="fw-bold text-success small">t/pl</td>
                        @for($i = 1; $i <= $maxSemanasRelativasGlobal; $i++)
                            @php
                                $semKey = 'Semana ' . $i;
                                $tallosSem = $i > $c->max_semana_lote ? null : ($c->produccion_semanal[$semKey] ?? 0);
                                $rendimiento = ($tallosSem !== null && $c->numero_plantas > 0) 
                                    ? number_format($tallosSem / $c->numero_plantas, 2) 
                                    : null;
                            @endphp
                            <td class="text-success fw-bold">{{ $rendimiento !== null ? $rendimiento : '-' }}</td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h6 class="fw-bold mt-4">Consolidado por Lote</h6>
    <table class="table table-bordered table-pdf">
        <thead>
            <tr><th>F. Siembra</th><th>Variedad</th><th>Color</th><th>Bloque</th><th class="text-center">Camas</th><th class="text-center">Plantas</th><th class="text-end">Total Tallos</th></tr>
        </thead>
        <tbody>
            @foreach ($consolidado as $c)
                <tr>
                    <td class="fw-bold">{{ $c->fecha_siembra }}</td><td>{{ $c->variedad }}</td><td>{{ $c->color }}</td>
                    <td>{{ $c->bloque }}</td><td class="text-center">{{ $c->numero_camas }}</td>
                    <td class="text-center">{{ number_format($c->numero_plantas) }}</td><td class="text-end fw-bold text-success">{{ number_format($c->total_tallos) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h6 class="fw-bold mt-4" style="page-break-before: auto;">Detalle Individual de Camas</h6>
    <table class="table table-bordered table-pdf table-striped">
        <thead>
            <tr><th>Ubicación</th><th>Variedad</th><th>F. Siembra</th><th class="text-center">Plantas</th><th class="text-end">Total Extraído</th></tr>
        </thead>
        <tbody>
            @foreach ($detalleCamas as $dc)
                <tr>
                    <td class="fw-bold">{{ $dc->bloque }} / N{{ $dc->nave }} / C{{ $dc->cama }}</td><td>{{ $dc->variedad }}</td>
                    <td>{{ $dc->fecha_siembra }}</td><td class="text-center">{{ number_format($dc->plantas) }}</td>
                    <td class="text-end">{{ number_format($dc->total_tallos) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('chartComparativa').getContext('2d');
            const labels = {!! json_encode($chartLabels) !!};
            const rawDatasets = {!! json_encode($chartDatasets) !!};
            const colors = ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6610f2', '#fd7e14', '#20c997'];

            const datasets = rawDatasets.map((ds, index) => {
                let color = colors[index % colors.length];
                return { label: ds.label, data: ds.data, borderColor: color, backgroundColor: color, borderWidth: 2.5, tension: 0.3, pointRadius: 2, fill: false };
            });

            new Chart(ctx, {
                type: 'line', data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: { onComplete: () => { setTimeout(() => { window.print(); }, 500); } },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 10, font: {size: 10} } } },
                    scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
                }
            });
        });
    </script>
</body>
</html>
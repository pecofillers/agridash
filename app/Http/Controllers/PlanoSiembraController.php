<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ubicacion;
use App\Models\Siembra;
use App\Models\Variedad;
use Carbon\Carbon;

class PlanoSiembraController extends Controller
{
    public function index(Request $request)
    {
        $fechaConsulta = $request->input('fecha', date('Y-m-d'));
        $bloqueSel = $request->input('bloque', '1');
        $naveSel = $request->input('nave', '');
        $filtroSeccion = $request->input('seccion', 'todas');
        $fechaSiembraFiltro = $request->input('fecha_siembra_filtro', '');

        $ordenNaves = $request->input('orden_naves') === 'desc' ? 'desc' : 'asc';
        $ordenCamas = $request->input('orden_camas') === 'desc' ? 'desc' : 'asc';

        // 1. Obtener Bloques
        $bloques = Ubicacion::select('Bloque')
            ->distinct()
            ->orderBy('Bloque')
            ->pluck('Bloque');

        // 2. Obtener Naves del bloque seleccionado
        $naves = Ubicacion::where('Bloque', $bloqueSel)
            ->select('Nave')
            ->distinct()
            ->orderByRaw('CAST(Nave AS UNSIGNED) asc')
            ->pluck('Nave');

        // 3. Fechas únicas de siembra SOLO del bloque actual
        $camasDelBloque = Ubicacion::where('Bloque', $bloqueSel)
            ->pluck('ID_Ubicacion');

        $fechasSiembra = Siembra::whereIn(
            'ID_Ubicacion',
            $camasDelBloque
        )
            ->whereNotNull('Fecha_Siembra')
            ->where('Fecha_Siembra', '!=', '0000-00-00')
            ->where('Fecha_Siembra', 'not like', '%-0001%')
            ->pluck('Fecha_Siembra')
            ->map(function ($fecha) {
                return Carbon::parse($fecha)->format('Y-m-d');
            })
            ->unique()
            ->sortDesc()
            ->values();

        // 4. Construir consulta base de ubicaciones
        $queryUbicaciones = Ubicacion::where(
            'Bloque',
            $bloqueSel
        )->where(
            'Estado',
            'ACTIVA'
        );

        if (!empty($naveSel)) {
            $queryUbicaciones->where(
                'Nave',
                $naveSel
            );
        }

        $queryUbicaciones
            ->orderByRaw(
                'CAST(Nave AS UNSIGNED) ' . $ordenNaves
            )
            ->orderByRaw(
                'CAST(Cama AS UNSIGNED) ' . $ordenCamas
            );

        $ubicaciones = $queryUbicaciones->get();

        // Filtro de secciones espaciales
        if ($filtroSeccion === 'pares') {
            $ubicaciones = $ubicaciones->filter(
                fn($u) => (int) $u->Cama % 2 === 0
            );
        } elseif ($filtroSeccion === 'impares') {
            $ubicaciones = $ubicaciones->filter(
                fn($u) => (int) $u->Cama % 2 !== 0
            );
        } elseif ($filtroSeccion === 'tercio1') {
            $ubicaciones = $ubicaciones->filter(
                fn($u) => (int) $u->Cama % 3 === 1
            );
        } elseif ($filtroSeccion === 'tercio2') {
            $ubicaciones = $ubicaciones->filter(
                fn($u) => (int) $u->Cama % 3 === 2
            );
        } elseif ($filtroSeccion === 'tercio3') {
            $ubicaciones = $ubicaciones->filter(
                fn($u) => (int) $u->Cama % 3 === 0
            );
        }

        $idsUbicaciones = $ubicaciones->pluck(
            'ID_Ubicacion'
        );

        // 5. Consultar siembras vigentes a la fecha elegida
        //
        // IMPORTANTE:
        // Para cada cama tomamos el ciclo más reciente que
        // haya comenzado antes de la fecha consultada.
        //
        // Esto evita que una siembra histórica con NULL en
        // Fecha_Erradicacion sea tomada incorrectamente como
        // siembra actual cuando existe una versión posterior.
        $siembras = collect();

        foreach ($idsUbicaciones as $idUbicacion) {
            $siembra = $this->obtenerSiembraVigente(
                $idUbicacion,
                $fechaConsulta
            );

            if ($siembra) {
                $siembras[(string) $idUbicacion] = $siembra;
            }
        }

        // 6. Aplicar lógica combinada:
        // Fecha de Siembra o Camas Vacías
        if ($fechaSiembraFiltro === 'vacias') {

            $ubicaciones = $ubicaciones->filter(
                fn($u) => !isset(
                    $siembras[(string) $u->ID_Ubicacion]
                )
            );
        } elseif (!empty($fechaSiembraFiltro)) {

            $ubicaciones = $ubicaciones->filter(
                function ($u) use (
                    $siembras,
                    $fechaSiembraFiltro
                ) {
                    if (
                        !isset(
                            $siembras[(string) $u->ID_Ubicacion]
                        )
                    ) {
                        return false;
                    }

                    $fechaCama = Carbon::parse(
                        $siembras[(string) $u->ID_Ubicacion]->Fecha_Siembra
                    )->format('Y-m-d');

                    return $fechaCama === $fechaSiembraFiltro;
                }
            );
        }

        $variedades = Variedad::orderBy(
            'Nombre_Variedad'
        )->get();

        return view(
            'agronomia.planos_siembra',
            compact(
                'bloques',
                'bloqueSel',
                'naves',
                'naveSel',
                'fechasSiembra',
                'fechaConsulta',
                'filtroSeccion',
                'fechaSiembraFiltro',
                'ordenNaves',
                'ordenCamas',
                'ubicaciones',
                'siembras',
                'variedades'
            )
        );
    }

    /**
     * Actualización masiva.
     *
     * Regla:
     *
     * - Si la cama tiene una siembra vigente:
     *      ACTUALIZAR esa siembra.
     *
     * - Si la cama está disponible:
     *      CREAR una nueva siembra.
     */
    public function actualizarMasivo(Request $request)
    {
        $request->validate([
            'ubicaciones_ids' => 'required|array|min:1',
            'ubicaciones_ids.*' => 'required|integer|distinct|exists:dim_ubicaciones,ID_Ubicacion',

            'ID_Variedad' =>
            'nullable|exists:dim_variedades,ID_Variedad',

            'Estado_Siembra' =>
            'nullable|string',

            'Cantidad_Plantas' =>
            'nullable|numeric|min:0',

            'Fecha_Siembra' =>
            'nullable|date',

            'Fecha_Pinch' =>
            'nullable|date',

            'Fecha_Hormona' =>
            'nullable|date',

            'Fecha_Erradicacion' =>
            'nullable|date',
        ]);

        $datosActualizar = [];

        if ($request->filled('ID_Variedad')) {
            $datosActualizar['ID_Variedad'] =
                $request->ID_Variedad;
        }

        if ($request->filled('Estado_Siembra')) {
            $datosActualizar['Estado_Siembra'] =
                $request->Estado_Siembra;
        }

        if ($request->filled('Cantidad_Plantas')) {
            $datosActualizar['Cantidad_Plantas'] =
                $request->Cantidad_Plantas;
        }

        if ($request->filled('Fecha_Siembra')) {
            $datosActualizar['Fecha_Siembra'] =
                Carbon::parse(
                    $request->Fecha_Siembra
                )->format('Y-m-d');
        }

        if ($request->filled('Fecha_Pinch')) {
            $datosActualizar['Fecha_Pinch'] =
                Carbon::parse(
                    $request->Fecha_Pinch
                )->format('Y-m-d');
        }

        if ($request->filled('Fecha_Hormona')) {
            $datosActualizar['Fecha_Hormona'] =
                Carbon::parse(
                    $request->Fecha_Hormona
                )->format('Y-m-d');
        }

        if ($request->filled('Fecha_Erradicacion')) {
            $datosActualizar['Fecha_Erradicacion'] =
                Carbon::parse(
                    $request->Fecha_Erradicacion
                )->format('Y-m-d');

            $datosActualizar['Estado_Siembra'] =
                'ERRADICADA';
        }

        if (empty($datosActualizar)) {
            return back()->withErrors(
                'Debes escribir al menos un dato (ej: Fecha Pinch) para actualizar.'
            );
        }

        $camasAfectadas = 0;

        foreach ($request->ubicaciones_ids as $idUbi) {

            /*
             * Buscar la siembra vigente de ESTA cama.
             *
             * Si existe:
             *      UPDATE
             *
             * Si no existe:
             *      CREATE
             */
            $siembra = $this->obtenerSiembraVigente(
                $idUbi,
                now()
            );

            if ($siembra) {

                /*
                 * IMPORTANTE:
                 *
                 * No agregamos ID_Ubicacion a
                 * $datosActualizar.
                 *
                 * Esto evita que una ubicación creada
                 * anteriormente dentro del foreach pueda
                 * contaminar la actualización de otra cama.
                 */
                $siembra->fill(
                    $datosActualizar
                )->save();

                $camasAfectadas++;
            } else {

                /*
                 * La cama está disponible.
                 *
                 * Para crear una nueva siembra necesitamos
                 * como mínimo:
                 *
                 * - Fecha_Siembra
                 * - ID_Variedad
                 */
                if (
                    isset(
                        $datosActualizar['Fecha_Siembra']
                    )
                    &&
                    isset(
                        $datosActualizar['ID_Variedad']
                    )
                ) {

                    /*
                     * Creamos una COPIA de los datos.
                     *
                     * NO modificamos $datosActualizar.
                     */
                    $datosCrear =
                        $datosActualizar;
                    $datosCrear['Cantidad_Plantas'] ??= 0;

                    $datosCrear['ID_Ubicacion'] =
                        $idUbi;

                    /*
                     * Si no viene Estado_Siembra,
                     * usamos SEMBRADA por defecto.
                     */
                    if (
                        empty($datosCrear['Estado_Siembra'])
                    ) {
                        $datosCrear['Estado_Siembra'] =
                            'SEMBRADA';
                    }

                    /*
                     * Si no se envió erradicación,
                     * debe quedar NULL.
                     */
                    if (
                        !isset(
                            $datosCrear['Fecha_Erradicacion']
                        )
                    ) {
                        $datosCrear['Fecha_Erradicacion'] =
                            null;
                    }

                    Siembra::create(
                        $datosCrear
                    );

                    $camasAfectadas++;
                }
            }
        }

        if ($camasAfectadas === 0) {
            return back()->withErrors(
                'Ninguna cama fue actualizada. Asegúrate de que las camas seleccionadas ya tengan una siembra activa o envía Variedad y Fecha de Siembra para sembrarlas.'
            );
        }

        return back()->with(
            'success',
            $camasAfectadas .
                ' camas fueron actualizadas correctamente.'
        );
    }

    /**
     * Actualiza la siembra vigente de una cama.
     *
     * Si la cama está disponible, crea una nueva siembra.
     */
    public function actualizarSiembra(
        Request $request,
        $idUbicacion
    ) {
        $request->validate([
            'ID_Variedad' =>
            'required|exists:dim_variedades,ID_Variedad',

            'Cantidad_Plantas' =>
            'required|numeric|min:0',

            'Estado_Siembra' =>
            'required|string',

            'Fecha_Siembra' =>
            'required|date',

            'Fecha_Pinch' =>
            'nullable|date',

            'Fecha_Hormona' =>
            'nullable|date',

            'Fecha_Erradicacion' =>
            'nullable|date',
        ]);

        $fechaSiembra =
            Carbon::parse(
                $request->Fecha_Siembra
            )->format('Y-m-d');

        /*
         * Buscamos la siembra vigente de la cama
         * para la fecha de la operación.
         */
        $siembra = $this->obtenerSiembraVigente(
            $idUbicacion,
            $fechaSiembra
        );

        /*
         * Solo tomamos los campos permitidos.
         *
         * Evitamos $request->all() para no permitir
         * que otros parámetros terminen accidentalmente
         * dentro de dim_siembras.
         */
        $datos = [
            'ID_Variedad' =>
            $request->ID_Variedad,

            'Cantidad_Plantas' =>
            $request->Cantidad_Plantas,

            'Estado_Siembra' =>
            $request->Estado_Siembra,

            'Fecha_Siembra' =>
            $fechaSiembra,

            'Fecha_Pinch' =>
            $request->filled('Fecha_Pinch')
                ? Carbon::parse(
                    $request->Fecha_Pinch
                )->format('Y-m-d')
                : null,

            'Fecha_Hormona' =>
            $request->filled('Fecha_Hormona')
                ? Carbon::parse(
                    $request->Fecha_Hormona
                )->format('Y-m-d')
                : null,

            'Fecha_Erradicacion' =>
            $request->filled(
                'Fecha_Erradicacion'
            )
                ? Carbon::parse(
                    $request->Fecha_Erradicacion
                )->format('Y-m-d')
                : null,
        ];

        /*
         * Si se proporciona fecha de erradicación,
         * automáticamente la siembra queda ERRADICADA.
         */
        if (
            !empty($datos['Fecha_Erradicacion'])
        ) {
            $datos['Estado_Siembra'] =
                'ERRADICADA';
        }

        if ($siembra) {

            /*
             * CAMA OCUPADA
             *
             * Editamos el ciclo vigente.
             */
            $siembra->update(
                $datos
            );

            $mensaje =
                'Registro de siembra actualizado correctamente.';
        } else {

            /*
             * CAMA DISPONIBLE
             *
             * Creamos un NUEVO ciclo.
             */
            $datos['ID_Ubicacion'] =
                $idUbicacion;

            /*
             * Si por alguna razón llega una nueva
             * siembra sin estado, la dejamos como
             * SEMBRADA.
             */
            if (
                empty($datos['Estado_Siembra'])
            ) {
                $datos['Estado_Siembra'] =
                    'SEMBRADA';
            }

            Siembra::create(
                $datos
            );

            $mensaje =
                'Nueva siembra creada correctamente.';
        }

        return back()->with(
            'success',
            $mensaje
        );
    }

    /**
     * Obtiene la siembra vigente de una cama
     * para una fecha determinada.
     *
     * Regla:
     *
     * Fecha_Siembra <= fecha consultada
     *
     * y además:
     *
     * Fecha_Erradicacion es NULL
     * o Fecha_Erradicacion > fecha consultada.
     *
     * Se ordena primero por fecha de siembra y luego
     * por ID para obtener el ciclo más reciente.
     */
    private function obtenerSiembraVigente(
        $idUbicacion,
        $fecha = null
    ) {
        $fecha = $fecha
            ? Carbon::parse($fecha)
            : now();

        return Siembra::with('variedad')->where(
            'ID_Ubicacion',
            $idUbicacion
        )
            ->whereDate(
                'Fecha_Siembra',
                '<=',
                $fecha->toDateString()
            )
            ->where(function ($query) use ($fecha) {

                $query
                    ->whereNull(
                        'Fecha_Erradicacion'
                    )

                    ->orWhere(
                        'Fecha_Erradicacion',
                        '0000-00-00'
                    )

                    ->orWhere(
                        'Fecha_Erradicacion',
                        '0000-00-00 00:00:00'
                    )

                    ->orWhere(
                        'Fecha_Erradicacion',
                        ''
                    )

                    ->orWhereDate(
                        'Fecha_Erradicacion',
                        '>',
                        $fecha->toDateString()
                    );
            })
            ->orderByDesc(
                'Fecha_Siembra'
            )
            ->orderByDesc(
                'ID_Siembra'
            )
            ->first();
    }
}

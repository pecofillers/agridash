<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\RendimientoController;
use App\Http\Controllers\AgronomiaController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UbicacionesController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\PlanoSiembraController;
use App\Http\Controllers\InsumosController;
use App\Http\Controllers\HistoricoSiembrasController;
use App\Http\Controllers\ComparadorSiembrasController;
use App\Http\Controllers\ProduccionReporteController;
use Illuminate\Support\Facades\Route;


// ------------------------------------------------------------------
// Autenticacion publica
// ------------------------------------------------------------------
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ------------------------------------------------------------------
// Rutas protegidas (requieren autenticacion y permisos)
// ------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('check.submodulo:vista_gerencial,ver');

    // Produccion
    Route::middleware('permiso:registro_produccion,ver')->prefix('produccion')->name('produccion.')->group(function () {
        Route::get('/', [ProduccionController::class, 'index'])->name('index')->middleware('check.submodulo:registro_produccion,registro');
    });

    // Exportar e importar produccion
    Route::get('/produccion/exportar-multinave', [ProduccionController::class, 'exportarExcelMultiNave'])->name('produccion.exportar_multinave');
    Route::post('/produccion/sincronizar_bloque', [ProduccionController::class, 'sincronizar_bloque'])->name('produccion.sincronizar_bloque');
    Route::post('/produccion/sincronizar_todo', [ProduccionController::class, 'sincronizarTodo'])->name('produccion.sincronizar_todo');
    Route::get('/produccion/estado-sincronizacion', [ProduccionController::class, 'estadoSincronizacion'])->name('produccion.estado_sincronizacion');

    // Rendimiento
    Route::middleware('permiso:rendimiento_colaboradores,ver')->prefix('rendimiento')->name('rendimiento.')->group(function () {
        Route::get('/', [RendimientoController::class, 'index'])->name('index')->middleware('check.submodulo:rendimiento_colaboradores,registro_labor');
        Route::get('/grupos', [RendimientoController::class, 'grupos'])->name('grupos')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::get('/labores', [RendimientoController::class, 'gestionLabores'])->name('labores')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/labores/guardar', [RendimientoController::class, 'guardarLaborCatalogo'])->name('guardarLaborCatalogo')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/registrar', [RendimientoController::class, 'registrarLabor'])->name('registrar')->middleware('check.submodulo:rendimiento_colaboradores,registro_labor');
        Route::post('/crear-grupo', [RendimientoController::class, 'crearGrupo'])->name('crearGrupo')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/agregar', [RendimientoController::class, 'agregarUsuario'])->name('agregar');
        Route::post('/quitar', [RendimientoController::class, 'quitarUsuario'])->name('quitar');
        Route::post('/actualizar-supervisor', [RendimientoController::class, 'actualizarSupervisorGrupo'])->name('actualizarSupervisor')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::get('/reporte', [RendimientoController::class, 'reporte'])->name('reporte')->middleware('check.submodulo:rendimiento_colaboradores,reporte_graficas');
        Route::get('/reporte-semanal', [RendimientoController::class, 'reporteSemanal'])->name('reporteSemanal')->middleware('check.submodulo:rendimiento_colaboradores,reporte_semanal');
        Route::put('/actualizar/{id}', [RendimientoController::class, 'actualizarLabor'])->name('actualizar');
        Route::delete('/eliminar/{id}', [RendimientoController::class, 'eliminarLabor'])->name('eliminar');
        Route::delete('/labores/eliminar/{id}', [RendimientoController::class, 'eliminarLaborCatalogo'])->name('eliminarLaborCatalogo')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
    });

    // Agronomia
    Route::middleware('permiso:agronomia,ver')->prefix('agronomia')->name('agronomia.')->group(function () {
        Route::get('/', [AgronomiaController::class, 'index'])->name('index')->middleware('check.submodulo:agronomia,siembra');
        Route::post('/siembra', [AgronomiaController::class, 'registrarSiembra'])->name('siembra')->middleware('check.submodulo:agronomia,siembra');
        Route::post('/variedad', [AgronomiaController::class, 'crearVariedad'])->name('variedad')->middleware('check.submodulo:agronomia,variedades');
        Route::put('/{id}', [AgronomiaController::class, 'actualizar'])->name('actualizar');
        // Importación / Exportación masiva
        Route::get('/exportar-multibloque', [AgronomiaController::class, 'exportarSiembrasMultiBloque'])->name('exportar_multibloque')->middleware('check.submodulo:agronomia,siembra');
        Route::post('/importar-multibloque', [AgronomiaController::class, 'importarSiembrasMultiBloque'])->name('importar_multibloque')->middleware('check.submodulo:agronomia,siembra');

        // Exportar comparativa de siembras
        Route::get('/consolidado-bloque', [AgronomiaController::class, 'consolidadoBloque'])->name('consolidado_bloque')->middleware('check.submodulo:agronomia,consolidado_bloque');
        Route::get('/insumos', [InsumosController::class, 'index'])->name('insumos')->middleware('check.submodulo:agronomia,insumos');
        Route::post('/insumos/guardar', [InsumosController::class, 'store'])->name('insumos.guardar')->middleware('check.submodulo:agronomia,insumos');
        Route::get('/insumos/{insumo}/editar', [InsumosController::class, 'edit'])->name('insumos.editar')->middleware('check.submodulo:agronomia,insumos');
        Route::put('/insumos/{insumo}', [InsumosController::class, 'update'])->name('insumos.actualizar')->middleware('check.submodulo:agronomia,insumos');

        //Historico de siembras
        Route::get('/historico-siembras', [HistoricoSiembrasController::class, 'index'])->name('historico_siembras')->middleware('check.submodulo:agronomia,historico_siembras');
        Route::get('/siembras-bloque/{bloque}', [HistoricoSiembrasController::class, 'siembrasBloque'])->name('siembras_bloque')->middleware('check.submodulo:agronomia,historico_siembras');

        // Comparador Siembras.
        Route::get('/comparador-siembras',[ComparadorSiembrasController::class, 'index'])->name('comparador-siembras')->middleware('check.submodulo:agronomia,comparador-siembras');

        // Reporte de Producción
        Route::get('/reporte-produccion', [ProduccionReporteController::class, 'index'])->name('reporte-produccion')->middleware('check.submodulo:agronomia,reporte-produccion');
    });

    // ------------------------------------------------------------------
    // Planos de Siembra Interactivos (Vista tipo Excel)
    // ------------------------------------------------------------------
    Route::middleware('permiso:agronomia,ver')->prefix('planos')->name('plano_siembra.')->group(function () {
        Route::get('/', [PlanoSiembraController::class, 'index'])->name('index');
        Route::put('/actualizar-siembra/{idUbicacion}', [PlanoSiembraController::class, 'actualizarSiembra'])->name('actualizar_siembra');
        Route::put('/actualizar-masivo', [PlanoSiembraController::class, 'actualizarMasivo'])->name('actualizar_masivo');
    });

    // Gestion de usuarios
    Route::middleware('permiso:gestion_usuarios,ver')->prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsuariosController::class, 'index'])->name('index')->middleware('check.submodulo:gestion_usuarios,directorio');
        Route::post('/crear', [UsuariosController::class, 'crear'])->name('crear')->middleware('check.submodulo:gestion_usuarios,registrar');
        Route::post('/estado', [UsuariosController::class, 'cambiarEstado'])->name('estado')->middleware('check.submodulo:gestion_usuarios,directorio');
    });

    // Roles     
    Route::middleware('permiso:administracion_roles,ver')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('index')->middleware('check.submodulo:administracion_roles,editar');
        Route::post('/guardar', [RolesController::class, 'guardar'])->name('guardar')->middleware('check.submodulo:administracion_roles,editar');
        Route::post('/borrar', [RolesController::class, 'borrar'])->name('borrar')->middleware('check.submodulo:administracion_roles,eliminar');
    });

    // Ubicaciones
    Route::middleware('permiso:administracion_ubicaciones,ver')->prefix('ubicaciones')->name('ubicaciones.')->group(function () {
        Route::get('/', [UbicacionesController::class, 'index'])->name('index')->middleware('check.submodulo:administracion_ubicaciones,listado');
        Route::post('/crear', [UbicacionesController::class, 'crear'])->name('crear')->middleware('check.submodulo:administracion_ubicaciones,listado');
        Route::put('/actualizar/{id}', [UbicacionesController::class, 'actualizar'])->name('actualizar');
    });

    // Configuracion
    Route::middleware('permiso:configuracion,ver')->prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])->name('index');

        // Sub-tab: Gestion de usuarios y estados
        Route::post('/crear-usuario', [ConfiguracionController::class, 'crearUsuario'])->name('crear_usuario')->middleware('check.submodulo:configuracion,usuarios');
        Route::post('/cambiar-estado', [ConfiguracionController::class, 'cambiarEstado'])->name('cambiar_estado')->middleware('check.submodulo:configuracion,usuarios');
        Route::post('/desbloquear', [ConfiguracionController::class, 'desbloquear'])->name('desbloquear')->middleware('check.submodulo:configuracion,usuarios');

        // Sub-tab: Cambio de contrasenas
        Route::post('/cambiar-contrasena', [ConfiguracionController::class, 'cambiarContrasena'])->name('cambiar_contrasena');
        Route::post('/restablecer-contrasena', [ConfiguracionController::class, 'restablecerContrasena'])->name('restablecer_contrasena')->middleware('check.submodulo:configuracion,credenciales');

        // Produccion
        Route::get('/configuracion-bloques', [ProduccionController::class, 'configuracionEnlaces'])->name('bloques.configuracion')->middleware('check.submodulo:configuracion,bloques');
        Route::post('/configuracion-bloques/guardar', [ProduccionController::class, 'guardarEnlace'])->name('bloques.guardar')->middleware('check.submodulo:configuracion,bloques');
        Route::post('/configuracion-bloques/crear', [ProduccionController::class, 'crearBloque'])->name('bloques.crear')->middleware('check.submodulo:configuracion,bloques');
    });
});

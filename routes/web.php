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
        Route::post('/guardar', [ProduccionController::class, 'guardar'])->name('guardar');
        Route::post('/actualizar', [ProduccionController::class, 'actualizar'])->name('actualizar');
    });

// Rendimiento
    Route::middleware('permiso:rendimiento_colaboradores,ver')->prefix('rendimiento')->name('rendimiento.')->group(function () {
Route::get('/', [RendimientoController::class, 'index'])->name('index')->middleware('check.submodulo:rendimiento_colaboradores,registro_labor');
        Route::get('/grupos', [RendimientoController::class, 'grupos'])->name('grupos')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/registrar', [RendimientoController::class, 'registrarLabor'])->name('registrar')->middleware('check.submodulo:rendimiento_colaboradores,registro_labor');
        Route::post('/crear-grupo', [RendimientoController::class, 'crearGrupo'])->name('crearGrupo')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/agregar', [RendimientoController::class, 'agregarColaborador'])->name('agregar')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
Route::post('/quitar', [RendimientoController::class, 'quitarColaborador'])->name('quitar')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::post('/actualizar-supervisor', [RendimientoController::class, 'actualizarSupervisorGrupo'])->name('actualizarSupervisor')->middleware('check.submodulo:rendimiento_colaboradores,gestion_grupos');
        Route::get('/reporte', [RendimientoController::class, 'reporte'])->name('reporte')->middleware('check.submodulo:rendimiento_colaboradores,reporte_graficas');
        Route::get('/reporte-semanal', [RendimientoController::class, 'reporteSemanal'])->name('reporteSemanal')->middleware('check.submodulo:rendimiento_colaboradores,reporte_semanal');
    });

// Agronomia
    Route::middleware('permiso:agronomia,ver')->prefix('agronomia')->name('agronomia.')->group(function () {
        Route::get('/', [AgronomiaController::class, 'index'])->name('index')->middleware('check.submodulo:agronomia,historial');
        Route::post('/siembra', [AgronomiaController::class, 'registrarSiembra'])->name('siembra')->middleware('check.submodulo:agronomia,siembra');
        Route::post('/variedad', [AgronomiaController::class, 'crearVariedad'])->name('variedad')->middleware('check.submodulo:agronomia,variedades');
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
        Route::post('/crear', [UbicacionesController::class, 'crear'])->name('crear')->middleware('check.submodulo:administracion_ubicaciones,crear');
    });

// Configuracion
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
    });
});

<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Grupo;
use App\Models\Produccion;
use App\Models\RendimientoLabor;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\Rbac;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
public function index()
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = auth()->user();
        $rolId = $usuario?->ID_Rol;

        $menu = Rbac::menuPorRol($rolId);

        // Estadisticas basicas para el dashboard
        $colaboradoresActivos = Colaborador::where('Estado', 'ACTIVO')->count();
        $usuarios = Usuario::count();
        $grupos = Grupo::count();
        $ubicaciones = Ubicacion::count();

        return view('dashboard', compact('menu', 'colaboradoresActivos', 'usuarios', 'grupos', 'ubicaciones'));
    }
}

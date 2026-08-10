<?php

namespace App\Http\Controllers;

use App\Models\PermisoRol;
use App\Models\Rol;
use App\Support\Rbac;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
$roles = Rol::orderBy('Nombre_Rol')->get();
        $rolSeleccionado = null;
        $permisos = collect();

        $idRol = request('rol');
        if ($idRol) {
            $rolSeleccionado = Rol::find($idRol);
            $permisos = $rolSeleccionado
                ? PermisoRol::where('ID_Rol', $rolSeleccionado->ID_Rol)->get()
                : collect();
        }

        return view('roles.index', [
            'roles' => $roles,
            'rolSeleccionado' => $rolSeleccionado,
            'permisos' => $permisos,
            'modulos' => Rbac::MODULOS,
            'submodulos' => Rbac::SUBMODULOS,
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'Nombre_Rol' => 'required|string|max:50',
            'Descripcion' => 'nullable|string|max:255',
        ]);

        $nombre = strtoupper(trim($request->Nombre_Rol));

        // Crear o actualizar el rol
        $rol = Rol::where('Nombre_Rol', $nombre)->first();
        if ($rol) {
            $rol->update(['Descripcion' => $request->Descripcion]);
        } else {
            $rol = Rol::create(['Nombre_Rol' => $nombre, 'Descripcion' => $request->Descripcion]);
        }

        // Borrar permisos viejos
        PermisoRol::where('ID_Rol', $rol->ID_Rol)->delete();

        // Insertar permisos por pestaña (submódulo). El acceso a un módulo se
        // deriva de tener al menos una pestaña concedida. No se usan acciones
        // de módulo (ver/crear/editar/eliminar).
        $modulos = $request->input('submodulos', []);
        foreach ($modulos as $modulo => $subsConcedidos) {
            foreach (Rbac::SUBMODULOS[$modulo] ?? [] as $claveSub => $label) {
                PermisoRol::create([
                    'ID_Rol' => $rol->ID_Rol,
                    'Modulo' => $modulo,
                    'Submodulo' => $claveSub,
                    'Permiso_Ver' => in_array($claveSub, $subsConcedidos, true),
                ]);
            }
        }

        Rbac::limpiarCache();

        return back()->with('success', "Permisos actualizados para el rol $nombre.");
    }

    public function borrar(Request $request)
    {
        $request->validate(['ID_Rol' => 'required|integer']);

        $rol = Rol::find($request->ID_Rol);
        if (!$rol) {
            return back()->with('error', 'El rol no existe.');
        }

        PermisoRol::where('ID_Rol', $rol->ID_Rol)->delete();
        $rol->delete();
        Rbac::limpiarCache();

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}

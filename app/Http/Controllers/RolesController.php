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
            'ID_Rol' => 'nullable|integer', // Agregamos el ID para distinguir edición de creación
            'Nombre_Rol' => 'required|string|max:50',
            'Descripcion' => 'nullable|string|max:255',
        ]);

        $nombre = strtoupper(trim($request->Nombre_Rol));

        // 1. Crear o actualizar el rol
        if ($request->ID_Rol) {
            // Modo Edición
            $rol = Rol::findOrFail($request->ID_Rol);
            $rol->update([
                'Nombre_Rol' => $nombre,
                'Descripcion' => $request->Descripcion
            ]);
            $mensaje = "Rol y permisos actualizados correctamente.";
        } else {
            // Modo Creación (o sobrescribir si escriben el mismo nombre)
            $rol = Rol::where('Nombre_Rol', $nombre)->first();
            if ($rol) {
                $rol->update(['Descripcion' => $request->Descripcion]);
            } else {
                $rol = Rol::create(['Nombre_Rol' => $nombre, 'Descripcion' => $request->Descripcion]);
            }
            $mensaje = "Rol $nombre creado correctamente.";
        }

        // 2. Borrar permisos viejos
        PermisoRol::where('ID_Rol', $rol->ID_Rol)->delete();

        // 3. Insertar permisos por pestaña (submódulo)
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

        // Redirigir limpiando el parámetro ?rol= de la URL
        return redirect()->route('roles.index')->with('success', $mensaje);
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

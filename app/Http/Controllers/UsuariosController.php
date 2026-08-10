<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('rol')->orderBy('Username')->get();
        $roles = Rol::orderBy('Nombre_Rol')->get();
        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function crear(UsuarioRequest $request)
    {
        Usuario::create([
            'Username' => strtolower(trim($request->Username)),
            'Nombre' => $request->Nombre,
            'Apellidos' => $request->Apellidos,
            'Telefono' => $request->Telefono,
            'Correo' => strtolower($request->Correo),
            'Password_Hash' => Hash::make($request->Password),
            'ID_Rol' => $request->ID_Rol,
            'Estado' => 'ACTIVO',
            'Intentos_Fallidos' => 0,
        ]);

        return back()->with('success', 'Usuario registrado correctamente.');
    }

public function cambiarEstado(Request $request)
    {
        $request->validate([
            'ID_Usuario' => 'required|integer',
            'Estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        // Impedir que un usuario se desactive a si mismo
        if ((int) $request->ID_Usuario === (int) auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        Usuario::where('ID_Usuario', $request->ID_Usuario)->update(['Estado' => $request->Estado]);

        return back()->with('success', 'Estado actualizado.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        // 1. Capturamos el término de búsqueda si existe
        $buscar = $request->input('buscar');

        // 2. Iniciamos la consulta base
        $query = Usuario::with('rol');

        // 3. Aplicamos los filtros de búsqueda si el usuario escribió algo
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('Username', 'LIKE', "%{$buscar}%")
                  ->orWhere('Nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('Apellidos', 'LIKE', "%{$buscar}%")
                  ->orWhere('Correo', 'LIKE', "%{$buscar}%");
            });
        }

        // 4. Ejecutamos la consulta
        $usuarios = $query->orderBy('Username')->get();
        $roles = Rol::orderBy('Nombre_Rol')->get();

        // Buscar si se solicitó editar un usuario
        $usuarioSeleccionado = null;
        if ($request->has('usuario')) {
            $usuarioSeleccionado = Usuario::find($request->usuario);
        }

        // Pasamos la variable $buscar a la vista para mantener el texto en el input
        return view('usuarios.index', compact('usuarios', 'roles', 'usuarioSeleccionado', 'buscar'));
    }

    public function crear(Request $request)
    {
        $esEdicion = $request->filled('ID_Usuario');

        // Validaciones dinámicas (ignora el Username duplicado si es el mismo usuario)
        $request->validate([
            'ID_Usuario' => 'nullable|integer',
            'Username'   => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:dim_usuarios,Username,' . $request->ID_Usuario . ',ID_Usuario'],
            'Nombre'     => ['required', 'string', 'max:100'],
            'Apellidos'  => ['nullable', 'string', 'max:100'],
            'Telefono'   => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-() ]*$/'],
            'Correo'     => ['nullable', 'email', 'max:150'],
            // La contraseña solo es obligatoria si estamos creando un usuario nuevo
            'Password'   => [$esEdicion ? 'nullable' : 'required', \Illuminate\Validation\Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'ID_Rol'     => ['required', 'integer', 'exists:dim_roles,ID_Rol'],
        ]);

        if ($esEdicion) {
            // MODO EDICIÓN
            $usuario = Usuario::findOrFail($request->ID_Usuario);
            
            $datosActualizar = [
                'Username'  => strtolower(trim($request->Username)),
                'Nombre'    => $request->Nombre,
                'Apellidos' => $request->Apellidos,
                'Telefono'  => $request->Telefono,
                'Correo'    => strtolower($request->Correo),
                'ID_Rol'    => $request->ID_Rol,
            ];

            // Solo actualizamos la contraseña si escribieron una nueva
            if ($request->filled('Password')) {
                $datosActualizar['Password_Hash'] = Hash::make($request->Password);
            }

            $usuario->update($datosActualizar);
            $mensaje = 'Usuario actualizado correctamente.';
        } else {
            // MODO CREACIÓN
            Usuario::create([
                'Username'          => strtolower(trim($request->Username)),
                'Nombre'            => $request->Nombre,
                'Apellidos'         => $request->Apellidos,
                'Telefono'          => $request->Telefono,
                'Correo'            => strtolower($request->Correo),
                'Password_Hash'     => Hash::make($request->Password),
                'ID_Rol'            => $request->ID_Rol,
                'Estado'            => 'ACTIVO',
                'Intentos_Fallidos' => 0,
            ]);
            $mensaje = 'Usuario registrado correctamente.';
        }

        // Redirigimos al index limpiando la URL
        return redirect()->route('usuarios.index')->with('success', $mensaje);
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

<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ConfiguracionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = auth()->user();
        $usuarios = Usuario::with('rol')->orderBy('Username')->get();
        $roles = Rol::orderBy('Nombre_Rol')->get();

        return view('configuracion.index', compact('usuario', 'usuarios', 'roles'));
    }

    // ------------------------------------------------------------------
    // SUB-TAB: GESTION DE USUARIOS Y ESTADOS
    // ------------------------------------------------------------------

    /** Crear nuevo usuario (solo admin). */
    public function crearUsuario(Request $request)
    {
        $request->validate([
            'Username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:dim_usuarios,Username'],
            'Nombre' => ['required', 'string', 'max:100'],
            'Apellidos' => ['nullable', 'string', 'max:100'],
            'Telefono' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-() ]*$/'],
            'Correo' => ['nullable', 'email', 'max:150'],
            'Password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'ID_Rol' => ['required', 'integer', 'exists:dim_roles,ID_Rol'],
        ]);

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

    /** Cambiar estado (ACTIVO/INACTIVO) de un usuario. */
    public function cambiarEstado(Request $request)
    {
        $request->validate([
            'ID_Usuario' => 'required|integer',
            'Estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        // Impedir que un usuario desactive su propia cuenta
        if ((int) $request->ID_Usuario === (int) auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        Usuario::where('ID_Usuario', $request->ID_Usuario)->update(['Estado' => $request->Estado]);

        return back()->with('success', 'Estado actualizado.');
    }

    /** Desbloquear cuenta (resetear intentos fallidos y bloqueo). */
    public function desbloquear(Request $request)
    {
        $request->validate(['ID_Usuario' => 'required|integer']);

        Usuario::where('ID_Usuario', $request->ID_Usuario)->update([
            'Intentos_Fallidos' => 0,
            'Bloqueado_Hasta' => null,
        ]);

        return back()->with('success', 'Cuenta desbloqueada correctamente.');
    }

    // ------------------------------------------------------------------
    // SUB-TAB: CAMBIO DE CONTRASEÑAS
    // ------------------------------------------------------------------

    /** Cambiar mi propia contraseña. */
    public function cambiarContrasena(Request $request)
    {
        $request->validate([
            'password_actual' => 'required|string',
            // Regla nativa de Laravel: minima longitud + letras + mayusculas + numeros + simbolos
            'password_nueva' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        /** @var \App\Models\Usuario $usuario */
        $usuario = auth()->user();

        if (!Hash::check($request->password_actual, $usuario->Password_Hash)) {
            return back()->with('error', 'La contrasena actual es incorrecta.');
        }

        // Impedir reutilizar la misma contrasena
        if (Hash::check($request->password_nueva, $usuario->Password_Hash)) {
            return back()->with('error', 'La nueva contrasena no puede ser igual a la actual.');
        }

        $usuario->Password_Hash = Hash::make($request->password_nueva);
        $usuario->Intentos_Fallidos = 0;
        $usuario->Bloqueado_Hasta = null;
        $usuario->save();

        // Regenerar el hash del token de sesion (seguridad nativa tras cambiar credenciales)
        $request->session()->regenerate();

        return back()->with('success', 'Contrasena actualizada correctamente.');
    }

    /** Restablecer contrasena de otro usuario (solo admin). */
    public function restablecerContrasena(Request $request)
    {
        $request->validate([
            'ID_Usuario' => 'required|integer',
            'Password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $usuario = Usuario::find($request->ID_Usuario);
        if (!$usuario) {
            return back()->with('error', 'El usuario no existe.');
        }

        $usuario->Password_Hash = Hash::make($request->Password);
        $usuario->Intentos_Fallidos = 0;
        $usuario->Bloqueado_Hasta = null;
        $usuario->save();

        return back()->with('success', "Contrasena restablecida para {$usuario->Username}.");
    }
}

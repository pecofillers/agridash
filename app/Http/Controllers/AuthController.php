<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /** Duracion de la sesion por inactividad (minutos). */
    protected $minutosInactividad = 30;

    /** Maximos intentos fallidos por IP en la ventana. */
    protected $maxIntentosIP = 20;

    /** Ventana de tiempo para el rate limit por IP (minutos). */
    protected $minutosVentanaIP = 15;

    public function mostrarLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $username = strtolower(trim($request->username));
        $ip = $request->ip();

        // Rate limit por IP (anti fuerza bruta / DDoS)
        if (RateLimiter::tooManyAttempts('ip:'.$ip, $this->maxIntentosIP)) {
            return back()->withErrors([
                'login' => 'Demasiados intentos desde tu IP. Intenta de nuevo en unos minutos.',
            ])->withInput();
        }

        $usuario = Usuario::where('Username', $username)->first();

        // Mensaje generico (no revelar si el usuario existe)
        $mensajeGenerico = 'Usuario o contrasena incorrectos.';

        if (!$usuario || $usuario->Estado !== 'ACTIVO') {
            RateLimiter::hit('ip:'.$ip, $this->minutosVentanaIP * 60);
            return back()->withErrors(['login' => $mensajeGenerico])->withInput();
        }

        // Bloqueo por intentos fallidos persistentes en BD
        if ($usuario->Bloqueado_Hasta && now()->lt($usuario->Bloqueado_Hasta)) {
            RateLimiter::hit('ip:'.$ip, $this->minutosVentanaIP * 60);
            return back()->withErrors([
                'login' => 'Cuenta bloqueada temporalmente por multiples intentos fallidos. Intenta de nuevo mas tarde.',
            ])->withInput();
        }

// Verificar contrasena (Password_Hash usa bcrypt).
        // Usamos password_verify() en lugar de Hash::check() porque los
        // hashes generados por Python (bcrypt) usan el prefijo $2b$, que
        // PHP/password_verify soporta de forma nativa.
        if (!password_verify($request->password, $usuario->Password_Hash)) {
            $usuario->increment('Intentos_Fallidos');
            if ($usuario->Intentos_Fallidos >= 5) {
                $usuario->Bloqueado_Hasta = now()->addMinutes(15);
                $usuario->save();
            }
            RateLimiter::hit('ip:'.$ip, $this->minutosVentanaIP * 60);
            return back()->withErrors(['login' => $mensajeGenerico])->withInput();
        }

        // Login exitoso: resetear contadores
        $usuario->Intentos_Fallidos = 0;
        $usuario->Bloqueado_Hasta = null;
        $usuario->save();

        Auth::login($usuario, $request->boolean('remember'));

        // Guardar rol en sesion para el menu
        $rol = Rol::find($usuario->ID_Rol);
        session(['rol_nombre' => $rol?->Nombre_Rol ?? 'SIN ROL']);
        session(['rol_id' => $usuario->ID_Rol]);

        // Limpiar rate limiter de esta IP tras login exitoso
        RateLimiter::clear('ip:'.$ip);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

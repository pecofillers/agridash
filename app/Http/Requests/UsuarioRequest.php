<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:dim_usuarios,Username'],
            'Nombre' => ['required', 'string', 'max:100'],
            'Apellidos' => ['nullable', 'string', 'max:100'],
            'Telefono' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-() ]*$/'],
            'Correo' => ['nullable', 'email', 'max:150'],
            'Password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'ID_Rol' => ['required', 'integer', 'exists:dim_roles,ID_Rol'],
        ];
    }
}

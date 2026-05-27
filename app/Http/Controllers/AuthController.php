<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validamos que nos envíen forzosamente un correo y una contraseña
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Buscamos al usuario en la base de datos (PostgreSQL)
        $user = User::where('email', $request->email)->first();

        // 3. Verificamos que el usuario exista y la contraseña sea correcta
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Si falla, regresamos un error de seguridad
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 4. ¡Éxito! Generamos el Token de acceso usando Laravel Sanctum
        $token = $user->createToken('token_condominio')->plainTextToken;

        // 5. Le enviamos a Vue el token y los datos del usuario
        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso',
            'access_token' => $token,
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ]
        ]);
    }
}
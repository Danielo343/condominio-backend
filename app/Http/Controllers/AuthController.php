<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string',
            'role'     => 'required|in:admin,residente',
        ]);

        if ($validator->fails()) {
            return response()->json(['errores' => $validator->errors()], 422);
        }

        if (User::where('email', $request->email)->exists()) {
            return response()->json(['mensaje' => 'El correo electrónico ya está registrado.'], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => $request->role,
        ]);

        $token = $user->createToken('dispositivo_token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Usuario registrado exitosamente',
            'usuario' => $user,
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['mensaje' => 'Credenciales incorrectas'], 401);
        }

        $deviceName = $request->header('User-Agent', 'Dispositivo Desconocido');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso',
            'usuario' => $user,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Sesión cerrada correctamente']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'clave_actual' => 'required',
            'nueva_clave'  => 'required|min:6',
        ]);

        $user = $request->user();

        if (!Hash::check($request->clave_actual, $user->password)) {
            return response()->json(['mensaje' => 'La contraseña actual es incorrecta'], 422);
        }

        $user->password = Hash::make($request->nueva_clave);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente. Se ha cerrado sesión en todos los dispositivos.'
        ]);
    }
}
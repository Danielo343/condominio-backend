<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Residente;
use App\Models\PersonalAccessToken;
use App\Mail\CodigoRecuperacionMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Inicio de Sesión
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Si existe en residentes pero aún no en users, se crea en automático
        if (!$user) {
            $residente = Residente::where('email', $email)->first();
            if ($residente) {
                $user = User::create([
                    'name'     => $residente->nombre,
                    'email'    => $email,
                    'password' => Hash::make('Condo1234'),
                    'role'     => $residente->rol ?? 'Residente'
                ]);
            }
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401);
        }

        $token = Str::random(60);
        $tokenId = (string) Str::uuid();

        PersonalAccessToken::create([
            'token_id'       => $tokenId,
            'tokenable_type' => get_class($user),
            'tokenable_id'   => (string) $user->_id,
            'name'           => $request->header('User-Agent', 'Dispositivo_' . Str::random(5)),
            'token'          => hash('sha256', $token),
            'abilities'      => ['*'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Inicio de sesión exitoso.',
            'token' => $tokenId . '|' . $token,
            'user' => [
                'id' => (string) $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'Administrador'
            ]
        ], 200);
    }

    /**
     * Cierre de Sesión
     */
    public function logout(Request $request)
    {
        $bearerToken = $request->bearerToken();
        if ($bearerToken && str_contains($bearerToken, '|')) {
            [$tokenId] = explode('|', $bearerToken, 2);
            PersonalAccessToken::where('token_id', $tokenId)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.'
        ], 200);
    }

    public function userProfile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ]);
    }

    /**
     * Solicitar Código de 6 Dígitos por Correo
     */
    public function solicitarCodigo(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Sincronización automática si el correo está en residentes
        if (!$user) {
            $residente = Residente::where('email', $email)->first();
            if ($residente) {
                $user = User::create([
                    'name'     => $residente->nombre,
                    'email'    => $email,
                    'password' => Hash::make('Condo1234'),
                    'role'     => $residente->rol ?? 'Residente'
                ]);
            }
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró ninguna cuenta registrada con este correo.'
            ], 404);
        }

        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->reset_code = $codigo;
        $user->reset_code_expires_at = Carbon::now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->email)->send(new CodigoRecuperacionMail($codigo));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Se ha enviado un código de 6 dígitos a tu correo electrónico.'
        ], 200);
    }

    /**
     * Restablecer Contraseña
     */
    public function restablecerConCodigo(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'codigo'   => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user || $user->reset_code !== $request->codigo) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código introducido es incorrecto.'
            ], 400);
        }

        if (Carbon::now()->greaterThan($user->reset_code_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código de recuperación ha expirado.'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_code = null;
        $user->reset_code_expires_at = null;
        $user->save();

        // Cierra sesión en todos los demás dispositivos
        PersonalAccessToken::where('tokenable_id', (string) $user->_id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña restablecida correctamente.'
        ], 200);
    }

    /**
     * Cambiar Contraseña desde Configuración
     */
    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'      => 'required|string|min:6|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña actual no es correcta.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        PersonalAccessToken::where('tokenable_id', (string) $user->_id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente.'
        ], 200);
    }

    /**
     * Actualizar Perfil
     */
    public function actualizarPerfil(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50'
        ]);

        if (isset($validated['email'])) {
            $validated['email'] = strtolower(trim($validated['email']));
        }

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user
        ], 200);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\MensajeChat;
use App\Models\User;
use App\Events\MensajeEnviado;
use Illuminate\Http\Request;

class ControladorChat extends Controller
{
    public function obtenerMensajes()
    {
        try {
            $mensajes = MensajeChat::orderBy('created_at', 'asc')->get();

            // Obtenemos los IDs de usuarios activos en MongoDB
            $usuariosActivosIds = User::all()->pluck('_id')->map(fn($id) => (string)$id)->toArray();

            $data = $mensajes->map(function ($msg) use ($usuariosActivosIds) {
                $esUsuarioActivo = in_array((string)$msg->user_id, $usuariosActivosIds);
                return [
                    '_id'            => (string)$msg->_id,
                    'user_id'        => (string)$msg->user_id,
                    'nombre_usuario' => $msg->nombre_usuario,
                    'mensaje'        => $msg->mensaje,
                    'hora'           => $msg->hora,
                    'antiguo'        => !$esUsuarioActivo
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener mensajes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function enviarMensaje(Request $request)
    {
        try {
            $request->validate([
                'mensaje' => 'required|string|max:1000'
            ]);

            $user = $request->user();

            $mensaje = MensajeChat::create([
                'user_id'        => (string) $user->_id,
                'nombre_usuario' => $user->name,
                'mensaje'        => $request->mensaje,
                'hora'           => now()->format('H:i')
            ]);

            try {
                broadcast(new MensajeEnviado($mensaje))->toOthers();
            } catch (\Exception $e) {
                // Silencioso si Reverb está en segundo plano
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    '_id'            => (string)$mensaje->_id,
                    'user_id'        => (string)$mensaje->user_id,
                    'nombre_usuario' => $mensaje->nombre_usuario,
                    'mensaje'        => $mensaje->mensaje,
                    'hora'           => $mensaje->hora,
                    'antiguo'        => false
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al enviar mensaje: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\MensajeChat;
use App\Events\MensajeEnviado;
use Illuminate\Http\Request;

class ControladorChat extends Controller
{
    public function obtenerMensajes()
    {
        try {
            $mensajes = MensajeChat::get();

            return response()->json([
                'status' => 'success',
                'data' => $mensajes
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

            // Intentar transmitir por WebSocket si Reverb está activo
            try {
                broadcast(new MensajeEnviado($mensaje))->toOthers();
            } catch (\Exception $e) {
                // Si Reverb no está iniciado, el mensaje igual se guarda en MongoDB
            }

            return response()->json([
                'status' => 'success',
                'data' => $mensaje
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al enviar mensaje: ' . $e->getMessage()
            ], 500);
        }
    }
}
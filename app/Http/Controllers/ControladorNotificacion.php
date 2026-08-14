<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class ControladorNotificacion extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $userId = (string) $user->_id;

            $notificaciones = Notificacion::orderBy('created_at', 'desc')->take(15)->get();

            $data = $notificaciones->map(function ($notif) use ($userId) {
                $leidos = $notif->leido_por ?? [];
                return [
                    'id'        => (string) $notif->_id,
                    'titulo'    => $notif->titulo,
                    'mensaje'   => $notif->mensaje,
                    'tipo'      => $notif->tipo ?? 'general',
                    'leida'     => in_array($userId, $leidos),
                    'tiempo'    => $notif->created_at ? $notif->created_at->diffForHumans() : 'Reciente'
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cargar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function marcarTodasLeidas(Request $request)
    {
        try {
            $user = $request->user();
            $userId = (string) $user->_id;

            $notificaciones = Notificacion::all();
            foreach ($notificaciones as $notif) {
                $leidos = $notif->leido_por ?? [];
                if (!in_array($userId, $leidos)) {
                    $leidos[] = $userId;
                    $notif->leido_por = $leidos;
                    $notif->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Notificaciones marcadas como leídas.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
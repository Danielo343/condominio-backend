<?php

namespace App\Http\Controllers;

use App\Models\Residente;
use App\Models\Evento;
use App\Models\Notificacion;
use App\Models\ConfiguracionCondominio;
use App\Events\NotificacionCreada;
use Illuminate\Http\Request;

class ControladorDashboard extends Controller
{
    public function obtenerResumen()
    {
        try {
            $totalResidentes = Residente::count();
            $unidadesOcupadas = Residente::whereNotNull('unidad')->distinct('unidad')->count();
            $capacidadTotal = 150;
            $porcentajeOcupacion = $capacidadTotal > 0 ? round(($unidadesOcupadas / $capacidadTotal) * 100, 1) : 0;

            $config = ConfiguracionCondominio::where('clave_config', 'general_config')->first();
            $cuota = $config->cuota_mantenimiento ?? 1500;
            $ingresosMes = $unidadesOcupadas * $cuota;

            $eventos = Evento::orderBy('fecha', 'asc')->take(5)->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_residentes'     => $totalResidentes,
                    'unidades_ocupadas'    => $unidadesOcupadas,
                    'capacidad_total'      => $capacidadTotal,
                    'porcentaje_ocupacion' => $porcentajeOcupacion,
                    'ingresos_mes'         => $ingresosMes,
                    'total_eventos'        => $eventos->count(),
                    'lista_eventos'        => $eventos,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al calcular resumen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearEvento(Request $request)
    {
        try {
            $validated = $request->validate([
                'titulo'    => 'required|string|max:255',
                'fecha'     => 'required|string',
                'hora'      => 'required|string|max:50',
                'lugar'     => 'required|string|max:255',
                'categoria' => 'nullable|string|max:100'
            ]);

            $evento = Evento::create($validated);

            // 1. Guardar Notificación persistente en MongoDB Atlas
            $notificacion = Notificacion::create([
                'titulo'    => 'Nuevo Evento: ' . $evento->titulo,
                'mensaje'   => $evento->fecha . ' a las ' . $evento->hora . ' en ' . $evento->lugar,
                'tipo'      => 'evento',
                'leido_por' => []
            ]);

            // 2. Transmitir por WebSocket en vivo a todos los usuarios conectados
            try {
                broadcast(new NotificacionCreada($notificacion))->toOthers();
            } catch (\Exception $e) {
                // Silencioso si Reverb está en segundo plano
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Evento programado y notificación enviada a los residentes.',
                'data' => $evento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al programar evento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function eliminarEvento($id)
    {
        try {
            $evento = Evento::find($id);
            if (!$evento) {
                return response()->json(['status' => 'error', 'message' => 'Evento no encontrado.'], 404);
            }

            $evento->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Evento eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar evento: ' . $e->getMessage()
            ], 500);
        }
    }
}
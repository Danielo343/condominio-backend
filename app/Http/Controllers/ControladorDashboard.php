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

            // Obtenemos la configuración dinámica
            $config = ConfiguracionCondominio::where('clave_config', 'general_config')->first();
            $capacidadTotal = $config->capacidad_total ?? 50;
            $cuota = $config->cuota_mantenimiento ?? 1500;

            $porcentajeOcupacion = $capacidadTotal > 0 ? round(($unidadesOcupadas / $capacidadTotal) * 100, 1) : 0;
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

            $notificacion = Notificacion::create([
                'titulo'    => 'Nuevo Evento: ' . $evento->titulo,
                'mensaje'   => $evento->fecha . ' a las ' . $evento->hora . ' en ' . $evento->lugar,
                'tipo'      => 'evento',
                'leido_por' => []
            ]);

            try {
                broadcast(new NotificacionCreada($notificacion))->toOthers();
            } catch (\Exception $e) {}

            return response()->json([
                'status' => 'success',
                'message' => 'Evento programado correctamente.',
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
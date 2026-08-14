<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Residente;
use App\Models\Notificacion;
use App\Events\NotificacionCreada;
use Illuminate\Http\Request;

class ControladorFactura extends Controller
{
    /**
     * Listar facturas (Admin ve todas, Residente solo las suyas)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Factura::query();

            // Si es residente, filtrar estrictamente por su correo
            if ($user->role !== 'Administrador') {
                $query->where('email_residente', strtolower(trim($user->email)));
            }

            if ($request->filled('estado') && $request->estado !== 'Todos') {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_residente', 'like', "%{$search}%")
                      ->orWhere('unidad', 'like', "%{$search}%")
                      ->orWhere('folio', 'like', "%{$search}%")
                      ->orWhere('concepto', 'like', "%{$search}%");
                });
            }

            $facturas = $query->orderBy('created_at', 'desc')->get();

            // Si la colección está vacía, sembramos datos de demostración
            if ($facturas->isEmpty() && !$request->filled('search')) {
                Factura::create([
                    'folio'             => 'REC-2026-001',
                    'nombre_residente'  => 'Danielo',
                    'email_residente'   => 'heavydanker@gmail.com',
                    'unidad'            => 'Torre A - Depto 101',
                    'concepto'          => 'Cuota de Mantenimiento - Agosto 2026',
                    'monto'             => 1500.00,
                    'estado'            => 'Pagado',
                    'fecha_emision'     => '2026-08-01',
                    'fecha_vencimiento' => '2026-08-10',
                    'fecha_pago'        => '2026-08-05'
                ]);
                Factura::create([
                    'folio'             => 'REC-2026-002',
                    'nombre_residente'  => 'Residente Juan',
                    'email_residente'   => 'juan@gmail.com',
                    'unidad'            => 'Torre B - Depto 204',
                    'concepto'          => 'Cuota de Mantenimiento - Agosto 2026',
                    'monto'             => 1500.00,
                    'estado'            => 'Pendiente',
                    'fecha_emision'     => '2026-08-01',
                    'fecha_vencimiento' => '2026-08-10',
                    'fecha_pago'        => null
                ]);
                $facturas = Factura::orderBy('created_at', 'desc')->get();
            }

            return response()->json([
                'status' => 'success',
                'data' => $facturas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar facturación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Emitir nueva factura (Solo Admin)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'residente_email'   => 'required|email',
                'concepto'          => 'required|string|max:255',
                'monto'             => 'required|numeric|min:1',
                'fecha_vencimiento' => 'required|date'
            ]);

            $residente = Residente::where('email', strtolower(trim($validated['residente_email'])))->first();

            if (!$residente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El residente seleccionado no existe.'
                ], 404);
            }

            $folio = 'REC-' . date('Y') . '-' . str_pad(Factura::count() + 1, 3, '0', STR_PAD_LEFT);

            $factura = Factura::create([
                'folio'             => $folio,
                'residente_id'      => (string) $residente->_id,
                'nombre_residente'  => $residente->nombre,
                'email_residente'   => $residente->email,
                'unidad'            => $residente->unidad,
                'concepto'          => $validated['concepto'],
                'monto'             => (float) $validated['monto'],
                'estado'            => 'Pendiente',
                'fecha_emision'     => date('Y-m-d'),
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'fecha_pago'        => null
            ]);

            // Enviar notificación al residente
            $notif = Notificacion::create([
                'titulo'    => 'Nueva Cuota Asignada (' . $folio . ')',
                'mensaje'   => $validated['concepto'] . ' por $' . number_format($validated['monto'], 2),
                'tipo'      => 'pago',
                'leido_por' => []
            ]);

            try {
                broadcast(new NotificacionCreada($notif))->toOthers();
            } catch (\Exception $e) {}

            return response()->json([
                'status' => 'success',
                'message' => 'Recibo de pago emitido correctamente.',
                'data' => $factura
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al emitir factura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar estado de pago (Pagado / Pendiente)
     */
    public function cambiarEstado(Request $request, $id)
    {
        try {
            $factura = Factura::find($id);

            if (!$factura) {
                return response()->json(['status' => 'error', 'message' => 'Factura no encontrada.'], 404);
            }

            $nuevoEstado = $request->estado === 'Pagado' ? 'Pagado' : 'Pendiente';
            $factura->estado = $nuevoEstado;
            $factura->fecha_pago = $nuevoEstado === 'Pagado' ? date('Y-m-d') : null;
            $factura->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Estado de pago actualizado a: ' . $nuevoEstado,
                'data' => $factura
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar factura
     */
    public function destroy($id)
    {
        try {
            $factura = Factura::find($id);
            if (!$factura) {
                return response()->json(['status' => 'error', 'message' => 'Factura no encontrada.'], 404);
            }

            $factura->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Factura eliminada correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar factura: ' . $e->getMessage()
            ], 500);
        }
    }
}
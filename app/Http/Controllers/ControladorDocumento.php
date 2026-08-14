<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorDocumento extends Controller
{
    /**
     * Listar documentos con filtros de búsqueda y categoría
     */
    public function index(Request $request)
    {
        try {
            $query = Documento::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('titulo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($request->filled('categoria') && $request->categoria !== 'Todos') {
                $query->where('categoria', $request->categoria);
            }

            $documentos = $query->orderBy('created_at', 'desc')->get();

            // Si la colección está vacía, sembramos 3 documentos de demostración
            if ($documentos->isEmpty() && !$request->filled('search')) {
                Documento::create([
                    'titulo'          => 'Reglamento Interno del Condominio 2026',
                    'descripcion'     => 'Normativas oficiales de convivencia, áreas comunes y cuotas.',
                    'categoria'       => 'Reglamento',
                    'nombre_archivo'  => 'Reglamento_CondoMaster_2026.pdf',
                    'ruta_archivo'    => 'documentos/demo.pdf',
                    'tamanio_archivo' => '2.4 MB',
                    'extension'       => 'pdf'
                ]);
                Documento::create([
                    'titulo'          => 'Acta de Asamblea General Ordinaria',
                    'descripcion'     => 'Resoluciones tomadas respecto al presupuesto anual.',
                    'categoria'       => 'Asamblea',
                    'nombre_archivo'  => 'Acta_Asamblea_2026.pdf',
                    'ruta_archivo'    => 'documentos/demo.pdf',
                    'tamanio_archivo' => '1.2 MB',
                    'extension'       => 'pdf'
                ]);
                Documento::create([
                    'titulo'          => 'Informe Financiero y Balance Mensual',
                    'descripcion'     => 'Desglose de ingresos y gastos de mantenimiento.',
                    'categoria'       => 'Finanzas',
                    'nombre_archivo'  => 'Balance_Financiero_Condo.pdf',
                    'ruta_archivo'    => 'documentos/demo.pdf',
                    'tamanio_archivo' => '850 KB',
                    'extension'       => 'pdf'
                ]);
                $documentos = Documento::orderBy('created_at', 'desc')->get();
            }

            return response()->json([
                'status' => 'success',
                'data' => $documentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir nuevo documento y registrar en MongoDB
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'titulo'      => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'categoria'   => 'required|string|max:100',
                'archivo'     => 'nullable|file|max:15360' // Max 15MB
            ]);

            $nombreOriginal = 'Documento_' . time() . '.pdf';
            $rutaArchivo = 'documentos/' . $nombreOriginal;
            $tamanio = '1.5 MB';
            $extension = 'pdf';

            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');
                $nombreOriginal = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $rutaArchivo = $file->store('public/documentos');

                $bytes = $file->getSize();
                if ($bytes >= 1048576) {
                    $tamanio = number_format($bytes / 1048576, 1) . ' MB';
                } elseif ($bytes >= 1024) {
                    $tamanio = number_format($bytes / 1024, 1) . ' KB';
                } else {
                    $tamanio = $bytes . ' B';
                }
            }

            $documento = Documento::create([
                'titulo'          => $request->titulo,
                'descripcion'     => $request->descripcion ?? 'Documento oficial del condominio.',
                'categoria'       => $request->categoria,
                'nombre_archivo'  => $nombreOriginal,
                'ruta_archivo'    => $rutaArchivo,
                'tamanio_archivo' => $tamanio,
                'extension'       => $extension
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Documento subido y registrado correctamente.',
                'data' => $documento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al subir documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar documento
     */
    public function destroy($id)
    {
        try {
            $documento = Documento::find($id);

            if (!$documento) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Documento no encontrado.'
                ], 404);
            }

            if ($documento->ruta_archivo && Storage::exists($documento->ruta_archivo)) {
                Storage::delete($documento->ruta_archivo);
            }

            $documento->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Documento eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar documento: ' . $e->getMessage()
            ], 500);
        }
    }
}
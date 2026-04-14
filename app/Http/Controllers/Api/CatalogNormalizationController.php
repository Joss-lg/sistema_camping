<?php

namespace App\Http\Controllers\Api;

use App\Services\CatalogNormalizationService;
use App\Services\PermisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogNormalizationController
{
    /**
     * Buscar categorías por texto
     */
    public function buscarCategorias(Request $request, CatalogNormalizationService $service): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
        ]);

        $searchText = $request->query('q');
        $resultados = $service->buscarCategorias($searchText);

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'count' => count($resultados),
        ]);
    }

    /**
     * Buscar unidades de medida por texto
     */
    public function buscarUnidades(Request $request, CatalogNormalizationService $service): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
        ]);

        $searchText = $request->query('q');
        $resultados = $service->buscarUnidades($searchText);

        return response()->json([
            'success' => true,
            'data' => $resultados,
            'count' => count($resultados),
        ]);
    }

    /**
     * Normalizar y obtener ID de categoría
     * Intenta mapear a existente, si no existe crea nueva
     */
    public function normalizarCategoria(Request $request, CatalogNormalizationService $service): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $nombre = $request->input('nombre');
        $user = Auth::user();

        // Intenta normalizar a existente
        $categoria = $service->normalizarCategoria($nombre);

        if (!$categoria) {
            $puedeCrear = $user && (
                PermisoService::isSuperAdmin($user) ||
                $user->canCustom('Insumos', 'crear')
            );
            if ($puedeCrear) {
                $categoria = $service->crearOEncontrarCategoria($nombre);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada y no tienes permiso para crear nuevas',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'normalizado' => true,
            ],
        ]);
    }

    /**
     * Normalizar y obtener ID de unidad de medida
     * Intenta mapear a existente, si no existe crea nueva
     */
    public function normalizarUnidad(Request $request, CatalogNormalizationService $service): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'abreviatura' => 'nullable|string|max:10',
        ]);

        $nombre = $request->input('nombre');
        $abreviatura = $request->input('abreviatura');
        $user = Auth::user();

        // Intenta normalizar a existente
        $unidad = $service->normalizarUnidad($nombre);

        if (!$unidad) {
            $puedeCrear = $user && (
                PermisoService::isSuperAdmin($user) ||
                $user->canCustom('Insumos', 'crear')
            );
            if ($puedeCrear) {
                $unidad = $service->crearOEncontrarUnidad($nombre, $abreviatura);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unidad de medida no encontrada y no tienes permiso para crear nuevas',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $unidad->id,
                'nombre' => $unidad->nombre,
                'abreviatura' => $unidad->abreviatura,
                'normalizado' => true,
            ],
        ]);
    }
}

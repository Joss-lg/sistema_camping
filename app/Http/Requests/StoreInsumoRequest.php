<?php

namespace App\Http\Requests;

use App\Services\CatalogNormalizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreInsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_insumo' => ['required', 'string', 'max:30', 'unique:insumos,codigo_insumo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'especificaciones_tecnicas' => ['nullable', 'string'],
            'categoria_insumo_id' => ['required', 'integer', 'exists:categorias_insumo,id'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidades_medida,id'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_actual' => ['required', 'numeric', 'min:0'],
            'stock_reservado' => ['nullable', 'numeric', 'min:0'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'codigo_proveedor_insumo' => ['nullable', 'string', 'max:50'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'precio_costo' => ['nullable', 'numeric', 'min:0'],
            'ubicacion_almacen_id' => ['nullable', 'integer', 'exists:ubicaciones_almacen,id'],
            'estado' => ['nullable', 'string', 'max:30'],
            'activo' => ['nullable', 'boolean'],
            'unidad_compra' => ['nullable', 'string', 'max:30'],
            'cantidad_minima_orden' => ['nullable', 'integer', 'min:1'],
            'imagen_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Preparar datos antes de validación
     * Normaliza categoría y unidad si es necesario
     */
    protected function prepareForValidation(): void
    {
        $service = app(CatalogNormalizationService::class);

        // Si no hay categoria_insumo_id pero hay categoriaSearch, intenta normalizar
        if (!$this->filled('categoria_insumo_id') && $this->filled('nombre')) {
            // El frontend debe enviar categoria_insumo_id ya normalizado
            // (el API normaliza en los endpoints)
        }

        // Si no hay unidad_medida_id pero hay unidadSearch, intenta normalizar
        if (!$this->filled('unidad_medida_id') && $this->filled('nombre')) {
            // El frontend debe enviar unidad_medida_id ya normalizado
            // (el API normaliza en los endpoints)
        }
    }

    public function messages(): array
    {
        return [
            'codigo_insumo.unique' => 'Este código de insumo ya existe. Usa uno único.',
            'categoria_insumo_id.required' => 'Debes seleccionar o crear una categoría.',
            'unidad_medida_id.required' => 'Debes seleccionar o crear una unidad de medida.',
            'proveedor_id.required' => 'Debes seleccionar un proveedor.',
        ];
    }
}


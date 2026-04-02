<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $insumoId = $this->route('insumo')?->id;

        return [
            'codigo_insumo' => ['sometimes', 'string', 'max:30', 'unique:insumos,codigo_insumo,' . $insumoId],
            'nombre' => ['sometimes', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'especificaciones_tecnicas' => ['nullable', 'string'],
            'categoria_insumo_id' => ['sometimes', 'integer', 'exists:categorias_insumo,id'],
            'unidad_medida_id' => ['sometimes', 'integer', 'exists:unidades_medida,id'],
            'tipo_producto_id' => ['nullable', 'integer', 'exists:tipos_producto,id'],
            'stock_minimo' => ['sometimes', 'numeric', 'min:0'],
            'stock_actual' => ['sometimes', 'numeric', 'min:0'],
            'stock_reservado' => ['nullable', 'numeric', 'min:0'],
            'proveedor_id' => ['sometimes', 'integer', 'exists:proveedores,id'],
            'codigo_proveedor_insumo' => ['nullable', 'string', 'max:50'],
            'precio_unitario' => ['sometimes', 'numeric', 'min:0'],
            'precio_costo' => ['nullable', 'numeric', 'min:0'],
            'ubicacion_almacen_id' => ['nullable', 'integer', 'exists:ubicaciones_almacen,id'],
            'estado' => ['sometimes', 'string', 'max:30'],
            'activo' => ['nullable', 'boolean'],
            'unidad_compra' => ['nullable', 'string', 'max:30'],
            'cantidad_minima_orden' => ['nullable', 'integer', 'min:1'],
            'imagen_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}

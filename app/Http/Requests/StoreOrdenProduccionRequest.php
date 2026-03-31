<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_orden' => ['nullable', 'string', 'max:50', 'unique:ordenes_produccion,numero_orden'],
            'tipo_producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'fecha_orden' => ['nullable', 'date'],
            'fecha_inicio_prevista' => ['required', 'date'],
            'fecha_fin_prevista' => ['required', 'date', 'after_or_equal:fecha_inicio_prevista'],
            'cantidad_produccion' => ['required', 'numeric', 'gt:0'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidades_medida,id'],
            'estado' => ['nullable', 'string', 'max:50'],
            'costo_estimado' => ['nullable', 'numeric', 'min:0'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'requiere_calidad' => ['nullable', 'boolean'],
            'notas' => ['nullable', 'string'],
            'especificaciones_especiales' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ordenId = $this->route('ordenProduccion')?->id;

        return [
            'numero_orden' => ['sometimes', 'string', 'max:50', 'unique:ordenes_produccion,numero_orden,' . $ordenId],
            'tipo_producto_id' => ['sometimes', 'integer', 'exists:tipos_producto,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'fecha_orden' => ['sometimes', 'date'],
            'fecha_inicio_prevista' => ['sometimes', 'date'],
            'fecha_fin_prevista' => ['sometimes', 'date', 'after_or_equal:fecha_inicio_prevista'],
            'fecha_inicio_real' => ['nullable', 'date'],
            'fecha_fin_real' => ['nullable', 'date', 'after_or_equal:fecha_inicio_real'],
            'cantidad_produccion' => ['sometimes', 'numeric', 'gt:0'],
            'unidad_medida_id' => ['sometimes', 'integer', 'exists:unidades_medida,id'],
            'estado' => ['sometimes', 'string', 'max:50'],
            'etapas_totales' => ['sometimes', 'integer', 'min:0'],
            'etapas_completadas' => ['sometimes', 'integer', 'min:0'],
            'porcentaje_completado' => ['sometimes', 'numeric', 'between:0,100'],
            'costo_estimado' => ['nullable', 'numeric', 'min:0'],
            'costo_real' => ['nullable', 'numeric', 'min:0'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'requiere_calidad' => ['nullable', 'boolean'],
            'notas' => ['nullable', 'string'],
            'especificaciones_especiales' => ['nullable', 'string'],
        ];
    }
}

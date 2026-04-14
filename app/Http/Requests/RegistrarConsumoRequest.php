<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarConsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orden_produccion_id' => ['required', 'integer', 'exists:ordenes_produccion,id'],
            'material_id' => ['required', 'integer', 'exists:insumos,id'],
            'cantidad_usada' => ['required', 'numeric', 'gt:0'],
            'cantidad_merma' => ['nullable', 'numeric', 'gte:0'],
            'tipo_merma' => ['nullable', 'in:Corte,Costura,Defecto,Manejo,Otro'],
            'motivo_merma' => ['nullable', 'string', 'max:500'],
            'redirect_seguimiento' => ['nullable', 'boolean'],
        ];
    }
}

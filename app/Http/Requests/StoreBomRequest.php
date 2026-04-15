<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
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
            'producto_nombre' => ['required', 'string', 'max:100'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['required', 'integer', 'distinct', 'exists:insumos,id'],
            'cantidad_base' => ['required', 'array', 'min:1'],
            'cantidad_base.*' => ['required', 'numeric', 'gt:0'],
            'activo' => ['nullable', 'array'],
            'activo.*' => ['nullable', 'in:0,1'],
            'activo_general' => ['nullable', 'in:0,1'],
        ];
    }
}

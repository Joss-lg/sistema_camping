<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProduccionRequest extends FormRequest
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
            'producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_esperada' => ['nullable', 'date'],
            'maquina_asignada' => ['nullable', 'string', 'max:120'],
            'turno_asignado' => ['nullable', 'in:Manana,Tarde,Noche'],
        ];
    }
}

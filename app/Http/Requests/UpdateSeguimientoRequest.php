<?php

namespace App\Http\Requests;

use App\Models\EtapaProduccionPlantilla;
use App\Models\OrdenProduccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeguimientoRequest extends FormRequest
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
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'maquina_asignada' => ['nullable', 'string', 'max:120'],
            'turno_asignado' => ['nullable', 'in:Manana,Tarde,Noche'],
            'estado' => ['nullable', 'in:PENDIENTE,EN_PROCESO,FINALIZADA'],
            'cantidad_completada' => ['nullable', 'numeric', 'gte:0'],
            'etapa_fabricacion_actual' => ['nullable', Rule::in($this->etapasPermitidas())],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function etapasPermitidas(): array
    {
        $ordenId = (int) ($this->route('id') ?? 0);

        if ($ordenId <= 0) {
            return ['Corte', 'Costura', 'Ensamblado', 'Acabado'];
        }

        $tipoProductoId = (int) (OrdenProduccion::query()->whereKey($ordenId)->value('tipo_producto_id') ?? 0);

        if ($tipoProductoId <= 0) {
            return ['Corte', 'Costura', 'Ensamblado', 'Acabado'];
        }

        $etapas = EtapaProduccionPlantilla::query()
            ->where('tipo_producto_id', $tipoProductoId)
            ->where('activo', true)
            ->orderBy('numero_secuencia')
            ->pluck('nombre')
            ->filter(fn ($nombre): bool => trim((string) $nombre) !== '')
            ->values()
            ->all();

        if (! empty($etapas)) {
            return array_map(fn ($nombre): string => (string) $nombre, $etapas);
        }

        return ['Corte', 'Costura', 'Ensamblado', 'Acabado'];
    }
}

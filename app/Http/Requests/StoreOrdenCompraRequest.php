<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_orden' => ['nullable', 'string', 'max:50', 'unique:ordenes_compra,numero_orden'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha_orden' => ['nullable', 'date'],
            'fecha_entrega_prevista' => ['required', 'date'],
            'estado' => ['nullable', 'string', 'max:50'],
            'impuestos' => ['nullable', 'numeric', 'min:0'],
            'descuentos' => ['nullable', 'numeric', 'min:0'],
            'costo_flete' => ['nullable', 'numeric', 'min:0'],
            'numero_folio_proveedor' => ['nullable', 'string', 'max:100'],
            'numero_contenedor' => ['nullable', 'string', 'max:100'],
            'numero_awb' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'condiciones_pago' => ['nullable', 'string', 'max:100'],
            'incoterm' => ['nullable', 'string', 'max:20'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.insumo_id' => ['required', 'integer', 'exists:insumos,id'],
            'detalles.*.unidad_medida_id' => ['required', 'integer', 'exists:unidades_medida,id'],
            'detalles.*.cantidad_solicitada' => ['required', 'numeric', 'gt:0'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'between:0,100'],
            'detalles.*.fecha_entrega_esperada_linea' => ['nullable', 'date'],
            'detalles.*.notas' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $detalles = $this->input('detalles', []);

        if (! is_array($detalles)) {
            return;
        }

        $detallesNormalizados = collect($detalles)
            ->map(function ($detalle) {
                return is_array($detalle) ? $detalle : [];
            })
            ->filter(function (array $detalle) {
                return isset($detalle['insumo_id'])
                    || isset($detalle['unidad_medida_id'])
                    || isset($detalle['cantidad_solicitada'])
                    || isset($detalle['precio_unitario'])
                    || isset($detalle['descuento_porcentaje'])
                    || isset($detalle['fecha_entrega_esperada_linea'])
                    || isset($detalle['notas']);
            })
            ->values()
            ->all();

        $this->merge(['detalles' => $detallesNormalizados]);
    }
}

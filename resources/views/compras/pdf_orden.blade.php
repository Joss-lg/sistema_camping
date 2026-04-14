<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra #{{ $ordenCompra->numero_orden ?: $ordenCompra->id }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; border-spacing: 0; }
        .info-table td { vertical-align: top; width: 50%; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background-color: #f2f2f2; padding: 8px; border: 1px solid #ddd; text-align: left; font-size: 11px; }
        .items-table td { padding: 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .status-badge { padding: 4px 8px; background: #eee; border-radius: 4px; font-size: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        h1 { margin: 0; color: #1a202c; }
        .small { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $proveedor = $ordenCompra->proveedor;
        $usuario = $ordenCompra->user;
        $contacto = $contactoProveedor ?? null;
    @endphp

    <table class="header-table">
        <tr>
            <td>
                <h1>ORDEN DE COMPRA</h1>
                <p>Folio: <strong>#{{ $ordenCompra->numero_orden ?: $ordenCompra->id }}</strong></p>
                <p>Estado: <span class="status-badge">{{ strtoupper((string) $ordenCompra->estado) }}</span></p>
            </td>
            <td class="text-right">
                <p class="font-bold" style="font-size: 16px;">LOGICAMP</p>
                <p>RFC: -------------<br>Dirección General, Ciudad, País</p>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <p class="font-bold" style="color: #666; text-transform: uppercase;">Proveedor:</p>
                <p>
                    <strong>{{ $proveedor?->nombre_comercial ?: $proveedor?->razon_social ?: 'Sin proveedor' }}</strong><br>
                    @if($contacto)
                        Contacto: {{ $contacto->nombre_completo }}{{ $contacto->cargo ? ' (' . $contacto->cargo . ')' : '' }}<br>
                        Tel. contacto: {{ $contacto->telefono_movil ?: $contacto->telefono ?: 'N/A' }}<br>
                        Email contacto: {{ $contacto->email ?: 'N/A' }}<br>
                    @endif
                    Tel. general: {{ $proveedor?->telefono_principal ?: 'N/A' }}<br>
                    Email general: {{ $proveedor?->email_general ?: 'N/A' }}
                </p>
            </td>
            <td class="text-right">
                <p class="font-bold" style="color: #666; text-transform: uppercase;">Detalles de Envío:</p>
                <p>
                    Fecha Emisión: {{ optional($ordenCompra->fecha_orden)->format('d/m/Y') ?: 'N/A' }}<br>
                    <strong>Fecha Esperada: {{ optional($ordenCompra->fecha_entrega_prevista)->format('d/m/Y') ?: 'N/A' }}</strong><br>
                    Solicitado por: {{ $usuario?->name ?: 'Sistema' }}<br>
                    Incoterm: {{ $ordenCompra->incoterm ?: 'N/A' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción del Material</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Unidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($ordenCompra->detalles as $detalle)
                @php 
                    $subtotal = (float) $detalle->subtotal;
                    if ($subtotal <= 0) {
                        $subtotal = ((float) $detalle->cantidad_solicitada) * ((float) $detalle->precio_unitario);
                    }
                    $total += $subtotal;
                @endphp
                <tr>
                    <td>{{ $detalle->insumo?->nombre ?: 'Insumo' }}</td>
                    <td class="text-right">{{ number_format((float) $detalle->cantidad_solicitada, 2) }}</td>
                    <td class="text-right">{{ $detalle->unidadMedida?->abreviatura ?: ($detalle->unidadMedida?->nombre ?: 'un.') }}</td>
                    <td class="text-right">${{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">${{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right font-bold" style="padding-top: 20px;">TOTAL:</td>
                <td class="text-right font-bold" style="padding-top: 20px; font-size: 14px;">
                    ${{ number_format((float) ($ordenCompra->monto_total ?: $total), 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 40px;">
        <p class="font-bold">Notas:</p>
        <p style="font-style: italic; color: #666;">
            {{ $ordenCompra->notas ?: 'Esta es una orden de compra generada por el sistema. Favor de confirmar la recepción de este documento y la fecha de entrega.' }}
        </p>
        @if($contacto)
            <p class="small">
                Documento dirigido a contacto de proveedor: {{ $contacto->nombre_completo }}
                @if($contacto->email)
                    ({{ $contacto->email }})
                @endif
            </p>
        @endif
    </div>

    <div class="footer">
        Generado el {{ $fechaGeneracion ?? now()->format('d/m/Y H:i') }} - Página 1 de 1
    </div>

</body>
</html>
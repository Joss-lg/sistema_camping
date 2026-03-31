<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra #{{ $orden->id }}</title>
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
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <h1>ORDEN DE COMPRA</h1>
                <p>Folio: <strong>#{{ $orden->id }}</strong></p>
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
                    <strong>{{ $orden->proveedor->nombre }}</strong><br>
                    Contacto: {{ $orden->proveedor->contacto_nombre ?? 'N/A' }}<br>
                    Tel: {{ $orden->proveedor->telefono }}<br>
                    Email: {{ $orden->proveedor->email }}
                </p>
            </td>
            <td class="text-right">
                <p class="font-bold" style="color: #666; text-transform: uppercase;">Detalles de Envío:</p>
                <p>
                    Fecha Emisión: {{ $orden->fecha->format('d/m/Y') }}<br>
                    <strong>Fecha Esperada: {{ $orden->fecha_esperada->format('d/m/Y') }}</strong><br>
                    Solicitado por: {{ $orden->usuario->nombre ?? 'Sistema' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción del Material</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($orden->items as $item)
                @php 
                    $subtotal = $item->cantidad * $item->precio_unitario;
                    $total += $subtotal;
                @endphp
                <tr>
                    <td>{{ $item->material->nombre }}</td>
                    <td class="text-right">{{ number_format($item->cantidad, 2) }} {{ $item->material->unidad->nombre ?? 'un.' }}</td>
                    <td class="text-right">${{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-right">${{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right font-bold" style="padding-top: 20px;">TOTAL:</td>
                <td class="text-right font-bold" style="padding-top: 20px; font-size: 14px;">
                    ${{ number_format($total, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 40px;">
        <p class="font-bold">Notas:</p>
        <p style="font-style: italic; color: #666;">
            Esta es una orden de compra generada automáticamente por el sistema de soporte operativo. 
            Favor de confirmar la recepción de este documento y la fecha de entrega.
        </p>
    </div>

    <div class="footer">
        Generado el {{ $fecha }} - Página 1 de 1
    </div>

</body>
</html>
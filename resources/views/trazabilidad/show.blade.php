@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Detalle de trazabilidad</h1>
<div class="bg-white border rounded p-4 space-y-2">
    <p><strong>Lote:</strong> {{ $producto->numero_lote_produccion }}</p>
    <p><strong>Serie:</strong> {{ $producto->numero_serie }}</p>
    <p><strong>Estado:</strong> {{ $producto->estado }}</p>
</div>
@endsection

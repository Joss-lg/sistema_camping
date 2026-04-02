@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Detalle de trazabilidad</h1>
<div class="bg-white border rounded p-4 space-y-2">
    <p><strong>Orden:</strong> {{ $registro->numero_orden }}</p>
    <p><strong>Lote:</strong> {{ $registro->numero_lote_produccion ?? '-' }}</p>
    <p><strong>Serie:</strong> {{ $registro->numero_serie ?? '-' }}</p>
    <p><strong>Referencia:</strong> {{ $registro->referencia }}</p>
    <p><strong>Estado:</strong> {{ $registro->estado }}</p>
</div>
@endsection

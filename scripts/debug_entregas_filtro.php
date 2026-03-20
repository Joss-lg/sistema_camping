<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function dumpRows($desc, $rows) {
    echo "=== $desc (".count($rows).")\n";
    foreach ($rows as $r) {
        echo "{$r->id} {$r->fecha_entrega}\n";
    }
}

$all = DB::table('entrega_proveedor')->orderBy('fecha_entrega')->get();
dumpRows('all', $all);

$range = DB::table('entrega_proveedor')
    ->whereBetween('fecha_entrega', ['2026-03-12 00:00:00', '2026-03-20 23:59:59'])
    ->orderBy('fecha_entrega')->get();

dumpRows('range 12-20', $range);

$day = DB::table('entrega_proveedor')->whereDate('fecha_entrega', '2026-03-11')->get();
dumpRows('day 2026-03-11', $day);

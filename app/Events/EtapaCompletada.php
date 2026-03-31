<?php

namespace App\Events;

use App\Models\TrazabilidadEtapa;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EtapaCompletada
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TrazabilidadEtapa $etapa)
    {
    }
}

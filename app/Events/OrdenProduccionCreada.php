<?php

namespace App\Events;

use App\Models\OrdenProduccion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenProduccionCreada
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public OrdenProduccion $ordenProduccion)
    {
    }
}

<?php

namespace App\Events;

use App\Models\ConsumoMaterial;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaterialConsumido
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ConsumoMaterial $consumoMaterial)
    {
    }
}

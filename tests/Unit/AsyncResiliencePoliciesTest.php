<?php

namespace Tests\Unit;

use App\Jobs\CalcularCostosPromedioJob;
use App\Jobs\VerificarStockBajoJob;
use App\Jobs\VerificarStockBajoTerminadosJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class AsyncResiliencePoliciesTest extends TestCase
{
    public function test_calcular_costos_job_has_retry_policy(): void
    {
        $job = new CalcularCostosPromedioJob();

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame(300, $job->timeout);
        $this->assertSame([60, 300, 900], $job->backoff());
    }

    public function test_verificar_stock_bajo_job_has_retry_policy(): void
    {
        $job = new VerificarStockBajoJob();

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertSame([60, 300, 900], $job->backoff());
    }

    public function test_verificar_stock_bajo_terminados_job_has_base_queue_policy(): void
    {
        $job = new VerificarStockBajoTerminadosJob();

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertSame([60, 300, 900], $job->backoff());
    }
}

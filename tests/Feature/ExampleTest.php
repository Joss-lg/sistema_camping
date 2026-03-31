<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_can_view_public_welcome_from_root(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('LogiCamp');
    }
}

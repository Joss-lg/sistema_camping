<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'session.auth'])->get('/_test/session-auth', fn () => 'ok');
        Route::middleware(['web', 'session.auth', 'admin.only'])->get('/_test/admin-only', fn () => 'ok');
        Route::middleware(['web', 'session.auth', 'proveedor.only'])->get('/_test/proveedor-only', fn () => 'ok');
    }

    public function test_guest_is_redirected_when_route_requires_session(): void
    {
        $response = $this->get('/_test/session-auth');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_session_can_access_protected_route(): void
    {
        $response = $this->withSession([
            'auth_user_id' => 1,
            'auth_user_rol' => 'ADMIN',
        ])->get('/_test/session-auth');

        $response->assertOk();
    }

    public function test_non_admin_is_redirected_from_admin_route(): void
    {
        $response = $this->withSession([
            'auth_user_id' => 2,
            'auth_user_rol' => 'ALMACEN',
        ])->get('/_test/admin-only');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_admin_route(): void
    {
        $response = $this->withSession([
            'auth_user_id' => 3,
            'auth_user_rol' => 'ADMIN',
        ])->get('/_test/admin-only');

        $response->assertOk();
    }

    public function test_non_proveedor_is_redirected_from_proveedor_route(): void
    {
        $response = $this->withSession([
            'auth_user_id' => 4,
            'auth_user_rol' => 'ADMIN',
        ])->get('/_test/proveedor-only');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_proveedor_can_access_proveedor_route(): void
    {
        $response = $this->withSession([
            'auth_user_id' => 5,
            'auth_user_rol' => 'PROVEEDOR',
        ])->get('/_test/proveedor-only');

        $response->assertOk();
    }
}

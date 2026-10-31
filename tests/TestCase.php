<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function panelPath(string $suffix=''): string
    {
        return '/'.\App\Support\PanelAccess::path().$suffix;
    }
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        parent::actingAs($user,$guard);
        // Simulate an already authenticated session; real login/revocation tests use HTTP login.
        session()->put('auth_versions.'.($guard??config('auth.defaults.guard')), (int)$user->auth_version);
        return $this;
    }
    protected function setUp(): void
    {
        parent::setUp();
        // Workflow tests exercise domain behavior; verification is covered with middleware enabled separately.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyHuman::class);
    }
}

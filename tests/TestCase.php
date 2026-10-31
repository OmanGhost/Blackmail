<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Workflow tests exercise domain behavior; verification is covered with middleware enabled separately.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyHuman::class);
    }
}

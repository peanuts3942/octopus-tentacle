<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (env('APP_ENV') === 'production') {
            $this->fail('Tests cannot run in production environment.');
        }

        parent::setUp();
    }
}

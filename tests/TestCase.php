<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aset Vite tidak dibutuhkan saat pengujian (manifest belum tentu ada).
        $this->withoutVite();
    }
}

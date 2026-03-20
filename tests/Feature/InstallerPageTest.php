<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useStoragePath(base_path('tests/storage'));

        $appStorage = storage_path('app');
        if (! is_dir($appStorage)) {
            mkdir($appStorage, 0777, true);
        }

        @unlink(storage_path('app/install.lock'));
    }

    public function test_install_setup_page_is_accessible(): void
    {
        $response = $this->get('/install');

        $response->assertOk();
    }
}

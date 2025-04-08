<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Support\Number;
use Orchestra\Testbench\TestCase;

class SyncLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->setLocale('en');
        $this->app->shouldSyncLocale(false);

        parent::tearDown();
    }

    public function testItShouldSyncLocale()
    {
        $this->app->shouldSyncLocale();
        $this->app->setLocale('es');

        $this->assertSame('tres', Number::spell(3));
    }
}

<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class RefreshCommandTest extends TestCase
{
    public function testRefreshWithoutRealpath()
    {
        $this->app->setBasePath(__DIR__);

        $options = [
            '--path' => 'stubs/',
        ];

        $this->migrateRefreshWith($options);
    }

    public function testRefreshWithRealpath()
    {
        $options = [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];

        $this->migrateRefreshWith($options);
    }

    private function migrateRefreshWith(array $options)
    {
        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->artisan('migrate:rollback', $options);
        });

        $this->artisan('migrate:refresh', $options);
        DB::table('members')->insert(['name' => 'foo', 'email' => 'foo@bar', 'password' => 'secret']);
        $this->assertEquals(1, DB::table('members')->count());

        $this->artisan('migrate:refresh', $options);
        $this->assertEquals(0, DB::table('members')->count());
    }
}

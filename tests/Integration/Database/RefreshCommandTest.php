<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;

class RefreshCommandTest extends DatabaseTestCase
{
    public function testRefreshWithoutRealpath()
    {
        $this->app->setBasePath(__DIR__);

        $options = [
            '--path' => 'stubs/',
        ];

        $this->migrate_refresh_with($options);
    }

    public function testRefreshWithRealpath()
    {
        $options = [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];

        $this->migrate_refresh_with($options);
    }

    private function migrate_refresh_with(array $options)
    {
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

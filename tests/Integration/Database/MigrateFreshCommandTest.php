<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class MigrateFreshCommandTest extends TestCase
{
    public function testFreshWithPreserveDataOption()
    {
        $this->app->setBasePath(__DIR__);

        $options = [
            '--path' => 'stubs/',
        ];

        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->artisan('migrate:rollback', $options);
        });

        $this->artisan('migrate', $options);

        DB::table('members')->insert([
            ['name' => 'foo', 'email' => 'foo@bar', 'password' => 'secret'],
            ['name' => 'bar', 'email' => 'bar@foo', 'password' => 'secret'],
        ]);

        $this->assertSame(2, DB::table('members')->count());

        $this->artisan('migrate:fresh', array_merge($options, [
            '--preserve-data' => true,
        ]));

        $this->assertSame(2, DB::table('members')->count());
        $this->assertSame(['bar@foo', 'foo@bar'], DB::table('members')->orderBy('email')->pluck('email')->all());
    }
}

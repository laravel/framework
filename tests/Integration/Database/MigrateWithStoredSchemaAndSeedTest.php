<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class MigrateWithStoredSchemaAndSeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }
    }

    protected function tearDown(): void
    {
        foreach (array_keys($this->app['db']->getConnections()) as $name) {
            $this->app['db']->purge($name);
        }

        parent::tearDown();
    }

    public function testMigratingWithStoredSchemaAndSeedCreatesTheMigrationsTable()
    {
        $this->artisan('migrate', [
            '--path' => realpath(__DIR__.'/stubs/migrate-schema'),
            '--realpath' => true,
            '--schema-path' => __DIR__.'/stubs/migrate-schema/schema.sql',
            '--seed' => true,
            '--seeder' => MigrateWithStoredSchemaAndSeedTestSeeder::class,
        ]);

        $this->assertTrue(Schema::hasTable('migrations'));
        $this->assertDatabaseHas('migrations', [
            'migration' => '2014_10_12_000000_create_people_table',
            'batch' => 1,
        ]);

        $this->assertTrue(Schema::hasTable('people'));
        $this->assertDatabaseHas('people', ['name' => 'seeded']);
    }
}

class MigrateWithStoredSchemaAndSeedTestSeeder extends Seeder
{
    public function run()
    {
        DB::table('people')->insert(['name' => 'seeded']);
    }
}

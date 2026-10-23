<?php

namespace Illuminate\Tests\Integration\Migration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class StatusCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('migrator')->getRepository()->createRepository();
    }

    public function testMigrationsThatShouldNotRunAreReportedAsSkipped()
    {
        $this->app->make('migrator')->run([__DIR__.'/Fixtures']);

        $this->artisan('migrate:status', ['--path' => __DIR__.'/Fixtures', '--realpath' => true])
            ->expectsOutputToContain('Skipped')
            ->doesntExpectOutputToContain('Pending')
            ->assertSuccessful();
    }

    public function testSkippedMigrationsAreNotConsideredPending()
    {
        $this->app->make('migrator')->run([__DIR__.'/Fixtures']);

        $this->artisan('migrate:status', ['--path' => __DIR__.'/Fixtures', '--realpath' => true, '--pending' => 100])
            ->expectsOutputToContain('No pending migrations')
            ->assertSuccessful();
    }

    public function testShouldRunIsNotEvaluatedAfterThePendingMigrationsItMayDependOn()
    {
        $this->artisan('migrate:status', ['--path' => __DIR__.'/Fixtures/status', '--realpath' => true, '--pending' => 100])
            ->doesntExpectOutputToContain('2024_01_01_000000_create_airlines_table')
            ->expectsOutputToContain('2024_01_02_000000_create_flights_table')
            ->expectsOutputToContain('2024_01_03_000000_add_name_to_flights_table')
            ->assertExitCode(100);

        $this->app->make('migrator')->run([__DIR__.'/Fixtures/status']);

        $this->assertFalse(Schema::hasTable('airlines'));
        $this->assertTrue(Schema::hasColumn('flights', 'name'));
    }

    public function testStatusCanBeOutputAsJson()
    {
        $this->app->make('migrator')->run([__DIR__.'/Fixtures/status']);

        $this->app->make('migrator')->getRepository()->delete((object) ['migration' => '2024_01_03_000000_add_name_to_flights_table']);

        Artisan::call('migrate:status', ['--path' => __DIR__.'/Fixtures/status', '--realpath' => true, '--json' => true]);

        $this->assertSame([
            ['migration' => '2024_01_01_000000_create_airlines_table', 'batch' => null, 'status' => 'skipped'],
            ['migration' => '2024_01_02_000000_create_flights_table', 'batch' => 1, 'status' => 'ran'],
            ['migration' => '2024_01_03_000000_add_name_to_flights_table', 'batch' => null, 'status' => 'pending'],
        ], json_decode(Artisan::output(), true));
    }

    public function testPendingStatusCanBeOutputAsJson()
    {
        $exitCode = Artisan::call('migrate:status', ['--path' => __DIR__.'/Fixtures/status', '--realpath' => true, '--pending' => 100, '--json' => true]);

        $this->assertSame(100, $exitCode);
        $this->assertSame([
            ['migration' => '2024_01_02_000000_create_flights_table', 'batch' => null, 'status' => 'pending'],
            ['migration' => '2024_01_03_000000_add_name_to_flights_table', 'batch' => null, 'status' => 'pending'],
        ], json_decode(Artisan::output(), true));
    }

    public function testNoPendingMigrationsAreOutputAsEmptyJsonArray()
    {
        $this->app->make('migrator')->run([__DIR__.'/Fixtures/status']);

        $exitCode = Artisan::call('migrate:status', ['--path' => __DIR__.'/Fixtures/status', '--realpath' => true, '--pending' => 100, '--json' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('[]', trim(Artisan::output()));
    }
}

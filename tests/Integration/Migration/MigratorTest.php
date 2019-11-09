<?php

namespace Illuminate\Tests\Integration\Migration;

use Orchestra\Testbench\TestCase;
use PDOException;

class MigratorTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function testDontDisplayOutputWhenOutputObjectIsNotAvailable()
    {
        $migrator = $this->app->make('migrator');

        $migrator->getRepository()->createRepository();

        $migrator->run([__DIR__.'/fixtures']);

        $this->assertTrue($this->tableExists('people'));
    }

    private function tableExists($table): bool
    {
        try {
            $this->app->make('db')->select("SELECT COUNT(*) FROM $table");
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }
}

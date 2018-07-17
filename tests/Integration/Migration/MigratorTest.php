<?php

namespace Illuminate\Tests\Integration\Migration;

use Orchestra\Testbench\TestCase;

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

        $app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * @test
     */
    public function dont_display_output_when_output_object_is_not_available()
    {
        $migrator = $this->app->make('migrator');

        $migrator->getRepository()->createRepository();

        $migrator->run([__DIR__.'/fixtures']);

        $this->assertTrue($this->tableExists('members'));
    }

    /**
     * @test
     */
    public function migrator_will_not_modify_the_default_database_connection()
    {
        $migrator = $this->app->make('migrator');

        $migrator->setConnection('tenant');

        $migrator->getRepository()->createRepository();

        $migrator->run([__DIR__.'/fixtures']);

        $databaseManager = $this->app->make('db');

        $connection = $databaseManager->connection();

        $this->assertSame('testbench', $this->app['config']->get('database.default'));

        $this->assertSame('testbench', $connection->getName());
    }

    private function tableExists($table): bool
    {
        try {
            $this->app->make('db')->select("SELECT COUNT(*) FROM $table");
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }
}

<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_pgsql')]
class DatabasePgsqlPooledConnectionTest extends PostgresTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $config = $app['config']->get('database.connections.pgsql');

        $app['config']->set('database.connections.pgsql.direct', array_filter([
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'sslmode' => $config['sslmode'] ?? null,
        ]));
    }

    public function testPooledAndDirectConnectionsUseExpectedPrepareModes()
    {
        $this->assertTrue(
            DB::connection('pgsql')->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES)
        );

        $this->assertFalse(
            DB::connection('pgsql::direct')->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES)
        );
    }

    public function testRuntimeSchemaInspectionWorksThroughPooledConnection()
    {
        $this->assertIsBool(DB::connection('pgsql')->getSchemaBuilder()->hasTable('migrations'));
    }

    public function testPooledConnectionCanBindBooleansWithEmulatedPrepares()
    {
        $schema = DB::connection('pgsql::direct')->getSchemaBuilder();

        $schema->dropIfExists('pooled_boolean_bindings');
        $schema->create('pooled_boolean_bindings', function ($table) {
            $table->boolean('active');
        });

        try {
            DB::connection('pgsql')->table('pooled_boolean_bindings')->insert([
                'active' => true,
            ]);

            $this->assertSame(
                1,
                DB::connection('pgsql')->table('pooled_boolean_bindings')->where('active', true)->count()
            );
        } finally {
            $schema->dropIfExists('pooled_boolean_bindings');
        }
    }
}

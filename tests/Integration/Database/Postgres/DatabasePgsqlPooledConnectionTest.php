<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Eloquent\Model;
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

    public function testEloquentModelsStayOnTheDirectConnection()
    {
        $direct = DB::connection('pgsql::direct');
        $schema = $direct->getSchemaBuilder();

        $schema->dropIfExists('pooled_direct_models');

        try {
            $direct->transaction(function () use ($schema) {
                $schema->create('pooled_direct_models', function ($table) {
                    $table->id();
                    $table->string('name');
                });

                // Every model the builder produces has to keep the routing, whether it is created
                // or hydrated. The table only exists inside this transaction, so anything falling
                // back to the pooled connection can neither write to it nor read it back.
                $created = PooledDirectModel::on('pgsql::direct')->create(['name' => 'direct']);
                $retrieved = PooledDirectModel::on('pgsql::direct')->first();

                $this->assertSame('pgsql::direct', $created->getConnectionName());
                $this->assertSame('pgsql::direct', $retrieved->getConnectionName());
                $this->assertSame('direct', $retrieved->name);
            });
        } finally {
            $schema->dropIfExists('pooled_direct_models');
        }
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

class PooledDirectModel extends Model
{
    protected $table = 'pooled_direct_models';

    protected $guarded = [];

    public $timestamps = false;
}

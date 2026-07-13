<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigratorTwoConnectionsTest extends TestCase
{
    protected $tmpMigrationsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpMigrationsPath = sys_get_temp_dir().'/migrator_two_conn_test_'.uniqid();
        mkdir($this->tmpMigrationsPath);

        file_put_contents(
            $this->tmpMigrationsPath.'/2024_01_01_000000_create_secondary_table.php',
            <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql2';

    public function up(): void
    {
        Schema::connection($this->connection)->create('secondary_table', function ($table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('secondary_table');
    }
};
PHP
        );
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpMigrationsPath.'/*'));
        rmdir($this->tmpMigrationsPath);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        m::close();

        parent::tearDown();
    }

    public function testFreshOnOneConnectionDoesNotTouchTheOther()
    {
        $files = new Filesystem;

        $repository = m::mock(MigrationRepositoryInterface::class);
        $repository->shouldReceive('getRan')->andReturn([]);
        $repository->shouldReceive('getNextBatchNumber')->andReturn(1);
        $repository->shouldReceive('log')->zeroOrMoreTimes();
        $repository->shouldReceive('setSource')->zeroOrMoreTimes();

        $mysqlSchema = m::mock(SchemaBuilder::class);
        $mysql2Schema = m::mock(SchemaBuilder::class);

        $mysqlConnection = m::mock(Connection::class);
        $mysqlConnection->shouldReceive('getSchemaBuilder')->andReturn($mysqlSchema);
        $mysqlConnection->shouldReceive('getSchemaGrammar')->andReturn(new MySqlGrammar($mysqlConnection));
        $mysqlConnection->shouldReceive('getName')->andReturn('mysql');
        $mysqlConnection->shouldReceive('hasDirectConnection')->once()->andReturn(false);

        $mysql2Connection = m::mock(Connection::class);
        $mysql2Connection->shouldReceive('getSchemaBuilder')->andReturn($mysql2Schema);
        $mysql2Connection->shouldReceive('getSchemaGrammar')->andReturn(new MySqlGrammar($mysql2Connection));
        $mysql2Connection->shouldReceive('getName')->andReturn('mysql2');
        $mysql2Connection->shouldReceive('hasDirectConnection')->once()->andReturn(false);
        $mysql2Connection->shouldReceive('getNameWithReadWriteType')->once()->andReturn('mysql::direct');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('connection')->with('mysql')->andReturn($mysqlConnection);
        $resolver->shouldReceive('connection')->with('mysql2')->andReturn($mysql2Connection);
        $resolver->shouldReceive('setDefaultConnection');
        $resolver->shouldReceive('getDefaultConnection')->andReturn('mysql');

        $container = new Container;
        $container->instance('db', $resolver);
        Facade::setFacadeApplication($container);

        $migrator = new Migrator($repository, $resolver, $files);

        $mysql2Schema->shouldReceive('create')->once();

        $migrator->usingConnection('mysql', function () use ($migrator) {
            $migrator->run([$this->tmpMigrationsPath]);
        });
    }
}

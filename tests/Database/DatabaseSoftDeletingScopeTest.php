<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\SQLiteConnection;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseSoftDeletingScopeTest extends TestCase
{
    public function testApplyingScopeToABuilder()
    {
        $builder = $this->newBuilder(scoped: false);

        (new SoftDeletingScope)->apply($builder, $builder->getModel());

        $this->assertSame('select * from "users" where "users"."deleted_at" is null', $builder->toSql());
    }

    public function testRestoreExtension()
    {
        $connection = $this->newConnection();
        $builder = $this->newBuilder($connection);

        $builder->restore();

        $this->assertSame(0, $connection->table('users')->whereNotNull('deleted_at')->count());
    }

    public function testRestoreOrCreateExtension()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = new EloquentBuilder(new BaseBuilder(
            $connection,
            new Grammar($connection),
            new Processor
        ));

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restoreOrCreate');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $givenBuilder->expects('withTrashed');
        $attributes = ['name' => 'foo'];
        $values = ['email' => 'bar'];
        $model = Mockery::mock(Model::class);
        $givenBuilder->expects('firstOrCreate')->with($attributes, $values)->andReturn($model);
        $model->expects('restore')->andReturn(true);
        $result = $callback($givenBuilder, $attributes, $values);

        $this->assertEquals($model, $result);
    }

    public function testCreateOrRestoreExtension()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = new EloquentBuilder(new BaseBuilder(
            $connection,
            new Grammar($connection),
            new Processor
        ));

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('createOrRestore');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $givenBuilder->expects('withTrashed');
        $attributes = ['name' => 'foo'];
        $values = ['email' => 'bar'];
        $model = Mockery::mock(Model::class);
        $givenBuilder->expects('createOrFirst')->with($attributes, $values)->andReturn($model);
        $model->expects('restore')->andReturn(true);
        $result = $callback($givenBuilder, $attributes, $values);

        $this->assertEquals($model, $result);
    }

    public function testWithTrashedExtension()
    {
        $builder = $this->newBuilder();

        $this->assertSame('select * from "users" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertSame('select * from "users"', $builder->withTrashed()->toSql());
    }

    public function testOnlyTrashedExtension()
    {
        $builder = $this->newBuilder();

        $this->assertSame('select * from "users" where "users"."deleted_at" is not null', $builder->onlyTrashed()->toSql());
    }

    public function testWithoutTrashedExtension()
    {
        $builder = $this->newBuilder();

        $this->assertSame('select * from "users" where "users"."deleted_at" is null', $builder->withoutTrashed()->toSql());
    }

    protected function newConnection(): SQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "users" ("id" integer primary key, "deleted_at" text, "updated_at" text)');
        $pdo->exec("insert into \"users\" values (1, '2023-01-01 00:00:00', null)");
        $pdo->exec('insert into "users" values (2, null, null)');

        return new SQLiteConnection($pdo);
    }

    protected function newBuilder(?SQLiteConnection $connection = null, bool $scoped = true): EloquentBuilder
    {
        $builder = (new EloquentBuilder(($connection ?? new SQLiteConnection(new PDO('sqlite::memory:')))->query()))
            ->setModel(new SoftDeletingScopeModelStub);

        if ($scoped) {
            $scope = new SoftDeletingScope;
            $builder->withGlobalScope(SoftDeletingScope::class, $scope);
            $scope->extend($builder);
        }

        return $builder;
    }
}

class SoftDeletingScopeModelStub extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $dateFormat = 'Y-m-d H:i:s';
}

<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;

class UniqueConstraintViolationTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_unique_constraint', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique('single_unique_idx');
        });

        Schema::create('test_unique_constraint_composite', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');

            $table->unique(['first_name', 'last_name'], 'unique_composite_idx');
        });
    }

    private function createUniqueModel(): UniqueConstraintViolationException
    {
        UniqueSingleModel::query()->create(['name' => 'test']);
        try {
            UniqueSingleModel::query()->create(['name' => 'test']);
        } catch (UniqueConstraintViolationException $e) {
            return $e;
        }
        $this->fail('No exception was thrown');
    }


    private function createCompositeModel(): UniqueConstraintViolationException
    {
        UniqueCompositeModel::query()->create(['first_name' => 'Taylor', 'last_name' => 'Otwell']);
        try {
            UniqueCompositeModel::query()->create(['first_name' => 'Taylor', 'last_name' => 'Otwell']);
        } catch (UniqueConstraintViolationException $e) {
            return $e;
        }

        $this->fail('No exception was thrown');
    }

    #[RequiresDatabase('sqlite')]
    public function testSqliteUniqueConstraint()
    {
        $e = $this->createUniqueModel();
        $this->assertSame(['name'], $e->columns);
        $this->assertNull($e->index);
    }

    #[RequiresDatabase('sqlite')]
    public function testSqliteUniqueCompositeConstraint()
    {
        $e = $this->createCompositeModel();
        $this->assertSame(['first_name', 'last_name'], $e->columns);
        $this->assertNull($e->index);
    }

    #[RequiresDatabase('mysql')]
    public function testMysqlUniqueConstraint()
    {
        $e = $this->createUniqueModel();
        $this->assertSame('single_unique_idx', $e->index);
        $this->assertSame([], $e->columns);
    }

    #[RequiresDatabase('mysql')]
    public function testMysqlUniqueCompositeConstraint()
    {
        $e = $this->createCompositeModel();
        $this->assertSame('unique_composite_idx', $e->index);
        $this->assertSame([], $e->columns);
    }

    #[RequiresDatabase('pgsql')]
    public function testPostgresUniqueConstraint()
    {
        $e = $this->createUniqueModel();
        $this->assertSame('single_unique_idx', $e->index);
        $this->assertSame(['name'], $e->columns);
    }

    #[RequiresDatabase('pgsql')]
    public function testPostgresUniqueCompositeConstraint()
    {
        $e = $this->createCompositeModel();
        $this->assertSame('unique_composite_idx', $e->index);
        $this->assertSame(['first_name', 'last_name'], $e->columns);
    }

    #[RequiresDatabase('sqlsrv')]
    public function testSqlServerUniqueConstraint()
    {
        $e = $this->createUniqueModel();
        $this->assertSame('single_unique_idx', $e->index);
        $this->assertSame([], $e->columns);
    }

    #[RequiresDatabase('sqlsrv')]
    public function testSqlServerUniqueCompositeConstraint()
    {
        $e = $this->createCompositeModel();
        $this->assertSame('unique_composite_idx', $e->index);
        $this->assertSame([], $e->columns);
    }
}

class UniqueSingleModel extends Model
{
    protected $table = 'test_unique_constraint';

    protected $fillable = ['name'];

    public $timestamps = false;
}

class UniqueCompositeModel extends Model
{
    protected $table = 'test_unique_constraint_composite';

    protected $fillable = ['first_name', 'last_name'];

    public $timestamps = false;
}

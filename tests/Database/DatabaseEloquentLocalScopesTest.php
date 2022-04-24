<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentLocalScopesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        tap(new DB)->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ])->bootEloquent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Model::unsetConnectionResolver();
    }

    public function testCanCheckExistenceOfLocalScope()
    {
        $model = new EloquentLocalScopesTestModel;

        $this->assertTrue($model->hasNamedScope('active'));
        $this->assertTrue($model->hasNamedScope('type'));
        $this->assertTrue($model->hasNamedScope('published'));

        $this->assertFalse($model->hasNamedScope('nonExistentLocalScope'));
    }

    public function testLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->active();

        $this->assertSame('select * from "table" where "active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    public function testDynamicLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->type('foo');

        $this->assertSame('select * from "table" where "type" = ?', $query->toSql());
        $this->assertEquals(['foo'], $query->getBindings());
    }

    public function testClassBasedLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->published();

        $this->assertSame('select * from "table" where "published" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    public function testClassBasedDynamicLocalScopeIsApplied()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->price(1000);

        $this->assertSame('select * from "table" where "price" = ?', $query->toSql());
        $this->assertEquals([1000], $query->getBindings());
    }

    public function testClassAndMethodBasedLocalScopesAreChainable()
    {
        $model = new EloquentLocalScopesTestModel;
        $query = $model->newQuery()->type('foo')->price(1000);

        $this->assertSame('select * from "table" where "type" = ? and "price" = ?', $query->toSql());
        $this->assertEquals(['foo', 1000], $query->getBindings());
    }
}

class EloquentLocalScopesTestModel extends Model
{
    protected $table = 'table';

    protected static $scopes = [
        'published' => Published::class,
        'price' => Price::class,
    ];

    public function scopeActive($query)
    {
        $query->where('active', true);
    }

    public function scopeType($query, $type)
    {
        $query->where('type', $type);
    }
}

class Published
{
    public function apply($query)
    {
        $query->where('published', true);
    }
}

class Price
{
    public function apply($query, $value)
    {
        $query->where('price', $value);
    }
}

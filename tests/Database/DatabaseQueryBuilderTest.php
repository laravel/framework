<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Carbon\Carbon;
use Closure;
use DateTime;
use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Pagination\AbstractPaginator as Paginator;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Tests\Database\Fixtures\Enums\Bar;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

include_once 'Enums.php';

class DatabaseQueryBuilderTest extends TestCase
{
    protected $called;

    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicSelect()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $this->assertSame('select * from "users"', $builder->toSql());
    }

    public function testBasicSelectWithGetColumns()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processSelect');
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql) {
            $this->assertSame('select * from "users"', $sql);
        });
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql) {
            $this->assertSame('select "foo", "bar" from "users"', $sql);
        });
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql) {
            $this->assertSame('select "baz" from "users"', $sql);
        });

        $builder->from('users')->get();
        $this->assertNull($builder->columns);

        $builder->from('users')->get(['foo', 'bar']);
        $this->assertNull($builder->columns);

        $builder->from('users')->get('baz');
        $this->assertNull($builder->columns);

        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertNull($builder->columns);
    }

    public function testBasicSelectUseWritePdo()
    {
        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with('select * from `users`', [], false);
        $builder->useWritePdo()->select('*')->from('users')->get();

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with('select * from `users`', [], true);
        $builder->select('*')->from('users')->get();
    }

    public function testBasicTableWrappingProtectsQuotationMarks()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('some"table');
        $this->assertSame('select * from "some""table"', $builder->toSql());
    }

    public function testAliasWrappingAsWholeConstant()
    {
        $builder = $this->getBuilder();
        $builder->select('x.y as foo.bar')->from('baz');
        $this->assertSame('select "x"."y" as "foo.bar" from "baz"', $builder->toSql());
    }

    public function testAliasWrappingWithSpacesInDatabaseName()
    {
        $builder = $this->getBuilder();
        $builder->select('w x.y.z as foo.bar')->from('baz');
        $this->assertSame('select "w x"."y"."z" as "foo.bar" from "baz"', $builder->toSql());
    }

    public function testAddingSelects()
    {
        $builder = $this->getBuilder();
        $builder->select('foo')->addSelect('bar')->addSelect(['baz', 'boom'])->addSelect('bar')->from('users');
        $this->assertSame('select "foo", "bar", "baz", "boom" from "users"', $builder->toSql());
    }

    public function testBasicSelectWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->select('*')->from('users');
        $this->assertSame('select * from "prefix_users"', $builder->toSql());
    }

    public function testBasicSelectDistinct()
    {
        $builder = $this->getBuilder();
        $builder->distinct()->select('foo', 'bar')->from('users');
        $this->assertSame('select distinct "foo", "bar" from "users"', $builder->toSql());
    }

    public function testBasicSelectDistinctOnColumns()
    {
        $builder = $this->getBuilder();
        $builder->distinct('foo')->select('foo', 'bar')->from('users');
        $this->assertSame('select distinct "foo", "bar" from "users"', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->distinct('foo')->select('foo', 'bar')->from('users');
        $this->assertSame('select distinct on ("foo") "foo", "bar" from "users"', $builder->toSql());
    }

    public function testBasicAlias()
    {
        $builder = $this->getBuilder();
        $builder->select('foo as bar')->from('users');
        $this->assertSame('select "foo" as "bar" from "users"', $builder->toSql());
    }

    public function testAliasWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->select('*')->from('users as people');
        $this->assertSame('select * from "prefix_users" as "prefix_people"', $builder->toSql());
    }

    public function testJoinAliasesWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->select('*')->from('services')->join('translations AS t', 't.item_id', '=', 'services.id');
        $this->assertSame('select * from "prefix_services" inner join "prefix_translations" as "prefix_t" on "prefix_t"."item_id" = "prefix_services"."id"', $builder->toSql());
    }

    public function testBasicTableWrapping()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('public.users');
        $this->assertSame('select * from "public"."users"', $builder->toSql());
    }

    public function testWhenCallback()
    {
        $callback = function ($query, $condition) {
            $this->assertTrue($condition);

            $query->where('id', '=', 1);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when(true, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when(false, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "email" = ?', $builder->toSql());
    }

    public function testWhenCallbackWithReturn()
    {
        $callback = function ($query, $condition) {
            $this->assertTrue($condition);

            return $query->where('id', '=', 1);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when(true, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when(false, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "email" = ?', $builder->toSql());
    }

    public function testWhenCallbackWithDefault()
    {
        $callback = function ($query, $condition) {
            $this->assertSame('truthy', $condition);

            $query->where('id', '=', 1);
        };

        $default = function ($query, $condition) {
            $this->assertEquals(0, $condition);

            $query->where('id', '=', 2);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when('truthy', $callback, $default)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->when(0, $callback, $default)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 'foo'], $builder->getBindings());
    }

    public function testUnlessCallback()
    {
        $callback = function ($query, $condition) {
            $this->assertFalse($condition);

            $query->where('id', '=', 1);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless(false, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless(true, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "email" = ?', $builder->toSql());
    }

    public function testUnlessCallbackWithReturn()
    {
        $callback = function ($query, $condition) {
            $this->assertFalse($condition);

            return $query->where('id', '=', 1);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless(false, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless(true, $callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "email" = ?', $builder->toSql());
    }

    public function testUnlessCallbackWithDefault()
    {
        $callback = function ($query, $condition) {
            $this->assertEquals(0, $condition);

            $query->where('id', '=', 1);
        };

        $default = function ($query, $condition) {
            $this->assertSame('truthy', $condition);

            $query->where('id', '=', 2);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless(0, $callback, $default)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->unless('truthy', $callback, $default)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 'foo'], $builder->getBindings());
    }

    public function testTapCallback()
    {
        $callback = function ($query) {
            return $query->where('id', '=', 1);
        };

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->tap($callback)->where('email', 'foo');
        $this->assertSame('select * from "users" where "id" = ? and "email" = ?', $builder->toSql());
    }

    public function testBasicWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $this->assertSame('select * from "users" where "id" = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWhereNot()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot('name', 'foo')->whereNot('name', '<>', 'bar');
        $this->assertSame('select * from "users" where not "name" = ? and not "name" <> ?', $builder->toSql());
        $this->assertEquals(['foo', 'bar'], $builder->getBindings());
    }

    public function testWheresWithArrayValue()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', [12]);
        $this->assertSame('select * from "users" where "id" = ?', $builder->toSql());
        $this->assertEquals([0 => 12], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', [12, 30]);
        $this->assertSame('select * from "users" where "id" = ?', $builder->toSql());
        $this->assertEquals([0 => 12], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '!=', [12, 30]);
        $this->assertSame('select * from "users" where "id" != ?', $builder->toSql());
        $this->assertEquals([0 => 12], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '<>', [12, 30]);
        $this->assertSame('select * from "users" where "id" <> ?', $builder->toSql());
        $this->assertEquals([0 => 12], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', [[12, 30]]);
        $this->assertSame('select * from "users" where "id" = ?', $builder->toSql());
        $this->assertEquals([0 => 12], $builder->getBindings());
    }

    public function testMySqlWrappingProtectsQuotationMarks()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->From('some`table');
        $this->assertSame('select * from `some``table`', $builder->toSql());
    }

    public function testDateBasedWheresAcceptsTwoArguments()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', 1);
        $this->assertSame('select * from `users` where date(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', 1);
        $this->assertSame('select * from `users` where day(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', 1);
        $this->assertSame('select * from `users` where month(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', 1);
        $this->assertSame('select * from `users` where year(`created_at`) = ?', $builder->toSql());
    }

    public function testDateBasedOrWheresAcceptsTwoArguments()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDate('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or date(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDay('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or day(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereMonth('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or month(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereYear('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or year(`created_at`) = ?', $builder->toSql());
    }

    public function testDateBasedWheresExpressionIsNotBound()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'))->where('admin', true);
        $this->assertEquals([true], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', new Raw('NOW()'));
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', new Raw('NOW()'));
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', new Raw('NOW()'));
        $this->assertEquals([], $builder->getBindings());
    }

    public function testWhereDateMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from `users` where date(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', new Raw('NOW()'));
        $this->assertSame('select * from `users` where date(`created_at`) = NOW()', $builder->toSql());
    }

    public function testWhereDayMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from `users` where day(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testOrWhereDayMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1)->orWhereDay('created_at', '=', 2);
        $this->assertSame('select * from `users` where day(`created_at`) = ? or day(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testOrWhereDayPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1)->orWhereDay('created_at', '=', 2);
        $this->assertSame('select * from "users" where extract(day from "created_at") = ? or extract(day from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testOrWhereDaySqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1)->orWhereDay('created_at', '=', 2);
        $this->assertSame('select * from [users] where day([created_at]) = ? or day([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testWhereMonthMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from `users` where month(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testOrWhereMonthMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5)->orWhereMonth('created_at', '=', 6);
        $this->assertSame('select * from `users` where month(`created_at`) = ? or month(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 5, 1 => 6], $builder->getBindings());
    }

    public function testOrWhereMonthPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5)->orWhereMonth('created_at', '=', 6);
        $this->assertSame('select * from "users" where extract(month from "created_at") = ? or extract(month from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 5, 1 => 6], $builder->getBindings());
    }

    public function testOrWhereMonthSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5)->orWhereMonth('created_at', '=', 6);
        $this->assertSame('select * from [users] where month([created_at]) = ? or month([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 5, 1 => 6], $builder->getBindings());
    }

    public function testWhereYearMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from `users` where year(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testOrWhereYearMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014)->orWhereYear('created_at', '=', 2015);
        $this->assertSame('select * from `users` where year(`created_at`) = ? or year(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014, 1 => 2015], $builder->getBindings());
    }

    public function testOrWhereYearPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014)->orWhereYear('created_at', '=', 2015);
        $this->assertSame('select * from "users" where extract(year from "created_at") = ? or extract(year from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 2014, 1 => 2015], $builder->getBindings());
    }

    public function testOrWhereYearSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014)->orWhereYear('created_at', '=', 2015);
        $this->assertSame('select * from [users] where year([created_at]) = ? or year([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014, 1 => 2015], $builder->getBindings());
    }

    public function testWhereTimeMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from `users` where time(`created_at`) >= ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeOperatorOptionalMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from `users` where time(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeOperatorOptionalPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from [users] where cast([created_at] as time) = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', new Raw('NOW()'));
        $this->assertSame('select * from [users] where cast([created_at] as time) = NOW()', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testOrWhereTimeMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '<=', '10:00')->orWhereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from `users` where time(`created_at`) <= ? or time(`created_at`) >= ?', $builder->toSql());
        $this->assertEquals([0 => '10:00', 1 => '22:00'], $builder->getBindings());
    }

    public function testOrWhereTimePostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '<=', '10:00')->orWhereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time <= ? or "created_at"::time >= ?', $builder->toSql());
        $this->assertEquals([0 => '10:00', 1 => '22:00'], $builder->getBindings());
    }

    public function testOrWhereTimeSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '<=', '10:00')->orWhereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from [users] where cast([created_at] as time) <= ? or cast([created_at] as time) >= ?', $builder->toSql());
        $this->assertEquals([0 => '10:00', 1 => '22:00'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '<=', '10:00')->orWhereTime('created_at', new Raw('NOW()'));
        $this->assertSame('select * from [users] where cast([created_at] as time) <= ? or cast([created_at] as time) = NOW()', $builder->toSql());
        $this->assertEquals([0 => '10:00'], $builder->getBindings());
    }

    public function testWhereDatePostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from "users" where "created_at"::date = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from "users" where "created_at"::date = NOW()', $builder->toSql());
    }

    public function testWhereDayPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from "users" where extract(day from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonthPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from "users" where extract(month from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYearPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from "users" where extract(year from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereTimePostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time >= ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWherePast()
    {
        Carbon::setTestNow('2022-04-20 23:45:06.123456');

        $testDate = Carbon::create('2022-04-20 23:45:06.123456');

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->wherePast('published_at');
        $this->assertSame('select * from "posts" where "published_at" < ?', $builder->toSql());
        $this->assertEquals([0 => $testDate], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWherePast('published_at');
        $this->assertSame('select * from "posts" where "id" = ? or "published_at" < ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => $testDate], $builder->getBindings());
    }

    public function testWherePastUsesArray()
    {
        Carbon::setTestNow('2022-04-20 12:34:56.123456');

        $testDate = Carbon::create('2022-04-20 12:34:56.123456');

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->wherePast(['published_at', 'held_at']);
        $this->assertSame('select * from "posts" where "published_at" < ? and "held_at" < ?', $builder->toSql());
        $this->assertEquals([0 => $testDate, 1 => $testDate], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWherePast(['published_at', 'held_at']);
        $this->assertSame('select * from "posts" where "id" = ? or "published_at" < ? or "held_at" < ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => $testDate, 2 => $testDate], $builder->getBindings());
    }

    public function testWhereTodayMySQL()
    {
        Carbon::setTestNow('2022-04-20 12:34:56.123456');

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('posts')->whereToday('published_at');
        $this->assertSame('select * from `posts` where date(`published_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '2022-04-20'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereToday('published_at');
        $this->assertSame('select * from `posts` where `id` = ? or date(`published_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => '2022-04-20'], $builder->getBindings());
    }

    public function testPassingArrayToWhereTodayMySQL()
    {
        Carbon::setTestNow('2022-04-20 12:34:56.123456');

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('posts')->whereToday(['published_at', 'held_at']);
        $this->assertSame('select * from `posts` where date(`published_at`) = ? and date(`held_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '2022-04-20', 1 => '2022-04-20'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereToday(['published_at', 'held_at']);
        $this->assertSame('select * from `posts` where `id` = ? or date(`published_at`) = ? or date(`held_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => '2022-04-20', 2 => '2022-04-20'], $builder->getBindings());
    }

    public function testWhereTodaySqlServer()
    {
        Carbon::setTestNow('2022-04-20 12:34:56.123456');

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('posts')->whereToday('published_at');
        $this->assertSame('select * from [posts] where cast([published_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => '2022-04-20'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereToday('published_at');
        $this->assertSame('select * from [posts] where [id] = ? or cast([published_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => '2022-04-20'], $builder->getBindings());
    }

    public function testPassingArrayToWhereTodaySqlServer()
    {
        Carbon::setTestNow('2022-04-20 12:34:56.123456');

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('posts')->whereToday(['published_at', 'held_at']);
        $this->assertSame('select * from [posts] where cast([published_at] as date) = ? and cast([held_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => '2022-04-20', 1 => '2022-04-20'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereToday(['published_at', 'held_at']);
        $this->assertSame('select * from [posts] where [id] = ? or cast([published_at] as date) = ? or cast([held_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => '2022-04-20', 2 => '2022-04-20'], $builder->getBindings());
    }

    public function testWhereFuture()
    {
        Carbon::setTestNow('2022-04-22 21:01:23.123456');

        $testDate = Carbon::create('2022-04-22 21:01:23.123456');

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->whereFuture('published_at');
        $this->assertSame('select * from "posts" where "published_at" > ?', $builder->toSql());
        $this->assertEquals([0 => $testDate], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereFuture('published_at');
        $this->assertSame('select * from "posts" where "id" = ? or "published_at" > ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => $testDate], $builder->getBindings());
    }

    public function testPassingArrayToWhereFuture()
    {
        Carbon::setTestNow('2022-04-22 01:23:45.123456');

        $testDate = Carbon::create('2022-04-22 01:23:45.123456');

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->whereFuture(['published_at', 'held_at']);
        $this->assertSame('select * from "posts" where "published_at" > ? and "held_at" > ?', $builder->toSql());
        $this->assertEquals([0 => $testDate, 1 => $testDate], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('id', '=', 1)->orWhereFuture(['published_at', 'held_at']);
        $this->assertSame('select * from "posts" where "id" = ? or "published_at" > ? or "held_at" > ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => $testDate, 2 => $testDate], $builder->getBindings());
    }

    public function testWhereLikePostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', 'like', '1');
        $this->assertSame('select * from "users" where "id"::text like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', 'LIKE', '1');
        $this->assertSame('select * from "users" where "id"::text LIKE ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', 'ilike', '1');
        $this->assertSame('select * from "users" where "id"::text ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', 'not like', '1');
        $this->assertSame('select * from "users" where "id"::text not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', 'not ilike', '1');
        $this->assertSame('select * from "users" where "id"::text not ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testWhereLikeClausePostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1');
        $this->assertSame('select * from "users" where "id"::text ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1', false);
        $this->assertSame('select * from "users" where "id"::text ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1', true);
        $this->assertSame('select * from "users" where "id"::text like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1');
        $this->assertSame('select * from "users" where "id"::text not ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1', false);
        $this->assertSame('select * from "users" where "id"::text not ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1', true);
        $this->assertSame('select * from "users" where "id"::text not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testWhereLikeClauseMysql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1');
        $this->assertSame('select * from `users` where `id` like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1', false);
        $this->assertSame('select * from `users` where `id` like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1', true);
        $this->assertSame('select * from `users` where `id` like binary ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1');
        $this->assertSame('select * from `users` where `id` not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1', false);
        $this->assertSame('select * from `users` where `id` not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1', true);
        $this->assertSame('select * from `users` where `id` not like binary ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testWhereLikeClauseSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1');
        $this->assertSame('select * from "users" where "id" like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1', true);
        $this->assertSame('select * from "users" where "id" glob ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereLike('description', 'Hell* _orld?%', true);
        $this->assertSame('select * from "users" where "description" glob ?', $builder->toSql());
        $this->assertEquals([0 => 'Hell[*] ?orld[?]*'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1');
        $this->assertSame('select * from "users" where "id" not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereNotLike('description', 'Hell* _orld?%', true);
        $this->assertSame('select * from "users" where "description" not glob ?', $builder->toSql());
        $this->assertEquals([0 => 'Hell[*] ?orld[?]*'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereLike('name', 'John%', true)->whereNotLike('name', '%Doe%', true);
        $this->assertSame('select * from "users" where "name" glob ? and "name" not glob ?', $builder->toSql());
        $this->assertEquals([0 => 'John*', 1 => '*Doe*'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereLike('name', 'John%')->orWhereLike('name', 'Jane%', true);
        $this->assertSame('select * from "users" where "name" like ? or "name" glob ?', $builder->toSql());
        $this->assertEquals([0 => 'John%', 1 => 'Jane*'], $builder->getBindings());
    }

    public function testWhereLikeClauseSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1');
        $this->assertSame('select * from [users] where [id] like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereLike('id', '1')->orWhereLike('id', '2');
        $this->assertSame('select * from [users] where [id] like ? or [id] like ?', $builder->toSql());
        $this->assertEquals([0 => '1', 1 => '2'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereNotLike('id', '1');
        $this->assertSame('select * from [users] where [id] not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testWhereDateSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from "users" where strftime(\'%Y-%m-%d\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from "users" where strftime(\'%Y-%m-%d\', "created_at") = cast(NOW() as text)', $builder->toSql());
    }

    public function testWhereDaySqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from "users" where strftime(\'%d\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonthSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from "users" where strftime(\'%m\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYearSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from "users" where strftime(\'%Y\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereTimeSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where strftime(\'%H:%M:%S\', "created_at") >= cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeOperatorOptionalSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from "users" where strftime(\'%H:%M:%S\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereDateSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from [users] where cast([created_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from [users] where cast([created_at] as date) = NOW()', $builder->toSql());
    }

    public function testWhereDaySqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from [users] where day([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonthSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from [users] where month([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYearSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from [users] where year([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereBetweens()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('id', [1, 2]);
        $this->assertSame('select * from "users" where "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('id', [[1, 2, 3]]);
        $this->assertSame('select * from "users" where "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('id', [[1], [2, 3]]);
        $this->assertSame('select * from "users" where "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotBetween('id', [1, 2]);
        $this->assertSame('select * from "users" where "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('id', [new Raw(1), new Raw(2)]);
        $this->assertSame('select * from "users" where "id" between 1 and 2', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $period = now()->startOfDay()->toPeriod(now()->addDay()->startOfDay());
        $builder->select('*')->from('users')->whereBetween('created_at', $period);
        $this->assertSame('select * from "users" where "created_at" between ? and ?', $builder->toSql());
        $this->assertEquals([now()->startOfDay(), now()->addDay()->startOfDay()], $builder->getBindings());

        // custom long carbon period date
        $builder = $this->getBuilder();
        $period = now()->startOfDay()->toPeriod(now()->addMonth()->startOfDay());
        $builder->select('*')->from('users')->whereBetween('created_at', $period);
        $this->assertSame('select * from "users" where "created_at" between ? and ?', $builder->toSql());
        $this->assertEquals([now()->startOfDay(), now()->addMonth()->startOfDay()], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('id', collect([1, 2]));
        $this->assertSame('select * from "users" where "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testOrWhereBetween()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', [3, 5]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 5], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', [[3, 4, 5]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 4], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', [[3, 5]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 5], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', [[4], [6, 8]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 4, 2 => 6], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', collect([3, 4]));
        $this->assertSame('select * from "users" where "id" = ? or "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 4], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereBetween('id', [new Raw(3), new Raw(4)]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between 3 and 4', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testOrWhereNotBetween()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', [3, 5]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 5], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', [[3, 4, 5]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 4], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', [[3, 5]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 5], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', [[4], [6, 8]]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 4, 2 => 6], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', collect([3, 4]));
        $this->assertSame('select * from "users" where "id" = ? or "id" not between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 3, 2 => 4], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotBetween('id', [new Raw(3), new Raw(4)]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between 3 and 4', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereBetweenColumns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetweenColumns('id', ['users.created_at', 'users.updated_at']);
        $this->assertSame('select * from "users" where "id" between "users"."created_at" and "users"."updated_at"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotBetweenColumns('id', ['created_at', 'updated_at']);
        $this->assertSame('select * from "users" where "id" not between "created_at" and "updated_at"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetweenColumns('id', [new Raw(1), new Raw(2)]);
        $this->assertSame('select * from "users" where "id" between 1 and 2', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testOrWhereBetweenColumns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereBetweenColumns('id', ['users.created_at', 'users.updated_at']);
        $this->assertSame('select * from "users" where "id" = ? or "id" between "users"."created_at" and "users"."updated_at"', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereBetweenColumns('id', ['created_at', 'updated_at']);
        $this->assertSame('select * from "users" where "id" = ? or "id" between "created_at" and "updated_at"', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereBetweenColumns('id', [new Raw(1), new Raw(2)]);
        $this->assertSame('select * from "users" where "id" = ? or "id" between 1 and 2', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());
    }

    public function testOrWhereNotBetweenColumns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereNotBetweenColumns('id', ['users.created_at', 'users.updated_at']);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between "users"."created_at" and "users"."updated_at"', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereNotBetweenColumns('id', ['created_at', 'updated_at']);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between "created_at" and "updated_at"', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 2)->orWhereNotBetweenColumns('id', [new Raw(1), new Raw(2)]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not between 1 and 2', $builder->toSql());
        $this->assertEquals([0 => 2], $builder->getBindings());
    }

    public function testBasicOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhere('email', '=', 'foo');
        $this->assertSame('select * from "users" where "id" = ? or "email" = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testBasicOrWhereNot()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orWhereNot('name', 'foo')->orWhereNot('name', '<>', 'bar');
        $this->assertSame('select * from "users" where not "name" = ? or not "name" <> ?', $builder->toSql());
        $this->assertEquals(['foo', 'bar'], $builder->getBindings());
    }

    public function testRawWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereRaw('id = ? or email = ?', [1, 'foo']);
        $this->assertSame('select * from "users" where id = ? or email = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testRawOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereRaw('email = ?', ['foo']);
        $this->assertSame('select * from "users" where "id" = ? or email = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testBasicWhereIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', [1, 2, 3]);
        $this->assertSame('select * from "users" where "id" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $builder->getBindings());

        // associative arrays as values:
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', [
            'issue' => 45582,
            'id' => 2,
            3,
        ]);
        $this->assertSame('select * from "users" where "id" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 45582, 1 => 2, 2 => 3], $builder->getBindings());

        // can accept some nested arrays as values.
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', [
            ['issue' => 45582],
            ['id' => 2],
            [3],
        ]);
        $this->assertSame('select * from "users" where "id" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 45582, 1 => 2, 2 => 3], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereIn('id', [1, 2, 3]);
        $this->assertSame('select * from "users" where "id" = ? or "id" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 1, 2 => 2, 3 => 3], $builder->getBindings());
    }

    public function testBasicWhereInsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', [
            [
                'a' => 1,
                'b' => 1,
            ],
            ['c' => 2],
            [3],
        ]);
    }

    public function testBasicWhereNotIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotIn('id', [1, 2, 3]);
        $this->assertSame('select * from "users" where "id" not in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotIn('id', [1, 2, 3]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 1, 2 => 2, 3 => 3], $builder->getBindings());
    }

    public function testRawWhereIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', [new Raw(1)]);
        $this->assertSame('select * from "users" where "id" in (1)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereIn('id', [new Raw(1)]);
        $this->assertSame('select * from "users" where "id" = ? or "id" in (1)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testEmptyWhereIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', []);
        $this->assertSame('select * from "users" where 0 = 1', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereIn('id', []);
        $this->assertSame('select * from "users" where "id" = ? or 0 = 1', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testEmptyWhereNotIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotIn('id', []);
        $this->assertSame('select * from "users" where 1 = 1', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotIn('id', []);
        $this->assertSame('select * from "users" where "id" = ? or 1 = 1', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereIntegerInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIntegerInRaw('id', [
            '1a', 2, Bar::FOO,
        ]);
        $this->assertSame('select * from "users" where "id" in (1, 2, 5)', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIntegerInRaw('id', [
            ['id' => '1a'],
            ['id' => 2],
            ['any' => '3'],
            ['id' => Bar::FOO],
        ]);
        $this->assertSame('select * from "users" where "id" in (1, 2, 3, 5)', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testOrWhereIntegerInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereIntegerInRaw('id', ['1a', 2]);
        $this->assertSame('select * from "users" where "id" = ? or "id" in (1, 2)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereIntegerNotInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIntegerNotInRaw('id', ['1a', 2]);
        $this->assertSame('select * from "users" where "id" not in (1, 2)', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testOrWhereIntegerNotInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereIntegerNotInRaw('id', ['1a', 2]);
        $this->assertSame('select * from "users" where "id" = ? or "id" not in (1, 2)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testEmptyWhereIntegerInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIntegerInRaw('id', []);
        $this->assertSame('select * from "users" where 0 = 1', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testEmptyWhereIntegerNotInRaw()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIntegerNotInRaw('id', []);
        $this->assertSame('select * from "users" where 1 = 1', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testBasicWhereColumn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn('first_name', 'last_name')->orWhereColumn('first_name', 'middle_name');
        $this->assertSame('select * from "users" where "first_name" = "last_name" or "first_name" = "middle_name"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn('updated_at', '>', 'created_at');
        $this->assertSame('select * from "users" where "updated_at" > "created_at"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testArrayWhereColumn()
    {
        $conditions = [
            ['first_name', 'last_name'],
            ['updated_at', '>', 'created_at'],
        ];

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn($conditions);
        $this->assertSame('select * from "users" where ("first_name" = "last_name" and "updated_at" > "created_at")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testWhereFulltextMySql()
    {
        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World');
        $this->assertSame('select * from `users` where match (`body`) against (? in natural language mode)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World', ['expanded' => true]);
        $this->assertSame('select * from `users` where match (`body`) against (? in natural language mode with query expansion)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', '+Hello -World', ['mode' => 'boolean']);
        $this->assertSame('select * from `users` where match (`body`) against (? in boolean mode)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', '+Hello -World', ['mode' => 'boolean', 'expanded' => true]);
        $this->assertSame('select * from `users` where match (`body`) against (? in boolean mode)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText(['body', 'title'], 'Car,Plane');
        $this->assertSame('select * from `users` where match (`body`, `title`) against (? in natural language mode)', $builder->toSql());
        $this->assertEquals(['Car,Plane'], $builder->getBindings());
    }

    public function testWhereFulltextPostgres()
    {
        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World');
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World', ['language' => 'simple']);
        $this->assertSame('select * from "users" where (to_tsvector(\'simple\', "body")) @@ plainto_tsquery(\'simple\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World', ['mode' => 'plain']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World', ['mode' => 'phrase']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ phraseto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', '+Hello -World', ['mode' => 'websearch']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ websearch_to_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText('body', 'Hello World', ['language' => 'simple', 'mode' => 'plain']);
        $this->assertSame('select * from "users" where (to_tsvector(\'simple\', "body")) @@ plainto_tsquery(\'simple\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getPostgresBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFullText(['body', 'title'], 'Car Plane');
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body") || to_tsvector(\'english\', "title")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Car Plane'], $builder->getBindings());
    }

    public function testWhereAll()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAll(['last_name', 'email'], '%Otwell%');
        $this->assertSame('select * from "users" where ("last_name" = ? and "email" = ?)', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAll(['last_name', 'email'], 'not like', '%Otwell%');
        $this->assertSame('select * from "users" where ("last_name" not like ? and "email" not like ?)', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAll([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where (("last_name" like ?) and ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testOrWhereAll()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAll(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" like ? and "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->whereAll(['last_name', 'email'], 'like', '%Otwell%', 'or');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" like ? and "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAll(['last_name', 'email'], '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" = ? and "email" = ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAll([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where "first_name" like ? or (("last_name" like ?) and ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testWhereAny()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAny(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAny(['last_name', 'email'], '%Otwell%');
        $this->assertSame('select * from "users" where ("last_name" = ? or "email" = ?)', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAny([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where (("last_name" like ?) or ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testOrWhereAny()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAny(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->whereAny(['last_name', 'email'], 'like', '%Otwell%', 'or');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAny(['last_name', 'email'], '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or ("last_name" = ? or "email" = ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereAny([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where "first_name" like ? or (("last_name" like ?) or ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testWhereNone()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNone(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where not ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNone(['last_name', 'email'], 'Otwell');
        $this->assertSame('select * from "users" where not ("last_name" = ? or "email" = ?)', $builder->toSql());
        $this->assertEquals(['Otwell', 'Otwell'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->whereNone(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? and not ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNone([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where not (("last_name" like ?) or ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testOrWhereNone()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereNone(['last_name', 'email'], 'like', '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or not ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->whereNone(['last_name', 'email'], 'like', '%Otwell%', 'or');
        $this->assertSame('select * from "users" where "first_name" like ? or not ("last_name" like ? or "email" like ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereNone(['last_name', 'email'], '%Otwell%');
        $this->assertSame('select * from "users" where "first_name" like ? or not ("last_name" = ? or "email" = ?)', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('first_name', 'like', '%Taylor%')->orWhereNone([
            fn (Builder $query) => $query->where('last_name', 'like', '%Otwell%'),
            fn (Builder $query) => $query->where('email', 'like', '%Otwell%'),
        ]);
        $this->assertSame('select * from "users" where "first_name" like ? or not (("last_name" like ?) or ("email" like ?))', $builder->toSql());
        $this->assertEquals(['%Taylor%', '%Otwell%', '%Otwell%'], $builder->getBindings());
    }

    public function testUnions()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from "users" where "id" = ?) union (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getMySqlBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from `users` where `id` = ?) union (select * from `users` where `id` = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getMysqlBuilder();
        $expectedSql = '(select `a` from `t1` where `a` = ? and `b` = ?) union (select `a` from `t2` where `a` = ? and `b` = ?) order by `a` asc limit 10';
        $union = $this->getMysqlBuilder()->select('a')->from('t2')->where('a', 11)->where('b', 2);
        $builder->select('a')->from('t1')->where('a', 10)->where('b', 1)->union($union)->orderBy('a')->limit(10);
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 10, 1 => 1, 2 => 11, 3 => 2], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $expectedSql = '(select "name" from "users" where "id" = ?) union (select "name" from "users" where "id" = ?)';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getPostgresBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $expectedSql = 'select * from (select "name" from "users" where "id" = ?) union select * from (select "name" from "users" where "id" = ?)';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getSQLiteBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $expectedSql = 'select * from (select [name] from [users] where [id] = ?) as [temp_table] union select * from (select [name] from [users] where [id] = ?) as [temp_table]';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getSqlServerBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $eloquentBuilder = new EloquentBuilder($this->getBuilder());
        $builder->select('*')->from('users')->where('id', '=', 1)->union($eloquentBuilder->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from "users" where "id" = ?) union (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionAlls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->unionAll($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $expectedSql = '(select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?)';
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->unionAll($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $eloquentBuilder = new EloquentBuilder($this->getBuilder());
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->unionAll($eloquentBuilder->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testMultipleUnions()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $builder->union($this->getBuilder()->select('*')->from('users')->where('id', '=', 3));
        $this->assertSame('(select * from "users" where "id" = ?) union (select * from "users" where "id" = ?) union (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $builder->getBindings());
    }

    public function testMultipleUnionAlls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->unionAll($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $builder->unionAll($this->getBuilder()->select('*')->from('users')->where('id', '=', 3));
        $this->assertSame('(select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $builder->getBindings());
    }

    public function testUnionOrderBys()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $builder->orderBy('id', 'desc');
        $this->assertSame('(select * from "users" where "id" = ?) union (select * from "users" where "id" = ?) order by "id" desc', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionLimitsAndOffsets()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getBuilder()->select('*')->from('dogs'));
        $builder->skip(5)->take(10);
        $this->assertSame('(select * from "users") union (select * from "dogs") limit 10 offset 5', $builder->toSql());

        $expectedSql = '(select * from "users") union (select * from "dogs") limit 10 offset 5';
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getBuilder()->select('*')->from('dogs'));
        $builder->skip(5)->take(10);
        $this->assertEquals($expectedSql, $builder->toSql());

        $expectedSql = '(select * from "users" limit 11) union (select * from "dogs" limit 22) limit 10 offset 5';
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->limit(11);
        $builder->union($this->getBuilder()->select('*')->from('dogs')->limit(22));
        $builder->skip(5)->take(10);
        $this->assertEquals($expectedSql, $builder->toSql());
    }

    public function testUnionWithJoin()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getBuilder()->select('*')->from('dogs')->join('breeds', function ($join) {
            $join->on('dogs.breed_id', '=', 'breeds.id')
                ->where('breeds.is_native', '=', 1);
        }));
        $this->assertSame('(select * from "users") union (select * from "dogs" inner join "breeds" on "dogs"."breed_id" = "breeds"."id" and "breeds"."is_native" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testMySqlUnionOrderBys()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getMySqlBuilder()->select('*')->from('users')->where('id', '=', 2));
        $builder->orderBy('id', 'desc');
        $this->assertSame('(select * from `users` where `id` = ?) union (select * from `users` where `id` = ?) order by `id` desc', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testMySqlUnionLimitsAndOffsets()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getMySqlBuilder()->select('*')->from('dogs'));
        $builder->skip(5)->take(10);
        $this->assertSame('(select * from `users`) union (select * from `dogs`) limit 10 offset 5', $builder->toSql());
    }

    public function testUnionAggregate()
    {
        $expected = 'select count(*) as aggregate from ((select * from `posts`) union (select * from `videos`)) as `temp_table`';
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getMySqlBuilder()->from('videos'))->count();

        $expected = 'select count(*) as aggregate from ((select `id` from `posts`) union (select `id` from `videos`)) as `temp_table`';
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->select('id')->union($this->getMySqlBuilder()->from('videos')->select('id'))->count();

        $expected = 'select count(*) as aggregate from ((select * from "posts") union (select * from "videos")) as "temp_table"';
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getPostgresBuilder()->from('videos'))->count();

        $expected = 'select count(*) as aggregate from (select * from (select * from "posts") union select * from (select * from "videos")) as "temp_table"';
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getSQLiteBuilder()->from('videos'))->count();

        $expected = 'select count(*) as aggregate from (select * from (select * from [posts]) as [temp_table] union select * from (select * from [videos]) as [temp_table]) as [temp_table]';
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getSqlServerBuilder()->from('videos'))->count();
    }

    public function testHavingAggregate()
    {
        $expected = 'select count(*) as aggregate from (select (select `count(*)` from `videos` where `posts`.`id` = `videos`.`post_id`) as `videos_count` from `posts` having `videos_count` > ?) as `temp_table`';
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [0 => 1], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $builder->from('posts')->selectSub(function ($query) {
            $query->from('videos')->select('count(*)')->whereColumn('posts.id', '=', 'videos.post_id');
        }, 'videos_count')->having('videos_count', '>', 1);
        $builder->count();
    }

    public function testSubSelectWhereIns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereIn('id', function ($q) {
            $q->select('id')->from('users')->where('age', '>', 25)->take(3);
        });
        $this->assertSame('select * from "users" where "id" in (select "id" from "users" where "age" > ? limit 3)', $builder->toSql());
        $this->assertEquals([25], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotIn('id', function ($q) {
            $q->select('id')->from('users')->where('age', '>', 25)->take(3);
        });
        $this->assertSame('select * from "users" where "id" not in (select "id" from "users" where "age" > ? limit 3)', $builder->toSql());
        $this->assertEquals([25], $builder->getBindings());
    }

    public function testBasicWhereNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNull('id');
        $this->assertSame('select * from "users" where "id" is null', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNull('id');
        $this->assertSame('select * from "users" where "id" = ? or "id" is null', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWhereNullExpressionsMysql()
    {
        $builder = $this->getMysqlBuilder();
        $builder->select('*')->from('users')->whereNull(new Raw('id'));
        $this->assertSame('select * from `users` where id is null', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getMysqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNull(new Raw('id'));
        $this->assertSame('select * from `users` where `id` = ? or id is null', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testJsonWhereNullMysql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNull('items->id');
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is null OR json_type(json_extract(`items`, \'$."id"\')) = \'NULL\')', $builder->toSql());
    }

    public function testJsonWhereNotNullMysql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNotNull('items->id');
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is not null AND json_type(json_extract(`items`, \'$."id"\')) != \'NULL\')', $builder->toSql());
    }

    public function testJsonWhereNullExpressionMysql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNull(new Raw('items->id'));
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is null OR json_type(json_extract(`items`, \'$."id"\')) = \'NULL\')', $builder->toSql());
    }

    public function testJsonWhereNotNullExpressionMysql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereNotNull(new Raw('items->id'));
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is not null AND json_type(json_extract(`items`, \'$."id"\')) != \'NULL\')', $builder->toSql());
    }

    public function testArrayWhereNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNull(['id', 'expires_at']);
        $this->assertSame('select * from "users" where "id" is null and "expires_at" is null', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereNull(['id', 'expires_at']);
        $this->assertSame('select * from "users" where "id" = ? or "id" is null or "expires_at" is null', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWhereNotNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotNull('id');
        $this->assertSame('select * from "users" where "id" is not null', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '>', 1)->orWhereNotNull('id');
        $this->assertSame('select * from "users" where "id" > ? or "id" is not null', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testArrayWhereNotNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotNull(['id', 'expires_at']);
        $this->assertSame('select * from "users" where "id" is not null and "expires_at" is not null', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '>', 1)->orWhereNotNull(['id', 'expires_at']);
        $this->assertSame('select * from "users" where "id" > ? or "id" is not null or "expires_at" is not null', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testGroupBys()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy('email');
        $this->assertSame('select * from "users" group by "email"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy('id', 'email');
        $this->assertSame('select * from "users" group by "id", "email"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy(['id', 'email']);
        $this->assertSame('select * from "users" group by "id", "email"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy(new Raw('DATE(created_at)'));
        $this->assertSame('select * from "users" group by DATE(created_at)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupByRaw('DATE(created_at), ? DESC', ['foo']);
        $this->assertSame('select * from "users" group by DATE(created_at), ? DESC', $builder->toSql());
        $this->assertEquals(['foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->havingRaw('?', ['havingRawBinding'])->groupByRaw('?', ['groupByRawBinding'])->whereRaw('?', ['whereRawBinding']);
        $this->assertEquals(['whereRawBinding', 'groupByRawBinding', 'havingRawBinding'], $builder->getBindings());
    }

    public function testOrderBys()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
        $this->assertSame('select * from "users" order by "email" asc, "age" desc', $builder->toSql());

        $builder->orders = null;
        $this->assertSame('select * from "users"', $builder->toSql());

        $builder->orders = [];
        $this->assertSame('select * from "users"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('email')->orderByRaw('"age" ? desc', ['foo']);
        $this->assertSame('select * from "users" order by "email" asc, "age" ? desc', $builder->toSql());
        $this->assertEquals(['foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderByDesc('name');
        $this->assertSame('select * from "users" order by "name" desc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('public', 1)
            ->unionAll($this->getBuilder()->select('*')->from('videos')->where('public', 1))
            ->orderByRaw('field(category, ?, ?) asc', ['news', 'opinion']);
        $this->assertSame('(select * from "posts" where "public" = ?) union all (select * from "videos" where "public" = ?) order by field(category, ?, ?) asc', $builder->toSql());
        $this->assertEquals([1, 1, 'news', 'opinion'], $builder->getBindings());
    }

    public function testLatest()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->latest();
        $this->assertSame('select * from "users" order by "created_at" desc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->latest()->limit(1);
        $this->assertSame('select * from "users" order by "created_at" desc limit 1', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->latest('updated_at');
        $this->assertSame('select * from "users" order by "updated_at" desc', $builder->toSql());
    }

    public function testOldest()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->oldest();
        $this->assertSame('select * from "users" order by "created_at" asc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->oldest()->limit(1);
        $this->assertSame('select * from "users" order by "created_at" asc limit 1', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->oldest('updated_at');
        $this->assertSame('select * from "users" order by "updated_at" asc', $builder->toSql());
    }

    public function testInRandomOrderMySql()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->inRandomOrder();
        $this->assertSame('select * from "users" order by RANDOM()', $builder->toSql());
    }

    public function testInRandomOrderPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->inRandomOrder();
        $this->assertSame('select * from "users" order by RANDOM()', $builder->toSql());
    }

    public function testInRandomOrderSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->inRandomOrder();
        $this->assertSame('select * from [users] order by NEWID()', $builder->toSql());
    }

    public function testOrderBysSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
        $this->assertSame('select * from [users] order by [email] asc, [age] desc', $builder->toSql());

        $builder->orders = null;
        $this->assertSame('select * from [users]', $builder->toSql());

        $builder->orders = [];
        $this->assertSame('select * from [users]', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->orderBy('email');
        $this->assertSame('select * from [users] order by [email] asc', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->orderByDesc('name');
        $this->assertSame('select * from [users] order by [name] desc', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->orderByRaw('[age] asc');
        $this->assertSame('select * from [users] order by [age] asc', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->orderBy('email')->orderByRaw('[age] ? desc', ['foo']);
        $this->assertSame('select * from [users] order by [email] asc, [age] ? desc', $builder->toSql());
        $this->assertEquals(['foo'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(25)->take(10)->orderByRaw('[email] desc');
        $this->assertSame('select * from [users] order by [email] desc offset 25 rows fetch next 10 rows only', $builder->toSql());
    }

    public function testReorder()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('name');
        $this->assertSame('select * from "users" order by "name" asc', $builder->toSql());
        $builder->reorder();
        $this->assertSame('select * from "users"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('name');
        $this->assertSame('select * from "users" order by "name" asc', $builder->toSql());
        $builder->reorder('email', 'desc');
        $this->assertSame('select * from "users" order by "email" desc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('first');
        $builder->union($this->getBuilder()->select('*')->from('second'));
        $builder->orderBy('name');
        $this->assertSame('(select * from "first") union (select * from "second") order by "name" asc', $builder->toSql());
        $builder->reorder();
        $this->assertSame('(select * from "first") union (select * from "second")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderByRaw('?', [true]);
        $this->assertEquals([true], $builder->getBindings());
        $builder->reorder();
        $this->assertEquals([], $builder->getBindings());
    }

    public function testOrderBySubQueries()
    {
        $expected = 'select * from "users" order by (select "created_at" from "logins" where "user_id" = "users"."id" limit 1)';
        $subQuery = function ($query) {
            return $query->select('created_at')->from('logins')->whereColumn('user_id', 'users.id')->limit(1);
        };

        $builder = $this->getBuilder()->select('*')->from('users')->orderBy($subQuery);
        $this->assertSame("$expected asc", $builder->toSql());

        $builder = $this->getBuilder()->select('*')->from('users')->orderBy($subQuery, 'desc');
        $this->assertSame("$expected desc", $builder->toSql());

        $builder = $this->getBuilder()->select('*')->from('users')->orderByDesc($subQuery);
        $this->assertSame("$expected desc", $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('posts')->where('public', 1)
            ->unionAll($this->getBuilder()->select('*')->from('videos')->where('public', 1))
            ->orderBy($this->getBuilder()->selectRaw('field(category, ?, ?)', ['news', 'opinion']));
        $this->assertSame('(select * from "posts" where "public" = ?) union all (select * from "videos" where "public" = ?) order by (select field(category, ?, ?)) asc', $builder->toSql());
        $this->assertEquals([1, 1, 'news', 'opinion'], $builder->getBindings());
    }

    public function testOrderByInvalidDirectionParam()
    {
        $this->expectException(InvalidArgumentException::class);

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('age', 'asec');
    }

    public function testHavings()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('email', '>', 1);
        $this->assertSame('select * from "users" having "email" > ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->orHaving('email', '=', 'test@example.com')
            ->orHaving('email', '=', 'test2@example.com');
        $this->assertSame('select * from "users" having "email" = ? or "email" = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy('email')->having('email', '>', 1);
        $this->assertSame('select * from "users" group by "email" having "email" > ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('email as foo_email')->from('users')->having('foo_email', '>', 1);
        $this->assertSame('select "email" as "foo_email" from "users" having "foo_email" > ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->having('total', '>', new Raw('3'));
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" > 3', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->having('total', '>', 3);
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" > ?', $builder->toSql());
    }

    public function testNestedHavings()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('email', '=', 'foo')->orHaving(function ($q) {
            $q->having('name', '=', 'bar')->having('age', '=', 25);
        });
        $this->assertSame('select * from "users" having "email" = ? or ("name" = ? and "age" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'foo', 1 => 'bar', 2 => 25], $builder->getBindings());
    }

    public function testNestedHavingBindings()
    {
        $builder = $this->getBuilder();
        $builder->having('email', '=', 'foo')->having(function ($q) {
            $q->selectRaw('?', ['ignore'])->having('name', '=', 'bar');
        });
        $this->assertEquals([0 => 'foo', 1 => 'bar'], $builder->getBindings());
    }

    public function testHavingBetweens()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingBetween('id', [1, 2, 3]);
        $this->assertSame('select * from "users" having "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingBetween('id', [[1, 2], [3, 4]]);
        $this->assertSame('select * from "users" having "id" between ? and ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testHavingNull()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingNull('email');
        $this->assertSame('select * from "users" having "email" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->havingNull('email')
            ->havingNull('phone');
        $this->assertSame('select * from "users" having "email" is null and "phone" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->orHavingNull('email')
            ->orHavingNull('phone');
        $this->assertSame('select * from "users" having "email" is null or "phone" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy('email')->havingNull('email');
        $this->assertSame('select * from "users" group by "email" having "email" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('email as foo_email')->from('users')->havingNull('foo_email');
        $this->assertSame('select "email" as "foo_email" from "users" having "foo_email" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->havingNull('total');
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->havingNull('total');
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" is null', $builder->toSql());
    }

    public function testHavingNotNull()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingNotNull('email');
        $this->assertSame('select * from "users" having "email" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->havingNotNull('email')
            ->havingNotNull('phone');
        $this->assertSame('select * from "users" having "email" is not null and "phone" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')
            ->orHavingNotNull('email')
            ->orHavingNotNull('phone');
        $this->assertSame('select * from "users" having "email" is not null or "phone" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->groupBy('email')->havingNotNull('email');
        $this->assertSame('select * from "users" group by "email" having "email" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('email as foo_email')->from('users')->havingNotNull('foo_email');
        $this->assertSame('select "email" as "foo_email" from "users" having "foo_email" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->havingNotNull('total');
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['category', new Raw('count(*) as "total"')])->from('item')->where('department', '=', 'popular')->groupBy('category')->havingNotNull('total');
        $this->assertSame('select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" is not null', $builder->toSql());
    }

    public function testHavingExpression()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having(
            new class() implements ConditionExpression
            {
                public function getValue(\Illuminate\Database\Grammar $grammar)
                {
                    return '1 = 1';
                }
            }
        );
        $this->assertSame('select * from "users" having 1 = 1', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function testHavingShortcut()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('email', 1)->orHaving('email', 2);
        $this->assertSame('select * from "users" having "email" = ? or "email" = ?', $builder->toSql());
    }

    public function testHavingFollowedBySelectGet()
    {
        $builder = $this->getBuilder();
        $query = 'select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" > ?';
        $builder->getConnection()->shouldReceive('select')->once()->with($query, ['popular', 3], true)->andReturn([['category' => 'rock', 'total' => 5]]);
        $builder->getProcessor()->shouldReceive('processSelect')->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('item');
        $result = $builder->select(['category', new Raw('count(*) as "total"')])->where('department', '=', 'popular')->groupBy('category')->having('total', '>', 3)->get();
        $this->assertEquals([['category' => 'rock', 'total' => 5]], $result->all());

        // Using \Raw value
        $builder = $this->getBuilder();
        $query = 'select "category", count(*) as "total" from "item" where "department" = ? group by "category" having "total" > 3';
        $builder->getConnection()->shouldReceive('select')->once()->with($query, ['popular'], true)->andReturn([['category' => 'rock', 'total' => 5]]);
        $builder->getProcessor()->shouldReceive('processSelect')->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('item');
        $result = $builder->select(['category', new Raw('count(*) as "total"')])->where('department', '=', 'popular')->groupBy('category')->having('total', '>', new Raw('3'))->get();
        $this->assertEquals([['category' => 'rock', 'total' => 5]], $result->all());
    }

    public function testRawHavings()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingRaw('user_foo < user_bar');
        $this->assertSame('select * from "users" having user_foo < user_bar', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('baz', '=', 1)->orHavingRaw('user_foo < user_bar');
        $this->assertSame('select * from "users" having "baz" = ? or user_foo < user_bar', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->havingBetween('last_login_date', ['2018-11-16', '2018-12-16'])->orHavingRaw('user_foo < user_bar');
        $this->assertSame('select * from "users" having "last_login_date" between ? and ? or user_foo < user_bar', $builder->toSql());
    }

    public function testLimitsAndOffsets()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->offset(5)->limit(10);
        $this->assertSame('select * from "users" limit 10 offset 5', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->limit(null);
        $this->assertSame('select * from "users"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->limit(0);
        $this->assertSame('select * from "users" limit 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->skip(5)->take(10);
        $this->assertSame('select * from "users" limit 10 offset 5', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->skip(0)->take(0);
        $this->assertSame('select * from "users" limit 0 offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->skip(-5)->take(-10);
        $this->assertSame('select * from "users" offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->skip(null)->take(null);
        $this->assertSame('select * from "users" offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->skip(5)->take(null);
        $this->assertSame('select * from "users" offset 5', $builder->toSql());
    }

    public function testForPage()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(2, 15);
        $this->assertSame('select * from "users" limit 15 offset 15', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(0, 15);
        $this->assertSame('select * from "users" limit 15 offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(-2, 15);
        $this->assertSame('select * from "users" limit 15 offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(2, 0);
        $this->assertSame('select * from "users" limit 0 offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(0, 0);
        $this->assertSame('select * from "users" limit 0 offset 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->forPage(-2, 0);
        $this->assertSame('select * from "users" limit 0 offset 0', $builder->toSql());
    }

    public function testGetCountForPaginationWithBindings()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->selectSub(function ($q) {
            $q->select('body')->from('posts')->where('id', 4);
        }, 'post');

        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $count = $builder->getCountForPagination();
        $this->assertEquals(1, $count);
        $this->assertEquals([4], $builder->getBindings());
    }

    public function testGetCountForPaginationWithColumnAliases()
    {
        $builder = $this->getBuilder();
        $columns = ['body as post_body', 'teaser', 'posts.created as published'];
        $builder->from('posts')->select($columns);

        $builder->getConnection()->shouldReceive('select')->once()->with('select count("body", "teaser", "posts"."created") as aggregate from "posts"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $count = $builder->getCountForPagination($columns);
        $this->assertEquals(1, $count);
    }

    public function testGetCountForPaginationWithUnion()
    {
        $builder = $this->getBuilder();
        $builder->from('posts')->select('id')->union($this->getBuilder()->from('videos')->select('id'));

        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from ((select "id" from "posts") union (select "id" from "videos")) as "temp_table"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $count = $builder->getCountForPagination();
        $this->assertEquals(1, $count);
    }

    public function testGetCountForPaginationWithUnionOrders()
    {
        $builder = $this->getBuilder();
        $builder->from('posts')->select('id')->union($this->getBuilder()->from('videos')->select('id'))->latest();

        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from ((select "id" from "posts") union (select "id" from "videos")) as "temp_table"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $count = $builder->getCountForPagination();
        $this->assertEquals(1, $count);
    }

    public function testGetCountForPaginationWithUnionLimitAndOffset()
    {
        $builder = $this->getBuilder();
        $builder->from('posts')->select('id')->union($this->getBuilder()->from('videos')->select('id'))->take(15)->skip(1);

        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from ((select "id" from "posts") union (select "id" from "videos")) as "temp_table"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });

        $count = $builder->getCountForPagination();
        $this->assertEquals(1, $count);
    }

    public function testWhereShortcut()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhere('name', 'foo');
        $this->assertSame('select * from "users" where "id" = ? or "name" = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testOrWheresHaveConsistentResults()
    {
        $queries = [];
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere(['foo' => 1, 'bar' => 2]);
        $queries[] = $builder->toSql();

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere([['foo', 1], ['bar', 2]]);
        $queries[] = $builder->toSql();

        $this->assertSame([
            'select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)',
            'select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)',
        ], $queries);

        $queries = [];
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereColumn(['foo' => '_foo', 'bar' => '_bar']);
        $queries[] = $builder->toSql();

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereColumn([['foo', '_foo'], ['bar', '_bar']]);
        $queries[] = $builder->toSql();

        $this->assertSame([
            'select * from "users" where "xxxx" = ? or ("foo" = "_foo" or "bar" = "_bar")',
            'select * from "users" where "xxxx" = ? or ("foo" = "_foo" or "bar" = "_bar")',
        ], $queries);
    }

    public function testWhereWithArrayConditions()
    {
        /*
         * where(key, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', 2]]);
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', 2]], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', 2]], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where(['foo' => 1, 'bar' => 2], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where(['foo' => 1, 'bar' => 2], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        /*
         * where(key, <, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" < ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', '<', 2]], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = ? or "bar" < ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where([['foo', 1], ['bar', '<', 2]], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" < ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        /*
         * whereNot(key, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', 2]]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', 2]], boolean: 'or');
        $this->assertSame('select * from "users" where not (("foo" = ? or "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', 2]], boolean: 'and');
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot(['foo' => 1, 'bar' => 2], boolean: 'or');
        $this->assertSame('select * from "users" where not (("foo" = ? or "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot(['foo' => 1, 'bar' => 2], boolean: 'and');
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        /*
         * whereNot(key, <, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" < ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', '<', 2]], boolean: 'or');
        $this->assertSame('select * from "users" where not (("foo" = ? or "bar" < ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', '<', 2]], boolean: 'and');
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" < ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        /*
         * whereColumn(col1, col2)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '_bar']]);
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '_bar']], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = "_foo" or "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '_bar']], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn(['foo' => '_foo', 'bar' => '_bar']);
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn(['foo' => '_foo', 'bar' => '_bar'], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = "_foo" or "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn(['foo' => '_foo', 'bar' => '_bar'], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        /*
         * whereColumn(col1, <, col2)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '<', '_bar']]);
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" < "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '<', '_bar']], boolean: 'or');
        $this->assertSame('select * from "users" where ("foo" = "_foo" or "bar" < "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn([['foo', '_foo'], ['bar', '<', '_bar']], boolean: 'and');
        $this->assertSame('select * from "users" where ("foo" = "_foo" and "bar" < "_bar")', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        /*
         * whereAll([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAll(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAll(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        /*
         * whereAny([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAny(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereAny(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        /*
         * whereNone([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNone(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where not ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNone(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where not ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 2, 1 => 2], $builder->getBindings());

        /*
         * where()->orWhere(key, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere([['foo', 1], ['bar', 2]]);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhere(key, <, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" < ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereColumn(col1, col2)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereColumn([['foo', '_foo'], ['bar', '_bar']]);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = "_foo" or "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([0 => 'xxxx'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereColumn(['foo' => '_foo', 'bar' => '_bar']);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = "_foo" or "bar" = "_bar")', $builder->toSql());
        $this->assertEquals([0 => 'xxxx'], $builder->getBindings());

        /*
         * where()->orWhere(key, <, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhere([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" < ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereNot(key, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereNot([['foo', 1], ['bar', 2]]);
        $this->assertSame('select * from "users" where "xxxx" = ? or not (("foo" = ? or "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereNot(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "users" where "xxxx" = ? or not (("foo" = ? or "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereNot(key, <, value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereNot([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where "xxxx" = ? or not (("foo" = ? or "bar" < ?))', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 1, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereAll([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereAll(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereAll(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? and "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereAny([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereAny(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereAny(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());

        /*
         * where()->orWhereNone([...keys], value)
         */

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereNone(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or not ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('xxxx', 'xxxx')->orWhereNone(['foo', 'bar'], 2);
        $this->assertSame('select * from "users" where "xxxx" = ? or not ("foo" = ? or "bar" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'xxxx', 1 => 2, 2 => 2], $builder->getBindings());
    }

    public function testNestedWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', '=', 'foo')->orWhere(function ($q) {
            $q->where('name', '=', 'bar')->where('age', '=', 25);
        });
        $this->assertSame('select * from "users" where "email" = ? or ("name" = ? and "age" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'foo', 1 => 'bar', 2 => 25], $builder->getBindings());
    }

    public function testNestedWhereBindings()
    {
        $builder = $this->getBuilder();
        $builder->where('email', '=', 'foo')->where(function ($q) {
            $q->selectRaw('?', ['ignore'])->where('name', '=', 'bar');
        });
        $this->assertEquals([0 => 'foo', 1 => 'bar'], $builder->getBindings());
    }

    public function testWhereNot()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot(function ($q) {
            $q->where('email', '=', 'foo');
        });
        $this->assertSame('select * from "users" where not ("email" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('name', '=', 'bar')->whereNot(function ($q) {
            $q->where('email', '=', 'foo');
        });
        $this->assertSame('select * from "users" where "name" = ? and not ("email" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'bar', 1 => 'foo'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('name', '=', 'bar')->orWhereNot(function ($q) {
            $q->where('email', '=', 'foo');
        });
        $this->assertSame('select * from "users" where "name" = ? or not ("email" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'bar', 1 => 'foo'], $builder->getBindings());
    }

    public function testIncrementManyArgumentValidation1()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-numeric value passed as increment amount for column: \'col\'.');
        $builder = $this->getBuilder();
        $builder->from('users')->incrementEach(['col' => 'a']);
    }

    public function testIncrementManyArgumentValidation2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-associative array passed to incrementEach method.');
        $builder = $this->getBuilder();
        $builder->from('users')->incrementEach([11 => 11]);
    }

    public function testWhereNotWithArrayConditions()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', 2]]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" = ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNot([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "users" where not (("foo" = ? and "bar" < ?))', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testFullSubSelects()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', '=', 'foo')->orWhere('id', '=', function ($q) {
            $q->select(new Raw('max(id)'))->from('users')->where('email', '=', 'bar');
        });

        $this->assertSame('select * from "users" where "email" = ? or "id" = (select max(id) from "users" where "email" = ?)', $builder->toSql());
        $this->assertEquals([0 => 'foo', 1 => 'bar'], $builder->getBindings());
    }

    public function testWhereExists()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereExists(function ($q) {
            $q->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'));
        });
        $this->assertSame('select * from "orders" where exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereNotExists(function ($q) {
            $q->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'));
        });
        $this->assertSame('select * from "orders" where not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where('id', '=', 1)->orWhereExists(function ($q) {
            $q->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'));
        });
        $this->assertSame('select * from "orders" where "id" = ? or exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where('id', '=', 1)->orWhereNotExists(function ($q) {
            $q->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'));
        });
        $this->assertSame('select * from "orders" where "id" = ? or not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereExists(
            $this->getBuilder()->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'))
        );
        $this->assertSame('select * from "orders" where exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereNotExists(
            $this->getBuilder()->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'))
        );
        $this->assertSame('select * from "orders" where not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where('id', '=', 1)->orWhereExists(
            $this->getBuilder()->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'))
        );
        $this->assertSame('select * from "orders" where "id" = ? or exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where('id', '=', 1)->orWhereNotExists(
            $this->getBuilder()->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'))
        );
        $this->assertSame('select * from "orders" where "id" = ? or not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereExists(
            (new EloquentBuilder($this->getBuilder()))->select('*')->from('products')->where('products.id', '=', new Raw('"orders"."id"'))
        );
        $this->assertSame('select * from "orders" where exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());
    }

    public function testBasicJoins()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', 'users.id', 'contacts.id');
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->leftJoin('photos', 'users.id', '=', 'photos.id');
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" left join "photos" on "users"."id" = "photos"."id"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoinWhere('photos', 'users.id', '=', 'bar')->joinWhere('photos', 'users.id', '=', 'foo');
        $this->assertSame('select * from "users" left join "photos" on "users"."id" = ? inner join "photos" on "users"."id" = ?', $builder->toSql());
        $this->assertEquals(['bar', 'foo'], $builder->getBindings());
    }

    public function testCrossJoins()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('sizes')->crossJoin('colors');
        $this->assertSame('select * from "sizes" cross join "colors"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('tableB')->join('tableA', 'tableA.column1', '=', 'tableB.column2', 'cross');
        $this->assertSame('select * from "tableB" cross join "tableA" on "tableA"."column1" = "tableB"."column2"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('tableB')->crossJoin('tableA', 'tableA.column1', '=', 'tableB.column2');
        $this->assertSame('select * from "tableB" cross join "tableA" on "tableA"."column1" = "tableB"."column2"', $builder->toSql());
    }

    public function testCrossJoinSubs()
    {
        $builder = $this->getBuilder();
        $builder->selectRaw('(sale / overall.sales) * 100 AS percent_of_total')->from('sales')->crossJoinSub($this->getBuilder()->selectRaw('SUM(sale) AS sales')->from('sales'), 'overall');
        $this->assertSame('select (sale / overall.sales) * 100 AS percent_of_total from "sales" cross join (select SUM(sale) AS sales from "sales") as "overall"', $builder->toSql());
    }

    public function testComplexJoin()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->orOn('users.name', '=', 'contacts.name');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "users"."name" = "contacts"."name"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->where('users.id', '=', 'foo')->orWhere('users.name', '=', 'bar');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = ? or "users"."name" = ?', $builder->toSql());
        $this->assertEquals(['foo', 'bar'], $builder->getBindings());

        // Run the assertions again
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = ? or "users"."name" = ?', $builder->toSql());
        $this->assertEquals(['foo', 'bar'], $builder->getBindings());
    }

    public function testJoinWhereNull()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->whereNull('contacts.deleted_at');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" and "contacts"."deleted_at" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->orWhereNull('contacts.deleted_at');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "contacts"."deleted_at" is null', $builder->toSql());
    }

    public function testJoinWhereNotNull()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->whereNotNull('contacts.deleted_at');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" and "contacts"."deleted_at" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->orWhereNotNull('contacts.deleted_at');
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "contacts"."deleted_at" is not null', $builder->toSql());
    }

    public function testJoinWhereIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->whereIn('contacts.name', [48, 'baz', null]);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" and "contacts"."name" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([48, 'baz', null], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->orWhereIn('contacts.name', [48, 'baz', null]);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "contacts"."name" in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([48, 'baz', null], $builder->getBindings());
    }

    public function testJoinWhereInSubquery()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $q = $this->getBuilder();
            $q->select('name')->from('contacts')->where('name', 'baz');
            $j->on('users.id', '=', 'contacts.id')->whereIn('contacts.name', $q);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" and "contacts"."name" in (select "name" from "contacts" where "name" = ?)', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $q = $this->getBuilder();
            $q->select('name')->from('contacts')->where('name', 'baz');
            $j->on('users.id', '=', 'contacts.id')->orWhereIn('contacts.name', $q);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "contacts"."name" in (select "name" from "contacts" where "name" = ?)', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testJoinWhereNotIn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->whereNotIn('contacts.name', [48, 'baz', null]);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" and "contacts"."name" not in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([48, 'baz', null], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->orWhereNotIn('contacts.name', [48, 'baz', null]);
        });
        $this->assertSame('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "contacts"."name" not in (?, ?, ?)', $builder->toSql());
        $this->assertEquals([48, 'baz', null], $builder->getBindings());
    }

    public function testJoinsWithNestedConditions()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->where(function ($j) {
                $j->where('contacts.country', '=', 'US')->orWhere('contacts.is_partner', '=', 1);
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and ("contacts"."country" = ? or "contacts"."is_partner" = ?)', $builder->toSql());
        $this->assertEquals(['US', 1], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', '=', 'contacts.id')->where('contacts.is_active', '=', 1)->orOn(function ($j) {
                $j->orWhere(function ($j) {
                    $j->where('contacts.country', '=', 'UK')->orOn('contacts.type', '=', 'users.type');
                })->where(function ($j) {
                    $j->where('contacts.country', '=', 'US')->orWhereNull('contacts.is_partner');
                });
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and "contacts"."is_active" = ? or (("contacts"."country" = ? or "contacts"."type" = "users"."type") and ("contacts"."country" = ? or "contacts"."is_partner" is null))', $builder->toSql());
        $this->assertEquals([1, 'UK', 'US'], $builder->getBindings());
    }

    public function testJoinsWithAdvancedConditions()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')->where(function ($j) {
                $j->whereRole('admin')
                    ->orWhereNull('contacts.disabled')
                    ->orWhereRaw('year(contacts.created_at) = 2016');
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and ("role" = ? or "contacts"."disabled" is null or year(contacts.created_at) = 2016)', $builder->toSql());
        $this->assertEquals(['admin'], $builder->getBindings());
    }

    public function testJoinsWithSubqueryCondition()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')->whereIn('contact_type_id', function ($q) {
                $q->select('id')->from('contact_types')
                    ->where('category_id', '1')
                    ->whereNull('deleted_at');
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and "contact_type_id" in (select "id" from "contact_types" where "category_id" = ? and "deleted_at" is null)', $builder->toSql());
        $this->assertEquals(['1'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')->whereExists(function ($q) {
                $q->selectRaw('1')->from('contact_types')
                    ->whereRaw('contact_types.id = contacts.contact_type_id')
                    ->where('category_id', '1')
                    ->whereNull('deleted_at');
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and exists (select 1 from "contact_types" where contact_types.id = contacts.contact_type_id and "category_id" = ? and "deleted_at" is null)', $builder->toSql());
        $this->assertEquals(['1'], $builder->getBindings());
    }

    public function testJoinsWithAdvancedSubqueryCondition()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')->whereExists(function ($q) {
                $q->selectRaw('1')->from('contact_types')
                    ->whereRaw('contact_types.id = contacts.contact_type_id')
                    ->where('category_id', '1')
                    ->whereNull('deleted_at')
                    ->whereIn('level_id', function ($q) {
                        $q->select('id')->from('levels')
                            ->where('is_active', true);
                    });
            });
        });
        $this->assertSame('select * from "users" left join "contacts" on "users"."id" = "contacts"."id" and exists (select 1 from "contact_types" where contact_types.id = contacts.contact_type_id and "category_id" = ? and "deleted_at" is null and "level_id" in (select "id" from "levels" where "is_active" = ?))', $builder->toSql());
        $this->assertEquals(['1', true], $builder->getBindings());
    }

    public function testJoinsWithNestedJoins()
    {
        $builder = $this->getBuilder();
        $builder->select('users.id', 'contacts.id', 'contact_types.id')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')->join('contact_types', 'contacts.contact_type_id', '=', 'contact_types.id');
        });
        $this->assertSame('select "users"."id", "contacts"."id", "contact_types"."id" from "users" left join ("contacts" inner join "contact_types" on "contacts"."contact_type_id" = "contact_types"."id") on "users"."id" = "contacts"."id"', $builder->toSql());
    }

    public function testJoinsWithMultipleNestedJoins()
    {
        $builder = $this->getBuilder();
        $builder->select('users.id', 'contacts.id', 'contact_types.id', 'countries.id', 'planets.id')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')
                ->join('contact_types', 'contacts.contact_type_id', '=', 'contact_types.id')
                ->leftJoin('countries', function ($q) {
                    $q->on('contacts.country', '=', 'countries.country')
                        ->join('planets', function ($q) {
                            $q->on('countries.planet_id', '=', 'planet.id')
                                ->where('planet.is_settled', '=', 1)
                                ->where('planet.population', '>=', 10000);
                        });
                });
        });
        $this->assertSame('select "users"."id", "contacts"."id", "contact_types"."id", "countries"."id", "planets"."id" from "users" left join ("contacts" inner join "contact_types" on "contacts"."contact_type_id" = "contact_types"."id" left join ("countries" inner join "planets" on "countries"."planet_id" = "planet"."id" and "planet"."is_settled" = ? and "planet"."population" >= ?) on "contacts"."country" = "countries"."country") on "users"."id" = "contacts"."id"', $builder->toSql());
        $this->assertEquals(['1', 10000], $builder->getBindings());
    }

    public function testJoinsWithNestedJoinWithAdvancedSubqueryCondition()
    {
        $builder = $this->getBuilder();
        $builder->select('users.id', 'contacts.id', 'contact_types.id')->from('users')->leftJoin('contacts', function ($j) {
            $j->on('users.id', 'contacts.id')
                ->join('contact_types', 'contacts.contact_type_id', '=', 'contact_types.id')
                ->whereExists(function ($q) {
                    $q->select('*')->from('countries')
                        ->whereColumn('contacts.country', '=', 'countries.country')
                        ->join('planets', function ($q) {
                            $q->on('countries.planet_id', '=', 'planet.id')
                                ->where('planet.is_settled', '=', 1);
                        })
                        ->where('planet.population', '>=', 10000);
                });
        });
        $this->assertSame('select "users"."id", "contacts"."id", "contact_types"."id" from "users" left join ("contacts" inner join "contact_types" on "contacts"."contact_type_id" = "contact_types"."id") on "users"."id" = "contacts"."id" and exists (select * from "countries" inner join "planets" on "countries"."planet_id" = "planet"."id" and "planet"."is_settled" = ? where "contacts"."country" = "countries"."country" and "planet"."population" >= ?)', $builder->toSql());
        $this->assertEquals(['1', 10000], $builder->getBindings());
    }

    public function testJoinWithNestedOnCondition()
    {
        $builder = $this->getBuilder();
        $builder->select('users.id')->from('users')->join('contacts', function (JoinClause $j) {
            return $j
                ->on('users.id', 'contacts.id')
                ->addNestedWhereQuery($this->getBuilder()->where('contacts.id', 1));
        });
        $this->assertSame('select "users"."id" from "users" inner join "contacts" on "users"."id" = "contacts"."id" and ("contacts"."id" = ?)', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testJoinSub()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->joinSub('select * from "contacts"', 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "users" inner join (select * from "contacts") as "sub" on "users"."id" = "sub"."id"', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->from('users')->joinSub(function ($q) {
            $q->from('contacts');
        }, 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "users" inner join (select * from "contacts") as "sub" on "users"."id" = "sub"."id"', $builder->toSql());

        $builder = $this->getBuilder();
        $eloquentBuilder = new EloquentBuilder($this->getBuilder()->from('contacts'));
        $builder->from('users')->joinSub($eloquentBuilder, 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "users" inner join (select * from "contacts") as "sub" on "users"."id" = "sub"."id"', $builder->toSql());

        $builder = $this->getBuilder();
        $sub1 = $this->getBuilder()->from('contacts')->where('name', 'foo');
        $sub2 = $this->getBuilder()->from('contacts')->where('name', 'bar');
        $builder->from('users')
            ->joinSub($sub1, 'sub1', 'users.id', '=', 1, 'inner', true)
            ->joinSub($sub2, 'sub2', 'users.id', '=', 'sub2.user_id');
        $expected = 'select * from "users" ';
        $expected .= 'inner join (select * from "contacts" where "name" = ?) as "sub1" on "users"."id" = ? ';
        $expected .= 'inner join (select * from "contacts" where "name" = ?) as "sub2" on "users"."id" = "sub2"."user_id"';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals(['foo', 1, 'bar'], $builder->getRawBindings()['join']);

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->from('users')->joinSub(['foo'], 'sub', 'users.id', '=', 'sub.id');
    }

    public function testJoinSubWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->from('users')->joinSub('select * from "contacts"', 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "prefix_users" inner join (select * from "contacts") as "prefix_sub" on "prefix_users"."id" = "prefix_sub"."id"', $builder->toSql());
    }

    public function testLeftJoinSub()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->leftJoinSub($this->getBuilder()->from('contacts'), 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "users" left join (select * from "contacts") as "sub" on "users"."id" = "sub"."id"', $builder->toSql());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->from('users')->leftJoinSub(['foo'], 'sub', 'users.id', '=', 'sub.id');
    }

    public function testRightJoinSub()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->rightJoinSub($this->getBuilder()->from('contacts'), 'sub', 'users.id', '=', 'sub.id');
        $this->assertSame('select * from "users" right join (select * from "contacts") as "sub" on "users"."id" = "sub"."id"', $builder->toSql());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->from('users')->rightJoinSub(['foo'], 'sub', 'users.id', '=', 'sub.id');
    }

    public function testJoinLateral()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral('select * from `contacts` where `contracts`.`user_id` = `users`.`id`', 'sub');
        $this->assertSame('select * from `users` inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id`) as `sub` on true', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub');
        $this->assertSame('select * from `users` inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id`) as `sub` on true', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $sub = $this->getMySqlBuilder();
        $sub->getConnection()->shouldReceive('getDatabaseName');
        $eloquentBuilder = new EloquentBuilder($sub->from('contacts')->whereColumn('contracts.user_id', 'users.id'));
        $builder->from('users')->joinLateral($eloquentBuilder, 'sub');
        $this->assertSame('select * from `users` inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id`) as `sub` on true', $builder->toSql());

        $sub1 = $this->getMySqlBuilder();
        $sub1->getConnection()->shouldReceive('getDatabaseName');
        $sub1 = $sub1->from('contacts')->whereColumn('contracts.user_id', 'users.id')->where('name', 'foo');

        $sub2 = $this->getMySqlBuilder();
        $sub2->getConnection()->shouldReceive('getDatabaseName');
        $sub2 = $sub2->from('contacts')->whereColumn('contracts.user_id', 'users.id')->where('name', 'bar');

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral($sub1, 'sub1')->joinLateral($sub2, 'sub2');

        $expected = 'select * from `users` ';
        $expected .= 'inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id` and `name` = ?) as `sub1` on true ';
        $expected .= 'inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id` and `name` = ?) as `sub2` on true';

        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals(['foo', 'bar'], $builder->getRawBindings()['join']);

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getMySqlBuilder();
        $builder->from('users')->joinLateral(['foo'], 'sub');
    }

    public function testJoinLateralMariaDb()
    {
        $this->expectException(RuntimeException::class);
        $builder = $this->getMariaDbBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub')->toSql();
    }

    public function testJoinLateralSQLite()
    {
        $this->expectException(RuntimeException::class);
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub')->toSql();
    }

    public function testJoinLateralPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub');
        $this->assertSame('select * from "users" inner join lateral (select * from "contacts" where "contracts"."user_id" = "users"."id") as "sub" on true', $builder->toSql());
    }

    public function testJoinLateralSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->joinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub');
        $this->assertSame('select * from [users] cross apply (select * from [contacts] where [contracts].[user_id] = [users].[id]) as [sub]', $builder->toSql());
    }

    public function testJoinLateralWithPrefix()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->from('users')->joinLateral('select * from `contacts` where `contracts`.`user_id` = `users`.`id`', 'sub');
        $this->assertSame('select * from `prefix_users` inner join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id`) as `prefix_sub` on true', $builder->toSql());
    }

    public function testLeftJoinLateral()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');

        $sub = $this->getMySqlBuilder();
        $sub->getConnection()->shouldReceive('getDatabaseName');

        $builder->from('users')->leftJoinLateral($sub->from('contacts')->whereColumn('contracts.user_id', 'users.id'), 'sub');
        $this->assertSame('select * from `users` left join lateral (select * from `contacts` where `contracts`.`user_id` = `users`.`id`) as `sub` on true', $builder->toSql());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->from('users')->leftJoinLateral(['foo'], 'sub');
    }

    public function testLeftJoinLateralSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->from('users')->leftJoinLateral(function ($q) {
            $q->from('contacts')->whereColumn('contracts.user_id', 'users.id');
        }, 'sub');
        $this->assertSame('select * from [users] outer apply (select * from [contacts] where [contracts].[user_id] = [users].[id]) as [sub]', $builder->toSql());
    }

    public function testRawExpressionsInSelect()
    {
        $builder = $this->getBuilder();
        $builder->select(new Raw('substr(foo, 6)'))->from('users');
        $this->assertSame('select substr(foo, 6) from "users"', $builder->toSql());
    }

    public function testFindReturnsFirstResultByID()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', [1], true)->andReturn([['foo' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->find(1);
        $this->assertEquals(['foo' => 'bar'], $results);
    }

    public function testFindOrReturnsFirstResultByID()
    {
        $builder = $this->getMockQueryBuilder();
        $data = m::mock(stdClass::class);
        $builder->shouldReceive('first')->andReturn($data)->once();
        $builder->shouldReceive('first')->with(['column'])->andReturn($data)->once();
        $builder->shouldReceive('first')->andReturn(null)->once();

        $this->assertSame($data, $builder->findOr(1, fn () => 'callback result'));
        $this->assertSame($data, $builder->findOr(1, ['column'], fn () => 'callback result'));
        $this->assertSame('callback result', $builder->findOr(1, fn () => 'callback result'));
    }

    public function testFirstMethodReturnsFirstResult()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', [1], true)->andReturn([['foo' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->first();
        $this->assertEquals(['foo' => 'bar'], $results);
    }

    public function testFirstOrFailMethodReturnsFirstResult()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', [1], true)->andReturn([['foo' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->firstOrFail();
        $this->assertEquals(['foo' => 'bar'], $results);
    }

    public function testFirstOrFailMethodThrowsRecordNotFoundException()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', [1], true)->andReturn([]);

        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [])->andReturn([]);

        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('No record found for the given query.');

        $builder->from('users')->where('id', '=', 1)->firstOrFail();
    }

    public function testPluckMethodGetsCollectionOfColumnValues()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->andReturn([['foo' => 'bar'], ['foo' => 'baz']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar'], ['foo' => 'baz']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->pluck('foo');
        $this->assertEquals(['bar', 'baz'], $results->all());

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->andReturn([['id' => 1, 'foo' => 'bar'], ['id' => 10, 'foo' => 'baz']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['id' => 1, 'foo' => 'bar'], ['id' => 10, 'foo' => 'baz']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->pluck('foo', 'id');
        $this->assertEquals([1 => 'bar', 10 => 'baz'], $results->all());
    }

    public function testPluckAvoidsDuplicateColumnSelection()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select "foo" from "users" where "id" = ?', [1], true)->andReturn([['foo' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->pluck('foo', 'foo');
        $this->assertEquals(['bar' => 'bar'], $results->all());
    }

    public function testImplode()
    {
        // Test without glue.
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->andReturn([['foo' => 'bar'], ['foo' => 'baz']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar'], ['foo' => 'baz']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->implode('foo');
        $this->assertSame('barbaz', $results);

        // Test with glue.
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->andReturn([['foo' => 'bar'], ['foo' => 'baz']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar'], ['foo' => 'baz']])->andReturnUsing(function ($query, $results) {
            return $results;
        });
        $results = $builder->from('users')->where('id', '=', 1)->implode('foo', ',');
        $this->assertSame('bar,baz', $results);
    }

    public function testValueMethodReturnsSingleColumn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select "foo" from "users" where "id" = ? limit 1', [1], true)->andReturn([['foo' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['foo' => 'bar']])->andReturn([['foo' => 'bar']]);
        $results = $builder->from('users')->where('id', '=', 1)->value('foo');
        $this->assertSame('bar', $results);
    }

    public function testRawValueMethodReturnsSingleColumn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select UPPER("foo") from "users" where "id" = ? limit 1', [1], true)->andReturn([['UPPER("foo")' => 'BAR']]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, [['UPPER("foo")' => 'BAR']])->andReturn([['UPPER("foo")' => 'BAR']]);
        $results = $builder->from('users')->where('id', '=', 1)->rawValue('UPPER("foo")');
        $this->assertSame('BAR', $results);
    }

    public function testAggregateFunctions()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->count();
        $this->assertEquals(1, $results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select exists(select * from "users") as "exists"', [], true)->andReturn([['exists' => 1]]);
        $results = $builder->from('users')->exists();
        $this->assertTrue($results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select exists(select * from "users") as "exists"', [], true)->andReturn([['exists' => 0]]);
        $results = $builder->from('users')->doesntExist();
        $this->assertTrue($results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select max("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->max('id');
        $this->assertEquals(1, $results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select min("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->min('id');
        $this->assertEquals(1, $results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select sum("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->sum('id');
        $this->assertEquals(1, $results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select avg("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->avg('id');
        $this->assertEquals(1, $results);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select avg("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $results = $builder->from('users')->average('id');
        $this->assertEquals(1, $results);
    }

    public function testSqlServerExists()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select top 1 1 [exists] from [users]', [], true)->andReturn([['exists' => 1]]);
        $results = $builder->from('users')->exists();
        $this->assertTrue($results);
    }

    public function testExistsOr()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->andReturn([['exists' => 1]]);
        $results = $builder->from('users')->doesntExistOr(function () {
            return 123;
        });
        $this->assertSame(123, $results);
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->andReturn([['exists' => 0]]);
        $results = $builder->from('users')->doesntExistOr(function () {
            throw new RuntimeException;
        });
        $this->assertTrue($results);
    }

    public function testDoesntExistsOr()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->andReturn([['exists' => 0]]);
        $results = $builder->from('users')->existsOr(function () {
            return 123;
        });
        $this->assertSame(123, $results);
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->andReturn([['exists' => 1]]);
        $results = $builder->from('users')->existsOr(function () {
            throw new RuntimeException;
        });
        $this->assertTrue($results);
    }

    public function testAggregateResetFollowedByGet()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getConnection()->shouldReceive('select')->once()->with('select sum("id") as aggregate from "users"', [], true)->andReturn([['aggregate' => 2]]);
        $builder->getConnection()->shouldReceive('select')->once()->with('select "column1", "column2" from "users"', [], true)->andReturn([['column1' => 'foo', 'column2' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('users')->select('column1', 'column2');
        $count = $builder->count();
        $this->assertEquals(1, $count);
        $sum = $builder->sum('id');
        $this->assertEquals(2, $sum);
        $result = $builder->get();
        $this->assertEquals([['column1' => 'foo', 'column2' => 'bar']], $result->all());
    }

    public function testAggregateResetFollowedBySelectGet()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select count("column1") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getConnection()->shouldReceive('select')->once()->with('select "column2", "column3" from "users"', [], true)->andReturn([['column2' => 'foo', 'column3' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('users');
        $count = $builder->count('column1');
        $this->assertEquals(1, $count);
        $result = $builder->select('column2', 'column3')->get();
        $this->assertEquals([['column2' => 'foo', 'column3' => 'bar']], $result->all());
    }

    public function testAggregateResetFollowedByGetWithColumns()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select count("column1") as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getConnection()->shouldReceive('select')->once()->with('select "column2", "column3" from "users"', [], true)->andReturn([['column2' => 'foo', 'column3' => 'bar']]);
        $builder->getProcessor()->shouldReceive('processSelect')->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('users');
        $count = $builder->count('column1');
        $this->assertEquals(1, $count);
        $result = $builder->get(['column2', 'column3']);
        $this->assertEquals([['column2' => 'foo', 'column3' => 'bar']], $result->all());
    }

    public function testAggregateWithSubSelect()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from "users"', [], true)->andReturn([['aggregate' => 1]]);
        $builder->getProcessor()->shouldReceive('processSelect')->once()->andReturnUsing(function ($builder, $results) {
            return $results;
        });
        $builder->from('users')->selectSub(function ($query) {
            $query->from('posts')->select('foo', 'bar')->where('title', 'foo');
        }, 'post');
        $count = $builder->count();
        $this->assertEquals(1, $count);
        $this->assertSame('(select "foo", "bar" from "posts" where "title" = ?) as "post"', $builder->getGrammar()->getValue($builder->columns[0]));
        $this->assertEquals(['foo'], $builder->getBindings());
    }

    public function testSubqueriesBindings()
    {
        $builder = $this->getBuilder();
        $second = $this->getBuilder()->select('*')->from('users')->orderByRaw('id = ?', 2);
        $third = $this->getBuilder()->select('*')->from('users')->where('id', 3)->groupBy('id')->having('id', '!=', 4);
        $builder->groupBy('a')->having('a', '=', 1)->union($second)->union($third);
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3, 3 => 4], $builder->getBindings());

        $builder = $this->getBuilder()->select('*')->from('users')->where('email', '=', function ($q) {
            $q->select(new Raw('max(id)'))
                ->from('users')->where('email', '=', 'bar')
                ->orderByRaw('email like ?', '%.com')
                ->groupBy('id')->having('id', '=', 4);
        })->orWhere('id', '=', 'foo')->groupBy('id')->having('id', '=', 5);
        $this->assertEquals([0 => 'bar', 1 => 4, 2 => '%.com', 3 => 'foo', 4 => 5], $builder->getBindings());
    }

    public function testInsertMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('insert')->once()->with('insert into "users" ("email") values (?)', ['foo'])->andReturn(true);
        $result = $builder->from('users')->insert(['email' => 'foo']);
        $this->assertTrue($result);
    }

    public function testInsertUsingMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "table1" ("foo") select "bar" from "table2" where "foreign_id" = ?', [5])->andReturn(1);

        $result = $builder->from('table1')->insertUsing(
            ['foo'],
            function (Builder $query) {
                $query->select(['bar'])->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testInsertUsingWithEmptyColumns()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "table1" select * from "table2" where "foreign_id" = ?', [5])->andReturn(1);

        $result = $builder->from('table1')->insertUsing(
            [],
            function (Builder $query) {
                $query->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testInsertUsingInvalidSubquery()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->from('table1')->insertUsing(['foo'], ['bar']);
    }

    public function testInsertOrIgnoreMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not support');
        $builder = $this->getBuilder();
        $builder->from('users')->insertOrIgnore(['email' => 'foo']);
    }

    public function testMySqlInsertOrIgnoreMethod()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert ignore into `users` (`email`) values (?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testPostgresInsertOrIgnoreMethod()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email") values (?) on conflict do nothing', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testSQLiteInsertOrIgnoreMethod()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert or ignore into "users" ("email") values (?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testSqlServerInsertOrIgnoreMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not support');
        $builder = $this->getSqlServerBuilder();
        $builder->from('users')->insertOrIgnore(['email' => 'foo']);
    }

    public function testInsertOrIgnoreUsingMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not support');
        $builder = $this->getBuilder();
        $builder->from('users')->insertOrIgnoreUsing(['email' => 'foo'], 'bar');
    }

    public function testSqlServerInsertOrIgnoreUsingMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not support');
        $builder = $this->getSqlServerBuilder();
        $builder->from('users')->insertOrIgnoreUsing(['email' => 'foo'], 'bar');
    }

    public function testMySqlInsertOrIgnoreUsingMethod()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert ignore into `table1` (`foo`) select `bar` from `table2` where `foreign_id` = ?', [0 => 5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            ['foo'],
            function (Builder $query) {
                $query->select(['bar'])->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testMySqlInsertOrIgnoreUsingWithEmptyColumns()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert ignore into `table1` select * from `table2` where `foreign_id` = ?', [0 => 5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            [],
            function (Builder $query) {
                $query->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testMySqlInsertOrIgnoreUsingInvalidSubquery()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getMySqlBuilder();
        $builder->from('table1')->insertOrIgnoreUsing(['foo'], ['bar']);
    }

    public function testPostgresInsertOrIgnoreUsingMethod()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "table1" ("foo") select "bar" from "table2" where "foreign_id" = ? on conflict do nothing', [5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            ['foo'],
            function (Builder $query) {
                $query->select(['bar'])->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testPostgresInsertOrIgnoreUsingWithEmptyColumns()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "table1" select * from "table2" where "foreign_id" = ? on conflict do nothing', [5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            [],
            function (Builder $query) {
                $query->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testPostgresInsertOrIgnoreUsingInvalidSubquery()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getPostgresBuilder();
        $builder->from('table1')->insertOrIgnoreUsing(['foo'], ['bar']);
    }

    public function testSQLiteInsertOrIgnoreUsingMethod()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert or ignore into "table1" ("foo") select "bar" from "table2" where "foreign_id" = ?', [5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            ['foo'],
            function (Builder $query) {
                $query->select(['bar'])->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testSQLiteInsertOrIgnoreUsingWithEmptyColumns()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('getDatabaseName');
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert or ignore into "table1" select * from "table2" where "foreign_id" = ?', [5])->andReturn(1);

        $result = $builder->from('table1')->insertOrIgnoreUsing(
            [],
            function (Builder $query) {
                $query->from('table2')->where('foreign_id', '=', 5);
            }
        );

        $this->assertEquals(1, $result);
    }

    public function testSQLiteInsertOrIgnoreUsingInvalidSubquery()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getSQLiteBuilder();
        $builder->from('table1')->insertOrIgnoreUsing(['foo'], ['bar']);
    }

    public function testInsertGetIdMethod()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?)', ['foo'], 'id')->andReturn(1);
        $result = $builder->from('users')->insertGetId(['email' => 'foo'], 'id');
        $this->assertEquals(1, $result);
    }

    public function testInsertGetIdMethodRemovesExpressions()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email", "bar") values (?, bar)', ['foo'], 'id')->andReturn(1);
        $result = $builder->from('users')->insertGetId(['email' => 'foo', 'bar' => new Raw('bar')], 'id');
        $this->assertEquals(1, $result);
    }

    public function testInsertGetIdWithEmptyValues()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into `users` () values ()', [], null);
        $builder->from('users')->insertGetId([]);

        $builder = $this->getPostgresBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" default values returning "id"', [], null);
        $builder->from('users')->insertGetId([]);

        $builder = $this->getSQLiteBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" default values', [], null);
        $builder->from('users')->insertGetId([]);

        $builder = $this->getSqlServerBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into [users] default values', [], null);
        $builder->from('users')->insertGetId([]);
    }

    public function testInsertMethodRespectsRawBindings()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('insert')->once()->with('insert into "users" ("email") values (CURRENT TIMESTAMP)', [])->andReturn(true);
        $result = $builder->from('users')->insert(['email' => new Raw('CURRENT TIMESTAMP')]);
        $this->assertTrue($result);
    }

    public function testMultipleInsertsWithExpressionValues()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('insert')->once()->with('insert into "users" ("email") values (UPPER(\'Foo\')), (LOWER(\'Foo\'))', [])->andReturn(true);
        $result = $builder->from('users')->insert([['email' => new Raw("UPPER('Foo')")], ['email' => new Raw("LOWER('Foo')")]]);
        $this->assertTrue($result);
    }

    public function testUpdateMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `email` = ?, `name` = ? where `id` = ? order by `foo` desc limit 5', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->orderBy('foo', 'desc')->limit(5)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpsertMethod()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `email` = values(`email`), `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) as laravel_upsert_alias on duplicate key update `email` = `laravel_upsert_alias`.`email`, `name` = `laravel_upsert_alias`.`name`', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "email" = "excluded"."email", "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "email" = "excluded"."email", "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [email] = [laravel_source].[email], [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name]);', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);
    }

    public function testUpsertMethodWithUpdateColumns()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) as laravel_upsert_alias on duplicate key update `name` = `laravel_upsert_alias`.`name`', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name]);', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);
    }

    public function testUpdateMethodWithJoins()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" inner join "orders" on "users"."id" = "orders"."user_id" set "email" = ?, "name" = ? where "users"."id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ? set "email" = ?, "name" = ?', [1, 'foo', 'bar'])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsOnSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update [users] set [email] = ?, [name] = ? from [users] inner join [orders] on [users].[id] = [orders].[user_id] where [users].[id] = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update [users] set [email] = ?, [name] = ? from [users] inner join [orders] on [users].[id] = [orders].[user_id] and [users].[id] = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsOnMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` inner join `orders` on `users`.`id` = `orders`.`user_id` set `email` = ?, `name` = ? where `users`.`id` = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` inner join `orders` on `users`.`id` = `orders`.`user_id` and `users`.`id` = ? set `email` = ?, `name` = ?', [1, 'foo', 'bar'])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsOnSQLite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" where "users"."id" > ? order by "id" asc limit 3)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('users.id', '>', 1)->limit(3)->oldest('id')->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" where "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" as "u" set "email" = ?, "name" = ? where "rowid" in (select "u"."rowid" from "users" as "u" inner join "orders" as "o" on "u"."id" = "o"."user_id")', ['foo', 'bar'])->andReturn(1);
        $result = $builder->from('users as u')->join('orders as o', 'u.id', '=', 'o.user_id')->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsAndAliasesOnSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update [u] set [email] = ?, [name] = ? from [users] as [u] inner join [orders] on [u].[id] = [orders].[user_id] where [u].[id] = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users as u')->join('orders', 'u.id', '=', 'orders.user_id')->where('u.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithoutJoinsOnPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->selectRaw('?', ['ignore'])->update(['users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users"."users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users.users')->where('id', '=', 1)->selectRaw('?', ['ignore'])->update(['users.users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsOnPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" where "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ? where "name" = ?)', ['foo', 'bar', 1, 'baz'])->andReturn(1);
        $result = $builder->from('users')
            ->join('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('users.id', '=', 1);
            })->where('name', 'baz')
            ->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateFromMethodWithJoinsOnPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "users"."id" = ? and "users"."id" = "orders"."user_id"', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "users"."id" = "orders"."user_id" and "users"."id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "name" = ? and "users"."id" = "orders"."user_id" and "users"."id" = ?', ['foo', 'bar', 'baz', 1])->andReturn(1);
        $result = $builder->from('users')
            ->join('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('users.id', '=', 1);
            })->where('name', 'baz')
            ->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodRespectsRaw()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = foo, "name" = ? where "id" = ?', ['bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['email' => new Raw('foo'), 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWorksWithQueryAsValue()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "credits" = (select sum(credits) from "transactions" where "transactions"."user_id" = "users"."id" and "type" = ?) where "id" = ?', ['foo', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['credits' => $this->getBuilder()->from('transactions')->selectRaw('sum(credits)')->whereColumn('transactions.user_id', 'users.id')->where('type', 'foo')]);

        $this->assertEquals(1, $result);
    }

    public function testUpdateOrInsertMethod()
    {
        $builder = m::mock(Builder::class.'[where,exists,insert]', [
            m::mock(ConnectionInterface::class),
            new Grammar,
            m::mock(Processor::class),
        ]);

        $builder->shouldReceive('where')->once()->with(['email' => 'foo'])->andReturn(m::self());
        $builder->shouldReceive('exists')->once()->andReturn(false);
        $builder->shouldReceive('insert')->once()->with(['email' => 'foo', 'name' => 'bar'])->andReturn(true);

        $this->assertTrue($builder->updateOrInsert(['email' => 'foo'], ['name' => 'bar']));

        $builder = m::mock(Builder::class.'[where,exists,update]', [
            m::mock(ConnectionInterface::class),
            new Grammar,
            m::mock(Processor::class),
        ]);

        $builder->shouldReceive('where')->once()->with(['email' => 'foo'])->andReturn(m::self());
        $builder->shouldReceive('exists')->once()->andReturn(true);
        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('update')->once()->with(['name' => 'bar'])->andReturn(1);

        $this->assertTrue($builder->updateOrInsert(['email' => 'foo'], ['name' => 'bar']));
    }

    public function testUpdateOrInsertMethodWorksWithEmptyUpdateValues()
    {
        $builder = m::spy(Builder::class.'[where,exists,update]', [
            m::mock(ConnectionInterface::class),
            new Grammar,
            m::mock(Processor::class),
        ]);

        $builder->shouldReceive('where')->once()->with(['email' => 'foo'])->andReturn(m::self());
        $builder->shouldReceive('exists')->once()->andReturn(true);

        $this->assertTrue($builder->updateOrInsert(['email' => 'foo']));
        $builder->shouldNotHaveReceived('update');
    }

    public function testDeleteMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "email" = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "users"."id" = ?', [1])->andReturn(1);
        $result = $builder->from('users')->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "users"."id" = ?', [1])->andReturn(1);
        $result = $builder->from('users')->selectRaw('?', ['ignore'])->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getSqliteBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "rowid" in (select "users"."rowid" from "users" where "email" = ? order by "id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from `users` where `email` = ? order by `id` asc limit 1', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from [users] where [email] = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete top (1) from [users] where [email] = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();
        $this->assertEquals(1, $result);
    }

    public function testDeleteWithJoinMethod()
    {
        $builder = $this->getSqliteBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "rowid" in (select "users"."rowid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."email" = ? order by "users"."id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('users.email', '=', 'foo')->orderBy('users.id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getSqliteBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" as "u" where "rowid" in (select "u"."rowid" from "users" as "u" inner join "contacts" as "c" on "u"."id" = "c"."id")', [])->andReturn(1);
        $result = $builder->from('users as u')->join('contacts as c', 'u.id', '=', 'c.id')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `users` from `users` inner join `contacts` on `users`.`id` = `contacts`.`id` where `email` = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `a` from `users` as `a` inner join `users` as `b` on `a`.`id` = `b`.`user_id` where `email` = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users AS a')->join('users AS b', 'a.id', '=', 'b.user_id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `users` from `users` inner join `contacts` on `users`.`id` = `contacts`.`id` where `users`.`id` = ?', [1])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->orderBy('id')->take(1)->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete [users] from [users] inner join [contacts] on [users].[id] = [contacts].[id] where [email] = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete [a] from [users] as [a] inner join [users] as [b] on [a].[id] = [b].[user_id] where [email] = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users AS a')->join('users AS b', 'a.id', '=', 'b.user_id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete [users] from [users] inner join [contacts] on [users].[id] = [contacts].[id] where [users].[id] = ?', [1])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."email" = ?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('users.email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" as "a" where "ctid" in (select "a"."ctid" from "users" as "a" inner join "users" as "b" on "a"."id" = "b"."user_id" where "email" = ? order by "id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users AS a')->join('users AS b', 'a.id', '=', 'b.user_id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."id" = ? order by "id" asc limit 1)', [1])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->orderBy('id')->take(1)->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."user_id" and "users"."id" = ? where "name" = ?)', [1, 'baz'])->andReturn(1);
        $result = $builder->from('users')
            ->join('contacts', function ($join) {
                $join->on('users.id', '=', 'contacts.user_id')
                    ->where('users.id', '=', 1);
            })->where('name', 'baz')
            ->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id")', [])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->delete();
        $this->assertEquals(1, $result);
    }

    public function testTruncateMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('statement')->once()->with('truncate table "users"', []);
        $builder->from('users')->truncate();

        $sqlite = new SQLiteGrammar;
        $builder = $this->getBuilder();
        $builder->from('users');
        $this->assertEquals([
            'delete from sqlite_sequence where name = ?' => ['users'],
            'delete from "users"' => [],
        ], $sqlite->compileTruncate($builder));
    }

    public function testTruncateMethodWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->getConnection()->shouldReceive('statement')->once()->with('truncate table "prefix_users"', []);
        $builder->from('users')->truncate();

        $sqlite = new SQLiteGrammar;
        $sqlite->setTablePrefix('prefix_');
        $builder = $this->getBuilder();
        $builder->from('users');
        $this->assertEquals([
            'delete from sqlite_sequence where name = ?' => ['prefix_users'],
            'delete from "prefix_users"' => [],
        ], $sqlite->compileTruncate($builder));
    }

    public function testPreserveAddsClosureToArray()
    {
        $builder = $this->getBuilder();
        $builder->beforeQuery(function () {
        });
        $this->assertCount(1, $builder->beforeQueryCallbacks);
        $this->assertInstanceOf(Closure::class, $builder->beforeQueryCallbacks[0]);
    }

    public function testApplyPreserveCleansArray()
    {
        $builder = $this->getBuilder();
        $builder->beforeQuery(function () {
        });
        $this->assertCount(1, $builder->beforeQueryCallbacks);
        $builder->applyBeforeQueryCallbacks();
        $this->assertCount(0, $builder->beforeQueryCallbacks);
    }

    public function testPreservedAreAppliedByToSql()
    {
        $builder = $this->getBuilder();
        $builder->beforeQuery(function ($builder) {
            $builder->where('foo', 'bar');
        });
        $this->assertSame('select * where "foo" = ?', $builder->toSql());
        $this->assertEquals(['bar'], $builder->getBindings());
    }

    public function testPreservedAreAppliedByInsert()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('insert')->once()->with('insert into "users" ("email") values (?)', ['foo']);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->insert(['email' => 'foo']);
    }

    public function testPreservedAreAppliedByInsertGetId()
    {
        $this->called = false;
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?)', ['foo'], 'id');
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->insertGetId(['email' => 'foo'], 'id');
    }

    public function testPreservedAreAppliedByInsertUsing()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email") select *', []);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->insertUsing(['email'], $this->getBuilder());
    }

    public function testPreservedAreAppliedByUpsert()
    {
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`) values (?) on duplicate key update `email` = values(`email`)', ['foo']);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->upsert(['email' => 'foo'], 'id');

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`) values (?) as laravel_upsert_alias on duplicate key update `email` = `laravel_upsert_alias`.`email`', ['foo']);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->upsert(['email' => 'foo'], 'id');
    }

    public function testPreservedAreAppliedByUpdate()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ? where "id" = ?', ['foo', 1]);
        $builder->from('users')->beforeQuery(function ($builder) {
            $builder->where('id', 1);
        });
        $builder->update(['email' => 'foo']);
    }

    public function testPreservedAreAppliedByDelete()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users"', []);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->delete();
    }

    public function testPreservedAreAppliedByTruncate()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('statement')->once()->with('truncate table "users"', []);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->truncate();
    }

    public function testPreservedAreAppliedByExists()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select exists(select * from "users") as "exists"', [], true);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->exists();
    }

    public function testPostgresInsertGetId()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?) returning "id"', ['foo'], 'id')->andReturn(1);
        $result = $builder->from('users')->insertGetId(['email' => 'foo'], 'id');
        $this->assertEquals(1, $result);
    }

    public function testMySqlWrapping()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users');
        $this->assertSame('select * from `users`', $builder->toSql());
    }

    public function testMySqlUpdateWrappingJson()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                'update `users` set `name` = json_set(`name`, \'$."first_name"\', ?), `name` = json_set(`name`, \'$."last_name"\', ?) where `active` = ?',
                ['John', 'Doe', 1]
            );

        $builder = new Builder($connection, $grammar, $processor);

        $builder->from('users')->where('active', '=', 1)->update(['name->first_name' => 'John', 'name->last_name' => 'Doe']);
    }

    public function testMySqlUpdateWrappingNestedJson()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                'update `users` set `meta` = json_set(`meta`, \'$."name"."first_name"\', ?), `meta` = json_set(`meta`, \'$."name"."last_name"\', ?) where `active` = ?',
                ['John', 'Doe', 1]
            );

        $builder = new Builder($connection, $grammar, $processor);

        $builder->from('users')->where('active', '=', 1)->update(['meta->name->first_name' => 'John', 'meta->name->last_name' => 'Doe']);
    }

    public function testMySqlUpdateWrappingJsonArray()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                'update `users` set `options` = ?, `meta` = json_set(`meta`, \'$."tags"\', cast(? as json)), `group_id` = 45, `created_at` = ? where `active` = ?',
                [
                    json_encode(['2fa' => false, 'presets' => ['laravel', 'vue']]),
                    json_encode(['white', 'large']),
                    new DateTime('2019-08-06'),
                    1,
                ]
            );

        $builder = new Builder($connection, $grammar, $processor);
        $builder->from('users')->where('active', 1)->update([
            'options' => ['2fa' => false, 'presets' => ['laravel', 'vue']],
            'meta->tags' => ['white', 'large'],
            'group_id' => new Raw('45'),
            'created_at' => new DateTime('2019-08-06'),
        ]);
    }

    public function testMySqlUpdateWrappingJsonPathArrayIndex()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('update')
            ->with(
                'update `users` set `options` = json_set(`options`, \'$[1]."2fa"\', false), `meta` = json_set(`meta`, \'$."tags"[0][2]\', ?) where `active` = ?',
                [
                    'large',
                    1,
                ]
            );

        $builder = new Builder($connection, $grammar, $processor);
        $builder->from('users')->where('active', 1)->update([
            'options->[1]->2fa' => false,
            'meta->tags[0][2]' => 'large',
        ]);
    }

    public function testMySqlUpdateWithJsonPreparesBindingsCorrectly()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('update')
            ->once()
            ->with(
                'update `users` set `options` = json_set(`options`, \'$."enable"\', false), `updated_at` = ? where `id` = ?',
                ['2015-05-26 22:02:06', 0]
            );
        $builder = new Builder($connection, $grammar, $processor);
        $builder->from('users')->where('id', '=', 0)->update(['options->enable' => false, 'updated_at' => '2015-05-26 22:02:06']);

        $connection->shouldReceive('update')
            ->once()
            ->with(
                'update `users` set `options` = json_set(`options`, \'$."size"\', ?), `updated_at` = ? where `id` = ?',
                [45, '2015-05-26 22:02:06', 0]
            );
        $builder = new Builder($connection, $grammar, $processor);
        $builder->from('users')->where('id', '=', 0)->update(['options->size' => 45, 'updated_at' => '2015-05-26 22:02:06']);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `options` = json_set(`options`, \'$."size"\', ?)', [null]);
        $builder->from('users')->update(['options->size' => null]);

        $builder = $this->getMySqlBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `options` = json_set(`options`, \'$."size"\', 45)', []);
        $builder->from('users')->update(['options->size' => new Raw('45')]);
    }

    public function testPostgresUpdateWrappingJson()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = jsonb_set("options"::jsonb, \'{"name","first_name"}\', ?)', ['"John"']);
        $builder->from('users')->update(['users.options->name->first_name' => 'John']);

        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = jsonb_set("options"::jsonb, \'{"language"}\', \'null\')', []);
        $builder->from('users')->update(['options->language' => new Raw("'null'")]);
    }

    public function testPostgresUpdateWrappingJsonArray()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = ?, "meta" = jsonb_set("meta"::jsonb, \'{"tags"}\', ?), "group_id" = 45, "created_at" = ?', [
                json_encode(['2fa' => false, 'presets' => ['laravel', 'vue']]),
                json_encode(['white', 'large']),
                new DateTime('2019-08-06'),
            ]);

        $builder->from('users')->update([
            'options' => ['2fa' => false, 'presets' => ['laravel', 'vue']],
            'meta->tags' => ['white', 'large'],
            'group_id' => new Raw('45'),
            'created_at' => new DateTime('2019-08-06'),
        ]);
    }

    public function testPostgresUpdateWrappingJsonPathArrayIndex()
    {
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = jsonb_set("options"::jsonb, \'{1,"2fa"}\', ?), "meta" = jsonb_set("meta"::jsonb, \'{"tags",0,2}\', ?) where ("options"->1->\'2fa\')::jsonb = \'true\'::jsonb', [
                'false',
                '"large"',
            ]);

        $builder->from('users')->where('options->[1]->2fa', true)->update([
            'options->[1]->2fa' => false,
            'meta->tags[0][2]' => 'large',
        ]);
    }

    public function testSQLiteUpdateWrappingJsonArray()
    {
        $builder = $this->getSQLiteBuilder();

        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = ?, "group_id" = 45, "created_at" = ?', [
                json_encode(['2fa' => false, 'presets' => ['laravel', 'vue']]),
                new DateTime('2019-08-06'),
            ]);

        $builder->from('users')->update([
            'options' => ['2fa' => false, 'presets' => ['laravel', 'vue']],
            'group_id' => new Raw('45'),
            'created_at' => new DateTime('2019-08-06'),
        ]);
    }

    public function testSQLiteUpdateWrappingNestedJsonArray()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "group_id" = 45, "created_at" = ?, "options" = json_patch(ifnull("options", json(\'{}\')), json(?))', [
                new DateTime('2019-08-06'),
                json_encode(['name' => 'Taylor', 'security' => ['2fa' => false, 'presets' => ['laravel', 'vue']], 'sharing' => ['twitter' => 'username']]),
            ]);

        $builder->from('users')->update([
            'options->name' => 'Taylor',
            'group_id' => new Raw('45'),
            'options->security' => ['2fa' => false, 'presets' => ['laravel', 'vue']],
            'options->sharing->twitter' => 'username',
            'created_at' => new DateTime('2019-08-06'),
        ]);
    }

    public function testSQLiteUpdateWrappingJsonPathArrayIndex()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = json_patch(ifnull("options", json(\'{}\')), json(?)), "meta" = json_patch(ifnull("meta", json(\'{}\')), json(?)) where json_extract("options", \'$[1]."2fa"\') = true', [
                '{"[1]":{"2fa":false}}',
                '{"tags[0][2]":"large"}',
            ]);

        $builder->from('users')->where('options->[1]->2fa', true)->update([
            'options->[1]->2fa' => false,
            'meta->tags[0][2]' => 'large',
        ]);
    }

    public function testMySqlWrappingJsonWithString()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->sku', '=', 'foo-bar');
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."sku"\')) = ?', $builder->toSql());
        $this->assertCount(1, $builder->getRawBindings()['where']);
        $this->assertSame('foo-bar', $builder->getRawBindings()['where'][0]);
    }

    public function testMySqlWrappingJsonWithInteger()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->price', '=', 1);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"\')) = ?', $builder->toSql());
    }

    public function testMySqlWrappingJsonWithDouble()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->price', '=', 1.5);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"\')) = ?', $builder->toSql());
    }

    public function testMySqlWrappingJsonWithBoolean()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from `users` where json_extract(`items`, \'$."available"\') = true', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where(new Raw("items->'$.available'"), '=', true);
        $this->assertSame("select * from `users` where items->'$.available' = true", $builder->toSql());
    }

    public function testMySqlWrappingJsonWithBooleanAndIntegerThatLooksLikeOne()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true)->where('items->active', '=', false)->where('items->number_available', '=', 0);
        $this->assertSame('select * from `users` where json_extract(`items`, \'$."available"\') = true and json_extract(`items`, \'$."active"\') = false and json_unquote(json_extract(`items`, \'$."number_available"\')) = ?', $builder->toSql());
    }

    public function testJsonPathEscaping()
    {
        $expectedWithJsonEscaped = <<<'SQL'
select json_unquote(json_extract(`json`, '$."''))#"'))
SQL;

        $builder = $this->getMySqlBuilder();
        $builder->select("json->'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select("json->\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select("json->\\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select("json->\\\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());
    }

    public function testMySqlWrappingJson()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereRaw('items->\'$."price"\' = 1');
        $this->assertSame('select * from `users` where items->\'$."price"\' = 1', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select json_unquote(json_extract(`items`, \'$."price"\')) from `users` where json_unquote(json_extract(`users`.`items`, \'$."price"\')) = ? order by json_unquote(json_extract(`items`, \'$."price"\')) asc', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"."in_usd"\')) = ?', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"."in_usd"\')) = ? and json_unquote(json_extract(`items`, \'$."age"\')) = ?', $builder->toSql());
    }

    public function testPostgresWrappingJson()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select "items"->>\'price\' from "users" where "users"."items"->>\'price\' = ? order by "items"->>\'price\' asc', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from "users" where "items"->\'price\'->>\'in_usd\' = ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where "items"->\'price\'->>\'in_usd\' = ? and "items"->>\'age\' = ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('items->prices->0', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where "items"->\'prices\'->>0 = ? and "items"->>\'age\' = ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from "users" where ("items"->\'available\')::jsonb = \'true\'::jsonb', $builder->toSql());
    }

    public function testSqlServerWrappingJson()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select json_value([items], \'$."price"\') from [users] where json_value([users].[items], \'$."price"\') = ? order by json_value([items], \'$."price"\') asc', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from [users] where json_value([items], \'$."price"."in_usd"\') = ?', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from [users] where json_value([items], \'$."price"."in_usd"\') = ? and json_value([items], \'$."age"\') = ?', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from [users] where json_value([items], \'$."available"\') = \'true\'', $builder->toSql());
    }

    public function testSqliteWrappingJson()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select json_extract("items", \'$."price"\') from "users" where json_extract("users"."items", \'$."price"\') = ? order by json_extract("items", \'$."price"\') asc', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from "users" where json_extract("items", \'$."price"."in_usd"\') = ?', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where json_extract("items", \'$."price"."in_usd"\') = ? and json_extract("items", \'$."age"\') = ?', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from "users" where json_extract("items", \'$."available"\') = true', $builder->toSql());
    }

    public function testSQLiteOrderBy()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->orderBy('email', 'desc');
        $this->assertSame('select * from "users" order by "email" desc', $builder->toSql());
    }

    public function testSqlServerLimitsAndOffsets()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->take(10);
        $this->assertSame('select top 10 * from [users]', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(10)->orderBy('email', 'desc');
        $this->assertSame('select * from [users] order by [email] desc offset 10 rows', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(10)->take(10);
        $this->assertSame('select * from [users] order by (SELECT 0) offset 10 rows fetch next 10 rows only', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(11)->take(10)->orderBy('email', 'desc');
        $this->assertSame('select * from [users] order by [email] desc offset 11 rows fetch next 10 rows only', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $subQuery = function ($query) {
            return $query->select('created_at')->from('logins')->where('users.name', 'nameBinding')->whereColumn('user_id', 'users.id')->limit(1);
        };
        $builder->select('*')->from('users')->where('email', 'emailBinding')->orderBy($subQuery)->skip(10)->take(10);
        $this->assertSame('select * from [users] where [email] = ? order by (select top 1 [created_at] from [logins] where [users].[name] = ? and [user_id] = [users].[id]) asc offset 10 rows fetch next 10 rows only', $builder->toSql());
        $this->assertEquals(['emailBinding', 'nameBinding'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->take('foo');
        $this->assertSame('select * from [users]', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->take('foo')->offset('bar');
        $this->assertSame('select * from [users]', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->offset('bar');
        $this->assertSame('select * from [users]', $builder->toSql());
    }

    public function testMySqlSoundsLikeOperator()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('name', 'sounds like', 'John Doe');
        $this->assertSame('select * from `users` where `name` sounds like ?', $builder->toSql());
        $this->assertEquals(['John Doe'], $builder->getBindings());
    }

    public function testBitwiseOperators()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('bar', '&', 1);
        $this->assertSame('select * from "users" where "bar" & ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('bar', '#', 1);
        $this->assertSame('select * from "users" where ("bar" # ?)::bool', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('range', '>>', '[2022-01-08 00:00:00,2022-01-09 00:00:00)');
        $this->assertSame('select * from "users" where ("range" >> ?)::bool', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('bar', '&', 1);
        $this->assertSame('select * from [users] where ([bar] & ?) != 0', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('bar', '&', 1);
        $this->assertSame('select * from "users" having "bar" & ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->having('bar', '#', 1);
        $this->assertSame('select * from "users" having ("bar" # ?)::bool', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->having('range', '>>', '[2022-01-08 00:00:00,2022-01-09 00:00:00)');
        $this->assertSame('select * from "users" having ("range" >> ?)::bool', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->having('bar', '&', 1);
        $this->assertSame('select * from [users] having ([bar] & ?) != 0', $builder->toSql());
    }

    public function testMergeWheresCanMergeWheresAndBindings()
    {
        $builder = $this->getBuilder();
        $builder->wheres = ['foo'];
        $builder->mergeWheres(['wheres'], [12 => 'foo', 13 => 'bar']);
        $this->assertEquals(['foo', 'wheres'], $builder->wheres);
        $this->assertEquals(['foo', 'bar'], $builder->getBindings());
    }

    public function testPrepareValueAndOperator()
    {
        $builder = $this->getBuilder();
        [$value, $operator] = $builder->prepareValueAndOperator('>', '20');
        $this->assertSame('>', $value);
        $this->assertSame('20', $operator);

        $builder = $this->getBuilder();
        [$value, $operator] = $builder->prepareValueAndOperator('>', '20', true);
        $this->assertSame('20', $value);
        $this->assertSame('=', $operator);
    }

    public function testPrepareValueAndOperatorExpectException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal operator and value combination.');

        $builder = $this->getBuilder();
        $builder->prepareValueAndOperator(null, 'like');
    }

    public function testProvidingNullWithOperatorsBuildsCorrectly()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('foo', null);
        $this->assertSame('select * from "users" where "foo" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('foo', '=', null);
        $this->assertSame('select * from "users" where "foo" is null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('foo', '!=', null);
        $this->assertSame('select * from "users" where "foo" is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('foo', '<>', null);
        $this->assertSame('select * from "users" where "foo" is not null', $builder->toSql());
    }

    public function testDynamicWhere()
    {
        $method = 'whereFooBarAndBazOrQux';
        $parameters = ['corge', 'waldo', 'fred'];
        $builder = m::mock(Builder::class)->makePartial();

        $builder->shouldReceive('where')->with('foo_bar', '=', $parameters[0], 'and')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('baz', '=', $parameters[1], 'and')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('qux', '=', $parameters[2], 'or')->once()->andReturnSelf();

        $this->assertEquals($builder, $builder->dynamicWhere($method, $parameters));
    }

    public function testDynamicWhereIsNotGreedy()
    {
        $method = 'whereIosVersionAndAndroidVersionOrOrientation';
        $parameters = ['6.1', '4.2', 'Vertical'];
        $builder = m::mock(Builder::class)->makePartial();

        $builder->shouldReceive('where')->with('ios_version', '=', '6.1', 'and')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('android_version', '=', '4.2', 'and')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('orientation', '=', 'Vertical', 'or')->once()->andReturnSelf();

        $builder->dynamicWhere($method, $parameters);
    }

    public function testCallTriggersDynamicWhere()
    {
        $builder = $this->getBuilder();

        $this->assertEquals($builder, $builder->whereFooAndBar('baz', 'qux'));
        $this->assertCount(2, $builder->wheres);
    }

    public function testBuilderThrowsExpectedExceptionWithUndefinedMethod()
    {
        $this->expectException(BadMethodCallException::class);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select');
        $builder->getProcessor()->shouldReceive('processSelect')->andReturn([]);

        $builder->noValidMethodHere();
    }

    public function testMySqlLock()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock();
        $this->assertSame('select * from `foo` where `bar` = ? for update', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false);
        $this->assertSame('select * from `foo` where `bar` = ? lock in share mode', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock('lock in share mode');
        $this->assertSame('select * from `foo` where `bar` = ? lock in share mode', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testPostgresLock()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock();
        $this->assertSame('select * from "foo" where "bar" = ? for update', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false);
        $this->assertSame('select * from "foo" where "bar" = ? for share', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock('for key share');
        $this->assertSame('select * from "foo" where "bar" = ? for key share', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testSqlServerLock()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock();
        $this->assertSame('select * from [foo] with(rowlock,updlock,holdlock) where [bar] = ?', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false);
        $this->assertSame('select * from [foo] with(rowlock,holdlock) where [bar] = ?', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock('with(holdlock)');
        $this->assertSame('select * from [foo] with(holdlock) where [bar] = ?', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testSelectWithLockUsesWritePdo()
    {
        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with(m::any(), m::any(), false);
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock()->get();

        $builder = $this->getMySqlBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with(m::any(), m::any(), false);
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false)->get();
    }

    public function testBindingOrder()
    {
        $expectedSql = 'select * from "users" inner join "othertable" on "bar" = ? where "registered" = ? group by "city" having "population" > ? order by match ("foo") against(?)';
        $expectedBindings = ['foo', 1, 3, 'bar'];

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->join('othertable', function ($join) {
            $join->where('bar', '=', 'foo');
        })->where('registered', 1)->groupBy('city')->having('population', '>', 3)->orderByRaw('match ("foo") against(?)', ['bar']);
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());

        // order of statements reversed
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderByRaw('match ("foo") against(?)', ['bar'])->having('population', '>', 3)->groupBy('city')->where('registered', 1)->join('othertable', function ($join) {
            $join->where('bar', '=', 'foo');
        });
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());
    }

    public function testAddBindingWithArrayMergesBindings()
    {
        $builder = $this->getBuilder();
        $builder->addBinding(['foo', 'bar']);
        $builder->addBinding(['baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $builder->getBindings());
    }

    public function testAddBindingWithArrayMergesBindingsInCorrectOrder()
    {
        $builder = $this->getBuilder();
        $builder->addBinding(['bar', 'baz'], 'having');
        $builder->addBinding(['foo'], 'where');
        $this->assertEquals(['foo', 'bar', 'baz'], $builder->getBindings());
    }

    public function testAddBindingWithEnum()
    {
        $builder = $this->getBuilder();
        $builder->addBinding(IntegerStatus::done);
        $builder->addBinding([NonBackedStatus::done]);
        $this->assertEquals([2, 'done'], $builder->getBindings());
    }

    public function testMergeBuilders()
    {
        $builder = $this->getBuilder();
        $builder->addBinding(['foo', 'bar']);
        $otherBuilder = $this->getBuilder();
        $otherBuilder->addBinding(['baz']);
        $builder->mergeBindings($otherBuilder);
        $this->assertEquals(['foo', 'bar', 'baz'], $builder->getBindings());
    }

    public function testMergeBuildersBindingOrder()
    {
        $builder = $this->getBuilder();
        $builder->addBinding('foo', 'where');
        $builder->addBinding('baz', 'having');
        $otherBuilder = $this->getBuilder();
        $otherBuilder->addBinding('bar', 'where');
        $builder->mergeBindings($otherBuilder);
        $this->assertEquals(['foo', 'bar', 'baz'], $builder->getBindings());
    }

    public function testSubSelect()
    {
        $expectedSql = 'select "foo", "bar", (select "baz" from "two" where "subkey" = ?) as "sub" from "one" where "key" = ?';
        $expectedBindings = ['subval', 'val'];

        $builder = $this->getPostgresBuilder();
        $builder->from('one')->select(['foo', 'bar'])->where('key', '=', 'val');
        $builder->selectSub(function ($query) {
            $query->from('two')->select('baz')->where('subkey', '=', 'subval');
        }, 'sub');
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->from('one')->select(['foo', 'bar'])->where('key', '=', 'val');
        $subBuilder = $this->getPostgresBuilder();
        $subBuilder->from('two')->select('baz')->where('subkey', '=', 'subval');
        $builder->selectSub($subBuilder, 'sub');
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getPostgresBuilder();
        $builder->selectSub(['foo'], 'sub');
    }

    public function testSubSelectResetBindings()
    {
        $builder = $this->getPostgresBuilder();
        $builder->from('one')->selectSub(function ($query) {
            $query->from('two')->select('baz')->where('subkey', '=', 'subval');
        }, 'sub');

        $this->assertSame('select (select "baz" from "two" where "subkey" = ?) as "sub" from "one"', $builder->toSql());
        $this->assertEquals(['subval'], $builder->getBindings());

        $builder->select('*');

        $this->assertSame('select * from "one"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    public function testSqlServerWhereDate()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-09-23');
        $this->assertSame('select * from [users] where cast([created_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-09-23'], $builder->getBindings());
    }

    public function testUppercaseLeadingBooleansAreRemoved()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('name', '=', 'Taylor', 'AND');
        $this->assertSame('select * from "users" where "name" = ?', $builder->toSql());
    }

    public function testLowercaseLeadingBooleansAreRemoved()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('name', '=', 'Taylor', 'and');
        $this->assertSame('select * from "users" where "name" = ?', $builder->toSql());
    }

    public function testCaseInsensitiveLeadingBooleansAreRemoved()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('name', '=', 'Taylor', 'And');
        $this->assertSame('select * from "users" where "name" = ?', $builder->toSql());
    }

    public function testTableValuedFunctionAsTableInSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users()');
        $this->assertSame('select * from [users]()', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users(1,2)');
        $this->assertSame('select * from [users](1,2)', $builder->toSql());
    }

    public function testChunkWithLastChunkComplete()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect(['foo1', 'foo2']);
        $chunk2 = collect(['foo3', 'foo4']);
        $chunk3 = collect([]);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(3, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkWithLastChunkPartial()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect(['foo1', 'foo2']);
        $chunk2 = collect(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect(['foo1', 'foo2']);
        $chunk2 = collect(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->never()->with(2, 2);
        $builder->shouldReceive('get')->times(1)->andReturn($chunk1);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);

            return false;
        });
    }

    public function testChunkWithCountZero()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = collect([]);
        $builder->shouldReceive('forPage')->once()->with(1, 0)->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunk(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkByIdOnArrays()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect([['someIdField' => 1], ['someIdField' => 2]]);
        $chunk2 = collect([['someIdField' => 10], ['someIdField' => 11]]);
        $chunk3 = collect([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithLastChunkComplete()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = collect([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = collect([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithLastChunkPartial()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = collect([(object) ['someIdField' => 10]]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithCountZero()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = collect([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(0, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunkById(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithAlias()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = collect([(object) ['table_id' => 1], (object) ['table_id' => 10]]);
        $chunk2 = collect([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'table.id')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 10, 'table.id')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'table.id', 'table_id');
    }

    public function testChunkPaginatesUsingIdDesc()
    {
        $builder = $this->getMockQueryBuilder();
        $builder->orders[] = ['column' => 'foobar', 'direction' => 'desc'];

        $chunk1 = collect([(object) ['someIdField' => 10], (object) ['someIdField' => 1]]);
        $chunk2 = collect([]);
        $builder->shouldReceive('forPageBeforeId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageBeforeId')->once()->with(2, 1, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunkByIdDesc(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testPaginate()
    {
        $perPage = 16;
        $columns = ['test'];
        $pageName = 'page-name';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('getCountForPagination')->once()->andReturn(2);
        $builder->shouldReceive('forPage')->once()->with($page, $perPage)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($results);

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate($perPage, $columns, $pageName, $page);

        $this->assertEquals(new LengthAwarePaginator($results, 2, $perPage, $page, [
            'path' => $path,
            'pageName' => $pageName,
        ]), $result);
    }

    public function testPaginateWithDefaultArguments()
    {
        $perPage = 15;
        $pageName = 'page';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('getCountForPagination')->once()->andReturn(2);
        $builder->shouldReceive('forPage')->once()->with($page, $perPage)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($results);

        Paginator::currentPageResolver(function () {
            return 1;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate();

        $this->assertEquals(new LengthAwarePaginator($results, 2, $perPage, $page, [
            'path' => $path,
            'pageName' => $pageName,
        ]), $result);
    }

    public function testPaginateWhenNoResults()
    {
        $perPage = 15;
        $pageName = 'page';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = [];

        $builder->shouldReceive('getCountForPagination')->once()->andReturn(0);
        $builder->shouldNotReceive('forPage');
        $builder->shouldNotReceive('get');

        Paginator::currentPageResolver(function () {
            return 1;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate();

        $this->assertEquals(new LengthAwarePaginator($results, 0, $perPage, $page, [
            'path' => $path,
            'pageName' => $pageName,
        ]), $result);
    }

    public function testPaginateWithSpecificColumns()
    {
        $perPage = 16;
        $columns = ['id', 'name'];
        $pageName = 'page-name';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = collect([['id' => 3, 'name' => 'Taylor'], ['id' => 5, 'name' => 'Mohamed']]);

        $builder->shouldReceive('getCountForPagination')->once()->andReturn(2);
        $builder->shouldReceive('forPage')->once()->with($page, $perPage)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($results);

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate($perPage, $columns, $pageName, $page);

        $this->assertEquals(new LengthAwarePaginator($results, 2, $perPage, $page, [
            'path' => $path,
            'pageName' => $pageName,
        ]), $result);
    }

    public function testPaginateWithTotalOverride()
    {
        $perPage = 16;
        $columns = ['id', 'name'];
        $pageName = 'page-name';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = collect([['id' => 3, 'name' => 'Taylor'], ['id' => 5, 'name' => 'Mohamed']]);

        $builder->shouldReceive('getCountForPagination')->never();
        $builder->shouldReceive('forPage')->once()->with($page, $perPage)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($results);

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate($perPage, $columns, $pageName, $page, 10);

        $this->assertEquals(10, $result->total());
    }

    public function testCursorPaginate()
    {
        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['test' => 'bar']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->orderBy('test');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select * from "foobar" where ("test" > ?) order by "test" asc limit 17',
                $builder->toSql());
            $this->assertEquals(['bar'], $builder->bindings['where']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateMultipleOrderColumns()
    {
        $perPage = 16;
        $columns = ['test', 'another'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['test' => 'bar', 'another' => 'foo']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->orderBy('test')->orderBy('another');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo', 'another' => 1], ['test' => 'bar', 'another' => 2]]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select * from "foobar" where ("test" > ? or ("test" = ? and ("another" > ?))) order by "test" asc, "another" asc limit 17',
                $builder->toSql()
            );
            $this->assertEquals(['bar', 'bar', 'foo'], $builder->bindings['where']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test', 'another'],
        ]), $result);
    }

    public function testCursorPaginateWithDefaultArguments()
    {
        $perPage = 15;
        $cursorName = 'cursor';
        $cursor = new Cursor(['test' => 'bar']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->orderBy('test');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select * from "foobar" where ("test" > ?) order by "test" asc limit 16',
                $builder->toSql());
            $this->assertEquals(['bar'], $builder->bindings['where']);

            return $results;
        });

        CursorPaginator::currentCursorResolver(function () use ($cursor) {
            return $cursor;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate();

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateWhenNoResults()
    {
        $perPage = 15;
        $cursorName = 'cursor';
        $builder = $this->getMockQueryBuilder()->orderBy('test');
        $path = 'http://foo.bar?cursor=3';

        $results = [];

        $builder->shouldReceive('get')->once()->andReturn($results);

        CursorPaginator::currentCursorResolver(function () {
            return null;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate();

        $this->assertEquals(new CursorPaginator($results, $perPage, null, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateWithSpecificColumns()
    {
        $perPage = 16;
        $columns = ['id', 'name'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['id' => 2]);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->orderBy('id');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor=3';

        $results = collect([['id' => 3, 'name' => 'Taylor'], ['id' => 5, 'name' => 'Mohamed']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select * from "foobar" where ("id" > ?) order by "id" asc limit 17',
                $builder->toSql());
            $this->assertEquals([2], $builder->bindings['where']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['id'],
        ]), $result);
    }

    public function testCursorPaginateWithMixedOrders()
    {
        $perPage = 16;
        $columns = ['foo', 'bar', 'baz'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['foo' => 1, 'bar' => 2, 'baz' => 3]);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->orderBy('foo')->orderByDesc('bar')->orderBy('baz');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['foo' => 1, 'bar' => 2, 'baz' => 4], ['foo' => 1, 'bar' => 1, 'baz' => 1]]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select * from "foobar" where ("foo" > ? or ("foo" = ? and ("bar" < ? or ("bar" = ? and ("baz" > ?))))) order by "foo" asc, "bar" desc, "baz" asc limit 17',
                $builder->toSql()
            );
            $this->assertEquals([1, 1, 2, 2, 3], $builder->bindings['where']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['foo', 'bar', 'baz'],
        ]), $result);
    }

    public function testCursorPaginateWithDynamicColumnInSelectRaw()
    {
        $perPage = 15;
        $cursorName = 'cursor';
        $cursor = new Cursor(['test' => 'bar']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->select('*')->selectRaw('(CONCAT(firstname, \' \', lastname)) as test')->orderBy('test');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select *, (CONCAT(firstname, \' \', lastname)) as test from "foobar" where ((CONCAT(firstname, \' \', lastname)) > ?) order by "test" asc limit 16',
                $builder->toSql());
            $this->assertEquals(['bar'], $builder->bindings['where']);

            return $results;
        });

        CursorPaginator::currentCursorResolver(function () use ($cursor) {
            return $cursor;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate();

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateWithDynamicColumnWithCastInSelectRaw()
    {
        $perPage = 15;
        $cursorName = 'cursor';
        $cursor = new Cursor(['test' => 'bar']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->select('*')->selectRaw('(CAST(CONCAT(firstname, \' \', lastname) as VARCHAR)) as test')->orderBy('test');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select *, (CAST(CONCAT(firstname, \' \', lastname) as VARCHAR)) as test from "foobar" where ((CAST(CONCAT(firstname, \' \', lastname) as VARCHAR)) > ?) order by "test" asc limit 16',
                $builder->toSql());
            $this->assertEquals(['bar'], $builder->bindings['where']);

            return $results;
        });

        CursorPaginator::currentCursorResolver(function () use ($cursor) {
            return $cursor;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate();

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateWithDynamicColumnInSelectSub()
    {
        $perPage = 15;
        $cursorName = 'cursor';
        $cursor = new Cursor(['test' => 'bar']);
        $builder = $this->getMockQueryBuilder();
        $builder->from('foobar')->select('*')->selectSub('CONCAT(firstname, \' \', lastname)', 'test')->orderBy('test');
        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results) {
            $this->assertEquals(
                'select *, (CONCAT(firstname, \' \', lastname)) as "test" from "foobar" where ((CONCAT(firstname, \' \', lastname)) > ?) order by "test" asc limit 16',
                $builder->toSql());
            $this->assertEquals(['bar'], $builder->bindings['where']);

            return $results;
        });

        CursorPaginator::currentCursorResolver(function () use ($cursor) {
            return $cursor;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate();

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['test'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionWheres()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts]);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at')->selectRaw("'video' as type")->from('videos');
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'news' as type")->from('news'));
        $builder->orderBy('created_at');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video'],
            ['id' => 2, 'created_at' => now(), 'type' => 'news'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", \'video\' as type from "videos" where ("start_time" > ?)) union (select "id", "created_at", \'news\' as type from "news" where ("created_at" > ?)) order by "created_at" asc limit 17',
                $builder->toSql());
            $this->assertEquals([$ts], $builder->bindings['where']);
            $this->assertEquals([$ts], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at'],
        ]), $result);
    }

    public function testCursorPaginateWithMultipleUnionsAndMultipleWheres()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts]);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at')->selectRaw("'video' as type")->from('videos');
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'news' as type")->from('news')->where('extra', 'first'));
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'podcast' as type")->from('podcasts')->where('extra', 'second'));
        $builder->orderBy('created_at');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video'],
            ['id' => 2, 'created_at' => now(), 'type' => 'news'],
            ['id' => 3, 'created_at' => now(), 'type' => 'podcasts'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", \'video\' as type from "videos" where ("start_time" > ?)) union (select "id", "created_at", \'news\' as type from "news" where "extra" = ? and ("created_at" > ?)) union (select "id", "created_at", \'podcast\' as type from "podcasts" where "extra" = ? and ("created_at" > ?)) order by "created_at" asc limit 17',
                $builder->toSql());
            $this->assertEquals([$ts], $builder->bindings['where']);
            $this->assertEquals(['first', $ts, 'second', $ts], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionMultipleWheresMultipleOrders()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['id', 'created_at', 'type'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['id' => 1, 'created_at' => $ts, 'type' => 'news']);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at', 'type')->from('videos')->where('extra', 'first');
        $builder->union($this->getBuilder()->select('id', 'created_at', 'type')->from('news')->where('extra', 'second'));
        $builder->union($this->getBuilder()->select('id', 'created_at', 'type')->from('podcasts')->where('extra', 'third'));
        $builder->orderBy('id')->orderByDesc('created_at')->orderBy('type');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now()->addDay(), 'type' => 'video'],
            ['id' => 1, 'created_at' => now(), 'type' => 'news'],
            ['id' => 1, 'created_at' => now(), 'type' => 'podcast'],
            ['id' => 2, 'created_at' => now(), 'type' => 'podcast'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", "type" from "videos" where "extra" = ? and ("id" > ? or ("id" = ? and ("start_time" < ? or ("start_time" = ? and ("type" > ?)))))) union (select "id", "created_at", "type" from "news" where "extra" = ? and ("id" > ? or ("id" = ? and ("start_time" < ? or ("start_time" = ? and ("type" > ?)))))) union (select "id", "created_at", "type" from "podcasts" where "extra" = ? and ("id" > ? or ("id" = ? and ("start_time" < ? or ("start_time" = ? and ("type" > ?)))))) order by "id" asc, "created_at" desc, "type" asc limit 17',
                $builder->toSql());
            $this->assertEquals(['first', 1, 1, $ts, $ts, 'news'], $builder->bindings['where']);
            $this->assertEquals(['second', 1, 1, $ts, $ts, 'news', 'third', 1, 1, $ts, $ts, 'news'], $builder->bindings ['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['id', 'created_at', 'type'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionWheresWithRawOrderExpression()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts]);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'is_published', 'start_time as created_at')->selectRaw("'video' as type")->where('is_published', true)->from('videos');
        $builder->union($this->getBuilder()->select('id', 'is_published', 'created_at')->selectRaw("'news' as type")->where('is_published', true)->from('news'));
        $builder->orderByRaw('case when (id = 3 and type="news" then 0 else 1 end)')->orderBy('created_at');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video', 'is_published' => true],
            ['id' => 2, 'created_at' => now(), 'type' => 'news', 'is_published' => true],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "is_published", "start_time" as "created_at", \'video\' as type from "videos" where "is_published" = ? and ("start_time" > ?)) union (select "id", "is_published", "created_at", \'news\' as type from "news" where "is_published" = ? and ("created_at" > ?)) order by case when (id = 3 and type="news" then 0 else 1 end), "created_at" asc limit 17',
                $builder->toSql());
            $this->assertEquals([true, $ts], $builder->bindings['where']);
            $this->assertEquals([true, $ts], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionWheresReverseOrder()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts], false);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at')->selectRaw("'video' as type")->from('videos');
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'news' as type")->from('news'));
        $builder->orderBy('created_at');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video'],
            ['id' => 2, 'created_at' => now(), 'type' => 'news'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", \'video\' as type from "videos" where ("start_time" < ?)) union (select "id", "created_at", \'news\' as type from "news" where ("created_at" < ?)) order by "created_at" desc limit 17',
                $builder->toSql());
            $this->assertEquals([$ts], $builder->bindings['where']);
            $this->assertEquals([$ts], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionWheresMultipleOrders()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts, 'id' => 1]);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at')->selectRaw("'video' as type")->from('videos');
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'news' as type")->from('news'));
        $builder->orderByDesc('created_at')->orderBy('id');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video'],
            ['id' => 2, 'created_at' => now(), 'type' => 'news'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", \'video\' as type from "videos" where ("start_time" < ? or ("start_time" = ? and ("id" > ?)))) union (select "id", "created_at", \'news\' as type from "news" where ("created_at" < ? or ("created_at" = ? and ("id" > ?)))) order by "created_at" desc, "id" asc limit 17',
                $builder->toSql());
            $this->assertEquals([$ts, $ts, 1], $builder->bindings['where']);
            $this->assertEquals([$ts, $ts, 1], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at', 'id'],
        ]), $result);
    }

    public function testCursorPaginateWithUnionWheresAndAliassedOrderColumns()
    {
        $ts = now()->toDateTimeString();

        $perPage = 16;
        $columns = ['test'];
        $cursorName = 'cursor-name';
        $cursor = new Cursor(['created_at' => $ts]);
        $builder = $this->getMockQueryBuilder();
        $builder->select('id', 'start_time as created_at')->selectRaw("'video' as type")->from('videos');
        $builder->union($this->getBuilder()->select('id', 'created_at')->selectRaw("'news' as type")->from('news'));
        $builder->union($this->getBuilder()->select('id', 'init_at as created_at')->selectRaw("'podcast' as type")->from('podcasts'));
        $builder->orderBy('created_at');

        $builder->shouldReceive('newQuery')->andReturnUsing(function () use ($builder) {
            return new Builder($builder->connection, $builder->grammar, $builder->processor);
        });

        $path = 'http://foo.bar?cursor='.$cursor->encode();

        $results = collect([
            ['id' => 1, 'created_at' => now(), 'type' => 'video'],
            ['id' => 2, 'created_at' => now(), 'type' => 'news'],
            ['id' => 3, 'created_at' => now(), 'type' => 'podcast'],
        ]);

        $builder->shouldReceive('get')->once()->andReturnUsing(function () use ($builder, $results, $ts) {
            $this->assertEquals(
                '(select "id", "start_time" as "created_at", \'video\' as type from "videos" where ("start_time" > ?)) union (select "id", "created_at", \'news\' as type from "news" where ("created_at" > ?)) union (select "id", "init_at" as "created_at", \'podcast\' as type from "podcasts" where ("init_at" > ?)) order by "created_at" asc limit 17',
                $builder->toSql());
            $this->assertEquals([$ts], $builder->bindings['where']);
            $this->assertEquals([$ts, $ts], $builder->bindings['union']);

            return $results;
        });

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->cursorPaginate($perPage, $columns, $cursorName, $cursor);

        $this->assertEquals(new CursorPaginator($results, $perPage, $cursor, [
            'path' => $path,
            'cursorName' => $cursorName,
            'parameters' => ['created_at'],
        ]), $result);
    }

    public function testWhereExpression()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where(
            new class() implements ConditionExpression
            {
                public function getValue(\Illuminate\Database\Grammar $grammar)
                {
                    return '1 = 1';
                }
            }
        );
        $this->assertSame('select * from "orders" where 1 = 1', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function testWhereRowValues()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereRowValues(['last_update', 'order_number'], '<', [1, 2]);
        $this->assertSame('select * from "orders" where ("last_update", "order_number") < (?, ?)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->where('company_id', 1)->orWhereRowValues(['last_update', 'order_number'], '<', [1, 2]);
        $this->assertSame('select * from "orders" where "company_id" = ? or ("last_update", "order_number") < (?, ?)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereRowValues(['last_update', 'order_number'], '<', [1, new Raw('2')]);
        $this->assertSame('select * from "orders" where ("last_update", "order_number") < (?, 2)', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereRowValuesArityMismatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of columns must match the number of values');

        $builder = $this->getBuilder();
        $builder->select('*')->from('orders')->whereRowValues(['last_update'], '<', [1, 2]);
    }

    public function testWhereJsonContainsMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', ['en']);
        $this->assertSame('select * from `users` where json_contains(`options`, ?)', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->languages', ['en']);
        $this->assertSame('select * from `users` where json_contains(`users`.`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContains('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or json_contains(`options`, \'["en"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonOverlapsMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonOverlaps('options', ['en', 'fr']);
        $this->assertSame('select * from `users` where json_overlaps(`options`, ?)', $builder->toSql());
        $this->assertEquals(['["en","fr"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonOverlaps('users.options->languages', ['en', 'fr']);
        $this->assertSame('select * from `users` where json_overlaps(`users`.`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en","fr"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonOverlaps('options->languages', new Raw("'[\"en\", \"fr\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or json_overlaps(`options`, \'["en", "fr"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonContainsPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', ['en']);
        $this->assertSame('select * from "users" where ("options")::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->languages', ['en']);
        $this->assertSame('select * from "users" where ("users"."options"->\'languages\')::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContains('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from "users" where "id" = ? or ("options"->\'languages\')::jsonb @> \'["en"]\'', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonContainsSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', 'en')->toSql();
        $this->assertSame('select * from "users" where exists (select 1 from json_each("options") where "json_each"."value" is ?)', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->language', 'en')->toSql();
        $this->assertSame('select * from "users" where exists (select 1 from json_each("users"."options", \'$."language"\') where "json_each"."value" is ?)', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());
    }

    public function testWhereJsonContainsSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', true);
        $this->assertSame('select * from [users] where ? in (select [value] from openjson([options]))', $builder->toSql());
        $this->assertEquals(['true'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->languages', 'en');
        $this->assertSame('select * from [users] where ? in (select [value] from openjson([users].[options], \'$."languages"\'))', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContains('options->languages', new Raw("'en'"));
        $this->assertSame('select * from [users] where [id] = ? or \'en\' in (select [value] from openjson([options], \'$."languages"\'))', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntContainMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', ['en']);
        $this->assertSame('select * from `users` where not json_contains(`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContain('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or not json_contains(`options`, \'["en"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntOverlapMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntOverlap('options->languages', ['en', 'fr']);
        $this->assertSame('select * from `users` where not json_overlaps(`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en","fr"]'], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntOverlap('options->languages', new Raw("'[\"en\", \"fr\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or not json_overlaps(`options`, \'["en", "fr"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntContainPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', ['en']);
        $this->assertSame('select * from "users" where not ("options"->\'languages\')::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContain('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from "users" where "id" = ? or not ("options"->\'languages\')::jsonb @> \'["en"]\'', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntContainSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options', 'en')->toSql();
        $this->assertSame('select * from "users" where not exists (select 1 from json_each("options") where "json_each"."value" is ?)', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('users.options->language', 'en')->toSql();
        $this->assertSame('select * from "users" where not exists (select 1 from json_each("users"."options", \'$."language"\') where "json_each"."value" is ?)', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());
    }

    public function testWhereJsonDoesntContainSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', 'en');
        $this->assertSame('select * from [users] where not ? in (select [value] from openjson([options], \'$."languages"\'))', $builder->toSql());
        $this->assertEquals(['en'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContain('options->languages', new Raw("'en'"));
        $this->assertSame('select * from [users] where [id] = ? or not \'en\' in (select [value] from openjson([options], \'$."languages"\'))', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonContainsKeyMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`users`.`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`options`, \'one\', \'$."language"."primary"\'), 0)', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from `users` where `id` = ? or ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`options`, \'one\', \'$."languages"[0][1]\'), 0)', $builder->toSql());
    }

    public function testWhereJsonContainsKeyPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from "users" where coalesce(("users"."options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from "users" where coalesce(("options"->\'language\')::jsonb ?? \'primary\', false)', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where case when jsonb_typeof(("options"->\'languages\'->0)::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\'->0)::jsonb) >= 2 else false end', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[-1]');
        $this->assertSame('select * from "users" where case when jsonb_typeof(("options"->\'languages\')::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\')::jsonb) >= 1 else false end', $builder->toSql());
    }

    public function testWhereJsonContainsKeySqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from "users" where json_type("users"."options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from "users" where json_type("options", \'$."language"."primary"\') is not null', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where json_type("options", \'$."languages"[0][1]\') is not null', $builder->toSql());
    }

    public function testWhereJsonContainsKeySqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from [users] where \'languages\' in (select [key] from openjson([users].[options]))', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from [users] where \'primary\' in (select [key] from openjson([options], \'$."language"\'))', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from [users] where [id] = ? or \'languages\' in (select [key] from openjson([options]))', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from [users] where 1 in (select [key] from openjson([options], \'$."languages"[0]\'))', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKeyMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from `users` where not ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from `users` where `id` = ? or not ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from `users` where not ifnull(json_contains_path(`options`, \'one\', \'$."languages"[0][1]\'), 0)', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKeyPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where not coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or not coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where not case when jsonb_typeof(("options"->\'languages\'->0)::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\'->0)::jsonb) >= 2 else false end', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[-1]');
        $this->assertSame('select * from "users" where not case when jsonb_typeof(("options"->\'languages\')::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\')::jsonb) >= 1 else false end', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKeySqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where not json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or not json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where "id" = ? or not json_type("options", \'$."languages"[0][1]\') is not null', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKeySqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from [users] where not \'languages\' in (select [key] from openjson([options]))', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from [users] where [id] = ? or not \'languages\' in (select [key] from openjson([options]))', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from [users] where [id] = ? or not 1 in (select [key] from openjson([options], \'$."languages"[0]\'))', $builder->toSql());
    }

    public function testWhereJsonLengthMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from `users` where json_length(`options`) = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from `users` where json_length(`users`.`options`, \'$."languages"\') > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from `users` where `id` = ? or json_length(`options`, \'$."languages"\') = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getMySqlBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from `users` where `id` = ? or json_length(`options`, \'$."languages"\') > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonLengthPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from "users" where jsonb_array_length(("options")::jsonb) = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from "users" where jsonb_array_length(("users"."options"->\'languages\')::jsonb) > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or jsonb_array_length(("options"->\'languages\')::jsonb) = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or jsonb_array_length(("options"->\'languages\')::jsonb) > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonLengthSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from "users" where json_array_length("options") = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from "users" where json_array_length("users"."options", \'$."languages"\') > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or json_array_length("options", \'$."languages"\') = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getSQLiteBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or json_array_length("options", \'$."languages"\') > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonLengthSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from [users] where (select count(*) from openjson([options])) = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from [users] where (select count(*) from openjson([users].[options], \'$."languages"\')) > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from [users] where [id] = ? or (select count(*) from openjson([options], \'$."languages"\')) = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from [users] where [id] = ? or (select count(*) from openjson([options], \'$."languages"\')) > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testFrom()
    {
        $builder = $this->getBuilder();
        $builder->from($this->getBuilder()->from('users'), 'u');
        $this->assertSame('select * from (select * from "users") as "u"', $builder->toSql());

        $builder = $this->getBuilder();
        $eloquentBuilder = new EloquentBuilder($this->getBuilder());
        $builder->from($eloquentBuilder->from('users'), 'u');
        $this->assertSame('select * from (select * from "users") as "u"', $builder->toSql());
    }

    public function testFromSub()
    {
        $builder = $this->getBuilder();
        $builder->fromSub(function ($query) {
            $query->select(new Raw('max(last_seen_at) as last_seen_at'))->from('user_sessions')->where('foo', '=', '1');
        }, 'sessions')->where('bar', '<', '10');
        $this->assertSame('select * from (select max(last_seen_at) as last_seen_at from "user_sessions" where "foo" = ?) as "sessions" where "bar" < ?', $builder->toSql());
        $this->assertEquals(['1', '10'], $builder->getBindings());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->fromSub(['invalid'], 'sessions')->where('bar', '<', '10');
    }

    public function testFromSubWithPrefix()
    {
        $builder = $this->getBuilder();
        $builder->getGrammar()->setTablePrefix('prefix_');
        $builder->fromSub(function ($query) {
            $query->select(new Raw('max(last_seen_at) as last_seen_at'))->from('user_sessions')->where('foo', '=', '1');
        }, 'sessions')->where('bar', '<', '10');
        $this->assertSame('select * from (select max(last_seen_at) as last_seen_at from "prefix_user_sessions" where "foo" = ?) as "prefix_sessions" where "bar" < ?', $builder->toSql());
        $this->assertEquals(['1', '10'], $builder->getBindings());
    }

    public function testFromSubWithoutBindings()
    {
        $builder = $this->getBuilder();
        $builder->fromSub(function ($query) {
            $query->select(new Raw('max(last_seen_at) as last_seen_at'))->from('user_sessions');
        }, 'sessions');
        $this->assertSame('select * from (select max(last_seen_at) as last_seen_at from "user_sessions") as "sessions"', $builder->toSql());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->fromSub(['invalid'], 'sessions');
    }

    public function testFromRaw()
    {
        $builder = $this->getBuilder();
        $builder->fromRaw(new Raw('(select max(last_seen_at) as last_seen_at from "user_sessions") as "sessions"'));
        $this->assertSame('select * from (select max(last_seen_at) as last_seen_at from "user_sessions") as "sessions"', $builder->toSql());
    }

    public function testFromRawOnSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->fromRaw('dbo.[SomeNameWithRoundBrackets (test)]');
        $this->assertSame('select * from dbo.[SomeNameWithRoundBrackets (test)]', $builder->toSql());
    }

    public function testFromRawWithWhereOnTheMainQuery()
    {
        $builder = $this->getBuilder();
        $builder->fromRaw(new Raw('(select max(last_seen_at) as last_seen_at from "sessions") as "last_seen_at"'))->where('last_seen_at', '>', '1520652582');
        $this->assertSame('select * from (select max(last_seen_at) as last_seen_at from "sessions") as "last_seen_at" where "last_seen_at" > ?', $builder->toSql());
        $this->assertEquals(['1520652582'], $builder->getBindings());
    }

    public function testFromQuestionMarkOperatorOnPostgres()
    {
        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('roles', '?', 'superuser');
        $this->assertSame('select * from "users" where "roles" ?? ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('roles', '?|', 'superuser');
        $this->assertSame('select * from "users" where "roles" ??| ?', $builder->toSql());

        $builder = $this->getPostgresBuilder();
        $builder->select('*')->from('users')->where('roles', '?&', 'superuser');
        $this->assertSame('select * from "users" where "roles" ??& ?', $builder->toSql());
    }

    public function testUseIndexMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('foo')->from('users')->useIndex('test_index');
        $this->assertSame('select `foo` from `users` use index (test_index)', $builder->toSql());
    }

    public function testForceIndexMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('foo')->from('users')->forceIndex('test_index');
        $this->assertSame('select `foo` from `users` force index (test_index)', $builder->toSql());
    }

    public function testIgnoreIndexMySql()
    {
        $builder = $this->getMySqlBuilder();
        $builder->select('foo')->from('users')->ignoreIndex('test_index');
        $this->assertSame('select `foo` from `users` ignore index (test_index)', $builder->toSql());
    }

    public function testUseIndexSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('foo')->from('users')->useIndex('test_index');
        $this->assertSame('select "foo" from "users"', $builder->toSql());
    }

    public function testForceIndexSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('foo')->from('users')->forceIndex('test_index');
        $this->assertSame('select "foo" from "users" indexed by test_index', $builder->toSql());
    }

    public function testIgnoreIndexSqlite()
    {
        $builder = $this->getSQLiteBuilder();
        $builder->select('foo')->from('users')->ignoreIndex('test_index');
        $this->assertSame('select "foo" from "users"', $builder->toSql());
    }

    public function testUseIndexSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('foo')->from('users')->useIndex('test_index');
        $this->assertSame('select [foo] from [users]', $builder->toSql());
    }

    public function testForceIndexSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('foo')->from('users')->forceIndex('test_index');
        $this->assertSame('select [foo] from [users] with (index(test_index))', $builder->toSql());
    }

    public function testIgnoreIndexSqlServer()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('foo')->from('users')->ignoreIndex('test_index');
        $this->assertSame('select [foo] from [users]', $builder->toSql());
    }

    public function testClone()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $clone = $builder->clone()->where('email', 'foo');

        $this->assertNotSame($builder, $clone);
        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());
    }

    public function testCloneWithout()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'foo')->orderBy('email');
        $clone = $builder->cloneWithout(['orders']);

        $this->assertSame('select * from "users" where "email" = ? order by "email" asc', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());
    }

    public function testCloneWithoutBindings()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('email', 'foo')->orderBy('email');
        $clone = $builder->cloneWithout(['wheres'])->cloneWithoutBindings(['where']);

        $this->assertSame('select * from "users" where "email" = ? order by "email" asc', $builder->toSql());
        $this->assertEquals([0 => 'foo'], $builder->getBindings());

        $this->assertSame('select * from "users" order by "email" asc', $clone->toSql());
        $this->assertEquals([], $clone->getBindings());
    }

    public function testToRawSql()
    {
        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('prepareBindings')
            ->with(['foo'])
            ->andReturn(['foo']);
        $grammar = m::mock(Grammar::class)->makePartial();
        $grammar->shouldReceive('substituteBindingsIntoRawSql')
            ->with('select * from "users" where "email" = ?', ['foo'])
            ->andReturn('select * from "users" where "email" = \'foo\'');
        $builder = new Builder($connection, $grammar, m::mock(Processor::class));
        $builder->select('*')->from('users')->where('email', 'foo');

        $this->assertSame('select * from "users" where "email" = \'foo\'', $builder->toRawSql());
    }

    protected function getConnection()
    {
        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        return $connection;
    }

    protected function getBuilder()
    {
        $grammar = new Grammar;
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getPostgresBuilder()
    {
        $grammar = new PostgresGrammar;
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getMySqlBuilder()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    protected function getMariaDbBuilder()
    {
        $grammar = new MariaDbGrammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    protected function getSQLiteBuilder()
    {
        $grammar = new SQLiteGrammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    protected function getSqlServerBuilder()
    {
        $grammar = new SqlServerGrammar;
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getMySqlBuilderWithProcessor()
    {
        $grammar = new MySqlGrammar;
        $processor = new MySqlProcessor;

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    protected function getPostgresBuilderWithProcessor()
    {
        $grammar = new PostgresGrammar;
        $processor = new PostgresProcessor;

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    /**
     * @return \Mockery\MockInterface|\Illuminate\Database\Query\Builder
     */
    protected function getMockQueryBuilder()
    {
        return m::mock(Builder::class, [
            m::mock(ConnectionInterface::class),
            new Grammar,
            m::mock(Processor::class),
        ])->makePartial();
    }
}

<?php

namespace Illuminate\Tests\Database\MySql;

use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseQueryBuilderTest extends TestCase
{
    public function testBasicSelectUseWritePdo()
    {
        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with('select * from `users`', [], false);
        $builder->useWritePdo()->select('*')->from('users')->get();

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with('select * from `users`', [], true);
        $builder->select('*')->from('users')->get();
    }

    public function testWrappingProtectsQuotationMarks()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->From('some`table');
        $this->assertSame('select * from `some``table`', $builder->toSql());
    }

    public function testDateBasedWheresAcceptsTwoArguments()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', 1);
        $this->assertSame('select * from `users` where date(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', 1);
        $this->assertSame('select * from `users` where day(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', 1);
        $this->assertSame('select * from `users` where month(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', 1);
        $this->assertSame('select * from `users` where year(`created_at`) = ?', $builder->toSql());
    }

    public function testDateBasedOrWheresAcceptsTwoArguments()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDate('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or date(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereDay('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or day(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereMonth('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or month(`created_at`) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', 1)->orWhereYear('created_at', 1);
        $this->assertSame('select * from `users` where `id` = ? or year(`created_at`) = ?', $builder->toSql());
    }

    public function testWhereDate()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from `users` where date(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', new Raw('NOW()'));
        $this->assertSame('select * from `users` where date(`created_at`) = NOW()', $builder->toSql());
    }

    public function testWhereDay()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from `users` where day(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testOrWhereDay()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1)->orWhereDay('created_at', '=', 2);
        $this->assertSame('select * from `users` where day(`created_at`) = ? or day(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from `users` where month(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testOrWhereMonth()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5)->orWhereMonth('created_at', '=', 6);
        $this->assertSame('select * from `users` where month(`created_at`) = ? or month(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 5, 1 => 6], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from `users` where year(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testOrWhereYear()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014)->orWhereYear('created_at', '=', 2015);
        $this->assertSame('select * from `users` where year(`created_at`) = ? or year(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014, 1 => 2015], $builder->getBindings());
    }

    public function testWhereTime()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from `users` where time(`created_at`) >= ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeOperatorOptional()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from `users` where time(`created_at`) = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereFulltext()
    {
        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World');
        $this->assertSame('select * from `users` where match (`body`) against (? in natural language mode)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World', ['expanded' => true]);
        $this->assertSame('select * from `users` where match (`body`) against (? in natural language mode with query expansion)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', '+Hello -World', ['mode' => 'boolean']);
        $this->assertSame('select * from `users` where match (`body`) against (? in boolean mode)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', '+Hello -World', ['mode' => 'boolean', 'expanded' => true]);
        $this->assertSame('select * from `users` where match (`body`) against (? in boolean mode)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext(['body', 'title'], 'Car,Plane');
        $this->assertSame('select * from `users` where match (`body`, `title`) against (? in natural language mode)', $builder->toSql());
        $this->assertEquals(['Car,Plane'], $builder->getBindings());
    }

    public function testUnions()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getMySQLBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertSame('(select * from `users` where `id` = ?) union (select * from `users` where `id` = ?)', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $expectedSql = '(select `a` from `t1` where `a` = ? and `b` = ?) union (select `a` from `t2` where `a` = ? and `b` = ?) order by `a` asc limit 10';
        $union = $this->getMySQLBuilder()->select('a')->from('t2')->where('a', 11)->where('b', 2);
        $builder->select('a')->from('t1')->where('a', 10)->where('b', 1)->union($union)->orderBy('a')->limit(10);
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 10, 1 => 1, 2 => 11, 3 => 2], $builder->getBindings());
    }

    public function testJsonWhereNull()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereNull('items->id');
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is null OR json_type(json_extract(`items`, \'$."id"\')) = \'NULL\')', $builder->toSql());
    }

    public function testJsonWhereNotNull()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereNotNull('items->id');
        $this->assertSame('select * from `users` where (json_extract(`items`, \'$."id"\') is not null AND json_type(json_extract(`items`, \'$."id"\')) != \'NULL\')', $builder->toSql());
    }

    public function testUnionOrderBys()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->union($this->getMySQLBuilder()->select('*')->from('users')->where('id', '=', 2));
        $builder->orderBy('id', 'desc');
        $this->assertSame('(select * from `users` where `id` = ?) union (select * from `users` where `id` = ?) order by `id` desc', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionLimitsAndOffsets()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getMySQLBuilder()->select('*')->from('dogs'));
        $builder->skip(5)->take(10);
        $this->assertSame('(select * from `users`) union (select * from `dogs`) limit 10 offset 5', $builder->toSql());
    }

    public function testInsertOrIgnoreMethod()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert ignore into `users` (`email`) values (?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testInsertGetIdWithEmptyValues()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into `users` () values ()', [], null);
        $builder->from('users')->insertGetId([]);
    }

    public function testUpdateMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `email` = ?, `name` = ? where `id` = ? order by `foo` desc limit 5', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->orderBy('foo', 'desc')->limit(5)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpsertMethod()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `email` = values(`email`), `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) as laravel_upsert_alias on duplicate key update `email` = `laravel_upsert_alias`.`email`, `name` = `laravel_upsert_alias`.`name`', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);
    }

    public function testUpsertMethodWithUpdateColumns()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) as laravel_upsert_alias on duplicate key update `name` = `laravel_upsert_alias`.`name`', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);
    }

    public function testUpdateMethodWithJoinsOn()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` inner join `orders` on `users`.`id` = `orders`.`user_id` set `email` = ?, `name` = ? where `users`.`id` = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` inner join `orders` on `users`.`id` = `orders`.`user_id` and `users`.`id` = ? set `email` = ?, `name` = ?', [1, 'foo', 'bar'])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testDeleteMethod()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from `users` where `email` = ? order by `id` asc limit 1', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();
        $this->assertEquals(1, $result);
    }

    public function testDeleteWithJoinMethod()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `users` from `users` inner join `contacts` on `users`.`id` = `contacts`.`id` where `email` = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `a` from `users` as `a` inner join `users` as `b` on `a`.`id` = `b`.`user_id` where `email` = ?', ['foo'])->andReturn(1);
        $result = $builder->from('users AS a')->join('users AS b', 'a.id', '=', 'b.user_id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete `users` from `users` inner join `contacts` on `users`.`id` = `contacts`.`id` where `users`.`id` = ?', [1])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->orderBy('id')->take(1)->delete(1);
        $this->assertEquals(1, $result);
    }

    public function testPreservedAreAppliedByUpsert()
    {
        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(false)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`) values (?) on duplicate key update `email` = values(`email`)', ['foo']);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->upsert(['email' => 'foo'], 'id');

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()
            ->shouldReceive('getConfig')->with('use_upsert_alias')->andReturn(true)
            ->shouldReceive('affectingStatement')->once()->with('insert into `users` (`email`) values (?) as laravel_upsert_alias on duplicate key update `email` = `laravel_upsert_alias`.`email`', ['foo']);
        $builder->beforeQuery(function ($builder) {
            $builder->from('users');
        });
        $builder->upsert(['email' => 'foo'], 'id');
    }

    public function testUpdateWrappingJson()
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

    public function testUpdateWrappingNestedJson()
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

    public function testUpdateWrappingJsonArray()
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

    public function testUpdateWrappingJsonPathArrayIndex()
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

    public function testWrapping()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users');
        $this->assertSame('select * from `users`', $builder->toSql());
    }

    public function testUpdateWithJsonPreparesBindingsCorrectly()
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

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `options` = json_set(`options`, \'$."size"\', ?)', [null]);
        $builder->from('users')->update(['options->size' => null]);

        $builder = $this->getMySQLBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update `users` set `options` = json_set(`options`, \'$."size"\', 45)', []);
        $builder->from('users')->update(['options->size' => new Raw('45')]);
    }

    public function testWrappingJsonWithString()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->sku', '=', 'foo-bar');
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."sku"\')) = ?', $builder->toSql());
        $this->assertCount(1, $builder->getRawBindings()['where']);
        $this->assertSame('foo-bar', $builder->getRawBindings()['where'][0]);
    }

    public function testWrappingJsonWithInteger()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->price', '=', 1);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"\')) = ?', $builder->toSql());
    }

    public function testWrappingJsonWithDouble()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->price', '=', 1.5);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"\')) = ?', $builder->toSql());
    }

    public function testWrappingJsonWithBoolean()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from `users` where json_extract(`items`, \'$."available"\') = true', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where(new Raw("items->'$.available'"), '=', true);
        $this->assertSame("select * from `users` where items->'$.available' = true", $builder->toSql());
    }

    public function testWrappingJsonWithBooleanAndIntegerThatLooksLikeOne()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true)->where('items->active', '=', false)->where('items->number_available', '=', 0);
        $this->assertSame('select * from `users` where json_extract(`items`, \'$."available"\') = true and json_extract(`items`, \'$."active"\') = false and json_unquote(json_extract(`items`, \'$."number_available"\')) = ?', $builder->toSql());
    }

    public function testJsonPathEscaping()
    {
        $expectedWithJsonEscaped = <<<'SQL'
select json_unquote(json_extract(`json`, '$."''))#"'))
SQL;

        $builder = $this->getMySQLBuilder();
        $builder->select("json->'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select("json->\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select("json->\\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select("json->\\\'))#");
        $this->assertEquals($expectedWithJsonEscaped, $builder->toSql());
    }

    public function testWrappingJson()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereRaw('items->\'$."price"\' = 1');
        $this->assertSame('select * from `users` where items->\'$."price"\' = 1', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select json_unquote(json_extract(`items`, \'$."price"\')) from `users` where json_unquote(json_extract(`users`.`items`, \'$."price"\')) = ? order by json_unquote(json_extract(`items`, \'$."price"\')) asc', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"."in_usd"\')) = ?', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from `users` where json_unquote(json_extract(`items`, \'$."price"."in_usd"\')) = ? and json_unquote(json_extract(`items`, \'$."age"\')) = ?', $builder->toSql());
    }

    public function testSoundsLikeOperator()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('name', 'sounds like', 'John Doe');
        $this->assertSame('select * from `users` where `name` sounds like ?', $builder->toSql());
        $this->assertEquals(['John Doe'], $builder->getBindings());
    }

    public function testLock()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock();
        $this->assertSame('select * from `foo` where `bar` = ? for update', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false);
        $this->assertSame('select * from `foo` where `bar` = ? lock in share mode', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock('lock in share mode');
        $this->assertSame('select * from `foo` where `bar` = ? lock in share mode', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testSelectWithLockUsesWritePdo()
    {
        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with(m::any(), m::any(), false);
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock()->get();

        $builder = $this->getMySQLBuilderWithProcessor();
        $builder->getConnection()->shouldReceive('select')->once()
            ->with(m::any(), m::any(), false);
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false)->get();
    }

    public function testWhereJsonContains()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', ['en']);
        $this->assertSame('select * from `users` where json_contains(`options`, ?)', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->languages', ['en']);
        $this->assertSame('select * from `users` where json_contains(`users`.`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContains('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or json_contains(`options`, \'["en"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntContain()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', ['en']);
        $this->assertSame('select * from `users` where not json_contains(`options`, ?, \'$."languages"\')', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContain('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from `users` where `id` = ? or not json_contains(`options`, \'["en"]\', \'$."languages"\')', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonContainsKey()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`users`.`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`options`, \'one\', \'$."language"."primary"\'), 0)', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from `users` where `id` = ? or ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from `users` where ifnull(json_contains_path(`options`, \'one\', \'$."languages"[0][1]\'), 0)', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKey()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from `users` where not ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from `users` where `id` = ? or not ifnull(json_contains_path(`options`, \'one\', \'$."languages"\'), 0)', $builder->toSql());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from `users` where not ifnull(json_contains_path(`options`, \'one\', \'$."languages"[0][1]\'), 0)', $builder->toSql());
    }

    public function testWhereJsonLength()
    {
        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from `users` where json_length(`options`) = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from `users` where json_length(`users`.`options`, \'$."languages"\') > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from `users` where `id` = ? or json_length(`options`, \'$."languages"\') = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getMySQLBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from `users` where `id` = ? or json_length(`options`, \'$."languages"\') > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
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

    protected function getMySQLBuilder()
    {
        $grammar = new MySqlGrammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }

    protected function getMySQLBuilderWithProcessor()
    {
        $grammar = new MySqlGrammar;
        $processor = new MySqlProcessor;

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }
}

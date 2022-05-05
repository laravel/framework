<?php

namespace Illuminate\Tests\Database\SqlServer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseQueryBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testWhereTime()
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

    public function testWhereDate()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from [users] where cast([created_at] as date) = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from [users] where cast([created_at] as date) = NOW()', $builder->toSql());
    }

    public function testWhereDay()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from [users] where day([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from [users] where month([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from [users] where year([created_at]) = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testUnions()
    {
        $builder = $this->getSqlServerBuilder();
        $expectedSql = 'select * from (select [name] from [users] where [id] = ?) as [temp_table] union select * from (select [name] from [users] where [id] = ?) as [temp_table]';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getSqlServerBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionAggregate()
    {
        $expected = 'select count(*) as aggregate from (select * from (select * from [posts]) as [temp_table] union select * from (select * from [videos]) as [temp_table]) as [temp_table]';
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getSqlServerBuilder()->from('videos'))->count();
    }

    public function testOrderBys()
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

    public function testExists()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with('select top 1 1 [exists] from [users]', [], true)->andReturn([['exists' => 1]]);
        $results = $builder->from('users')->exists();
        $this->assertTrue($results);
    }

    public function testInsertOrIgnoreMethod()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not support');
        $builder = $this->getSqlServerBuilder();
        $builder->from('users')->insertOrIgnore(['email' => 'foo']);
    }

    public function testInsertGetIdWithEmptyValues()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into [users] default values', [], null);
        $builder->from('users')->insertGetId([]);
    }

    public function testUpsertMethod()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [email] = [laravel_source].[email], [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name]);', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);
    }

    public function testUpsertMethodWithUpdateColumns()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name]);', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);
    }

    public function testUpdateMethodWithJoinsOn()
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

    public function testUpdateMethodWithJoinsAndAliasesOn()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update [u] set [email] = ?, [name] = ? from [users] as [u] inner join [orders] on [u].[id] = [orders].[user_id] where [u].[id] = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users as u')->join('orders', 'u.id', '=', 'orders.user_id')->where('u.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testDeleteMethod()
    {
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
    }

    public function testWrappingJson()
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

    public function testLimitsAndOffsets()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->take(10);
        $this->assertSame('select top 10 * from [users]', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(10);
        $this->assertSame('select * from (select *, row_number() over (order by (select 0)) as row_num from [users]) as temp_table where row_num >= 11 order by row_num', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(10)->take(10);
        $this->assertSame('select * from (select *, row_number() over (order by (select 0)) as row_num from [users]) as temp_table where row_num between 11 and 20 order by row_num', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->skip(11)->take(10)->orderBy('email', 'desc');
        $this->assertSame('select * from [users] order by [email] desc offset 11 rows fetch next 10 rows only', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $subQueryBuilder = $this->getSqlServerBuilder();
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

    public function testBitwiseOperators()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->where('bar', '&', 1);
        $this->assertSame('select * from [users] where ([bar] & ?) != 0', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users')->having('bar', '&', 1);
        $this->assertSame('select * from [users] having ([bar] & ?) != 0', $builder->toSql());
    }

    public function testLock()
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

    public function testTableValuedFunctionAsTableIn()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users()');
        $this->assertSame('select * from [users]()', $builder->toSql());

        $builder = $this->getSqlServerBuilder();
        $builder->select('*')->from('users(1,2)');
        $this->assertSame('select * from [users](1,2)', $builder->toSql());
    }

    public function testWhereJsonContains()
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

    public function testWhereJsonDoesntContain()
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

    public function testWhereJsonContainsKey()
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

    public function testWhereJsonDoesntContainKey()
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

    public function testWhereJsonLength()
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

    public function testFromRawOn()
    {
        $builder = $this->getSqlServerBuilder();
        $builder->fromRaw('dbo.[SomeNameWithRoundBrackets (test)]');
        $this->assertSame('select * from dbo.[SomeNameWithRoundBrackets (test)]', $builder->toSql());
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

    protected function getSqlServerBuilder()
    {
        $grammar = new SqlServerGrammar;
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }
}

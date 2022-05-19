<?php

namespace Illuminate\Tests\Database\SQLite;

use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
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

    public function testTruncateMethod()
    {
        $sqlite = new SQLiteGrammar;
        $builder = $this->getBuilder();
        $builder->from('users');
        $this->assertEquals([
            'delete from sqlite_sequence where name = ?' => ['users'],
            'delete from "users"' => [],
        ], $sqlite->compileTruncate($builder));
    }

    public function testWhereDate()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from "users" where strftime(\'%Y-%m-%d\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from "users" where strftime(\'%Y-%m-%d\', "created_at") = cast(NOW() as text)', $builder->toSql());
    }

    public function testWhereDay()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from "users" where strftime(\'%d\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from "users" where strftime(\'%m\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from "users" where strftime(\'%Y\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereTime()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where strftime(\'%H:%M:%S\', "created_at") >= cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereTimeOperatorOptional()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from "users" where strftime(\'%H:%M:%S\', "created_at") = cast(? as text)', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testUnions()
    {
        $builder = $this->getBuilder();
        $expectedSql = 'select * from (select "name" from "users" where "id" = ?) union select * from (select "name" from "users" where "id" = ?)';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionAggregate()
    {
        $expected = 'select count(*) as aggregate from (select * from (select * from "posts") union select * from (select * from "videos")) as "temp_table"';
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getBuilder()->from('videos'))->count();
    }

    public function testInsertOrIgnoreMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert or ignore into "users" ("email") values (?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testInsertGetIdWithEmptyValues()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" default values', [], null);
        $builder->from('users')->insertGetId([]);
    }

    public function testUpsertMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "email" = "excluded"."email", "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);
    }

    public function testUpsertMethodWithUpdateColumns()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])->andReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);
    }

    public function testUpdateMethodWithJoinsOn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" where "users"."id" > ? order by "id" asc limit 3)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('users.id', '>', 1)->limit(3)->oldest('id')->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" where "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "rowid" in (select "users"."rowid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" as "u" set "email" = ?, "name" = ? where "rowid" in (select "u"."rowid" from "users" as "u" inner join "orders" as "o" on "u"."id" = "o"."user_id")', ['foo', 'bar'])->andReturn(1);
        $result = $builder->from('users as u')->join('orders as o', 'u.id', '=', 'o.user_id')->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testDeleteMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "rowid" in (select "users"."rowid" from "users" where "email" = ? order by "id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();
        $this->assertEquals(1, $result);
    }

    public function testDeleteWithJoinMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "rowid" in (select "users"."rowid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."email" = ? order by "users"."id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('users.email', '=', 'foo')->orderBy('users.id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" as "u" where "rowid" in (select "u"."rowid" from "users" as "u" inner join "contacts" as "c" on "u"."id" = "c"."id")', [])->andReturn(1);
        $result = $builder->from('users as u')->join('contacts as c', 'u.id', '=', 'c.id')->delete();
        $this->assertEquals(1, $result);
    }

    public function testUpdateWrappingJsonArray()
    {
        $builder = $this->getBuilder();

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

    public function testUpdateWrappingNestedJsonArray()
    {
        $builder = $this->getBuilder();
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

    public function testUpdateWrappingJsonPathArrayIndex()
    {
        $builder = $this->getBuilder();
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

    public function testWrappingJson()
    {
        $builder = $this->getBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select json_extract("items", \'$."price"\') from "users" where json_extract("users"."items", \'$."price"\') = ? order by json_extract("items", \'$."price"\') asc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from "users" where json_extract("items", \'$."price"."in_usd"\') = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where json_extract("items", \'$."price"."in_usd"\') = ? and json_extract("items", \'$."age"\') = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from "users" where json_extract("items", \'$."available"\') = true', $builder->toSql());
    }

    public function testOrderBy()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('email', 'desc');
        $this->assertSame('select * from "users" order by "email" desc', $builder->toSql());
    }

    public function testWhereJsonContains()
    {
        $this->expectException(RuntimeException::class);

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options->languages', ['en'])->toSql();
    }

    public function testWhereJsonDoesntContain()
    {
        $this->expectException(RuntimeException::class);

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', ['en'])->toSql();
    }

    public function testWhereJsonContainsKey()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from "users" where json_type("users"."options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from "users" where json_type("options", \'$."language"."primary"\') is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where json_type("options", \'$."languages"[0][1]\') is not null', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKey()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where not json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or not json_type("options", \'$."languages"\') is not null', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where "id" = ? or not json_type("options", \'$."languages"[0][1]\') is not null', $builder->toSql());
    }

    public function testWhereJsonLength()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from "users" where json_array_length("options") = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from "users" where json_array_length("users"."options", \'$."languages"\') > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or json_array_length("options", \'$."languages"\') = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or json_array_length("options", \'$."languages"\') > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    protected function getBuilder()
    {
        $grammar = new SQLiteGrammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }
}

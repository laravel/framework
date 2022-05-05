<?php

namespace Illuminate\Tests\Database\Postgres;

use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Query\Processors\Processor;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseQueryBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicSelectDistinctOnColumns()
    {
        $builder = $this->getBuilder();
        $builder->distinct('foo')->select('foo', 'bar')->from('users');
        $this->assertSame('select distinct on ("foo") "foo", "bar" from "users"', $builder->toSql());
    }

    public function testUnions()
    {
        $builder = $this->getBuilder();
        $expectedSql = '(select "name" from "users" where "id" = ?) union (select "name" from "users" where "id" = ?)';
        $builder->select('name')->from('users')->where('id', '=', 1);
        $builder->union($this->getBuilder()->select('name')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionAlls()
    {
        $expectedSql = '(select * from "users" where "id" = ?) union all (select * from "users" where "id" = ?)';
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1);
        $builder->unionAll($this->getBuilder()->select('*')->from('users')->where('id', '=', 2));
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2], $builder->getBindings());
    }

    public function testUnionAggregate()
    {
        $expected = 'select count(*) as aggregate from ((select * from "posts") union (select * from "videos")) as "temp_table"';
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with($expected, [], true);
        $builder->getProcessor()->shouldReceive('processSelect')->once();
        $builder->from('posts')->union($this->getBuilder()->from('videos'))->count();
    }

    public function testUnionLimitsAndOffsets()
    {
        $expectedSql = '(select * from "users") union (select * from "dogs") limit 10 offset 5';
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $builder->union($this->getBuilder()->select('*')->from('dogs'));
        $builder->skip(5)->take(10);
        $this->assertEquals($expectedSql, $builder->toSql());

        $expectedSql = '(select * from "users" limit 11) union (select * from "dogs" limit 22) limit 10 offset 5';
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->limit(11);
        $builder->union($this->getBuilder()->select('*')->from('dogs')->limit(22));
        $builder->skip(5)->take(10);
        $this->assertEquals($expectedSql, $builder->toSql());
    }

    public function testInsertOrIgnoreMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('affectingStatement')->once()->with('insert into "users" ("email") values (?) on conflict do nothing', ['foo'])->andReturn(1);
        $result = $builder->from('users')->insertOrIgnore(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testInsertGetIdWithEmptyValues()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" default values returning "id"', [], null);
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

    public function testUpdateMethodWithoutJoinsOn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->update(['users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->where('id', '=', 1)->selectRaw('?', ['ignore'])->update(['users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users"."users" set "email" = ?, "name" = ? where "id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users.users')->where('id', '=', 1)->selectRaw('?', ['ignore'])->update(['users.users.email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateMethodWithJoinsOn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" where "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ?)', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "ctid" in (select "users"."ctid" from "users" inner join "orders" on "users"."id" = "orders"."user_id" and "users"."id" = ? where "name" = ?)', ['foo', 'bar', 1, 'baz'])->andReturn(1);
        $result = $builder->from('users')
            ->join('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('users.id', '=', 1);
            })->where('name', 'baz')
            ->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateFromMethodWithJoinsOn()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "users"."id" = ? and "users"."id" = "orders"."user_id"', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', 'users.id', '=', 'orders.user_id')->where('users.id', '=', 1)->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "users"."id" = "orders"."user_id" and "users"."id" = ?', ['foo', 'bar', 1])->andReturn(1);
        $result = $builder->from('users')->join('orders', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('users.id', '=', 1);
        })->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? from "orders" where "name" = ? and "users"."id" = "orders"."user_id" and "users"."id" = ?', ['foo', 'bar', 'baz', 1])->andReturn(1);
        $result = $builder->from('users')
            ->join('orders', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                   ->where('users.id', '=', 1);
            })->where('name', 'baz')
           ->updateFrom(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWrappingJson()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = jsonb_set("options"::jsonb, \'{"name","first_name"}\', ?)', ['"John"']);
        $builder->from('users')->update(['users.options->name->first_name' => 'John']);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')
            ->with('update "users" set "options" = jsonb_set("options"::jsonb, \'{"language"}\', \'null\')', []);
        $builder->from('users')->update(['options->language' => new Raw("'null'")]);
    }

    public function testUpdateWrappingJsonArray()
    {
        $builder = $this->getBuilder();
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

    public function testUpdateWrappingJsonPathArrayIndex()
    {
        $builder = $this->getBuilder();
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

    public function testInsertGetId()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?) returning "id"', ['foo'], 'id')->andReturn(1);
        $result = $builder->from('users')->insertGetId(['email' => 'foo'], 'id');
        $this->assertEquals(1, $result);
    }

    public function testDeleteWithJoinMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."email" = ?)', ['foo'])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('users.email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" as "a" where "ctid" in (select "a"."ctid" from "users" as "a" inner join "users" as "b" on "a"."id" = "b"."user_id" where "email" = ? order by "id" asc limit 1)', ['foo'])->andReturn(1);
        $result = $builder->from('users AS a')->join('users AS b', 'a.id', '=', 'b.user_id')->where('email', '=', 'foo')->orderBy('id')->limit(1)->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id" where "users"."id" = ? order by "id" asc limit 1)', [1])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->orderBy('id')->take(1)->delete(1);
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."user_id" and "users"."id" = ? where "name" = ?)', [1, 'baz'])->andReturn(1);
        $result = $builder->from('users')
            ->join('contacts', function ($join) {
                $join->on('users.id', '=', 'contacts.user_id')
                    ->where('users.id', '=', 1);
            })->where('name', 'baz')
            ->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "ctid" in (select "users"."ctid" from "users" inner join "contacts" on "users"."id" = "contacts"."id")', [])->andReturn(1);
        $result = $builder->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->delete();
        $this->assertEquals(1, $result);
    }

    public function testWrappingJson()
    {
        $builder = $this->getBuilder();
        $builder->select('items->price')->from('users')->where('users.items->price', '=', 1)->orderBy('items->price');
        $this->assertSame('select "items"->>\'price\' from "users" where "users"."items"->>\'price\' = ? order by "items"->>\'price\' asc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1);
        $this->assertSame('select * from "users" where "items"->\'price\'->>\'in_usd\' = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->price->in_usd', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where "items"->\'price\'->>\'in_usd\' = ? and "items"->>\'age\' = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->prices->0', '=', 1)->where('items->age', '=', 2);
        $this->assertSame('select * from "users" where "items"->\'prices\'->>0 = ? and "items"->>\'age\' = ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('items->available', '=', true);
        $this->assertSame('select * from "users" where ("items"->\'available\')::jsonb = \'true\'::jsonb', $builder->toSql());
    }

    public function testBitwiseOperators()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('bar', '#', 1);
        $this->assertSame('select * from "users" where ("bar" # ?)::bool', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('range', '>>', '[2022-01-08 00:00:00,2022-01-09 00:00:00)');
        $this->assertSame('select * from "users" where ("range" >> ?)::bool', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('bar', '#', 1);
        $this->assertSame('select * from "users" having ("bar" # ?)::bool', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->having('range', '>>', '[2022-01-08 00:00:00,2022-01-09 00:00:00)');
        $this->assertSame('select * from "users" having ("range" >> ?)::bool', $builder->toSql());
    }

    public function testWhereTimeOperatorOptional()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereDate()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from "users" where "created_at"::date = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from "users" where "created_at"::date = NOW()', $builder->toSql());
    }

    public function testWhereDay()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from "users" where extract(day from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from "users" where extract(month from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from "users" where extract(year from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereTime()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time >= ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereLike()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 'like', '1');
        $this->assertSame('select * from "users" where "id"::text like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 'LIKE', '1');
        $this->assertSame('select * from "users" where "id"::text LIKE ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 'ilike', '1');
        $this->assertSame('select * from "users" where "id"::text ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 'not like', '1');
        $this->assertSame('select * from "users" where "id"::text not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', 'not ilike', '1');
        $this->assertSame('select * from "users" where "id"::text not ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testWhereFulltext()
    {
        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World');
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World', ['language' => 'simple']);
        $this->assertSame('select * from "users" where (to_tsvector(\'simple\', "body")) @@ plainto_tsquery(\'simple\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World', ['mode' => 'plain']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World', ['mode' => 'phrase']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ phraseto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', '+Hello -World', ['mode' => 'websearch']);
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body")) @@ websearch_to_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['+Hello -World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext('body', 'Hello World', ['language' => 'simple', 'mode' => 'plain']);
        $this->assertSame('select * from "users" where (to_tsvector(\'simple\', "body")) @@ plainto_tsquery(\'simple\', ?)', $builder->toSql());
        $this->assertEquals(['Hello World'], $builder->getBindings());

        $builder = $this->getBuilderWithProcessor();
        $builder->select('*')->from('users')->whereFulltext(['body', 'title'], 'Car Plane');
        $this->assertSame('select * from "users" where (to_tsvector(\'english\', "body") || to_tsvector(\'english\', "title")) @@ plainto_tsquery(\'english\', ?)', $builder->toSql());
        $this->assertEquals(['Car Plane'], $builder->getBindings());
    }

    public function testWhereJsonContains()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContains('options', ['en']);
        $this->assertSame('select * from "users" where ("options")::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContains('users.options->languages', ['en']);
        $this->assertSame('select * from "users" where ("users"."options"->\'languages\')::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContains('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from "users" where "id" = ? or ("options"->\'languages\')::jsonb @> \'["en"]\'', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonDoesntContain()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContain('options->languages', ['en']);
        $this->assertSame('select * from "users" where not ("options"->\'languages\')::jsonb @> ?', $builder->toSql());
        $this->assertEquals(['["en"]'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContain('options->languages', new Raw("'[\"en\"]'"));
        $this->assertSame('select * from "users" where "id" = ? or not ("options"->\'languages\')::jsonb @> \'["en"]\'', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testWhereJsonContainsKey()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('users.options->languages');
        $this->assertSame('select * from "users" where coalesce(("users"."options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->language->primary');
        $this->assertSame('select * from "users" where coalesce(("options"->\'language\')::jsonb ?? \'primary\', false)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonContainsKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where case when jsonb_typeof(("options"->\'languages\'->0)::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\'->0)::jsonb) >= 2 else false end', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonContainsKey('options->languages[-1]');
        $this->assertSame('select * from "users" where case when jsonb_typeof(("options"->\'languages\')::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\')::jsonb) >= 1 else false end', $builder->toSql());
    }

    public function testWhereJsonDoesntContainKey()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where not coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonDoesntContainKey('options->languages');
        $this->assertSame('select * from "users" where "id" = ? or not coalesce(("options")::jsonb ?? \'languages\', false)', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[0][1]');
        $this->assertSame('select * from "users" where not case when jsonb_typeof(("options"->\'languages\'->0)::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\'->0)::jsonb) >= 2 else false end', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonDoesntContainKey('options->languages[-1]');
        $this->assertSame('select * from "users" where not case when jsonb_typeof(("options"->\'languages\')::jsonb) = \'array\' then jsonb_array_length(("options"->\'languages\')::jsonb) >= 1 else false end', $builder->toSql());
    }

    public function testWhereJsonLength()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonLength('options', 0);
        $this->assertSame('select * from "users" where jsonb_array_length(("options")::jsonb) = ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereJsonLength('users.options->languages', '>', 0);
        $this->assertSame('select * from "users" where jsonb_array_length(("users"."options"->\'languages\')::jsonb) > ?', $builder->toSql());
        $this->assertEquals([0], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or jsonb_array_length(("options"->\'languages\')::jsonb) = 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('id', '=', 1)->orWhereJsonLength('options->languages', '>', new Raw('0'));
        $this->assertSame('select * from "users" where "id" = ? or jsonb_array_length(("options"->\'languages\')::jsonb) > 0', $builder->toSql());
        $this->assertEquals([1], $builder->getBindings());
    }

    public function testFromQuestionMarkOperatorOn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('roles', '?', 'superuser');
        $this->assertSame('select * from "users" where "roles" ?? ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('roles', '?|', 'superuser');
        $this->assertSame('select * from "users" where "roles" ??| ?', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('roles', '?&', 'superuser');
        $this->assertSame('select * from "users" where "roles" ??& ?', $builder->toSql());
    }

    public function testLock()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock();
        $this->assertSame('select * from "foo" where "bar" = ? for update', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock(false);
        $this->assertSame('select * from "foo" where "bar" = ? for share', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('foo')->where('bar', '=', 'baz')->lock('for key share');
        $this->assertSame('select * from "foo" where "bar" = ? for key share', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testSubSelect()
    {
        $expectedSql = 'select "foo", "bar", (select "baz" from "two" where "subkey" = ?) as "sub" from "one" where "key" = ?';
        $expectedBindings = ['subval', 'val'];

        $builder = $this->getBuilder();
        $builder->from('one')->select(['foo', 'bar'])->where('key', '=', 'val');
        $builder->selectSub(function ($query) {
            $query->from('two')->select('baz')->where('subkey', '=', 'subval');
        }, 'sub');
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->from('one')->select(['foo', 'bar'])->where('key', '=', 'val');
        $subBuilder = $this->getBuilder();
        $subBuilder->from('two')->select('baz')->where('subkey', '=', 'subval');
        $builder->selectSub($subBuilder, 'sub');
        $this->assertEquals($expectedSql, $builder->toSql());
        $this->assertEquals($expectedBindings, $builder->getBindings());

        $this->expectException(InvalidArgumentException::class);
        $builder = $this->getBuilder();
        $builder->selectSub(['foo'], 'sub');
    }

    public function testSubSelectResetBindings()
    {
        $builder = $this->getBuilder();
        $builder->from('one')->selectSub(function ($query) {
            $query->from('two')->select('baz')->where('subkey', '=', 'subval');
        }, 'sub');

        $this->assertSame('select (select "baz" from "two" where "subkey" = ?) as "sub" from "one"', $builder->toSql());
        $this->assertEquals(['subval'], $builder->getBindings());

        $builder->select('*');

        $this->assertSame('select * from "one"', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());
    }

    protected function getConnection()
    {
        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        return $connection;
    }

    protected function getBuilder()
    {
        $grammar = new PostgresGrammar;
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getBuilderWithProcessor()
    {
        $grammar = new PostgresGrammar;
        $processor = new PostgresProcessor;

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }
}

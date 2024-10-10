<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;

class SchemaBuilderTest extends DatabaseTestCase
{
    protected function destroyDatabaseMigrations()
    {
        Schema::dropAllViews();
    }

    public function testDropAllTables()
    {
        $this->expectNotToPerformAssertions();

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    public function testDropAllViews()
    {
        $this->expectNotToPerformAssertions();

        DB::statement('create view foo (id) as select 1');

        Schema::dropAllViews();

        DB::statement('create view foo (id) as select 1');
    }

    #[RequiresDatabase('sqlite')]
    public function testChangeToTinyInteger()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->string('test_column');
        });

        $blueprint = new Blueprint('test', function (Blueprint $table) {
            $table->tinyInteger('test_column')->change();
        });

        $blueprint->build($this->getConnection(), new SQLiteGrammar);

        $this->assertSame('integer', Schema::getColumnType('test', 'test_column'));
    }

    #[RequiresDatabase(['mysql', 'mariadb'])]
    public function testChangeToTextColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->integer('test_column');
        });

        foreach (['tinyText', 'text', 'mediumText', 'longText'] as $type) {
            $blueprint = new Blueprint('test', function ($table) use ($type) {
                $table->$type('test_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $uppercase = strtolower($type);

            $expected = ["alter table `test` modify `test_column` $uppercase not null"];

            $this->assertEquals($expected, $queries);
        }
    }

    #[RequiresDatabase(['mysql', 'mariadb'])]
    public function testChangeTextColumnToTextColumn()
    {
        Schema::create('test', static function (Blueprint $table) {
            $table->text('test_column');
        });

        foreach (['tinyText', 'mediumText', 'longText'] as $type) {
            $blueprint = new Blueprint('test', function ($table) use ($type) {
                $table->$type('test_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $lowercase = strtolower($type);

            $expected = ["alter table `test` modify `test_column` $lowercase not null"];

            $this->assertEquals($expected, $queries);
        }
    }

    #[RequiresDatabase(['mysql', 'mariadb'])]
    public function testModifyNullableColumn()
    {
        Schema::create('test', static function (Blueprint $table) {
            $table->string('not_null_column_to_not_null');
            $table->string('not_null_column_to_nullable');
            $table->string('nullable_column_to_nullable')->nullable();
            $table->string('nullable_column_to_not_null')->nullable();
        });

        $blueprint = new Blueprint('test', function ($table) {
            $table->text('not_null_column_to_not_null')->change();
            $table->text('not_null_column_to_nullable')->nullable()->change();
            $table->text('nullable_column_to_nullable')->nullable()->change();
            $table->text('nullable_column_to_not_null')->change();
        });

        $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

        $expected = [
            'alter table `test` modify `not_null_column_to_not_null` text not null',
            'alter table `test` modify `not_null_column_to_nullable` text null',
            'alter table `test` modify `nullable_column_to_nullable` text null',
            'alter table `test` modify `nullable_column_to_not_null` text not null',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testChangeNullableColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->string('not_null_column_to_not_null');
            $table->string('not_null_column_to_nullable');
            $table->string('nullable_column_to_nullable')->nullable();
            $table->string('nullable_column_to_not_null')->nullable();
        });

        $columns = collect(Schema::getColumns('test'));

        $this->assertFalse($columns->firstWhere('name', 'not_null_column_to_not_null')['nullable']);
        $this->assertFalse($columns->firstWhere('name', 'not_null_column_to_nullable')['nullable']);
        $this->assertTrue($columns->firstWhere('name', 'nullable_column_to_nullable')['nullable']);
        $this->assertTrue($columns->firstWhere('name', 'nullable_column_to_not_null')['nullable']);

        Schema::table('test', function (Blueprint $table) {
            $table->text('not_null_column_to_not_null')->change();
            $table->text('not_null_column_to_nullable')->nullable()->change();
            $table->text('nullable_column_to_nullable')->nullable()->change();
            $table->text('nullable_column_to_not_null')->change();
        });

        $columns = collect(Schema::getColumns('test'));

        $this->assertFalse($columns->firstWhere('name', 'not_null_column_to_not_null')['nullable']);
        $this->assertTrue($columns->firstWhere('name', 'not_null_column_to_nullable')['nullable']);
        $this->assertTrue($columns->firstWhere('name', 'nullable_column_to_nullable')['nullable']);
        $this->assertFalse($columns->firstWhere('name', 'nullable_column_to_not_null')['nullable']);
    }

    public function testRenameColumnWithDefault()
    {
        Schema::create('test', static function (Blueprint $table) {
            $table->timestamp('foo')->useCurrent();
            $table->string('bar')->default('value');
        });

        $columns = Schema::getColumns('test');
        $defaultFoo = collect($columns)->firstWhere('name', 'foo')['default'];
        $defaultBar = collect($columns)->firstWhere('name', 'bar')['default'];

        Schema::table('test', static function (Blueprint $table) {
            $table->renameColumn('foo', 'new_foo');
            $table->renameColumn('bar', 'new_bar');
        });

        $this->assertEquals(collect(Schema::getColumns('test'))->firstWhere('name', 'new_foo')['default'], $defaultFoo);
        $this->assertEquals(collect(Schema::getColumns('test'))->firstWhere('name', 'new_bar')['default'], $defaultBar);
    }

    #[RequiresDatabase('sqlite')]
    public function testModifyColumnWithZeroDefaultOnSqlite()
    {
        Schema::create('test', static function (Blueprint $table) {
            $table->integer('column_default_zero')->default(new Expression('0'));
            $table->integer('column_to_change');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->smallInteger('column_to_change')->default(new Expression('0'))->change();
        });

        $columns = collect(Schema::getColumns('test'));

        $this->assertSame('0', $columns->firstWhere('name', 'column_default_zero')['default']);
        $this->assertSame('0', $columns->firstWhere('name', 'column_to_change')['default']);
    }

    public function testCompoundPrimaryWithAutoIncrement()
    {
        if ($this->driver === 'sqlite') {
            $this->markTestSkipped('Compound primary key with an auto increment column is not supported on SQLite.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->id();
            $table->uuid();

            $table->primary(['id', 'uuid']);
        });

        $this->assertTrue(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertTrue(Schema::hasIndex('test', ['id', 'uuid'], 'primary'));
    }

    public function testModifyingAutoIncrementColumn()
    {
        if ($this->driver === 'sqlsrv') {
            $this->markTestSkipped('Changing a primary column is not supported on SQL Server.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->increments('id');
        });

        $this->assertTrue(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertTrue(Schema::hasIndex('test', ['id'], 'primary'));

        Schema::table('test', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });

        $this->assertTrue(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertTrue(Schema::hasIndex('test', ['id'], 'primary'));
    }

    public function testModifyingColumnToAutoIncrementColumn()
    {
        if (in_array($this->driver, ['pgsql', 'sqlsrv'])) {
            $this->markTestSkipped('Changing a column to auto increment is not supported on PostgreSQL and SQL Server.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
        });

        $this->assertFalse(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertFalse(Schema::hasIndex('test', ['id'], 'primary'));

        Schema::table('test', function (Blueprint $table) {
            $table->bigIncrements('id')->primary()->change();
        });

        $this->assertTrue(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertTrue(Schema::hasIndex('test', ['id'], 'primary'));
    }

    public function testAddingAutoIncrementColumn()
    {
        if ($this->driver === 'sqlite') {
            $this->markTestSkipped('Adding a primary column is not supported on SQLite.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->string('name');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        $this->assertTrue(collect(Schema::getColumns('test'))->firstWhere('name', 'id')['auto_increment']);
        $this->assertTrue(Schema::hasIndex('test', ['id'], 'primary'));
    }

    public function testGetTables()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->comment('This is a comment');
            $table->increments('id');
        });

        Schema::create('bar', function (Blueprint $table) {
            $table->string('name');
        });

        Schema::create('baz', function (Blueprint $table) {
            $table->integer('votes');
        });

        $tables = Schema::getTables();

        $this->assertEmpty(array_diff(['foo', 'bar', 'baz'], array_column($tables, 'name')));

        if (in_array($this->driver, ['mysql', 'mariadb', 'pgsql'])) {
            $this->assertNotEmpty(array_filter($tables, function ($table) {
                return $table['name'] === 'foo' && $table['comment'] === 'This is a comment';
            }));
        }
    }

    public function testHasView()
    {
        DB::statement('create view foo (id) as select 1');

        $this->assertTrue(Schema::hasView('foo'));
    }

    public function testGetViews()
    {
        DB::statement('create view foo (id) as select 1');
        DB::statement('create view bar (name) as select 1');
        DB::statement('create view baz (votes) as select 1');

        $views = Schema::getViews();

        $this->assertEmpty(array_diff(['foo', 'bar', 'baz'], array_column($views, 'name')));
    }

    #[RequiresDatabase('pgsql')]
    public function testGetAndDropTypes()
    {
        DB::statement('create type pseudo_foo');
        DB::statement('create type comp_foo as (f1 int, f2 text)');
        DB::statement("create type enum_foo as enum ('new', 'open', 'closed')");
        DB::statement('create type range_foo as range (subtype = float8)');
        DB::statement('create domain domain_foo as text');
        DB::statement('create type base_foo');
        DB::statement("create function foo_in(cstring) returns base_foo language internal immutable strict parallel safe as 'int2in'");
        DB::statement("create function foo_out(base_foo) returns cstring language internal immutable strict parallel safe as 'int2out'");
        DB::statement('create type base_foo (input = foo_in, output = foo_out)');

        $types = Schema::getTypes();

        if (version_compare($this->getConnection()->getServerVersion(), '14.0', '<')) {
            $this->assertCount(10, $types);
        } else {
            $this->assertCount(13, $types);
        }

        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'pseudo_foo' && $type['type'] === 'pseudo' && ! $type['implicit']));
        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'comp_foo' && $type['type'] === 'composite' && ! $type['implicit']));
        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'enum_foo' && $type['type'] === 'enum' && ! $type['implicit']));
        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'range_foo' && $type['type'] === 'range' && ! $type['implicit']));
        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'domain_foo' && $type['type'] === 'domain' && ! $type['implicit']));
        $this->assertTrue(collect($types)->contains(fn ($type) => $type['name'] === 'base_foo' && $type['type'] === 'base' && ! $type['implicit']));

        Schema::dropAllTypes();
        $types = Schema::getTypes();

        $this->assertEmpty($types);
    }

    public function testGetColumns()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->id();
            $table->string('bar')->nullable();
            $table->string('baz')->default('test');
        });

        $columns = Schema::getColumns('foo');

        $this->assertCount(3, $columns);
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'id' && $column['auto_increment'] && ! $column['nullable']
        ));
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'bar' && $column['nullable']
        ));
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'baz' && ! $column['nullable'] && str_contains($column['default'], 'test')
        ));
    }

    public function testGetColumnsOnView()
    {
        DB::statement('create view foo (bar) as select 1');

        $columns = Schema::getColumns('foo');

        $this->assertCount(1, $columns);
        $this->assertTrue($columns[0]['name'] === 'bar');
    }

    public function testGetIndexes()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->string('bar')->index('my_index');
        });

        $indexes = Schema::getIndexes('foo');

        $this->assertCount(1, $indexes);
        $this->assertTrue(
            $indexes[0]['name'] === 'my_index'
            && $indexes[0]['columns'] === ['bar']
            && ! $indexes[0]['unique']
            && ! $indexes[0]['primary']
        );
        $this->assertTrue(Schema::hasIndex('foo', 'my_index'));
        $this->assertTrue(Schema::hasIndex('foo', ['bar']));
        $this->assertFalse(Schema::hasIndex('foo', 'my_index', 'primary'));
        $this->assertFalse(Schema::hasIndex('foo', ['bar'], 'unique'));
    }

    public function testGetUniqueIndexes()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->id();
            $table->string('bar');
            $table->integer('baz');

            $table->unique(['baz', 'bar']);
        });

        $indexes = Schema::getIndexes('foo');

        $this->assertCount(2, $indexes);
        $this->assertTrue(collect($indexes)->contains(
            fn ($index) => $index['columns'] === ['id'] && $index['primary']
        ));
        $this->assertTrue(collect($indexes)->contains(
            fn ($index) => $index['name'] === 'foo_baz_bar_unique' && $index['columns'] === ['baz', 'bar'] && $index['unique']
        ));
        $this->assertTrue(Schema::hasIndex('foo', 'foo_baz_bar_unique'));
        $this->assertTrue(Schema::hasIndex('foo', 'foo_baz_bar_unique', 'unique'));
        $this->assertTrue(Schema::hasIndex('foo', ['baz', 'bar']));
        $this->assertTrue(Schema::hasIndex('foo', ['baz', 'bar'], 'unique'));
        $this->assertFalse(Schema::hasIndex('foo', ['baz', 'bar'], 'primary'));
    }

    public function testGetIndexesWithCompositeKeys()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->unsignedBigInteger('key');
            $table->string('bar')->unique();
            $table->integer('baz');

            $table->primary(['baz', 'key']);
        });

        $indexes = Schema::getIndexes('foo');

        $this->assertCount(2, $indexes);
        $this->assertTrue(collect($indexes)->contains(
            fn ($index) => $index['columns'] === ['baz', 'key'] && $index['primary']
        ));
        $this->assertTrue(collect($indexes)->contains(
            fn ($index) => $index['name'] === 'foo_bar_unique' && $index['columns'] === ['bar'] && $index['unique']
        ));
    }

    #[RequiresDatabase(['mysql', 'mariadb', 'pgsql'])]
    public function testGetFullTextIndexes()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body');

            $table->fulltext(['body', 'title']);
        });

        $indexes = Schema::getIndexes('articles');

        $this->assertCount(2, $indexes);
        $this->assertTrue(collect($indexes)->contains(fn ($index) => $index['columns'] === ['id'] && $index['primary']));
        $this->assertTrue(collect($indexes)->contains('name', 'articles_body_title_fulltext'));
    }

    public function testHasIndexOrder()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->integer('bar');
            $table->integer('baz');
            $table->integer('qux');

            $table->unique(['bar', 'baz']);
            $table->index(['baz', 'bar']);
            $table->index(['baz', 'qux']);
        });

        $this->assertTrue(Schema::hasIndex('foo', ['bar', 'baz']));
        $this->assertTrue(Schema::hasIndex('foo', ['bar', 'baz'], 'unique'));
        $this->assertTrue(Schema::hasIndex('foo', ['baz', 'bar']));
        $this->assertFalse(Schema::hasIndex('foo', ['baz', 'bar'], 'unique'));
        $this->assertTrue(Schema::hasIndex('foo', ['baz', 'qux']));
        $this->assertFalse(Schema::hasIndex('foo', ['qux', 'baz']));
    }

    public function testGetForeignKeys()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
        });

        $foreignKeys = Schema::getForeignKeys('posts');

        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(
            fn ($foreign) => $foreign['columns'] === ['user_id']
                && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['id']
                && $foreign['on_update'] === 'cascade' && $foreign['on_delete'] === 'set null'
        ));
    }

    public function testGetCompoundForeignKeys()
    {
        Schema::create('parent', function (Blueprint $table) {
            $table->id();
            $table->integer('a');
            $table->integer('b');

            $table->unique(['b', 'a']);
        });

        Schema::create('child', function (Blueprint $table) {
            $table->integer('c');
            $table->integer('d');

            $table->foreign(['d', 'c'], 'test_fk')->references(['b', 'a'])->on('parent');
        });

        $foreignKeys = Schema::getForeignKeys('child');

        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(
            fn ($foreign) => $foreign['columns'] === ['d', 'c']
                && $foreign['foreign_table'] === 'parent'
                && $foreign['foreign_columns'] === ['b', 'a']
        ));
    }

    public function testAlteringTableWithForeignKeyConstraintsEnabled()
    {
        Schema::enableForeignKeyConstraints();

        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->text('name');
        });

        Schema::create('children', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained();
        });

        $id = DB::table('parents')->insertGetId(['name' => 'foo']);
        DB::table('children')->insert(['parent_id' => $id]);

        Schema::table('parents', function (Blueprint $table) {
            $table->string('name')->change();
        });

        $foreignKeys = Schema::getForeignKeys('children');

        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(
            fn ($foreign) => $foreign['columns'] === ['parent_id']
                && $foreign['foreign_table'] === 'parents' && $foreign['foreign_columns'] === ['id']
        ));
    }

    #[RequiresDatabase('mariadb')]
    public function testSystemVersionedTables()
    {
        DB::statement('create table `test` (`foo` int) WITH system versioning;');

        $this->assertTrue(Schema::hasTable('test'));

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        DB::statement('create table `test` (`foo` int) WITH system versioning;');
    }

    #[RequiresDatabase('sqlite')]
    public function testAddingStoredColumnOnSqlite()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->integer('price');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->integer('virtual_column')->virtualAs('"price" - 5');
            $table->integer('stored_column')->storedAs('"price" - 5');
        });

        $this->assertTrue(Schema::hasColumns('test', ['virtual_column', 'stored_column']));
    }

    #[RequiresDatabase('sqlite')]
    public function testModifyingStoredColumnOnSqlite()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->integer('price');
            $table->integer('virtual_price')->virtualAs('price - 2');
            $table->integer('stored_price')->storedAs('price - 4');
            $table->integer('virtual_price_changed')->virtualAs('price - 6');
            $table->integer('stored_price_changed')->storedAs('price - 8');
        });

        DB::table('test')->insert(['price' => 100]);

        Schema::table('test', function (Blueprint $table) {
            $table->integer('virtual_price_changed')->virtualAs('price - 5')->change();
            $table->integer('stored_price_changed')->storedAs('price - 7')->change();
        });

        $this->assertEquals(
            ['price' => 100, 'virtual_price' => 98, 'stored_price' => 96, 'virtual_price_changed' => 95, 'stored_price_changed' => 93],
            (array) DB::table('test')->first()
        );

        $columns = Schema::getColumns('test');

        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'virtual_price' && $column['generation']['type'] === 'virtual'
                && $column['generation']['expression'] === 'price - 2'
        ));
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'stored_price' && $column['generation']['type'] === 'stored'
                && $column['generation']['expression'] === 'price - 4'
        ));
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'virtual_price_changed' && $column['generation']['type'] === 'virtual'
                && $column['generation']['expression'] === 'price - 5'
        ));
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'stored_price_changed' && $column['generation']['type'] === 'stored'
                && $column['generation']['expression'] === 'price - 7'
        ));
    }

    #[RequiresDatabase('pgsql', '>=12.0')]
    public function testGettingGeneratedColumns()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->integer('price');

            if ($this->driver === 'sqlsrv') {
                $table->computed('virtual_price', 'price - 5');
                $table->computed('stored_price', 'price - 10')->persisted();
            } else {
                if ($this->driver !== 'pgsql') {
                    $table->integer('virtual_price')->virtualAs('price - 5');
                }
                $table->integer('stored_price')->storedAs('price - 10');
            }
        });

        $columns = Schema::getColumns('test');

        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'price' && is_null($column['generation'])
        ));
        if ($this->driver !== 'pgsql') {
            $this->assertTrue(collect($columns)->contains(
                fn ($column) => $column['name'] === 'virtual_price'
                    && $column['generation']['type'] === 'virtual'
                    && match ($this->driver) {
                        'mysql' => $column['generation']['expression'] === '(`price` - 5)',
                        'mariadb' => $column['generation']['expression'] === '`price` - 5',
                        'sqlsrv' => $column['generation']['expression'] === '([price]-(5))',
                        default => $column['generation']['expression'] === 'price - 5',
                    }
            ));
        }
        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'stored_price'
                && $column['generation']['type'] === 'stored'
                && match ($this->driver) {
                    'mysql' => $column['generation']['expression'] === '(`price` - 10)',
                    'mariadb' => $column['generation']['expression'] === '`price` - 10',
                    'sqlsrv' => $column['generation']['expression'] === '([price]-(10))',
                    'pgsql' => $column['generation']['expression'] === '(price - 10)',
                    default => $column['generation']['expression'] === 'price - 10',
                }
        ));
    }

    #[RequiresDatabase('sqlite')]
    public function testAddForeignKeysOnSqlite()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->string('title')->unique();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index()->constrained();
            $table->string('user_name');
            $table->foreign('user_name')->references('name')->on('users');
        });

        $foreignKeys = Schema::getForeignKeys('posts');
        $this->assertCount(2, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_id'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['id']));
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_name'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['name']));
        $this->assertTrue(Schema::hasColumns('posts', ['title', 'user_id', 'user_name']));
        $this->assertTrue(Schema::hasIndex('posts', ['user_id']));
        $this->assertTrue(Schema::hasIndex('posts', ['title'], 'unique'));
    }

    #[RequiresDatabase('sqlite')]
    public function testDropForeignKeysOnSqlite()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained();
            $table->string('user_name')->unique();
            $table->foreign('user_name')->references('name')->on('users');
        });

        $foreignKeys = Schema::getForeignKeys('posts');
        $this->assertCount(2, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_id'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['id']));
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_name'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['name']));
        $this->assertTrue(Schema::hasIndex('posts', ['id'], 'primary'));

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->unique();
            $table->dropIndex(['user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        $foreignKeys = Schema::getForeignKeys('posts');
        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_name'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['name']));
        $this->assertTrue(Schema::hasColumns('posts', ['user_name', 'title']));
        $this->assertTrue(Schema::hasIndex('posts', ['id'], 'primary'));
        $this->assertTrue(Schema::hasIndex('posts', ['title'], 'unique'));
        $this->assertTrue(Schema::hasIndex('posts', ['user_name'], 'unique'));
        $this->assertFalse(Schema::hasColumn('posts', 'user_id'));
        $this->assertFalse(Schema::hasIndex('posts', ['user_id']));
    }

    #[RequiresDatabase('sqlite')]
    public function testAddAndDropPrimaryOnSqlite()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index()->constrained();
            $table->string('user_name')->unique();
            $table->foreign('user_name')->references('name')->on('users');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->primary();
            $table->dropIndex(['user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        $foreignKeys = Schema::getForeignKeys('posts');
        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_name'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['name']));
        $this->assertTrue(Schema::hasColumns('posts', ['user_name', 'title']));
        $this->assertTrue(Schema::hasIndex('posts', ['title'], 'primary'));
        $this->assertTrue(Schema::hasIndex('posts', ['user_name'], 'unique'));
        $this->assertFalse(Schema::hasColumn('posts', 'user_id'));
        $this->assertFalse(Schema::hasIndex('posts', ['user_id']));

        Schema::table('posts', function (Blueprint $table) {
            $table->dropPrimary();
            $table->integer('votes');
        });

        $foreignKeys = Schema::getForeignKeys('posts');
        $this->assertCount(1, $foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreign) => $foreign['columns'] === ['user_name'] && $foreign['foreign_table'] === 'users' && $foreign['foreign_columns'] === ['name']));
        $this->assertTrue(Schema::hasColumns('posts', ['user_name', 'title', 'votes']));
        $this->assertFalse(Schema::hasIndex('posts', ['title'], 'primary'));
        $this->assertTrue(Schema::hasIndex('posts', ['user_name'], 'unique'));
    }

    #[RequiresDatabase('sqlite')]
    public function testSetJournalModeOnSqlite()
    {
        file_put_contents(DB::connection('sqlite')->getConfig('database'), '');

        $this->assertSame('delete', DB::connection('sqlite')->select('PRAGMA journal_mode')[0]->journal_mode);

        Schema::connection('sqlite')->setJournalMode('WAL');

        $this->assertSame('wal', DB::connection('sqlite')->select('PRAGMA journal_mode')[0]->journal_mode);
    }

    public function testAddingMacros()
    {
        Schema::macro('foo', fn () => 'foo');

        $this->assertEquals('foo', Schema::foo());

        Schema::macro('hasForeignKeyForColumn', function (string $column, string $table, string $foreignTable) {
            return collect(Schema::getForeignKeys($table))
                ->contains(function (array $foreignKey) use ($column, $foreignTable) {
                    return collect($foreignKey['columns'])->contains($column)
                        && $foreignKey['foreign_table'] == $foreignTable;
                });
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('body');
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->string('body');
            $table->foreignId('question_id')->constrained();
        });

        $this->assertTrue(Schema::hasForeignKeyForColumn('question_id', 'answers', 'questions'));
        $this->assertFalse(Schema::hasForeignKeyForColumn('body', 'answers', 'questions'));
    }
}

<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\TestCase;

#[RequiresDatabase('sqlite')]
class DatabaseSchemaBuilderTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'database.connections.sqlite-with-prefix' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => 'example_',
                'prefix_indexes' => false,
            ],
            'database.connections.sqlite-with-indexed-prefix' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => 'example_',
                'prefix_indexes' => true,
            ],
        ]);
    }

    public function testDropAllTablesWorksWithForeignKeys()
    {
        Schema::create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
        });

        Schema::create('table2', function (Blueprint $table) {
            $table->integer('id');
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('table1');
        });

        $this->assertTrue(Schema::hasTable('table1'));
        $this->assertTrue(Schema::hasTable('table2'));

        Schema::dropAllTables();

        $this->assertFalse(Schema::hasTable('table1'));
        $this->assertFalse(Schema::hasTable('table2'));
    }

    public function testHasColumnAndIndexWithPrefixIndexDisabled()
    {
        $connection = DB::connection('sqlite-with-prefix');

        Schema::connection('sqlite-with-prefix')->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $indexes = array_column($connection->getSchemaBuilder()->getIndexes('table1'), 'name');

        $this->assertContains('table1_name_index', $indexes, 'name');
    }

    public function testHasColumnAndIndexWithPrefixIndexEnabled()
    {
        $connection = DB::connection('sqlite-with-indexed-prefix');

        Schema::connection('sqlite-with-indexed-prefix')->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $indexes = array_column($connection->getSchemaBuilder()->getIndexes('table1'), 'name');

        $this->assertContains('example_table1_name_index', $indexes);
    }

    public function testAlterTableAddForeignKeyWithPrefix()
    {
        $schema = Schema::connection('sqlite-with-prefix');

        $schema->create('table1', function (Blueprint $table) {
            $table->id();
        });

        $schema->create('table2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('table1');
        });

        $schema->table('table2', function (Blueprint $table) {
            $table->foreignId('moderator_id')->constrained('table1');
        });

        $foreignKeys = collect($schema->getForeignKeys('table2'));

        $this->assertTrue($foreignKeys->contains(
            fn ($fk) => $fk['foreign_table'] === 'example_table1' &&
                $fk['foreign_columns'] === ['id'] &&
                $fk['columns'] === ['author_id'])
        );

        $this->assertTrue($foreignKeys->contains(
            fn ($fk) => $fk['foreign_table'] === 'example_table1' &&
                $fk['foreign_columns'] === ['id'] &&
                $fk['columns'] === ['moderator_id'])
        );
    }

    public function testAlterTableAddForeignKeyWithExpressionDefault()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->json('flags')->default(new Expression('(JSON_ARRAY())'));
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->constrained('items');
        });

        $this->assertTrue(collect(Schema::getForeignKeys('items'))->contains(
            fn ($fk) => $fk['foreign_table'] === 'items' &&
                $fk['foreign_columns'] === ['id'] &&
                $fk['columns'] === ['item_id']
        ));

        $columns = Schema::getColumns('items');

        $this->assertTrue(collect($columns)->contains(
            fn ($column) => $column['name'] === 'flags' && $column['default'] === 'JSON_ARRAY()'
        ));

        $this->assertTrue(collect($columns)->contains(fn ($column) => $column['name'] === 'item_id' && $column['nullable']));
    }

    public function testGetCheckConstraints()
    {
        DB::statement(<<<SQL
            CREATE TABLE products (
                id INT PRIMARY KEY,
                status VARCHAR(255) CHECK ("status" in ('pending', 'processing', 'shipped', 'delivered')),
                product_name VARCHAR(255) CHECK (LENGTH(product_name) > 0),
                price DECIMAL(8,2) CONSTRAINT positive_price CHECK (price > 0),
                discount_percent INT CHECK (discount_percent >= 0 AND discount_percent <= 100),
                CONSTRAINT price_discount_check CHECK (price * (1 - discount_percent/100.0) > 0)
            )
        SQL);

        $constraints = Schema::getCheckConstraints('products');

        $this->assertCount(5, $constraints);
        $this->assertContains(['name' => null, 'columns' => ['status'], 'definition' => "(\"status\" in ('pending', 'processing', 'shipped', 'delivered'))"], $constraints);
        $this->assertContains(['name' => null, 'columns' => ['product_name'], 'definition' => '(LENGTH(product_name) > 0)'], $constraints);
        $this->assertContains(['name' => 'positive_price', 'columns' => ['price'], 'definition' => '(price > 0)'], $constraints);
        $this->assertContains(['name' => null, 'columns' => ['discount_percent'], 'definition' => '(discount_percent >= 0 AND discount_percent <= 100)'], $constraints);
        $this->assertContains(['name' => 'price_discount_check', 'columns' => ['price', 'discount_percent'], 'definition' => '(price * (1 - discount_percent/100.0) > 0)'], $constraints);
    }
}

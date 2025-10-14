<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once 'Enums.php';

class QueryBuilderUpdateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('example', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->json('payload')->nullable();
        });
    }

    #[DataProvider('jsonValuesDataProvider')]
    #[RequiresDatabase(['sqlite', 'mysql', 'mariadb'])]
    public function testBasicUpdateForJson($column, $given, $expected)
    {
        DB::table('example')->insert([
            ['name' => 'Taylor Otwell', 'title' => 'Mr.'],
        ]);

        DB::table('example')->update([
            $column => $given,
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
            $column => $column === 'payload' ? $this->castAsJson($expected) : $expected,
        ]);
    }

    #[RequiresDatabase(['sqlite', 'mysql', 'mariadb'])]
    public function testSubqueryUpdate()
    {
        DB::table('example')->insert([
            ['name' => 'Taylor Otwell', 'title' => 'Mr.'],
        ]);

        var_dump(DB::table('example')->selectRaw('CONCAT(example.title, example.name)')::class);

        DB::table('example')->update([
            'payload->fullname' => DB::table('example')->selectRaw('CONCAT(title, name)'),
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
            'payload' => $this->castAsJson(['fullname' => 'Mr. Taylor Otwell']),
        ]);
    }

    public static function jsonValuesDataProvider()
    {
        yield ['payload', ['Laravel', 'Founder'], ['Laravel', 'Founder']];
        yield ['payload', collect(['Laravel', 'Founder']), ['Laravel', 'Founder']];
        yield ['status', StringStatus::draft, 'draft'];
    }
}

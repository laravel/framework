<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Database\Fixtures\Enums\StringStatus;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../Database/Fixtures/Enums/Enums.php';

class QueryBuilderUpdateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('example', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->integer('credits')->nullable();
            $table->json('payload')->nullable();
        });

        Schema::create('example_credits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('example_id');
            $table->integer('credits');
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

    #[RequiresDatabase(['sqlite', 'mysql', 'mariadb', 'pgsql'])]
    public function testUpdateJsonPathWithArray()
    {
        DB::table('example')->insert([
            ['name' => 'Taylor Otwell', 'payload' => json_encode(['tags' => [], 'active' => true])],
        ]);

        DB::table('example')->update([
            'payload->tags' => ['Laravel', 'Founder'],
        ]);

        $this->assertSame(
            ['tags' => ['Laravel', 'Founder'], 'active' => true],
            json_decode(DB::table('example')->value('payload'), true)
        );
    }

    #[RequiresDatabase(['sqlite', 'mysql', 'mariadb', 'pgsql'])]
    public function testUpdateJsonPathWithCollection()
    {
        DB::table('example')->insert([
            ['name' => 'Taylor Otwell', 'payload' => json_encode(['tags' => [], 'active' => true])],
        ]);

        DB::table('example')->update([
            'payload->tags' => collect(['Laravel', 'Founder']),
        ]);

        $this->assertSame(
            ['tags' => ['Laravel', 'Founder'], 'active' => true],
            json_decode(DB::table('example')->value('payload'), true)
        );
    }

    #[RequiresDatabase(['sqlite', 'mysql', 'mariadb'])]
    public function testSubqueryUpdate()
    {
        DB::table('example')->insert([
            ['name' => 'Taylor Otwell', 'title' => 'Mr.'],
            ['name' => 'Tim MacDonald', 'title' => 'Mr.'],
        ]);

        DB::table('example_credits')->insert([
            ['example_id' => 1, 'credits' => 10],
            ['example_id' => 1, 'credits' => 20],
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
            'credits' => null,
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Tim MacDonald',
            'title' => 'Mr.',
            'credits' => null,
        ]);

        DB::table('example')->update([
            'credits' => DB::table('example_credits')->selectRaw('sum(credits)')->whereColumn('example_credits.example_id', 'example.id'),
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
            'credits' => 30,
        ]);

        $this->assertDatabaseHas('example', [
            'name' => 'Tim MacDonald',
            'title' => 'Mr.',
            'credits' => null,
        ]);
    }

    public function testUpdateWithOrderAndLimit()
    {
        DB::table('example')->insert([
            ['name' => 'A', 'credits' => 30],
            ['name' => 'B', 'credits' => 10],
            ['name' => 'C', 'credits' => 40],
            ['name' => 'D', 'credits' => 20],
        ]);

        DB::table('example')->orderBy('credits')->limit(2)->update(['title' => 'Low']);

        $this->assertSame(['B', 'D'], DB::table('example')->where('title', 'Low')->orderBy('name')->pluck('name')->all());
    }

    public function testDeleteWithOrderAndLimit()
    {
        DB::table('example')->insert([
            ['name' => 'A', 'credits' => 30],
            ['name' => 'B', 'credits' => 10],
            ['name' => 'C', 'credits' => 40],
            ['name' => 'D', 'credits' => 20],
        ]);

        DB::table('example')->orderBy('credits')->limit(2)->delete();

        $this->assertSame(['A', 'C'], DB::table('example')->orderBy('name')->pluck('name')->all());
    }

    public static function jsonValuesDataProvider()
    {
        yield ['payload', ['Laravel', 'Founder'], ['Laravel', 'Founder']];
        yield ['payload', collect(['Laravel', 'Founder']), ['Laravel', 'Founder']];
        yield ['status', StringStatus::draft, 'draft'];
    }
}

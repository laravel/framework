<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class QueryBuilderUpdateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('example', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->json('payload')->nullable();
        });
    }

    #[DataProvider('jsonValuesDataProvider')]
    public function testBasicUpdateForJson($given, $expected)
    {
        DB::table('example')->insert([
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
        ]);

        DB::table('example')->update(['payload' => $given]);

        $this->assertDatabaseHas('example', [
            'name' => 'Taylor Otwell',
            'title' => 'Mr.',
            'payload' => $expected,
        ]);
    }

    public static function jsonValuesDataProvider()
    {
        yield [['Laravel', 'Founder'], json_encode(['Laravel', 'Founder'], JSON_UNESCAPED_UNICODE)];
        yield [collect(['Laravel', 'Founder']), json_encode(['Laravel', 'Founder'], JSON_UNESCAPED_UNICODE)];
    }
}

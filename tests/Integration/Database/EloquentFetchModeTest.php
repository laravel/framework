<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\Fixtures\Post;
use Illuminate\Tests\Integration\Database\Fixtures\PostStringyKey;
use Illuminate\Tests\Integration\Database\Fixtures\User;
use PHPUnit\Framework\Attributes\DataProvider;

class EloquentFetchModeTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
            $table->timestamps();
        });

        User::create(['name' => 'Taylor', 'title' => 'Manager']);
        User::create(['name' => 'Graham', 'title' => 'Developer-1']);
        User::create(['name' => 'Dries', 'title' => 'Developer-1']);
        User::create(['name' => 'Tetiana', 'title' => 'Developer-2']);
        User::create(['name' => 'Mohamed', 'title' => 'Developer-1']);
        User::create(['name' => 'Lucas', 'title' => 'Developer-2']);
        User::create(['name' => 'Joseph', 'title' => 'Developer-3']);
    }

    public function testPluck(): void
    {
        $this->assertEquals([
            'Taylor' => 'Manager',
            'Graham' => 'Developer-1',
            'Dries' => 'Developer-1',
            'Tetiana' => 'Developer-2',
            'Mohamed' => 'Developer-1',
            'Lucas' => 'Developer-2',
            'Joseph' => 'Developer-3',
        ], User::query()->pluck('title', 'name')->toArray());

        $this->assertEquals([
            'Manager' => 'Taylor',
            'Developer-1' => 'Mohamed',
            'Developer-2' => 'Lucas',
            'Developer-3' => 'Joseph',
        ], User::query()->pluck('name', 'title')->toArray());
    }

    public function testKeyedArray(): void
    {
        $results = User::query()
            ->select(['title', 'title', 'name'])
            ->mode(DB::mode()->keyed())
            ->get();

        $this->assertEquals('Taylor', $results['Manager']->name);
        $this->assertEquals('Mohamed', $results['Developer-1']->name);
        $this->assertEquals('Lucas', $results['Developer-2']->name);
        $this->assertEquals('Joseph', $results['Developer-3']->name);
    }

    public function testUnbufferedCursor(): void
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        $results = User::query()
            ->select(['title', 'name'])
            ->mode(DB::mode()->buffered(false))
            ->cursor();

        $this->assertCount(7, $results->all());
    }

    #[DataProvider('scrollableCursorDataProvider')]
    public function testScrollableCursor(int $nth, array $names): void
    {
        if (! in_array($this->driver, ['pgsql', 'sqlsrv'])) {
            $this->markTestSkipped('Test requires a PostgreSQL or SQL Server connection.');
        }

        $results = User::query()
            ->select(['title', 'title', 'name'])
            ->mode(DB::mode()->scrollableCursor($nth))
            ->cursor()
            // Retrieve all items from the lazy collection so we can assert its contents.
            ->all();

        $this->assertCount(count($names), $results);

        foreach ($results as $i => $result) {
            $this->assertEquals($names[$i], $result->name);
        }
    }

    /**
     * @return array
     */
    public static function scrollableCursorDataProvider()
    {
        return [
            'Every' => [1, ['Taylor', 'Graham', 'Dries', 'Tetiana', 'Mohamed', 'Lucas', 'Joseph']],
            'Every 2nd' => [2, ['Graham','Tetiana','Lucas']],
            'Every 3rd' => [3, ['Dries', 'Lucas']],
        ];
    }
}


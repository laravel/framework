<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentFillMissingAttributesTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
        });
    }

    public function testUpdateMissing()
    {
        $record = TestMissingModel1::create([
            'name' => 'LTKort',
            'title' => null,
        ]);

        $record->updateMissing([
            'name' => 'Marshmallow',
            'title' => 'Mr.',
        ]);

        $this->assertSame('LTKort', $record->name);
        $this->assertNotEmpty($record->title);
    }

    public function testFillMissing()
    {
        $record = TestMissingModel1::create([
            'name' => 'LTKort',
            'title' => null,
        ]);

        $record_filled = $record->fillMissing([
            'name' => 'Marshmallow',
            'title' => 'Mr.',
        ]);

        $this->assertSame($record->name, $record_filled->name);
        $this->assertNotEmpty($record_filled->title);
    }
}

class TestMissingModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
}

<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Tests\Integration\Database\Fixtures\JsonArray;
use Illuminate\Tests\Integration\Database\Fixtures\JsonObject;

class DatabaseSqlServerModelCastJsonTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('json_arrays', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });

        Schema::create('json_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->json('sample_data');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('json_arrays');

        Schema::drop('json_objects');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testModelJsonObjectCastUpdateWithSameArrayDoesNotAffectChanges()
    {
        $sampleData = [
            'aa' => 1,
            'b' => 2,
        ];

        $model = new JsonObject();
        $model->sample_data = $sampleData;
        $model->save();

        $newModel = JsonObject::find(1);
        $newModel->sample_data = $sampleData;
        $newModel->save();

        $this->assertEmpty($newModel->getChanges(), Arr::first($newModel->getChanges()).' is not the expected '.json_encode($sampleData));
    }

    public function testModelJsonArrayCastUpdateWithSameArrayDoesNotAffectChanges()
    {
        $sampleData = [
            'aa' => 1,
            'b' => 2,
        ];

        $model = new JsonArray();
        $model->sample_data = $sampleData;
        $model->save();

        $newModel = JsonArray::find(1);
        $newModel->sample_data = $sampleData;
        $newModel->save();

        $this->assertEmpty($newModel->getChanges(), Arr::first($newModel->getChanges()).' is not the expected '.json_encode($sampleData));
    }
}

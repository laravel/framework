<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentCustomDateCastingRetrivalTest extends MySqlTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id('id');
            $table->date('custom_date');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('test_models');
    }

    public function testCustomDateMatchesGivenDateBeforeSave()
    {
        TestModel::create(['custom_date' => '26.01.2022']);

        $this->assertEquals('26.01.2022', TestModel::first()->custom_date->format('d.m.Y'));
    }
}

class TestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'custom_date' => 'date:d.m.Y'
    ];
}

<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelWithoutEventsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('auto_filled_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('project')->nullable();
        });
    }

    public function testWithoutEventsRegistersBootedListenersForLater()
    {
        $model = AutoFilledModel::withoutEvents(function () {
            return AutoFilledModel::create();
        });

        $this->assertNull($model->project);

        $model->save();

        $this->assertSame('Laravel', $model->project);
    }
}

class AutoFilledModel extends Model
{
    public $table = 'auto_filled_models';
    public $timestamps = false;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->project = 'Laravel';
        });
    }
}

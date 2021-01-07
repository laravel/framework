<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentModelWithoutEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('auto_filled_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('project')->nullable();
            $table->integer('stars')->default(0);
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

    public function testWithoutEventsRegistersBootedListenersForLaterWithName()
    {
        AutoFilledModel::flushEventListeners(['saving']);
        $model = AutoFilledModel::create();
        $this->assertNull($model->project);
        $this->assertSame(1, $model->stars);
    }

    public function testWithoutEventsRegistersBootedListeners()
    {
        AutoFilledModel::flushEventListeners();
        $model = AutoFilledModel::create();
        $this->assertNull($model->project);
        $this->assertNull($model->stars);
    }

    public function testWithoutEventsRegistersBootedListenersWithName()
    {
        AutoFilledModel::flushEventListeners(['created']);
        $model = AutoFilledModel::create();
        $this->assertSame('Laravel', $model->project);
        $this->assertNull($model->stars);
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

        static::created(function ($model) {
            $model->increment('stars');
        });
    }
}

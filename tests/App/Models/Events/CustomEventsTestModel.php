<?php

namespace Illuminate\Tests\App\Models\Events;

use Illuminate\Database\Eloquent\Model;

class CustomEventsTestModel extends Model
{
    public $dispatchesEvents = ['created' => CustomEvent::class];
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
}

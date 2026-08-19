<?php

namespace Illuminate\Tests\App\Models\Events;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(CustomObserver::class)]
class EloquentModelStubWithCustomEventFromTrait extends Model
{
    use CustomEventTrait;

    public $timestamps = false;
}

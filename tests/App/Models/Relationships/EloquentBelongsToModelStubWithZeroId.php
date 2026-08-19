<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentBelongsToModelStubWithZeroId extends Model
{
    public $foreign_key = 0;
}

<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentMorphToModelStub extends Model
{
    public $foreign_key = 'foreign.value';

    public $table = 'eloquent_morph_to_model_stubs';

    public function relation()
    {
        return $this->morphTo();
    }
}

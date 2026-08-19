<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class PivotSerializationTestTagAttachment extends MorphPivot
{
    public $table = 'taggables';

    public $timestamps = false;
}

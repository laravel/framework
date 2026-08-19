<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EloquentMorphToAccessibleStub extends MorphTo
{
    public function callMatchToMorphParents($type, EloquentCollection $results): void
    {
        $this->matchToMorphParents($type, $results);
    }
}

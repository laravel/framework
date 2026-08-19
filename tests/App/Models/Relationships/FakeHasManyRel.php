<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\HasMany;

class FakeHasManyRel extends HasMany
{
    public function __construct()
    {
        //
    }

    public function getResults()
    {
        return ['many' => 'related'];
    }
}

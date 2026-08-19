<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Queue\SerializesModels;

class PivotSerializationTestCollectionClass
{
    use SerializesModels;

    public $pivots;

    public function __construct($pivots)
    {
        $this->pivots = $pivots;
    }
}

<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Queue\SerializesModels;

class PivotSerializationTestClass
{
    use SerializesModels;

    public $pivot;

    public function __construct($pivot)
    {
        $this->pivot = $pivot;
    }
}

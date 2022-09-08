<?php

namespace Illuminate\Support;

use Carbon\CarbonInterval as BaseCarbonInterval;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;

class CarbonInterval extends BaseCarbonInterval implements Arrayable
{
    use Conditionable;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return $this->__toString();
    }
}

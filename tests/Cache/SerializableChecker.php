<?php

namespace Illuminate\Tests\Cache;

use Serializable;

class SerializableChecker implements Serializable
{
    public $wasSerialized = false;

    public function serialize()
    {
        return serialize('');
    }

    public function unserialize($serialized)
    {
        $this->wasSerialized = true;
    }
}

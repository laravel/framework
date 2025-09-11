<?php

namespace Illuminate\Tests\Integration\Cache\Fixtures;

use Exception;

class Unserializable
{
    public function __unserialize(array $data)
    {
        throw new Exception('Not serializable');
    }
}

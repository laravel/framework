<?php

namespace Illuminate\Tests\Integration\Cache\Fixtures;

use Exception;

class Unserializable
{
    public function __serialize()
    {
        throw new Exception('Not serializable');
    }
}

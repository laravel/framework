<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

class DataProvider
{
    public function data($a, $b)
    {
        return ['foo' => $a.$b];
    }
}

<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

class DummyInstanceFactory
{
    /**
     * Make an dummy anonymous instace.
     */
    public static function withName(string $name): object
    {
        return new class($name)
        {
            public function __construct(public readonly string $name)
            {
                //
            }
        };
    }

    public static function withConfig(array $config): object
    {
        return new class($config)
        {
            public function __construct(public readonly array $config)
            {
                //
            }
        };
    }
}

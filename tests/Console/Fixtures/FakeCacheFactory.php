<?php

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Contracts\Cache\Factory;

class FakeCacheFactory implements Factory
{
    /**
     * The last store name that was requested.
     *
     * @var string|null
     */
    public $name;

    /**
     * @param  \Illuminate\Contracts\Cache\Repository|null  $repository
     */
    public function __construct(public $repository = null)
    {
    }

    public function store($name = null)
    {
        $this->name = $name;

        return $this->repository;
    }
}

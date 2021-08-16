<?php

namespace Illuminate\Testing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\Comparators\ModelComparator;
use SebastianBergmann\Comparator\Factory;

class TestingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $comparatorFactory = Factory::getInstance();
        $comparatorFactory->register(new ModelComparator());
    }
}

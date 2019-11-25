<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Observers\DiscoverObservers;
use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Models\ModelOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Models\ModelTwo;
use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverThree;
use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverTwo;
use Orchestra\Testbench\TestCase;

class DiscoverObserversTest extends TestCase
{
    public function testObserversCanBeDiscovered()
    {
        class_alias(ObserverOne::class, 'Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverOne');
        class_alias(ObserverTwo::class, 'Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverTwo');
        class_alias(ObserverThree::class, 'Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers\ObserverThree');

        $observers = DiscoverObservers::within(__DIR__.'/Fixtures/ObserverDiscovery/Observers', getcwd());

        $this->assertEquals([
            ModelOne::class => [
                ObserverOne::class,
            ],
            ModelTwo::class => [
                ObserverTwo::class,
            ],
        ], $observers);
    }
}

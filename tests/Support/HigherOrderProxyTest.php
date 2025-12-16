<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderCollectionProxy;
use Illuminate\Support\HigherOrderTapProxy;
use PHPUnit\Framework\TestCase;

class HigherOrderProxyTest extends TestCase
{
    public function test_get_proxies_property_access_to_items()
    {
        $items = new Collection([
            (object)['name' => 'Alice'],
            (object)['name' => 'Bob'],
        ]);

        $proxy = new HigherOrderCollectionProxy($items, 'pluck');

        // The proxied method returns a Collection instance; assert type and values
        $this->assertInstanceOf(Collection::class, $proxy->name);
        $this->assertEquals(['Alice', 'Bob'], $proxy->name->all());
    }

    public function test_call_proxies_method_call_to_items()
    {
        $items = new Collection([
            new class {
                public function shout($s)
                {
                    return strtoupper($s);
                }
            },
            new class {
                public function shout($s)
                {
                    return strtoupper($s) . '!';
                }
            },
        ]);

        $proxy = new HigherOrderCollectionProxy($items, 'map');

        $result = $proxy->shout('hey');

        $this->assertEquals(['HEY', 'HEY!'], $result->all());
    }

    public function test_call_forwards_and_returns_target()
    {
        $target = new class {
            public $count = 0;

            public function increment($by = 1)
            {
                $this->count += $by;
            }
        };

        $proxy = new HigherOrderTapProxy($target);

        $result = $proxy->increment(3);

        $this->assertSame(3, $target->count);
        $this->assertSame($target, $result);
    }
}

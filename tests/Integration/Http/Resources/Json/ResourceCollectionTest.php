<?php

namespace Illuminate\Tests\Integration\Http\Resources\Json;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Fluent;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ResourceCollectionTest extends TestCase
{
    #[DataProvider('toArrayDataProvider')]
    public function testItCanReturnToArray(ResourceCollection $collection, mixed $expected)
    {
        $request = Request::create('GET', '/');

        $this->assertSame($expected, $collection->toArray($request));
    }

    public static function toArrayDataProvider()
    {
        yield [
            new ResourceCollection([new Fluent(), new Fluent(), new Fluent()]),
            [[], [], []],
        ];

        yield [
            (new ResourceCollection([
                (new User())->forceFill(['name' => 'Taylor Otwell']),
                (new User())->forceFill(['name' => 'Laravel']),
            ]))->additional(['total', 1]),
            [
                ['name' => 'Taylor Otwell'],
                ['name' => 'Laravel'],
            ],
        ];
    }
}

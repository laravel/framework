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
            new ResourceCollection([
                new Fluent(['id' => 1]),
                new Fluent(['id' => 2]),
                new Fluent(['id' => 3]),
            ]),
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
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

        yield [
            new class(['list' => new Fluent(['id' => 1]), 'total' => 1]) extends ResourceCollection {
                public function toArray(Request $request) {
                    return $this->resource->toArray();
                }
            },
            [
                'list' => ['id' => 1],
                'total' => 1,
            ],
        ];
    }
}

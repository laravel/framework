<?php

use Illuminate\Contracts\Support\Arrayable;

use function PHPStan\Testing\assertType;

/**
 * @implements Arrayable<array{id: int, name: string, tags: array<string, string>}>
 */
class ShapedArrayableResponse implements Arrayable
{
    public function toArray(): array
    {
        return [
            'id' => 1,
            'name' => 'Taylor',
            'tags' => ['framework' => 'Laravel'],
        ];
    }
}

assertType('array{id: int, name: string, tags: array<string, string>}', (new ShapedArrayableResponse)->toArray());
